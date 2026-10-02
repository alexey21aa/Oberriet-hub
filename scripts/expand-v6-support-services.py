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
('spitex-assessment','https://www.spitex-oberriet.ch/Dienstleistungen/PGUAb/','Spitex Verein Oberriet','oberriet','health-social',['consultation'],
['Spitex Oberriet · Abklärung und Beratung','Spitex Oberriet · care assessment','Spitex Oberriet · оценка помощи на дому','Spitex Oberriet · оцінка допомоги вдома'],
['Pflegebedarf zu Hause abklären; Beratung für Betroffene und Angehörige.','Assess home care needs; advice for clients and relatives.','Оценка потребности в уходе дома; консультация для человека и близких.','Оцінка потреби в догляді вдома; консультація для людини та близьких.'],'turn10view0'),
('spitex-care','https://www.spitex-oberriet.ch/Dienstleistungen/PGUAb/','Spitex Verein Oberriet','oberriet','health-social',[],
['Spitex Oberriet · Pflege zu Hause','Spitex Oberriet · nursing at home','Spitex Oberriet · уход на дому','Spitex Oberriet · догляд вдома'],
['Behandlungs- und Grundpflege; Bedarf, Verordnung und Kosten mit Anbieter klären.','Treatment and basic nursing; clarify needs, referrals and costs with the provider.','Лечебный и базовый уход; потребность, назначения и стоимость уточняйте у службы.','Лікувальний і базовий догляд; потребу, призначення та вартість уточнюйте у служби.'],'turn10view0'),
('legal-advice','https://www.sgav.ch/verband/unentgeltliche-rechtsauskunft/region-rheintal-4.html','St.Galler Anwaltsverband','altstaetten','health-social',['consultation'],
['Kostenlose Rechtsauskunft Rheintal','Free legal advice Rheintal','Бесплатная юридическая консультация Rheintal','Безкоштовна юридична консультація Rheintal'],
['Kurze persönliche Rechtsauskunft in Altstätten; Online-Anmeldung nötig. Keine telefonische Beratung.','Brief in-person legal advice in Altstätten; online booking required. No telephone advice.','Краткая личная консультация в Altstätten; нужна онлайн-запись. По телефону не консультируют.','Коротка особиста консультація в Altstätten; потрібен онлайн-запис. Телефоном не консультують.'],'turn10view1'),
('victim-advice','https://ohsg.ch/beratung/','Opferhilfe SG-AR-AI','st-gallen','health-social',['consultation'],
['Opferhilfe · vertrauliche Beratung','Victim support · confidential advice','Помощь пострадавшим от насилия','Допомога постраждалим від насильства'],
['Kostenlose vertrauliche Beratung zu Gewalt, Schutz und rechtlichen Möglichkeiten.','Free confidential advice about violence, protection and legal options.','Бесплатная конфиденциальная консультация о насилии, защите и правовых возможностях.','Безкоштовна конфіденційна консультація щодо насильства, захисту та правових можливостей.'],'turn10view2'),
('bus-maps','https://www.rtb.ch/reisen/liniennetzplaene','RTB Rheintal Bus','rheintal','transport',[],
['RTB · Busnetz und Tarifzonen','RTB · bus network and fare zones','RTB · схема автобусов Rheintal','RTB · схема автобусів Rheintal'],
['Offizielle Liniennetzpläne für Rheintal und Region Altstätten; OSTWIND-Zonen beachten.','Official network maps for Rheintal and Altstätten; check OSTWIND fare zones.','Официальные схемы маршрутов Rheintal и Altstätten; учитывайте тарифные зоны OSTWIND.','Офіційні схеми маршрутів Rheintal та Altstätten; враховуйте тарифні зони OSTWIND.'],'turn10view3'),
('bus-oberdorf','https://www.rtb.ch/reisen/info-haltestelle/detail-haltestelle/87489-oberriet-sg-oberdorf','RTB Rheintal Bus','oberriet','transport',[],
['Oberriet Oberdorf · Busabfahrten','Oberriet Oberdorf · bus departures','Oberriet Oberdorf · отправления автобусов','Oberriet Oberdorf · відправлення автобусів'],
['Haltestellenseite mit Echtzeitanzeige des Betreibers. Aktuelle Abfahrten dort prüfen.','Stop page with the operator’s real-time display. Check current departures there.','Страница остановки с табло реального времени перевозчика. Проверяйте отправления там.','Сторінка зупинки з табло реального часу перевізника. Перевіряйте відправлення там.'],'turn11view0'),
]
services=[];sources={};proof=[]
for slug,url,authority,locality,topic,concepts,titles,descriptions,ref in rows:
 sid='v6-support-'+slug
 # Two offerings on one primary page share one provenance registry entry.
 source_id=sources.get(url,{}).get('source_id',sid+'-source')
 sources[url]={'source_id':source_id,'source_url':url,'authority':authority,'review_status':'checked','last_checked':'2026-10-02','content_reviewed_at':'2026-10-02','trust':'A' if authority in ['SVA St.Gallen','Kantonales Steueramt St.Gallen'] else 'B','ttl_days':7,'freshness_class':'F3','fetch_mode':'html','fetch_status':'ok','verification_scope':'page','metadata_only':True,'reuse_note':'Original concise summaries and links; no full page text or staff contacts.','evidence_method':'Primary-source web retrieval reviewed on 2026-10-02; direct HTTP/hash receipt pending.'}
 services.append({'id':sid,'title':localized(titles),'description_short':localized(descriptions),'topic':topic,'locality':locality,'authority':authority,'source_id':source_id,'source_url':url,'official_url':url,'source_checked_at':'2026-10-02','source_trust_level':sources[url]['trust'],'status':'checked','verification_scope':'page','search_concepts':concepts,'fee':None,'requirements':None,'eligibility':None,'next_action':localized(['Aktuelle Details beim Anbieter prüfen.','Check current details with the provider.','Уточните актуальные детали у организатора.','Уточніть актуальні деталі в організатора.']),'translation_status':{l:'editorial-draft' for l in LANGS}})
 proof.append({'record_id':sid,'source_url':url,'retrieval_ref':ref,'checked_at':'2026-10-02','verification':'primary-web-content','http_status':None,'sha256':None})
delta={'services':services,'sources':list(sources.values())}
(ROOT/'data/v6-support-services-delta.json').write_text(json.dumps(delta,ensure_ascii=False,indent=2)+'\n')
(ROOT/'data/v6-support-services-verification.json').write_text(json.dumps(proof,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'services':len(services),'sources':len(sources),'live_imported':False}))
