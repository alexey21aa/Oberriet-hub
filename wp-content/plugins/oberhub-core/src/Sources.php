<?php
namespace OberHub;
final class Sources {
    public static function boot(): void { add_action('oh_daily',[self::class,'check']); }
    public static function allowed(string $url): bool {
        $p=wp_parse_url($url); if (!$p || ($p['scheme'] ?? '')!=='https' || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && (int)$p['port']!==443)) { return false; }
        $host=strtolower($p['host'] ?? '');
        $allow=['www.oberriet.ch','oberriet.ch','www.sg.ch','sg.ch','www.hallo.sg.ch','hallo.sg.ch','daten.sg.ch','www.sbb.ch','www.orschulen.ch','orschulen.ch','www.edoeb.admin.ch','www.fedlex.admin.ch','www.ige.ch','www.gesetzessammlung.sg.ch','schalter-e.sg.ch','www.eumzug.swiss','www.h-och.ch','h-och.ch','msor.ch','www.msor.ch','www.ch.ch','ch.ch','www.svasg.ch','svasg.ch'];
        return in_array($host,$allow,true);
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
        // A small fixed daily batch, conditional GET, safe URL checks and NO redirects.
        $posts=get_posts(['post_type'=>'oh_source','numberposts'=>-1,'post_status'=>'publish','orderby'=>'ID','order'=>'ASC']);$n=0;
        $cursor=$force && count($posts) ? absint(get_option('oh_source_cursor',0))%count($posts) : 0;
        if ($cursor) { $posts=array_merge(array_slice($posts,$cursor),array_slice($posts,0,$cursor)); }
        foreach ($posts as $p) {
            $s=get_post_meta($p->ID,'_oh_record',true); if (!is_array($s) || !is_string($s['source_url'] ?? null) || !self::allowed($s['source_url'])) { continue; }
            if (!$force && time()-strtotime($s['last_checked'] ?? '1970-01-01')<max(1,(int)($s['ttl_days'] ?? 30))*DAY_IN_SECONDS) { continue; }
            if ($n>=max(1,min(100,$limit))) { break; } $n++;
            $headers=[]; if (!empty($s['etag'])) { $headers['If-None-Match']=$s['etag']; } if (!empty($s['last_modified'])) { $headers['If-Modified-Since']=$s['last_modified']; }
            $r=wp_safe_remote_get($s['source_url'],['timeout'=>5,'redirection'=>0,'limit_response_size'=>300000,'headers'=>$headers,'user-agent'=>'OberrietHub/0.1 source freshness checker']);
            $s['http_status']=is_wp_error($r) ? 0 : wp_remote_retrieve_response_code($r);$s['last_checked']=gmdate('Y-m-d');
            if ($s['http_status']===200) {
                $hash=hash('sha256',wp_remote_retrieve_body($r));
                if (empty($s['monitor_hash'])) { $s['review_status']='needs_review'; } elseif ($s['monitor_hash']!==$hash) { $s['review_status']='needs_review';$s['last_changed']=gmdate('Y-m-d'); }
                $s['monitor_hash']=$hash;$s['etag']=wp_remote_retrieve_header($r,'etag');$s['last_modified']=wp_remote_retrieve_header($r,'last-modified');
            } elseif ($s['http_status']!==304) { $s['review_status']='needs_review'; }
            update_post_meta($p->ID,'_oh_record',$s);
        }
        if ($force && count($posts)) { update_option('oh_source_cursor',($cursor+$n)%count($posts),false); }
        return $n;
    }
}
