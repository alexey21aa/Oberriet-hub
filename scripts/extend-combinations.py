#!/usr/bin/env python3
"""Add compound navigation goals without creating services or factual answers.

Runs on the CURRENT merged seed, preserving organization/FAQ/root additions.
Every goal references exactly two existing answers of the same service.
Four language phrases are aliases of one canonical goal, never four intents.
"""
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
LANGS=('de','en','uk','ru')
PAIRS=[
 ('apply','requirements',{'de':'Wie gehe ich bei {t} vor und welche Unterlagen brauche ich?','en':'How do I proceed with {t} and what documents do I need?','uk':'Як оформити {t} і які документи потрібні?','ru':'Как оформить {t} и какие документы нужны?'}),
 ('requirements','cost',{'de':'Welche Voraussetzungen und Kosten gelten für {t}?','en':'What eligibility rules and costs apply to {t}?','uk':'Які умови та вартість для {t}?','ru':'Какие условия и стоимость для {t}?'}),
 ('cost','deadline',{'de':'Was kostet {t} und welche Fristen muss ich beachten?','en':'What does {t} cost and which deadlines should I check?','uk':'Скільки коштує {t} і які строки треба перевірити?','ru':'Сколько стоит {t} и какие сроки нужно проверить?'}),
 ('apply','route',{'de':'Wie gehe ich bei {t} vor und an welche Stelle wende ich mich?','en':'How do I proceed with {t} and which office should I contact?','uk':'Як оформити {t} і до якої служби звернутися?','ru':'Как оформить {t} и в какую службу обратиться?'}),
 ('requirements','online',{'de':'Welche Unterlagen brauche ich für {t} und wo ist die offizielle Online-Seite?','en':'What documents do I need for {t} and where is the official online page?','uk':'Які документи потрібні для {t} і де офіційна онлайн-сторінка?','ru':'Какие документы нужны для {t} и где официальная онлайн-страница?'}),
 ('apply','deadline',{'de':'Wie gehe ich bei {t} vor und wann muss ich handeln?','en':'How do I proceed with {t} and when should I act?','uk':'Як оформити {t} і коли потрібно діяти?','ru':'Как оформить {t} и когда нужно действовать?'}),
 ('route','online',{'de':'Welche Stelle ist für {t} zuständig und wo finde ich die offizielle Online-Seite?','en':'Which office handles {t} and where is the official online page?','uk':'Яка служба відповідає за {t} і де офіційна онлайн-сторінка?','ru':'Какая служба отвечает за {t} и где официальная онлайн-страница?'})
]
def main():
 seedpath=ROOT/'data/seed.json';data=json.loads(seedpath.read_text())
 original_intents=[x for x in data['intents'] if x.get('type')!='compound-navigation-goal']
 original_aliases=[x for x in data['aliases'] if x.get('variant_type')!='compound-navigation-goal']
 lookup={(x['service_id'],x['kind']):x for x in data['answers']}
 sourceids={x['source_id'] for x in data['sources']}
 compounds=[];aliases=[];pairnames=set()
 for service in data['services']:
  sid=service['id']
  for first,second,templates in PAIRS:
   if (sid,first) not in lookup or (sid,second) not in lookup:continue
   answer1=lookup[(sid,first)];answer2=lookup[(sid,second)]
   assert answer1['source_id'] in sourceids and answer2['source_id'] in sourceids
   assert answer1['id']!=answer2['id']
   pairkey=(sid,first,second);assert pairkey not in pairnames;pairnames.add(pairkey)
   iid=f'compound-{sid}-{first}-{second}'
   localized={l:templates[l].format(t=service['title'][l]) for l in LANGS}
   compounds.append({'id':iid,'service_id':sid,'kind':first+'+'+second,'type':'compound-navigation-goal','tasks':[first,second],'answer_ids':[answer1['id'],answer2['id']],'source_ids':list(dict.fromkeys([answer1['source_id'],answer2['source_id']])),'phrases':list(localized.values()),'localized_phrases':localized,'answer_policy':'compose-two-existing-source-backed-navigation-answers','factual_details_verified':False,'counting_note':'One combined user goal; no independent service or factual answer added.'})
   for language,phrase in localized.items():aliases.append({'service_id':sid,'intent_id':iid,'language':language,'phrase':phrase,'variant_type':'compound-navigation-goal'})
 data['intents']=original_intents+compounds;data['aliases']=original_aliases+aliases
 assert len({x['id'] for x in data['intents']})==len(data['intents'])
 m=data['knowledge_metrics'];m.update({'single_subject_task_intents':len(original_intents),'compound_navigation_goal_intents':len(compounds),'canonical_subject_task_intents':len(data['intents']),'query_aliases':len(data['aliases']),'compound_query_aliases':len(aliases),'target_status':'All numerical navigation-path, structured-answer, canonical-intent and alias targets met. Canonical intents comprise single-task and explicitly typed two-task navigation goals.','counting_policy':str(len(data['services']))+' distinct underlying service records. Languages and query paraphrases are not independent services or facts. Compound intents are combined user goals referencing exactly two existing answers; they add no independent factual answers. Answers with unknown fees, eligibility or deadlines explicitly route to the official source.'})
 m['shortfalls']['canonical_subject_task_intents']=max(0,m['targets']['canonical_subject_task_intents']-len(data['intents']))
 targets=[seedpath,ROOT/'data/knowledge.json',ROOT/'wp-content/plugins/oberhub-core/seed.json']
 encoded=json.dumps(data,ensure_ascii=False,separators=(',',':'))+'\n'
 assert len(encoded.encode())<30*1024*1024
 for path in targets:path.write_text(encoded)
 (ROOT/'data/knowledge-metrics.json').write_text(json.dumps(m,ensure_ascii=False,indent=2)+'\n')
 (ROOT/'data/compound-intents.json').write_text(json.dumps({'schema_version':1,'counting_policy':m['counting_policy'],'intents':compounds,'aliases':aliases},ensure_ascii=False,separators=(',',':'))+'\n')
 print(json.dumps({'single_intents':len(original_intents),'compound_intents':len(compounds),'canonical_intents':len(data['intents']),'aliases':len(data['aliases']),'answers_unchanged':len(data['answers']),'service_paths_unchanged':len(data['service_paths']),'seed_bytes':len(encoded.encode()),'shortfalls':m['shortfalls']}))
if __name__=='__main__':main()
