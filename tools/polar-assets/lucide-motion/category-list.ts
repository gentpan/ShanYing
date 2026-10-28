const mounted=new Map<HTMLElement,()=>void>();
function mount(){
 for(const [el,clean] of mounted)if(!el.isConnected){clean();mounted.delete(el);}
 document.querySelectorAll<HTMLElement>('[data-category-list]').forEach(card=>{
  if(mounted.has(card))return;
  const preview=card.querySelector<HTMLImageElement>('.feng-category-list__preview');
  const base=card.querySelector<HTMLImageElement>('.feng-category-list__base')?.src;
  const picture=card.querySelector<HTMLElement>('.feng-category-list__picture');
  if(!preview||!picture)return;
  const controller=new AbortController();let version=0,timer:number|undefined;
  const reset=()=>{version++;clearTimeout(timer);picture.classList.remove('is-loading');preview.classList.remove('is-visible');};
  const show=(url:string)=>{
   reset();if(!url||url===base)return;
   picture.classList.add('is-loading');const current=version;const image=new Image();image.src=url;
   image.decode().then(()=>{if(current!==version)return;timer=window.setTimeout(()=>{if(current!==version)return;picture.classList.remove('is-loading');preview.src=url;preview.hidden=false;requestAnimationFrame(()=>{if(current===version)preview.classList.add('is-visible');});},160);}).catch(()=>{if(current===version)picture.classList.remove('is-loading');});
  };
  card.querySelectorAll<HTMLElement>('[data-category-preview]').forEach(link=>{
   link.addEventListener('pointerenter',e=>{if(e.pointerType!=='touch')show(link.dataset.categoryPreview||'');},{signal:controller.signal});
   link.addEventListener('focus',()=>show(link.dataset.categoryPreview||''),{signal:controller.signal});
  });
  card.addEventListener('pointerleave',reset,{signal:controller.signal});
  card.addEventListener('focusout',e=>{if(!card.contains(e.relatedTarget as Node))reset();},{signal:controller.signal});
  mounted.set(card,()=>{reset();controller.abort();});
 });
}
document.addEventListener('xf:mounted',mount);
document.addEventListener('xf:before-unmount',()=>{mounted.forEach(clean=>clean());mounted.clear();});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();

let hubRequest:AbortController|null=null;
document.addEventListener('xf:before-unmount',()=>hubRequest?.abort());
async function fillHub(panel:HTMLElement,type='latest',page='1'){
 hubRequest?.abort();const controller=new AbortController();hubRequest=controller;
 const url=new URL(panel.dataset.hubEndpoint!,location.href);url.searchParams.set('action','feng_hub');url.searchParams.set('category',panel.dataset.hubCategory||'0');url.searchParams.set('type',type);url.searchParams.set('page',page);
 panel.setAttribute('aria-busy','true');const timer=setTimeout(()=>controller.abort(),12000);
 try{const response=await fetch(url,{signal:controller.signal,credentials:'same-origin'});const data=await response.json();if(!response.ok||!data.success)throw new Error();if(controller.signal.aborted||!panel.isConnected)return;panel.innerHTML=data.data.html;delete panel.dataset.hubLazy;mount();return true;}
 catch{if(!controller.signal.aborted)(window as any).fengToast?.('加载失败，请重试','error');return false;}
 finally{clearTimeout(timer);if(hubRequest===controller){panel.removeAttribute('aria-busy');hubRequest=null;}}
}
document.addEventListener('click',async event=>{
 const button=(event.target as Element).closest<HTMLButtonElement>('[data-collection-tab]');
 if(!button)return;
 const hub=button.closest('.feng-article-hub');if(!hub)return;
 const description=hub.querySelector<HTMLElement>('[data-hub-description]'),watermark=hub.querySelector<HTMLImageElement>('[data-hub-watermark]');
 if(description)description.textContent=button.dataset.description||'';
 if(watermark){watermark.hidden=!button.dataset.watermark;if(button.dataset.watermark)watermark.src=button.dataset.watermark;else watermark.removeAttribute('src');}

 hub.querySelectorAll<HTMLElement>('[data-collection-tab]').forEach(tab=>tab.setAttribute('aria-pressed',String(tab===button)));
 hub.querySelectorAll<HTMLElement>('[data-collection-panel]').forEach(panel=>{panel.hidden=panel.dataset.collectionPanel!==button.dataset.collectionTab;});
 const panel=hub.querySelector<HTMLElement>(`[data-collection-panel="${button.dataset.collectionTab}"]`);
 if(panel?.dataset.hubLazy==='1')await fillHub(panel);
});
document.addEventListener('click',async event=>{
 const button=(event.target as Element).closest<HTMLButtonElement>('[data-hub-type],[data-hub-page]');if(!button||button.disabled)return;
 const panel=button.closest<HTMLElement>('[data-hub-endpoint]');if(!panel)return;
 const ok=await fillHub(panel,button.dataset.hubType||button.dataset.hubSort||'latest',button.dataset.hubPage||'1');
 if(ok)panel.querySelector<HTMLElement>('[aria-pressed="true"]')?.focus({preventScroll:true});
});

// Category strip: manual selection with category backgrounds.
let stripCleanup: (()=>void)|undefined;
function mountStrip(){
 stripCleanup?.();
 const nav=document.querySelector<HTMLElement>('.feng-article-hub__tabs');if(!nav)return;
 const tabs=Array.from(nav.querySelectorAll<HTMLButtonElement>('[data-collection-tab]'));
 const abort=new AbortController(),signal=abort.signal;
 nav.style.setProperty('--category-count',String(tabs.length));
 tabs.forEach(tab=>{
  if(tab.querySelector('.polar-tab-label'))return;
  const label=document.createElement('span');label.className='polar-tab-label';while(tab.firstChild)label.append(tab.firstChild);tab.append(label);
  if(tab.dataset.watermark){const img=new Image();img.src=tab.dataset.watermark;img.alt='';img.className='polar-tab-cover';img.loading='lazy';img.addEventListener('error',()=>{img.remove();tab.classList.remove('has-category-cover');},{signal});tab.prepend(img);tab.classList.add('has-category-cover');}
 });
 stripCleanup=()=>{abort.abort();};
}
document.addEventListener('xf:mounted',mountStrip);
document.addEventListener('xf:before-unmount',()=>stripCleanup?.());
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mountStrip,{once:true});else mountStrip();
