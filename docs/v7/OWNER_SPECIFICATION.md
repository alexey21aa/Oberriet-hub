# OBER RIET HUB — V7 UNIVERSAL LOCAL INTELLIGENCE
## Massive Coverage / Business Graph / 100k+ Intent Universe / Continue Existing Project
### Master continuation prompt for ChatGPT Work

> **ВАЖНО: ЭТО ПРОДОЛЖЕНИЕ СУЩЕСТВУЮЩЕГО ПРОЕКТА. НЕ НАЧИНАТЬ С НУЛЯ.**
>
> Работать с уже существующими:
> - Production: `https://oberriethub.ch`
> - GitHub: `alexey21aa/Oberriet-hub`
> - WordPress + WPVibe
> - `WORK_STATE.md`
> - текущей production-базой
> - текущим V6 index
> - текущими scripts/tests/releases
>
> Перед любым изменением:
> 1. Получить фактический `HEAD` ветки `main`.
> 2. Полностью прочитать `WORK_STATE.md`.
> 3. Проверить production через WPVibe.
> 4. Проверить текущие версии Core/V6 addon.
> 5. Проверить `/oberhub/v1/v6/status`.
> 6. Прогнать live acceptance.
> 7. Считать фактический HEAD + `WORK_STATE.md` + production единственной истиной.
>
> На момент подготовки этого задания последний наблюдавшийся checkpoint был `46256e7`, production — Core 0.2.3 + V6 addon 0.1.0, live V6 index — около 4 523 records / 5 728 terms. **Эти цифры могли измениться — перепроверить, не откатывать более новое состояние.**

---

# 1. НОВАЯ ГЛАВНАЯ ЦЕЛЬ

Oberriet Hub должен перестать быть просто справочником Gemeinde и стать **универсальной локальной интеллектуальной системой уровня “ChatGPT для Rheintal”**.

Пользователь должен иметь возможность написать практически любое обычное жизненное слово или вопрос:

- `сауна`
- `батуты`
- `бассейн`
- `где отремонтировать кофемашину`
- `танцы для женщин`
- `спорт ребенку 7 лет`
- `где купить корм для попугая`
- `нотариус`
- `психолог говорит по-русски`
- `кто чинит крышу`
- `работа ночью`
- `бесплатный немецкий`
- `куда сдать старый холодильник`
- `детский день рождения в помещении`
- `прокат прицепа`
- `где сделать ключ`
- `маникюр вечером`
- `есть ли фермерский магазин`
- `куда поехать с ребенком в дождь`
- `помощь с долгами`
- `налоговая декларация`
- `ветеринар ночью`
- `снять зал`
- `сауна сегодня`
- `банкомат`
- `ремонт телефона`
- `прачечная`
- `установка солнечных панелей`
- `стоматолог`
- `стройматериалы`
- `курсы вождения`
- `бассейн для малыша`
- `велоремонт`
- `рыбалка`
- `игровая комната`
- `сквош`
- `стрельба`
- `шахматы`
- `шиномонтаж`
- `няня`
- `сиделка`
- `Spitex`
- `таблетки ночью где купить`
- `дешево поесть`
- `что открыто в воскресенье`

и получать **реальные локальные варианты, а не пустой результат**.

Если в Oberriet ничего нет, система должна автоматически расширять географию и находить ближайшее полезное решение.

---

# 2. КРИТИЧЕСКОЕ ПРАВИЛО: НЕ ЗАЦИКЛИВАТЬСЯ НА ПРИМЕРАХ

Запросы `сауны`, `батуты`, `бассейны` являются только симптомами недостаточной ширины базы.

Не исправлять проект путем добавления трех ручных карточек.

Вместо этого построить универсальную систему покрытия **всего пространства человеческих потребностей, бизнеса, услуг, организаций, досуга, государства, здоровья, работы и быта**.

Каждый обнаруженный пробел должен приводить к расширению класса/таксономии, а не к локальному hardcode одного запроса.

---

# 3. НОВЫЕ KPI ПО ШИРИНЕ

## 3.1 Concept Universe

Создать `Universal Life & Local Commerce Ontology`.

Целевые показатели:

- **минимум 25 000 canonical concepts**, реально различающихся по смыслу;
- **минимум 100 000 unique concept-bearing terms / search intents** после дедупликации;
- синонимы, орфографические варианты и простые переводы **не засчитывать как новые темы**;
- поверх этого допускаются сотни тысяч/миллионы surface forms для поиска;
- миллионы возможных запросов получать **комбинаторно**, а не путем ручной записи миллионов строк.

