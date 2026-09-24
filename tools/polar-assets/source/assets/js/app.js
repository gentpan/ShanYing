/* FENG — independent progressive navigation and page components. */
(() => {
  'use strict';
  const fengIcons = JSON.parse(document.getElementById('feng-config')?.textContent || '{}').icons || {};
  const root = document.documentElement;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const dark = matchMedia('(prefers-color-scheme: dark)');
  const status = document.querySelector('[data-xf-status]');
  const progress = document.querySelector('[data-xf-progress]');
  let pageCleanup = () => {};
  let request = null;
  let navigationId = 0;
  let committedURL = location.href;
  const announce = text => { if (status) status.textContent = text; };
  const storedTheme = (() => { try { return localStorage.getItem('xf-theme'); } catch { return null; } })();
  const configuredScheme=document.querySelector('meta[name="feng-scheme"]')?.content;
  const fixedScheme=['light','dark'].includes(configuredScheme)?configuredScheme:null;
  let themePreference = fixedScheme || (['light','dark','system'].includes(storedTheme) ? storedTheme : 'system');
  let manualTheme=false;
  try{manualTheme=localStorage.getItem('xf-theme-manual')==='1'&&['light','dark'].includes(storedTheme);}catch{}
  if(manualTheme)themePreference=storedTheme;
  let visitorWeather=null;
  try{const cached=JSON.parse(localStorage.getItem('polar-visitor-weather')||sessionStorage.getItem('polar-visitor-weather'));if(cached&&Date.now()-cached.at<3600000)visitorWeather=cached.weather;}catch{}
  function isVisitorNight(){
    // All pages resolve the visitor's solar clock; the Hero module is home-only.
    const now=new Date(),seconds=now.getTime()/1000,w=visitorWeather;
    const offset=Number.isFinite(w?.utc_offset)?w.utc_offset:null;
    const hour=offset===null?now.getHours()+now.getMinutes()/60:((seconds+offset)%86400+86400)%86400/3600;
    const rises=w?.sunrise||[],sets=w?.sunset||[],day=Math.floor((seconds+(offset||0))/86400);
    for(let i=0;i<rises.length;i++){
      if(rises[i]>0&&sets[i]>rises[i]&&Math.floor((rises[i]+(offset||0))/86400)===day)return seconds<rises[i]||seconds>=sets[i];
    }
    if(w&&rises.length&&typeof w.day==='boolean')return !w.day;
    return hour<6||hour>=18;
  }
  document.addEventListener('polar:visitor-weather',event=>{
    visitorWeather=event.detail;
    try{const cached=JSON.stringify({at:Date.now(),weather:visitorWeather});sessionStorage.setItem('polar-visitor-weather',cached);localStorage.setItem('polar-visitor-weather',cached);}catch{}
    themeButtons();
  });
  setInterval(()=>{if(!document.hidden)themeButtons();},60000);
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)themeButtons();});
  function themeButtons() {
    if(fixedScheme&&!manualTheme)themePreference=fixedScheme;
    const night=isVisitorNight();
    root.dataset.xfNight=String(night);
    const autoNight=night&&!manualTheme;
    root.dataset.xfTheme = autoNight?'dark':(themePreference === 'system' ? (dark.matches ? 'dark' : 'light') : themePreference);
    const names={system:'跟随系统',light:'浅色模式',dark:'深色模式'};
    const next={system:'light',light:'dark',dark:'system'};
    document.querySelectorAll('[data-xf-theme-toggle]').forEach(b => {
      b.dataset.mode=autoNight?'dark':themePreference;b.removeAttribute('aria-pressed');
      b.setAttribute('aria-label',names[themePreference]+'，点击切换为'+names[next[themePreference]]);
      b.title=autoNight?'夜间自动深色，点击切换浅色':names[themePreference];
      if(autoNight)b.setAttribute('aria-label',b.title);
      b.removeAttribute('aria-disabled');
    });
  }
  function setHeaderSearch(open, restoreFocus = false) {
    const host = document.querySelector('.feng-header-search');
    if (!host) return;
    const form = host.querySelector('form');
    const button = host.querySelector('[data-xf-search-open]');
    host.toggleAttribute('data-open', open);
    form.inert = !open;
    form.setAttribute('aria-hidden', String(!open));
    button.setAttribute('aria-expanded', String(open));
    button.setAttribute('aria-label', open ? '收起搜索' : '打开搜索');
    if (open) form.querySelector('input').focus({preventScroll:true});
    else if (restoreFocus) button.focus({preventScroll:true});
  }
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.querySelector('.feng-header-search[data-open]')) {
      event.preventDefault(); setHeaderSearch(false, true);
    }
  });
  dark.addEventListener('change', themeButtons);
  let commentSuccessTimer;
  function showCommentSuccess(text) {
    document.dispatchEvent(new CustomEvent('feng:comment-added'));
    let notice = document.querySelector('[data-feng-comment-success]');
    if (!notice) {
      notice = document.createElement('div');
      notice.className = 'feng-comment-success';
      notice.setAttribute('data-feng-comment-success', '');
      // The form status and persistent live region already announce the result.
      notice.setAttribute('aria-hidden', 'true');
      document.body.append(notice);
    }
    clearTimeout(commentSuccessTimer);
    notice.innerHTML = '<span class="feng-success-check" data-state="out">' + fengIcons.check + '</span><span class="feng-comment-success__text"></span>';
    notice.querySelector('.feng-comment-success__text').textContent = text;
    notice.hidden = false;
    requestAnimationFrame(() => notice.querySelector('.feng-success-check').setAttribute('data-state', 'in'));
    commentSuccessTimer = setTimeout(() => { notice.hidden = true; }, 3600);
  }
  const cardColorRequests = new Map();
  let cardColorActive = 0;
  const cardColorQueue = [];
  function requestCardColor(url) {
    if (cardColorRequests.has(url)) return cardColorRequests.get(url);
    const promise = new Promise(resolve => {
      const run = async () => {
        cardColorActive++;
        const abort = new AbortController();
        const timer = setTimeout(() => abort.abort(), 10000);
        try {
          const response = await fetch(url, {signal:abort.signal, credentials:'same-origin'});
          const result = response.ok ? await response.json() : null;
          const color = result?.success && /^#[0-9a-f]{6}$/i.test(result.data?.color) ? result.data.color : null;
          resolve(color);
        } catch { resolve(null); }
        finally { clearTimeout(timer); cardColorActive--; cardColorQueue.shift()?.(); }
      };
      if (cardColorActive < 2) run(); else cardColorQueue.push(run);
    });
    cardColorRequests.set(url, promise);
    return promise;
  }
  function mount(view) {
    const controller = new AbortController();
    const options = { signal: controller.signal };
    let observer;
    let mediaObserver;
    let emojiObserver;
    const reveal = el => {
      el.removeAttribute('data-xf-pending');
      observer?.unobserve(el);
    };
    const hydrate = el => {
      const template = el.querySelector('template');
      if (!template) return;
      template.replaceWith(template.content.cloneNode(true));
      const host = el.closest('[data-xf-lazy-host]') || el;
      const img = el.querySelector('img');
      const ready = () => {
        host.setAttribute('data-xf-loaded', '');
        const card = host.closest('[data-feng-color-url]');
        if (card && !card.hasAttribute('data-feng-color-ready') && img?.naturalWidth) {
          card.setAttribute('data-feng-color-ready', '');
          requestCardColor(card.dataset.fengColorUrl).then(color => {
            if (color && card.isConnected && !controller.signal.aborted) card.style.setProperty('--feng-card-tone',color);
          });
        }
      };
      if (!img || img.complete) ready();
      else { img.addEventListener('load', ready, { ...options, once: true }); img.addEventListener('error', ready, { ...options, once: true }); }
      mediaObserver?.unobserve(host);
    };
    const lazyImages = [...view.querySelectorAll('[data-xf-lazy-image]')];
    const revealItems = [...view.querySelectorAll('[data-xf-reveal]')];
    if ('IntersectionObserver' in window) {
      mediaObserver = new IntersectionObserver(entries => entries.forEach(entry => {
        if (entry.isIntersecting) hydrate(entry.target);
      }), { rootMargin: '220px 0px', threshold: 0 });
      // Observe the reserved thumbnail rectangle, not a dimensionless inner span.
      lazyImages.forEach(el => {
        const host = el.closest('.feng-recent-row__image,.feng-cover-card__image,.xf-note-card__thumb,.xf-story__image,.feng-friend__avatar') || el;
        host.dataset.xfLazyHost = '';
        mediaObserver.observe(host);
      });
      if (!reduced.matches && document.querySelector('meta[name="feng-reveal"]')?.content !== 'off') {
        observer = new IntersectionObserver(entries => {
          const arriving = entries.filter(entry => entry.isIntersecting)
            .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top || a.boundingClientRect.left - b.boundingClientRect.left);
          arriving.forEach((entry, index) => {
            const el = entry.target;
            if(el.classList.contains('feng-cover-card')) {
              el.style.removeProperty('--feng-card-enter-delay');
            } else {
              el.style.setProperty('--xf-reveal-delay', `${Math.min(index, 3) * 60}ms`);
              el.addEventListener('transitionend', () => el.style.removeProperty('--xf-reveal-delay'), { ...options, once: true });
            }
            reveal(el);
          });
        }, { rootMargin: '0px 0px -28px 0px', threshold: 0 });
        revealItems.forEach(el => { el.dataset.xfPending = ''; observer.observe(el); });
      }
    } else lazyImages.forEach(hydrate);
    // Fetch only the requested three-card group; returning to a group uses its cache.
    view.querySelectorAll('[data-feng-collection]').forEach(section => {
      const viewport = section.querySelector('.feng-collection-window');
      const controls = section.querySelector('.feng-collection-controls');
      const previous = controls.querySelector('[data-feng-slide="-1"]');
      const next = controls.querySelector('[data-feng-slide="1"]');
      const counter = controls.querySelector('.feng-collection-count');
      const status = section.querySelector('[data-feng-slide-status]');
      let current = 1, pages = Number(section.dataset.pages), busy = false;
      const cache = new Map([[1, viewport.firstElementChild.innerHTML]]);
      controls.hidden = pages <= 1;
      const sync = () => {
        previous.disabled = busy || current <= 1;
        next.disabled = busy || current >= pages;
        counter.textContent = `${current} / ${pages}`;
      };
      controls.addEventListener('click', async event => {
        const button = event.target.closest('[data-feng-slide]');
        if (!button || busy || button.disabled) return;
        const direction = Number(button.dataset.fengSlide), target = current + direction;
        if (target < 1 || target > pages) return;
        busy = true; sync(); viewport.setAttribute('aria-busy','true');
        status.textContent = '正在加载文章…';
        const requestController = new AbortController();
        const abort = () => requestController.abort();
        controller.signal.addEventListener('abort',abort,{once:true});
        const timer = setTimeout(abort,10000);
        try {
          if (!cache.has(target)) {
            const url = new URL(section.dataset.endpoint); url.searchParams.set('page',target);
            const response = await fetch(url,{signal:requestController.signal,credentials:'same-origin'});
            const result = await response.json();
            if (!response.ok || !result.success || typeof result.data?.html !== 'string') throw new Error('load');
            pages = result.data.pages; cache.set(target,result.data.html);
          }
          if (controller.signal.aborted) return;
          const old = viewport.firstElementChild;
          const incoming = old.cloneNode(false);
          incoming.innerHTML = cache.get(target);
          incoming.inert = true;
          incoming.querySelectorAll('[data-xf-pending]').forEach(reveal);
          viewport.append(incoming);
          incoming.querySelectorAll('[data-xf-lazy-image]').forEach(el => {
            const host = el.closest('.feng-cover-card__image') || el;
            host.dataset.xfLazyHost = ''; hydrate(host);
          });
          // Both panels occupy one grid cell; the viewport and page stay still.
          old.inert = true;
          if (!reduced.matches && incoming.animate) {
            const timing = {duration:1000,easing:'cubic-bezier(.4,0,.2,1)'};
            const animations = [
              old.animate([{transform:'translateX(0)',opacity:1},{transform:`translateX(${-direction*100}%)`,opacity:.25}],timing),
              incoming.animate([{transform:`translateX(${direction*100}%)`,opacity:.25},{transform:'translateX(0)',opacity:1}],timing)
            ];
            const cancel = () => animations.forEach(animation => animation.cancel());
            controller.signal.addEventListener('abort',cancel,{once:true});
            await Promise.allSettled(animations.map(animation => animation.finished));
            controller.signal.removeEventListener('abort',cancel);
          }
          old.querySelectorAll('[data-xf-lazy-host]').forEach(el => mediaObserver?.unobserve(el));
          old.querySelectorAll('[data-xf-reveal]').forEach(el => observer?.unobserve(el));
          old.remove(); incoming.inert = false; current = target;
          status.textContent = `第 ${current} 组，共 ${pages} 组文章。`;
        } catch (error) {
          if (!controller.signal.aborted) status.textContent = '文章加载失败，请再点击箭头重试。';
        } finally {
          clearTimeout(timer); controller.signal.removeEventListener('abort',abort);
          busy = false; viewport.removeAttribute('aria-busy'); sync();
        }
      }, options);
    });
    window.addEventListener('beforeprint', () => { lazyImages.forEach(hydrate); revealItems.forEach(reveal); }, options);
    // Keyboard focus must never land on an invisible card. Reveal any pending ancestors too.
    view.addEventListener('focusin', event => {
      let el = event.target;
      while (el && el !== view) {
        if (el.hasAttribute('data-xf-pending')) { el.style.removeProperty('--xf-reveal-delay'); reveal(el); }
        el = el.parentElement;
      }
    }, options);
    reduced.addEventListener('change', () => {
      if (reduced.matches) { revealItems.forEach(reveal); observer?.disconnect(); }
    }, options);
    view.querySelectorAll('[data-xf-media]').forEach(media => {
      const img = media.querySelector('img');
      if (!img) return;
      const update = () => { media.dataset.xfState = img.naturalWidth ? 'ready' : 'error'; };
      if (img.complete) update();
      else { media.dataset.xfState = 'loading'; img.addEventListener('load', update, options); img.addEventListener('error', update, options); }
    });
    view.querySelectorAll('.xf-prose img, .xf-media--cover img').forEach(img => {
      if (img.closest('a,button')) return;
      img.dataset.xfZoom = '';
      img.tabIndex = 0;
      img.setAttribute('role', 'button');
      img.setAttribute('aria-label', `放大图片${img.alt ? '：' + img.alt : ''}`);
      img.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); img.click(); }
      }, options);
    });
    // Keep short quotes in one column; longer plain quotes may balance across two.
    view.querySelectorAll('.xf-prose blockquote').forEach(quote => {
      const simple = [...quote.children].every(child => ['P', 'CITE'].includes(child.tagName));
      const text = [...quote.querySelectorAll(':scope > p')].map(p => p.textContent).join('');
      quote.classList.toggle('feng-quote--columns', simple && text.trim().length > 170);
    });
    view.querySelectorAll('pre > code').forEach(code => {
      const button = document.createElement('button');
      button.type = 'button'; button.className = 'xf-copy'; button.innerHTML = '<span data-lordicon-content="copy" aria-hidden="true"><i class="fa-regular fa-copy"></i></span><span data-copy-label>复制</span>'; const label=button.querySelector('[data-copy-label]'); button.setAttribute('aria-live','polite');
      button.addEventListener('click', async () => {
        try {
          // Run the synchronous fallback inside the click gesture, before any await.
          const field=document.createElement('textarea');field.value=code.textContent;field.readOnly=true;
          field.style.cssText='position:fixed;left:0;top:0;width:2px;height:2px;padding:0;border:0;opacity:0;font-size:16px';document.body.append(field);
          let copied=false;
          try {field.focus({preventScroll:true});field.select();field.setSelectionRange(0,field.value.length);copied=document.execCommand('copy');}catch{}finally{field.remove();button.focus({preventScroll:true});}
          if(!copied){
            if(!navigator.clipboard?.writeText)throw new Error('Clipboard unavailable');
            await navigator.clipboard.writeText(code.textContent);
          }
          label.textContent='复制成功';button.classList.add('is-copied');window.fengToast?.('复制成功');
          setTimeout(()=>{if(button.isConnected){label.textContent='复制';button.classList.remove('is-copied');}},2500);
        }
        catch { label.textContent = '请手动选择复制'; window.fengToast?.('复制失败，请手动选择复制','error'); }
      }, options);
      code.parentElement.append(button);
    });
    view.querySelectorAll('[data-feng-share]').forEach(share => {
      share.hidden = false;
      const toggle = share.querySelector('[data-feng-share-toggle]');
      const menu = share.querySelector('.feng-share-options');
      const feedback = share.querySelector('.feng-share-feedback');
      const setOpen = (open, restoreFocus = false) => {
        share.toggleAttribute('data-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? '收起分享' : '展开分享');
        menu.inert = !open;
        menu.setAttribute('aria-hidden', String(!open));
        if (open) feedback.hidden = true;
        if (restoreFocus) toggle.focus({preventScroll:true});
      };
      toggle.addEventListener('click', () => setOpen(!share.hasAttribute('data-open')), options);
      document.addEventListener('pointerdown', event => {
        if (!share.contains(event.target)) { setOpen(false); feedback.hidden = true; }
      }, options);
      share.addEventListener('focusout', event => {
        if (event.relatedTarget && !share.contains(event.relatedTarget)) { setOpen(false); feedback.hidden = true; }
      }, options);
      share.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false, true); feedback.hidden = true; }
      }, options);
      share.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false, true), options));
      share.querySelector('[data-feng-wechat]').addEventListener('click', async () => {
        setOpen(false, true);
        try {
          await navigator.clipboard.writeText(share.dataset.fengShare);
          if (controller.signal.aborted) return;
          feedback.querySelector('p').textContent = '链接已复制，打开微信粘贴分享。';
          feedback.querySelector('label').hidden = true;
        } catch {
          if (controller.signal.aborted) return;
          feedback.querySelector('p').textContent = '复制下方链接，到微信粘贴分享。';
          feedback.querySelector('label').hidden = false;
        }
        feedback.hidden = false;
        if (!feedback.querySelector('label').hidden) {
          feedback.querySelector('input').focus({preventScroll:true}); feedback.querySelector('input').select();
        }
      }, options);
    });
    const commentForm = view.querySelector('#commentform');
    if (commentForm) {
      // Animate a visual copy; native labels and placeholder attributes remain
      // intact for assistive technology, autofill and the no-JavaScript form.
      commentForm.querySelectorAll('#comment[placeholder],#author[placeholder],#email[placeholder],#url[placeholder]').forEach(field => {
        const host = field.parentElement;
        if (host.querySelector(':scope > .feng-placeholder-window')) return;
        const clip = document.createElement('span');
        clip.className = 'feng-placeholder-window';
        clip.setAttribute('aria-hidden', 'true');
        const label = document.createElement('span');
        label.className = 'feng-placeholder-label';
        label.textContent = field.getAttribute('placeholder');
        clip.append(label);
        host.append(clip);
        host.classList.add('feng-floating-field');
      });
      // Restore empty hints when switching fields, but keep them raised during
      // the temporary trip to an emoji button and back to the editor.
      const releaseHints = () => commentForm.querySelectorAll('[data-feng-engaged]').forEach(field => field.removeAttribute('data-feng-engaged'));
      commentForm.addEventListener('focusin', event => {
        if (event.target.matches('#comment,#author,#email,#url')) {
          const fromEmoji = event.relatedTarget instanceof Element && event.relatedTarget.closest('[data-feng-emojis]');
          if (!fromEmoji) {
            commentForm.querySelectorAll('[data-feng-engaged]').forEach(host => {
              if (host !== event.target.parentElement) host.removeAttribute('data-feng-engaged');
            });
          }
          event.target.parentElement.setAttribute('data-feng-engaged', '');
        }
      }, options);
      commentForm.addEventListener('focusout', event => {
        if (event.relatedTarget && !commentForm.contains(event.relatedTarget)) releaseHints();
      }, options);
      document.addEventListener('pointerdown', event => {
        if (!commentForm.contains(event.target)) releaseHints();
      }, options);
      const textarea = commentForm.querySelector('#comment');
      const resizeComment = () => {
        if (!textarea || !textarea.clientHeight) return;
        // Only grow when content overflows. scrollHeight excludes borders; resetting
        // to auto on every input shrinks the border-box and discards manual resizing.
        if (textarea.scrollHeight > textarea.clientHeight) {
          const style = getComputedStyle(textarea);
          const border = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
          textarea.style.height = `${Math.max(textarea.getBoundingClientRect().height, textarea.scrollHeight + border)}px`;
        }
      };
      textarea?.addEventListener('input', resizeComment, options);
      window.addEventListener('resize', resizeComment, options);
      const emojiBar = commentForm.querySelector('[data-feng-emojis]');
      if (emojiBar && textarea) {
        emojiBar.hidden = false;
        emojiBar.querySelectorAll('[data-feng-emoji]').forEach(button => {
          button.addEventListener('click', () => {
            textarea.setRangeText(button.dataset.fengEmoji, textarea.selectionStart, textarea.selectionEnd, 'end');
            textarea.focus({ preventScroll: true }); textarea.dispatchEvent(new Event('input', { bubbles: true }));
          }, options);
        });
        const more = emojiBar.querySelector('[data-feng-emoji-more]');
        const palette = emojiBar.querySelector('.feng-emoji-options');
        const buttons = [...palette.querySelectorAll('[data-feng-emoji]')];
        let expanded = false;
        let columns = 0;
        const layoutEmojis = () => {
          columns = Math.max(1, Math.floor((palette.clientWidth + 2) / 34));

          palette.style.setProperty('--feng-emoji-columns', columns);
          buttons.forEach((button, i) => { button.hidden = i >= columns * (expanded ? 3 : 1); });
          more.hidden = buttons.length <= columns;
          more.setAttribute('aria-expanded', String(expanded));
          more.setAttribute('aria-label', expanded ? '收起表情' : '展开更多表情');
          more.title = expanded ? '收起表情' : '展开更多表情';
          emojiBar.toggleAttribute('data-expanded', expanded);
        };
        more?.addEventListener('click', () => {
          expanded = !expanded;
          layoutEmojis();
        }, options);
        emojiBar.addEventListener('keydown', event => {
          if (event.key === 'Escape' && expanded) {
            event.preventDefault(); expanded = false; layoutEmojis(); more.focus();
          }
        }, options);
        if ('ResizeObserver' in window) {
          emojiObserver = new ResizeObserver(() => {
            const next = Math.max(1, Math.floor((palette.clientWidth + 2) / 34));
            if (next !== columns) layoutEmojis();
          });
          emojiObserver.observe(palette);
        } else window.addEventListener('resize', layoutEmojis, options);
        layoutEmojis();
      }
      const config = commentForm.querySelector('[data-feng-comment-endpoint]');
      const renderSubmittedComment = data => {
        const region = commentForm.closest('#comments');
        if (!region || typeof data.commentsHtml !== 'string') return false;
        const nextList = document.createElement('ol');
        nextList.className = 'xf-comment-list feng-comment-list';
        nextList.innerHTML = data.commentsHtml;
        const added = nextList.querySelector(`#comment-${data.edit?.id}`);
        if (!added) return false;
        if (commentForm.querySelector('[name=comment_parent]')?.value !== '0') region.querySelector('#cancel-comment-reply-link')?.click();
        const oldList = region.querySelector('.feng-comment-list');
        const respond = commentForm.closest('#respond');
        if (respond && oldList?.contains(respond)) region.append(respond);
        if (oldList) oldList.replaceWith(nextList);
        else region.insertBefore(nextList, region.querySelector(':scope > #respond'));
        region.querySelector(':scope > .feng-empty')?.remove();
        const heading = region.querySelector('.feng-group-title h2');
        if (heading) heading.replaceChildren(...(heading.firstElementChild ? [heading.firstElementChild.cloneNode(true)] : []), document.createTextNode(`${data.commentCount} 条评论`));
        document.dispatchEvent(new Event('feng:comments-refreshed'));
        requestAnimationFrame(() => added.scrollIntoView({ block: 'center', behavior: reduced.matches ? 'instant' : 'smooth' }));
        return true;
      };
      if (config) commentForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (commentForm.dataset.sending) return;
        const message = commentForm.querySelector('[data-feng-comment-status]');
        const submit = commentForm.querySelector('[type=submit]');
        const body = new FormData(commentForm); body.set('action', 'feng_comment');
        commentForm.dataset.sending = 'true'; submit.disabled = true;
        message.textContent = '正在发送…'; message.removeAttribute('data-error');
        const previousSuccess = document.querySelector('[data-feng-comment-success]');
        if (previousSuccess) previousSuccess.hidden = true;
        const sending = new AbortController();
        const timeout = setTimeout(() => sending.abort(), 20000);
        try {
          const response = await fetch(config.dataset.fengCommentEndpoint, { method: 'POST', body, credentials: 'same-origin', signal: sending.signal });
          const result = await response.json();
          if (!result.success) throw new Error(result.data?.message || '提交失败，请稍后再试。');
          if (!commentForm.isConnected) return;
          if(result.data.edit)try{sessionStorage.setItem('feng-comment-edit-'+result.data.edit.id,JSON.stringify(result.data.edit));}catch{}
          message.textContent = result.data.message;
          showCommentSuccess(result.data.message);
          if (textarea) textarea.value = '';
          announce(result.data.message);
          let updated = false;
          try { updated = renderSubmittedComment(result.data); } catch { /* preserve the successful submission and use navigation below */ }
          if (!updated && result.data.url && safeURL(result.data.url)) {
            if (document.querySelector('meta[name="xf-navigation"]')?.content === 'on') {await navigate(result.data.url);const anchor=document.getElementById(new URL(result.data.url).hash.slice(1));anchor?.scrollIntoView({block:'center',behavior:reduced.matches?'instant':'smooth'});}
            else {
              // Native navigation needs a short beat for the success stroke to finish.
              await new Promise(resolve => setTimeout(resolve, reduced.matches ? 0 : 650));
              if (commentForm.isConnected) location.assign(result.data.url);
            }
          }
        } catch (error) {
          if (commentForm.isConnected) {
            message.dataset.error = '';
            message.textContent = error instanceof TypeError || error.name === 'AbortError' || error instanceof SyntaxError
              ? '未能确认提交结果。内容已保留，请检查留言区后再试。' : error.message;
          }
        } finally { clearTimeout(timeout); delete commentForm.dataset.sending; submit.disabled = false; }
      }, options);
    }
    syncScrollChrome();
    motionButtons();
    // WordPress comment-reply observes DOM replacement itself; do not initialize it twice.
    themeButtons();
    document.dispatchEvent(new CustomEvent('xf:mounted', { detail: { view } }));
    return () => {
      document.dispatchEvent(new CustomEvent('xf:before-unmount', { detail: { view } }));
      controller.abort(); observer?.disconnect(); mediaObserver?.disconnect(); emojiObserver?.disconnect();
      view.querySelectorAll('audio,video').forEach(media => media.pause());
    };
  }
  function savePosition() {
    if (location.href !== committedURL) return;
    history.replaceState({ ...(history.state || {}), xf: true, x: scrollX, y: scrollY }, '', location.href);
  }
  function safeURL(value) {
    const url = new URL(value, location.href);
    return url.origin === location.origin && ['http:', 'https:'].includes(url.protocol)
      && !/\/(?:wp-admin|wp-login\.php|wp-json|wp-comments-post\.php)(?:\/|$|\?)/.test(url.pathname)
      && !/\.(?:zip|pdf|jpg|jpeg|png|webp|gif|svg|mp[34]|webm|xml|json|txt|css|js)$/i.test(url.pathname)
      && !['preview', 'customize_changeset_uuid', 'rest_route', 'action', '_wpnonce', 'add-to-cart'].some(k => url.searchParams.has(k));
  }
  function pageScript(node) {
    if (!node.src) return /^document\.getElementById\(\s*["']ak_js_1["']/.test(node.textContent.trim());
    const url = new URL(node.src, location.href);
    return /\/(?:wp-includes\/js\/comment-reply(?:\.min)?\.js|assets\/vendor\/highlight\/highlight\.min\.js|assets\/js\/article-media\.js|wp-content\/plugins\/akismet\/_inc\/akismet-frontend\.js)$/.test(url.pathname)
      || url.hostname === 'litezoom.dev' && url.pathname === '/litezoom.min.js'
      || url.hostname === 'api.jieqi.dev' && url.pathname === '/v1/widget.js';
  }
  function scripts(doc) {
    // Known page enhancers can load after a shell swap; other runtime scripts must still match.
    // Core may append its emoji renderer after load; it is not a new page dependency.
    return [...doc.querySelectorAll('script')].filter(s => {
      if (pageScript(s)) return false;
      if (!s.src) return true;
      const url = new URL(s.src, location.href);
      return !(url.origin === location.origin && /\/wp-includes\/js\/wp-emoji-release(?:\.min)?\.js$/.test(url.pathname));
    }).map(s => JSON.stringify([
      s.getAttribute('src'), s.getAttribute('type'), s.getAttribute('integrity'), s.textContent.trim()
    ])).sort().join('\n');
  }
  async function loadPageScripts(doc) {
    const akismetTime = document.getElementById('ak_js_1');
    if (akismetTime) {
      const timestamp = String(Date.now());
      akismetTime.value = timestamp;
      akismetTime.setAttribute('value', timestamp);
    }
    for (const source of doc.querySelectorAll('script[src]')) {
      if (!pageScript(source)) continue;
      const url = new URL(source.src, location.href);
      const akismet = /\/wp-content\/plugins\/akismet\/_inc\/akismet-frontend\.js$/.test(url.pathname);
      if (!akismet && [...document.scripts].some(script => script.src === source.src)) continue;
      await new Promise(resolve => {
        const script = document.createElement('script');
        script.src = source.src;
        script.async = false;
        script.onload = resolve;
        script.onerror = resolve;
        document.body.append(script);
      });
    }
  }
  function unsupported(doc) {
    return doc.querySelector('[data-wp-interactive], [data-xf-native], iframe')
      || [...doc.querySelectorAll('#xf-view form')].some(f => !f.matches('.xf-search,#commentform,.post-password-form,[data-talk-search],[data-talk-form]'));
  }
  function compatible(doc) {
    return doc.querySelector('meta[name="xf-theme"]')?.content === document.querySelector('meta[name="xf-theme"]')?.content
      && doc.querySelector('meta[name="xf-navigation"]')?.content === 'on'
      && doc.querySelector('#xf-view #xf-content')
      && scripts(doc) === scripts(document) && !unsupported(doc) && !unsupported(document);
  }
  async function prepareStyles(doc, signal) {
    const current = [...document.head.querySelectorAll('link[rel="stylesheet"],style')];
    const incoming = [...doc.head.querySelectorAll('link[rel="stylesheet"],style')];
    const added = [];
    // Retain matching nodes to avoid flashing and preserve identical CSS order.
    const keys = nodes => nodes.map(n => n.outerHTML);
    const currentKeys = keys(current);
    const nextKeys = keys(incoming);
    try {
      for (let i = 0; i < incoming.length; i++) {
        if (currentKeys.includes(nextKeys[i])) continue;
        const clone = document.importNode(incoming[i], true);
        added.push(clone);
        // Place new page styles before the next retained stylesheet. Moving a
        // live stylesheet can restart CSS animations on the persistent backdrop.
        const anchor = incoming.slice(i + 1).map(n => current.find(e => e.outerHTML === n.outerHTML && e.isConnected)).find(Boolean) || null;
        if (clone.tagName === 'LINK') {
          await new Promise((resolve, reject) => {
            const abort = () => reject(new DOMException('Aborted', 'AbortError'));
            signal.addEventListener('abort', abort, { once: true });
            clone.onload = () => { signal.removeEventListener('abort', abort); resolve(); };
            clone.onerror = () => { signal.removeEventListener('abort', abort); reject(new Error('Stylesheet failed')); };
            document.head.insertBefore(clone, anchor);
            if (signal.aborted) abort();
          });
        } else document.head.insertBefore(clone, anchor);
      }
      return {
        commit() {
          current.forEach((n, i) => { if (!nextKeys.includes(currentKeys[i])) n.remove(); });
          // Keep already ordered nodes connected and untouched. Only move nodes
          // whose relative stylesheet order actually changed in the target page.
          const selector = 'link[rel="stylesheet"],style';
          let cursor = document.head.querySelector(selector);
          incoming.forEach((n, i) => {
            const existing = [...current, ...added].find(e => e.isConnected && e.outerHTML === nextKeys[i]);
            if (!existing) return;
            if (existing === cursor) {
              cursor = cursor.nextElementSibling;
              while (cursor && !cursor.matches(selector)) cursor = cursor.nextElementSibling;
            } else document.head.insertBefore(existing, cursor);
          });
        },
        cancel() { added.forEach(n => n.remove()); }
      };
    } catch (error) { added.forEach(n => n.remove()); throw error; }
  }
  function syncMetadata(doc) {
    const selector = 'meta[name="description"],meta[name="robots"],meta[property^="og:"],meta[name^="twitter:"],link[rel="canonical"],link[rel="prev"],link[rel="next"]';
    document.head.querySelectorAll(selector).forEach(n => n.remove());
    doc.head.querySelectorAll(selector).forEach(n => document.head.append(document.importNode(n, true)));
    document.title = doc.title;
    root.lang = doc.documentElement.lang;
    root.dir = doc.documentElement.dir;
    document.body.className = doc.body.className;
  }
  async function navigate(target, { pop = false, position = null } = {}) {
    window.fengDashboard?.close(false);
    request?.abort();
    const id = ++navigationId;
    const controller = new AbortController(); request = controller;
    const timeout = setTimeout(() => controller.abort('timeout'), 10000);
    let styles;
    savePosition();
    progress?.setAttribute('data-xf-loading', '');
    document.querySelector('#xf-content')?.setAttribute('aria-busy', 'true');
    announce('正在加载页面');
    document.querySelectorAll('dialog[open]').forEach(d => d.close());
    try {
      const response = await fetch(target, { credentials: 'same-origin', signal: controller.signal, cache: 'no-store', headers: { 'X-XF-Navigation': '1' } });
      if (!response.ok || !response.headers.get('content-type')?.includes('text/html') || !safeURL(response.url)) throw new Error('Native navigation required');
      const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
      if (!compatible(doc)) throw new Error('Page runtime differs');
      styles = await prepareStyles(doc, controller.signal);
      if (controller.signal.aborted || id !== navigationId) { styles.cancel(); return; }
      const view = document.importNode(doc.querySelector('#xf-view'), true);
      // DOMParser parses noscript as markup; strip its fallback before insertion so it cannot fetch media.
      view.querySelectorAll('noscript').forEach(fallback => fallback.remove());
      const persistentHeader=document.querySelector('#xf-view > .xf-header');
      const incomingHeader=view.querySelector('.xf-header');
      pageCleanup();
      // Keep the animated shell alive across navigation; refresh page-specific menu contents.
      if(persistentHeader && incomingHeader){
        persistentHeader.className=incomingHeader.className;
        persistentHeader.replaceChildren(...incomingHeader.childNodes);
        incomingHeader.replaceWith(persistentHeader);
      }
      document.querySelector('#xf-view').replaceWith(view);
      styles.commit();
      syncMetadata(doc);
      const finalURL = new URL(response.url); finalURL.hash = new URL(target, location.href).hash;
      if (!pop) history.pushState({ xf: true, x: 0, y: 0 }, '', finalURL.href);
      committedURL = location.href;
      pageCleanup = mount(view);
      view.querySelector('#xf-content')?.classList.add('xf-view-enter');
      const main = view.querySelector('#xf-content'); main?.focus({ preventScroll: true });
      const hash = new URL(location.href).hash;
      let anchor = null;
      try { anchor = hash ? document.getElementById(decodeURIComponent(hash.slice(1))) : null; } catch { /* malformed anchor stays at top */ }
      if (pop && position) scrollTo(position.x || 0, position.y || 0);
      else if (anchor) anchor.scrollIntoView();
      else scrollTo(0, 0);
      syncScrollChrome();
      savePosition();
      announce(`已打开：${doc.title}`);
      await loadPageScripts(doc);
    } catch (error) {
      styles?.cancel();
      if (id !== navigationId) return;
      if (controller.signal.aborted && controller.signal.reason !== 'timeout') return;
      announce('正在使用标准方式打开页面');
      // Offline/server/plugin failures retain the real URL and browser's native recovery.
      if (pop) location.replace(target); else location.assign(target);
    } finally {
      clearTimeout(timeout);
      if (id === navigationId) {
        progress?.removeAttribute('data-xf-loading');
        document.querySelector('#xf-content')?.removeAttribute('aria-busy');
        request = null;
      }
    }
  }
  function motionButtons() {
    const paused = root.dataset.xfMotion === 'paused' || reduced.matches;
    document.querySelectorAll('[data-xf-motion-toggle]').forEach(button => {
      button.setAttribute('aria-pressed', String(paused));
      button.disabled = reduced.matches;
      button.setAttribute('aria-label', reduced.matches ? '已按系统偏好减少动效' : (paused ? '播放背景动效' : '暂停背景动效'));
      button.innerHTML = fengIcons[paused ? 'play' : 'pause'];
    });
  }
  function syncBackgroundMotion() {
    const paused = root.dataset.xfMotion === 'paused' || reduced.matches;
    document.querySelectorAll('[data-xf-cover-video]').forEach(video => {
      if (paused) video.pause();
      else video.play().then(() => video.setAttribute('data-xf-playing', '')).catch(() => video.removeAttribute('data-xf-playing'));
    });
    motionButtons();
  }
  reduced.addEventListener('change', syncBackgroundMotion);
  syncBackgroundMotion();
  let randomRequest=null;
  document.addEventListener('xf:before-unmount',()=>randomRequest?.abort());
  async function openRandomArticle(button){
    if(randomRequest)return;
    const controller=new AbortController();randomRequest=controller;
    const timeout=setTimeout(()=>controller.abort(),10000);
    button.setAttribute('aria-busy','true');button.setAttribute('aria-disabled','true');
    announce('正在随机选择一篇文章');
    try{
      const url=new URL(button.dataset.endpoint,location.href);url.searchParams.set('action','feng_random_article');url.searchParams.set('exclude',button.dataset.exclude||'0');
      const [response]=await Promise.all([fetch(url,{signal:controller.signal,credentials:'same-origin',cache:'no-store'}),new Promise(resolve=>setTimeout(resolve,1000))]);
      const result=await response.json();
      if(!response.ok||!result.success)throw new Error(result.data?.message||'暂时无法选择文章，请重试。');
      if(controller.signal.aborted||!button.isConnected)return;
      if(!safeURL(result.data.url))throw new Error('文章地址无效，请重试。');
      if(document.querySelector('meta[name="xf-navigation"]')?.content==='on')await navigate(result.data.url);
      else location.assign(result.data.url);
    }catch(error){if(button.isConnected){const message=error.name==='AbortError'?'请求超时，请再试一次。':error.message;announce(message);button.title=message;}}
    finally{clearTimeout(timeout);button.removeAttribute('aria-busy');button.removeAttribute('aria-disabled');if(randomRequest===controller)randomRequest=null;}
  }
  document.addEventListener('click', event => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;
    if (!target.closest('.feng-header-search')) setHeaderSearch(false);
    if (target.closest('[data-xf-motion-toggle]')) {
      root.dataset.xfMotion = root.dataset.xfMotion === 'paused' ? 'playing' : 'paused';
      syncBackgroundMotion(); return;
    }
    const toggle = target.closest('[data-xf-theme-toggle]');
    if (toggle) {
      if(fixedScheme)return;
      themePreference = isVisitorNight()&&!manualTheme?'light':{system:'light',light:'dark',dark:'system'}[themePreference];
      manualTheme=themePreference!=='system';
      try { localStorage.setItem('xf-theme', themePreference);localStorage.setItem('xf-theme-manual',manualTheme?'1':'0'); } catch {}
      themeButtons(); return;
    }
    if (target.closest('[data-xf-search-open]')) {
      setHeaderSearch(!target.closest('.feng-header-search').hasAttribute('data-open'), true); return;
    }
    if (target.closest('[data-xf-dialog-close]')) { target.closest('dialog')?.close(); return; }
    if (target.closest('[data-xf-top]')) { scrollTo({ top: 0, behavior: reduced.matches ? 'auto' : 'smooth' }); return; }
    const randomButton=target.closest('[data-feng-random]');
    if(randomButton&&!event.defaultPrevented&&event.button===0&&!event.metaKey&&!event.ctrlKey&&!event.shiftKey&&!event.altKey){event.preventDefault();openRandomArticle(randomButton);return;}
    const link = target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
      || link.hasAttribute('download') || link.hasAttribute('data-xf-native') || (link.target && link.target !== '_self')
      || link.rel.split(/\s+/).includes('external') || link.closest('#wpadminbar') || !safeURL(link.href)) return;
    const url = new URL(link.href);
    if (url.pathname === location.pathname && url.search === location.search && url.hash) {
      let anchor;
      try { anchor = document.getElementById(decodeURIComponent(url.hash.slice(1))); } catch { return; }
      if (!anchor) return;
      event.preventDefault(); window.fengDashboard?.close(false); savePosition();
      history.pushState({ xf: true, x: 0, y: 0 }, '', url.href); committedURL = url.href;
      anchor.scrollIntoView({ behavior: reduced.matches ? 'auto' : 'smooth' });
      return;
    }
    if (document.querySelector('meta[name="xf-navigation"]')?.content !== 'on') return;
    event.preventDefault(); navigate(url.href);
  });
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('.xf-search') || form.method.toLowerCase() !== 'get'
      || document.querySelector('meta[name="xf-navigation"]')?.content !== 'on' || !safeURL(form.action)) return;
    const url = new URL(form.action); new FormData(form).forEach((v, k) => url.searchParams.set(k, String(v)));
    event.preventDefault(); navigate(url.href);
  });
  document.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('click', event => {
    if (event.target !== dialog || dialog.matches('[data-talk-detail],[data-talk-compose]')) return;
    const box = dialog.getBoundingClientRect();
    if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
  }));
  window.addEventListener('popstate', event => {
    if (document.querySelector('meta[name="xf-navigation"]')?.content !== 'on') return;
    const next = new URL(location.href), previous = new URL(committedURL);
    if (next.pathname === previous.pathname && next.search === previous.search) {
      request?.abort(); navigationId++;
      progress?.removeAttribute('data-xf-loading');
      document.querySelector('#xf-content')?.removeAttribute('aria-busy');
      committedURL = location.href;
      if (event.state?.xf) scrollTo(event.state.x || 0, event.state.y || 0);
      return;
    }
    navigate(location.href, { pop: true, position: event.state?.xf ? event.state : null });
  });
  function syncScrollChrome() {
    // Hysteresis prevents flickering while hovering around the collapse point.
    const compact = root.hasAttribute('data-xf-scrolled');
    root.toggleAttribute('data-xf-scrolled', compact ? scrollY > 24 : scrollY > 72);
    document.querySelector('[data-xf-top]')?.toggleAttribute('data-xf-visible', scrollY > 500);
  }
  let scrollFrame = 0;
  let positionTimer = 0;
  window.addEventListener('pagehide', savePosition);
  window.addEventListener('scroll', () => {
    if (scrollFrame) return;
    scrollFrame = requestAnimationFrame(() => {
      scrollFrame = 0;
      syncScrollChrome();
      clearTimeout(positionTimer);
      positionTimer = setTimeout(savePosition, 250);
    });
  }, { passive: true });
  if (document.querySelector('meta[name="xf-navigation"]')?.content === 'on') history.scrollRestoration = 'manual';
  history.replaceState({ ...(history.state || {}), xf: true, x: scrollX, y: scrollY }, '', location.href);
  const view = document.querySelector('#xf-view');
  if (view) pageCleanup = mount(view);
})();

