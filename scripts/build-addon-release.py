#!/usr/bin/env python3
"""Package only the isolated addon; never package or replace live core."""
import hashlib,json,re,zipfile
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];plugin=ROOT/'wp-content/plugins/oberhub-v6'
version=re.search(r'Version: ([\d.]+)',(plugin/'oberhub-v6.php').read_text()).group(1)
out=ROOT/'releases';out.mkdir(exist_ok=True);target=out/f'oberhub-v6-{version}.zip'
with zipfile.ZipFile(target,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for p in sorted(plugin.rglob('*')):
  if p.is_file():
   info=zipfile.ZipInfo('oberhub-v6/'+str(p.relative_to(plugin)),date_time=(2026,10,3,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16;z.writestr(info,p.read_bytes())
with zipfile.ZipFile(target) as z:
 assert z.testzip() is None
 assert 'oberhub-v6/src/UniversalSearch.php' in z.namelist() and 'oberhub-v6/ontology.json' in z.namelist()
 assert all(n.startswith('oberhub-v6/') for n in z.namelist())
manifest={'version':version,'sha256':hashlib.sha256(target.read_bytes()).hexdigest(),'bytes':target.stat().st_size,'candidate_only':True,'core_included':False,'ontology':json.loads((plugin/'ontology.json').read_text())['counts']}
(out/f'oberhub-v6-{version}.json').write_text(json.dumps(manifest,indent=2)+'\n');print(json.dumps(manifest))
