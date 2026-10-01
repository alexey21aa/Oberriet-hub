<?php
namespace OberHub;
final class Sources {
    public static function boot(): void { add_action('oh_daily',[self::class,'check']); }
    public static function allowed(string $url): bool {
        $p=wp_parse_url($url); if (!$p || ($p['scheme'] ?? '')!=='https' || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && (int)$p['port']!==443)) { return false; }
        $host=strtolower($p['host'] ?? '');
        $allow=['www.oberriet.ch','oberriet.ch','www.sg.ch','sg.ch','www.hallo.sg.ch','hallo.sg.ch','daten.sg.ch','www.sbb.ch','www.orschulen.ch','orschulen.ch','www.edoeb.admin.ch','www.fedlex.admin.ch','www.ige.ch','www.gesetzessammlung.sg.ch','schalter-e.sg.ch','www.eumzug.swiss','www.h-och.ch','h-och.ch','msor.ch','www.msor.ch'];
        return in_array($host,$allow,true);
    }
    public static function validate_record($row,string $type): string {
        if (!is_array($row)) { return 'Record must be a JSON object.'; }
        if (empty($row['id']) && empty($row['source_id'])) { return 'Missing stable id.'; }
        if (!preg_match('/^[a-z0-9-]{1,80}$/',(string)($row['id'] ?? $row['source_id']))) { return 'Invalid stable id.'; }
        foreach (['title','description_short','description_full','instruction'] as $field) {
            if (!isset($row[$field]) || is_string($row[$field]) && in_array($type,['contact','place'],true)) { continue; }
            if (!is_array($row[$field])) { return 'Localized field must be an object: '.$field; }
            foreach (['de','en','ru','uk'] as $l) { if (!isset($row[$field][$l]) || !is_string($row[$field][$l]) || mb_strlen($row[$field][$l])>10000) { return 'Invalid translation: '.$field.'/'.$l; } }
        }
        if (!empty($row['email']) && !is_email($row['email'])) { return 'Invalid email.'; }
        if ($type==='source' && (!isset($row['source_url']) || !self::allowed($row['source_url']))) { return 'Source needs an approved HTTPS URL.'; }
        foreach (['source_url','official_url'] as $f) { if (!empty($row[$f]) && !self::allowed($row[$f])) { return 'URL outside approved HTTPS domains: '.$f; } }
        if ($type!=='source' && empty($row['source_id'])) { return 'Source registry link is required.'; }
        if (in_array($type,['service','event'],true)) {
            foreach (['de','en','ru','uk'] as $l) { if (empty($row['title'][$l])) { return 'Missing title: '.$l; } }
        }
        if (isset($row['source_checked_at']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$row['source_checked_at'])) { return 'Invalid verification date.'; }
        if ($type==='event' && (empty($row['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$row['date']))) { return 'Event needs an ISO date.'; }
        return '';
    }
    public static function validate_dataset(array $d): array {
        $errors=[]; $known=array_column($d['sources'] ?? [],'source_id');
        foreach (['services'=>'service','contacts'=>'contact','events'=>'event','places'=>'place','sources'=>'source'] as $key=>$type) {
            $seen=[];
            foreach ($d[$key] ?? [] as $row) {
                $err=self::validate_record($row,$type);$id=(string)($row['id'] ?? $row['source_id'] ?? '?');
                if ($err) { $errors[]=$key.'/'.$id.': '.$err; }
                if (isset($seen[$id])) { $errors[]='Duplicate id: '.$key.'/'.$id; } $seen[$id]=true;
                if ($type!=='source' && !in_array($row['source_id'] ?? '',$known,true)) { $errors[]='Unknown source: '.$key.'/'.$id; }
            }
        }
        foreach ($d['waste'] ?? [] as $row) {
            $err=self::validate_record($row,'waste'); if ($err) { $errors[]='Waste: '.$err; }
            foreach (['valid_from','valid_to'] as $f) { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$row[$f] ?? '')) { $errors[]='Waste needs ISO validity dates.'; } }
            if (!is_array($row['weekday'] ?? null) || !is_array($row['dates'] ?? null)) { $errors[]='Waste needs schedules.'; }
            if (!in_array($row['source_id'] ?? '',$known,true)) { $errors[]='Waste has unknown source.'; }
        }
        foreach ($d['intents'] ?? [] as $intent) {
            if (!in_array($intent['id'] ?? '',array_column($d['services'] ?? [],'id'),true) || !is_array($intent['phrases'] ?? null)) { $errors[]='Invalid intent reference.'; }
            else { foreach ($intent['phrases'] as $phrase) { if (!is_string($phrase) || strlen($phrase)>1000) { $errors[]='Invalid intent phrase.'; } } }
        }
        if (count($d['services'] ?? [])>5000) { $errors[]='Import exceeds record limit.'; }
        return $errors;
    }
    public static function check(): void {
        // A small fixed daily batch, conditional GET, safe URL checks and NO redirects.
        $posts=get_posts(['post_type'=>'oh_source','numberposts'=>-1,'post_status'=>'publish']);$n=0;
        foreach ($posts as $p) {
            $s=get_post_meta($p->ID,'_oh_record',true); if (!is_array($s) || !self::allowed($s['source_url'] ?? '')) { continue; }
            if (time()-strtotime($s['last_checked'] ?? '1970-01-01')<max(1,(int)($s['ttl_days'] ?? 30))*DAY_IN_SECONDS) { continue; }
            if (++$n>5) { break; }
            $headers=[]; if (!empty($s['etag'])) { $headers['If-None-Match']=$s['etag']; } if (!empty($s['last_modified'])) { $headers['If-Modified-Since']=$s['last_modified']; }
            $r=wp_safe_remote_get($s['source_url'],['timeout'=>10,'redirection'=>0,'limit_response_size'=>300000,'headers'=>$headers,'user-agent'=>'OberrietHub/0.1 source freshness checker']);
            $s['http_status']=is_wp_error($r) ? 0 : wp_remote_retrieve_response_code($r);$s['last_checked']=gmdate('Y-m-d');
            if ($s['http_status']===200) {
                $hash=hash('sha256',wp_remote_retrieve_body($r));
                if (empty($s['monitor_hash'])) { $s['review_status']='needs_review'; } elseif ($s['monitor_hash']!==$hash) { $s['review_status']='needs_review';$s['last_changed']=gmdate('Y-m-d'); }
                $s['monitor_hash']=$hash;$s['etag']=wp_remote_retrieve_header($r,'etag');$s['last_modified']=wp_remote_retrieve_header($r,'last-modified');
            } elseif ($s['http_status']!==304) { $s['review_status']='needs_review'; }
            update_post_meta($p->ID,'_oh_record',$s);
        }
        Analytics::purge();
    }
}
