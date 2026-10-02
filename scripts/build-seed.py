import json,re,html,pathlib,hashlib
R=pathlib.Path(__file__).resolve().parents[1]
def clean(s):return html.unescape(re.sub('<[^>]+>','',s)).strip()
def dump(path,x):path.write_text(json.dumps(x,ensure_ascii=False,indent=2))
D='2026-10-01';sources={}
def source(url,authority='Politische Gemeinde Oberriet',trust='A',ttl=30):
 k='s-'+hashlib.sha256(url.encode()).hexdigest()[:12]
 sources[k]={'source_id':k,'source_url':url,'source_domain':url.split('/')[2],'source_type':'official-web','authority':authority,'license_or_reuse_note':'Factual metadata and original short summary; original content remains with publisher. No copied images.','last_checked':D,'last_changed':None,'http_status':None,'content_hash':'','review_status':'checked','trust_level':trust,'ttl_days':ttl}
 for p in (R/'data/evidence').glob('*.json'):
  e=json.loads(p.read_text())
  if e['url']==url:sources[k].update(http_status=e.get('http_status'),content_hash=e.get('hash',''))
 return k
raw=json.loads((R/'data/municipal-directory-raw.json').read_text())
# Four-language editorial titles; canonical public institution names stay German.
translations='''23529|Municipal taxes|Муниципальные налоги|Муніципальні податки
23620|Register after moving|Регистрация после переезда|Реєстрація після переїзду
23622|Capacity and advance directive certificate|Справка об опеке и распоряжении на будущее|Довідка про опіку та розпорядження на майбутнє
23623|Swiss identity card|Швейцарская удостоверительная карта|Швейцарська посвідчувальна картка
23624|Swiss passport|Швейцарский паспорт|Швейцарський паспорт
23625|Civil status certificates|Документы гражданского состояния|Документи цивільного стану
23626|Deregister when leaving|Снятие с регистрации при выезде|Зняття з реєстрації при виїзді
23627|Residence certificate|Справка о месте жительства|Довідка про місце проживання
23628|Residence permits|Разрешения на проживание|Дозволи на проживання
23702|Document authentication|Заверение документов|Засвідчення документів
23707|Debt enforcement register extract|Выписка из реестра взысканий|Витяг із реєстру стягнень
23895|Dog registration and tax|Регистрация собаки и налог|Реєстрація собаки та податок
23903|Parental assistance contributions|Родительские пособия|Батьківські виплати
23980|Learner driving permit|Разрешение на обучение вождению|Дозвіл на навчання водінню
23984|Change address within the municipality|Изменение адреса внутри общины|Зміна адреси в межах громади
23986|Tax return|Налоговая декларация|Податкова декларація
23987|Final tax bill|Окончательный налоговый счёт|Остаточний податковий рахунок
23988|Provisional tax bill|Предварительный налоговый счёт|Попередній податковий рахунок
23989|Moving abroad and taxes|Налоги при выезде за границу|Податки при виїзді за кордон
23992|Oberriet village fair|Ярмарка Oberriet|Ярмарок Oberriet
23993|Montlingen village fair|Ярмарка Montlingen|Ярмарок Montlingen
23994|Kriessern village fair|Ярмарка Kriessern|Ярмарок Kriessern
23997|AHV pension benefits|Пенсионные выплаты AHV|Пенсійні виплати AHV
23998|AHV contributions|Взносы AHV|Внески AHV
23999|Bridge benefits for older unemployed people|Переходные выплаты пожилым безработным|Перехідні виплати літнім безробітним
24000|Disability insurance IV|Страхование инвалидности IV|Страхування інвалідності IV
24001|Income compensation EO|Компенсация заработка EO|Компенсація заробітку EO
24004|Health insurance premium reduction|Субсидия на медицинскую страховку|Субсидія на медичне страхування
24005|Care financing|Финансирование ухода|Фінансування догляду
24006|Supplementary benefits EL|Дополнительные выплаты EL|Додаткові виплати EL
24008|Maternity allowance|Пособие по материнству|Допомога у зв’язку з материнством
24009|Family allowances|Семейные пособия|Сімейні виплати
24014|Debt enforcement procedure|Процедура взыскания долгов|Процедура стягнення боргів
24017|Order voting documents|Заказать документы для голосования|Замовити документи для голосування
24057|Asylum and refugees|Убежище и беженцы|Притулок і біженці
24058|Social assistance|Социальная помощь|Соціальна допомога
24060|Personal counselling|Личная социальная консультация|Особиста соціальна консультація
24061|Foster children|Приёмные дети|Прийомні діти
24168|Building permit procedure|Разрешение на строительство|Дозвіл на будівництво
24170|Energy subsidies|Субсидии на энергоэффективность|Субсидії на енергоефективність
24172|Utility network plans|Планы коммунальных сетей|Плани комунальних мереж
24173|Zoning plan|План зонирования|План зонування
24174|Heating installation checks|Проверка отопительных установок|Перевірка опалювальних установок
24176|Waste disposal overview|Утилизация отходов|Утилізація відходів
24199|Apprenticeships and vocational training|Профессиональное обучение|Професійне навчання
24200|Notify utility provider when moving|Сообщить поставщику энергии о переезде|Повідомити постачальника енергії про переїзд
24206|Votes and elections|Голосования и выборы|Голосування та вибори
24208|Municipal rules and publications|Правила и документы общины|Правила й документи громади
24230|Naturalisation|Получение гражданства|Отримання громадянства
24232|Criminal record extract|Справка о несудимости|Довідка про несудимість
24236|Temporary event catering permit|Разрешение на питание на мероприятии|Дозвіл на харчування на заході
24237|Restaurant operating permit|Разрешение для ресторана|Дозвіл для ресторану
24238|Legal advice|Юридическая консультация|Юридична консультація
24241|Employment conciliation|Примирение по трудовым спорам|Примирення у трудових спорах
24243|Equal treatment conciliation|Споры о равноправии|Спори про рівноправність
24245|Fireworks information|Правила фейерверков|Правила феєрверків
24246|General civil conciliation|Примирение по гражданским спорам|Примирення у цивільних спорах
24254|Tenancy conciliation|Споры об аренде|Спори про оренду
24967|Property valuation|Оценка недвижимости|Оцінка нерухомості
24969|Building damage|Повреждение здания|Пошкодження будівлі
24970|Land register transactions|Сделки в земельном реестре|Угоди в земельному реєстрі
26831|Fibre network information|Оптоволоконная сеть|Оптоволоконна мережа
26844|Regional integration advice|Региональная помощь с интеграцией|Регіональна допомога з інтеграцією
29364|Suisse ePolice online services|Онлайн-услуги полиции|Онлайн-послуги поліції
36046|Wasp nest removal|Удаление осиного гнезда|Видалення осиного гнізда
47146|Death and funeral arrangements|Смерть и организация похорон|Смерть і організація поховання
77569|Childcare subsidies|Субсидии на присмотр за детьми|Субсидії на догляд за дітьми
80900|Welcome information|Информация для новых жителей|Інформація для нових мешканців
82487|Early childhood support|Поддержка развития детей|Підтримка розвитку дітей
82493|Ukraine information and support|Информация и помощь украинцам|Інформація та допомога українцям
96286|Youth jobs|Работа для молодёжи|Робота для молоді
110992|Oberriet bicycle lockers|Велосипедные боксы Oberriet|Велосипедні бокси Oberriet
111286|Village apps|Приложения пяти деревень|Застосунки п’яти сіл
113539|Book rooms at Burg|Бронирование помещений Burg|Бронювання приміщень Burg
122615|German course discount|Скидка на курсы немецкого|Знижка на курси німецької
129544|Tree advice|Консультация по деревьям|Консультація щодо дерев'''
T={a[0]:a[1:] for a in [x.split('|') for x in translations.splitlines()]}
contacts=[]
for i,row in enumerate(json.loads((R/'data/departments-research.json').read_text())):
 title=clean(row.get('name',''));m=re.search(r'href="([^"]+)"',row.get('name',''));url='https://www.oberriet.ch'+m[1] if m else 'https://www.oberriet.ch/aemter'
 url=re.sub(r'/_rte/amt/','/aemter/',url)
 contacts.append({'id':'contact-'+str(i),'title':title,'phone':clean(row.get('telefon','')),'email':clean(row.get('email','')),'official_url':url,'source_id':source('https://www.oberriet.ch/aemter'),'source_checked_at':D,'locality':'all','trust_level':'A','status':'checked'})
