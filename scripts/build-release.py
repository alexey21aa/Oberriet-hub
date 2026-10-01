"""Build one complete, credential-free deployable package from tested source."""
from pathlib import Path
import shutil,zipfile,subprocess,json,hashlib
R=Path(__file__).resolve().parents[1];out=R.parent/'deliverables';out.mkdir(exist_ok=True);release=R/'release'/'Oberriet_Hub_MVP';
if release.exists():shutil.rmtree(release)
release.mkdir(parents=True,exist_ok=True)
# Extract official WordPress core, then install our exact tested plugins/theme.
core_input=Path(__import__('os').environ.get('OBERHUB_WORDPRESS_CORE',str(R.parent/'recovered/package/Oberriet_Hub_MVP/wordpress')))
if core_input.is_dir():shutil.copytree(core_input,release/'wordpress')
else:
 with zipfile.ZipFile(core_input) as z:z.extractall(release)
core=release/'wordpress';content=core/'wp-content'
for x in [content/'plugins'/'akismet',content/'plugins'/'hello.php']:
 if x.is_dir():shutil.rmtree(x)
 elif x.exists():x.unlink()
for name in ['oberhub-core','two-factor']:shutil.copytree(R/'wp-content/plugins'/name,content/'plugins'/name,dirs_exist_ok=True)
shutil.copytree(R/'wp-content/themes/oberhub-theme',content/'themes/oberhub-theme',dirs_exist_ok=True)
shutil.copy2(R/'scripts/apache.htaccess',core/'.htaccess')
# Source tree via Git tracked files; all reports and docs must be committed first.
source=release/'source';source.mkdir(exist_ok=True)
files=subprocess.check_output(['git','ls-files','-z'],cwd=R).decode().split('\0')
for f in filter(None,files):
 if f.startswith('tests/results/') and (f.endswith('.html') or f.endswith('.log') or 'qa-blueprint' in f):continue
 target=source/f;target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(R/f,target)
shutil.copytree(R/'docs',release/'docs',dirs_exist_ok=True);shutil.copy2(R/'README.md',release/'README.md')
previews=release/'previews';previews.mkdir(exist_ok=True)
for n in ['desktop.png','mobile.png','admin.png']:
 if (R/'tests/results'/n).exists():shutil.copy2(R/'tests/results'/n,previews/n)
subprocess.run(['git','bundle','create',str(release/'oberriet-hub.git.bundle'),'--all'],cwd=R,check=True)
# ZIPs compatible with the native uploader for an existing WordPress installation.
for kind,name in [('plugins','oberhub-core'),('plugins','two-factor'),('themes','oberhub-theme')]:
 with zipfile.ZipFile(release/(name+'.zip'),'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
  for p in sorted((R/'wp-content'/kind/name).rglob('*')):
   if p.is_file():z.write(p,Path(name)/p.relative_to(R/'wp-content'/kind/name))
manifest={}
for p in sorted(release.rglob('*')):
 if p.is_file() and p.name!='SHA256SUMS.json':manifest[str(p.relative_to(release))]=hashlib.sha256(p.read_bytes()).hexdigest()
(release/'SHA256SUMS.json').write_text(json.dumps(manifest,indent=2))
archive=out/'Oberriet_Hub_MVP.zip'
with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
 for p in sorted(release.rglob('*')):
  if p.is_file():z.write(p,Path('Oberriet_Hub_MVP')/p.relative_to(release))
with zipfile.ZipFile(archive) as z:
 bad=z.testzip();assert bad is None,bad
 assert not any(n.endswith('wp-config.php') or n.endswith('.sqlite') for n in z.namelist())
 assert z.read('Oberriet_Hub_MVP/wordpress/wp-content/plugins/oberhub-core/seed.json')==(R/'data/seed.json').read_bytes()
print(json.dumps({'archive':str(archive),'bytes':archive.stat().st_size,'sha256':hashlib.sha256(archive.read_bytes()).hexdigest(),'files':len(manifest)}))
