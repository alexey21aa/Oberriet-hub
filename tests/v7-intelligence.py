#!/usr/bin/env python3
"""Candidate ontology/graph invariants; no live WordPress mutation."""
import collections,importlib.util,json,math,sys,time
from datetime import datetime,timezone,timedelta
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];sys.path.insert(0,str(ROOT/'scripts'))
from v7_intelligence import ConceptIndex,one_edit,distance_km,geo_select,merge_candidates,taxonomy_candidates,reviewed_offering_candidates
spec=importlib.util.spec_from_file_location('harvester',ROOT/'scripts/harvest-v7-businesses.py');h=importlib.util.module_from_spec(spec);spec.loader.exec_module(h)
checks=collections.Counter();failures=[]
def check(category,condition,detail):
 checks[category]+=1
 if not condition:failures.append({'category':category,'detail':detail})
start=time.perf_counter();index=ConceptIndex()
rows=[json.loads(x) for x in (ROOT/'data/v7/ontology/concept_aliases.jsonl').read_text().splitlines()]
for a in rows:check('multilingual_exact_alias',a['concept_id'] in index.resolve(a['term'],a['lang'])['concept_ids'],a['term'])
for c in index.concepts.values():check('concept_integrity',c['parent_id'].startswith('category:') and bool(c['classifications']),c['id'])
for a,b,want in [('sport','sport',True),('sport','spott',True),('sport','sprt',True),('sport','spoort',True),('sport','sprot',True),('sport','spartt',False),('abcd','cdab',False)]:check('edit_distance',one_edit(a,b)==want,a+' '+b)
for q,want in [('dentist','osm:amenity:dentist'),('sauna','osm:leisure:sauna'),('swimming pool','osm:leisure:swimming_pool'),('trampoline park','osm:leisure:trampoline_park')]:check('search_concept',want in index.resolve(q)['concept_ids'],q)
check('unknown_intent_not_camping',index.resolve('unreviewed test')['concept_ids']==[],'stray test/tent fuzzy collision')
r=index.resolve('dentist Oberriet age 12 years free open now');check('typed_facets',r['facets']=={'municipalities':['Oberriet'],'age':12,'free_requested':True,'open_now_requested':True},str(r))
try:index.resolve('x'*513);bounded=False
except ValueError:bounded=True
check('query_bound',bounded,'513-char query')
a={'lat':47.4,'lon':9.5};b={'lat':47.5,'lon':9.5}
check('geo_distance',10<distance_km(a,b)<12,'known degree delta')
for c in [None,{}, {'lat':100,'lon':0},{'lat':True,'lon':0},{'lat':float('nan'),'lon':0},{'lat':'47','lon':0}]:check('geo_invalid',distance_km(a,c) is None,str(c))
entities=[{'id':'local','municipality':'Oberriet','coordinates':a},{'id':'near','municipality':'Rebstein','coordinates':b},{'id':'unknown','municipality':None,'coordinates':None}]
check('geo_municipality',[r['id'] for r in geo_select(entities,'Oberriet',a)['results']]==['local'],'local before radius')
check('geo_radius',geo_select(entities,'missing',a)['stage']=='radius','radius fallback')
check('geo_unknown',geo_select([entities[-1]],'Oberriet',a)['results']==[],'unknown is not local')
check('geo_ambiguity',geo_select([dict(entities[0],municipality_ambiguous=True)],'Oberriet')['stage']!='municipality','boundary ambiguity')
a={'id':'a','name':'Branch','public_contact_metadata':{'website':'https://chain.example','phone':'1'},'coordinates':None};b=dict(a,id='b')
check('identity_shared_domain',merge_candidates(a,b) is None,'shared website/phone not identity')
check('identity_uid_conflict',merge_candidates(dict(a,legal_uid_hint='CHE-111.111.111'),dict(b,legal_uid_hint='CHE-222.222.222')) is None,'conflicting UID')
check('identity_uid_branches',merge_candidates(dict(a,legal_uid_hint='CHE-111.111.111'),dict(b,legal_uid_hint='CHE-111.111.111'))=='same-legal-entity-keep-branches','one UID distinct branches')
for tag in [{'access':'private'},{'disused:shop':'yes'}]:check('discovery_exclusion',h.normalize_element({'type':'node','id':1,'tags':dict(name='x',shop='books',**tag)},'Oberriet',{}) is None,str(tag))
pois=json.loads((ROOT/'data/v7/business/business_entities.json').read_text())
area={'type':'area','id':3600123456,'tags':{'name':'Rüthi (SG)','boundary':'administrative','admin_level':'8'}}
check('municipality_boundary_identity',h.resolved_boundary([area],'Rüthi')['relation_id']==123456,'official suffix with stable relation ID')
for elements in [[],[area,area],[dict(area,tags=dict(area['tags'],name='Rüti'))],[dict(area,tags=dict(area['tags'],admin_level='4'))]]:
 try:h.resolved_boundary(elements,'Rüthi');rejected=False
 except ValueError:rejected=True
 check('municipality_boundary_rejection',rejected,'unresolved, duplicate or wrong administrative identity')
