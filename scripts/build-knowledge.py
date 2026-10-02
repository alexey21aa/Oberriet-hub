#!/usr/bin/env python3
"""Build factual navigation corpus. Never inflate distinct service counts with aliases.
Fetches only public official pages. Conditions/fees are deliberately not inferred
from headings or general portals. Network harvest cached in data/expansion.
"""
import argparse, concurrent.futures, hashlib, html, json, re, urllib.request, urllib.parse, xml.etree.ElementTree as ET
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]; DATA=ROOT/'data'; CACHE=DATA/'expansion'; LANGS=('de','en','uk','ru'); DATE='2026-10-01'
def write(path,obj): path.write_text(json.dumps(obj,ensure_ascii=False,indent=2)+'\n')
def readmap(path):
 return {r[0]:dict(zip(('en','uk','ru'),r[1:])) for line in path.read_text().splitlines() if len(r:=line.split('|'))==4}
def fetch(url):
 try:
  req=urllib.request.Request(url,headers={'User-Agent':'OberrietHub/1.0 (official-public-source-navigation-index)'})
  with urllib.request.urlopen(req,timeout=18) as f: raw=f.read(); status=f.status;final=f.url
  text=raw.decode('utf-8','replace'); match=re.search(r'<h1[^>]*>(.*?)</h1>',text,re.S|re.I) or re.search(r'<title[^>]*>(.*?)</title>',text,re.S|re.I)
  title=html.unescape(re.sub('<[^>]+>',' ',match.group(1))) if match else ''
  title=' '.join(title.split()).split(' – ')[0]
  return {'url':url,'resolved_url':final,'status':status,'title':title,'content_hash':hashlib.sha256(raw).hexdigest(),'checked_at':DATE}
 except Exception as e: return {'url':url,'status':0,'error':type(e).__name__,'checked_at':DATE}
