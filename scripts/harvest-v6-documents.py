#!/usr/bin/env python3
"""Bounded public metadata harvester. Conditional GET, robots, same-host dedupe.

Links are discoveries, never marked verified forms or translated legal advice.
Run: python3 scripts/harvest-v6-documents.py --max-sources 8 --max-pages 16
"""
import argparse,hashlib,json,time,urllib.request,urllib.error,urllib.parse,urllib.robotparser,re
from html.parser import HTMLParser
from pathlib import Path
from datetime import datetime,timezone
ROOT=Path(__file__).resolve().parents[1]
UA='OberrietHub/0.3 public-metadata (+https://oberriethub.ch)'
class Links(HTMLParser):
 def __init__(self): super().__init__();self.links=[];self.href=None;self.text=[]
 def handle_starttag(self,tag,attrs):
  if tag=='a':self.href=dict(attrs).get('href');self.text=[]
 def handle_data(self,text):
  if self.href:self.text.append(text)
 def handle_endtag(self,tag):
  if tag=='a' and self.href:self.links.append((self.href,' '.join(' '.join(self.text).split())));self.href=None

def canonical(url):
 p=urllib.parse.urlsplit(url)
 if p.scheme!='https' or p.username or p.password or p.port not in (None,443):return None
 if not re.fullmatch(r'(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,63}',p.hostname or ''):return None
 if re.search(r'(?:^|\.)(localhost|local|internal|test|invalid)$',p.hostname):return None
 return urllib.parse.urlunsplit(('https',p.netloc.lower(),p.path or '/',p.query,''))

def main():
 a=argparse.ArgumentParser();a.add_argument('--max-sources',type=int,default=8);a.add_argument('--max-pages',type=int,default=16);a.add_argument('--timeout',type=int,default=10);a.add_argument('--source-offset',type=int,default=0);args=a.parse_args()
 out=ROOT/'data/v6-harvest';out.mkdir(exist_ok=True)
 sources=json.loads((ROOT/'data/sources.json').read_text());sources=sources.get('sources',[]) if isinstance(sources,dict) else sources
 seeds=json.loads((ROOT/'data/v6-source-manifest.json').read_text());sources=seeds+sources
 sources=[s for s in sources if s.get('review_status') in ('checked','seed-unverified') and canonical(s.get('source_url',''))]
 # Federal/SG roots before municipal long tail. Never count query aliases as documents.
 sources=list({s['source_url']:s for s in sources}.values())
 sources.sort(key=lambda s:(not 'form' in s['source_url'].lower(),not 'ahv-iv' in s['source_url'],not 'sg.ch/steuern' in s['source_url'],s['source_url']))
 state_path=out/'state.json';state=json.loads(state_path.read_text()) if state_path.exists() else {}
 manifest=[];documents={d['id']:d for d in json.loads((out/'documents.json').read_text())} if (out/'documents.json').exists() else {};robots={};last={};offset=max(0,args.source_offset);queue=[(s['source_url'],s) for s in sources[offset:offset+max(1,min(args.max_sources,100))]];seen=set();discovered=set()
 def checkpoint():
  # Atomic per-page receipts survive interruption and retain earlier discoveries.
  for name,value in [('state',state),('documents',list(documents.values())),('manifest',manifest)]:
   temporary=out/(name+'.json.tmp');temporary.write_text(json.dumps(value,ensure_ascii=False,indent=2)+'\n');temporary.replace(out/(name+'.json'))
 while queue and len(manifest)<max(1,min(args.max_pages,500)):
  checkpoint()
  url,source=queue.pop(0);url=canonical(url)
  if not url or url in seen:continue
  seen.add(url);host=urllib.parse.urlsplit(url).hostname
  if host not in robots:
   rp=urllib.robotparser.RobotFileParser('https://'+host+'/robots.txt')
   try:
    with urllib.request.urlopen(urllib.request.Request(rp.url,headers={'User-Agent':UA}),timeout=args.timeout) as r: rp.parse(r.read(256000).decode('utf8','replace').splitlines())
   except urllib.error.HTTPError as e:
    if e.code in (404,410):rp.parse([])
    else:manifest.append({'url':url,'status':'robots-unavailable','error':str(e)});continue
   except Exception as e:manifest.append({'url':url,'status':'robots-unavailable','error':str(e)});continue
   robots[host]=rp
  if not robots[host].can_fetch(UA,url):manifest.append({'url':url,'status':'robots-denied'});continue
  wait=max(0,2-(time.monotonic()-last.get(host,0)))
  if wait:time.sleep(wait)
  last[host]=time.monotonic();headers={'User-Agent':UA,'Accept':'text/html,application/xml,application/json'};previous=state.get(url,{})
  for field,header in [('etag','If-None-Match'),('last_modified','If-Modified-Since')]:
   if previous.get(field):headers[header]=previous[field]
  try:
   with urllib.request.urlopen(urllib.request.Request(url,headers=headers),timeout=args.timeout) as r:
    resolved=canonical(r.url)
    if not resolved or urllib.parse.urlsplit(resolved).hostname!=host:raise ValueError('Redirect outside approved source host')
    body=r.read(2000001)
    if len(body)>2000000:raise ValueError('Page exceeds bounded response size')
    checked=datetime.now(timezone.utc).isoformat(timespec='seconds');proof={'url':url,'resolved_url':resolved,'http_status':r.status,'fetched_at':checked,'sha256':hashlib.sha256(body).hexdigest(),'etag':r.headers.get('ETag'),'last_modified':r.headers.get('Last-Modified')}
    state[url]=proof;manifest.append(proof)
    parser=Links();parser.feed(body.decode('utf8','replace'))
    for href,title in parser.links:
     target=canonical(urllib.parse.urljoin(resolved,href))
     if not target or urllib.parse.urlsplit(target).hostname!=host or not title or len(title)>250:continue
     interesting=bool(re.search(r'\.pdf(?:\?|$)|\.docx?(?:\?|$)|\.xlsx?(?:\?|$)|formular|formulare|formulaires|muster|vorlage|steuer|tax|recht|gesundheit|kulturlegi|beratung|sozial|familie|kinder|sport|tanzen|bildung|sprache|integration',target+' '+title,re.I))
     if not interesting:continue
     id='doc-'+hashlib.sha256(target.encode()).hexdigest()[:24]
     discovered.add(id)
     documents[id]={'id':id,'source_id':source['source_id'],'source_url':target,'official_url':target,'title':{l:title for l in ['de','en','ru','uk']},'description_short':{l:'' for l in ['de','en','ru','uk']},'status':'unreviewed','verification_scope':'link-discovery','translation_status':{l:'original-language' for l in ['de','en','ru','uk']},'discovered_at':checked,'parent_url':resolved,'jurisdiction':'CH-SG' if '.sg.ch' in host else 'CH','type':'form-link' if re.search(r'formular|\.pdf|vorlage|muster',target,re.I) else 'guide-link'}
     if not re.search(r'\.(pdf|docx?|xlsx?)(?:\?|$)',target,re.I) and len(queue)<200:queue.append((target,source))
  except urllib.error.HTTPError as e:
   manifest.append({'url':url,'http_status':e.code,'status':'unchanged' if e.code==304 else 'error'})
  except Exception as e:manifest.append({'url':url,'status':'error','error':str(e)})
 checkpoint()
 print(json.dumps({'source_offset':offset,'pages_attempted':len(manifest),'http_200':sum(p.get('http_status')==200 for p in manifest),'discovered_links':len(discovered),'stored_unique_links':len(documents),'verified_forms':0}),flush=True)
if __name__=='__main__':main()
