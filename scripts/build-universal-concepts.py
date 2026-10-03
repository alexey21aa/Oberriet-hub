#!/usr/bin/env python3
"""Pinned, licensed taxonomy -> canonical tag concepts + separate multilingual aliases.
Never count translations, brands, audiences, locations or query combinations as concepts.
"""
import argparse,collections,csv,hashlib,json,re,unicodedata,urllib.request
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
UP=ROOT/'data/v7/upstream';OUT=ROOT/'data/v7/ontology'
SHA='d0f7d2e897c2c3a3e84879cdf90ed7b5d768e199'
KEYS=['amenity','shop','craft','office','healthcare','leisure','sport','tourism','club','social_facility',
 'emergency','public_transport','playground','vending','attraction','historic','natural']
EXCLUDE={'yes','no','other','unknown','construction','disused','abandoned','vacant','proposed'}
def normalize(s):
 return ' '.join(re.findall(r'[^\W_]+',unicodedata.normalize('NFKC',s).casefold()))
def write(path,value):
 path.parent.mkdir(parents=True,exist_ok=True);tmp=path.with_suffix(path.suffix+'.tmp');tmp.write_text(value);tmp.replace(path)
def jsonl(path,rows):write(path,''.join(json.dumps(x,ensure_ascii=False,separators=(',',':'))+'\n' for x in rows))
def build():
 presets=json.loads((UP/'osm-presets.json').read_text())
 trans={lang:json.loads((UP/f'osm-{lang}.json').read_text())[lang]['presets']['presets'] for lang in ['en','de','ru','uk']}
 concepts={};rejected=collections.Counter();by_label={};aliases={};preset_map={}
 for pid,p in sorted(presets.items()):
  tags=p.get('tags',{});key=next((k for k in KEYS if k in tags),None)
  if not key or pid.startswith('@') or p.get('searchable') is False:rejected['technical_or_unsearchable']+=1;continue
  value=tags[key]
  if value in EXCLUDE or '*' in value or ';' in value or any(k in tags for k in ['brand','name','operator']):rejected['generic_brand_or_named']+=1;continue
  # One concept per primary tag. Subpresets based on gender/cuisine/access are facets,
  # not canonical topics. Existing synonyms with the same label merge across keys.
  base_pid=key+'/'+value;names=trans['en'].get(base_pid,{})
  label=names.get('name') or value.replace('_',' ')
  identity=normalize(label);cid=by_label.get(identity) or 'osm:'+key+':'+value
  if cid not in concepts:
   concepts[cid]={'id':cid,'label':label,'parent_id':'category:'+key,'classifications':[],
    'source_id':'osm-id-schema','source_revision':SHA,'semantic_review':'taxonomy-derived-pending-cross-taxonomy-review'}
   by_label[identity]=cid
  else:rejected['same_primary_tag_or_label']+=1
  mapping={key:value}
  if mapping not in concepts[cid]['classifications']:concepts[cid]['classifications'].append(mapping)
  preset_map[pid]=cid
  # Facet-specific names must not become unrestricted aliases for the parent.
  if pid!=base_pid and pid.count('/')>1:continue
  for lang,t in trans.items():
   entry=t.get(base_pid,{})
   values=[(entry.get('name',label if lang=='en' else ''),'label')]+[(v,'alias') for v in entry.get('terms',[])]+[(v,'alias') for v in entry.get('aliases',[])]
   for text,kind in values:
    n=normalize(text)
    if n and len(n)<=100:aliases[(cid,lang,n)]={'concept_id':cid,'lang':lang,'term':text,'normalized':n,'kind':kind}
 extensions=[];canonical_map={}
 extension=ROOT/'data/v7/review/medical-concept-extension.json'
 if extension.exists():extensions.extend(json.loads(extension.read_text()))
 needs=ROOT/'data/v7/review/human-needs.tsv'
 if needs.exists():
  with needs.open() as f:
   for r in csv.DictReader(f,delimiter='\t'):
    extensions.append({'id':'need:'+r['domain']+':'+r['slug'],'label':r['en'],
     'labels':{l:r[l] for l in ['en','de','ru','uk']},'parent_id':'category:'+r['domain'],
     'classifications':[{'human_need':r['slug']}],'source_id':'oberhub-editorial-needs',
     'semantic_review':'editorial-distinction-reviewed-cross-taxonomy-audit-pending',
     'provider_facts_asserted':False})
 for c in extensions:
   label_key=normalize(c['label']);cid=by_label.get(label_key,c['id'])
   canonical_map[c['id']]=cid
   if cid not in concepts:concepts[cid]=c;by_label[label_key]=cid
   else:rejected['same_primary_tag_or_label']+=1
   for lang,text in c['labels'].items():
    n=normalize(text);aliases[(cid,lang,n)]={'concept_id':cid,'lang':lang,'term':text,'normalized':n,'kind':'label'}
 # Aliases shared by different concepts are ambiguous: retained, never blindly merged.
 rows=sorted(concepts.values(),key=lambda x:x['id']);ars=sorted(aliases.values(),key=lambda x:(x['concept_id'],x['lang'],x['normalized']))
 jsonl(OUT/'concepts.jsonl',rows);jsonl(OUT/'concept_aliases.jsonl',ars)
 write(OUT/'preset_concept_map.json',json.dumps(preset_map,ensure_ascii=False,separators=(',',':'))+'\n')
 write(OUT/'extension_concept_map.json',json.dumps(canonical_map,ensure_ascii=False,indent=2)+'\n')
 reverse=collections.defaultdict(set)
 for a in ars:reverse[(a['lang'],a['normalized'])].add(a['concept_id'])
 metrics={'canonical_concepts':len(rows),'canonical_concepts_semantically_audited':0,
  'unique_terms':len(reverse),'aliases':sum(a['kind']=='alias' for a in ars),
  'translations':sum(a['kind']=='label' and a['lang']!='en' for a in ars),'typo_forms':0,
  'rejected_duplicates':rejected['same_primary_tag_or_label'],'excluded_presets':dict(rejected),
  'ambiguous_terms':sum(len(v)>1 for v in reverse.values()),'source_breakdown':dict(collections.Counter(r['source_id'] for r in rows)),
  'target_25000_met':False,'target_100000_met':False,'businesses_inferred_from_taxonomy':0,
  'limitations':['OSM taxonomy, primary-provider concepts and editorial human needs; targets remain pending. Editorial concepts assert no provider facts.',
   'Exact normalized label and primary-tag dedupe; embedding semantic audit still pending.',
   'Terms/translations and combinatorial intents do not count as canonical topics.']}
 write(OUT/'concept_metrics.json',json.dumps(metrics,ensure_ascii=False,indent=2)+'\n')
 write(OUT/'source_receipts.json',json.dumps(json.loads((UP/'receipts.json').read_text()),indent=2)+'\n')
 write(OUT/'LICENSE-OSM-SCHEMA.md',(UP/'osm-LICENSE.md').read_text())
 print(json.dumps(metrics))
if __name__=='__main__':
 ap=argparse.ArgumentParser();ap.add_argument('--fetch',action='store_true');args=ap.parse_args()
 if args.fetch:
  UP.mkdir(parents=True,exist_ok=True);receipts=[]
  paths={'osm-presets.json':'dist/presets.min.json','osm-LICENSE.md':'LICENSE.md',**{f'osm-{l}.json':f'dist/translations/{l}.min.json' for l in ['en','de','ru','uk']}}
  for name,path in paths.items():
   url=f'https://raw.githubusercontent.com/openstreetmap/id-tagging-schema/{SHA}/{path}'
   with urllib.request.urlopen(urllib.request.Request(url,headers={'User-Agent':'OberrietHub/0.7 (+https://oberriethub.ch)'}),timeout=20) as r:body=r.read(2000001)
   if len(body)>2000000:raise ValueError('Source exceeds 2MB')
   write(UP/name,body.decode());receipts.append({'file':name,'url':url,'sha256':hashlib.sha256(body).hexdigest(),'bytes':len(body)})
  write(UP/'receipts.json',json.dumps(receipts,indent=2)+'\n')
 build()
