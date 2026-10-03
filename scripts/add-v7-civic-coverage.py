#!/usr/bin/env python3
"""Original multilingual summaries from reviewed primary-source pages, receipt-gated."""
import hashlib,json
from datetime import datetime,timezone
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'data/v7/review'
receipts={r['url']:r for r in json.loads((OUT/'civic-primary-receipts.json').read_text())}
def tr(en,de,ru,uk):return dict(zip(['en','de','ru','uk'],[en,de,ru,uk]))
def proof(url):
 r=receipts[url];ok=r.get('status')=='fetched' and r.get('http_status')==200
 return {'source_url':url,'checked_at':r['checked_at'],'body_sha256':r.get('sha256'),
 'fetch_status':'ok' if ok else 'error','review_status':'primary-web-content-reviewed',
 'evidence_method':'reviewed-web-snapshot' if not ok else 'reviewed-content-and-direct-receipt',
 'trust':'A' if 'www.sg.ch/' in url else 'B','ttl_days':7,'external_ai_eligible':ok}
biz='https://www.sg.ch/bildung-sport/bslb/biz-standorte/rheintal.html'
sdm='https://s-d-m.ch/familie-soziales-sucht/beratung-mediation/'
aid='https://s-d-m.ch/familie-soziales-sucht/familien-in-not/'
pair='https://www.paarundfamilienberatung-rheintal.ch/angebot/paarberatung/'
law='https://www.sg.ch/recht/gerichte/organisation---standorte/schlichtungsstellen-und-vermittlungsaemter.html'
provider_rows=[('biz-rheintal','Berufs- und Laufbahnberatung Rheintal','Altstätten','Marktgasse 27, 9450 Altstätten',biz),
 ('sdm-fss','Familie, Soziales, Sucht SDM','Heerbrugg','Widnauerstrasse 8, 9435 Heerbrugg',sdm),
 ('pair-rheintal','Paar- und Familienberatung Rheintal','Altstätten','Marktgasse 21, 9450 Altstätten',pair),
 ('conciliation-rheintal','Vermittlungsamt Rheintal','Marbach','Obergasse 4, 9437 Marbach',law),
 ('rent-conciliation-rheintal','Schlichtungsstelle für Miet- und Pachtverhältnisse Rheintal','Altstätten','Rathausplatz 2, 9450 Altstätten',law),
 ('work-conciliation-rheintal','Schlichtungsstelle für Arbeitsverhältnisse Rheintal','Altstätten','Im Kirlenhof 1, 9450 Altstätten',law)]
entities=[]
for slug,name,place,address,url in provider_rows:
 eid='reviewed:civic:'+slug
 entities.append({'id':eid,'entity_kind':'provider-location','name':name,'municipality':place,
 'coordinates':None,'category_tags':{},'source_links':[url],'source_provenance':[proof(url)],
 'legal_registration_verified':False,'published_live':False,'primary_provider_review':{
 'provider_name':name,'municipality':place,'address':address,'contact_provenance':proof(url)}})
offers=[]
def add(slug,provider,concepts,title,description,url,scope=None):
 p=proof(url)
 offers.append({'id':'v7-civic-'+slug,'business_entity_id':'reviewed:civic:'+provider,
 'title':title,'description':description,'concept_ids':['need:'+c for c in concepts],
 'source_provenance':p,'offering_verified':p['external_ai_eligible'],
 'content_reviewed':True,'existing_live_service':False,'graph_published_live':False,
 'canonical_review':'pending-live-full-record-comparison','eligible_for_live_import':False,
 'fee':None,'age_note':None,'schedule':None,'opening_hours_verified':False,
 'service_area':scope,'unknown_fields':['current-intake','individual-eligibility','opening-exceptions']})
area=['Balgach','Berneck','Widnau','Diepoldsau']
add('career','biz-rheintal',['work:career-guidance','work:career-change'],
 tr('Career guidance Rheintal','Berufsberatung Rheintal','Профориентация Rheintal','Профорієнтація Rheintal'),
 tr('Career appointments on site or online; short consultations available without booking.','Beratung vor Ort oder online; Kurzgespräche ohne Voranmeldung möglich.','Консультации лично или онлайн; короткие беседы возможны без предварительной записи.','Консультації особисто або онлайн; короткі бесіди можливі без попереднього запису.'),biz)
add('family','sdm-fss',['family:family-counselling','family:parenting-advice','family:separation-mediation'],
 tr('Family advice and mediation SDM','Familienberatung und Mediation SDM','Семейная консультация и медиация SDM','Сімейна консультація та медіація SDM'),
 tr('Family and separation support; phone ahead. Service area is limited to four municipalities.','Hilfe bei Familien- und Trennungsthemen; vorher anrufen. Angebot für vier Gemeinden.','Помощь по семейным вопросам и расставанию; запись по телефону. Обслуживаются четыре общины.','Допомога із сімейними питаннями та розставанням; запис телефоном. Обслуговуються чотири громади.'),sdm,area)
