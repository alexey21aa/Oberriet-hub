"""Candidate server-side ontology/graph primitives; never browser capability-dependent."""
import collections,json,math,re,unicodedata
from datetime import datetime,timezone,timedelta
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
MUNICIPALITIES=['Oberriet','Montlingen','Kriessern','Eichenwies','Altstätten','Rüthi','Eichberg','Rebstein','Marbach','Balgach','Widnau','Diepoldsau','Au','Berneck','St. Margrethen','Buchs','St.Gallen']
def norm(s):return ' '.join(re.findall(r'[^\W_]+',unicodedata.normalize('NFKC',s).casefold()))
def one_edit(a,b):
 if a==b:return True
 if abs(len(a)-len(b))>1:return False
 if len(a)==len(b):
  dif=[i for i,(x,y) in enumerate(zip(a,b)) if x!=y]
  return len(dif)==1 or (len(dif)==2 and dif[1]==dif[0]+1 and a[dif[0]]==b[dif[1]] and a[dif[1]]==b[dif[0]])
 if len(a)>len(b):a,b=b,a
 i=j=errors=0
 while i<len(a) and j<len(b):
  if a[i]==b[j]:i+=1;j+=1
  else:errors+=1;j+=1
  if errors>1:return False
 return True
class ConceptIndex:
 def __init__(self,root=ROOT/'data/v7/ontology'):
  self.concepts={r['id']:r for r in map(json.loads,(root/'concepts.jsonl').read_text().splitlines())}
  self.terms=collections.defaultdict(set)
  for a in map(json.loads,(root/'concept_aliases.jsonl').read_text().splitlines()):self.terms[a['normalized']].add(a['concept_id'])
  self.by_length=collections.defaultdict(list)
  for term in self.terms:self.by_length[len(term)].append(term)
 def resolve(self,query,lang='en'):
  if not isinstance(query,str) or len(query)>512:raise ValueError('Query must be a string of at most 512 characters')
  q=norm(query);matches=[]
  if q in self.terms:matches=[(q,'exact')]
  else:
   # Recover the complete phrase before generic fragments such as "shop" or
   # "office" can redirect a misspelled specific need to an unrelated category.
   if len(q)>=4:
    matches=[(t,'one-edit') for length in [len(q)-1,len(q),len(q)+1] for t in self.by_length[length] if one_edit(q,t)]
   padded=' '+q+' '
   if not matches:matches=[(t,'phrase') for t in self.terms if ' '+t+' ' in padded]
   if matches:
    longest=max(len(t.split()) for t,k in matches);matches=[m for m in matches if len(m[0].split())==longest]
   else:
    # Whole one-word typos and phrase n-grams, never uncontrolled substring fuzzy.
    words=q.split()
    # A stray "test" -> "tent" must not turn an unknown medical phrase into
    # camping. Multiword unknown intent needs a multiword fuzzy match.
    minimum=1 if len(words)==1 else 2
    spans={' '.join(words[i:i+n]) for n in range(minimum,min(5,len(words))+1) for i in range(len(words)-n+1)}
    for span in spans:
     if len(span)<4:continue
     for length in [len(span)-1,len(span),len(span)+1]:
      for t in self.by_length[length]:
       if one_edit(span,t):matches.append((t,'one-edit'))
    if matches:
     longest=max(len(t.split()) for t,k in matches);matches=[m for m in matches if len(m[0].split())==longest]
  ids=sorted({cid for t,k in matches for cid in self.terms[t]})
  age=re.search(r'(?:age\s*|возраст\s*)?(\d{1,2})\s*(?:years?|лет|года?|років|роки|jahre)',q)
  facets={'municipalities':[m for m in MUNICIPALITIES if ' '+norm(m)+' ' in ' '+q+' '],
   'age':int(age.group(1)) if age else None,
   'free_requested':bool(re.search(r'\b(?:free|kostenlos|gratis|бесплатн\w*|безкоштовн\w*)\b',q)),
   'open_now_requested':any(s in q for s in ['open now','jetzt offen','открыто сейчас','відчинено зараз'])}
  return {'concept_ids':ids,'ambiguous':len(ids)>1,'matches':[{'term':t,'method':k} for t,k in sorted(set(matches))],
   'facets':facets,'language':lang,'device_independent':True,'facts_generated':False}
