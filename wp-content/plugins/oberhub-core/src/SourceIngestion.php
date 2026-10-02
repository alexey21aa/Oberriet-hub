<?php
namespace OberHub;
/** Bounded public-document ingestion. Extracted text is evidence, never a reviewed fact. */
final class SourceIngestion {
    public static function permitted(array $root,string $url):bool {
        if(!Sources::allowed($url)||empty($root['index_for_ai'])||empty($root['license_or_reuse_note'])||!empty($root['contains_personal_data_risk']))return false;
        $base=wp_parse_url($root['base_url']??$root['source_url']??'');$p=wp_parse_url($url);
        if(!$base||strtolower($base['host']??'')!==strtolower($p['host']??''))return false;
        $decoded=rawurldecode($p['path']??'/');$segments=[];foreach(explode('/',$decoded) as $segment){if($segment==='..')array_pop($segments);elseif($segment!=='.'&&$segment!=='')$segments[]=$segment;}$path='/'.implode('/',$segments);if(preg_match('~(?:^|/)(?:login|wp-admin|wp-login|account|profil|myaccount|kundenportal)(?:[./]|$)~i',$path))return false;
        foreach($root['deny_paths']??[] as $deny)if(str_starts_with($path,$deny))return false;
        foreach($root['allow_paths']??['/'] as $allow)if(str_starts_with($path,$allow))return true;
        return false;
    }
    public static function absolute(string $base,string $ref):string {
        $ref=trim(html_entity_decode($ref,ENT_QUOTES|ENT_HTML5,'UTF-8'));if($ref===''||str_starts_with($ref,'#')||preg_match('~^(mailto|tel|javascript|data):~i',$ref))return '';
        if(preg_match('~^https://~i',$ref))return preg_replace('/#.*$/','',$ref);$b=wp_parse_url($base);if(!$b)return '';$origin='https://'.($b['host']??'');if(str_starts_with($ref,'//'))return 'https:'.$ref;
        $path=str_starts_with($ref,'/')?$ref:preg_replace('~/[^/]*$~','/',$b['path']??'/').$ref;$parts=[];foreach(explode('/',$path) as $part){if($part==='..')array_pop($parts);elseif($part!=='.'&&$part!=='')$parts[]=$part;}return preg_replace('/#.*$/','',$origin.'/'.implode('/',$parts));
    }
    public static function discover(string $body,string $mode,string $base,array $root,int $limit=20):array {
        $urls=[];$limit=max(1,min(50,$limit));
        if(in_array($mode,['sitemap','rss'],true)&&class_exists('DOMDocument')){$dom=new \DOMDocument();$old=libxml_use_internal_errors(true);$ok=$dom->loadXML($body,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($old);if($ok){foreach($dom->getElementsByTagName($mode==='sitemap'?'loc':'link') as $node){$ref=$node->getAttribute('href')?:trim($node->textContent);$url=self::absolute($base,$ref);if(self::permitted($root,$url))$urls[$url]=true;if(count($urls)>=$limit)break;}}}
        elseif($mode==='html'&&class_exists('DOMDocument')){$dom=self::html($body);if($dom){foreach($dom->getElementsByTagName('a') as $node){$url=self::absolute($base,$node->getAttribute('href'));if(self::permitted($root,$url))$urls[$url]=true;if(count($urls)>=$limit)break;}}}
        return array_keys($urls);
    }
    private static function html(string $body):?\DOMDocument {$dom=new \DOMDocument();$old=libxml_use_internal_errors(true);$ok=$dom->loadHTML('<?xml encoding="UTF-8">'.$body,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);libxml_clear_errors();libxml_use_internal_errors($old);return $ok?$dom:null;}
    private static function text(string $v,int $limit=500):string {$v=trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($v),ENT_QUOTES|ENT_HTML5,'UTF-8')));return function_exists('mb_substr')?mb_substr($v,0,$limit,'UTF-8'):substr($v,0,$limit);}
    public static function extract(string $body,string $mode,string $url,array $root):array {
        $title='';$snippet='';$status='metadata';$items=[];
        if($mode==='html'&&class_exists('DOMDocument')){$dom=self::html($body);if($dom){$xpath=new \DOMXPath($dom);foreach($xpath->query('//script|//style|//nav|//footer|//header|//form|//aside') as $node)$node->parentNode?->removeChild($node);$title=self::text($xpath->evaluate('string((//h1)[1])')?:$xpath->evaluate('string((//title)[1])'),180);$description=$xpath->query('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]')->item(0);$main=$xpath->query('//main|//article')->item(0);$snippet=self::text($description?->getAttribute('content')?:($main?->textContent??''));$status=$snippet!==''?'snippet':'metadata';}}
        elseif($mode==='api'){$decoded=json_decode($body,true);if(is_array($decoded)){if(isset($decoded['title']))$items=[$decoded];else $items=$decoded['items']??$decoded['results']??(array_is_list($decoded)?$decoded:[]);$items=array_slice($items,0,25);}}
        elseif($mode==='rss'&&class_exists('DOMDocument')){$dom=new \DOMDocument();$old=libxml_use_internal_errors(true);$ok=$dom->loadXML($body,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($old);if($ok){foreach($dom->getElementsByTagName('item') as $node){$item=[];foreach(['title','description','link','pubDate'] as $tag)$item[$tag]=$node->getElementsByTagName($tag)->item(0)?->textContent??'';$items[]=$item;if(count($items)>=25)break;}}}
        elseif($mode==='ics'){ $unfold=preg_replace('/\r?\n[ \t]/','',$body);preg_match_all('/BEGIN:VEVENT\r?\n(.*?)END:VEVENT/s',$unfold,$matches);foreach(array_slice($matches[1],0,25) as $event){$item=[];foreach(['SUMMARY'=>'title','DESCRIPTION'=>'description','URL'=>'link','DTSTART'=>'date'] as $field=>$key){if(preg_match('/^'.$field.'(?:;[^:]*)?:(.*)$/m',$event,$m))$item[$key]=str_replace(['\\n','\\,','\\;'],["\n",',',';'],trim($m[1]));}$items[]=$item;}}
        elseif($mode==='pdf'){$extracted=apply_filters('oh_pdf_extract_text','',$body,$url);if(is_string($extracted)&&$extracted!==''){$snippet=self::text($extracted);$status='snippet';}else{$status='pdf-extractor-unavailable';}}
        if(!$items)$items=[['title'=>$title?:($root['name']??$root['authority']??basename(wp_parse_url($url,PHP_URL_PATH)?:$url)),'description'=>$snippet]];
        $docs=[];foreach($items as $item){if(!is_array($item))continue;$documentUrl=self::absolute($url,(string)($item['link']??$item['url']??$url));if(!self::permitted($root,$documentUrl))continue;$heading=self::text(is_string($item['title']??null)?$item['title']:'',180);$summary=self::text((string)($item['description']??$item['summary']??''));if($heading==='')continue;$localized=array_fill_keys(['de','en','ru','uk'],$heading);$descriptions=array_fill_keys(['de','en','ru','uk'],!empty($root['metadata_only'])?'':$summary);
            $docs[]=['id'=>'doc-'.substr(hash('sha256',$documentUrl.'|'.$heading),0,28),'title'=>$localized,'description_short'=>$descriptions,'source_id'=>$root['source_id']??$root['id'],'source_url'=>$documentUrl,'official_url'=>$documentUrl,'source_checked_at'=>gmdate('Y-m-d'),'checked_at_utc'=>gmdate('c'),'source_trust_level'=>$root['trust_level']??'B','locality'=>$root['locality']??'all','topic'=>$root['source_category']??'administration','source_language'=>$root['languages'][0]??'de','translation_status'=>array_fill_keys(['de','en','ru','uk'],'original-language'),'verification_scope'=>'automatic-snippet','status'=>'unreviewed','extraction_status'=>!empty($root['metadata_only'])?'metadata':$status,'content_hash'=>hash('sha256',$summary),'event_start_raw'=>self::text((string)($item['date']??''),40)];
        }return $docs;
    }
    public static function robotsAllowed(string $robots,string $url,string $agent='OberrietHub'):bool {
        $groups=[];$agents=[];$rules=[];$seenRules=false;foreach(preg_split('/\r?\n/',$robots) as $line){$line=trim(preg_replace('/#.*$/','',$line));if(!preg_match('/^(user-agent|allow|disallow)\s*:\s*(.*)$/i',$line,$m))continue;$name=strtolower($m[1]);$value=trim($m[2]);if($name==='user-agent'){if($seenRules){$groups[]=[$agents,$rules];$agents=[];$rules=[];$seenRules=false;}$agents[]=strtolower($value);}elseif($agents){$rules[]=[$name,$value];$seenRules=true;}}if($agents)$groups[]=[$agents,$rules];$selected=[];$specific=[];foreach($groups as [$agents,$rules]){if(in_array(strtolower($agent),$agents,true))$specific=array_merge($specific,$rules);elseif(in_array('*',$agents,true))$selected=array_merge($selected,$rules);}if($specific)$selected=$specific;$path=wp_parse_url($url,PHP_URL_PATH)?:'/';$best=-1;$allowed=true;foreach($selected as [$kind,$pattern]){if($pattern==='')continue;$regex='~^'.str_replace(['\\*','\\$'],['.*','$'],preg_quote($pattern,'~')).'~';if(preg_match($regex,$path)&&strlen($pattern)>=$best){if(strlen($pattern)===$best&&$kind==='disallow')continue;$best=strlen($pattern);$allowed=$kind==='allow';}}return $allowed;
    }
    public static function ingest(array $root,string $url,string $body,string $mode):int {
        if(!self::permitted($root,$url))return 0;$extracted=self::extract($body,$mode,$url,$root);$docs=get_option('oh_documents',[]);$map=[];foreach($docs as $doc)$map[$doc['id']]=$doc;foreach($map as $id=>$doc){if(($doc['source_url']??'')===$url)unset($map[$id]);}foreach($extracted as $doc)$map[$doc['id']]=$doc;
        // Replace changed pages rather than accumulating old snippets. Keep bounded storage.
        $result=array_slice(array_values($map),-2000);update_option('oh_documents',$result,false);update_option('oh_knowledge_dirty',1,false);return count($extracted);
    }
}
