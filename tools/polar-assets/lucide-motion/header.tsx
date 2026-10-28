import './category-list';
import './content-icons';
import {createRoot, type Root} from 'react-dom/client';
import {forwardRef,useEffect,useImperativeHandle,useRef,useState} from 'react';
import {MotionConfig} from 'motion/react';
import {Monitor} from 'lucide-react';
import {RssIcon} from './lordicon/Rss';
import {CheckIcon} from './lordicon/Check';
import {XIcon} from './icons/x';
type Handle = {startAnimation:()=>void;stopAnimation:()=>void};
// The system-theme monitor is the only non-Lordicon supplemental icon.
const SystemIcon = forwardRef<Handle,{size:number}>(({size},ref)=>{
 const [playing,setPlaying]=useState(false);
 useImperativeHandle(ref,()=>({startAnimation:()=>setPlaying(true),stopAnimation:()=>setPlaying(false)}));
 useEffect(()=>{if(!playing)return;const timer=window.setTimeout(()=>setPlaying(false),650);return()=>clearTimeout(timer);},[playing]);
 return <div data-supplement="system" data-playing={playing}><Monitor size={size} strokeWidth={2}/></div>;
});
function ButtonIcon({button,kind,index}:{button:HTMLElement;kind:string;index:number}){
 const ref=useRef<Handle>(null);
 const getState=()=>kind==='panel'?(button.getAttribute('aria-expanded')==='true'?'panel-open':'panel'):kind==='search'?(button.closest('.feng-header-search')?.hasAttribute('data-open')?'close':'search'):kind==='rss'?(button.classList.contains('is-copied')?'check':'rss'):kind==='theme'?(document.documentElement.dataset.xfTheme||'light'):(button.getAttribute('aria-busy')==='true'?'loading':'dice');
 const [state,setState]=useState(getState);
 useEffect(()=>{
  const media=matchMedia('(prefers-reduced-motion: reduce)');
  const start=()=>{if(!media.matches)ref.current?.startAnimation();},stop=()=>ref.current?.stopAnimation();
  button.addEventListener('pointerenter',start);button.addEventListener('pointerleave',stop);button.addEventListener('focus',start);button.addEventListener('blur',stop);
  const observer=new MutationObserver(()=>setState(getState()));
  if(kind==='theme')observer.observe(document.documentElement,{attributes:true,attributeFilter:['data-xf-theme']});
  observer.observe(button,{attributes:true,attributeFilter:['class','data-mode','aria-expanded','aria-busy']});
  const search=button.closest('.feng-header-search');if(kind==='search'&&search)observer.observe(search,{attributes:true,attributeFilter:['data-open']});
  const timer=window.setTimeout(start,document.querySelector('.feng-profile-hero')?index*150+100:0);
  media.addEventListener('change',stop);
  return()=>{clearTimeout(timer);observer.disconnect();media.removeEventListener('change',stop);button.removeEventListener('pointerenter',start);button.removeEventListener('pointerleave',stop);button.removeEventListener('focus',start);button.removeEventListener('blur',stop);};
 },[button]);
 useEffect(()=>{if(!matchMedia('(prefers-reduced-motion: reduce)').matches&&(state==='check'||state==='close'))ref.current?.startAnimation();},[state]);
 if(kind==='dice'&&state==='loading')return <svg className="feng-random-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor"><g><circle cx="12" cy="12" r="9.5" strokeWidth="3" strokeLinecap="round"/></g></svg>;
 if(kind==='search'||kind==='dice')return <i className={'feng-header-fa fa-solid '+(kind==='dice'?'fa-dice':state==='close'?'fa-xmark':'fa-magnifying-glass')}/>;
 if(kind==='panel')return <svg className="feng-panel-morph" data-open={state==='panel-open'} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path className="feng-panel-morph__top" d="M4 6h16"/><path className="feng-panel-morph__middle" d="M4 12h16"/><path className="feng-panel-morph__bottom" d="M4 18h16"/></svg>;
 if(kind==='theme')return <span className="feng-theme-orbit" data-light={state==='light'}><svg className="feng-theme-orbit__moon" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 14.2A8.7 8.7 0 0 1 9.8 3.5a9 9 0 1 0 10.7 10.7Z"/></svg><svg className="feng-theme-orbit__sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><circle cx="12" cy="12" r="4" fill="currentColor" stroke="none"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg></span>;
 const Component={rss:RssIcon,check:CheckIcon,close:XIcon}[state];
 return <MotionConfig reducedMotion="user">{Component?<Component key={state} ref={ref} size={20}/>:<SystemIcon key={state} ref={ref} size={20}/>}</MotionConfig>;
}
let mounted:Array<{button:HTMLElement;host:HTMLElement;root:Root}>=[];
function prune(){
 mounted=mounted.filter(({button,host,root})=>{
  if(button.isConnected&&host.isConnected)return true;
  root.unmount();host.remove();button.classList.remove('has-lucide-animation');
  return false;
 });
}
function cleanup(){mounted.forEach(({button,host,root})=>{root.unmount();host.remove();button.classList.remove('has-lucide-animation');});mounted=[];}
function mount(){prune();const header=document.querySelector('.xf-header');if(!header)return;
 const specs=[['search','[data-xf-search-open]'],['dice','[data-feng-random]'],['theme','[data-xf-theme-toggle]'],['rss','[data-feng-copy-rss]'],['panel','[data-feng-dashboard-toggle]']];
 specs.forEach(([kind,selector],index)=>{const button=header.querySelector<HTMLElement>(selector);if(!button||mounted.some(item=>item.button===button))return;const host=document.createElement('span');host.className='feng-lucide-animation';host.setAttribute('aria-hidden','true');button.append(host);button.classList.add('has-lucide-animation');const root=createRoot(host);root.render(<ButtonIcon button={button} kind={kind} index={index}/>);mounted.push({button,host,root});});
}
document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',prune);window.addEventListener('pagehide',cleanup);window.addEventListener('pageshow',()=>{if(!mounted.length)mount();});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
