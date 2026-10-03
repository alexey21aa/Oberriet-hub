#!/usr/bin/env python3
"""Bounded primary-page receipts. Bodies stay in ignored scratch evidence cache."""
import argparse,hashlib,ipaddress,json,socket,urllib.request,urllib.error,urllib.parse,urllib.robotparser
from datetime import datetime,timezone
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
UA='OberrietHub/0.7 primary-directory-review (+https://oberriethub.ch)'
def safe_url(url):
 p=urllib.parse.urlsplit(url)
 if p.scheme!='https' or not p.hostname or p.username or p.password or p.port not in (None,443):raise ValueError('Unsafe URL')
 addresses=socket.getaddrinfo(p.hostname,443,type=socket.SOCK_STREAM)
 if not addresses or any(not ipaddress.ip_address(a[4][0]).is_global for a in addresses):raise ValueError('Non-public DNS')
 return p
class Guard(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,req,fp,code,msg,headers,newurl):
  p=safe_url(newurl)
  if p.hostname!=urllib.parse.urlsplit(req.full_url).hostname:raise ValueError('Cross-host redirect requires review')
  return super().redirect_request(req,fp,code,msg,headers,newurl)
def fetch(url,limit=2000000):
 safe_url(url)
 with urllib.request.build_opener(Guard()).open(urllib.request.Request(url,headers={'User-Agent':UA}),timeout=8) as r:
  body=r.read(limit+1)
  if len(body)>limit:raise ValueError('Page exceeds limit')
  return r.status,r.url,r.headers.get('Content-Type',''),body
if __name__=='__main__':
 ap=argparse.ArgumentParser();ap.add_argument('urls',nargs='+');ap.add_argument('--output',default='data/v7/review/civic-primary-receipts.json');args=ap.parse_args()
 if len(args.urls)>12:ap.error('Maximum 12 explicit URLs')
 target=ROOT/args.output;target.parent.mkdir(parents=True,exist_ok=True)
 records=json.loads(target.read_text()) if target.exists() else [];by_url={r['url']:r for r in records};robots={}
 for url in args.urls:
  receipt={'url':url,'checked_at':datetime.now(timezone.utc).isoformat(timespec='seconds')}
  try:
   p=safe_url(url)
   if p.hostname not in robots:
    rp=urllib.robotparser.RobotFileParser()
    try:_,_,_,body=fetch('https://'+p.hostname+'/robots.txt',256000);rp.parse(body.decode('utf8','replace').splitlines())
    except urllib.error.HTTPError as e:
     if e.code not in (404,410):raise
     rp.parse([])
    robots[p.hostname]=rp
   if not robots[p.hostname].can_fetch(UA,url):raise ValueError('Robots denied')
   status,final,ctype,body=fetch(url)
   if 'text/html' not in ctype:raise ValueError('Expected HTML')
   sha=hashlib.sha256(body).hexdigest();cache=ROOT/'data/evidence/v7';cache.mkdir(parents=True,exist_ok=True);(cache/(sha+'.html')).write_bytes(body)
   receipt.update(status='fetched',http_status=status,final_url=final,content_type=ctype,sha256=sha,bytes=len(body),robots_checked=True)
  except Exception as e:receipt.update(status='error',error=str(e)[:400])
  by_url[url]=receipt;tmp=target.with_suffix('.tmp');tmp.write_text(json.dumps(list(by_url.values()),ensure_ascii=False,indent=2)+'\n');tmp.replace(target)
  print(json.dumps(receipt,ensure_ascii=False),flush=True)