Пример:

Canonical concept:
`indoor_trampoline_park`

Не считать отдельными темами:
- trampoline park
- Trampolinpark
- батутный парк
- батуты
- батутний парк

Это aliases/translations одного concept.

Но считать различными:
- trampoline park
- gymnastics club
- indoor playground
- climbing hall
- swimming pool
- sauna
- thermal spa
- escape room
- bowling
- laser tag

## 3.2 Intent Universe

Построить композиционный `Intent Cube`:

`ACTION × OBJECT × AUDIENCE × ATTRIBUTE × PRICE × TIME × GEO × ACCESSIBILITY`

Примеры отдельных intent combinations:

- find + swimming pool + child + indoor + today + 10km
- find + dance class + women + beginner + evening + low-cost
- find + pharmacy + emergency + open-now
- find + tax-help + low-income + free
- find + restaurant + child-friendly + Sunday
- find + garage + tyre-change + today
- find + German-course + A1 + morning + free
- find + lawyer + tenancy + low-cost
- find + sauna + women-only + evening
- find + repair + smartphone + same-day

Так система понимает **миллионы естественных запросов**, не сохраняя миллионы страниц.

---

# 4. ОТКУДА ВЗЯТЬ 100 000+ НЕПОВТОРЯЮЩИХСЯ ТЕМ

Не придумывать случайные слова вручную.

Собрать canonical universe программно из реальных классификаций и источников.

## 4.1 Swiss NOGA 2025
Источник:
`https://www.kubb-tool.bfs.admin.ch/de/noga/2025`

Использовать:
- все разделы;
- divisions;
- groups;
- classes;
- explanatory terms;
- KUBB activity keywords, если их использование разрешено;
- реальные экономические виды деятельности.

NOGA должна стать backbone для бизнеса:
- agriculture
- manufacturing
- construction
- wholesale
- retail
- transport
- food
- IT
- telecom
- finance
- real estate
- professional services
- health
- education
- arts
- sport
- repair
- personal services
- associations
- etc.

## 4.2 OpenStreetMap taxonomy
Использовать OSM/Taginfo/Overpass для:
- `amenity=*`
- `shop=*`
- `craft=*`
- `office=*`
- `healthcare=*`
- `leisure=*`
- `sport=*`
- `tourism=*`
- `club=*`
- `social_facility=*`
- `man_made=*`
- `service=*`
- relevant `industrial=*`
- `landuse=*`
- transport/public_transport tags.

Не загружать каждый технический OSM tag как тему. Выбирать только человеко-ориентированные категории.

## 4.3 Swiss government service taxonomy
Собрать:
- ch.ch;
- sg.ch;
- Schalter E;
- Gemeinde services;
- SVA;
- tax;
- courts;
- federal agencies;
- forms and public services.

## 4.4 Medicine
Canonical concepts брать из:
- медицинских специальностей MedReg;
- GesReg/NAREG;
- PsyReg;
- hospital/service taxonomies;
- public-health service categories.

Не копировать лицензированные клинические онтологии без проверки лицензии.

## 4.5 Education / jobs
Использовать:
- Swiss occupation/education categories;
- vocational fields;
- Weiterbildung categories;
- apprenticeship categories;
- job families;
- employer service categories.

## 4.6 Human-life ontology
Добавить структурированные категории:
- hobbies
- sports
- children's activities
- household tasks
- repairs
- shopping needs
- beauty/personal care
- pets
- food
- travel
- nightlife
- nature
- disability
- senior needs
- family needs
- low-income needs
- integration
- languages
- legal problems
- insurance
- finance
- digital services
- emergencies
- religion/community only as neutral practical information.

---

# 5. BUSINESS GRAPH — МАКСИМАЛЬНО ПОЛНЫЙ СПИСОК ФИРМ

Создать отдельную сущность:

`business_entity`

Она не должна быть обычной service card.

## 5.1 Цель

Собрать максимально полный business graph для:

