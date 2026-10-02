#!/usr/bin/env python3
"""Apply the reviewed legal-booking correction without changing record identities."""
import json
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
patch=json.loads((ROOT/'data/v6-legal-booking-correction.json').read_text())
assert set(patch)=={'service','overview','apply'}
assert patch['service']['id']=='practical-legal-advice'
assert patch['overview']['id']=='answer-practical-legal-advice-overview'
assert patch['apply']['id']=='answer-practical-legal-advice-apply'
plans=[]
for name in ['data/seed.json','data/knowledge.json','data/knowledge-expansion.json','data/expansion/practical-scenarios.json','wp-content/plugins/oberhub-core/seed.json']:
 path=ROOT/name;original=path.read_text();data=json.loads(original);matched=[]
 def visit(value):
  if isinstance(value,dict):
   for kind,correction in patch.items():
    if value.get('id')!=correction['id']:continue
    fields=['description_short','description_full'] if kind=='service' else ['answer']
    for field in fields:
     assert set(correction[field])=={'de','en','ru','uk'}
     assert all(isinstance(text,str) and 0<len(text)<=2000 for text in correction[field].values())
    for field in fields+['content_review_sources','content_reviewed_at']:value[field]=correction[field]
    matched.append(correction['id'])
   for child in value.values():visit(child)
  elif isinstance(value,list):
   for child in value:visit(child)
 visit(data)
 assert matched, 'Expected correction targets missing: '+name
 changed=json.dumps(data,ensure_ascii=False,indent=2)+'\n'
 plans.append((path,original,changed,matched))
for path,original,changed,matched in plans:
 if changed!=original:
  temp=path.with_suffix(path.suffix+'.tmp');temp.write_text(changed);temp.replace(path)
 print(json.dumps({'path':str(path.relative_to(ROOT)),'matched':len(matched),'changed':changed!=original}))
