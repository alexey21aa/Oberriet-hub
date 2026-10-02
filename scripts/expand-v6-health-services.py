#!/usr/bin/env python3
"""Reviewed primary-provider summaries, not diagnoses or eligibility decisions."""
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
LANGS=['de','en','ru','uk']
BASE='https://ostschweiz.krebsliga.ch/dienstleistungen/'
rows=[
('cancer-advice','beratung-unterstuetzung/wir-sind-fuer-sie-da','turn17view3',
 ['Krebsliga Ostschweiz · kostenlose Beratung','Krebsliga Ostschweiz · free cancer support','Бесплатная помощь при онкологических заболеваниях','Безкоштовна допомога при онкологічних захворюваннях'],
 ['Kostenlose Beratung für Betroffene und Angehörige in St.Gallen und Buchs; Termin vereinbaren.','Free advice for affected people and relatives in St.Gallen and Buchs; arrange an appointment.','Бесплатные консультации для людей с онкологическим заболеванием и близких в St.Gallen и Buchs; нужна запись.','Безкоштовні консультації для людей з онкологічним захворюванням і близьких у St.Gallen та Buchs; потрібен запис.']),
('cancer-social-insurance','beratung-unterstuetzung/wir-sind-fuer-sie-da','turn17view3',
 ['Krebs · Sozialversicherungsberatung','Cancer · social insurance advice','Онкология · помощь с социальным страхованием','Онкологія · допомога із соціальним страхуванням'],
 ['Unterstützung beim Klären von Sozialversicherungsleistungen und beim Kontakt zu Behörden und Arbeitgebern.','Help clarifying social insurance benefits and contacting authorities and employers.','Помощь с выяснением социальных страховых выплат и общением с ведомствами и работодателем.','Допомога зі з’ясуванням соціальних страхових виплат і спілкуванням з відомствами та роботодавцем.']),
('cancer-financial-support','beratung-unterstuetzung/wir-sind-fuer-sie-da','turn17view3',
 ['Krebs · finanzielle Unterstützung prüfen','Cancer · check financial support','Онкология · проверка финансовой помощи','Онкологія · перевірка фінансової допомоги'],
 ['Budgetberatung und mögliche Hilfe für krankheitsbedingte Mehrkosten nach eigenen Richtlinien; keine garantierte Auszahlung.','Budget advice and possible help with illness-related extra costs under provider rules; no guaranteed payment.','Помощь с бюджетом и возможная поддержка дополнительных расходов из-за болезни по правилам службы; выплата не гарантирована.','Допомога з бюджетом і можлива підтримка додаткових витрат через хворобу за правилами служби; виплату не гарантовано.']),
('stoma-advice','stomaberatung/unser-angebot','turn17view4',
 ['Stomaberatung Ostschweiz','Stoma care advice Ostschweiz','Консультации по уходу за стомой','Консультації з догляду за стомою'],
 ['Spezialisierte Pflegefachpersonen begleiten vor und nach Stomaanlage und beim Erlernen selbstständiger Pflege.','Specialist nurses support people before and after stoma surgery and learning independent care.','Специализированные медсёстры помогают до и после установки стомы и при освоении самостоятельного ухода.','Спеціалізовані медсестри допомагають до та після встановлення стоми й під час освоєння самостійного догляду.']),
('palliative-home-care','palliativer-brueckendienst/unser-angebot','turn17view1',
 ['Palliativer Brückendienst · zu Hause','Palliative bridge service · home support','Паллиативная помощь на дому','Паліативна допомога вдома'],
 ['Mobiler spezialisierter Beratungsdienst ergänzt Spitex und Hausarzt bei palliativer Betreuung zu Hause.','A mobile specialist advisory service complements Spitex and family doctors for palliative home care.','Мобильная специализированная консультационная служба дополняет Spitex и семейного врача при паллиативной помощи дома.','Мобільна спеціалізована консультаційна служба доповнює Spitex і сімейного лікаря при паліативній допомозі вдома.']),
('palliative-registration','palliativer-brueckendienst/anmeldeformular-palliativer-brueckendienst-patienten-zu-hause','turn17view2',
 ['Palliativer Brückendienst · Online-Anmeldeformular','Palliative bridge service · online referral form','Форма обращения за паллиативной помощью дома','Форма звернення по паліативну допомогу вдома'],
 ['Onlineformular für Patienten zu Hause; Angaben zu Hausarzt, Spitex und Bezugsperson nötig. Medizinische Fachpersonen beachten zusätzlich den EPS-Test.','Online form for patients at home; family doctor, Spitex and contact person details required. Medical professionals must also check the EPS test requirement.','Онлайн-форма для пациентов дома: нужны сведения о семейном враче, Spitex и контактном лице. Медицинским специалистам также нужно проверить требование EPS-теста.','Онлайн-форма для пацієнтів удома: потрібні відомості про сімейного лікаря, Spitex і контактну особу. Медичним фахівцям також потрібно перевірити вимогу EPS-тесту.'])]
services=[];sources={};proof=[]
for slug,path,ref,titles,descriptions in rows:
 url=BASE+path;sid='v6-health-'+slug
 source_id=sources.get(url,{}).get('source_id',sid+'-source')
 sources[url]={'source_id':source_id,'source_url':url,'authority':'Krebsliga Ostschweiz','review_status':'checked','content_reviewed_at':'2026-10-02','last_checked':'2026-10-02','trust':'B','ttl_days':7,'freshness_class':'F3','verification_scope':'page','fetch_status':'unknown','metadata_only':True,'evidence_method':'Primary web page content reviewed; direct HTTP/hash receipt pending.'}
 services.append({'id':sid,'title':dict(zip(LANGS,titles)),'description_short':dict(zip(LANGS,descriptions)),'topic':'health-social','locality':'st-gallen','coverage_localities':['st-gallen','buchs'] if slug.startswith('cancer-') else [],'authority':'Krebsliga Ostschweiz','source_id':source_id,'source_url':url,'official_url':url,'source_checked_at':'2026-10-02','source_trust_level':'B','status':'checked','verification_scope':'page','search_concepts':['forms'] if slug.endswith('registration') else ['consultation'],'fee':None,'eligibility':None,'translation_status':{l:'editorial-draft' for l in LANGS}})
 proof.append({'record_id':sid,'source_url':url,'retrieval_ref':ref,'content_reviewed_at':'2026-10-02','verification':'primary-web-content','http_status':None,'sha256':None,'live_imported':False})
(ROOT/'data/v6-health-services-delta.json').write_text(json.dumps({'services':services,'sources':list(sources.values())},ensure_ascii=False,indent=2)+'\n')
(ROOT/'data/v6-health-services-verification.json').write_text(json.dumps(proof,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'services':len(services),'sources':len(sources),'reviewed_form_pages':1,'live_imported':False}))