1. Gemeinde Oberriet;
2. Altstätten;
3. Rüthi;
4. Eichberg;
5. Rebstein;
6. Marbach;
7. Balgach;
8. Widnau;
9. Diepoldsau;
10. Au;
11. Berneck;
12. St. Margrethen;
13. весь St. Galler Rheintal;
14. соседний Werdenberg/Buchs для полезных категорий;
15. Liechtenstein;
16. ближайший Vorarlberg — только когда он реально ближе/полезнее;
17. St.Gallen city — для региональных услуг.

## 5.2 Официальный реестр компаний

Использовать официальные открытые данные Zefix/LINDAS как важный backbone зарегистрированных юридических лиц.

Стартовые официальные страницы:
- `https://www.bj.admin.ch/de/handelsregister-zefix-und-regix`
- Zefix/OpenData/LINDAS links from that page.

Не пытаться массово скрейпить UI Zefix, если доступен официальный linked/open dataset.

Использовать:
- company/legal name;
- active status;
- legal seat;
- domicile/address where public;
- legal form;
- identifier where lawfully public.

## 5.3 UID
Источник:
`https://www.uid.admin.ch/`

Использовать публичную информацию для entity resolution/verification, а не как повод хранить лишние персональные данные.

## 5.4 OSM / Overpass
Mass harvest по географическому bounding polygon.

Главная бесплатная discovery-система для микро-бизнеса и POI.

## 5.5 Официальные сайты компаний
После discovery каждой компании попытаться найти официальный домен.

Из официального сайта извлекать:
- services;
- product/service categories;
- address;
- public phone;
- public general email;
- opening hours;
- appointment requirements;
- booking;
- prices where explicitly published;
- target customers;
- branch locations;
- current notices;
- seasonal hours;
- accessibility;
- service area.

Использовать Schema.org/JSON-LD, когда он есть.

## 5.6 Малый и микро-бизнес
Не исключать:
- sole proprietors;
- one-person workshops;
- home/mobile services with a public business listing;
- small salons;
- therapists;
- repairers;
- craftspeople;
- farm shops;
- self-service farm stands;
- local instructors;
- small studios;
- freelancers;
- small garages;
- trades;
- clubs that provide public activities.

Но:
- не раскрывать домашний/private address, если он не опубликован именно как business contact;
- не индексировать частного человека только потому, что его имя случайно встречается в сети.

---

# 6. GOOGLE MAPS: ИСПОЛЬЗОВАТЬ ГРАМОТНО

Пользователь хочет охватить даже фирмы, которые едва видны в Google/Google Maps.

Это означает:

- Google Search/Maps могут использоваться для **discovery и spot verification** в Work/browser;
- официальный Google Places API можно использовать только в рамках его актуальных условий и квот;
- **не строить бесплатную постоянную базу путем массового копирования/кэширования Google Maps content**;
- не делать Google единственным источником;
- для постоянного хранения фактов предпочитать:
  1. официальный сайт бизнеса;
  2. Zefix/UID;
  3. OSM;
  4. municipal/association directory;
  5. provider page;
  6. другой источник с разрешенным повторным использованием.

Google Place ID может храниться как external reference только если это соответствует актуальным Terms.

Главная задача — добиться **сопоставимого покрытия**, а не незаконно клонировать Google Maps.

---

# 7. OPENING HOURS И "ГРАФИК СМЕН"

Разделять два разных понятия.

## 7.1 Customer opening hours

Хранить:
- regular opening hours;
- weekday intervals;
- breaks;
- Sunday/holiday rules;
- appointment-only;
- seasonal schedules;
- temporary closure;
- source and checked_at.

Источники:
- official website;
- official booking page;
- OSM opening_hours if current;
- trusted public directory only as fallback.

Поддержать:
- `open now`
- `open tonight`
- `open Sunday`
- `open after 18:00`
- `open tomorrow morning`.

## 7.2 Employer shift patterns

Никогда не выдумывать расписание сотрудников.

Хранить только публично опубликованное:
- 2-Schicht;
- 3-Schicht;
- Nachtschicht;
- Wochenenddienst;
- Pikett;
- flexible shifts;
- rotating shifts.

Источники:
- current employer career page;
- live job advertisement;
- collective agreement;
- official company documentation.

TTL для job/shift claims: короткий, например 7–30 дней.

Никогда не хранить employee rosters.

---

# 8. PRODUCT / SERVICE DISCOVERY

Для каждого бизнеса недостаточно знать только название.

Нужно понимать **что именно у него можно получить**.