/* Comment locations: lazy lookup with PJAX cleanup. */
(() => {
 let cleanup=()=>{};
 function mount(){cleanup();const controller=new AbortController(),queue=[];let running=0;const nodes=[...document.querySelectorAll('[data-comment-geo]')];
 function pump(){while(running<4&&queue.length&&!controller.signal.aborted){const node=queue.shift();running++;fetch(JSON.parse(document.getElementById('feng-config')?.textContent||'{}').commentLocationEndpoint+node.dataset.commentGeo,{signal:controller.signal}).then(r=>{if(!r.ok)throw Error();return r.json();}).then(data=>{if(!node.isConnected)return;const label=node.querySelector('[data-geo-label]');label.textContent=data.label||'所在地未知';if(/^[a-z]{2}$/.test(data.code||'')){const img=document.createElement('img');img.src='https://flagcdn.io/flags/4x3/'+data.code+'.svg';img.alt='';img.width=18;img.height=14;img.addEventListener('error',()=>img.remove(),{once:true});node.prepend(img);}}).catch(()=>{if(!controller.signal.aborted){node.querySelector('[data-geo-label]').textContent='所在地未知';}}).finally(()=>{running--;pump();});}}
 const observer=new IntersectionObserver(entries=>{entries.forEach(entry=>{if(entry.isIntersecting){observer.unobserve(entry.target);queue.push(entry.target);}});pump();},{rootMargin:'200px'});nodes.forEach(n=>observer.observe(n));cleanup=()=>{controller.abort();observer.disconnect();};
 }
 document.addEventListener('xf:mounted',mount);document.addEventListener('feng:comments-refreshed',mount);document.addEventListener('xf:before-unmount',()=>cleanup());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
