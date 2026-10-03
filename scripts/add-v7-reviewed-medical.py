#!/usr/bin/env python3
"""Encode bounded editorial facts from reviewed public primary-provider pages.
Not medical advice, no effectiveness/insurance claims, no live writes.
"""
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'data/v7/review'
receipts={r['url']:r for r in json.loads((OUT/'medical-primary-receipts.json').read_text())}
def provenance(url):
 r=receipts[url]
 if r['status']!='fetched' or r['http_status']!=200:raise ValueError('Direct receipt required')
 return {'source_url':url,'checked_at':r['checked_at'],'body_sha256':r['sha256'],'review_status':'primary-content-reviewed','trust':'B','ttl_days':7,'scope':'directory-offering-only'}
gi='https://www.bauchmed.ch/leistungen.html';run='https://laufstil.ch/deine-ansprechpartnerin/';radio='https://www.team-radiologie.ch/die-standorte/radiologie-nordost-in-altstaetten/'
concept_data=[
 ('gastroenterology',{'en':'Gastroenterology','de':'Gastroenterologie','ru':'Гастроэнтерология','uk':'Гастроентерологія'},gi),
 ('abdominal-ultrasound',{'en':'Abdominal ultrasound','de':'Ultraschall Bauch','ru':'УЗИ живота','uk':'УЗД живота'},gi),
 ('colonoscopy',{'en':'Colonoscopy','de':'Darmspiegelung','ru':'Колоноскопия','uk':'Колоноскопія'},gi),
 ('gastroscopy',{'en':'Gastroscopy','de':'Magenspiegelung','ru':'Гастроскопия','uk':'Гастроскопія'},gi),
 ('breath-testing',{'en':'Breath test','de':'Atemtest','ru':'Дыхательный тест','uk':'Дихальний тест'},gi),
 ('radiology',{'en':'Radiology','de':'Radiologie','ru':'Радиология','uk':'Радіологія'},radio),
 ('orthopaedic-insoles',{'en':'Orthopaedic insoles','de':'Orthopädische Einlagen','ru':'Ортопедические стельки','uk':'Ортопедичні устілки'},run)]
concepts=[{'id':'need:health:'+slug,'label':labels['en'],'labels':labels,'parent_id':'category:healthcare','classifications':[{'human_need':slug}],'source_id':'reviewed-primary-provider','source_provenance':provenance(url),'semantic_review':'primary-offering-distinction-reviewed-cross-taxonomy-audit-pending'} for slug,labels,url in concept_data]
(OUT/'medical-concept-extension.json').write_text(json.dumps(concepts,ensure_ascii=False,indent=2)+'\n')
def offer(id,eid,title,description,cids,url):return {'id':id,'business_entity_id':eid,'title':dict(zip(['en','de','ru','uk'],title)),'description':dict(zip(['en','de','ru','uk'],description)),'concept_ids':['need:health:'+c for c in cids],'source_provenance':provenance(url),'offering_verified':True,'existing_live_service':False,'graph_published_live':False,'canonical_review':'pending-live-full-record-comparison','eligible_for_live_import':False,'fee':None,'age_note':None,'schedule':None,'opening_hours_verified':False}
offers=[
 offer('v7-bauchmed-consultation','osm:node:13109225607',['Gastroenterology consultation','Gastroenterologische Sprechstunde','Консультация гастроэнтеролога','Консультація гастроентеролога'],['Digestive-system specialist consultations. Contact the practice for appointments.','Fachärztliche Sprechstunden zum Verdauungssystem. Termine bei der Praxis anfragen.','Консультации по заболеваниям пищеварительной системы. Запись через практику.','Консультації щодо захворювань травної системи. Запис через практику.'],['gastroenterology'],gi),
 offer('v7-bauchmed-diagnostics','osm:node:13109225607',['Digestive-system examinations','Untersuchungen des Verdauungssystems','Обследования пищеварительной системы','Обстеження травної системи'],['Provider lists ultrasound, breath tests, gastroscopy and colonoscopy.','Die Praxis nennt Ultraschall, Atemtests, Magen- und Darmspiegelungen.','Практика предлагает УЗИ, дыхательные тесты, гастроскопию и колоноскопию.','Практика пропонує УЗД, дихальні тести, гастроскопію та колоноскопію.'],['abdominal-ultrasound','breath-testing','gastroscopy','colonoscopy'],gi),
 offer('v7-radiologie-altstaetten','osm:node:13436092133',['Radiology practice Altstätten','Radiologische Praxis Altstätten','Радиологическая практика Altstätten','Радіологічна практика Altstätten'],['Radiology location at Churerstrasse 1; provider links clinician referral registration.','Radiologie an der Churerstrasse 1; Anbieter verlinkt die Anmeldung durch Zuweiser.','Радиология на Churerstrasse 1; провайдер указывает регистрацию направляющим специалистом.','Радіологія на Churerstrasse 1; провайдер вказує реєстрацію спеціалістом, який направляє.'],['radiology'],radio),
 offer('v7-laufstil-insoles','osm:node:13158305718',['Custom insoles and foot-support products','Individuelle Einlagen und Fusshilfsmittel','Индивидуальные стельки и средства поддержки стоп','Індивідуальні устілки та засоби підтримки стоп'],['Provider describes custom orthopaedic and sensorimotor insoles. Appointment booking is linked.','Der Anbieter beschreibt individuelle orthopädische und sensomotorische Einlagen. Terminbuchung ist verlinkt.','Провайдер описывает индивидуальные ортопедические и сенсомоторные стельки. Есть ссылка для записи.','Провайдер описує індивідуальні ортопедичні та сенсомоторні устілки. Є посилання для запису.'],['orthopaedic-insoles'],run)]
reviews=[{'business_entity_id':'osm:node:13109225607','provider_name':'BauchMedizin Rheintal','municipality':'Altstätten','address':'Heidenerstrasse 11, 9450 Altstätten','contact_provenance':provenance('https://www.bauchmed.ch/kontakt.html'),'classification_review':'gastroenterology-confirmed','verified_legal_registration':False},
 {'business_entity_id':'osm:node:13436092133','provider_name':'Radiologie Nordost Altstätten','municipality':'Altstätten','address':'Churerstrasse 1, 9450 Altstätten','contact_provenance':provenance(radio),'classification_review':'radiology-location-confirmed','verified_legal_registration':False},
 {'business_entity_id':'osm:node:13158305718','provider_name':'Laufstil','municipality':'Altstätten','address':'Breite 11, 9450 Altstätten','contact_provenance':provenance('https://laufstil.ch/'),'classification_review':'osm-doctor-label-not-supported-by-reviewed-content','verified_legal_registration':False,'clinical_licensure_verified':False,'reviewed_category':'orthopaedic-insole-craft-and-products'}]
(OUT/'medical-reviewed-offerings.json').write_text(json.dumps({'provider_reviews':reviews,'offerings':offers,'new_live_imports':0,'limitations':['Content is provider directory information, not medical advice or clinical effectiveness verification.','OSM coordinates/legal identities remain discovery metadata.','Live full-record canonical comparison required before import.','Unknown fees, patient age limits and insurance coverage stay unknown.']},ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'providers_reviewed':len(reviews),'reviewed_offer_drafts':len(offers),'human_need_concepts':len(concepts),'direct_receipts':len(receipts),'new_live_imports':0}))
