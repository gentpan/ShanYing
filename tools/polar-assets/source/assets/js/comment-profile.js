(() => {
 function mount(){
  const form=document.querySelector('#commentform');if(!form)return;
  if(navigator.userAgentData?.getHighEntropyValues&&!form.dataset.platformRequested){
   form.dataset.platformRequested='1';
   navigator.userAgentData.getHighEntropyValues(['platformVersion']).then(data=>{
    if(data.platform!=='macOS'||!/^\d{1,3}(?:\.\d{1,3}){0,2}$/.test(data.platformVersion)||!form.isConnected)return;
    const field=document.createElement('input');field.type='hidden';field.name='feng_macos_version';field.value=data.platformVersion;form.append(field);
   }).catch(()=>{});
  }
  const saved=[...form.querySelectorAll('#author,#email,#url')].map(input=>({input,value:input.value}));
  const button=document.querySelector('[data-feng-switch-profile]');
  if(button&&!button.dataset.bound){button.dataset.bound='1';button.addEventListener('click',()=>{
   const editing=form.classList.toggle('is-editing-profile');button.setAttribute('aria-expanded',String(editing));button.textContent=editing?'使用已存资料':'切换资料';
   let flag=form.querySelector('[name=feng_switch_identity]');if(!flag){flag=document.createElement('input');flag.type='hidden';flag.name='feng_switch_identity';form.append(flag);}flag.value=editing?'1':'';
   if(!editing)saved.forEach(({input,value})=>input.value=value);
   if(editing)form.querySelector('#author')?.focus();
  });}
  const hash=location.hash;if(/^#comment-\d+$/.test(hash)){const comment=document.getElementById(hash.slice(1));if(comment){requestAnimationFrame(()=>{comment.scrollIntoView({block:'center',behavior:'auto'});comment.classList.add('feng-comment-arrived');});}}
 }
 document.addEventListener('xf:mounted',mount);if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();

(() => {
 let timers=[];
 function mountEdits(){
  timers.forEach(clearTimeout);timers=[];
  const endpoint=document.querySelector('[data-feng-comment-endpoint]')?.dataset.fengCommentEndpoint;if(!endpoint)return;
  document.querySelectorAll('.feng-comment[id^="comment-"]').forEach(comment=>{
   const id=comment.id.slice(8);let ticket;try{ticket=JSON.parse(sessionStorage.getItem('feng-comment-edit-'+id));}catch{}
   if(!ticket)return;const remaining=ticket.expires*1000-Date.now();
   if(remaining<=0){try{sessionStorage.removeItem('feng-comment-edit-'+id);}catch{}return;}
   const actions=comment.querySelector('.feng-comment__actions');if(!actions||actions.querySelector('[data-self-edit],a[aria-label="编辑评论"]'))return;
   const button=document.createElement('button');button.type='button';button.dataset.selfEdit='';button.setAttribute('aria-label','编辑自己的评论（提交后60秒内）');button.title='提交后60秒内可编辑';button.innerHTML='<i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>';actions.prepend(button);
   timers.push(setTimeout(()=>button.remove(),remaining));
   button.addEventListener('click',async()=>{
    const request=async extra=>{const response=await fetch(endpoint,{method:'POST',credentials:'same-origin',body:new URLSearchParams({action:'feng_comment_self_edit',id,token:ticket.token,...extra})});const result=await response.json();if(!result.success)throw Error(result.data?.message||'编辑失败');return result.data;};
    button.disabled=true;
    try{
     const data=await request({});
     const dialog=document.createElement('dialog');dialog.className='feng-self-edit-dialog';dialog.setAttribute('aria-label','修改评论');
     dialog.innerHTML='<form><h3>修改评论</h3><p>提交后 60 秒内可以修改，保存后仍按站点规则审核。</p><textarea aria-label="评论内容" required maxlength="10000"></textarea><p role="status"></p><div><button type="button">取消</button><button type="submit">保存修改</button></div></form>';
     dialog.querySelector('textarea').value=data.content;document.body.append(dialog);dialog.showModal();
     dialog.addEventListener('close',()=>dialog.remove(),{once:true});dialog.querySelector('[type=button]').onclick=()=>dialog.close();
     dialog.querySelector('form').onsubmit=async e=>{e.preventDefault();const submit=dialog.querySelector('[type=submit]');submit.disabled=true;try{await request({content:dialog.querySelector('textarea').value});dialog.close();location.hash='comment-'+id;location.reload();}catch(error){dialog.querySelector('[role=status]').textContent=error.message;submit.disabled=false;}};
    }catch(error){window.fengToast?.(error.message,'error');}finally{button.disabled=false;}
   });
  });
 }
 document.addEventListener('xf:mounted',mountEdits);document.addEventListener('feng:comments-refreshed',mountEdits);document.addEventListener('xf:before-unmount',()=>{timers.forEach(clearTimeout);document.querySelector('.feng-self-edit-dialog')?.remove();});
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mountEdits,{once:true});else mountEdits();
})();
