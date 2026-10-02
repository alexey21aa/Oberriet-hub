import concurrent.futures,html,json,re,urllib.request,hashlib
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];P=ROOT/'data'/'expansion'
def links(language):
 raw=urllib.request.urlopen('https://www.hallo.sg.ch/'+language+'.html',timeout=30).read().decode();result={}
 for url,title in re.findall(r'<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>',raw,re.S):
  title=' '.join(html.unescape(re.sub('<[^>]+>',' ',title)).split())
  if url.startswith('/'+language+'/') and title and not re.search('/(?:f1?|t)/',url):result.setdefault(url.split('/',2)[2],title)
 return result
langs={}
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
 tasks={pool.submit(links,l):l for l in ['de','en','uk','ru']}
 for f in concurrent.futures.as_completed(tasks):langs[tasks[f]]=f.result()
records=[]
for path,title in langs['de'].items():
 if not all(path in langs[l] for l in ['en','uk','ru']):continue
 url='https://www.hallo.sg.ch/de/'+path
 records.append({'url':url,'title':title,'translation':{l:langs[l][path] for l in ['en','uk','ru']},'authority':'Kanton St.Gallen / Fachstelle Integration','topic':'integration','id':'nav-'+hashlib.sha256(url.encode()).hexdigest()[:12],'localized_source_urls':{l:'https://www.hallo.sg.ch/'+l+'/'+path for l in langs}})
import importlib.util
spec=importlib.util.spec_from_file_location('build',ROOT/'scripts/build-knowledge.py');build=importlib.util.module_from_spec(spec);spec.loader.exec_module(build)
with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
 tasks={pool.submit(build.fetch,r['url']):r for r in records}
 out=[]
 for f in concurrent.futures.as_completed(tasks):
  r=tasks[f];m=f.result();m.update(r);out.append(m)
(P/'hallo-pages.json').write_text(json.dumps(out,ensure_ascii=False,indent=2));print('Hallo fully multilingual verified pages',len(out),sum(x['status']==200 for x in out),flush=True)
