#!/usr/bin/env python3
"""Produce primary-provider review jobs; never promote OSM metadata to facts."""
import collections,hashlib,ipaddress,json,urllib.parse
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'data/v7/review';OUT.mkdir(parents=True,exist_ok=True)
def public_url(value):
 try:
  p=urllib.parse.urlsplit(value)
  if p.scheme not in ['http','https'] or not p.hostname or p.username or p.password:return None
  host=p.hostname.casefold().rstrip('.')
  if host=='localhost' or '.' not in host or host.endswith(('.local','.internal','.localhost')):return None
  if p.port not in [None,80,443]:return None
  try:
   if not ipaddress.ip_address(host).is_global:return None
  except ValueError:pass
  return urllib.parse.urlunsplit((p.scheme,p.netloc,p.path or '/',p.query,''))
 except (ValueError,TypeError):return None
def build():
 rows=json.loads((ROOT/'data/v7/business/business_entities.json').read_text());jobs=[];missing=blocked=0
 review_file=OUT/'medical-reviewed-offerings.json'
 reviewed={r['business_entity_id']:r for r in json.loads(review_file.read_text())['provider_reviews']} if review_file.exists() else {}
 for r in rows:
  contact=r.get('public_contact_metadata',{});raw=contact.get('website') or contact.get('contact:website')
  if not raw:missing+=1;continue
  url=public_url(raw)
  if not url:blocked+=1;continue
  cid='provider-page:'+hashlib.sha256(url.encode()).hexdigest()[:20]
  jobs.append({'id':r['id'],'provider_source_id':cid,'url':url,'name':r['name'],'municipality':r['municipality'],
   'discovery_classification':r['category_tags'],'status':'pending-primary-review','source_discovery_trust':'D',
   'required_checks':['public-dns-and-redirects','robots-and-license','primary-provider-identity','offerings-and-audience','price-and-eligibility-if-stated','field-level-freshness'],
   'verified_claims':[],'eligible_for_live_import':False})
 for j in jobs:
  if j['id'] in reviewed:
   j['status']='primary-content-reviewed-canonical-pending';j['provider_review']=reviewed[j['id']]
 jobs.sort(key=lambda j:(0 if 'healthcare' in j['discovery_classification'] else 1,j['municipality'] or '',j['id']))
 (OUT/'primary-provider-queue.jsonl').write_text(''.join(json.dumps(j,ensure_ascii=False,separators=(',',':'))+'\n' for j in jobs))
 receipt_file=OUT/'medical-primary-receipts.json'
 receipt_rows=json.loads(receipt_file.read_text()) if receipt_file.exists() else []
 metrics={'public_pois':len(rows),'queued_entity_reviews':len(jobs),'unique_candidate_urls':len({j['url'] for j in jobs}),'no_website_metadata':missing,'blocked_url_metadata':blocked,'primary_pages_fetched':sum(r['status']=='fetched' for r in receipt_rows),'primary_provider_reviews_complete':len(reviewed),'pending_provider_reviews':len(jobs)-len(reviewed),'eligible_for_live_import':0,'scope':'OSM-derived website references are discovery only. Three bounded editorial provider reviews are separate; canonical comparison still pending. DNS/robots/redirect validation required before automated bulk fetch.'}
 (OUT/'queue_metrics.json').write_text(json.dumps(metrics,ensure_ascii=False,indent=2)+'\n');print(json.dumps(metrics,ensure_ascii=False))
if __name__=='__main__':build()
