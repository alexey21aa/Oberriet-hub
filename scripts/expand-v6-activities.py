#!/usr/bin/env python3
"""Add-only, primary-provider activity delta. No invented prices or age limits."""
import json,hashlib
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
LANGS=['de','en','ru','uk']
def loc(*values): return dict(zip(LANGS,values))
SOURCES={
 'tsv-montlingen':('https://www.tsvmontlingen.ch/riegen/jugi','TSV Montlingen','page'),
 'tsv-la':('https://www.tsvmontlingen.ch/riegen/LA','TSV Montlingen','page'),
 'tsv-damen':('https://www.tsvmontlingen.ch/riegen/damenriege','TSV Montlingen','page'),
 'tsv-fitness':('https://www.tsvmontlingen.ch/riegen/Fitnessriege','TSV Montlingen','page'),
 'tsv-frauen':('https://www.tsvmontlingen.ch/riegen/Frauenriege','TSV Montlingen','page'),
 'rcog-kiri':('https://www.rcog.ch/kinderringen','Ringerclub Oberriet-Grabs','page'),
 'stvoe-jugi':('https://www.stvoe.ch/Riegenueberblick/Jugi','STV Oberriet-Eichenwies','page'),
 'msor-dance':('https://msor.ch/tanzen/','Musikschule Oberrheintal','page'),
 'tanoshii':('https://www.tanoshii.ch/de/','Tanoshii Funpark','page'),
 'plusport':('https://www.plusport-rheintal.ch/turnen','PluSport Rheintal','page'),
 'creative-movements':('https://www.creativemovements.ch/kurse','Creative Movements','page'),
}
rows=[]
def add(id,source,name,place,concepts,description,age=None):
 url,provider,verification=SOURCES[source]
 r={'id':id,'title':loc(name,name,name,name),'description_short':description,'description_full':description,'topic':'leisure','locality':place,'authority':provider,'source_id':'v6-'+source,'source_url':url,'official_url':url,'source_checked_at':'2026-10-02','status':'checked','source_trust_level':'B','verification_scope':verification,'search_concepts':concepts,'age_note':age,'fees':None,'schedule':None,'next_action':loc('Aktuelle Zeiten und Teilnahme direkt beim Anbieter prüfen.','Check current times and participation with the provider.','Уточните актуальное расписание и участие у организатора.','Уточніть актуальний розклад та участь в організатора.'),'translation_status':{l:'editorial-draft' for l in LANGS}}
 rows.append(r)
add('v6-montlingen-kitu','tsv-montlingen','TSV Montlingen · KiTu','montlingen',['sport','children'],loc('Bewegung und Spiele für Kindergartenkinder in der Bergliturnhalle.','Movement and games for kindergarten children at Bergliturnhalle.','Подвижные игры и гимнастика для детей детсадовского возраста в Bergliturnhalle.','Рухливі ігри та гімнастика для дітей дитсадкового віку в Bergliturnhalle.'))
add('v6-montlingen-muki','tsv-montlingen','TSV Montlingen · MuKi','montlingen',['sport','children'],loc('Eltern-Kind-Turnen ab drei Jahren in der Berglihalle.','Parent and child gymnastics from age three at Berglihalle.','Гимнастика вместе с родителем для детей от трёх лет в Berglihalle.','Гімнастика з батьками для дітей від трьох років у Berglihalle.'))
for slug,name,grades in [('girls-small','Kleine Mädchen','1–3'),('girls-middle','Mittlere Mädchen','4–6'),('girls-youth','Grosse Mädchen','Oberstufe'),('boys-small','Kleine Knaben','1–3'),('boys-middle','Mittlere Knaben','4–6'),('boys-youth','Grosse Knaben','Oberstufe')]:
 add('v6-montlingen-'+slug,'tsv-montlingen','TSV Montlingen · '+name,'montlingen',['sport','children'],loc('Jugendturnen: '+name+'. Schulstufe: '+grades+'.','Youth gymnastics: '+name+'. School level: '+grades+'.','Детская гимнастика: '+name+'. Школьная ступень: '+grades+'.','Дитяча гімнастика: '+name+'. Шкільний ступінь: '+grades+'.'))