for p in pois:check('discovery_not_verified',not p['offerings_verified'] and not p['opening_hours_verified'] and not p['published_live'],p['id'])
for q in ['dentist','pharmacy','bakery','sauna','supermarket']:
 r=taxonomy_candidates(index,pois,q,'Oberriet');check('taxonomy_retrieval',all(p['category_tags'] for p in r['results']) and r['mode']=='discovery-only' and r['verified_offerings']==0,q)
graph=[json.loads(x) for x in (ROOT/'data/v7/graph/business_entities.jsonl').read_text().splitlines()]
offers=[json.loads(x) for x in (ROOT/'data/v7/graph/business_offerings.jsonl').read_text().splitlines()]
for q in ['gastroenterology','Гастроэнтерология','Gastroenterologie','Гастроентерологія','colonoscopy','Колоноскопия','Колоноскопія','Darmspiegelung','Gastroscopy','УЗИ живота','УЗД живота','Atemtest','breath test','Radiologie','Радиология','Радіологія','Orthopaedic insoles','Ортопедические стельки','Ортопедичні устілки','Orthopädische Einlagen']:
 r=reviewed_offering_candidates(index,graph,offers,q,'Altstätten');check('reviewed_graph_multilingual',len(r['results'])>0 and r['stage']=='municipality' and not r['live'] and not r['opening_hours_asserted'],q)
for q,want in [('колоноскопя','v7-bauchmed-diagnostics'),('гастроскопя','v7-bauchmed-diagnostics'),('Radilogie','v7-radiologie-altstaetten')]:
 r=reviewed_offering_candidates(index,graph,offers,q,'Altstätten');check('reviewed_graph_typos',want in [x['id'] for x in r['results']],q)
for age in [8,-1]:
 copies=[dict(o,source_provenance=dict(o['source_provenance'],checked_at=(datetime.now(timezone.utc)-timedelta(days=age)).isoformat())) for o in offers]
 check('reviewed_graph_freshness',reviewed_offering_candidates(index,graph,copies,'Radiology','Altstätten')['results']==[],str(age))
for o in offers:
 if not o['existing_live_service']:check('draft_import_gate',not o['eligible_for_live_import'] and o['canonical_review'].startswith('pending') and o['fee'] is None and not o['opening_hours_verified'],o['id'])
qspec=importlib.util.spec_from_file_location('queue_builder',ROOT/'scripts/build-v7-review-queue.py');qb=importlib.util.module_from_spec(qspec);qspec.loader.exec_module(qb)
for u in ['http://127.0.0.1/','http://169.254.169.254/latest/meta-data/','https://user:secret@example.org/','ftp://example.org/','https://localhost/','http://10.0.0.1/','http://[::1]/','https://test.local/','https://example.org:8443/']:
 check('review_url_guard',qb.public_url(u) is None,u)
check('review_url_public',qb.public_url('https://example.org/page#section')=='https://example.org/page','public fragment normalized')
samples=[];misses=[]
for t,ids in sorted(index.terms.items()):
 if len(t)<8 or len(ids)!=1:continue
 typo=t[:len(t)//2]+t[len(t)//2+1:]
 if typo in index.terms:continue
 started=time.perf_counter();got=index.resolve(typo)['concept_ids'];elapsed=(time.perf_counter()-started)*1000
 samples.append(elapsed)
 if not ids.intersection(got):misses.append({'term':t,'typo':typo,'expected':sorted(ids),'returned':got})
 if len(samples)>=500:break
benchmark={'distinct_queries':len(samples),'concept_recovery':len(samples)-len(misses),'recall':round((len(samples)-len(misses))/len(samples),4),'p95_ms':round(sorted(samples)[int(len(samples)*.95)],3),'miss_examples':misses[:20],'scope':'candidate deterministic deletion corpus, not human relevance or live search'}
result={'timestamp':datetime.now(timezone.utc).isoformat(),'candidate_only':True,'live_tests_included':False,'checks':dict(checks),'total':sum(checks.values()),'failed':len(failures),'failures':failures,'elapsed_seconds':round(time.perf_counter()-start,3),'typo_benchmark':benchmark,'limitations':['Candidate typo recall is measured separately from passing integrity checks.','Semantic reranking/inference and live deployment are not covered.']}
(ROOT/'tests/results/v7-intelligence.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n');print(json.dumps(result,ensure_ascii=False));sys.exit(bool(failures))