Примеры:
- bakery → bread, pastries, breakfast, catering
- garage → tyres, inspection preparation, diagnostics, bodywork
- wellness → sauna, steam bath, massage, pool
- sports centre → swimming, sauna, fitness, children's lessons
- farm shop → eggs, milk, vegetables, self-service
- electronics repair → phones, tablets, laptops
- dance studio → children, adults, women, styles, levels
- carpenter → windows, doors, furniture, renovations

Создать:
- `business_offering`
- `product_service_concept`
- `business_has_offering`

Не пытаться копировать весь ecommerce catalogue.
Индексировать категории, ключевые услуги и явно опубликованные специализации.

---

# 9. ENTITY DEDUPLICATION

Один объект может встретиться в:
- Zefix;
- UID;
- OSM;
- company website;
- municipality directory;
- association page;
- directory;
- event calendar.

Не создавать семь карточек.

Canonical merge keys:
1. UID if available;
2. official domain;
3. exact phone;
4. name + address;
5. coordinates + normalized name;
6. manually reviewed mapping.

Хранить `source_links[]` и `source_provenance[]`.

Слияние должно быть обратимым и auditable.

---

# 10. UNIVERSAL BUSINESS TAXONOMY

Минимально покрыть каждый NOGA sector и полезные consumer-facing subcategories.

Развернуть в реальные human search concepts:

## Agriculture / food production
- farms
- dairy
- eggs
- fruit
- vegetables
- wineries
- farm shops
- agricultural services
- machinery
- forestry
- wood.

## Construction / trades
- architect
- civil engineer
- electrician
- plumber
- heating
- ventilation
- solar
- roofer
- carpenter
- painter
- plasterer
- tiler
- flooring
- windows
- doors
- kitchen
- bathroom
- landscaping
- excavation
- scaffolding
- locksmith
- metalwork
- cleaning
- facility management.

## Automotive
- garages
- tyres
- body shop
- paint
- diagnostics
- towing
- vehicle inspection prep
- motorcycles
- bicycles
- e-bikes
- fuel
- car wash
- rental
- trailers.

## Retail
All relevant categories:
- food
- clothing
- shoes
- sports
- electronics
- appliances
- furniture
- household
- toys
- baby
- books
- stationery
- pet
- garden
- tools
- construction
- pharmacy
- cosmetics
- optician
- jewellery
- second hand.

## Personal services
- hair
- barber
- beauty
- nails
- massage
- tattoo/piercing
- laundry
- tailoring
- shoe repair
- key cutting
- photography
- funeral services.

## Professional
- lawyers
- notaries
- trustees
- accountants
- tax advisers
- insurance
- banks
- real estate
- architects
- engineers
- IT
- cybersecurity
- web
- marketing
- translation
- recruiting.

И так далее по всей NOGA 2025.

---

# 11. LEISURE / SPORT: ОСОБОЕ ПОКРЫТИЕ

Чтобы больше не было нулей по базовым понятиям, создать exhaustive category tree:

- swimming pool
- indoor pool
- outdoor pool
- thermal bath
- wellness
- sauna
- steam bath
- spa
- trampoline park
- indoor playground
- soft play
- climbing gym
- bouldering
- escape room
- bowling
- laser tag
- cinema
- theatre
- arcade
- billiards
- darts
- squash
- tennis
- badminton
- table tennis
- football
- futsal
- basketball
- volleyball
- handball
- gymnastics
- athletics
- dance
- ballet
- hip-hop
- contemporary
- ballroom
- pole
- Zumba
- yoga
- Pilates
- fitness
- functional training
- martial arts
- karate
- judo
- taekwondo
- boxing
- kickboxing
- wrestling
- shooting
- archery
- riding
- skiing
- snowboarding
- skating
- hockey
- fishing
- boating
- SUP
- cycling
- MTB
- running
- hiking
- playground
- water playground
- picnic
- BBQ
- zoo
- animal park
- museum
- science centre
- family excursion
- rainy-day activity.

Это не полный список — использовать его как seed и расширять программно.

---

# 12. MEDICAL BUSINESS/PROVIDER GRAPH

Официальные sources:
- MedReg / BAG
- GesReg/NAREG
- PsyReg
- hospitals
- cantonal health authorities
- pharmacies/hospital registers
- official practice/provider sites.

MedReg includes public information about doctors, dentists, pharmacists, veterinarians and chiropractors and may expose specialisation, licence and work contact information.