add('addiction','sdm-fss',['health:addiction-counselling','health:alcohol-counselling','health:gaming-addiction'],
 tr('Addiction counselling SDM','Suchtberatung SDM','Консультация по зависимости SDM','Консультація щодо залежності SDM'),
 tr('Advice about substances and behavioural addictions, also for relatives. Appointment required.','Beratung zu Substanzen und Verhaltenssüchten, auch für Angehörige. Termin vereinbaren.','Консультации по зависимостям от веществ и поведения, также для близких. Нужна запись.','Консультації щодо залежностей від речовин і поведінки, також для близьких. Потрібен запис.'),sdm,area)
add('budget','sdm-fss',['finance:budget-advice','finance:debt-advice'],
 tr('Budget and debt counselling SDM','Budget- und Schuldenberatung SDM','Консультация по бюджету и долгам SDM','Консультація щодо бюджету та боргів SDM'),
 tr('Advice on budgets, debts and social insurance; phone to arrange a consultation.','Beratung zu Budget, Schulden und Sozialversicherungen; Termin telefonisch vereinbaren.','Консультации по бюджету, долгам и социальному страхованию; запись по телефону.','Консультації щодо бюджету, боргів і соціального страхування; запис телефоном.'),sdm,area)
add('emergency-aid','sdm-fss',['finance:emergency-financial-help','community:food-assistance'],
 tr('Family emergency assistance SDM','Familien in Not SDM','Помощь семьям в трудной ситуации SDM','Допомога сімʼям у скруті SDM'),
 tr('Possible one-time help for local clients in counselling; assessment required, no guaranteed payment.','Mögliche einmalige Hilfe für lokale Beratungsklienten; Abklärung erforderlich, keine Auszahlungsgarantie.','Возможна разовая помощь местным клиентам консультации; требуется оценка, выплата не гарантируется.','Можлива разова допомога місцевим клієнтам консультації; потрібне оцінювання, виплата не гарантована.'),aid,area)
add('couples','pair-rheintal',['family:couples-counselling'],
 tr('Couples counselling Rheintal','Paarberatung Rheintal','Консультация для пар Rheintal','Консультація для пар Rheintal'),
 tr('Support for relationship conflict; the first discussion is free, later sessions involve an adjusted contribution.','Hilfe bei Beziehungskonflikten; Erstgespräch kostenlos, weitere Gespräche mit angepasster Kostenbeteiligung.','Помощь при конфликтах в паре; первая беседа бесплатна, следующие требуют индивидуального взноса.','Допомога при конфліктах у парі; перша бесіда безкоштовна, наступні потребують індивідуального внеску.'),pair)
add('civil','conciliation-rheintal',['legal:civil-conciliation'],
 tr('Civil conciliation Rheintal','Zivilschlichtung Rheintal','Примирение по гражданским спорам Rheintal','Примирення у цивільних спорах Rheintal'),
 tr('Civil conciliation office in Marbach; this office does not provide legal advice.','Vermittlungsamt in Marbach; das Amt erteilt keine Rechtsauskünfte.','Примирительное ведомство в Marbach; правовых консультаций здесь не предоставляют.','Примирне відомство в Marbach; правових консультацій тут не надають.'),law)
add('rent','rent-conciliation-rheintal',['housing:rental-dispute'],
 tr('Rental conciliation Rheintal','Mietschlichtung Rheintal','Примирение по спорам аренды Rheintal','Примирення у спорах оренди Rheintal'),
 tr('Specialist rental and lease conciliation office in Altstätten. Check jurisdiction before submission.','Schlichtungsstelle für Miete und Pacht in Altstätten. Zuständigkeit vor Eingabe prüfen.','Специализированное примирение по аренде в Altstätten. Перед подачей проверьте компетенцию.','Спеціалізоване примирення щодо оренди в Altstätten. Перед поданням перевірте компетенцію.'),law)
add('work','work-conciliation-rheintal',['work:employment-dispute'],
 tr('Employment conciliation Rheintal','Arbeitsschlichtung Rheintal','Примирение по трудовым спорам Rheintal','Примирення у трудових спорах Rheintal'),
 tr('Employment conciliation office in Altstätten. Confirm its remit for your employment relationship.','Arbeitsschlichtungsstelle in Altstätten. Zuständigkeit für das Arbeitsverhältnis klären.','Ведомство примирения по трудовым спорам в Altstätten. Уточните компетенцию для ваших трудовых отношений.','Відомство примирення у трудових спорах в Altstätten. Уточніть компетенцію для ваших трудових відносин.'),law)
patches=OUT/'civic-provider-enrichment-patches.json'
if patches.exists():
 mapped={p['post_id']:p['canonical_id'] for p in json.loads(patches.read_text())}
 for o in offers:
  post_id={'v7-civic-career':3636,'v7-civic-couples':3578}.get(o['id'])
  if post_id in mapped:
   o.update(canonical_live_service_id=mapped[post_id],existing_live_service=True,
    canonical_review='matched-existing-live-record-enrichment')
out={'entities':entities,'offerings':offers,'new_live_imports':0,
 'limitations':['Web snapshots were reviewed; direct receipt failures stay explicit and fail closed.','Canonical comparison required before publishing.','Fees/hours are not inferred; office availability is not employer shifts.']}
(OUT/'civic-reviewed-offerings.json').write_text(json.dumps(out,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'provider_locations':len(entities),'content_reviewed_offers':len(offers),'direct_verified':sum(o['offering_verified'] for o in offers),'live_imports':0}))
