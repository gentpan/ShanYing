/* FENG public dashboard. Keeps the document and wallpaper alive. */
(() => {
 'use strict';
 let active = null;
 let loading = null;
 const reduced = matchMedia('(prefers-reduced-motion: reduce)');
 const button = () => document.querySelector('[data-feng-dashboard-toggle]');
 function setButtonState(expanded) {
  const trigger = button();
  if (!trigger) return;
  trigger.setAttribute('aria-expanded', String(expanded));
  trigger.setAttribute('aria-label', expanded ? '收起控制面板' : '打开控制面板');
  trigger.querySelector('.t-icon-swap')?.setAttribute('data-state', expanded ? 'b' : 'a');
 }
 function close(restore = true) {
  if (!active) return;
  loading?.abort(); loading = null;
  active.hidden = true;
  document.body.classList.remove('feng-dashboard-open');
  document.body.style.removeProperty('overflow');
  setButtonState(false);
  active = null;
  document.querySelector('.xf-skip-link')?.setAttribute('href','#xf-content');
  if (restore) button()?.focus({preventScroll:true});
 }
 async function load(part = '', month = '') {
  const panel = active;
  if (!panel) return;
  loading?.abort();
  const controller = new AbortController(); loading = controller;
  const target = part === 'calendar' ? panel.querySelector('[data-feng-calendar]') : panel.querySelector('[data-feng-dashboard-content]');
  if (!target) return;
  target.setAttribute('aria-busy', 'true');
  if (!part) target.innerHTML = '<p class="feng-dashboard-loading" role="status">正在翻开站点的近况…</p>';
  const url = new URL(panel.dataset.endpoint, location.href);
  url.searchParams.set('action', 'feng_dashboard');
  if (part) { url.searchParams.set('part', part); url.searchParams.set('month', month); }
  const timeout = setTimeout(() => controller.abort('timeout'), 12000);
  try {
   const response = await fetch(url, {signal:controller.signal, credentials:'same-origin', cache:'no-store'});
   if (!response.ok) throw new Error('Request failed');
   const result = await response.json();
   if (!result.success || typeof result.data?.html !== 'string') throw new Error('Invalid response');
   if (active !== panel || controller.signal.aborted) return;
   target.innerHTML = result.data.html;
   if(!part){const summary=target.querySelector('[data-dashboard-summary]'),slot=panel.querySelector('[data-dashboard-summary-slot]');if(summary&&slot)slot.replaceChildren(summary);}

   if (part) target.querySelector('.feng-calendar-head h3')?.setAttribute('tabindex','-1');
   if (part) target.querySelector('.feng-calendar-head h3')?.focus({preventScroll:true});
  } catch (error) {
   if (controller.signal.aborted && controller.signal.reason !== 'timeout') return;
   if (active === panel) target.innerHTML = '<p class="feng-dashboard-empty" role="status">暂时没有加载成功。<button type="button" data-feng-dashboard-retry>重新加载</button></p>';
  } finally {
   clearTimeout(timeout);
   if (loading === controller) { target.removeAttribute('aria-busy'); loading = null; }
  }
 }
 function open() {
  const panel = document.querySelector('#feng-dashboard');
  if (!panel) return;
  active = panel;
  document.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
  document.body.classList.add('feng-dashboard-open');
  document.body.style.overflow = 'hidden';
  panel.hidden = false;
  document.querySelector('.xf-skip-link')?.setAttribute('href','#feng-dashboard-title');
  setButtonState(true);
  panel.querySelector('[data-feng-dashboard-close]')?.focus({preventScroll:true});
  if (!reduced.matches) panel.animate([{opacity:0},{opacity:1}],{duration:280,easing:'ease'});
  load();
 }
 document.addEventListener('click', event => {
  if (!(event.target instanceof Element)) return;
  if (event.target.closest('[data-feng-dashboard-close]')) { close(); return; }
  if (event.target.closest('[data-feng-dashboard-toggle]')) { active ? close() : open(); return; }
  if (event.target.closest('[data-feng-dashboard-retry]')) { load(); return; }
  const tab=event.target.closest('[data-dashboard-page]');
  if(tab&&active){active.querySelectorAll('[data-dashboard-page]').forEach(b=>b.setAttribute('aria-pressed',String(b===tab)));active.querySelectorAll('[data-dashboard-view]').forEach(v=>v.hidden=v.dataset.dashboardView!==tab.dataset.dashboardPage);return;}
  const month = event.target.closest('[data-feng-month]');
  if (month) load('calendar',month.dataset.fengMonth);
 });
 document.addEventListener('keydown', event => {
  if (!event.defaultPrevented && event.key === 'Escape' && active && !document.querySelector('dialog[open]')) { event.preventDefault(); close(); }
 });
 window.addEventListener('popstate', () => close(false));
 window.fengDashboard = {close};
})();