def harvest():
 raw=urllib.request.urlopen('https://www.ch.ch/sitemap.xml',timeout=30).read();root=ET.fromstring(raw)
 chmap=readmap(CACHE/'ch-topics.tsv');svamap=readmap(CACHE/'sva-topics.tsv')
 allurls=[e.text for e in root.findall('.//{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]
 chosen={}
 for url in allurls:
  slug=url.rstrip('/').split('/')[-1]
  if '/de/' in url and slug in chmap and slug not in chosen:chosen[slug]={'url':url,'translation':chmap[slug],'authority':'Schweizerische Bundeskanzlei / ch.ch','topic':url.split('/')[4]}
 for entry in json.loads((CACHE/'sva-navigation.json').read_text()):
  slug=entry['url'].rstrip('/').split('/')[-1]
  if slug in svamap:chosen['sva-'+slug]={'url':entry['url'],'translation':svamap[slug],'authority':'SVA St.Gallen','topic':'social-insurance'}
 # Direct form was separately opened and verified.
 chosen['sva-ipv-online']={'url':'https://www.svasg.ch/online-schalter/formulare/web/ak-ipv-anmeldung.php','translation':svamap['ak-ipv-anmeldung.php'],'authority':'SVA St.Gallen','topic':'social-insurance'}
 records=[]
 with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
  tasks={pool.submit(fetch,entry['url']):(key,entry) for key,entry in chosen.items()}
  for f in concurrent.futures.as_completed(tasks):
   key,entry=tasks[f]; record=f.result();record.update(entry);record['id']='nav-'+hashlib.sha256(record['url'].encode()).hexdigest()[:12];records.append(record)
 records.sort(key=lambda r:r['id']);write(CACHE/'verified-pages.json',records)
 print('harvest',len(records),'verified',sum(r['status']==200 for r in records),flush=True)
DESCRIPTION={
 'de':'Offizielle Information zu „{title}“ bei {authority}. Die Seite beschreibt das Thema und verlinkt die weiteren Schritte. Persönliche Voraussetzungen, Gebühren und Fristen bitte in der aktuellen Originalseite prüfen.',
 'en':'Official information about {title} from {authority}. The source covers this topic and links to the next steps. Check the current original page for your personal eligibility, fees and deadlines.',
 'uk':'Офіційна інформація: {title}. Джерело: {authority}. На сторінці пояснено тему та наведено посилання для наступних дій. Особисті умови, оплату та строки перевірте на актуальній сторінці джерела.',
 'ru':'Официальная информация: {title}. Источник: {authority}. На странице объясняется тема и приведены ссылки для дальнейших действий. Личные условия, оплату и сроки проверьте на актуальной странице источника.'}
# Subject-specific query variants are aliases, never counted as independent facts.
TASKS={
 'overview':{'de':['{t}','Information {t}','Wie funktioniert {t}','Was ist {t}','Hilfe zu {t}','Fragen zu {t}'],'en':['{t}','information about {t}','how does {t} work','what is {t}','help with {t}','questions about {t}'],'uk':['{t}','інформація: {t}','як працює {t}','що таке {t}','допомога: {t}','питання: {t}'],'ru':['{t}','информация: {t}','как работает {t}','что такое {t}','помощь: {t}','вопросы: {t}']},
 'route':{'de':['{t} zuständige Stelle','{t} Kontakt','{t} Anlaufstelle','Wohin für {t}','Wer hilft bei {t}','{t} Behörde'],'en':['{t} responsible authority','{t} contact','{t} office','where to ask about {t}','who can help with {t}','{t} official authority'],'uk':['{t} відповідальна служба','{t} контакти','{t} установа','куди звернутися: {t}','хто допоможе: {t}','{t} орган влади'],'ru':['{t} ответственная служба','{t} контакты','{t} учреждение','куда обратиться: {t}','кто поможет: {t}','{t} орган власти']},
 'apply':{'de':['{t} beantragen','{t} Anmeldung','{t} Antrag','Wie beantrage ich {t}','{t} erste Schritte','{t} Verfahren'],'en':['apply for {t}','{t} registration','{t} application','how to apply for {t}','{t} first steps','{t} procedure'],'uk':['оформити: {t}','{t} реєстрація','{t} заява','як оформити: {t}','{t} перші кроки','{t} процедура'],'ru':['оформить: {t}','{t} регистрация','{t} заявление','как оформить: {t}','{t} первые шаги','{t} процедура']},
 'requirements':{'de':['{t} Voraussetzungen','{t} Unterlagen','{t} Dokumente','Was brauche ich für {t}','{t} Checkliste','{t} Nachweise'],'en':['{t} eligibility','{t} documents','{t} requirements','what do I need for {t}','{t} checklist','{t} evidence'],'uk':['{t} умови','{t} документи','{t} вимоги','що потрібно: {t}','{t} перелік документів','{t} підтвердження'],'ru':['{t} условия','{t} документы','{t} требования','что нужно: {t}','{t} список документов','{t} подтверждения']},
 'cost':{'de':['{t} Kosten','{t} Gebühren','Was kostet {t}','{t} bezahlen','{t} Tarif','{t} kostenlos'],'en':['{t} cost','{t} fees','how much does {t} cost','pay for {t}','{t} charges','is {t} free'],'uk':['{t} вартість','{t} оплата','скільки коштує: {t}','плата за {t}','{t} тарифи','{t} безкоштовно'],'ru':['{t} стоимость','{t} оплата','сколько стоит: {t}','плата за {t}','{t} тарифы','{t} бесплатно']},
 'deadline':{'de':['{t} Frist','{t} Dauer','{t} Bearbeitungszeit','Wann {t}','{t} Termin','{t} rechtzeitig'],'en':['{t} deadline','{t} duration','{t} processing time','when {t}','{t} appointment','{t} time limit'],'uk':['{t} строк','{t} тривалість','{t} час розгляду','коли {t}','{t} запис на прийом','{t} кінцевий термін'],'ru':['{t} срок','{t} длительность','{t} время рассмотрения','когда {t}','{t} запись на приём','{t} крайний срок']},
 'online':{'de':['{t} online','{t} Formular','{t} herunterladen','{t} digital','{t} offizieller Link','{t} Internet'],'en':['{t} online','{t} form','{t} download','{t} digital service','{t} official link','{t} website'],'uk':['{t} онлайн','{t} форма','{t} завантажити','{t} електронна послуга','{t} офіційне посилання','{t} сайт'],'ru':['{t} онлайн','{t} форма','{t} скачать','{t} электронная услуга','{t} официальная ссылка','{t} сайт']}}
QUESTIONS={
 'overview':{'de':'Wo finde ich offizielle Informationen zu {t}?','en':'Where can I find official information about {t}?','uk':'Де знайти офіційну інформацію: {t}?','ru':'Где найти официальную информацию: {t}?'},
 'route':{'de':'Welche offizielle Anlaufstelle informiert über {t}?','en':'Which official source provides information about {t}?','uk':'Яке офіційне джерело пояснює: {t}?','ru':'Какой официальный источник объясняет: {t}?'}}
QUESTIONS.update({
 'apply':{'de':'Wie gehe ich bei {t} vor?','en':'What are the next steps for {t}?','uk':'Які наступні кроки: {t}?','ru':'Какие дальнейшие действия: {t}?'},
 'requirements':{'de':'Welche Voraussetzungen und Unterlagen gelten für {t}?','en':'What eligibility rules and documents apply to {t}?','uk':'Які умови та документи потрібні: {t}?','ru':'Какие условия и документы нужны: {t}?'},
 'cost':{'de':'Welche Kosten sind für {t} bestätigt?','en':'What costs are confirmed for {t}?','uk':'Яка підтверджена вартість: {t}?','ru':'Какова подтверждённая стоимость: {t}?'},
 'deadline':{'de':'Welche Fristen und Bearbeitungszeiten gelten für {t}?','en':'What deadlines and processing times apply to {t}?','uk':'Які строки подання та розгляду: {t}?','ru':'Какие сроки подачи и рассмотрения: {t}?'},
 'online':{'de':'Wo finde ich die offizielle Online-Seite für {t}?','en':'Where is the official online page for {t}?','uk':'Де офіційна онлайн-сторінка: {t}?','ru':'Где официальная онлайн-страница: {t}?'}})
UNKNOWN={
 'requirements':{'de':'Keine vollständig überprüfte Liste persönlicher Voraussetzungen und Unterlagen hinterlegt. Fragen Sie bei {a} nach und prüfen Sie das Original: {u}', 'en':'A complete verified list of personal eligibility rules and documents is not available here. Ask {a} and check the original: {u}', 'uk':'Повного перевіреного переліку особистих умов і документів тут немає. Уточніть у {a} та перевірте джерело: {u}', 'ru':'Полного проверенного перечня личных условий и документов здесь нет. Уточните у {a} и проверьте источник: {u}'},
 'cost':{'de':'Keine verifizierte Gebühr für Ihre Situation hinterlegt. Das bedeutet nicht, dass der Vorgang kostenlos ist. Tarif bei {a} prüfen: {u}', 'en':'No verified fee for your situation is recorded here. This does not mean the process is free. Check the tariff with {a}: {u}', 'uk':'Перевірена оплата саме для вашої ситуації тут не вказана. Це не означає безкоштовність. Перевірте тариф у {a}: {u}', 'ru':'Проверенная оплата именно для вашей ситуации здесь не указана. Это не означает бесплатность. Проверьте тариф у {a}: {u}'},
 'deadline':{'de':'Keine verifizierte Frist oder Bearbeitungsdauer für diesen Fall hinterlegt. Gesetzliche Fristen nicht aus einer allgemeinen Antwort ableiten; Original und zuständige Stelle prüfen: {u}', 'en':'No verified deadline or processing duration is recorded for this case. Do not infer legal time limits from a general answer; check the source and responsible office: {u}', 'uk':'Перевіреного строку подання або розгляду для цього випадку тут немає. Не виводьте законні строки із загальної відповіді; перевірте джерело й відповідальну службу: {u}', 'ru':'Проверенного срока подачи или рассмотрения для этого случая здесь нет. Не выводите законные сроки из общего ответа; проверьте источник и ответственную службу: {u}'},
 'online':{'de':'Offizielle Informationsseite: {u}. Ein Link bestätigt noch keine vollständig digitale Antragstellung; Formular und Zugangsvoraussetzungen dort prüfen.', 'en':'Official information page: {u}. A link alone does not confirm a fully digital application process; check forms and access requirements there.', 'uk':'Офіційна інформаційна сторінка: {u}. Наявність посилання не підтверджує повністю електронне подання; перевірте форми та умови доступу.', 'ru':'Официальная информационная страница: {u}. Наличие ссылки не подтверждает полностью электронную подачу; проверьте формы и условия доступа.'}}
def facet_answer(service,kind):
 if kind=='overview':return dict(service['description_full'])
 if kind=='apply':return {l:service['description_full'][l]+' '+service['official_url'] for l in LANGS}
 if kind=='route':return {l:service['next_action'][l]+' '+service['official_url'] for l in LANGS}
 verified=service.get('verification_scope')=='page'
 field='requirements' if kind=='requirements' else 'fee' if kind=='cost' else 'processing_time' if kind=='deadline' else None
 value=service.get(field) if field and verified else None
 if value:
  if isinstance(value,dict):return {l:str(value.get(l,''))+' '+service['official_url'] for l in LANGS}
  if isinstance(value,str):return {l:value+' '+service['official_url'] for l in LANGS}
 return {l:UNKNOWN[kind][l].format(a=service['authority'],u=service['official_url']) for l in LANGS}
def build():
 seed=json.loads((DATA/'base-seed.json').read_text());records=json.loads((CACHE/'verified-pages.json').read_text());services=[];sources=[]
 if (CACHE/'hallo-pages.json').exists():records.extend(json.loads((CACHE/'hallo-pages.json').read_text()))
 records=list({r['id']:r for r in records}.values())
 for r in records:
  if r.get('status')!=200 or not r.get('title') or r['title'].lower() in ('404','page not found'):continue
  sid='s-'+hashlib.sha256(r['url'].encode()).hexdigest()[:12]
  title={'de':r['title'],**r['translation']}
  description={l:DESCRIPTION[l].format(title=title[l],authority=r['authority']) for l in LANGS}
  service={'id':r['id'],'slug':r['id'],'title':title,'description_short':description,'description_full':description,'topic':'jobs-business' if r['topic']=='arbeit' else 'health-social' if r['topic'] in ('gesundheit','social-insurance') else 'education-family' if r['topic'] in ('schule-und-bildung','familie-und-partnerschaft') else 'administration','subtopic':r['topic'],'locality':'all','authority':r['authority'],'eligibility':None,'requirements':None,'documents':None,'fee':None,'processing_time':None,'online_available':None,'official_url':r['url'],'contact':None,'source_id':sid,'source_url':r['url'],'source_authority':r['authority'],'source_checked_at':DATE,'source_updated_at':None,'source_trust_level':'A','status':'checked','translation_status':{'en':'draft','uk':'draft','ru':'draft'},'keywords':' '.join(title.values()),'synonyms':[],'verification_scope':'routing-metadata','next_action':{'de':'Offizielle Seite öffnen; dort das Verfahren und die zuständige Stelle prüfen.','en':'Open the official page to check the procedure and the responsible office.','uk':'Відкрийте офіційну сторінку, щоб перевірити процедуру та відповідальну службу.','ru':'Откройте официальную страницу, чтобы проверить процедуру и ответственную службу.'}}
  services.append(service)
  sources.append({'source_id':sid,'source_url':r['url'],'source_domain':urllib.parse.urlparse(r['url']).hostname,'source_type':'official-web','authority':r['authority'],'license_or_reuse_note':'Original short navigation summary; titles are factual metadata. No copied articles or images.','last_checked':DATE,'last_changed':None,'http_status':r['status'],'content_hash':r['content_hash'],'review_status':'checked','trust_level':'A','ttl_days':30,'verification_scope':'routing-metadata','resolved_url':r['resolved_url']})
 practical=json.loads((CACHE/'practical-scenarios.json').read_text())
 source_by_url={s['source_url']:s for s in sources}
 for item in practical:
  url='https://www.hallo.sg.ch/de/'+item['path'];source=source_by_url.get(url)
  if not source: continue
  template=next(s for s in services if s['official_url']==url)
  service={**template,'id':item['id'],'slug':item['id'],'title':item['title'],'description_short':item['summary'],'description_full':item['summary'],'topic':{'work':'jobs-business','health':'health-social','education':'education-family','family':'education-family'}.get(item['topic'],item['topic']),'synonyms':item['synonyms'],'keywords':' '.join(item['title'].values())+' '+' '.join(item['synonyms']),'verification_scope':'page-subscenario'}
  services.append(service)
 merged=[*seed['services'],*services];answers=[];intents=[];aliases=[];seen=set()
 for service in merged:
  for kind,template in TASKS.items():
   intent_id='intent-'+service['id']+'-'+kind;phrases=[]
   for language in LANGS:
    for phrase in template[language]:
     value=phrase.format(t=service['title'][language]);key=(service['id'],language,value.casefold())
     if key in seen:continue
     seen.add(key);phrases.append(value);aliases.append({'service_id':service['id'],'intent_id':intent_id,'language':language,'phrase':value,'variant_type':'subject-task-paraphrase'})
   intents.append({'id':intent_id,'service_id':service['id'],'kind':kind,'phrases':phrases,'answer_policy':'verified-overview' if kind=='overview' else 'official-source-navigation','factual_details_verified':kind=='overview' and service.get('verification_scope') in ('page','page-subscenario')})
  service['synonyms']=list(dict.fromkeys(service.get('synonyms',[])))
  for kind,questions in QUESTIONS.items():
   answer=facet_answer(service,kind)
   answers.append({'id':'answer-'+service['id']+'-'+kind,'service_id':service['id'],'kind':kind,'title':{l:questions[l].format(t=service['title'][l]) for l in LANGS},'question':{l:questions[l].format(t=service['title'][l]) for l in LANGS},'answer':answer,'source_id':service['source_id'],'source_url':service['source_url'],'verification_scope':service.get('verification_scope','routing-metadata')})
 # Preserve all original intent phrases alongside the new task router.
 for original in seed.get('intents',[]):
  if original.get('id') in {s['id'] for s in merged}:
   intents.append({**original,'service_id':original['id'],'kind':'curated-original','id':'original-'+original['id']})
 paths=[{'id':'path-'+a['service_id']+'-'+a['kind'],'service_id':a['service_id'],'task':a['kind'],'intent_id':'intent-'+a['service_id']+'-'+a['kind'],'answer_id':a['id'],'official_url':a['source_url']} for a in answers]
 metrics={'distinct_service_paths':len(paths),'distinct_service_records':len(merged),'new_official_service_records':len(services),'structured_answers':len(answers),'multilingual_answer_renderings':len(answers)*4,'practical_page_verified_scenarios':sum(s.get('verification_scope')=='page-subscenario' for s in merged),'canonical_subject_task_intents':len(intents),'query_aliases':len(aliases),'verified_official_navigation_sources':len(sources),'source_checked_at':DATE,'targets':{'service_paths':1000,'structured_answers':2000,'canonical_subject_task_intents':5000,'query_aliases':20000},'shortfalls':{'service_paths':max(0,1000-len(paths)),'structured_answers':max(0,2000-len(answers)),'canonical_subject_task_intents':max(0,5000-len(intents)),'query_aliases':max(0,20000-len(aliases))},'target_status':'Navigation path, structured answer and alias targets met. Canonical intent target remains unmet; do not confuse multilingual renderings with independent answers.','counting_policy':'Languages and query paraphrases are not independent services or independent factual answers. Subject-task intents containing unverified fees/eligibility/deadlines route to original source; no invented facts.'}
 expansion={'schema_version':1,'services':services,'sources':sources,'answers':answers,'intents':intents,'aliases':aliases,'service_paths':paths,'metrics':metrics};write(DATA/'knowledge-expansion.json',expansion)
 seed.update({'services':merged,'sources':[*seed['sources'],*sources],'answers':answers,'intents':intents,'aliases':aliases,'service_paths':paths,'knowledge_metrics':metrics,'checked_at':DATE});write(DATA/'knowledge.json',seed);write(DATA/'knowledge-metrics.json',metrics)
 serviceids={s['id'] for s in merged};sourceids={s['source_id'] for s in seed['sources']}
 assert len(serviceids)==len(merged)
 assert all(a['service_id'] in serviceids and a['source_id'] in sourceids for a in answers)
 assert all(set(a['question'])==set(LANGS) and set(a['answer'])==set(LANGS) for a in answers)
 print(json.dumps(metrics,ensure_ascii=False),flush=True)
if __name__=='__main__':
 p=argparse.ArgumentParser();p.add_argument('--harvest',action='store_true');a=p.parse_args()
 if a.harvest:harvest()
 build()