contacts.insert(0,{'id':'gemeinde','title':'Gemeindeverwaltung Oberriet','phone':'+41 71 763 64 64','email':'info@oberriet.ch','official_url':'https://www.oberriet.ch/','source_id':source('https://www.oberriet.ch/'),'source_checked_at':D,'locality':'all','trust_level':'A','status':'checked','address':'Staatsstrasse 92, 9463 Oberriet'})
services=[]
for row in raw:
 mid=re.search('/dienst/([0-9]+)',row.get('dienstName',''))
 if not mid or mid[1] not in T:continue
 sid=mid[1];title=clean(row['dienstName']);authority=clean(row.get('zustaendigName',''));url='https://www.oberriet.ch/dienstleistungen/'+sid
 topic='administration'
 if 'Steuer' in title or 'steuer' in title:topic='administration'
 elif authority in ['AHV-Zweigstelle','Soziale Dienste'] or sid in ['26844','24238','36046','29364']:topic='health-social'
 elif sid in ['24176','24200','23895','110992','26831','24172']:topic='everyday-life'
 elif sid in ['77569','82487','24061','122615']:topic='education-family'
 elif sid in ['24199','96286','24241','24237']:topic='jobs-business'
 elif sid in ['23992','23993','23994','113539','111286','129544']:topic='culture-leisure'
 contact=next((c['id'] for c in contacts if c['title']==authority),'gemeinde')
 labels=dict(zip(['de','en','ru','uk'],[title,*T[sid]]))
 summaries={'de':f'Zuständige Anlaufstelle: {authority or "siehe offiziellen Dienst"}. Voraussetzungen und aktuelle Unterlagen im Original prüfen.','en':f'Contact: {authority or "see the official service"}. Check eligibility and current documents on the official page.','ru':f'Ответственная служба: {authority or "указана на официальной странице"}. Условия и актуальные документы уточните в оригинале.','uk':f'Відповідальна служба: {authority or "зазначена на офіційній сторінці"}. Умови й актуальні документи перевірте в оригіналі.'}
 services.append({'id':sid,'slug':'service-'+sid,'title':labels,'description_short':summaries,'description_full':summaries.copy(),'topic':topic,'subtopic':'','locality':'all','authority':authority,'eligibility':None,'requirements':None,'documents':None,'fee':None,'processing_time':None,'online_available':None,'official_url':url,'contact':contact,'source_id':source('https://www.oberriet.ch/dienstleistungen'),'source_url':url,'source_authority':'Politische Gemeinde Oberriet','source_checked_at':D,'source_updated_at':None,'source_trust_level':'A','status':'checked','translation_status':dict.fromkeys(['en','ru','uk'],'draft'),'keywords':' '.join(labels.values()),'synonyms':[]})