Use it carefully:
- provider identity;
- profession;
- public practice location;
- language if public;
- specialisation;
- licence status where relevant.

Do not infer medical quality/ranking.

---

# 13. SEARCH INDEX ARCHITECTURE FOR 100K+ CONCEPTS

Do **not** store 100,000 concepts as 100,000 WordPress posts unless there is a reason.

Split data:

## WordPress
Store:
- curated published services;
- businesses/entities;
- events;
- contacts;
- source records;
- forms.

## Build artifacts/search index
Store:
- concept ontology;
- aliases;
- translations;
- typo models;
- intent templates;
- semantic expansion;
- compact lookup tables.

Recommended files/tables:
- `concepts.jsonl`
- `concept_aliases.jsonl`
- `business_entities.jsonl`
- `business_offerings.jsonl`
- `geo_cells/*.json.gz`
- `intent_patterns.json`
- `query_concepts.json`
- `entity_aliases.json`
- `source_registry.jsonl`

If DB scale becomes high:
- use custom InnoDB tables;
- avoid `wp_postmeta` for high-cardinality search fields;
- shard indexes by geo/category;
- cache hot queries.

---

# 14. 100K CONCEPT BUILD PIPELINE

Create reproducible scripts.

Example:
- `scripts/build-universal-concepts.py`
- `scripts/import-noga.py`
- `scripts/import-osm-taxonomy.py`
- `scripts/build-human-life-ontology.py`
- `scripts/dedupe-concepts.py`
- `scripts/generate-intents.py`
- `scripts/generate-query-tests.py`

Pipeline:

1. ingest taxonomies;
2. normalize labels;
3. map translations;
4. semantic cluster;
5. reject aliases as separate concepts;
6. reject near-duplicate/tautology;
7. assign canonical ID;
8. assign parent/children;
9. assign source/license;
10. generate search aliases separately;
11. generate test queries separately.

---

# 15. ANTI-TAUTOLOGY GATE

A concept does NOT count toward the 100k KPI if it is only:
- translation;
- plural;
- spelling variant;
- hyphen difference;
- word order;
- typo;
- morphology;
- trivial "service/shop/provider" suffix;
- location-specific clone.

Use:
- normalized label comparison;
- language-aware stemming;
- lexical similarity;
- embedding similarity where possible;
- parent/category analysis.

Maintain:
`concept_metrics.json`

with:
- canonical_concepts
- unique_terms
- aliases
- translations
- typo_forms
- rejected_duplicates
- source breakdown.

---

# 16. MASS BUSINESS HARVEST PIPELINE

Create a reproducible and resumable harvester.

Pseudo stages:

`Zefix/LINDAS → UID → OSM → municipal directories → official websites → entity merge → offerings → opening hours → source receipts → index`

Properties:
- atomic progress;
- resumable offsets/cursors;
- rate limiting;
- robots respect;
- timeouts;
- conditional GET;
- per-source failure receipts;
- no single failure stops run;
- dedupe before import;
- dry-run;
- candidate vs live state separation.

Do not import unverified discovery metadata as reviewed facts.

---

# 17. COVERAGE BY MUNICIPALITY

Generate automated coverage reports by municipality.

Example dimensions:
- total businesses
- businesses by NOGA category
- OSM POIs
- healthcare
- food
- retail
- repair
- sport
- leisure
- education
- childcare
- beauty
- finance
- construction
- transport
- tourism.

Detect suspicious holes.

Example:
If municipality has:
- 0 hairdresser
- 0 garage
- 0 restaurant
- 0 electrician

but external discovery suggests otherwise, flag for review.

---

# 18. KNOWLEDGE GAP HUNTER

Existing zero-result analytics must drive expansion.

For every query:
- normalized query
- concept extraction
- zero/weak result
- expected category
- geo scope
- language
- typo-corrected form.

Daily/periodic process:
1. cluster missing queries;
2. identify missing concepts;
3. search sources;
4. add or map concept;
5. add entities;
6. add regression tests.

Never save raw personal/sensitive user queries long-term.
Use privacy-safe normalized aggregation.

---

# 19. ACCEPTANCE TEST MATRIX

Do not test only five legacy queries.

Build at least:

- 2 000 canonical concept tests;
- 2 000 typo tests;
- 1 000 multilingual tests;
- 1 000 geo fallback tests;
- 500 opening-hours/date tests;
- 500 audience/age tests;
- 500 price/free/KulturLegi tests;
- 500 business/service tests;
- 500 legal/medical safety tests.