add('v6-montlingen-athletics','tsv-la','TSV Montlingen · Leichtathletik','montlingen',['sport','children'],loc('Zusätzliches Leichtathletiktraining für sportbegeisterte Kinder.','Additional athletics training for children interested in sport.','Дополнительные занятия лёгкой атлетикой для детей.','Додаткові заняття легкою атлетикою для дітей.'))
add('v6-montlingen-damen','tsv-damen','TSV Montlingen · Damenriege','montlingen',['sport','dance','women','adults'],loc('Frauenriege mit Tanz, Leichtathletik und Fitness. Kein reiner Tanzkurs.','Women’s group combining dance, athletics and fitness; not a dedicated dance course.','Женская группа: танцы, лёгкая атлетика и фитнес. Это смешанные занятия, а не отдельная танцевальная школа.','Жіноча група: танці, легка атлетика та фітнес. Це змішані заняття, а не окремий танцювальний курс.'))
add('v6-montlingen-fitness','tsv-fitness','TSV Montlingen · Fitnessriege','montlingen',['sport','dance','women','adults'],loc('Frauenfitness mit Groupfitness und Unterhaltungstanz.','Women’s fitness with group fitness and performance dance.','Женский фитнес с групповыми тренировками и танцевальными выступлениями.','Жіночий фітнес із груповими тренуваннями й танцювальними виступами.'))
add('v6-montlingen-frauen','tsv-frauen','TSV Montlingen · Frauenriege','montlingen',['sport','women','adults'],loc('Fitness, Koordination und gemeinsame Aktivitäten für Frauen.','Fitness, coordination and social activities for women.','Фитнес, координация и совместный досуг для женщин.','Фітнес, координація та спільне дозвілля для жінок.'))
for place in ['oberriet','grabs']:
 add('v6-kiri-'+place,'rcog-kiri','KiRi · '+place.capitalize(),place,['sport','children'],loc('Kinderringen für Mädchen und Jungen ab dem Kindergartenalter. Kein Training während Schulferien.','Wrestling for girls and boys from kindergarten age; no training during school holidays.','Детская борьба для мальчиков и девочек с детсадовского возраста. На школьных каникулах тренировок нет.','Дитяча боротьба для хлопчиків і дівчат із дитсадкового віку. На шкільних канікулах тренувань немає.'))
add('v6-stvoe-jugi','stvoe-jugi','STV Oberriet-Eichenwies · Jugi','oberriet',['sport','children'],loc('Jugendturnen im örtlichen Turnverein.','Youth gymnastics at the local gymnastics club.','Детская гимнастика в местном спортивном клубе.','Дитяча гімнастика в місцевому спортивному клубі.'))
add('v6-msor-adult-dance','msor-dance','Musikschule Oberrheintal · Tanzen für Erwachsene','altstatten',['dance','adults'],loc('Tanzangebot für Erwachsene, solo oder als Paar. Nicht als ausschliesslich für Frauen bestätigt.','Adult dance, solo or with a partner; women-only restriction is not confirmed.','Танцы для взрослых соло или в паре. Ограничение «только для женщин» не подтверждено.','Танці для дорослих соло або в парі. Обмеження «лише для жінок» не підтверджене.'))
add('v6-msor-child-dance','msor-dance','Musikschule Oberrheintal · Kindertanz','altstatten',['dance','children'],loc('Ballett und zeitgenössischer Tanz für Kinder und Jugendliche.','Ballet and contemporary dance for children and young people.','Балет и современный танец для детей и подростков.','Балет і сучасний танець для дітей і підлітків.'))
add('v6-tanoshii','tanoshii','Tanoshii Funpark','altstatten',['indoor','children'],loc('Indoor-Trampoline und Ninja-Parcours ab sechs Jahren in Altstätten.','Indoor trampolines and ninja course from age six in Altstätten.','Крытый батутный парк и ниндзя-полоса в Altstätten для детей от шести лет.','Критий батутний парк і ніндзя-смуга в Altstätten для дітей від шести років.'))
add('v6-plusport-kids','plusport','PluSport Rheintal · Kinderturnen','rheintal',['sport','children'],loc('Inklusives Kinderturnen; Eignung und Unterstützung beim Anbieter abklären.','Inclusive children’s gymnastics; confirm suitability and support with the provider.','Инклюзивная детская гимнастика; подходящую группу и поддержку уточните у организатора.','Інклюзивна дитяча гімнастика; відповідну групу й підтримку уточніть в організатора.'))
add('v6-creative-kids','creative-movements','Creative Movements · Tanzkurse','rheintal',['dance','children'],loc('Tanzkurse für Kinder und Jugendliche im Rheintal.','Dance courses for children and young people in Rheintal.','Танцевальные курсы для детей и подростков в Rheintal.','Танцювальні курси для дітей і підлітків у Rheintal.'))
evidence=json.loads((ROOT/'data/v6-source-verification.json').read_text())+json.loads((ROOT/'data/v6-tsv-verification.json').read_text());evidence={r['url']:r for r in evidence}
sources=[]
for id,(url,provider,verification) in SOURCES.items():
 proof=evidence.get(url,{});assert proof.get('http_status')==200,url
 sources.append({'http_status':200,'sha256':proof['sha256'],'fetched_at':'2026-10-02','metadata_only':True,'source_id':'v6-'+id,'source_url':url,'authority':provider,'review_status':'checked','last_checked':'2026-10-02','content_reviewed_at':'2026-10-02','trust':'B','ttl_days':7,'freshness_class':'F3','fetch_mode':'html','fetch_status':'ok','verification_scope':verification,'reuse_note':'Original short factual summaries and links; no full provider text copied.','evidence_method':'Primary-provider content checked; direct HTTP 200 and SHA256 stored on 2026-10-02.'})
delta={'services':rows,'sources':sources}
(ROOT/'data/v6-activities-delta.json').write_text(json.dumps(delta,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'services':len(rows),'sources':len(sources)}))