def enrich(sid,text,aliases=[],**fields):
 s=next(s for s in services if s['id']==sid);s['description_short']=dict(zip(['de','en','ru','uk'],text));s['description_full']=s['description_short'].copy();s['synonyms']=aliases;s.update(fields);s['source_id']=source(s['source_url']);s['translation_status']=dict.fromkeys(['en','ru','uk'],'draft')
enrich('23620',['Zuzug innert 14 Tagen beim Einwohneramt anmelden: persönlich oder über eUmzug. Unterlagen hängen von Herkunft und Situation ab.','Register with Einwohneramt within 14 days after moving, in person or via eUmzug. Documents depend on your circumstances.','Зарегистрируйте переезд в Einwohneramt в течение 14 дней: лично или через eUmzug. Документы зависят от вашей ситуации.','Зареєструйте переїзд в Einwohneramt протягом 14 днів: особисто або через eUmzug. Документи залежать від вашої ситуації.'],['wo anmelden','umzug','zuzug','переезд','куда сообщить адрес','прописка','переїзд','де оформити прописку','moving register address'],fee='CHF 0',online_available=True,requirements={'de':'Für einen Zuzug aus der Schweiz als ausländische Person: Pass/Ausweis, Ausländerausweis, Krankenversicherungsnachweis; Familienbüchlein, falls vorhanden. Andere Fälle: Original prüfen.','en':'Foreign nationals moving within Switzerland: passport/ID, residence permit, health insurance evidence and family record if available. Other cases: check the official page.','ru':'Для иностранца при переезде внутри Швейцарии: паспорт, разрешение на проживание, подтверждение медстраховки; семейная книжка при наличии. Другие случаи — в оригинале.','uk':'Для іноземця при переїзді в межах Швейцарії: паспорт, дозвіл на проживання, підтвердження медстрахування; сімейна книжка за наявності. Інші випадки — в оригіналі.'})
enrich('23627',['Wohnsitzbestätigung am Schalter beziehen oder telefonisch beim Einwohneramt bestellen. CHF 15, zuzüglich Porto.','Order a residence certificate at the Einwohneramt counter or by telephone. CHF 15 plus postage.','Справку о месте жительства можно получить в Einwohneramt лично или заказать по телефону. CHF 15 плюс почтовые расходы.','Довідку про місце проживання можна отримати в Einwohneramt особисто або замовити телефоном. CHF 15 плюс поштові витрати.'],['wohnsitzbestätigung','residence certificate','справка о месте жительства','довідка про місце проживання','работодателя'],fee='CHF 15 + Porto')
enrich('23984',['Eine Adressänderung innerhalb der Gemeinde dem Einwohneramt melden. Der offizielle Umzugsdienst ist verlinkt.','Notify Einwohneramt when your address changes within Oberriet; follow the official moving service.','Сообщите Einwohneramt об изменении адреса внутри Oberriet через официальный сервис переезда.','Повідомте Einwohneramt про зміну адреси в межах Oberriet через офіційний сервіс переїзду.'],['adressänderung','new address','новый адрес','нову адресу'],online_available=True)
enrich('23895',['Ersthundehaltende melden sich beim Kassieramt für Amicus. Die Gemeinde nennt eine jährliche Hundeabgabe von CHF 120.','First-time dog owners contact Kassieramt for Amicus registration. The published annual dog tax is CHF 120.','При первой регистрации собаки обратитесь в Kassieramt для Amicus. Указанный годовой налог — CHF 120.','Для першої реєстрації собаки зверніться до Kassieramt для Amicus. Зазначений річний податок — CHF 120.'],['hund','dog','собака','собаку'],fee='CHF 120 / Jahr')
# Additional verified navigation records; no guessed eligibility or prices.
def extra(sid,titles,summaries,url,topic,authority,contact='gemeinde',locality='all',trust='A',aliases=[]):
 services.append({'id':sid,'slug':sid,'title':dict(zip(['de','en','ru','uk'],titles)),'description_short':dict(zip(['de','en','ru','uk'],summaries)),'description_full':dict(zip(['de','en','ru','uk'],summaries)),'topic':topic,'locality':locality,'authority':authority,'contact':contact,'official_url':url,'source_url':url,'source_id':source(url,authority,trust),'source_checked_at':D,'source_trust_level':trust,'status':'checked','translation_status':dict.fromkeys(['en','ru','uk'],'draft'),'keywords':' '.join(titles),'synonyms':aliases,'fee':None,'requirements':None,'processing_time':None,'online_available':None})
