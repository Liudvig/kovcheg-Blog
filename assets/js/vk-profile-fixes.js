(()=>{
 'use strict';
 const q=(selector,root=document)=>root.querySelector(selector);
 const qa=(selector,root=document)=>[...root.querySelectorAll(selector)];
 const base=String(window.KOVCHEG?.baseUrl||'').replace(/\/$/,'');
 const toast=(message,type='success')=>window.KovchegShowToast?.(message,{type});

 function initCarousel(root){
  if(!root||root.dataset.carouselReady==='1')return;
  root.dataset.carouselReady='1';
  const viewport=q('[data-vk-carousel-viewport]',root);
  const prev=q('[data-vk-carousel-prev]',root);
  const next=q('[data-vk-carousel-next]',root);
  if(!viewport)return;
  const update=()=>{
   const max=Math.max(0,viewport.scrollWidth-viewport.clientWidth);
   if(prev)prev.disabled=viewport.scrollLeft<=2;
   if(next)next.disabled=viewport.scrollLeft>=max-2;
  };
  const move=direction=>viewport.scrollBy({left:direction*Math.max(180,Math.round(viewport.clientWidth*.82)),behavior:'smooth'});
  prev?.addEventListener('click',()=>move(-1));
  next?.addEventListener('click',()=>move(1));
  viewport.addEventListener('scroll',update,{passive:true});
  addEventListener('resize',update,{passive:true});
  requestAnimationFrame(update);
 }

 function insertComment(form,result){
  const post=form.closest('[data-wall-post]');
  if(!post||!result?.html)return;
  const template=document.createElement('template');
  template.innerHTML=String(result.html).trim();
  const node=template.content.firstElementChild;
  if(!node)return;
  const commentId=node.getAttribute('data-wall-comment');
  if(commentId&&q(`[data-wall-comment="${CSS.escape(commentId)}"]`,post))return;
  const parentId=Number(result.parent_id||0);
  if(parentId){
   const parent=q(`[data-wall-comment="${parentId}"]`,post);
   let replies=parent&&q(`[data-wall-comment-replies="${parentId}"]`,parent);
   if(parent&&!replies){replies=document.createElement('div');replies.className='wall-comment-replies';replies.dataset.wallCommentReplies=String(parentId);q('.wall-comment-body',parent)?.append(replies);}
   replies?.append(node);
  }else{
   q('[data-wall-comments-list]',post)?.append(node);
  }
  const comments=q('[data-wall-comments]',post);if(comments)comments.hidden=false;
  const count=q('[data-wall-comment-count]',post);if(count)count.textContent=Number(result.count||0)>0?String(result.count):'';
 }

 async function submitComment(event){
  const form=event.target?.closest?.('[data-wall-comment-form]');
  if(!form)return;
  event.preventDefault();
  event.stopImmediatePropagation();
  if(form.dataset.commentSubmitting==='1')return;
  const textarea=q('textarea[name="body"]',form);
  if(!textarea?.value.trim()){textarea?.focus();return;}
  const button=q('button[type="submit"]',form);
  form.dataset.commentSubmitting='1';
  if(button)button.disabled=true;
  try{
   const response=await fetch(form.action.startsWith('http')?form.action:`${base}${form.action.startsWith('/')?'':'/'}${form.action}`,{
    method:'POST',body:new FormData(form),credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-Token':window.KOVCHEG?.csrf||''}
   });
   const result=await response.json().catch(()=>({}));
   if(!response.ok||result.ok===false)throw new Error(result.error||result.message||`Ошибка ${response.status}`);
   insertComment(form,result);
   form.reset();
   const context=q('[data-wall-comment-reply-context]',form);if(context)context.hidden=true;
   const parent=q('input[name="parent_id"]',form);if(parent)parent.value='';
  }catch(error){toast(error instanceof Error?error.message:'Не удалось отправить комментарий.','error');}
  finally{delete form.dataset.commentSubmitting;if(button)button.disabled=false;}
 }

 window.addEventListener('submit',submitComment,true);
 const boot=(root=document)=>qa('[data-vk-profile-carousel]',root).forEach(initCarousel);
 document.addEventListener('DOMContentLoaded',()=>boot(),{once:true});
 document.addEventListener('kovcheg:pagechange',event=>boot(event.target||document));
 boot();
})();
