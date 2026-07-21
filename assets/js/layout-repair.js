(()=>{
 'use strict';
 const q=(selector,root=document)=>root.querySelector(selector);
 const qa=(selector,root=document)=>[...root.querySelectorAll(selector)];

 function menuPanel(details){return q(':scope > .kov-header-panel, :scope > .vk-drop-panel',details);}
 function menuSummary(details){return q(':scope > summary',details);}
 function positionMenu(details){
  if(!details?.open)return;
  const summary=menuSummary(details),panel=menuPanel(details);if(!summary||!panel)return;
  const rect=summary.getBoundingClientRect();
  panel.style.visibility='hidden';panel.style.display='block';
  const preferred=Math.max(panel.offsetWidth,details.dataset.kovHeaderMenu==='notifications'?360:250);
  const width=Math.min(preferred,innerWidth-16);
  let left=rect.right-width;left=Math.max(8,Math.min(left,innerWidth-width-8));
  let top=rect.bottom+7;const maxHeight=Math.max(180,innerHeight-top-10);
  panel.style.width=`${Math.round(width)}px`;panel.style.left=`${Math.round(left)}px`;panel.style.right='auto';panel.style.top=`${Math.round(top)}px`;panel.style.maxHeight=`${Math.round(maxHeight)}px`;panel.style.visibility='visible';
 }
 function closeMenus(except=null){qa('[data-kov-header-menu][open]').forEach(details=>{if(details!==except){details.open=false;menuSummary(details)?.setAttribute('aria-expanded','false');}});}
 function initHeaderMenu(details){if(!details||details.dataset.headerRepairReady==='1')return;details.dataset.headerRepairReady='1';const summary=menuSummary(details);if(!summary)return;summary.setAttribute('aria-haspopup','menu');summary.setAttribute('aria-expanded',String(details.open));}
 function bootHeaderMenus(root=document){if(root.matches?.('[data-kov-header-menu]'))initHeaderMenu(root);qa('[data-kov-header-menu]',root).forEach(initHeaderMenu);}
 document.addEventListener('click',event=>{
  const summary=event.target.closest?.('[data-kov-header-menu] > summary');
  if(summary){
   const details=summary.parentElement;if(!(details instanceof HTMLDetailsElement))return;
   event.preventDefault();event.stopImmediatePropagation();
   const willOpen=!details.open;closeMenus(details);details.open=willOpen;summary.setAttribute('aria-expanded',String(willOpen));
   if(willOpen)requestAnimationFrame(()=>positionMenu(details));
   return;
  }
  if(!event.target.closest?.('[data-kov-header-menu]'))closeMenus();
 },true);
 document.addEventListener('keydown',event=>{if(event.key==='Escape')closeMenus();});
 addEventListener('resize',()=>qa('[data-kov-header-menu][open]').forEach(positionMenu),{passive:true});
 addEventListener('scroll',()=>qa('[data-kov-header-menu][open]').forEach(positionMenu),{passive:true,capture:true});

 function syncFiles(input,files){const transfer=new DataTransfer();[...files].forEach(file=>transfer.items.add(file));input.files=transfer.files;}
 function initAutoUpload(form){
  if(!form||form.dataset.autoUploadReady==='1')return;form.dataset.autoUploadReady='1';
  const input=q('input[type="file"][multiple]',form),drop=q('[data-auto-upload-drop]',form),status=q('[data-auto-upload-status]',form);if(!input)return;
  const paint=()=>{const count=input.files?.length||0;if(status)status.textContent=count?`Выбрано файлов: ${count}. Начинаю загрузку…`:'Можно выбрать несколько файлов';};
  const submit=()=>{if(!input.files?.length||form.dataset.autoUploading==='1')return;form.dataset.autoUploading='1';form.classList.add('is-uploading');paint();setTimeout(()=>{if(form.requestSubmit)form.requestSubmit();else form.submit();},80);};
  input.addEventListener('change',submit);
  drop?.addEventListener('click',event=>{if(event.target===input)return;event.preventDefault();input.click();});
  drop?.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();input.click();}});
  drop?.addEventListener('dragover',event=>{event.preventDefault();drop.classList.add('dragging');});
  drop?.addEventListener('dragleave',event=>{if(!drop.contains(event.relatedTarget))drop.classList.remove('dragging');});
  drop?.addEventListener('drop',event=>{event.preventDefault();drop.classList.remove('dragging');const incoming=[...(event.dataTransfer?.files||[])];if(!incoming.length)return;syncFiles(input,incoming);input.dispatchEvent(new Event('change',{bubbles:true}));});
  paint();
 }
 function bootAutoUpload(root=document){if(root.matches?.('[data-auto-multiupload]'))initAutoUpload(root);qa('[data-auto-multiupload]',root).forEach(initAutoUpload);}

 function boot(root=document){bootHeaderMenus(root);bootAutoUpload(root);}
 boot();document.addEventListener('DOMContentLoaded',()=>boot(),{once:true});document.addEventListener('kovcheg:pagechange',event=>boot(event.target||document));
 new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1)boot(node);}))).observe(document.documentElement,{childList:true,subtree:true});
})();
