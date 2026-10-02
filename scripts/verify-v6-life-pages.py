#!/usr/bin/env python3
"""Bounded primary-page receipts; no copied page bodies stored in the public repo."""
import argparse,hashlib,json,time,urllib.request,urllib.error,urllib.parse,urllib.robotparser
from pathlib import Path
from datetime import datetime,timezone
ROOT=Path(__file__).resolve().parents[1]
parser=argparse.ArgumentParser();parser.add_argument('--batch',choices=['life','community','support','health'],default='life')
parser.add_argument('--source-url',action='append',default=[],help='Revalidate only an exact registered URL; preserve other receipts.')
args=parser.parse_args()
PATH=ROOT/f'data/v6-{args.batch}-services-delta.json'
data=json.loads(PATH.read_text());proof=[];robots={};last={};UA='OberrietHub/0.3 public-metadata (+https://oberriethub.ch)'
selected=[s for s in data['sources'] if not args.source_url or s['source_url'] in args.source_url]
if set(args.source_url)-{s['source_url'] for s in selected}:parser.error('URL is not registered in this batch')
receipt_path=ROOT/'data'/f'v6-{args.batch}-http-verification.json'
if args.source_url and receipt_path.exists():
 changed={s['source_id'] for s in selected}
 proof=[p for p in json.loads(receipt_path.read_text()) if p['source_id'] not in changed]
for source in selected:
 url=source['source_url'];host=urllib.parse.urlsplit(url).hostname
 receipt={'source_id':source['source_id'],'source_url':url,'checked_at':datetime.now(timezone.utc).isoformat(timespec='seconds')}
 # A failed retry must not retain an old success/hash under a changed URL.
 for key in ['http_status','sha256','fetched_at']:source.pop(key,None)
 try:
  if host not in robots:
   rp=urllib.robotparser.RobotFileParser('https://'+host+'/robots.txt')
   try:
    with urllib.request.urlopen(urllib.request.Request(rp.url,headers={'User-Agent':UA}),timeout=6) as r:rp.parse(r.read(256000).decode('utf8','replace').splitlines())
   except urllib.error.HTTPError as e:
    if e.code in (404,410):rp.parse([])
    else:raise
   robots[host]=rp
  if not robots[host].can_fetch(UA,url):raise ValueError('robots-denied')
  wait=max(0,2-(time.monotonic()-last.get(host,0)))
  if wait:time.sleep(wait)
  last[host]=time.monotonic()
  with urllib.request.urlopen(urllib.request.Request(url,headers={'User-Agent':UA}),timeout=6) as r:
   if urllib.parse.urlsplit(r.url).hostname!=host:raise ValueError('redirect-host-change')
   body=r.read(2000001)
   if len(body)>2000000:raise ValueError('oversized-response')
   receipt.update(http_status=r.status,sha256=hashlib.sha256(body).hexdigest(),resolved_url=r.url,bytes=len(body))
   source.update(http_status=r.status,sha256=receipt['sha256'],fetched_at=receipt['checked_at'],fetch_status='ok',evidence_method='Primary web content reviewed and direct HTTP/hash receipt recorded.')
 except Exception as e:
  receipt['error']=str(e);source['fetch_status']='error'
  source['evidence_method']='Primary web snapshot reviewed; direct fetch failed. Revalidation required before external AI context.'
 proof.append(receipt)
 # Persist successful progress even if a later page stalls or this turn interrupts.
 for name,value in [(f'v6-{args.batch}-services-delta',data),(f'v6-{args.batch}-http-verification',proof)]:
  target=ROOT/'data'/f'{name}.json';temporary=target.with_suffix('.json.tmp');temporary.write_text(json.dumps(value,ensure_ascii=False,indent=2)+'\n');temporary.replace(target)
 print(json.dumps(receipt),flush=True)
print(json.dumps({'attempted':len(selected),'retained_receipts':len(proof),'http_200':sum(p.get('http_status')==200 for p in proof)}),flush=True)
