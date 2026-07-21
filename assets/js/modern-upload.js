(()=>{
 'use strict';
 const q=(selector,root=document)=>root.querySelector(selector);
 const qa=(selector,root=document)=>[...root.querySelectorAll(selector)];
 const imageTypes=new Set(['image/jpeg','image/png','image/webp']);
 const videoTypes=new Set(['video/mp4','video/webm']);
 const maxFiles=30;
 const formatBytes=bytes=>{bytes=Number(bytes)||0;if(bytes<1024)return `${bytes} Б`;if(bytes<1024*1024)return `${Math.max(1,Math.round(bytes/1024))} КБ`;return `${(bytes/1024/1024).toFixed(bytes>10*1024*1024?0:1)} МБ`;};
 const sync=(input,files)=>{if(!input)return;const transfer=new DataTransfer();files.forEach(file=>transfer.items.add(file));input.files=transfer.files;};
 const fileKey=file=>`${file.name}|${file.size}|${file.lastModified}`;
 const iconFor=file=>imageTypes.has(file.type)?'▧':videoTypes.has(file.type)?'▶':'📄';
 const kindFor=file=>imageTypes.has(file.type)?'photo':videoTypes.has(file.type)?'video':'document';

 function initComposer(form){
  if(!form||form.dataset.modernUploadReady==='1')return;form.dataset.modernUploadReady='1';
  const picker=q('[data-wall-unified-picker]',form),choose=q('[data-wall-unified-choose]',form),dropzone=q('[data-wall-unified-dropzone]',form),preview=q('[data-wall-unified-preview]',form),photoInput=q('[data-wall-photos]',form),videoInput=q('[data-wall-videos]',form),documentInput=q('[data-wall-documents]',form),summary=q('[data-wall-file-summary]',form),mode=q('[data-wall-publish-mode]',form),schedule=q('[data-wall-schedule-field]',form),submit=q('button[type="submit"]',form),modal=form.closest('[data-wall-publisher-modal]');
  let files=[];

  const updateSubmit=()=>{const value=mode?.value||'now';if(submit)submit.textContent=value==='draft'?'Сохранить черновик':value==='scheduled'?'Запланировать':'Опубликовать';if(schedule)schedule.hidden=value!=='scheduled';};
  const revoke=()=>qa('[data-object-url]',preview||document).forEach(node=>{try{URL.revokeObjectURL(node.dataset.objectUrl);}catch(_){};});
  const render=()=>{
   const photos=files.filter(file=>kindFor(file)==='photo'),videos=files.filter(file=>kindFor(file)==='video'),documents=files.filter(file=>kindFor(file)==='document');sync(photoInput,photos);sync(videoInput,videos);sync(documentInput,documents);
   if(summary)summary.textContent=files.length?`Выбрано файлов: ${files.length}`:'Файлы не выбраны';
   if(!preview)return;revoke();preview.hidden=!files.length;preview.innerHTML='';
   files.forEach((file,index)=>{const item=document.createElement('article');item.className=`wall-unified-item is-${kindFor(file)}`;const media=document.createElement('div');media.className='wall-unified-item-media';if(kindFor(file)==='photo'){const url=URL.createObjectURL(file),img=document.createElement('img');img.src=url;img.alt='';img.dataset.objectUrl=url;media.append(img);}else if(kindFor(file)==='video'){const url=URL.createObjectURL(file),video=document.createElement('video');video.src=url;video.muted=true;video.preload='metadata';video.playsInline=true;video.dataset.objectUrl=url;media.append(video);}else{media.textContent=iconFor(file);}const text=document.createElement('div');text.innerHTML='<b></b><small></small>';q('b',text).textContent=file.name;q('small',text).textContent=formatBytes(file.size);const remove=document.createElement('button');remove.type='button';remove.dataset.wallUnifiedRemove=String(index);remove.setAttribute('aria-label','Удалить файл');remove.textContent='×';item.append(media,text,remove);preview.append(item);});
  };
  const add=incoming=>{const known=new Set(files.map(fileKey));let rejected=0;for(const file of [...incoming]){if(!(file instanceof File)||known.has(fileKey(file)))continue;if(files.length>=maxFiles){rejected++;continue;}if(imageTypes.has(file.type)&&file.size>12*1024*1024){rejected++;continue;}files.push(file);known.add(fileKey(file));}render();if(rejected)window.KovchegShowToast?.(`Часть файлов не добавлена. Максимум ${maxFiles} файлов, фото — до 12 МБ.`,{type:'error'});};
  const openPicker=()=>picker?.click();

  choose?.addEventListener('click',event=>{event.preventDefault();openPicker();});
  dropzone?.addEventListener('click',event=>{if(event.target.closest('button'))return;openPicker();});
  dropzone?.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();openPicker();}});
  picker?.addEventListener('change',()=>{add(picker.files||[]);picker.value='';});
  preview?.addEventListener('click',event=>{const button=event.target.closest('[data-wall-unified-remove]');if(!button)return;files.splice(Number(button.dataset.wallUnifiedRemove),1);render();});
  dropzone?.addEventListener('dragover',event=>{event.preventDefault();event.stopPropagation();dropzone.classList.add('dragging');});
  dropzone?.addEventListener('dragleave',event=>{if(!dropzone.contains(event.relatedTarget))dropzone.classList.remove('dragging');});
  dropzone?.addEventListener('drop',event=>{event.preventDefault();event.stopPropagation();dropzone.classList.remove('dragging');add(event.dataTransfer?.files||[]);});
  mode?.addEventListener('change',updateSubmit);updateSubmit();
  form.addEventListener('submit',event=>{if((mode?.value||'now')==='scheduled'&&!q('input[name="publish_at"]',form)?.value){event.preventDefault();event.stopImmediatePropagation();window.KovchegShowToast?.('Выберите дату и время публикации.',{type:'error'});q('input[name="publish_at"]',form)?.focus();}},true);
  form.addEventListener('kovcheg-wall-reset',()=>{files=[];if(picker)picker.value='';render();if(mode)mode.value='now';if(schedule)schedule.hidden=true;updateSubmit();});
  modal?.addEventListener('transitionend',()=>{if(modal.hidden)revoke();});
  render();
 }

 function initGenericMultiple(input){
  if(!input||input.dataset.modernMultipleReady==='1'||input.closest('[data-modern-wall-composer]')||input.hidden)return;input.dataset.modernMultipleReady='1';input.classList.add('modern-multiple-input');const label=input.closest('label');if(!label)return;label.classList.add('modern-multiple-field');let note=q('[data-modern-multiple-count]',label);if(!note){note=document.createElement('small');note.dataset.modernMultipleCount='';note.textContent='Можно выбрать несколько файлов';label.append(note);}const paint=()=>{const count=input.files?.length||0;note.textContent=count?`Выбрано файлов: ${count}`:'Можно выбрать несколько файлов';label.classList.toggle('has-files',count>0);};input.addEventListener('change',paint);paint();
 }

 function boot(root=document){if(root.matches?.('[data-modern-wall-composer]'))initComposer(root);qa('[data-modern-wall-composer]',root).forEach(initComposer);if(root.matches?.('input[type="file"][multiple]'))initGenericMultiple(root);qa('input[type="file"][multiple]',root).forEach(initGenericMultiple);}
 boot();document.addEventListener('DOMContentLoaded',()=>boot(),{once:true});document.addEventListener('kovcheg:pagechange',event=>boot(event.target||document));new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1)boot(node);}))).observe(document.documentElement,{subtree:true,childList:true});
})();
