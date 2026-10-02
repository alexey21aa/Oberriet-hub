<?php
namespace OberHub;
require_once __DIR__.'/SourceIngestion.php';
final class Sources {
    public static function boot(): void {
        add_filter('cron_schedules',function($s){$s['oh_ten_minutes']=['interval'=>600,'display'=>'OberHub source refresh'];return $s;});
        add_action('init',function(){if(!wp_next_scheduled('oh_freshness'))wp_schedule_event(time()+120,'oh_ten_minutes','oh_freshness');});
        add_action('oh_freshness',fn()=>self::check_links(8,false));add_action('oh_daily',[self::class,'check']);
        add_action('oh_source_revalidate',function($id){$posts=get_posts(['post_type'=>'oh_source','post_status'=>'publish','meta_key'=>'_oh_id','meta_value'=>$id,'numberposts'=>1]);if($posts)self::refresh($posts[0]->ID,get_post_meta($posts[0]->ID,'_oh_record',true));},10,1);
    }
    public static function ttl(array $source):int {
        $classes=['F0'=>600,'F1'=>3600,'F2'=>86400,'F3'=>604800,'F4'=>2592000,'F5'=>7776000];
        return max(300,min(31536000,(int)($source['refresh_ttl']??$classes[$source['freshness_class']??'']??max(1,(int)($source['ttl_days']??30))*DAY_IN_SECONDS)));
    }
    public static function freshness(array $source,?int $now=null):array {
        $now=$now??time();$checked=strtotime((string)($source['fetched_at']??$source['last_checked']??''));$expires=$checked===false?0:$checked+self::ttl($source);
        $unavailable=in_array($source['fetch_status']??'', ['error','blocked','unavailable'],true);$stale=$expires<=$now||$unavailable;
        return ['freshness_class'=>$source['freshness_class']??'F4','checked_at'=>$source['fetched_at']??$source['last_checked']??null,'expires_at'=>$expires?gmdate('c',$expires):null,'stale'=>$stale,'fetch_status'=>$source['fetch_status']??'unknown','review_status'=>$source['review_status']??'needs_review'];
    }
    public static function allowed(string $url): bool {
        $p=wp_parse_url($url); if (!$p || ($p['scheme'] ?? '')!=='https' || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && (int)$p['port']!==443)) { return false; }
        $host=strtolower($p['host'] ?? '');
        $allow=['www.oberriet.ch','oberriet.ch','www.sg.ch','sg.ch','www.hallo.sg.ch','hallo.sg.ch','daten.sg.ch','www.sbb.ch','www.orschulen.ch','orschulen.ch','www.edoeb.admin.ch','www.fedlex.admin.ch','www.ige.ch','www.gesetzessammlung.sg.ch','schalter-e.sg.ch','www.eumzug.swiss','www.h-och.ch','h-och.ch','msor.ch','www.msor.ch','www.ch.ch','ch.ch','www.svasg.ch','svasg.ch','integrationrheintal.ch','www.integrationrheintal.ch'];
        $extra=get_option('oh_approved_source_hosts',[]);if(is_array($extra))foreach($extra as $approved){if(is_string($approved)&&self::publicHost($approved))$allow[]=strtolower($approved);}
        return self::publicHost($host)&&in_array($host,$allow,true);
    }
    private static function publicHost(string $host):bool {
        return strlen($host)<=253&&preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',$host)&&!preg_match('/(?:^|\.)(?:localhost|local|internal|test|invalid)$/i',$host)&&!filter_var($host,FILTER_VALIDATE_IP);
    }
    /** Explicit admin approval extends public source roots without code changes. */
    public static function approve_host(string $host):bool {
        $host=strtolower(trim($host));if(!current_user_can('manage_options')||!self::publicHost($host))return false;
        $approved=get_option('oh_approved_source_hosts',[]);$approved=is_array($approved)?$approved:[];$approved[]=$host;update_option('oh_approved_source_hosts',array_values(array_unique($approved)),false);return true;
    }
    public static function validate_record($row,string $type): string {
        if (!is_array($row)) { return 'Record must be a JSON object.'; }
        if (empty($row['id']) && empty($row['source_id'])) { return 'Missing stable id.'; }
        if (!is_string($row['id'] ?? $row['source_id']) || !preg_match('/^[a-z0-9-]{1,80}$/',$row['id'] ?? $row['source_id'])) { return 'Invalid stable id.'; }
        foreach (['title','description_short','description_full','instruction','question','answer'] as $field) {
            if (!isset($row[$field]) || is_string($row[$field]) && in_array($type,['contact','place','organization'],true)) { continue; }
            if (!is_array($row[$field])) { return 'Localized field must be an object: '.$field; }
            foreach (['de','en','ru','uk'] as $l) { if (!isset($row[$field][$l]) || !is_string($row[$field][$l]) || mb_strlen($row[$field][$l])>10000) { return 'Invalid translation: '.$field.'/'.$l; } }
        }
        if (!empty($row['email']) && (!is_string($row['email']) || !is_email($row['email']))) { return 'Invalid email.'; }
        if ($type==='source' && (!isset($row['source_url']) || !is_string($row['source_url']) || !self::allowed($row['source_url']))) { return 'Source needs an approved HTTPS URL.'; }
        foreach (['source_url','official_url'] as $f) { if (!empty($row[$f]) && (!is_string($row[$f]) || !self::allowed($row[$f]))) { return 'URL outside approved HTTPS domains: '.$f; } }
        if ($type!=='source' && empty($row['source_id'])) { return 'Source registry link is required.'; }
        if (in_array($type,['service','event'],true)) {
            foreach (['de','en','ru','uk'] as $l) { if (empty($row['title'][$l])) { return 'Missing title: '.$l; } }
        }
        if($type==='source'){
            if(isset($row['freshness_class'])&&!in_array($row['freshness_class'],['F0','F1','F2','F3','F4','F5'],true))return 'Invalid freshness class.';
            if(isset($row['refresh_ttl'])&&(!is_numeric($row['refresh_ttl'])||(int)$row['refresh_ttl']<300||(int)$row['refresh_ttl']>31536000))return 'Invalid source TTL.';
            if(isset($row['fetch_mode'])&&!in_array($row['fetch_mode'],['api','rss','ics','sitemap','html','pdf','manual'],true))return 'Invalid fetch mode.';
            foreach(['allow_paths','deny_paths'] as $key){if(isset($row[$key])){if(!is_array($row[$key]))return 'Source paths must be a list.';foreach($row[$key] as $path){if(!is_string($path)||!str_starts_with($path,'/')||str_contains($path,'..'))return 'Invalid source path.';}}}
            if(!empty($row['base_url'])&&(!self::allowed($row['base_url'])||wp_parse_url($row['base_url'],PHP_URL_HOST)!==wp_parse_url($row['source_url'],PHP_URL_HOST)))return 'Source base URL must use the same approved host.';
        }
        if (isset($row['source_checked_at']) && !self::valid_date($row['source_checked_at'])) { return 'Invalid verification date.'; }
        if ($type==='event' && (empty($row['date']) || !self::valid_date($row['date']))) { return 'Event needs an ISO date.'; }
        return '';
    }
    public static function validate_dataset(array $d): array {
        $errors=[];
        foreach (['services','contacts','events','places','sources','organizations','answers','faqs','guides','intents','aliases','waste'] as $key) { if (isset($d[$key]) && (!is_array($d[$key]) || !array_is_list($d[$key]))) { return ['Collection must be a list: '.$key]; } }
        $known=array_column($d['sources'] ?? [],'source_id');
        if (function_exists(__NAMESPACE__.'\\records')) { $known=array_unique(array_merge($known,array_column(records('source'),'source_id'))); }
        $service_ids=array_column($d['services'] ?? [],'id');
        if (function_exists(__NAMESPACE__.'\\records')) { $service_ids=array_unique(array_merge($service_ids,array_column(records('service'),'id'))); }
        foreach (['services'=>'service','contacts'=>'contact','events'=>'event','places'=>'place','sources'=>'source','organizations'=>'organization','answers'=>'answer','faqs'=>'faq','guides'=>'guide'] as $key=>$type) {
            $seen=[];
            foreach ($d[$key] ?? [] as $row) {
                $err=self::validate_record($row,$type);$id=(string)($row['id'] ?? $row['source_id'] ?? '?');
                if ($err) { $errors[]=$key.'/'.$id.': '.$err; }
                if (isset($seen[$id])) { $errors[]='Duplicate id: '.$key.'/'.$id; } $seen[$id]=true;
                if (isset($row['service_id']) && !in_array($row['service_id'],$service_ids,true)) { $errors[]='Unknown service: '.$key.'/'.$id; }
                if ($type!=='source' && !in_array($row['source_id'] ?? '',$known,true)) { $errors[]='Unknown source: '.$key.'/'.$id; }
            }
        }
        foreach ($d['waste'] ?? [] as $row) {
            $err=self::validate_record($row,'waste'); if ($err) { $errors[]='Waste: '.$err; }
            foreach (['valid_from','valid_to'] as $f) { if (!self::valid_date($row[$f] ?? '')) { $errors[]='Waste needs ISO validity dates.'; } }
            if (!is_array($row['weekday'] ?? null) || !is_array($row['dates'] ?? null)) { $errors[]='Waste needs schedules.'; }
            if (!in_array($row['source_id'] ?? '',$known,true)) { $errors[]='Waste has unknown source.'; }
        }
        foreach ($d['intents'] ?? [] as $intent) {
            if (!in_array($intent['service_id'] ?? $intent['target_id'] ?? $intent['id'] ?? '',$service_ids,true) || !is_array($intent['phrases'] ?? null)) { $errors[]='Invalid intent reference.'; }
            else { foreach ($intent['phrases'] as $phrase) { if (!is_string($phrase) || strlen($phrase)>1000) { $errors[]='Invalid intent phrase.'; } } }
        }
        foreach ($d['aliases'] ?? [] as $alias) { if (!is_array($alias) || !in_array($alias['service_id'] ?? '',$service_ids,true) || !in_array($alias['language'] ?? '',['de','en','ru','uk'],true) || !is_string($alias['phrase'] ?? null) || strlen($alias['phrase'])>1000) { $errors[]='Invalid alias.'; } }
        $count=0; foreach (['services','contacts','events','places','sources','organizations','answers','faqs','guides','intents','aliases'] as $key) { if (isset($d[$key]) && !is_array($d[$key])) { $errors[]='Collection must be an array: '.$key; } $count+=is_array($d[$key] ?? null) ? count($d[$key]) : 0; }
        if ($count>300000) { $errors[]='Import exceeds 300000 record limit.'; }
        return $errors;
    }
    public static function valid_date($value): bool {
        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$value,$m)) { return false; }
        return checkdate((int)$m[2],(int)$m[3],(int)$m[1]);
    }
    public static function check(): void { self::check_links(5,false); Analytics::purge(); }
    public static function check_links(int $limit=25,bool $force=true): int {
        $posts=get_posts(['post_type'=>'oh_source','numberposts'=>-1,'post_status'=>'publish','orderby'=>'ID','order'=>'ASC']);$n=0;$limit=max(1,min(100,$limit));
        // A rotating cursor avoids starvation when many sources on one host are due.
        $cursor=count($posts)?absint(get_option('oh_source_cursor',0))%count($posts):0;$posts=array_merge(array_slice($posts,$cursor),array_slice($posts,0,$cursor));$visited=0;
        foreach($posts as $p){$visited++;$s=get_post_meta($p->ID,'_oh_record',true);if(!is_array($s)||!self::allowed($s['source_url']??''))continue;
            if(!$force&&(!self::freshness($s)['stale']||(int)($s['retry_at']??0)>time()))continue;
            if(self::refresh($p->ID,$s))$n++;if($n>=$limit)break;
        }if(count($posts))update_option('oh_source_cursor',($cursor+$visited)%count($posts),false);self::process_queue(2);return $n;
    }
    private static function domainPermit(string $url,array $source):bool {
        $host=strtolower(wp_parse_url($url,PHP_URL_HOST)??'');$key='oh_domain_'.substr(hash('sha256',$host),0,20);$state=get_transient($key)?:['minute'=>floor(time()/60),'count'=>0,'last'=>0];$now=time();
        if($state['minute']!==floor($now/60))$state=['minute'=>floor($now/60),'count'=>0,'last'=>$state['last']];
        $limit=max(1,min(30,(int)($source['rate_limit']['requests_per_minute']??6)));if($state['count']>=$limit||$now-$state['last']<max(0,(int)($source['rate_limit']['min_interval_seconds']??0)))return false;
        $state['count']++;$state['last']=$now;set_transient($key,$state,120);return true;
    }
    private static function request(string $url,array $headers=[]){return wp_safe_remote_get($url,['timeout'=>6,'redirection'=>0,'limit_response_size'=>300001,'headers'=>$headers,'user-agent'=>'OberrietHub/0.2 (+public civic source freshness; no authentication)']);}
    public static function refresh(int $pid,array $source,bool $ingest=true):bool {
        $url=$source['source_url']??'';if(!self::allowed($url)||(int)($source['retry_at']??0)>time())return false;
        $lock='oh_refresh_lock_'.substr(hash('sha256',$url),0,24);if(get_transient($lock)||!self::domainPermit($url,$source))return false;set_transient($lock,1,30);
        try{$headers=[];if(!empty($source['etag']))$headers['If-None-Match']=$source['etag'];if(!empty($source['last_modified']))$headers['If-Modified-Since']=$source['last_modified'];
            $response=self::request($url,$headers);$result=self::apply_response($source,$response);update_post_meta($pid,'_oh_record',$result);
            if($ingest&&($result['http_status']??0)===200&&($result['fetch_status']??'')==='ok'&&SourceIngestion::permitted($result,$url)&&self::robots($url,$result)){
                $mode=$result['fetch_mode']??'html';$body=wp_remote_retrieve_body($response);if($mode!=='sitemap')SourceIngestion::ingest($result,$url,$body,$mode);
                $pending=SourceIngestion::discover($body,$mode,$url,$result,20);$old=get_option('oh_ingestion_queue',[]);foreach($pending as $child)$old[$child]=['source_id'=>$result['source_id'],'url'=>$child,'depth'=>1];update_option('oh_ingestion_queue',array_slice($old,0,300,true),false);
            }
        }finally{delete_transient($lock);}if($ingest)self::process_queue(1);return true;
    }
    public static function apply_response(array $s,$r,?int $now=null):array {
        $now=$now??time();$code=is_wp_error($r)?0:(int)wp_remote_retrieve_response_code($r);$s['http_status']=$code;$s['last_attempt_at']=gmdate('c',$now);
        $body=is_wp_error($r)?'':wp_remote_retrieve_body($r);$valid=($code===200&&strlen($body)<=300000)||($code===304&&!empty($s['monitor_hash']));
        if($valid){$s['fetch_status']='ok';$s['fetched_at']=gmdate('c',$now);$s['last_checked']=gmdate('c',$now);$s['failure_count']=0;$s['retry_at']=0;
            if($code===200){$hash=hash('sha256',$body);if(empty($s['monitor_hash'])||$s['monitor_hash']!==$hash){$s['review_status']='needs_review';$s['last_changed']=gmdate('c',$now);}$s['monitor_hash']=$hash;$s['etag']=(string)wp_remote_retrieve_header($r,'etag');$s['last_modified']=(string)wp_remote_retrieve_header($r,'last-modified');}
        }else{$s['fetch_status']=in_array($code,[401,403],true)?'blocked':(in_array($code,[404,410],true)?'unavailable':'error');$s['failure_count']=min(12,(int)($s['failure_count']??0)+1);$s['retry_at']=$now+min(86400,300*(2**($s['failure_count']-1)));$retry=is_wp_error($r)?'':wp_remote_retrieve_header($r,'retry-after');if(is_numeric($retry))$s['retry_at']=max($s['retry_at'],$now+min(86400,(int)$retry));$s['review_status']='needs_review';}
        return $s;
    }
    private static function robots(string $url,array $root):bool {
        $host=wp_parse_url($url,PHP_URL_HOST);$key='oh_robots_'.substr(hash('sha256',$host),0,20);$cache=get_transient($key);
        if($cache===false){if(!self::domainPermit($url,$root))return false;$r=self::request('https://'.$host.'/robots.txt');$code=is_wp_error($r)?0:wp_remote_retrieve_response_code($r);$cache=['allowed'=>$code===404||$code===200,'body'=>$code===200?wp_remote_retrieve_body($r):''];set_transient($key,$cache,3600);}
        return $cache['allowed']&&SourceIngestion::robotsAllowed($cache['body'],$url);
    }
    public static function process_queue(int $limit=2):int {
        $queue=get_option('oh_ingestion_queue',[]);$n=0;foreach($queue as $url=>$item){if((int)($item['retry_at']??0)>time())continue;if($n>=max(1,min(5,$limit)))break;$posts=get_posts(['post_type'=>'oh_source','post_status'=>'publish','meta_key'=>'_oh_id','meta_value'=>$item['source_id'],'numberposts'=>1]);if(!$posts){unset($queue[$url]);continue;}$root=get_post_meta($posts[0]->ID,'_oh_record',true);
            if(!SourceIngestion::permitted($root,$url)||!self::robots($url,$root)){unset($queue[$url]);continue;}if(!self::domainPermit($url,$root))continue;$r=self::request($url);$n++;if(!is_wp_error($r)&&wp_remote_retrieve_response_code($r)===200&&strlen(wp_remote_retrieve_body($r))<=300000){$path=wp_parse_url($url,PHP_URL_PATH)??'';$mode=str_ends_with(strtolower($path),'.pdf')?'pdf':(str_ends_with(strtolower($path),'.xml')?'sitemap':'html');if($mode!=='sitemap')SourceIngestion::ingest($root,$url,wp_remote_retrieve_body($r),$mode);
                if($mode==='sitemap'&&(int)$item['depth']<max(1,min(2,(int)($root['crawl_depth']??1))))foreach(SourceIngestion::discover(wp_remote_retrieve_body($r),$mode,$url,$root,20) as $child)$queue[$child]=['source_id'=>$item['source_id'],'url'=>$child,'depth'=>$item['depth']+1];unset($queue[$url]);
            }else{$item['attempts']=(int)($item['attempts']??0)+1;$item['retry_at']=time()+min(86400,300*2**min(8,$item['attempts']));if($item['attempts']>=5)unset($queue[$url]);else $queue[$url]=$item;}
        }update_option('oh_ingestion_queue',array_slice($queue,0,300,true),false);return $n;
    }
    public static function evidence(array $record,bool $sensitive=false,bool $allowRefresh=true):array {
        $posts=get_posts(['post_type'=>'oh_source','post_status'=>'publish','meta_key'=>'_oh_id','meta_value'=>$record['source_id']??'','numberposts'=>1]);$source=$posts?get_post_meta($posts[0]->ID,'_oh_record',true):[];$source=is_array($source)?$source:[];$state=self::freshness($source);
        if($state['stale']&&$posts&&$allowRefresh){if($sensitive){self::refresh($posts[0]->ID,$source,false);$source=get_post_meta($posts[0]->ID,'_oh_record',true);$state=self::freshness($source);}elseif(!wp_next_scheduled('oh_source_revalidate',[$record['source_id']]))wp_schedule_single_event(time()+10,'oh_source_revalidate',[$record['source_id']]);}
        $confidence=$state['stale']||($source['review_status']??'')!=='checked'?'low':(in_array($record['verification_scope']??'', ['page','page-subscenario','structured-fact'],true)&&($record['status']??'')==='checked'?'high':'medium');
        return $state+['source_url'=>$record['source_url']??$source['source_url']??null,'confidence'=>$confidence,'requires_confirmation'=>$confidence==='low','content_reviewed_at'=>$source['reviewed_at']??$record['source_checked_at']??null];
    }
}
