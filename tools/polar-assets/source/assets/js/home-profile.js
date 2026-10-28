(() => {
 let opener;
 let stackEpoch=0;
 const delay=ms=>new Promise(resolve=>setTimeout(resolve,ms));
 function loaded(img){return new Promise(resolve=>{
  if(img.complete&&img.naturalWidth){resolve(true);return;}
  let timer;const done=()=>{clearTimeout(timer);img.removeEventListener('load',done);img.removeEventListener('error',done);resolve(img.naturalWidth>0);};
  img.addEventListener('load',done);img.addEventListener('error',done);timer=setTimeout(done,12000);
 });}
 async function revealStack(){
  const epoch=++stackEpoch,stack=document.querySelector('[data-stack-loading]');if(!stack)return;
  const front=stack.querySelector('.feng-stack-front');await loaded(front);
  if(epoch!==stackEpoch||!stack.isConnected)return;
  try{await front.decode();}catch{}
  stack.classList.add('is-front-ready');
  const reduced=matchMedia('(prefers-reduced-motion:reduce)').matches;
  await delay(reduced?0:250);
  for(const corner of [0,1,3,2]){
   if(epoch!==stackEpoch||!stack.isConnected)return;
   const piece=stack.querySelector(`[data-corner="${corner}"]`),img=piece?.querySelector('img');if(!img)continue;
   img.src=img.dataset.stackSrc;const ok=await loaded(img);
   if(epoch!==stackEpoch||!stack.isConnected)return;
   if(ok){try{await img.decode();}catch{}piece.classList.add('is-photo-ready');await delay(reduced?0:200);}
  }
 }
 document.addEventListener('xf:before-unmount',()=>{stackEpoch++;});
 document.addEventListener('xf:mounted',revealStack);revealStack();
 const idle=fn=>'requestIdleCallback' in window?requestIdleCallback(fn,{timeout:2500}):setTimeout(fn,1);
 const cacheTtl=30*60*1000;
 function readCache(key){
  try{
   const item=JSON.parse(sessionStorage.getItem(key)||'');
   if(!item||!item.at||Date.now()-item.at>cacheTtl)return null;
   return item.data;
  }catch{return null;}
 }
 function writeCache(key,data){
  try{sessionStorage.setItem(key,JSON.stringify({at:Date.now(),data}));}catch{}
 }
 function paintGithub(root,html){
  const slot=root.querySelector('[data-github-slot]');if(!slot)return;
  slot.innerHTML=html;root.dataset.ready='1';
 }
 async function loadGithub(){
  const root=document.querySelector('[data-github-stats]');if(!root||root.dataset.ready)return;
  const slot=root.querySelector('[data-github-slot]');if(!slot||!root.dataset.endpoint)return;
  const cached=readCache('feng-github-stats');
  if(typeof cached==='string'&&cached){paintGithub(root,cached);return;}
  try{
   const url=new URL(root.dataset.endpoint,location.href);url.searchParams.set('action','feng_github_stats');
   const response=await fetch(url,{credentials:'same-origin'});const data=await response.json();
   if(data.success&&data.data?.html){paintGithub(root,data.data.html);writeCache('feng-github-stats',data.data.html);}
   else slot.innerHTML='<small>GitHub 暂未同步</small>';
  }catch{slot.innerHTML='<small>GitHub 暂未同步</small>';}
 }
 function paintHero(root,data){
  if(!root||!data)return;
  const counts=data.counts||{};
  const heatmap=root.querySelector('.feng-heatmap');
  heatmap?.querySelectorAll('[data-profile-day]').forEach(btn=>{
   const date=btn.dataset.profileDay,count=Number(counts[date]||0),level=Math.min(4,count);
   btn.dataset.level=String(level);btn.disabled=false;
   btn.setAttribute('aria-label',date+'，'+count+' 条记录');
   btn.title=date+' · '+count+' 条记录';
  });
  heatmap?.removeAttribute('aria-busy');
  const badges=root.querySelector('[data-hero-badges]');
  if(badges&&data.badges)badges.innerHTML=data.badges;
  const visitors=root.querySelector('[data-hero-visitors]');
  if(visitors&&typeof data.visitors==='string')visitors.innerHTML=data.visitors;
  root.dataset.ready='1';
 }
 async function loadHeroActivity(){
  const root=document.querySelector('[data-hero-activity]');if(!root||root.dataset.ready||!root.dataset.endpoint)return;
  const cached=readCache('feng-hero-activity');
  if(cached&&cached.counts){paintHero(root,cached);return;}
  try{
   const url=new URL(root.dataset.endpoint,location.href);url.searchParams.set('action','feng_hero_activity');
   const response=await fetch(url,{credentials:'same-origin'});const json=await response.json();
   if(json.success&&json.data){paintHero(root,json.data);writeCache('feng-hero-activity',json.data);}
   else root.querySelector('.feng-heatmap')?.removeAttribute('aria-busy');
  }catch{root.querySelector('.feng-heatmap')?.removeAttribute('aria-busy');}
 }
 function showHeroIdle(){
  if(document.querySelector('[data-github-stats]:not([data-ready])'))idle(loadGithub);
  if(document.querySelector('[data-hero-activity]:not([data-ready])'))idle(loadHeroActivity);
 }
 function mountHomeComments(){
  const root=document.querySelector('[data-home-comments]');
  if(!root||root.dataset.ready)return;
  const track=root.querySelector('[data-home-comments-track]');
  const group=track?.querySelector('.feng-home-comments__group');
  root.dataset.ready='1';
  if(!track||!group||root.classList.contains('is-static')||matchMedia('(prefers-reduced-motion:reduce)').matches)return;
  const seconds=Math.max(28,Math.round(group.scrollWidth/34));
  track.style.setProperty('--feng-comment-marquee',seconds+'s');
 }
 showHeroIdle();
 mountHomeComments();
 document.addEventListener('xf:mounted',()=>{showHeroIdle();mountHomeComments();});
 document.addEventListener('click',e=>{
  const hero=e.target.closest('.feng-profile-hero');if(!hero)return;
  const open=e.target.closest('[data-profile-open]'), day=e.target.closest('[data-profile-day]'), photo=e.target.closest('[data-profile-photo]'), close=e.target.closest('[data-profile-close]');
  if(open||day){opener=open||day;const dialog=hero.querySelector(day?'[data-profile-calendar-dialog]':'[data-profile-dialog]');
   if(day){
    const date=day.dataset.profileDay,list=dialog.querySelector('[data-profile-records]'),empty=dialog.querySelector('[data-profile-empty]');
    dialog.querySelector('[data-profile-date]').textContent=date;list.hidden=true;list.innerHTML='';empty.hidden=true;
    dialog.showModal();
    if(!Number(day.dataset.level)){empty.hidden=false;return;}
    const cached=readCache('feng-heatmap-day:'+date);
    if(typeof cached==='string'&&cached){list.innerHTML=cached;list.hidden=false;return;}
    const endpoint=day.closest('[data-hero-activity]')?.dataset.endpoint;if(!endpoint){empty.hidden=false;return;}
    const url=new URL(endpoint,location.href);url.searchParams.set('action','feng_heatmap_day');url.searchParams.set('date',date);
    list.setAttribute('aria-busy','true');
    fetch(url,{credentials:'same-origin'}).then(r=>r.json()).then(data=>{
     if(!dialog.open||dialog.querySelector('[data-profile-date]').textContent!==date)return;
     if(data.success&&data.data?.html){list.innerHTML=data.data.html;list.hidden=false;empty.hidden=true;writeCache('feng-heatmap-day:'+date,data.data.html);}
     else{empty.hidden=false;}
    }).catch(()=>{if(dialog.open)empty.hidden=false;}).finally(()=>list.removeAttribute('aria-busy'));
    return;
   }
   dialog.showModal();
  }
  if(photo){const img=hero.querySelector('[data-profile-large]');img.src=photo.dataset.profilePhoto;img.hidden=false;}
  if(close){close.closest('dialog').close();opener?.focus({preventScroll:true});}
  if(e.target.matches('dialog')){const r=e.target.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)e.target.close();}
 });
 document.addEventListener('xf:before-unmount',()=>document.querySelectorAll('.feng-profile-dialog[open]').forEach(d=>d.close()));
})();