schurl='https://www.oberriet.ch/bildungschule'
extra('school',['Kind zur Schule anmelden','Register a child for school','Записать ребёнка в школу','Записати дитину до школи'],['Schulgemeinde und Schuleinheit nach Wohnort über das offizielle Schulverzeichnis auswählen. Zuständigkeit vor Anmeldung bestätigen.','Select the school authority for your home address in the official school directory. Confirm the responsible office before registering.','Найдите школьную общину по адресу проживания в официальном списке. Уточните ответственного за регистрацию ребёнка.','Знайдіть шкільну громаду за адресою проживання в офіційному переліку. Уточніть відповідального за реєстрацію дитини.'],schurl,'education-family','Schulgemeinden Oberriet',aliases=['school registration','where do i register my child','schule','kindergarten','школа','дитину до школи'])
extra('lighting',['Strassenbeleuchtung melden','Report a broken streetlight','Сообщить о неисправном фонаре','Повідомити про несправний ліхтар'],['Zuständigkeit der Beleuchtung zuerst bei der Gemeindeverwaltung abklären; Strasse, Standort und Art der Störung nennen. Bei unmittelbarer Gefahr Notruf wählen.','Ask Gemeindeverwaltung to confirm the lighting operator. Include street, location and fault; use an emergency number for immediate danger.','Уточните в Gemeindeverwaltung ответственного за освещение. Укажите улицу, место и неисправность. При непосредственной опасности звоните в экстренную службу.','Уточніть у Gemeindeverwaltung відповідального за освітлення. Зазначте вулицю, місце та несправність. За безпосередньої небезпеки телефонуйте екстреній службі.'],'https://www.oberriet.ch/aemter/11024','everyday-life','Gemeindeverwaltung Oberriet',aliases=['фонарь не работает','не работает уличный фонарь','streetlight','lampe defekt','ліхтар','strassenbeleuchtung'])
extra('rav',['RAV Heerbrugg / Arbeit finden','RAV Heerbrugg / find work','RAV Heerbrugg / поиск работы','RAV Heerbrugg / пошук роботи'],['Oberriet gehört zum Einzugsgebiet RAV Heerbrugg. Die Anmeldung zur Arbeitsvermittlung ist über Job-Room möglich.','Oberriet is served by RAV Heerbrugg. Job-Room provides the official employment registration service.','Oberriet обслуживает RAV Heerbrugg. Официальная регистрация для поиска работы доступна через Job-Room.','Oberriet обслуговує RAV Heerbrugg. Офіційна реєстрація для пошуку роботи доступна через Job-Room.'],'https://www.sg.ch/wirtschaft-arbeit/arbeitslos-arbeit-finden/rav.html','jobs-business','Amt für Wirtschaft und Arbeit',aliases=['rav','arbeit','job','безработица','робота'])
extra('transport',['Fahrplan Oberriet SG','Public transport timetable','Расписание транспорта','Розклад транспорту'],['Verbindungen ab Oberriet SG und Ihrem lokalen Haltepunkt direkt im SBB-Fahrplan prüfen.','Check departures from Oberriet SG or your local stop in the SBB timetable.','Проверьте рейсы от Oberriet SG или местной остановки в расписании SBB.','Перевірте рейси від Oberriet SG або місцевої зупинки в розкладі SBB.'],'https://www.sbb.ch/de','everyday-life','SBB',trust='B',aliases=['zug','bus','train','поезд','поїзд'])
extra('bergli',['Montlinger Bergli','Montlinger Bergli','Montlinger Bergli','Montlinger Bergli'],['Der offizielle Natur-Eintrag beschreibt den Erlebnisraum und Rundweg auf dem Montlinger Bergli.','The official nature page describes the Montlinger Bergli recreation area and circular path.','Официальная страница описывает природную зону и круговой маршрут Montlinger Bergli.','Офіційна сторінка описує природну зону та круговий маршрут Montlinger Bergli.'],'https://www.oberriet.ch/natur/23738','culture-leisure','Politische Gemeinde Oberriet',locality='montlingen')
# Compact, verified waste facts. Dates are valid only in 2026.
wurl='https://www.oberriet.ch/online-schalter/101755/download';ws=source(wurl,ttl=1)
waste=[]
labels={'kehricht':['Kehricht','Household waste','Бытовой мусор','Побутове сміття'],'karton':['Karton','Cardboard','Картон','Картон'],'papier':['Altpapier','Paper','Бумага','Папір'],'gruen':['Grüngut','Green waste','Зелёные отходы','Зелені відходи'],'sperrgut':['Sperrgut','Bulky waste','Крупногабаритный мусор','Великогабаритне сміття'],'glas':['Glas','Glass','Стекло','Скло'],'metall':['Metall / Dosen','Metal / cans','Металл / банки','Метал / банки'],'textilien':['Textilien','Textiles','Текстиль','Текстиль'],'sonder':['Sonderabfall','Hazardous waste','Опасные отходы','Небезпечні відходи'],'elektro':['Elektrogeräte','Electronics','Электроника','Електроніка']}
notes={
'kehricht':['KVR-Gebührensäcke; ab 06.00 Uhr.','Use KVR fee bags; from 06:00.','Платные мешки KVR; с 06:00.','Платні мішки KVR; з 06:00.'],
'karton':['Gebündelt, gefaltet und mit Gebührenmarke; mit Kehricht.','Fold, tie and add a fee label; collected with household waste.','Сложить, связать и прикрепить платную марку; вместе с мусором.','Скласти, зв’язати й прикріпити платну марку; разом зі сміттям.'],
'papier':['Sauber und gebündelt; Karton separat.','Clean, tied bundles; cardboard separately.','Чистая связанная бумага; картон отдельно.','Чистий зв’язаний папір; картон окремо.'],
'gruen':['Keine Grüngut-Touren seit 2025; Selbstabgabe Oekoville bei ARA.','No collection rounds since 2025; take to Oekoville at ARA.','Сбора нет с 2025 года; самостоятельная сдача в Oekoville у ARA.','Збору немає з 2025 року; самостійне здавання в Oekoville біля ARA.'],
'sperrgut':['Gebührenmarken; Masse und Gewicht im Original prüfen.','Fee labels required; check size and weight limits in the original.','Нужны платные марки; размеры и вес проверьте в оригинале.','Потрібні платні марки; розміри й вагу перевірте в оригіналі.'],
'glas':['Farben trennen; Sammelstellen im offiziellen Plan.','Sort by colour; collection points in the official plan.','Разделите по цвету; пункты в официальном плане.','Розділіть за кольором; пункти в офіційному плані.'],
'metall':['Sammelstellen; Altmetallsammlung 2026 bereits vorbei.','Use collection points; the 2026 metal collection has passed.','Пункты приёма; сбор металлолома 2026 года уже прошёл.','Пункти приймання; збір металобрухту 2026 року вже минув.'],
'textilien':['Textilcontainer gemäss offiziellem Plan.','Textile containers listed in the official plan.','Контейнеры для текстиля — в официальном плане.','Контейнери для текстилю — в офіційному плані.'],
'sonder':['Nicht in Kehricht; Entsorgungsweg je Stoff im Original prüfen.','Do not put in household waste; check disposal by material.','Не выбрасывайте в бытовой мусор; способ сдачи зависит от вещества.','Не викидайте у побутове сміття; спосіб здавання залежить від речовини.'],
'elektro':['Rückgabe bei Verkaufsstellen / SENS / SWICO; nicht in Kehricht.','Return to retailers / SENS / SWICO; no household waste disposal.','Сдавайте в магазины / SENS / SWICO; не в бытовой мусор.','Здавайте до магазинів / SENS / SWICO; не в побутове сміття.']}
for kind in labels:waste.append({'id':kind,'title':dict(zip(['de','en','ru','uk'],labels[kind])),'instruction':dict(zip(['de','en','ru','uk'],notes[kind])),'source_id':ws,'source_url':wurl,'source_checked_at':D,'valid_from':'2026-01-01','valid_to':'2026-12-31','calendar_year':2026,'weekday':({'kriessern':1,'oberriet':3,'montlingen':3,'eichenwies':3} if kind in ['kehricht','karton','sperrgut'] else {}),'dates':({'oberriet':['2026-10-24'],'montlingen':['2026-11-07'],'eichenwies':['2026-11-07'],'kriessern':['2026-11-14']} if kind=='papier' else {}),'location_note':{'kobelwald':'Berggebiet: Zuständigkeit/Route vor Nutzung bestätigen; keine automatische Zuordnung.'}})
events=[{'id':'kilbi26','title':dict(zip(['de','en','ru','uk'],['Kilbi Oberriet','Oberriet village fair','Ярмарка Oberriet','Ярмарок Oberriet'])),'date':'2026-10-03','end_date':'2026-10-04','locality':'oberriet','type':'culture','location':'Oberriet','description':dict(zip(['de','en','ru','uk'],['Traditionelle Kilbi mit Vereinsbeizen und Sonntagsmarkt.','Village fair with association stalls and Sunday market.','Традиционная ярмарка и воскресный рынок.','Традиційний ярмарок і недільний ринок.'])),'source_url':'https://www.oberriet.ch/aktuellesinformationen/2996749','source_checked_at':D,'source_id':source('https://www.oberriet.ch/aktuellesinformationen/2996749',ttl=1)}, {'id':'marvin26','title':dict.fromkeys(['de','en','ru','uk'],'Marvin Sgier LIVE / 60 Jahre Jungwacht'),'date':'2026-10-02','end_date':'2026-10-02','start_time':'20:00','locality':'oberriet','type':'culture','location':'Sarasani, Oberriet','description':dict(zip(['de','en','ru','uk'],['Türöffnung 20.00 Uhr; Details beim Veranstalter.','Doors open at 20:00; check organiser details.','Открытие дверей в 20:00; детали у организатора.','Відкриття дверей о 20:00; деталі в організатора.'])),'source_url':'https://www.oberriet.ch/aktuellesinformationen/2996749','source_checked_at':D,'source_id':source('https://www.oberriet.ch/aktuellesinformationen/2996749',ttl=1)}]
places=[{'id':x.lower(),'title':x,'source_url':'https://www.oberriet.ch/5doerfer1gemeinde','source_id':source('https://www.oberriet.ch/5doerfer1gemeinde'),'source_checked_at':D} for x in ['Oberriet','Montlingen','Kriessern','Eichenwies','Kobelwald']]
intents=[{'id':s['id'],'phrases':s['synonyms']+[s['title'][l] for l in ['de','en','ru','uk']]} for s in services if s['synonyms'] or s['id'] in list(T)[:35]]
seed={'schema_version':1,'checked_at':D,'services':services,'contacts':contacts,'events':events,'waste':waste,'places':places,'sources':list(sources.values()),'intents':intents}
dump(R/'data/seed.json',seed);dump(R/'data/sources.json',seed['sources']);dump(R/'data/intents.json',intents)
dump(R/'wp-content/plugins/oberhub-core/seed.json',seed)
print(len(services),'services',len(contacts),'contacts',len(sources),'sources',len(intents),'intents')
