/* Progressive enhancement of native WordPress menu lists. */
(() => {
  const fengIcons = JSON.parse(document.getElementById('feng-config')?.textContent || '{}').icons || {};
  let cleanup = () => {};
  function mount(view) {
    cleanup();
    const controller = new AbortController(), options = {signal:controller.signal};
    const items = [], nav = view?.querySelector('.xf-header .xf-nav');
    if (!nav) return;
    const closeAll = except => items.forEach(item => { if(item !== except) item.close(); });
    nav.querySelectorAll('.xf-menu > li').forEach((li,index) => {
      const list = li.querySelector(':scope > .sub-menu'), link = li.querySelector(':scope > a');
      if (!list || !link) return;
      li.classList.add('feng-nav-parent');
      const popup = document.createElement('div');
      popup.className = 'feng-nav-popover'; popup.id = `feng-nav-popover-${index}`;
      popup.dataset.columns = list.children.length > 4 ? '2' : '1';
      popup.inert = true; popup.setAttribute('aria-hidden','true');
      popup.innerHTML = '<svg class="feng-nav-pointer" viewBox="0 0 28 14" aria-hidden="true"><path d="M0 14 C7 14 8 1 14 1 S21 14 28 14"/></svg>';
      list.before(popup); popup.append(list);
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'feng-nav-toggle';
      button.setAttribute('aria-label',`展开${link.textContent.trim()}子菜单`);
      button.setAttribute('aria-expanded','false'); button.setAttribute('aria-controls',popup.id);
      button.innerHTML = fengIcons['chevron-down'];
      link.after(button);
      let timer, pinned = false;
      const position = () => {
        const itemRect = li.getBoundingClientRect(), anchor = link.getBoundingClientRect();
        // offsetWidth ignores the entrance scale, so the notch stays on its anchor.
        const width = popup.offsetWidth, center = anchor.left + anchor.width/2;
        const left = Math.max(16,Math.min(center-width/2,document.documentElement.clientWidth-width-16));
        const arrow = Math.max(14,Math.min(center-left-14,width-42));
        popup.style.left = `${left-itemRect.left}px`;
        popup.style.setProperty('--feng-nav-arrow',`${arrow}px`);
        popup.style.setProperty('--feng-nav-origin',`${arrow+14}px`);
      };
      const close = () => {
        clearTimeout(timer); pinned = false; li.removeAttribute('data-feng-nav-open');
        popup.inert = true; popup.setAttribute('aria-hidden','true'); button.setAttribute('aria-expanded','false');
      };
      const item = {close,position,li}; items.push(item);
      const open = () => {
        clearTimeout(timer); closeAll(item); position();
        li.setAttribute('data-feng-nav-open',''); popup.inert = false;
        popup.setAttribute('aria-hidden','false'); button.setAttribute('aria-expanded','true');
      };
      button.addEventListener('click',() => {
        if(pinned) close(); else {open(); pinned = true;}
      },options);
      li.addEventListener('pointerenter',event => { if(event.pointerType==='mouse') open(); },options);
      li.addEventListener('pointerleave',event => {
        if(event.pointerType==='mouse' && !li.contains(document.activeElement)) timer=setTimeout(close,200);
      },options);
      li.addEventListener('focusin',event => { if(popup.contains(event.target)) open(); },options);
      li.addEventListener('focusout',event => { if(!li.contains(event.relatedTarget)) close(); },options);
      li.addEventListener('keydown',event => {
        if(event.key==='Escape' && li.hasAttribute('data-feng-nav-open')) {
          event.preventDefault(); event.stopPropagation(); close(); button.focus();
        } else if(event.key==='ArrowDown' && (event.target===link || event.target===button)) {
          event.preventDefault(); open(); list.querySelector('a')?.focus();
        }
      },options);
      controller.signal.addEventListener('abort',() => clearTimeout(timer),{once:true});
    });
    document.addEventListener('pointerdown',event => { if(!nav.contains(event.target)) closeAll(); },options);
    const reposition = () => items.forEach(item => {if(item.li.hasAttribute('data-feng-nav-open')) item.position();});
    window.addEventListener('resize',reposition,options);
    window.addEventListener('scroll',reposition,{...options,passive:true});
    view.querySelector('.xf-header')?.addEventListener('transitionend',reposition,options);
    cleanup = () => {closeAll(); controller.abort();};
  }
  document.addEventListener('xf:mounted',event => mount(event.detail.view));
  document.addEventListener('xf:before-unmount',() => cleanup());
  mount(document.querySelector('#xf-view'));
})();

/* Blue glider adapted from the radio indicator by Uiverse.io / Smit-Prajapati. */
(() => {
 let dispose=()=>{};
 function mountGlider(){
  const nav=document.querySelector('.xf-header .xf-nav');if(!nav)return;
  dispose();
  let glider=nav.querySelector('.feng-menu-glider');
  if(!glider){
   glider=document.createElement('span');glider.className='feng-menu-glider';glider.setAttribute('aria-hidden','true');nav.append(glider);
  }
  const controller=new AbortController(),options={signal:controller.signal};let selected=null;
  const links=()=>[...nav.querySelectorAll('.xf-menu>li>a')];
  const current=()=>nav.querySelector('.xf-menu>.current-menu-item>a,.xf-menu>.current-menu-parent>a,.xf-menu>.current-menu-ancestor>a')||links().find(a=>new URL(a.href,location.href).pathname===location.pathname);
  function move(link){selected=link;links().forEach(a=>a.classList.toggle('is-glider-active',a===link));if(!link){glider.style.opacity='0';return;}const r=link.getBoundingClientRect(),n=nav.getBoundingClientRect();glider.style.width=r.width+'px';glider.style.height=r.height+'px';glider.style.transform=`translate(${r.left-n.left}px,${r.top-n.top}px)`;glider.style.opacity='1';}
  nav.addEventListener('pointerover',e=>{const link=e.target.closest('.xf-menu>li>a');if(link)move(link);},options);
  nav.addEventListener('pointerleave',()=>move(current()),options);
  nav.addEventListener('focusin',e=>{const link=e.target.closest('.xf-menu>li>a');if(link)move(link);},options);
  nav.addEventListener('focusout',e=>{if(!nav.contains(e.relatedTarget))move(current());},options);
  const observer=new ResizeObserver(()=>move(selected?.isConnected?selected:current()));observer.observe(nav);links().forEach(a=>observer.observe(a));
  move(current());requestAnimationFrame(()=>glider.classList.add('is-ready'));
  dispose=()=>{controller.abort();observer.disconnect();links().forEach(a=>a.classList.remove('is-glider-active'));};
 }
 document.addEventListener('xf:mounted',mountGlider);mountGlider();
})();
