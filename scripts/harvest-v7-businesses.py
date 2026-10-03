#!/usr/bin/env python3
"""Bounded, serial OSM municipality discovery. No live WordPress writes.
Public POIs are discovery only; classification is not proof of an offering.
"""
import argparse,hashlib,json,re,time,urllib.request,urllib.parse
from datetime import datetime,timezone,timedelta
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'data/v7/business';OUT.mkdir(parents=True,exist_ok=True)
MUNICIPALITIES=['Oberriet','Altstätten','Rüthi','Eichberg','Rebstein','Marbach','Balgach','Widnau','Diepoldsau','Au','Berneck','St. Margrethen']
ENDPOINT='https://overpass-api.de/api/interpreter'
KEYS=['shop','craft','office','healthcare','amenity','leisure','tourism','club']
UA='OberrietHub/0.7 public-POI-discovery (+https://oberriethub.ch)'
def stamp():return datetime.now(timezone.utc).isoformat(timespec='seconds')
def atomic(path,value):
 tmp=path.with_suffix(path.suffix+'.tmp');tmp.write_text(value);tmp.replace(path)
def read(path,default):return json.loads(path.read_text()) if path.exists() else default
def normalize_name(s):return ' '.join(re.findall(r'[^\W_]+',s.casefold()))
def normalize_element(e,municipality,receipt):
 t=e.get('tags',{});name=t.get('name');key=next((k for k in KEYS if k in t),None)
 if e.get('type') not in ['node','way','relation'] or not name or not key:return None
 if t.get('access')=='private' or any(k.startswith(('disused:','abandoned:','proposed:')) for k in t):return None
 # Public business contact tags only. No employees/rosters/notes/private contacts.
 location=e.get('center') or {k:e[k] for k in ['lat','lon'] if k in e}
 allowed=['addr:street','addr:housenumber','addr:postcode','addr:city','website','contact:website','phone','contact:phone','opening_hours','wheelchair']
 facts={k:t[k] for k in allowed if k in t}
 uid=t.get('ref:vatin','')
 if not re.fullmatch(r'CHE[- ]?\d{3}[. ]?\d{3}[. ]?\d{3}(?: MWST)?',uid):uid=None
 return {'id':f"osm:{e['type']}:{e['id']}",'entity_kind':'public-establishment','name':name,
  'normalized_name':normalize_name(name),'municipality':municipality,'municipalities':[municipality],'municipality_ambiguous':False,'coordinates':location,
  'category_tags':{k:t[k] for k in KEYS if k in t},'public_contact_metadata':facts,'legal_uid_hint':uid,
  'source_links':[f"https://www.openstreetmap.org/{e['type']}/{e['id']}"],
  'source_provenance':[{'source_id':'osm-overpass','checked_at':receipt['checked_at'],'receipt_sha256':receipt['sha256'],'trust':'D','review_status':'discovery-only'}],
  'offerings_verified':False,'opening_hours_verified':False,'published_live':False}
def query(m):
 # Names such as Rüthi (SG), Au (SG) and Marbach (SG) require the
 # official canton suffix. Limit discovery to St. Gallen, never all of CH.
 if m not in MUNICIPALITIES:raise ValueError('Unknown municipality')
 pattern=json.dumps('^'+re.escape(m)+r'( \(SG\))?$',ensure_ascii=False)
 return f'[out:json][timeout:25];area["ISO3166-2"="CH-SG"]["admin_level"="4"]->.sg;relation(area.sg)["boundary"="administrative"]["admin_level"="8"]["name"~{pattern}];map_to_area->.a;.a out tags;nwr(area.a)["name"][~"^(shop|craft|office|healthcare|amenity|leisure|tourism|club)$"~"."];out center tags;'
def resolved_boundary(elements,municipality):
 areas=[e for e in elements if e.get('type')=='area']
 if len(areas)!=1:raise ValueError('Expected exactly one municipality area; unresolved or ambiguous is not zero coverage')
 area=areas[0];tags=area.get('tags',{})
 if tags.get('name') not in [municipality,municipality+' (SG)'] or tags.get('boundary')!='administrative' or tags.get('admin_level')!='8':
  raise ValueError('Municipality boundary identity mismatch')
 if not isinstance(area.get('id'),int) or area['id']<=3600000000:raise ValueError('Expected relation-derived municipality area')
 return {'area_id':area['id'],'relation_id':area['id']-3600000000,'name':tags['name'],'canton':'CH-SG','bfs_ref':tags.get('ref:FSO')}
