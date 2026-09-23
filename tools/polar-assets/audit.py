"""Check asset inventory, syntax and duplicate build entries without changing files."""
from pathlib import Path
import json,subprocess,sys
base=Path(__file__).resolve().parent
root=base.parents[1]
theme=root/'app/public/wp-content/themes/shanying'
order=json.loads((base/'bundle-order.json').read_text())
errors=[]
for group in ('styles','scripts','admin_scripts'):
 values=list(order[group].values())
 if len(values)!=len(set(values)):errors.append('Duplicate source in '+group)
for source in order['scripts'].values():
 if not (base/'source'/source).is_file():errors.append('Missing script: '+source)
listed={Path(v).name for v in order['scripts'].values()}|set(order['admin_scripts'].values())|{'header-lucide.bundle.js','stat-roll.js','article-media.js'}
for p in (base/'source/assets/js').glob('*.js'):
 if p.name not in listed:errors.append('Unlisted business script: '+p.name)
listedcss={Path(v).name for v in order['styles'].values() if not v.startswith('https:')}|{'admin.css','admin-square.css','admin-skin.css','admin-customizer.css','editor.css'}
for p in (base/'source/assets/css').glob('*.css'):
 if p.name not in listedcss:errors.append('Unlisted stylesheet: '+p.name)
js=set((base/'source/assets/js').rglob('*.js'))|set((theme/'assets').rglob('*.js'))
for p in sorted(js):
 result=subprocess.run(['node','--check',str(p)],capture_output=True,text=True)
 if result.returncode:errors.append(str(p)+': '+result.stderr)
css=list((base/'source').rglob('*.css'))+list((theme/'assets').rglob('*.css'))
check="""import {transform} from 'esbuild';import fs from 'node:fs';
const files=JSON.parse(fs.readFileSync(0,'utf8'));let failed=false;
for(const file of files){try{await transform(fs.readFileSync(file,'utf8'),{loader:'css',sourcefile:file,logLevel:'silent'});}catch(e){failed=true;console.error(e.message);}}
if(failed)process.exitCode=1;
"""
result=subprocess.run(['node','--input-type=module','-e',check],cwd=base/'lucide-motion',input=json.dumps([str(p) for p in css]),capture_output=True,text=True)
if result.returncode:errors.append(result.stderr)
print(f'Checked {len(js)} JavaScript files and {len(css)} CSS files; {len(errors)} errors.')
for error in errors:print(error)
sys.exit(bool(errors))
