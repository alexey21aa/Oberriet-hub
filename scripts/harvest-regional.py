#!/usr/bin/env python3
"""Fetch a narrow verified regional provider allowlist without credentials/cookies."""
import urllib.request,hashlib,json,re,time
from pathlib import Path
from datetime import datetime,timezone
ROOT=Path(__file__).resolve().parents[1];P=ROOT/'data/expansion/regional-evidence'
PATHS=['offene-sprechstunde','kontakt','schluesselpersonen','schenk-mir-eine-geschichte','wie-mir-der-schnabel-waechst','frauentreffs','rassismus-und-diskriminierung','begruessungsgespraeche']
# Last path's URL is independently discovered; do not fabricate spelling.
PATHS[-1]='begrussungsgesprache'
P.mkdir(parents=True,exist_ok=True)
records=[]
for index,path in enumerate(PATHS):
 if index:time.sleep(10)
 url='https://integrationrheintal.ch/'+path+'/'
 with urllib.request.urlopen(urllib.request.Request(url,headers={'User-Agent':'OberrietHub/0.3 official-source-verification'}),timeout=25) as response:
  raw=response.read();status=response.status;resolved=response.url
 assert status==200
 timestamp=datetime.now(timezone.utc).isoformat(timespec='seconds').replace('+00:00','Z')
 (P/(path+'.html')).write_bytes(raw)
 records.append({'url':url,'resolved_url':resolved,'status':status,'sha256':hashlib.sha256(raw).hexdigest(),'last_checked':timestamp,'path':path})
 (P/'manifest.json').write_text(json.dumps(records,ensure_ascii=False,indent=2)+'\n')
 print(path,status,len(raw),flush=True)
