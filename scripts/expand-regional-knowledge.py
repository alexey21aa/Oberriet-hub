#!/usr/bin/env python3
"""Incremental primary-source expansion; factual entities are not alias counters.
Requires a live, successfully verified manifest from harvest-regional.py.
Preserves all baseline services, events, waste, answers, intents and settings.
"""
import json,hashlib,re,html
from pathlib import Path
from datetime import datetime,timezone
ROOT=Path(__file__).resolve().parents[1];P=ROOT/'data/expansion';LANGS=('de','en','uk','ru')
def load(path):return json.loads(path.read_text())
def write(path,obj):
 tmp=path.with_suffix(path.suffix+'.tmp');tmp.write_text(json.dumps(obj,ensure_ascii=False,separators=(',',':'))+'\n');tmp.replace(path)
def upsert(rows,record,key='id'):
 for index,item in enumerate(rows):
  if item[key]==record[key]:rows[index]=record;return
 rows.append(record)
def main():
 manifest=load(P/'regional-evidence/manifest.json');evidence={e['path']:e for e in manifest};now=datetime.now(timezone.utc)
 assert len(manifest)==8
 for e in manifest:
  assert e['status']==200
  assert (now-datetime.fromisoformat(e['last_checked'].replace('Z','+00:00'))).total_seconds()<86400,'Reverify sources before regenerating mutable contacts/schedules.'
  raw=(P/'regional-evidence'/(e['path']+'.html')).read_bytes();assert hashlib.sha256(raw).hexdigest()==e['sha256']
 seed=load(ROOT/'data/seed.json');original_events=seed['events'];original_waste=seed['waste'];newservices=[];newcontacts=[];neworgs=[];sources=[]
 for e in manifest:
  personal=e['path'] in ('offene-sprechstunde','wie-mir-der-schnabel-waechst');dynamic=e['path'] in ('offene-sprechstunde','kontakt')
  source={'source_id':'s-'+hashlib.sha256(e['url'].encode()).hexdigest()[:12],'source_url':e['url'],'source_domain':'integrationrheintal.ch','source_type':'official-provider-web','authority':'Fachstelle Integration Rheintal / Verein St.Galler Rheintal','license_or_reuse_note':'Original concise factual summaries and public professional contact metadata only. No copied articles, photographs or personal correspondence.','last_checked':e['last_checked'],'fetched_at':e['last_checked'],'reviewed_at':e['last_checked'],'last_changed':None,'http_status':200,'content_hash':e['sha256'],'review_status':'checked','fetch_status':'current','trust_level':'B','ttl_days':7 if dynamic else 30,'freshness_class':'F3' if dynamic else 'F4','refresh_ttl':604800 if dynamic else 2592000,'fetch_mode':'html','allow_paths':['/'+e['path']+'/'],'deny_paths':['/wp-admin/','/wp-login.php'],'crawl_depth':1,'rate_limit':{'requests_per_minute':6,'min_interval_seconds':10},'index_for_ai':not personal,'contains_personal_data_risk':personal,'verification_scope':'primary-provider-page','resolved_url':e['resolved_url']}
  sources.append(source);upsert(seed['sources'],source,'source_id');e['source']=source
 def provenance(path):
  e=evidence[path];return {'source_id':e['source']['source_id'],'source_url':e['url'],'official_url':e['url'],'source_checked_at':e['last_checked'][:10],'source_updated_at':None,'checked_at_utc':e['last_checked'],'status':'checked','trust_level':'B','verification_scope':'primary-provider-page'}
 for c in load(P/'regional-contacts.json'):
  body=html.unescape((P/'regional-evidence'/(c['path']+'.html')).read_text())
  assert c['email'].lower() in body.lower(),c['email']
  localphone=re.sub('[^0-9]','',c['phone'].replace('+41','0'));assert localphone in re.sub('[^0-9]','',html.unescape(re.sub('<[^>]+>',' ',body))),c['phone']
  professional={k:v for k,v in c.items() if k!='path'};professional.update(provenance(c['path']));professional.update({'locality':'all','contact_scope':'published-professional-contact','contains_personal_data_risk':c['id']!='regional-desk','index_for_ai':c['id']=='regional-desk'})
  newcontacts.append(professional);upsert(seed['contacts'],professional)
 org={'id':'regional-integration-office','title':'Fachstelle Integration Rheintal','phone':'+41 71 722 95 54','email':'info@rheintal-integration.com','address':'Alte Landstrasse 106, 9445 Rebstein','entity_type':'regional-provider','contact_ids':['regional-desk'],'locality':'all',**provenance('kontakt')};neworgs.append(org);upsert(seed['organizations'],org)
 def make_service(id,title,summary,path,topic,locality='all',contact='regional-desk',synonyms=None,extra=None):
  e=evidence[path];record={'id':id,'slug':id,'title':title,'description_short':summary,'description_full':summary,'topic':topic,'subtopic':'regional-integration','locality':locality,'authority':'Fachstelle Integration Rheintal','eligibility':None,'requirements':None,'documents':None,'fee':None,'processing_time':None,'online_available':None,'contact':contact,'source_authority':'Fachstelle Integration Rheintal','source_trust_level':'B','translation_status':{'en':'draft','uk':'draft','ru':'draft'},'keywords':' '.join(title.values()),'synonyms':synonyms or [],'next_action':{'de':'Aktuelle Angaben beim Anbieter prüfen und über den veröffentlichten Arbeitskontakt nachfragen.','en':'Check current provider information and ask the published professional contact.','uk':'Перевірте актуальну інформацію організатора й зверніться до опублікованого робочого контакту.','ru':'Проверьте актуальную информацию организатора и обратитесь к опубликованному рабочему контакту.'},**provenance(path),**(extra or {})};newservices.append(record);upsert(seed['services'],record)
 for loc in load(P/'regional-locations.json'):
  ids=list(dict.fromkeys(s['contact_id'] for s in loc['sessions']));sid='regional-consultation-'+loc['id'];title={'de':'Kostenlose Offene Sprechstunde: '+loc['city'],'en':'Free multilingual advice: '+loc['city'],'uk':'Безкоштовна багатомовна консультація: '+loc['city'],'ru':'Бесплатная многоязычная консультация: '+loc['city']}
  summary={'de':f"Hilfe bei Formularen, Briefen und Terminen in {loc['venue']}. Kostenlos; die veröffentlichten Sprachzeiten und Arbeitskontakte stehen unten. Vor Besuch aktuelle Zeiten prüfen.",'en':f"Help with forms, letters and appointments at {loc['venue']}. Free; published language sessions and professional contacts are listed below. Confirm current hours before visiting.",'uk':f"Допомога з формами, листами й записом на прийом у {loc['venue']}. Безкоштовно; опубліковані мовні години та робочі контакти наведено нижче. Перед відвідуванням уточніть час.",'ru':f"Помощь с формами, письмами и записью на приём в {loc['venue']}. Бесплатно; опубликованные языковые часы и рабочие контакты приведены ниже. Перед посещением уточните время."}
  extra={'fee':'CHF 0','address':loc['address'],'regional_city':loc['city'],'organization_id':'regional-consultation-office-'+loc['id'],'contact_ids':ids,'weekly_sessions':loc['sessions'],'schedule_timezone':'Europe/Zurich','schedule_exceptions':None,'schedule_confirmation_required':True}
  if loc['id']=='oberriet':extra['source_conflicts']=[{'conflicting_source_url':'https://www.hallo.sg.ch/de/beratung-kontakte/beratungsstellen.html','field':'weekly_sessions','resolution':'Use the live primary provider schedule. Secondary directory previously showed Tuesday 16:00–19:00; primary page lists Wednesday 08:00–11:00.','observed_at':evidence['offene-sprechstunde']['last_checked']}]
  make_service(sid,title,summary,'offene-sprechstunde','health-social',loc['locality'],ids[0],[f"Schreibdienst {loc['city']}",f"help with letters {loc['city']}",f"допомога з листами {loc['city']}",f"помощь с письмами {loc['city']}"],extra)
  office={'id':extra['organization_id'],'title':loc['venue']+' / Offene Sprechstunde '+loc['city'],'address':loc['address'],'entity_type':'consultation-location','parent_organization_id':'regional-integration-office','contact_ids':ids,'service_ids':[sid],'locality':loc['locality'],'regional_city':loc['city'],'weekly_sessions':loc['sessions'],**provenance('offene-sprechstunde')};neworgs.append(office);upsert(seed['organizations'],office)
 for programme in load(P/'regional-programmes.json'):
  extra={'fee':programme['fee']} if programme.get('fee') else {}
  contact='regional-ursula-stadlmueller' if programme['id']=='regional-home-language-support' else 'regional-desk'
  make_service(programme['id'],programme['title'],programme['summary'],programme['path'],programme['topic'],contact=contact,synonyms=programme['synonyms'],extra=extra)
 for service in newservices:
  sid=service['id'];iid='intent-'+sid+'-overview';aid='answer-'+sid+'-overview';questions={'de':'Wie nutze ich '+service['title']['de']+'?','en':'How can I use '+service['title']['en']+'?','uk':'Як скористатися: '+service['title']['uk']+'?','ru':'Как воспользоваться: '+service['title']['ru']+'?'}
  answer={'id':aid,'service_id':sid,'kind':'overview','title':questions,'question':questions,'answer':dict(service['description_full']),'source_id':service['source_id'],'source_url':service['source_url'],'verification_scope':'primary-provider-page','source_checked_at':service['source_checked_at']}
  # The time is part of the answer, so a visitor can act without hidden JSON.
  if service.get('weekly_sessions'):
   days={'de':['Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'],'en':['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],'uk':['Понеділок','Вівторок','Середа','Четвер','П’ятниця','Субота','Неділя'],'ru':['Понедельник','Вторник','Среда','Четверг','Пятница','Суббота','Воскресенье']}
   for language in LANGS:
    sessions=['%s %s–%s (%s)'%(days[language][s['weekday']-1],s['start'],s['end'],' / '.join(s['languages']).upper()) for s in service['weekly_sessions']]
    c=next(c for c in newcontacts if c['id']==service['contact']);answer['answer'][language]+=' '+service['address']+'. '+'; '.join(sessions)+'. '+c['title']+': '+c['phone']+', '+c['email']+'.'
  service['description_full']=dict(answer['answer']);upsert(seed['services'],service)
  upsert(seed['answers'],answer)
  phrases=list(questions.values())+service['synonyms'];upsert(seed['intents'],{'id':iid,'service_id':sid,'kind':'overview','type':'verified-regional-service','phrases':phrases,'answer_ids':[aid],'answer_policy':'verified-primary-provider-summary','factual_details_verified':True})
  seed['aliases']=[a for a in seed['aliases'] if a.get('intent_id')!=iid]
  for n,phrase in enumerate(phrases):seed['aliases'].append({'service_id':sid,'intent_id':iid,'language':LANGS[n%4],'phrase':phrase,'variant_type':'curated-regional-query'})
  upsert(seed['service_paths'],{'id':'path-'+sid+'-overview','service_id':sid,'task':'overview','intent_id':iid,'answer_id':aid,'official_url':service['official_url']})
 # Attach the missing real professional desk to the existing municipal directory card.
 for service in seed['services']:
  if service['id']=='26844':service['contact']='regional-desk';service['organization_id']='regional-integration-office'
 m=seed['knowledge_metrics'];single=[i for i in seed['intents'] if i.get('type')!='compound-navigation-goal'];compound=[i for i in seed['intents'] if i.get('type')=='compound-navigation-goal']
 m.update({'distinct_service_records':len(seed['services']),'distinct_service_paths':len(seed['service_paths']),'structured_answers':len(seed['answers']),'multilingual_answer_renderings':len(seed['answers'])*4,'canonical_subject_task_intents':len(seed['intents']),'single_subject_task_intents':len(single),'compound_navigation_goal_intents':len(compound),'query_aliases':len(seed['aliases']),'contacts':len(seed['contacts']),'organizations_and_offices':len(seed['organizations']),'official_sources_total':len(seed['sources']),'new_regional_primary_services':len(newservices),'new_regional_professional_contacts':len(newcontacts),'new_regional_organizations_and_locations':len(neworgs),'regional_sources_live_checked_at':max(e['last_checked'] for e in manifest),'counting_policy':'Distinct services and programme/location entities are counted separately from queries. Existing compound goals add no factual services or answers. New regional services use live primary-provider evidence; contact people are public professional contacts. Unknown holiday exceptions and capacity are not inferred.'})
 seed['checked_at']=max(e['last_checked'][:10] for e in manifest)
 assert seed['events']==original_events and seed['waste']==original_waste
 for f in [ROOT/'data/seed.json',ROOT/'data/knowledge.json',ROOT/'wp-content/plugins/oberhub-core/seed.json']:write(f,seed)
 write(ROOT/'data/knowledge-metrics.json',m)
 delta={'schema_version':1,'checked_at':seed['checked_at'],'services':newservices,'contacts':newcontacts,'organizations':neworgs,'sources':sources,'answers':[a for a in seed['answers'] if a['service_id'] in {s['id'] for s in newservices}],'intents':[i for i in seed['intents'] if i.get('service_id') in {s['id'] for s in newservices}],'aliases':[a for a in seed['aliases'] if a.get('service_id') in {s['id'] for s in newservices}]}
 write(ROOT/'data/regional-delta-add-only.json',delta)
 existing=next(s for s in seed['services'] if s['id']=='26844');source=next(s for s in seed['sources'] if s['source_id']==existing['source_id'])
 write(ROOT/'data/regional-corrections-no-metadata.json',{'schema_version':1,'services':[existing],'sources':[source]})
 write(P/'regional-expansion.json',{'services':newservices,'contacts':newcontacts,'organizations':neworgs,'sources':sources,'checked_at':seed['checked_at']})
 print(json.dumps({'services':len(seed['services']),'contacts':len(seed['contacts']),'organizations':len(seed['organizations']),'sources':len(seed['sources']),'intents':len(seed['intents']),'answers':len(seed['answers']),'aliases':len(seed['aliases']),'new_real_services':len(newservices),'new_contacts':len(newcontacts),'new_organizations_locations':len(neworgs),'seed_bytes':(ROOT/'data/seed.json').stat().st_size}))
if __name__=='__main__':main()