def distance_km(a,b):
 if not a or not b or not all(k in a and k in b for k in ['lat','lon']):return None
 for p in (a,b):
  if any(isinstance(p[k],bool) or not isinstance(p[k],(int,float)) or not math.isfinite(p[k]) for k in ['lat','lon']):return None
  if not -90<=p['lat']<=90 or not -180<=p['lon']<=180:return None
 x,y=math.radians(a['lat']),math.radians(b['lat']);dx=y-x;dy=math.radians(b['lon']-a['lon'])
 return 6371*2*math.asin(min(1,math.sqrt(math.sin(dx/2)**2+math.cos(x)*math.cos(y)*math.sin(dy/2)**2)))
def geo_select(rows,municipality=None,origin=None,radii=(10,25,50)):
 # Unknown geography never becomes "local". No whole-corpus administrative fallback.
 local=[r for r in rows if municipality and not r.get('municipality_ambiguous',False) and norm(r.get('municipality') or '')==norm(municipality)]
 if local:return {'stage':'municipality','results':local,'distance_available':False}
 if origin:
  located=[(distance_km(origin,r.get('coordinates')),r) for r in rows];located=[(d,r) for d,r in located if d is not None]
  for radius in radii:
   found=sorted([(d,r) for d,r in located if d<=radius],key=lambda x:(x[0],x[1]['id']))
   if found:return {'stage':'radius','radius_km':radius,'results':[dict(r,distance_km=round(d,2)) for d,r in found],'distance_available':True}
 regional=[r for r in rows if r.get('municipality') in MUNICIPALITIES]
 return {'stage':'regional' if regional else 'no-verified-location','results':regional,'distance_available':False}
def merge_candidates(a,b):
 # Domain/phone alone are weak: chains, booking platforms and branches share them.
 if a['id']==b['id']:return 'same-source-object'
 ua,ub=a.get('legal_uid_hint'),b.get('legal_uid_hint')
 if ua and ub and ua!=ub:return None
 address_a=a.get('public_contact_metadata',{});address_b=b.get('public_contact_metadata',{})
 address_keys=['addr:street','addr:housenumber','addr:postcode']
 same_address=all(address_a.get(k) and norm(address_a[k])==norm(address_b.get(k,'')) for k in address_keys)
 if norm(a['name'])==norm(b['name']) and same_address:return 'review-name-address'
 d=distance_km(a.get('coordinates'),b.get('coordinates'))
 if norm(a['name'])==norm(b['name']) and d is not None and d<0.025:return 'review-name-location'
 if ua and ua==ub:return 'same-legal-entity-keep-branches'
 return None
def taxonomy_candidates(index,entities,query,municipality=None,origin=None):
 """Candidate discovery search only. Taxonomy matches never verify offerings/hours."""
 resolved=index.resolve(query)
 classifications=[c for cid in resolved['concept_ids'] for c in index.concepts[cid]['classifications']]
 rows=[r for r in entities if any(all(r.get('category_tags',{}).get(k)==v for k,v in c.items()) for c in classifications)]
 selected=geo_select(rows,municipality,origin)
 return dict(selected,query_understanding=resolved,mode='discovery-only',verified_offerings=0,live=False)
def reviewed_offering_candidates(index,entities,offerings,query,municipality=None,origin=None):
 """Search reviewed graph offers independently of OSM discovery categories."""
 resolved=index.resolve(query);ids=set(resolved['concept_ids']);by_id={r['id']:r for r in entities};matches=[]
 for offer in offerings:
  if not ids.intersection(offer.get('concept_ids',[])) or not offer.get('offering_verified'):continue
  p=offer['source_provenance'];checked=datetime.fromisoformat(p['checked_at'].replace('Z','+00:00'))
  if checked.tzinfo is None:checked=checked.replace(tzinfo=timezone.utc)
  if not timedelta(0)<=datetime.now(timezone.utc)-checked<=timedelta(days=p.get('ttl_days',7)):continue
  entity=by_id.get(offer['business_entity_id'])
  if not entity:continue
  review=entity.get('primary_provider_review',{})
  matches.append(dict(offer,municipality=review.get('municipality',entity.get('municipality')),coordinates=None))
 selected=geo_select(matches,municipality,origin)
 return dict(selected,query_understanding=resolved,mode='reviewed-candidate-offerings',live=False,opening_hours_asserted=False)
