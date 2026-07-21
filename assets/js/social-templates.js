(()=>{
 'use strict';
 const q=(selector,root=document)=>root.querySelector(selector);
 const qa=(selector,root=document)=>[...root.querySelectorAll(selector)];

 document.addEventListener('click',event=>{
  const trigger=event.target.closest('[data-social-composer-open]');
  if(!trigger)return;
  const opener=q('.wall-create-shell [data-wall-composer-open]');
  if(!opener)return;
  event.preventDefault();
  opener.click();
 });

 document.addEventListener('input',event=>{
  const input=event.target.closest('[data-social-people-search]');
  if(!input)return;
  const root=input.closest('main')||document;
  const query=input.value.trim().toLocaleLowerCase('ru-RU');
  let visible=0;
  qa('[data-social-person]',root).forEach(card=>{
   const matched=!query||String(card.dataset.searchText||card.textContent).toLocaleLowerCase('ru-RU').includes(query);
   card.hidden=!matched;
   if(matched)visible++;
  });
  const empty=q('[data-social-people-empty]',root);
  if(empty)empty.hidden=visible>0;
 });

 document.addEventListener('change',event=>{
  const input=event.target.closest('[data-profile-banner-input]');
  if(!input||!input.files?.[0])return;
  const preview=q('[data-profile-banner-preview]',input.closest('.profile-banner-settings')||document);
  if(!preview)return;
  const url=URL.createObjectURL(input.files[0]);
  preview.style.backgroundImage=`url("${url}")`;
  preview.classList.add('has-image');
  const label=q('span',preview);
  if(label)label.textContent='Предпросмотр нового баннера';
  const form=input.closest('form');
  form?.addEventListener('submit',()=>URL.revokeObjectURL(url),{once:true});
 });
})();