Then expand automatically as ontology grows.

Mandatory smoke terms in all supported languages:
- sauna
- trampoline
- swimming pool
- dentist
- lawyer
- pharmacy
- garage
- tyre shop
- hairdresser
- nails
- restaurant
- bakery
- butcher
- supermarket
- farm shop
- electrician
- plumber
- roofer
- solar
- childcare
- dance
- football
- gymnastics
- martial arts
- playground
- indoor playground
- museum
- veterinarian
- pet shop
- bank
- ATM
- post
- parcel
- printing
- phone repair
- computer repair
- taxi
- car rental
- trailer rental
- moving company
- cleaning
- laundry
- key cutting
- second hand
- furniture
- school
- German course
- debt help
- tax help
- insurance
- pension
- disability
- seniors
- Spitex.

For each concept, test:
- exact term;
- one typo;
- natural-language question;
- Russian/Ukrainian mixed spelling if relevant;
- geo-expansion behavior.

---

# 20. RESULT QUALITY

A search result should not merely show a title.

For local/business queries return structured cards:

- name
- why it matches
- category
- locality
- distance where coordinates exist
- opening status if verified
- price if verified
- audience/age if relevant
- phone
- website
- booking
- source
- last verified.

If opening hours are stale or contradictory:
say `Öffnungszeiten bitte beim Anbieter prüfen`.

Never fabricate "open now".

---

# 21. RANKING

Ranking priority:

1. exact intent
2. exact offering
3. exact geo
4. verified primary source
5. fresh opening/availability
6. distance
7. audience/age fit
8. price/discount fit
9. semantic relevance.

Do not rank a generic municipal PDF above the actual local provider for a consumer request.

For official/legal/tax questions, authoritative government sources outrank commercial providers.

---

# 22. GENERATIVE ANSWER

Primary answer remains server-side/device-independent.

Before generative synthesis:
- retrieve evidence;
- rerank;
- remove duplicates;
- freshness filter;
- source trust filter.

For high-risk medical/legal/tax:
- ground only in reviewed evidence;
- no diagnosis;
- no personalised legal determination;
- no invented amount/deadline;
- show official next step.

For ordinary local discovery:
AI may summarize:
- best nearby options;
- differences;
- distance;
- cost;
- age/audience;
- what is open;
provided these facts are in evidence.

---

# 23. FREE AI COST CONTROL

Do not let AI cost block database expansion.

Order:
1. precomputed answer packs for popular intents;
2. cache;
3. deterministic evidence synthesis;
4. free server AI providers;
5. local inference only as optional privacy/offline mode.

Never reduce data/retrieval quality for weak devices.

Do not wait for generative credentials to continue data coverage.

---

# 24. COPYRIGHT / DATA LICENSING

Every source adapter needs:
- source name
- source URL
- licence/terms status
- permitted storage
- permitted redistribution
- attribution
- caching policy.

Do not bulk copy:
- Google Maps reviews/photos/content;
- paid directories;
- copyrighted articles;
- proprietary product databases.

Prefer facts, IDs, links and independently written summaries.

---

# 25. DATA QUALITY LEVELS

Define:

A — official government/open register  
B — primary provider/business website  
C — trusted directory/association  
D — discovery-only

Rules:
- legal/tax = A required for binding procedure facts;
- medical provider facts = A/B;
- opening hours = preferably B, OSM/C fallback with freshness warning;
- discovery-only D never enough for high-confidence answer.

---

# 26. FRESHNESS

Suggested TTL:
- transport realtime: seconds/minutes
- current open status: compute from latest published hours
- job/shift information: 7–30 days
- events: daily
- temporary notices: daily
- business hours: 14–30 days
- prices: 30 days
- business offerings: 60–90 days
- contacts: 30–90 days
- legal/tax procedure: 30–90 days
- stable historical/tourism: 180–365 days.

---

# 27. DO NOT EXPLODE WORDPRESS

If entity volume becomes tens/hundreds of thousands:
- keep presentation in WordPress;
- move bulk searchable corpus to optimized custom tables/static shards;
- do not create millions of WP posts;
- do not let admin UI become unusable;
- build batch import/export tools.