def harvest(max_municipalities,timeout,retry_failed=False):
 state=read(OUT/'state.json',{'next_cursor':0,'jobs':[]});entities={x['id']:x for x in read(OUT/'business_entities.json',[])}
 cooldown=state.get('retry_not_before')
 if cooldown and datetime.now(timezone.utc)<datetime.fromisoformat(cooldown):
  print(json.dumps({'status':'cooldown','retry_not_before':cooldown}));return
 cursor=state['next_cursor'];stop=min(len(MUNICIPALITIES),cursor+max_municipalities)
 latest={j['municipality']:j['status'] for j in state['jobs']}
 indices=([i for i,m in enumerate(MUNICIPALITIES) if latest.get(m)=='error'][:max_municipalities] if retry_failed else range(cursor,stop))
 for i in indices:
  m=MUNICIPALITIES[i];q=query(m);receipt={'municipality':m,'checked_at':stamp(),'endpoint':ENDPOINT,'query_sha256':hashlib.sha256(q.encode()).hexdigest()}
  try:
   request=urllib.request.Request(ENDPOINT,data=urllib.parse.urlencode({'data':q}).encode(),headers={'User-Agent':UA,'Accept':'application/json'})
   with urllib.request.urlopen(request,timeout=timeout) as response:body=response.read(8000001)
   if len(body)>8000000:raise ValueError('Response exceeds 8MB')
   result=json.loads(body)
   if result.get('remark'):raise ValueError('Incomplete Overpass response: '+result['remark'][:200])
   receipt.update(sha256=hashlib.sha256(body).hexdigest(),http_status=200,bytes=len(body),osm_base=result.get('osm3s',{}).get('timestamp_osm_base'))
   elements=result.get('elements',[])
   receipt['municipality_area_resolved']=False
   boundary=resolved_boundary(elements,m)
   receipt.update(municipality_area_resolved=True,boundary=boundary)
   count=0
   for e in elements:
    row=normalize_element(e,m,receipt)
    if row:
     old=entities.get(row['id'])
     if old:
      scopes=sorted(set(old.get('municipalities',[old['municipality']])+[m]))
      row['municipalities']=scopes;row['municipality_ambiguous']=len(scopes)>1
      row['municipality']=None if len(scopes)>1 else scopes[0]
      row['source_provenance']=old['source_provenance']+[p for p in row['source_provenance'] if p not in old['source_provenance']]
     entities[row['id']]=row;count+=1
   receipt['discovered_entities']=count;receipt['status']='discovery-complete'
  except urllib.error.HTTPError as e:receipt.update(status='error',http_status=e.code,error=e.read(1200).decode('utf8','replace'))
  except Exception as e:receipt.update(status='error',error=str(e)[:400])
  state['jobs'].append(receipt)
  if receipt.get('http_status') in [429,406]:state['retry_not_before']=(datetime.now(timezone.utc)+timedelta(minutes=5)).isoformat(timespec='seconds')
  if not retry_failed:state['next_cursor']=i+1
  atomic(OUT/'business_entities.json',json.dumps(sorted(entities.values(),key=lambda x:x['id']),ensure_ascii=False,indent=2)+'\n')
  atomic(OUT/'state.json',json.dumps(state,ensure_ascii=False,indent=2)+'\n');print(json.dumps(receipt,ensure_ascii=False),flush=True)
  if receipt.get('http_status') in [429,406]:break
  if i+1<stop:time.sleep(10)
 print(json.dumps({'discovery_entities':len(entities),'verified_offerings':0,'published_live':0,'next_cursor':state['next_cursor']}))
if __name__=='__main__':
 ap=argparse.ArgumentParser();ap.add_argument('--max-municipalities',type=int,default=1);ap.add_argument('--timeout',type=int,default=35);ap.add_argument('--retry-failed',action='store_true');args=ap.parse_args()
 if not 1<=args.max_municipalities<=12 or not 1<=args.timeout<=45:ap.error('Use 1–12 municipalities and 1–45sec timeout')
 harvest(args.max_municipalities,args.timeout,args.retry_failed)
