<?php
// Include after WordPress boot; report alongside the existing runtime checks.
$searchCheck=function($name,$ok)use(&$r){$r[]=['name'=>$name,'passed'=>(bool)$ok];};
foreach([['Wohnsitzbestätigung','de','23627'],['переезл','ru','23620'],['фонарь не работает','ru','lighting']] as [$q,$lang,$id]){
    $result=\OberHub\Knowledge::search($q,$lang);
    $searchCheck('Indexed PHP search '.$q,($result['results'][0]['record']['id']??null)===$id);
}
$searchCheck('Indexed PHP unknown query returns no invented cards',\OberHub\Knowledge::search('zyxqv987zzblorf')['total']===0);
$searchCheck('Indexed PHP Unicode normalization',\OberHub\Knowledge::normalize('STRAßE Zürich Ёж')==='strasse zurich еж');
$searchCheck('Indexed PHP transposition',\OberHub\Knowledge::distance('register','regsiter')===1);
$request=new WP_REST_Request('GET','/oberhub/v1/search');$request->set_param('q','Wohnsitzbestätigung');$request->set_param('lang','de');$response=rest_do_request($request);$data=$response->get_data();
$searchCheck('Public REST indexed search',!$response->is_error()&&($data['results'][0]['record']['id']??null)==='23627');
$invalid=new WP_REST_Request('GET','/oberhub/v1/search');$invalid->set_param('q',str_repeat('a',601));$denied=rest_do_request($invalid);$searchCheck('Search rejects excessive query', $denied->get_status()===400);
