(()=>{
 'use strict';
 const humanSize=bytes=>{const n=Number(bytes)||0;if(n<1024)return `${n} Б`;const units=['КБ','МБ','ГБ'];let value=n/1024,index=0;while(value>=1024&&index<units.length-1){value/=1024;index++;}return `${value.toFixed(value>=10?1:2)} ${units[index]}`;};
 const mergeFiles=(input,incoming)=>{
  const transfer=new DataTransfer();
  const existing=input.multiple?Array.from(input.files||[]):[];
  const seen=new Set();
  [...existing,...incoming].forEach(file=>{const key=[file.name,file.size,file.lastModified].join(':');if(seen.has(key))return;seen.add(key);if(input.multiple||transfer.items.length===0)transfer.items.add(file);});
  input.files=transfer.files;
 };
 const render=(zone,input,list)=>{
  if(!list)return;
  list.innerHTML='';
  Array.from(input.files||[]).forEach((file,index)=>{
   const row=document.createElement('div');row.className='upload-selection__item';
   const info=document.createElement('div');const name=document.createElement('b');name.textContent=file.name;const meta=document.createElement('small');meta.textContent=`${humanSize(file.size)} · ${file.type||'тип не определён'}`;info.append(name,meta);
   const remove=document.createElement('button');remove.type='button';remove.textContent='Удалить';remove.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();const transfer=new DataTransfer();Array.from(input.files||[]).forEach((candidate,i)=>{if(i!==index)transfer.items.add(candidate);});input.files=transfer.files;render(zone,input,list);input.dispatchEvent(new Event('change',{bubbles:true}));});
   row.append(info,remove);list.append(row);
  });
  zone.classList.toggle('has-files',(input.files||[]).length>0);
 };
 const init=zone=>{
  const input=zone.querySelector('input[type=file]');if(!input)return;
  const target=zone.closest('[data-upload-block]')||zone.parentElement;
  const list=target?.querySelector('[data-upload-selection]')||null;
  ['dragenter','dragover'].forEach(type=>zone.addEventListener(type,event=>{event.preventDefault();event.stopPropagation();zone.classList.add('is-dragover');}));
  ['dragleave','drop'].forEach(type=>zone.addEventListener(type,event=>{event.preventDefault();event.stopPropagation();zone.classList.remove('is-dragover');}));
  zone.addEventListener('drop',event=>{const files=Array.from(event.dataTransfer?.files||[]);if(!files.length)return;mergeFiles(input,files);render(zone,input,list);input.dispatchEvent(new Event('change',{bubbles:true}));});
  input.addEventListener('change',()=>render(zone,input,list));
  render(zone,input,list);
  const form=input.form;if(form&&!form.dataset.uploadProgressBound){form.dataset.uploadProgressBound='1';form.addEventListener('submit',()=>{form.querySelectorAll('[data-upload-progress]').forEach(progress=>{progress.hidden=false;const bar=progress.querySelector('span');if(bar){bar.style.width='18%';requestAnimationFrame(()=>bar.style.width='82%');}});form.querySelectorAll('button[type=submit]').forEach(button=>{button.disabled=true;button.dataset.originalText=button.textContent||'';button.textContent='Загрузка…';});});}
 };
 const boot=()=>document.querySelectorAll('[data-upload-zone]').forEach(init);
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();