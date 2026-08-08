(()=>{
  'use strict';
  const getSlider=(id)=>document.getElementById(id);
  const move=(id,direction)=>{
    const slider=getSlider(id);
    if(!slider)return;
    const card=slider.querySelector('.farm-slide-card');
    const amount=card?card.getBoundingClientRect().width+14:Math.max(260,slider.clientWidth*.8);
    slider.scrollBy({left:amount*direction,behavior:'smooth'});
  };
  document.addEventListener('click',(event)=>{
    const prev=event.target.closest('[data-farm-slider-prev]');
    if(prev){move(prev.getAttribute('data-farm-slider-prev')||'',-1);return;}
    const next=event.target.closest('[data-farm-slider-next]');
    if(next)move(next.getAttribute('data-farm-slider-next')||'',1);
  });
})();
