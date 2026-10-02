import pathlib,json
R=pathlib.Path(__file__).resolve().parents[1]
rows='''home|Start|Home|Главная|Головна
services|Dienstleistungen|Services|Услуги|Послуги
search|Suche|Search|Поиск|Пошук
calendar|Kalender|Calendar|Календарь|Календар
waste|Abfall|Waste|Отходы|Відходи
ask|Wohin wenden?|Who can help?|Куда обратиться?|Куди звернутися?
contact|Kontakte|Contacts|Контакты|Контакти
about|Über den Hub|About the Hub|О проекте|Про проєкт
sources|Quellen|Sources|Источники|Джерела
privacy|Datenschutz|Privacy|Конфиденциальность|Конфіденційність
imprint|Impressum|Legal notice|Выходные данные|Вихідні дані
accessibility|Barrierefreiheit|Accessibility|Доступность|Доступність
hero|Was möchten Sie erledigen?|What would you like to do?|Какой вопрос вы хотите решить?|Яке питання ви хочете вирішити?
intro|Ihr Weg zu den richtigen Stellen. Für fünf Dörfer, in vier Sprachen.|Find the right service. Five villages, four languages.|Найдите нужную службу. Пять деревень, четыре языка.|Знайдіть потрібну службу. П’ять сіл, чотири мови.
independent|Unabhängiger Informationsdienst|Independent information service|Независимый информационный сервис|Незалежний інформаційний сервіс
placeholder|Zum Beispiel: Umzug nach Montlingen|For example: register after moving|Например: переезд в Montlingen|Наприклад: переїзд до Montlingen
locality|Ihr Dorf|Your village|Ваша деревня|Ваше село
all|Alle Dörfer|All villages|Все деревни|Усі села
popular|Häufig gesucht|Popular services|Популярные услуги|Популярні послуги
upcoming|Demnächst in Oberriet|Coming up in Oberriet|Ближайшие события|Найближчі події
nextwaste|Nächste Abfuhr|Next collection|Ближайший вывоз|Найближче вивезення
official|Offiziellen Dienst öffnen|Open official service|Открыть официальную услугу|Відкрити офіційну послугу
source|Quelle|Source|Источник|Джерело
checked|Geprüft|Checked|Проверено|Перевірено
details|Details ansehen|View details|Подробнее|Докладніше
fee|Gebühr|Fee|Стоимость|Вартість
requirements|Unterlagen / Voraussetzungen|Documents / requirements|Документы / условия|Документи / умови
unknown|Nicht bestätigt — im Original prüfen.|Not verified — check the official page.|Не подтверждено — проверьте в оригинале.|Не підтверджено — перевірте в оригіналі.
noresults|Keine geprüfte Antwort gefunden. Bitte wählen Sie ein Thema oder fragen Sie bei der Gemeinde nach.|No verified answer found. Choose a topic or contact the municipality.|Подтверждённый ответ не найден. Выберите тему или обратитесь в администрацию общины.|Підтверджену відповідь не знайдено. Виберіть тему або зверніться до адміністрації громади.
results|Suchergebnisse|Search results|Результаты поиска|Результати пошуку
sensitive|Bitte keine vertraulichen oder besonders schützenswerten Daten eingeben.|Please do not enter confidential or sensitive personal data.|Не вводите конфиденциальные или особо чувствительные данные.|Не вводьте конфіденційні або особливо чутливі дані.
free|Ohne Konto. Ohne Werbetracking.|No account. No advertising tracking.|Без регистрации. Без рекламного отслеживания.|Без реєстрації. Без рекламного відстеження.
viewall|Alle anzeigen|View all|Показать все|Показати всі
administration|Verwaltung & Steuern|Administration & taxes|Администрация и налоги|Адміністрація та податки
everyday-life|Alltag & Infrastruktur|Everyday life & infrastructure|Быт и инфраструктура|Побут та інфраструктура
health-social|Gesundheit & Soziales|Health & social support|Здоровье и социальная помощь|Здоров’я та соціальна допомога
education-family|Schule & Familie|Schools & family|Школа и семья|Школа та сім’я
jobs-business|Arbeit & Wirtschaft|Jobs & business|Работа и бизнес|Робота та бізнес
culture-leisure|Kultur & Freizeit|Culture & leisure|Культура и досуг|Культура та дозвілля
filter|Filtern|Filter|Фильтр|Фільтр
type|Art|Type|Тип|Тип
today|Heute|Today|Сегодня|Сьогодні
week|Diese Woche|This week|Эта неделя|Цей тиждень
month|Dieser Monat|This month|Этот месяц|Цей місяць
future|Alle kommenden Termine|All upcoming dates|Все будущие даты|Усі майбутні дати
export|Kalenderdatei (.ics)|Calendar file (.ics)|Файл календаря (.ics)|Файл календаря (.ics)
noevents|Keine bestätigten Termine für diese Auswahl. Offiziellen Kalender prüfen.|No verified events match these filters. Check the official calendar.|Нет подтверждённых событий по фильтру. Проверьте официальный календарь.|Немає підтверджених подій за фільтром. Перевірте офіційний календар.
valid|Gültig für|Valid for|Действует для|Чинне для
noconfirmeddate|Keine nächste bestätigte Abfuhr: offizielle Anleitung / Route prüfen.|No next verified collection: check the official instructions / route.|Следующая дата не подтверждена: проверьте инструкцию и маршрут.|Наступна дата не підтверджена: перевірте інструкцію та маршрут.
expired|Kalender abgelaufen. Keine aktuellen Termine bestätigt.|Calendar expired. No current dates verified.|Календарь устарел. Актуальные даты не подтверждены.|Календар застарів. Актуальні дати не підтверджено.
problem|Was ist Ihr Anliegen?|What do you need help with?|Опишите ваш вопрос|Опишіть ваше питання
classify|Zuständige Stelle finden|Find the right contact|Найти ответственную службу|Знайти відповідальну службу
compose|Deutsche E-Mail vorbereiten|Prepare an email in German|Подготовить письмо на немецком|Підготувати лист німецькою
name|Name (optional)|Name (optional)|Имя (необязательно)|Ім’я (необов’язково)
location|Strasse / Standort|Street / location|Улица / место|Вулиця / місце
description|Beschreibung|Description|Описание|Опис
subject|Betreff|Subject|Тема письма|Тема листа
recipient|Empfänger — vor Versand prüfen|Recipient — verify before sending|Получатель — проверьте перед отправкой|Одержувач — перевірте перед надсиланням
copy|Text kopieren|Copy text|Скопировать текст|Скопіювати текст
openmail|E-Mail öffnen|Open email app|Открыть почтовый клиент|Відкрити поштовий клієнт
copied|Text kopiert.|Text copied.|Текст скопирован.|Текст скопійовано.
copyfailed|Bitte Text markieren und manuell kopieren.|Select the text and copy it manually.|Выделите текст и скопируйте вручную.|Виділіть текст і скопіюйте вручну.
clear|Entwurf löschen|Clear draft|Очистить черновик|Очистити чернетку
localdraft|Der Entwurf bleibt nur auf dieser Seite. Nichts wird gespeichert oder übermittelt. Fremdsprachige Beschreibung vor Versand ins Deutsche übertragen.|The draft stays on this page. Nothing is saved or submitted. Translate a non-German description before sending.|Черновик остаётся только на этой странице. Он не сохраняется и не отправляется. Описание на другом языке переведите на немецкий перед отправкой.|Чернетка залишається лише на цій сторінці. Вона не зберігається й не надсилається. Опис іншою мовою перекладіть німецькою перед надсиланням.
translationdraft|Redaktionelle Übersetzung, nicht amtlich.|Editorial translation, not official.|Редакционный перевод, не официальный.|Редакційний переклад, не офіційний.
needsreview|Quelle oder Übersetzung braucht eine erneute Prüfung.|Source or translation needs another review.|Источник или перевод требует повторной проверки.|Джерело або переклад потребує повторної перевірки.
skip|Zum Inhalt springen|Skip to content|Перейти к содержимому|Перейти до вмісту
emergency|Notfall|Emergency|Экстренная помощь|Екстрена допомога
notfound|Seite nicht gefunden|Page not found|Страница не найдена|Сторінку не знайдено
breadcrumb|Sie sind hier|You are here|Вы здесь|Ви тут
knowledge|Orientierung mit verlinkten Originalen|Guidance with original sources|Навигация со ссылками на оригиналы|Навігація з посиланнями на оригінали
routeconfirm|Zuständigkeit vor Versand bestätigen.|Confirm responsibility before sending.|Уточните компетенцию перед отправкой.|Уточніть компетенцію перед надсиланням.
quality|Geprüfte Quellen statt Vermutungen|Verified sources, clear next steps|Проверенные источники и понятные действия|Перевірені джерела та зрозумілі дії'''
ui={l:{} for l in ['de','en','ru','uk']}
for row in rows.splitlines():
 k,*values=row.split('|')
 for l,v in zip(ui,values):ui[l][k]=v
foot=['Oberriet Hub ist ein unabhängiger Informationsdienst und keine offizielle Website der Politischen Gemeinde Oberriet oder des Kantons St.Gallen. Verbindliche Informationen finden Sie bei der verlinkten Behörde.','Oberriet Hub is an independent information service, not an official website of the municipality of Oberriet or the canton of St.Gallen. For authoritative information, consult the linked authority.','Oberriet Hub — независимый информационный сервис, а не официальный сайт общины Oberriet или кантона St. Gallen. Окончательную информацию предоставляет указанное ведомство.','Oberriet Hub — незалежний інформаційний сервіс, а не офіційний сайт громади Oberriet або кантону St. Gallen. Остаточну інформацію надає зазначений орган.']
for l,v in zip(ui,foot):ui[l]['disclaimer']=v
(R/'wp-content/plugins/oberhub-core/ui.json').write_text(json.dumps(ui,ensure_ascii=False,indent=2))

extra=json.loads((R/'data/ui-extra.json').read_text())
for k,values in extra.items():
 for l,v in zip(ui,values):ui[l][k]=v
(R/'wp-content/plugins/oberhub-core/ui.json').write_text(json.dumps(ui,ensure_ascii=False,indent=2))
