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
('rheintal-help','https://integrationrheintal.ch/offene-sprechstunde/','Fachstelle Integration Rheintal','rheintal','health-social',['consultation','forms','integration'],
['Offene Sprechstunde Rheintal','Open advice sessions Rheintal','Помощь с формами и письмами Rheintal','Допомога з формами та листами Rheintal'],
['Kostenlose mehrsprachige Alltagshilfe: Formulare, Dokumente, Briefe und Termine.','Free multilingual help with forms, documents, letters and appointments.','Бесплатная многоязычная помощь с формами, документами, письмами и записью на приём.','Безкоштовна багатомовна допомога з формами, документами, листами та записом на прийом.'],'turn4view3'),
('oberriet-help','https://integrationrheintal.ch/offene-sprechstunde/','Fachstelle Integration Rheintal','oberriet','health-social',['consultation','forms','integration'],
['Offene Sprechstunde Oberriet · Gleis 1','Oberriet advice · Gleis 1','Помощь в Oberriet · Gleis 1','Допомога в Oberriet · Gleis 1'],
['Mittwoch 08–11 Uhr; Ukrainisch, Russisch, Deutsch.','Wednesday 08–11; Ukrainian, Russian, German.','Среда 08–11; украинский, русский, немецкий.','Середа 08–11; українська, російська, німецька.'],'turn4view3'),
('rheintal-women','https://integrationrheintal.ch/frauentreffs/','Fachstelle Integration Rheintal','rheintal','leisure',['women','adults','integration'],
['Frauentreffs Rheintal','Women’s meetings Rheintal','Женские встречи Rheintal','Жіночі зустрічі Rheintal'],
['Kostenlos, ohne Anmeldung. Austausch und gemeinsame Aktivitäten.','Free, no registration. Conversation and shared activities.','Бесплатно, без регистрации. Общение и совместные занятия.','Безкоштовно, без реєстрації. Спілкування та спільні заняття.'],'turn5view0'),
('rheintal-men','https://integrationrheintal.ch/frauentreffs/','Fachstelle Integration Rheintal','rheintal','leisure',['adults','integration'],
['Männertreff Rheintal','Men’s meetings Rheintal','Мужские встречи Rheintal','Чоловічі зустрічі Rheintal'],
['Kostenlos, ohne Anmeldung. Begegnung und gemeinsame Unternehmungen.','Free, no registration. Meetings and shared outings.','Бесплатно, без регистрации. Встречи и совместный досуг.','Безкоштовно, без реєстрації. Зустрічі та спільне дозвілля.'],'turn5view0'),
('family-stories','https://integrationrheintal.ch/schenk-mir-eine-geschichte/','Fachstelle Integration Rheintal','rheintal','education-family',['children','education','language','integration'],
['Schenk mir eine Geschichte','Family storytelling','Семейное чтение на родном языке','Сімейне читання рідною мовою'],
['Eltern und Vorschulkinder: Geschichten und kreative Aktivitäten in der Erstsprache.','Parents and preschool children: stories and creative activities in their first language.','Родители и дошкольники: истории и творческие занятия на родном языке.','Батьки й дошкільнята: історії та творчі заняття рідною мовою.'],'turn5view1'),
('german-courses','https://integrationrheintal.ch/sprachkurse/','Fachstelle Integration Rheintal','rheintal','education-family',['language','education','integration'],
['Deutschkurse suchen','Find German courses','Поиск курсов немецкого','Пошук курсів німецької'],
['Kantonale Kurssuche; finanzielle Unterstützung ist einkommensabhängig.','Cantonal course search; financial support depends on income.','Кантональный поиск курсов; финансовая поддержка зависит от дохода.','Кантональний пошук курсів; фінансова підтримка залежить від доходу.'],'turn5view2'),
('kulturlegi-card','https://www.kulturlegi.ch/st-gallen-appenzell/kulturlegi-beantragen/was-ist-die-kulturlegi/','KulturLegi St.Gallen-Appenzell','st-gallen','leisure',['discount','education'],
['KulturLegi · Freizeit günstiger','KulturLegi · leisure discounts','KulturLegi · скидки на досуг','KulturLegi · знижки на дозвілля'],
['Persönlicher kostenloser Ausweis; Ermässigungen für Kultur, Sport und Bildung.','Free personal card; discounts on culture, sport and education.','Бесплатная именная карта: скидки на культуру, спорт и образование.','Безкоштовна іменна картка: знижки на культуру, спорт та освіту.'],'turn4view4'),
('kulturlegi-eligibility','https://www.kulturlegi.ch/st-gallen-appenzell/kulturlegi-beantragen/wer-ist-berechtigt','KulturLegi St.Gallen-Appenzell','st-gallen','health-social',['discount'],
['KulturLegi · Berechtigung prüfen','KulturLegi · check eligibility','KulturLegi · проверить условия','KulturLegi · перевірити умови'],
['Für Menschen mit kleinem Einkommen; Nachweise und Ausnahmen beim Anbieter prüfen.','For people with low income; verify evidence requirements and exceptions with the provider.','Для людей с низким доходом; подтверждения и исключения проверяйте у организатора.','Для людей із низьким доходом; підтвердження та винятки перевіряйте в організатора.'],'turn5view3'),
('kulturlegi-apply','https://www.kulturlegi.ch/st-gallen-appenzell/kulturlegi-beantragen/wie-komme-ich-zur-kulturlegi','KulturLegi St.Gallen-Appenzell','st-gallen','health-social',['discount','forms'],
['KulturLegi · Antrag','KulturLegi · application','KulturLegi · заявление','KulturLegi · заява'],
['Online- oder Papierantrag mit Foto; finanzielle Nachweise nach Situation.','Online or paper application with a photo; financial evidence depends on the situation.','Онлайн-заявление или бумажная форма с фото; финансовые подтверждения зависят от ситуации.','Онлайн-заява або паперова форма з фото; фінансові підтвердження залежать від ситуації.'],'turn5view4'),
('ipv-apply','https://www.svasg.ch/online-schalter/formulare/web/ak-ipv-anmeldung.php','SVA St.Gallen','st-gallen','health-social',['insurance','forms'],
['Prämienverbilligung · Anmeldung','Health premium subsidy · application','Субсидия медицинской страховки · заявление','Субсидія медичного страхування · заява'],
['Online-Antrag auch ohne Einladung; Antragsjahr bewusst auswählen.','Online application also without an invitation; select the application year carefully.','Онлайн-заявление доступно и без приглашения; внимательно выберите год заявления.','Онлайн-заява доступна й без запрошення; уважно виберіть рік заяви.'],'turn4view0'),
('tax-extension','https://www.sg.ch/steuern-finanzen/steuern/fristverlaengerung/privatpersonen.html','Kantonales Steueramt St.Gallen','st-gallen','tax-finance',['tax','forms'],
['Steuererklärung · Fristverlängerung','Tax return · deadline extension','Налоговая декларация · продление срока','Податкова декларація · продовження строку'],
['Elektronisches Gesuch benötigt Register-Nr. und Geburtsdatum; Bewilligung nicht garantiert.','Electronic request needs registration number and date of birth; approval is not guaranteed.','Электронный запрос требует регистрационный номер и дату рождения; одобрение не гарантировано.','Електронний запит потребує реєстраційний номер і дату народження; схвалення не гарантоване.'],'turn3search4'),
('tax-forms','https://www.sg.ch/steuern-finanzen/steuern/formulare-wegleitungen/einkommens-vermoegenssteuer-privatpersonen.html','Kantonales Steueramt St.Gallen','st-gallen','tax-finance',['tax','forms'],
['Steuerformulare Privatpersonen','Individual tax forms','Налоговые формы для физических лиц','Податкові форми для фізичних осіб'],
['Offizielle Formulare und Wegleitungen nach Steuerjahr.','Official forms and guides by tax year.','Официальные формы и инструкции по налоговым годам.','Офіційні форми та інструкції за податковими роками.'],'turn3search2'),
('older-unemployed','https://www.svasg.ch/produkte/uel/','SVA St.Gallen','st-gallen','health-social',['consultation'],
['Überbrückungsleistungen ältere Arbeitslose','Bridge benefits for older unemployed people','Переходные выплаты пожилым безработным','Перехідні виплати літнім безробітним'],
['Zuständige Produktseite der SVA; persönliche Voraussetzungen prüfen lassen.','SVA product page; have personal eligibility checked.','Страница SVA по этой выплате; личные условия нужно проверить.','Сторінка SVA щодо цієї виплати; особисті умови потрібно перевірити.'],'turn5view5'),
]
services=[];sources={};proof=[]
for slug,url,authority,locality,topic,concepts,titles,descriptions,ref in rows:
 sid='v6-life-'+slug
 # Two offerings on one primary page share one provenance registry entry.
 source_id=sources.get(url,{}).get('source_id',sid+'-source')
 sources[url]={'source_id':source_id,'source_url':url,'authority':authority,'review_status':'checked','last_checked':'2026-10-02','content_reviewed_at':'2026-10-02','trust':'A' if authority in ['SVA St.Gallen','Kantonales Steueramt St.Gallen'] else 'B','ttl_days':7,'freshness_class':'F3','fetch_mode':'html','fetch_status':'ok','verification_scope':'page','metadata_only':True,'reuse_note':'Original concise summaries and links; no full page text or staff contacts.','evidence_method':'Primary-source web retrieval reviewed on 2026-10-02; direct HTTP/hash receipt pending.'}
 services.append({'id':sid,'title':localized(titles),'description_short':localized(descriptions),'topic':topic,'locality':locality,'authority':authority,'source_id':source_id,'source_url':url,'official_url':url,'source_checked_at':'2026-10-02','source_trust_level':sources[url]['trust'],'status':'checked','verification_scope':'page','search_concepts':concepts,'fee':None,'requirements':None,'eligibility':None,'next_action':localized(['Aktuelle Details beim Anbieter prüfen.','Check current details with the provider.','Уточните актуальные детали у организатора.','Уточніть актуальні деталі в організатора.']),'translation_status':{l:'editorial-draft' for l in LANGS}})
 proof.append({'record_id':sid,'source_url':url,'retrieval_ref':ref,'checked_at':'2026-10-02','verification':'primary-web-content','http_status':None,'sha256':None})
delta={'services':services,'sources':list(sources.values())}
(ROOT/'data/v6-life-services-delta.json').write_text(json.dumps(delta,ensure_ascii=False,indent=2)+'\n')
(ROOT/'data/v6-life-services-verification.json').write_text(json.dumps(proof,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'services':len(services),'sources':len(sources),'live_imported':False}))
