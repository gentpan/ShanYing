"""Build and minify the front-end and admin bundles in explicit dependency order."""
from pathlib import Path
import json,re,shutil,subprocess,urllib.parse
base=Path(__file__).resolve().parent
root=base.parents[1]
theme=root if (root/'style.css').exists() else root/'app/public/wp-content/themes/shanying'
src=base/'source'
lucide=base/'lucide-motion'
order=json.loads((base/'bundle-order.json').read_text())
manifest={key:order[key] for key in ('styles','scripts')}
def css(text,url):
 def rewrite(match):
  value=match[1].strip(' \"\'')
  if value.startswith(('data:','#')):return match[0]
  resolved=urllib.parse.urljoin(url,value)
  if '/wp-content/themes/shanying/assets/' in resolved:resolved='../'+resolved.split('/wp-content/themes/shanying/assets/',1)[1]
  return 'url("'+resolved+'")'
 return re.sub(r'url\(([^)]+)\)',rewrite,text)
def stylesheet(handle,source):
 if source.startswith('https:'):
  filename={'feng-title-font':'title.css'}.get(handle,source.rsplit('/',1)[-1])
  path=src/'remote'/filename;url=source
 else:path=src/source;url='http://xifeng.local/wp-content/themes/shanying/'+source
 return '\n/* '+handle+' */\n'+css(path.read_text(),url)
def script(handle,path,enabled):
 return '\n;/* '+handle+' */\nif(window.'+enabled+'.includes('+json.dumps(handle)+')){\n'+path.read_text()+'\n}\n'
def minify(source,loader):
 program="""import {transform} from 'esbuild';
let source='';for await(const chunk of process.stdin)source+=chunk;
const result=await transform(source,{loader:process.argv[1],minify:true,legalComments:'inline'});
process.stdout.write(result.code);
"""
 result=subprocess.run(['node','--input-type=module','-e',program,loader],cwd=lucide,input=source,text=True,capture_output=True)
 if result.returncode:raise RuntimeError(result.stderr)
 return result.stdout
fonts=[];styles=[]
for handle,source in order['styles'].items():
 (fonts if 'font' in source or handle == 'feng-code-font' else styles).append(stylesheet(handle,source))
bootstrap="\n;window.shanyingEnabledScripts=JSON.parse(document.currentScript?.dataset.shanyingModules||'[]');\n"
scripts=[bootstrap+'\n/*! SunCalc 1.9.0 — BSD-2-Clause\n'+(src/'vendor/suncalc/LICENSE').read_text()+'\n*/\n'+(src/'vendor/suncalc/suncalc.js').read_text()]
scripts += [script(handle,src/source,'shanyingEnabledScripts') for handle,source in order['scripts'].items()]
subprocess.run(['node',str(lucide/'build.mjs')],check=True)
scripts.append('\n;/*! Lucide Animated\n'+(lucide/'LICENSE').read_text()+'\n*/\n'+(src/'assets/js/header-lucide.bundle.js').read_text())
scripts.append('\n;/* Statistic odometer */\n'+(src/'assets/js/stat-roll.js').read_text())
(theme/'assets/css/main.css').write_text(minify(''.join(fonts+styles),'css'))
(theme/'assets/js/main.js').write_text(minify(''.join(scripts),'js'))
relative='assets/vendor/highlight/highlight.min.js'
dest=theme/relative;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(src/relative,dest)
modules={'frontend':list(order['scripts']),'admin':list(order['admin_scripts'])}
(theme/'inc/modules.json').write_text(json.dumps(modules,indent=2)+'\n')
(base/'manifest.json').write_text(json.dumps(manifest,indent=2))
admin=[bootstrap.replace('shanyingEnabledScripts','shanyingEnabledAdminScripts')]
admin += [script(handle,src/'assets/js'/name,'shanyingEnabledAdminScripts') for handle,name in order['admin_scripts'].items()]
(theme/'assets/js/admin.js').write_text(minify(''.join(admin),'js'))
admincss=[(src/'assets/css'/name).read_text() for name in ('admin.css','admin-square.css','admin-skin.css','admin-customizer.css')]
admincss.extend(stylesheet('admin-'+name,'assets/css/'+name) for name in ('tokens.css','editor.css','reading.css'))
(theme/'assets/css/admin.css').write_text(minify('\n'.join(admincss),'css'))
print(len(styles),'CSS sections;',len(scripts),'JS sections; minified front-end and admin bundles')
