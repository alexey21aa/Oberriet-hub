#!/usr/bin/env python3
"""Separate OSM discovery POIs from reviewed primary-provider offerings.
No unsafe domain/phone merges; no live import; offers stay tied to reviewed source.
"""
import collections,hashlib,json
from datetime import datetime,timezone,timedelta
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'data/v7/graph';OUT.mkdir(parents=True,exist_ok=True)
def write_jsonl(path,rows):
 tmp=path.with_suffix('.tmp');tmp.write_text(''.join(json.dumps(x,ensure_ascii=False,separators=(',',':'))+'\n' for x in rows));tmp.replace(path)
discovery=ROOT/'data/v7/business/business_entities.json'
entities={r['id']:r for r in json.loads(discovery.read_text())} if discovery.exists() else {}
delta=json.loads((ROOT/'data/v6-activities-delta.json').read_text());sources={s['source_id']:s for s in delta['sources']};offerings=[]
localities={'oberriet':'Oberriet','montlingen':'Oberriet','kriessern':'Oberriet','eichenwies':'Oberriet','altstatten':'Altstätten','altstätten':'Altstätten','grabs':'Grabs'}
for r in delta['services']:
 source=sources.get(r.get('source_id'),{})
 if source.get('fetch_status') not in ['ok','current'] or source.get('review_status')!='checked':continue
 eid='curated:'+hashlib.sha256((r['authority']+'|'+r['locality']).encode()).hexdigest()[:20]
 if eid not in entities:
  entities[eid]={'id':eid,'entity_kind':'provider-location','name':r['authority'],'municipality':localities.get(r['locality'].casefold()),'locality':r['locality'],
   'coordinates':None,'category_tags':{},'source_links':[],'source_provenance':[],
   'legal_registration_verified':False,'published_live':False}
 entities[eid]['source_links']=sorted(set(entities[eid]['source_links']+[r['source_url']]))
 provenance={'source_id':r['source_id'],'source_url':r['source_url'],'checked_at':r['source_checked_at'],'trust':r['source_trust_level'],'review_status':'primary-content-reviewed'}
 if provenance not in entities[eid]['source_provenance']:entities[eid]['source_provenance'].append(provenance)
 checked=datetime.fromisoformat(r['source_checked_at'].replace('Z','+00:00'))
 if checked.tzinfo is None:checked=checked.replace(tzinfo=timezone.utc)
 fresh=timedelta(0)<=datetime.now(timezone.utc)-checked<=timedelta(days=7)
 offerings.append({'id':r['id'],'business_entity_id':eid,'title':r['title'],'description':r['description_short'],
  'legacy_concepts':r.get('search_concepts',[]),'source_provenance':provenance,
  'fee':r.get('fees'),'age_note':r.get('age_note'),'schedule':r.get('schedule'),
  'offering_verified':fresh,'source_freshness':'within-7-day-review-window' if fresh else 'needs-refresh','opening_hours_verified':False,'existing_live_service':True,'graph_published_live':False})
review_file=ROOT/'data/v7/review/medical-reviewed-offerings.json'
if review_file.exists():
 reviewed=json.loads(review_file.read_text())
 for review in reviewed['provider_reviews']:
  entity=entities.get(review['business_entity_id'])
  if entity:entity['primary_provider_review']=review
 for r in reviewed['offerings']:
  if r['business_entity_id'] not in entities:raise ValueError('Missing provider binding '+r['id'])
  checked=datetime.fromisoformat(r['source_provenance']['checked_at'])
  fresh=timedelta(0)<=datetime.now(timezone.utc)-checked<=timedelta(days=r['source_provenance']['ttl_days'])
  offerings.append(dict(r,offering_verified=fresh,source_freshness='within-review-window' if fresh else 'needs-refresh'))
civic=ROOT/'data/v7/review/civic-reviewed-offerings.json'
if civic.exists():
 reviewed=json.loads(civic.read_text())
 for r in reviewed['entities']:
  if r['id'] in entities:raise ValueError('Duplicate civic entity '+r['id'])
  entities[r['id']]=r
 for r in reviewed['offerings']:
  if r['business_entity_id'] not in entities:raise ValueError('Missing civic provider '+r['id'])
  offerings.append(r)
mapping_path=ROOT/'data/v7/ontology/extension_concept_map.json'
mapping=json.loads(mapping_path.read_text()) if mapping_path.exists() else {}
for offer in offerings:
 if 'concept_ids' in offer:offer['concept_ids']=sorted({mapping.get(cid,cid) for cid in offer['concept_ids']})
write_jsonl(OUT/'business_entities.jsonl',sorted(entities.values(),key=lambda r:r['id']))
write_jsonl(OUT/'business_offerings.jsonl',offerings)
coverage=collections.defaultdict(lambda:collections.Counter())
for r in entities.values():
 scope=r.get('municipality') or 'unknown';coverage[scope]['entities']+=1
 for k in r.get('category_tags',{}):coverage[scope][k]+=1
for r in offerings:coverage[entities[r['business_entity_id']]['municipality'] or 'unknown']['reviewed_offerings']+=1
metrics={'entities':len(entities),'osm_discovery_pois':sum(k.startswith('osm:') for k in entities),
 'reviewed_provider_locations':sum(k.startswith('curated:') for k in entities),'reviewed_offerings':len(offerings),
 'primary_reviewed_osm_locations':sum(k.startswith('osm:') and bool(r.get('primary_provider_review')) for k,r in entities.items()),
 'fresh_primary_verified_offerings':sum(bool(r.get('offering_verified')) for r in offerings),
 'receipt_pending_offerings':sum(not r.get('offering_verified') for r in offerings),
 'reviewed_civic_provider_locations':sum(k.startswith('reviewed:civic:') for k in entities),
 'existing_live_offerings':sum(r['existing_live_service'] for r in offerings),
 'new_candidate_offer_drafts':sum(not r['existing_live_service'] for r in offerings),
 'verified_customer_opening_schedules':0,'employer_shift_claims':0,'automatic_cross_source_merges':0,
 'live_new_entities':0,'coverage_by_municipality':dict(coverage),
 'limitations':['OSM POIs include businesses and public facilities; counts are not verified registered company counts.',
 'Discovery classifications/hours are not primary verified offerings or current-open claims.',
 'Existing live activities, primary-reviewed drafts and receipt-pending civic drafts are counted separately. New graph drafts are not live.',
 'Different branches sharing website/phone stay distinct until reviewed identity mapping.']}
(OUT/'coverage_metrics.json').write_text(json.dumps(metrics,ensure_ascii=False,indent=2)+'\n');print(json.dumps(metrics,ensure_ascii=False))