Threshold review:
- >25k entities: benchmark DB/query latency;
- >100k entities: require sharded/optimized index;
- >500k searchable records: separate search storage or edge index if needed.

No architecture migration without benchmark evidence.

---

# 28. SPEED OF EXECUTION

Do not attempt to manually verify 100k entities one-by-one.

Use automation:
- bulk open data ingestion;
- entity resolution;
- website discovery;
- structured data extraction;
- automated source receipts;
- confidence scoring;
- review queue.

Human/Work manual review is reserved for:
- conflicting facts;
- high-risk legal/medical/tax;
- high-value providers;
- ambiguous entities;
- failed automated extraction.

---

# 29. CURRENT PRIORITY BATCHES

Immediately after state verification, parallelize/sequence independent batches:

## Batch A — Universal ontology
Build canonical concept registry from NOGA + OSM + current taxonomy.

## Batch B — Business inventory
Rheintal registered companies + OSM POIs.

## Batch C — Leisure gap
Wellness, pools, sauna, trampoline, indoor play, sport, family leisure.

## Batch D — micro-business enrichment
Crafts, repairs, beauty, retail, professional services.

## Batch E — health
Official/public providers and specialties.

## Batch F — commerce/opening hours
Official website enrichment.

## Batch G — geo
Coordinates, locality, radius fallback.

## Batch H — search tests
Generate concept/typo/multilingual acceptance.

Work continuously; do not block Batch A because Batch E has a source issue.

---

# 30. GITHUB CHECKPOINT DISCIPLINE

Every meaningful batch:
- commit code/data/scripts/results;
- keep `WORK_STATE.md` updated;
- record metrics;
- record candidate/live distinction;
- record source failures;
- record next cursor/offset;
- record tests.

Before context/window exhaustion:
- push everything;
- update `WORK_STATE.md`;
- leave exact continuation commands.

---

# 31. LIVE DEPLOYMENT SAFETY

Production currently contains working data.

Never:
- wipe index;
- replace live corpus with smaller seed;
- repeat activity imports;
- overwrite Core 0.2.3 with older candidate;
- deploy a candidate only because unit tests pass.

For each deployment:
1. current status snapshot
2. backup/checkpoint
3. additive import
4. build isolated index
5. live smoke
6. rollback path.

---

# 32. SUCCESS CRITERIA FOR THIS EXPANSION PHASE

This phase is not done when "sauna" works.

It is done when:

1. A canonical ontology exists with >=25k genuinely distinct concepts.
2. The normalized intent/term universe contains >=100k non-tautological concept-bearing entries after dedupe.
3. Synonyms/typos/translations are tracked separately and do not inflate concept metrics.
4. Business inventory for St. Galler Rheintal is built from official/open/discovery sources.
5. Small/micro businesses are represented where publicly discoverable.
6. Each business can carry offerings and opening hours.
7. Search automatically expands geography when local inventory is empty.
8. Common one-word consumer queries return relevant real options.
9. Exact categories do not get drowned in administrative noise.
10. The five old mandatory queries still pass.
11. `сауны`, `батуты`, `бассейны` and equivalent DE/EN/UK forms pass live after relevant data deployment.
12. Hundreds/thousands of additional concept smoke tests pass.
13. All search remains device-independent.
14. Provenance/freshness is visible.
15. Production safety is preserved.

---

# 33. IMPORTANT REALISM RULE

Do not report success from inflated counters.

Bad metrics:
- one concept × four languages = four concepts;
- 100 typos = 100 topics;
- one business cloned by locality = multiple businesses;
- one page split into 50 fragments = 50 services.

Report separately:
- canonical concepts
- aliases
- translations
- typo forms
- entities
- offerings
- source pages
- index documents
- intents
- generated query tests.

Only canonical concepts count toward concept breadth.

---

# 34. FINAL EXECUTION INSTRUCTION

**Start immediately after reading current repository/live state. Do not write me a long plan before acting.**

Proceed autonomously with the largest safe amount of work possible.

If a source or deployment operation is blocked:
- preserve progress,
- switch to another expansion batch,
- never sit idle.

The project goal is maximum practical coverage of human life in and around Rheintal, with a search system capable of understanding simple words, complex questions, misspellings, multilingual input and geographic constraints.

The intended end state is:

> **“Ask almost anything about life, services, businesses, help, activities or practical needs in Rheintal — Oberriet Hub finds the real nearby answer.”**
