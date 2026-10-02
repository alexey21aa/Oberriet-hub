#!/usr/bin/env python3
"""Count observed evidence honestly; title matches are review hints, not dedupe."""
import json,collections,unicodedata
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
def read(p):return json.loads((ROOT/p).read_text())
def norm(s):return ''.join(c for c in unicodedata.normalize('NFKD',s.lower()) if c.isalnum())
live=read('data/v6-live-service-inventory.json');sources=read('data/v6-live-source-audit.json')['sources']
seed=read('data/seed.json');titles=collections.defaultdict(list)
for row in live['services']:titles[norm(row['post_title'])].append(row['ID'])
candidate=[]
for batch in ['activities','life-services','community-services','support-services','health-services']:
 for r in read(f'data/v6-{batch}-delta.json')['services']:
  candidate.append({'id':r['id'],'title':r['title']['de'],'exact_live_title_post_ids':titles.get(norm(r['title']['de']),[]),'decision':'review-canonical-identity-before-import'})
report={'checked_at':live['checked_at'],'live_service_posts':len(live['services']),'unique_live_titles':len(titles),'restored_seed_services':len(seed['services']),'live_source_entries':len(sources),'source_review_status':dict(collections.Counter(s.get('review_status','unknown') for s in sources)),'source_fetch_status':dict(collections.Counter(s.get('fetch_status') or 'unknown' for s in sources)),'source_verification_scope':dict(collections.Counter(s.get('verification_scope','unknown') for s in sources)),'candidate_services_including_already_live_activities':len(candidate),'candidate_overlap_hints':candidate,'confirmed_enrichment_targets':[{'candidate_id':'v6-support-victim-advice','live_post_id':3741,'canonical_id':'canton-service-5cad6a511a0055','action':'already enriched live; do not import duplicate'},{'candidate_id':'v6-support-legal-advice','live_post_id':661,'canonical_id':'practical-legal-advice','action':'booking corrected live; reconcile canonical offering before import'}],'limitations':['Post counts are not verified factual coverage counts.','Different cohorts on one URL remain distinct.','Title equality is only a review hint.','Most source entries are routing metadata, not reviewed full provider content.','No new candidates claimed imported.']}
(ROOT/'data/v6-coverage-audit.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({k:v for k,v in report.items() if k not in ['candidate_overlap_hints','limitations']}))
