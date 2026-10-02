#!/usr/bin/env python3
"""Original concise multilingual summaries from primary pages reviewed 2026-10-02.

No eligibility decisions, copied provider paragraphs, personal staff contacts or
inferred fees. Candidate only; import through the validated add-only queue.
"""
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
LANGS=['de','en','ru','uk']
def localized(values):return dict(zip(LANGS,values))
rows=[
('ludo-borrow','https://www.ludothek-altstaetten.ch/','Ludothek Altstätten','altstaetten','education-family',['children','adults','education'],
['Ludothek Altstätten · Spiele ausleihen','Ludothek Altstätten · borrow games','Ludothek Altstätten · прокат игр','Ludothek Altstätten · прокат ігор'],
['Spiele und Spielsachen für Kinder und Erwachsene; Ausleihgebühr beim Anbieter prüfen.','Games and toys for children and adults; check borrowing fees with the provider.','Игры и игрушки для детей и взрослых; уточните стоимость проката.','Ігри та іграшки для дітей і дорослих; уточніть вартість прокату.'],'turn7view0'),
('ludo-boxes','https://www.ludothek-altstaetten.ch/spielkisten/','Ludothek Altstätten','altstaetten','education-family',['children','adults'],
['Spielkisten für Geburtstage und Feste','Game boxes for birthdays and celebrations','Игровые наборы для детского праздника','Ігрові набори для дитячого свята'],
['Spielkisten individuell zusammenstellen und reservieren; Ausleihe für Feste und Anlässe.','Assemble and reserve game boxes for parties and events.','Можно подобрать и забронировать игровые наборы для праздников и мероприятий.','Можна підібрати та забронювати ігрові набори для свят і заходів.'],'turn8view1'),
('ludo-play','https://www.ludothek-altstaetten.ch/spielen-und-geburtstag-in-der-ludo/','Ludothek Altstätten','altstaetten','education-family',['children','adults'],
['Spielen und Geburtstag in der Ludothek','Games and birthdays at the toy library','Игры и день рождения в Ludothek','Ігри та день народження в Ludothek'],
['Spiele aus dem Sortiment vor Ort ausprobieren; Veranstaltung über Kontaktformular anfragen.','Try games from the collection on site; ask about an event through the contact form.','Игры из коллекции можно попробовать на месте; организацию мероприятия уточняйте через контактную форму.','Ігри з колекції можна спробувати на місці; організацію заходу уточнюйте через контактну форму.'],'turn8view0'),
('juniorband','https://mgme.ch/nachwuchs','Musikgesellschaft Montlingen-Eichenwies','montlingen','education-family',['children','education'],
['Juniorband Montlingen-Eichenwies','Junior band Montlingen-Eichenwies','Детский музыкальный ансамбль Montlingen-Eichenwies','Дитячий музичний ансамбль Montlingen-Eichenwies'],
['Für Kinder von 9 bis 12 Jahren nach einem Jahr Instrumental-Einzelunterricht; gemeinsam musizieren.','For children aged 9–12 after one year of individual instrument lessons; play music together.','Для детей 9–12 лет после года индивидуальных занятий на инструменте; совместное музицирование.','Для дітей 9–12 років після року індивідуальних занять на інструменті; спільне музикування.'],'turn8search2'),
('jungmusik','https://mgme.ch/nachwuchs','Musikgesellschaft Montlingen-Eichenwies','montlingen','education-family',['children','education'],
['Jungmusik Montlingen-Eichenwies','Youth music Montlingen-Eichenwies','Молодёжный оркестр Montlingen-Eichenwies','Молодіжний оркестр Montlingen-Eichenwies'],
['Gemeinsame Proben und Jahresprogramm mit Musiklager, Ausflug und Auftritten; Eintritt beim Verein klären.','Group rehearsals and an annual programme with music camp, outings and performances; check entry with the club.','Совместные репетиции, музыкальный лагерь, поездки и выступления; условия вступления уточняйте в клубе.','Спільні репетиції, музичний табір, поїздки та виступи; умови вступу уточнюйте в клубі.'],'turn8search2'),
]
services=[];sources={};proof=[]
for slug,url,authority,locality,topic,concepts,titles,descriptions,ref in rows:
 sid='v6-community-'+slug
 # Two offerings on one primary page share one provenance registry entry.
 source_id=sources.get(url,{}).get('source_id',sid+'-source')
 sources[url]={'source_id':source_id,'source_url':url,'authority':authority,'review_status':'checked','last_checked':'2026-10-02','content_reviewed_at':'2026-10-02','trust':'A' if authority in ['SVA St.Gallen','Kantonales Steueramt St.Gallen'] else 'B','ttl_days':7,'freshness_class':'F3','fetch_mode':'html','fetch_status':'ok','verification_scope':'page','metadata_only':True,'reuse_note':'Original concise summaries and links; no full page text or staff contacts.','evidence_method':'Primary-source web retrieval reviewed on 2026-10-02; direct HTTP/hash receipt pending.'}
 services.append({'id':sid,'title':localized(titles),'description_short':localized(descriptions),'topic':topic,'locality':locality,'authority':authority,'source_id':source_id,'source_url':url,'official_url':url,'source_checked_at':'2026-10-02','source_trust_level':sources[url]['trust'],'status':'checked','verification_scope':'page','search_concepts':concepts,'fee':None,'requirements':None,'eligibility':None,'next_action':localized(['Aktuelle Details beim Anbieter prüfen.','Check current details with the provider.','Уточните актуальные детали у организатора.','Уточніть актуальні деталі в організатора.']),'translation_status':{l:'editorial-draft' for l in LANGS}})
 proof.append({'record_id':sid,'source_url':url,'retrieval_ref':ref,'checked_at':'2026-10-02','verification':'primary-web-content','http_status':None,'sha256':None})
delta={'services':services,'sources':list(sources.values())}
(ROOT/'data/v6-community-services-delta.json').write_text(json.dumps(delta,ensure_ascii=False,indent=2)+'\n')
(ROOT/'data/v6-community-services-verification.json').write_text(json.dumps(proof,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'services':len(services),'sources':len(sources),'live_imported':False}))
