(()=>{
 'use strict';
 const q=(selector,root=document)=>root.querySelector(selector);
 const qa=(selector,root=document)=>[...root.querySelectorAll(selector)];
 const important=(node,name,value)=>node?.style?.setProperty(name,value,'important');

 function detectBorderCrop(img){
  const naturalWidth=img.naturalWidth||0,naturalHeight=img.naturalHeight||0;
  if(naturalWidth<80||naturalHeight<80)return null;
  const maxSample=260,scale=Math.min(1,maxSample/Math.max(naturalWidth,naturalHeight));
  const width=Math.max(20,Math.round(naturalWidth*scale)),height=Math.max(20,Math.round(naturalHeight*scale));
  const canvas=document.createElement('canvas');canvas.width=width;canvas.height=height;
  const context=canvas.getContext('2d',{willReadFrequently:true});if(!context)return null;
  try{context.drawImage(img,0,0,width,height);}catch(_){return null;}
  let pixels;try{pixels=context.getImageData(0,0,width,height).data;}catch(_){return null;}
  const sample=(x,y)=>{const index=(y*width+x)*4;return[pixels[index],pixels[index+1],pixels[index+2],pixels[index+3]];};
  const corners=[sample(0,0),sample(width-1,0),sample(0,height-1),sample(width-1,height-1)];
  const background=[0,1,2,3].map(channel=>corners.reduce((sum,pixel)=>sum+pixel[channel],0)/corners.length);
  const cornerDistance=Math.max(...corners.map(pixel=>Math.abs(pixel[0]-background[0])+Math.abs(pixel[1]-background[1])+Math.abs(pixel[2]-background[2])));
  const nearWhite=background[0]>232&&background[1]>232&&background[2]>232;
  const nearTransparent=background[3]<28;
  if(!nearWhite&&!nearTransparent&&cornerDistance>68)return null;
  const isContent=(x,y)=>{const pixel=sample(x,y);if(pixel[3]<20)return false;const distance=Math.abs(pixel[0]-background[0])+Math.abs(pixel[1]-background[1])+Math.abs(pixel[2]-background[2]);if(nearWhite&&pixel[0]>238&&pixel[1]>238&&pixel[2]>238)return false;return distance>52;};
  const rowThreshold=Math.max(2,Math.floor(width*.018)),columnThreshold=Math.max(2,Math.floor(height*.018));
  let top=0,bottom=height-1,left=0,right=width-1;
  const rowHasContent=y=>{let count=0;for(let x=0;x<width;x++){if(isContent(x,y)&&++count>=rowThreshold)return true;}return false;};
  const columnHasContent=x=>{let count=0;for(let y=0;y<height;y++){if(isContent(x,y)&&++count>=columnThreshold)return true;}return false;};
  while(top<height&&!rowHasContent(top))top++;
  while(bottom>=top&&!rowHasContent(bottom))bottom--;
  while(left<width&&!columnHasContent(left))left++;
  while(right>=left&&!columnHasContent(right))right--;
  if(right<=left||bottom<=top)return null;
  const padding=Math.max(1,Math.round(Math.min(width,height)*.008));
  left=Math.max(0,left-padding);right=Math.min(width-1,right+padding);top=Math.max(0,top-padding);bottom=Math.min(height-1,bottom+padding);
  const cropWidth=right-left+1,cropHeight=bottom-top+1;
  const removed=1-(cropWidth*cropHeight)/(width*height);
  const verticalTrim=(top+(height-1-bottom))/height,horizontalTrim=(left+(width-1-right))/width;
  if(removed<.09||(verticalTrim<.075&&horizontalTrim<.1)||cropWidth<width*.28||cropHeight<height*.2)return null;
  return{x:left/scale,y:top/scale,width:cropWidth/scale,height:cropHeight/scale,naturalWidth,naturalHeight};
 }

 function applyNaturalImage(media,item,img){
  media.classList.remove('wall-media-smart-crop');item.classList.remove('wall-media-smart-crop-item');delete img.dataset.wallCropReady;
  [media,item].forEach(node=>{important(node,'width','100%');important(node,'height','auto');important(node,'min-height','0');important(node,'max-height','none');important(node,'margin','0');important(node,'padding','0');important(node,'aspect-ratio','auto');important(node,'transform','none');important(node,'inset','auto');});
  important(media,'display','block');important(media,'contain','layout paint');important(media,'overflow','hidden');
  important(item,'position','static');important(item,'display','block');important(item,'overflow','hidden');
  img.removeAttribute('width');img.removeAttribute('height');important(img,'position','static');important(img,'display','block');important(img,'width','100%');important(img,'height','auto');important(img,'min-height','0');important(img,'max-height','none');important(img,'margin','0');important(img,'padding','0');important(img,'aspect-ratio','auto');important(img,'object-fit','fill');important(img,'object-position','center');important(img,'transform','none');important(img,'inset','auto');important(img,'left','auto');important(img,'top','auto');
 }

 function applySmartCrop(media,item,img,crop){
  if(!crop)return;
  const widthPercent=(crop.naturalWidth/crop.width)*100,leftPercent=-(crop.x/crop.width)*100,topPercent=-(crop.y/crop.height)*100;
  media.classList.add('wall-media-smart-crop');item.classList.add('wall-media-smart-crop-item');img.dataset.wallCropReady='1';
  important(media,'height','auto');important(media,'aspect-ratio',`${crop.width}/${crop.height}`);important(media,'overflow','hidden');
  important(item,'position','relative');important(item,'height','100%');important(item,'aspect-ratio',`${crop.width}/${crop.height}`);important(item,'overflow','hidden');
  important(img,'position','absolute');important(img,'display','block');important(img,'width',`${widthPercent}%`);important(img,'height','auto');important(img,'max-width','none');important(img,'max-height','none');important(img,'left',`${leftPercent}%`);important(img,'top',`${topPercent}%`);important(img,'object-fit','fill');important(img,'transform','none');
 }

 function normalizeSingleMedia(media){
  if(!(media instanceof HTMLElement))return;
  const item=q('.wall-media-item',media),img=q('img',media);if(!item||!img)return;
  const apply=()=>{applyNaturalImage(media,item,img);if(img.dataset.wallCropChecked==='1'){const saved=img.dataset.wallCrop;if(saved){try{applySmartCrop(media,item,img,JSON.parse(saved));}catch(_){}}return;}img.dataset.wallCropChecked='1';requestAnimationFrame(()=>{const crop=detectBorderCrop(img);if(crop){img.dataset.wallCrop=JSON.stringify(crop);applySmartCrop(media,item,img,crop);}else img.dataset.wallCrop='';});};
  if(img.complete&&img.naturalWidth)apply();else img.addEventListener('load',apply,{once:true});
 }

 function normalizePost(post){
  if(!(post instanceof HTMLElement))return;
  important(post,'display','flex');important(post,'flex-direction','column');important(post,'align-items','stretch');important(post,'justify-content','flex-start');important(post,'align-content','flex-start');important(post,'height','auto');important(post,'min-height','0');important(post,'max-height','none');important(post,'padding','0');important(post,'gap','0');important(post,'grid-template','none');important(post,'grid-template-rows','none');
  [...post.children].forEach(child=>{important(child,'flex','0 0 auto');important(child,'flex-grow','0');important(child,'flex-shrink','0');important(child,'align-self','stretch');important(child,'height','auto');important(child,'min-height','0');important(child,'max-height','none');important(child,'grid-column','auto');important(child,'grid-row','auto');});
  const head=q(':scope > .wall-post-head',post);if(head){important(head,'height','64px');important(head,'min-height','64px');important(head,'max-height','64px');important(head,'margin','0');important(head,'padding','12px 14px');important(head,'overflow','hidden');}
  const media=q(':scope > .wall-media-grid.count-1',post);if(media){if(head&&head.nextElementSibling!==media)post.insertBefore(media,head.nextElementSibling);normalizeSingleMedia(media);}
  qa(':scope > .wall-post-text,:scope > .wall-native-videos,:scope > .wall-document-list,:scope > .wall-video-embeds',post).forEach(block=>{if(!block.textContent.trim()&&!block.querySelector('img,video,iframe,a')){block.hidden=true;important(block,'display','none');important(block,'height','0');important(block,'padding','0');important(block,'margin','0');}});
  const actions=q(':scope > .wall-post-actions',post);if(actions){important(actions,'margin','0');important(actions,'padding','7px 12px');}
 }
 function normalizePosts(root=document){if(root.matches?.('[data-wall-post]'))normalizePost(root);qa('[data-wall-post]',root).forEach(normalizePost);}

 function normalizeVkAvatar(root=document){
  if(document.body?.dataset.template!=='vk')return;
  const left=root.matches?.('.kov-vk-old-profile-left')?root:q('.kov-vk-old-profile-left',root)||q('.kov-vk-old-profile-left');
  if(left){important(left,'display','flex');important(left,'flex-direction','column');important(left,'align-items','stretch');important(left,'justify-content','flex-start');important(left,'align-content','flex-start');important(left,'gap','12px');[...left.children].forEach(child=>{important(child,'flex','0 0 auto');important(child,'flex-grow','0');important(child,'flex-shrink','0');important(child,'height','auto');important(child,'min-height','0');important(child,'max-height','none');important(child,'margin','0');});}
  const card=q('.kov-vk-old-avatar-card',root)||q('.kov-vk-old-avatar-card');const shell=q('.kov-vk-avatar-shell',root)||q('.kov-vk-avatar-shell');const button=q('.kov-vk-avatar-open',root)||q('.kov-vk-avatar-open');const img=q('.kov-vk-avatar-image',root)||q('.kov-vk-avatar-image');
  [card,shell,button].forEach(node=>{if(!node)return;important(node,'height','auto');important(node,'min-height','0');important(node,'max-height','none');important(node,'overflow',node===card||node===shell?'visible':'hidden');});if(!button||!img)return;
  const apply=()=>{important(button,'width','100%');important(button,'height','auto');important(button,'min-height','0');important(button,'max-height','none');important(button,'display','block');important(button,'padding','0');important(button,'margin','0');important(img,'position','static');important(img,'display','block');important(img,'width','100%');important(img,'height','auto');important(img,'min-height','0');important(img,'max-height','none');important(img,'object-fit','contain');important(img,'object-position','center top');important(img,'margin','0');important(img,'padding','0');important(img,'transform','none');};
  if(img.complete)apply();else img.addEventListener('load',apply,{once:true});
 }

 function closeHeaderMenus(except=null){
  qa('body.vk-app [data-kov-header-menu]').forEach(details=>{if(details===except)return;details.open=false;q(':scope > summary',details)?.setAttribute('aria-expanded','false');});
 }
 function positionHeaderMenu(details){
  if(!(details instanceof HTMLDetailsElement)||!details.open)return;
  const summary=q(':scope > summary',details),panel=q(':scope > .vk-drop-panel',details);if(!summary||!panel)return;
  const rect=summary.getBoundingClientRect();const gap=6;
  important(panel,'top',`${Math.max(50,Math.round(rect.bottom+gap))}px`);
  important(panel,'right',`${Math.max(8,Math.round(innerWidth-rect.right))}px`);
  important(panel,'left','auto');
 }
 function initHeaderMenus(root=document){
  if(document.body?.dataset.template!=='vk')return;
  const menus=root.matches?.('[data-kov-header-menu]')?[root]:qa('[data-kov-header-menu]',root);
  menus.forEach(details=>{
   if(!(details instanceof HTMLDetailsElement)||details.dataset.vkDropdownReady==='1')return;
   details.dataset.vkDropdownReady='1';
   const summary=q(':scope > summary',details),panel=q(':scope > .vk-drop-panel',details);if(!summary||!panel)return;
   summary.setAttribute('aria-haspopup','menu');summary.setAttribute('aria-expanded',details.open?'true':'false');
   summary.addEventListener('click',event=>{event.preventDefault();event.stopImmediatePropagation();const opening=!details.open;closeHeaderMenus(details);details.open=opening;summary.setAttribute('aria-expanded',opening?'true':'false');if(opening)requestAnimationFrame(()=>positionHeaderMenu(details));},true);
   panel.addEventListener('click',event=>{event.stopPropagation();const link=event.target.closest('a');if(link){details.open=false;summary.setAttribute('aria-expanded','false');}});
   details.addEventListener('toggle',()=>{summary.setAttribute('aria-expanded',details.open?'true':'false');if(details.open)requestAnimationFrame(()=>positionHeaderMenu(details));});
  });
  if(document.documentElement.dataset.vkDropdownGlobalReady!=='1'){
   document.documentElement.dataset.vkDropdownGlobalReady='1';
   document.addEventListener('click',event=>{if(!event.target.closest('[data-kov-header-menu]'))closeHeaderMenus();});
   document.addEventListener('keydown',event=>{if(event.key==='Escape')closeHeaderMenus();});
  }
 }

 function initWeather(root=document){const shell=root.matches?.('.weather-shell-170')?root:q('.weather-shell-170',root);if(!shell||shell.dataset.weatherScrollReady==='1')return;shell.dataset.weatherScrollReady='1';const center=q('.weather-page-170',shell);if(!center)return;center.tabIndex=0;center.setAttribute('role','region');center.setAttribute('aria-label','Прогноз погоды');shell.addEventListener('wheel',event=>{if(matchMedia('(max-width:800px)').matches||event.ctrlKey)return;if(event.target.closest('input[type="range"],#weather-radar-map,textarea,select'))return;const delta=event.deltaMode===1?event.deltaY*18:event.deltaMode===2?event.deltaY*center.clientHeight:event.deltaY;if(!delta)return;const before=center.scrollTop;center.scrollTop+=delta;if(center.scrollTop!==before)event.preventDefault();},{passive:false,capture:true});}
 function boot(root=document){normalizePosts(root);normalizeVkAvatar(root);initHeaderMenus(root);initWeather(root);}
 boot();document.addEventListener('DOMContentLoaded',()=>boot(),{once:true});document.addEventListener('kovcheg:pagechange',event=>boot(event.target||document));new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1)boot(node);}))).observe(document.documentElement,{childList:true,subtree:true});addEventListener('resize',()=>{normalizePosts();normalizeVkAvatar();qa('body.vk-app [data-kov-header-menu][open]').forEach(positionHeaderMenu);},{passive:true});
})();
