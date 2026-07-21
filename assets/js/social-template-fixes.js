(()=>{
 'use strict';
 const q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
 const nativeFetch=window.fetch.bind(window);
 window.fetch=(input,init={})=>{
  const requestUrl=typeof input==='string'?input:input?.url||'';
  let path='';try{path=new URL(requestUrl,location.href).pathname;}catch(_){path=String(requestUrl);}
  const method=String(init.method||(typeof input!=='string'&&input?.method)||'GET').toUpperCase();
  if(method==='POST'&&path.endsWith('/profile/avatar')){
   const headers=new Headers(init.headers||(typeof input!=='string'?input.headers:undefined)||{});
   headers.set('Accept','application/json');headers.set('X-Requested-With','XMLHttpRequest');
   init={...init,headers};
  }
  return nativeFetch(input,init);
 };
 function initVkTabs(root=document){
  qa('[data-kov-vk-media]',root).forEach(shell=>{
   if(shell.dataset.ready==='1')return;shell.dataset.ready='1';
   const panels=()=>qa('[data-kov-vk-panel]',shell);
   const buttons=()=>qa('[data-kov-vk-tab]',document);
   const open=name=>{
    const valid=panels().some(panel=>panel.dataset.kovVkPanel===name)?name:'wall';
    buttons().forEach(button=>button.classList.toggle('active',button.dataset.kovVkTab===valid));
    panels().forEach(panel=>panel.hidden=panel.dataset.kovVkPanel!==valid);
    shell.dataset.activeTab=valid;
    const scroller=shell.closest('.kov-vk-old-profile-main');
    if(scroller)scroller.scrollTop=0;
   };
   buttons().forEach(button=>{
    if(button.dataset.kovVkBound==='1')return;button.dataset.kovVkBound='1';
    button.addEventListener('click',event=>{event.preventDefault();open(button.dataset.kovVkTab||'wall');});
   });
   open(shell.dataset.activeTab||'wall');
  });
 }
 document.addEventListener('click',event=>{
  const button=event.target.closest('[data-kov-vk-audio]');if(!button)return;
  const source=button.dataset.kovVkAudio;if(!source)return;
  let player=q('[data-kov-vk-audio-player]');
  if(!player){player=document.createElement('audio');player.dataset.kovVkAudioPlayer='1';player.hidden=true;document.body.append(player);}
  qa('[data-kov-vk-audio].playing').forEach(item=>item.classList.remove('playing'));
  if(player.src===new URL(source,location.href).href&&!player.paused){player.pause();return;}
  player.src=source;player.play().then(()=>button.classList.add('playing')).catch(()=>{});
  player.onended=()=>button.classList.remove('playing');player.onpause=()=>button.classList.remove('playing');
 });
 function initBannerPosition(root=document){
  qa('.profile-banner-settings',root).forEach(section=>{
   if(section.dataset.positionReady==='1')return;section.dataset.positionReady='1';
   const form=q('[data-profile-banner-form]',section),preview=q('[data-profile-banner-preview]',section),input=q('[data-profile-banner-input]',section);
   if(!form||!preview||!input)return;
   const label=document.createElement('label');label.className='x-banner-position-control';label.innerHTML='<span>Положение изображения</span><input type="range" name="banner_position" min="0" max="100" value="50" step="1">';
   form.insertBefore(label,form.querySelector('button'));
   const range=q('input[type="range"]',label);let dragging=false,startY=0,startValue=50;
   const paint=()=>{preview.style.setProperty('--x-banner-position',`${range.value}%`);preview.style.backgroundPosition=`center ${range.value}%`;};paint();
   range.addEventListener('input',paint);
   preview.addEventListener('pointerdown',event=>{dragging=true;startY=event.clientY;startValue=Number(range.value);preview.setPointerCapture?.(event.pointerId);});
   preview.addEventListener('pointermove',event=>{if(!dragging)return;const height=Math.max(80,preview.clientHeight);range.value=String(Math.max(0,Math.min(100,startValue+(event.clientY-startY)/height*100)));paint();});
   preview.addEventListener('pointerup',()=>dragging=false);preview.addEventListener('pointercancel',()=>dragging=false);
   input.addEventListener('change',()=>setTimeout(paint,0));
  });
 }
 const boot=()=>{initVkTabs();initBannerPosition();};
 document.addEventListener('DOMContentLoaded',boot,{once:true});document.addEventListener('kovcheg:pagechange',boot);boot();
})();
