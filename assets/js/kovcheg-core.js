/* KOVCHEG CMS 3.0. */
if(window.__KOVCHEG_CORE_255_LOADED__){console.warn('KOVCHEG core bundle requested twice.');}else{window.__KOVCHEG_CORE_255_LOADED__=true;
/* KOVCHEG CMS 3.0. */
(() => {
'use strict';
const K=window.KOVCHEG||{};
let pageAbortController=new AbortController();
window.KovchegLifecycle={get signal(){return pageAbortController.signal;},onPage(handler){if(typeof handler!=='function')return()=>{};handler(pageAbortController.signal);const listener=()=>handler(pageAbortController.signal);document.addEventListener('kovcheg:pagechange',listener);return()=>document.removeEventListener('kovcheg:pagechange',listener);}};
document.addEventListener('kovcheg:pagebeforechange',()=>{pageAbortController.abort();pageAbortController=new AbortController();},{capture:true});
const qs=(s,r=document)=>r.querySelector(s);
const qsa=(s,r=document)=>[...r.querySelectorAll(s)];
const escapeHtml=(v)=>{const n=document.createElement('div');n.textContent=String(v??'');return n.innerHTML;};
const clamp=(v,min,max)=>Math.min(max,Math.max(min,v));
const debounce=(fn,wait=180)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),wait);};};
const jsonFetch=async(url,options={})=>{const r=await fetch(K.baseUrl+url,{credentials:'same-origin',headers:{Accept:'application/json',...(options.headers||{})},...options});const j=await r.json().catch(()=>({ok:false,error:'Сервер вернул некорректный ответ.'}));if(!r.ok||j.ok===false)throw new Error(j.error||'Ошибка запроса.');return j;};
const post=async(url,data={})=>jsonFetch(url,{method:'POST',headers:{'X-CSRF-Token':K.csrf},body:data instanceof FormData?data:new URLSearchParams(data)});
const basePath=(()=>{try{return new URL(K.baseUrl,location.href).pathname.replace(/\/$/,'');}catch(_){return '';}})();
const relativePath=(url)=>{const path=new URL(url,location.href).pathname;if(basePath&&basePath!=='/'&&path.startsWith(basePath))return path.slice(basePath.length)||'/';return path.startsWith('/')?path:`/${path}`;};
const toastStack=qs('#toast-stack');
function showToast(text,opt={}){if(!toastStack||!text)return null;const t=document.createElement('div');t.className=`message-toast generic-toast ${opt.type?`toast-${opt.type}`:''}`;t.innerHTML=`<span class="toast-icon">${escapeHtml(opt.icon||(opt.type==='error'?'!':opt.type==='success'?'✓':'i'))}</span><div><b>${escapeHtml(opt.title||(opt.type==='error'?'Ошибка':opt.type==='success'?'Готово':'KOVCHEG CMS'))}</b><p>${escapeHtml(text)}</p></div>`;if(opt.actionLabel&&typeof opt.action==='function'){const b=document.createElement('button');b.className='toast-action';b.textContent=opt.actionLabel;b.onclick=async(e)=>{e.stopPropagation();b.disabled=true;try{await opt.action();t.remove();}catch(err){showToast(err.message,{type:'error'});}};t.append(b);}const c=document.createElement('button');c.className='toast-close';c.textContent='×';c.onclick=()=>t.remove();t.append(c);toastStack.append(t);while(toastStack.children.length>Math.max(4,Number(K.notifications?.max||3)+2))toastStack.firstElementChild?.remove();if(!opt.persistent)setTimeout(()=>t.remove(),opt.timeout||6000);return t;}
(K.flash||[]).forEach(i=>showToast(i.text,{type:i.type==='error'?'error':'success'}));
document.addEventListener('error',e=>{const img=e.target;if(!(img instanceof HTMLImageElement)||img.dataset.fallbackApplied)return;img.dataset.fallbackApplied='1';const avatar=img.closest('.avatar,.avatar-xs,.avatar-sm,.avatar-lg,.avatar-photo,.profile-avatar,.message-avatar-link,[data-avatar]');if(avatar){img.src=`${K.baseUrl}/assets/icons/default-avatar.svg?v=${encodeURIComponent(K.version||'2.3.7')}`;return;}const cover=img.closest('.track-cover-play,.playlist-cover,.playlist-head-cover,.content-cover-preview,.playlist-editor-cover');if(cover){img.hidden=true;cover.classList.add('cover-load-error');const fallback=cover.querySelector('span');if(fallback)fallback.hidden=false;}},{capture:true});

function openModal(m){if(!m)return;m.hidden=false;requestAnimationFrame(()=>m.classList.add('open'));setTimeout(()=>qs('input[autofocus]',m)?.focus(),30);}
function closeModal(m){if(!m)return;m.classList.remove('open');setTimeout(()=>m.hidden=true,140);}
document.addEventListener('click',(e)=>{
 const modalButton=e.target.closest('[data-modal]');if(modalButton){openModal(qs(`#${modalButton.dataset.modal}`));return;}
 const close=e.target.closest('.modal-close');if(close){closeModal(close.closest('.modal'));return;}
 if(e.target.classList?.contains('modal'))closeModal(e.target);
 const details=e.target.closest('[data-toggle-details]');if(details)qs('[data-chat-details]')?.classList.toggle('open');
});
const staticTabs=qs('[data-tabs]');
if(staticTabs){const open=(name)=>{qsa('[data-tab]',staticTabs).forEach(b=>b.classList.toggle('active',b.dataset.tab===name));qsa('[data-panel]').forEach(p=>p.classList.toggle('active',p.dataset.panel===name));history.replaceState(history.state,'',`${location.pathname}${location.search}#${name}`);};qsa('[data-tab]',staticTabs).forEach(b=>b.onclick=()=>open(b.dataset.tab));const h=location.hash.slice(1);if(h)open(h);}

let messenger=qs('.messenger');
let lastEvent=Number(messenger?.dataset.eventCursor||0);
let activeThread=0;
let syncBusy=false;
let syncTimer=null;
let selectedFiles=[];
let cropQueue=[];
let cropCurrent=null;
let cropUrl='';
let audioContext;
let notificationCount=0;
const emojis=['😀','😃','😄','😁','😆','😅','😂','🤣','😊','🙂','🙃','😉','😍','🥰','😘','😎','🤔','😐','😶','🙄','😢','😭','😡','🤯','🥳','👍','👎','👏','🙏','🤝','💪','🔥','❤️','💙','💚','💯','🎉','✅','❌','📎','📌','🚀','💡','👀','⚡','🌙','☀️'];

const currentChatId=()=>Number(messenger?.dataset.chatId||0);
const messageScrollStates=new WeakMap();
function messageBottomDistance(root){return Math.max(0,root.scrollHeight-root.scrollTop-root.clientHeight);}
function bindMessageScroll(root,initialBottom=false){
 if(!root)return null;let state=messageScrollStates.get(root);
 if(!state){
  state={pinned:messageBottomDistance(root)<=72,userActive:false,timer:0};messageScrollStates.set(root,state);
  const mark=()=>{state.userActive=true;clearTimeout(state.timer);state.timer=setTimeout(()=>state.userActive=false,700);};
  const update=()=>{state.pinned=messageBottomDistance(root)<=72;};
  root.addEventListener('wheel',mark,{passive:true});root.addEventListener('touchstart',mark,{passive:true});root.addEventListener('touchmove',mark,{passive:true});root.addEventListener('pointerdown',mark,{passive:true});root.addEventListener('scroll',update,{passive:true});
 }
 if(initialBottom)requestAnimationFrame(()=>{root.scrollTop=root.scrollHeight;state.pinned=true;});
 return state;
}
function htmlNodes(html){const t=document.createElement('template');t.innerHTML=(html||'').trim();return [...t.content.children];}
function upsertMessage(html,scroll=true,root=qs('#messages')){
 if(!root||!html)return;const state=bindMessageScroll(root,false),stick=Boolean(scroll&&state?.pinned&&!state?.userActive);
 const oldTop=root.scrollTop;let anchor=null,anchorOffset=0;
 if(!stick){anchor=[...root.querySelectorAll('.message[data-message-id]')].find(node=>node.offsetTop+node.offsetHeight>=oldTop+1)||null;if(anchor)anchorOffset=anchor.offsetTop-oldTop;}
 htmlNodes(html).forEach(n=>{const old=qs(`.message[data-message-id="${n.dataset.messageId}"]`,root);old?old.replaceWith(n):root.append(n);});
 if(stick)requestAnimationFrame(()=>{root.scrollTop=root.scrollHeight;if(state)state.pinned=true;});
 else if(anchor){const current=qs(`.message[data-message-id="${anchor.dataset.messageId}"]`,root)||anchor;if(root.contains(current))root.scrollTop=Math.max(0,current.offsetTop-anchorOffset);}
}
function resizeTextarea(t){if(!t)return;t.style.height='auto';t.style.height=`${Math.min(t.scrollHeight,150)}px`;}
function composerParts(){const f=qs('#composer');return{form:f,textarea:qs('textarea',f||document),reply:qs('input[name="reply_to_id"]',f||document),edit:qs('input[name="edit_message_id"]',f||document),commentBatch:qs('input[name="comment_message_ids"]',f||document),context:qs('[data-composer-context]',f||document),title:qs('[data-context-title]',f||document),text:qs('[data-context-text]',f||document)};}
function clearComposer(){const p=composerParts();if(p.reply)p.reply.value='';if(p.edit)p.edit.value='';if(p.commentBatch)p.commentBatch.value='';if(p.context)p.context.hidden=true;p.form?.classList.remove('editing','replying','commenting-batch');}
function setComposer(mode,id,title,text){const p=composerParts();if(!p.form)return;clearComposer();if(mode==='edit'&&p.edit)p.edit.value=String(id);if(mode==='reply'&&p.reply)p.reply.value=String(id);p.form.classList.add(mode==='edit'?'editing':'replying');if(p.title)p.title.textContent=title;if(p.text)p.text.textContent=text||'Вложение';if(p.context)p.context.hidden=false;p.textarea?.focus();}
function closePickers(){qsa('.emoji-picker,.sticker-picker,.reaction-menu,[data-composer-attach-menu]').forEach(x=>x.hidden=true);qsa('[data-composer-attach-toggle]').forEach(x=>x.setAttribute('aria-expanded','false'));}
function initPickers(){const ep=qs('.emoji-picker');if(ep&&!ep.dataset.ready){ep.dataset.ready='1';ep.innerHTML=emojis.map(x=>`<button type="button">${x}</button>`).join('');ep.addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;const t=composerParts().textarea;if(!t)return;const a=t.selectionStart??t.value.length,z=t.selectionEnd??a;t.setRangeText(b.textContent||'',a,z,'end');resizeTextarea(t);t.focus();});}}
function renderFiles(){const box=qs('.file-preview');if(!box)return;box.innerHTML=selectedFiles.map((f,i)=>({f,i})).filter(x=>x.f!==voiceFile).map(({f,i})=>`<span class="file-chip">${escapeHtml(f.name)} <button type="button" data-remove-file="${i}">×</button></span>`).join('');}
function addFiles(files){const added=[...files].filter(f=>f instanceof File);selectedFiles.push(...added);cropQueue.push(...added.filter(f=>f.type.startsWith('image/')));renderFiles();openNextCrop();}
function positionCrop(img,range){if(!img?.naturalWidth||!img.parentElement)return;const size=img.parentElement.clientWidth,scale=Number(range?.value||1),ratio=Math.max(size/img.naturalWidth,size/img.naturalHeight)*scale;img.style.width=`${img.naturalWidth*ratio}px`;img.style.height=`${img.naturalHeight*ratio}px`;img.style.left=`${(size-img.naturalWidth*ratio)/2}px`;img.style.top=`${(size-img.naturalHeight*ratio)/2}px`;}
function openNextCrop(){const modal=qs('[data-chat-crop-modal]'),img=qs('[data-chat-crop-image]'),range=qs('[data-chat-crop-scale]');if(!modal||!img||cropCurrent||!cropQueue.length)return;cropCurrent=cropQueue.shift();if(!selectedFiles.includes(cropCurrent)){cropCurrent=null;return openNextCrop();}if(cropUrl)URL.revokeObjectURL(cropUrl);cropUrl=URL.createObjectURL(cropCurrent);range.value='1';img.src=cropUrl;img.onload=()=>positionCrop(img,range);modal.hidden=false;}
function finishCrop(){if(cropUrl)URL.revokeObjectURL(cropUrl);cropUrl='';cropCurrent=null;const m=qs('[data-chat-crop-modal]');if(m)m.hidden=true;setTimeout(openNextCrop);}
function cropApply(){const img=qs('[data-chat-crop-image]'),stage=img?.parentElement;if(!img||!stage||!cropCurrent)return;const canvas=document.createElement('canvas');canvas.width=1200;canvas.height=1200;const r=1200/stage.clientWidth;canvas.getContext('2d').drawImage(img,parseFloat(img.style.left)*r,parseFloat(img.style.top)*r,parseFloat(img.style.width)*r,parseFloat(img.style.height)*r);canvas.toBlob(blob=>{if(blob){const i=selectedFiles.indexOf(cropCurrent);if(i>=0)selectedFiles[i]=new File([blob],cropCurrent.name.replace(/\.[^.]+$/,'.jpg'),{type:'image/jpeg'});renderFiles();}finishCrop();},'image/jpeg',.9);}
async function submitComposer(form){const p=composerParts();const batchIds=String(p.commentBatch?.value||'').split(',').map(Number).filter(Boolean);if(batchIds.length){const body=p.textarea?.value.trim()||'';if(!body)throw new Error('Добавьте комментарий к выбранным сообщениям.');const fd=new FormData();fd.append('chat_id',String(currentChatId()));fd.append('body',body);batchIds.forEach(id=>fd.append('message_ids[]',String(id)));const r=await post('/ajax/messages/comment-batch',fd);if(r.html)upsertMessage(r.html,true);lastEvent=Math.max(lastEvent,Number(r.event_cursor||0));p.textarea.value='';resizeTextarea(p.textarea);clearComposer();return;}const editId=Number(p.edit?.value||0);if(editId){const body=p.textarea?.value.trim()||'';if(!body)throw new Error('Введите текст.');const r=await post(`/ajax/message/${editId}/edit`,{body});if(r.html)upsertMessage(r.html,false);p.textarea.value='';resizeTextarea(p.textarea);clearComposer();return;}
 if(mediaRecorder?.state==='recording'){const ready=await finalizeVoiceRecording();if(!ready)throw new Error('Не удалось завершить запись голосового сообщения.');}
 const fd=new FormData(form);fd.delete('files[]');selectedFiles.filter(f=>f!==voiceFile).forEach(f=>fd.append('files[]',f,f.name));if(voiceFile)fd.append('voice',voiceFile,voiceFile.name);const r=await post('/ajax/send',fd);if(r.html)upsertMessage(r.html,!r.is_comment);lastEvent=Math.max(lastEvent,Number(r.event_cursor||0));form.reset();document.dispatchEvent(new CustomEvent('kovcheg:track-selection-clear',{detail:{form}}));selectedFiles=[];cropQueue=[];voiceFile=null;setVoiceRecordingUi(false);renderFiles();clearComposer();resizeTextarea(p.textarea);
}
let mediaRecorder=null,voiceChunks=[],voiceStream=null,voiceStartedAt=0,voiceTimerId=0,voiceFile=null,voiceStopPromise=null,voiceStopResolve=null,voiceCancelled=false;
function formatMediaTime(sec){sec=Math.max(0,Math.floor(Number(sec)||0));return `${Math.floor(sec/60)}:${String(sec%60).padStart(2,'0')}`;}
function voicePanelParts(){const panel=qs('[data-voice-panel]'),form=qs('#composer');return{panel,timer:qs('[data-voice-timer]',panel||document),textarea:qs('textarea',form||document),form};}
function setVoiceRecordingUi(active){const p=voicePanelParts();if(p.panel)p.panel.hidden=!active;if(p.textarea)p.textarea.hidden=active;p.form?.classList.toggle('voice-recording',active);qsa('[data-voice]').forEach(b=>{b.classList.toggle('recording',active);b.setAttribute('aria-pressed',String(active));b.title=active?'Отправить записанное голосовое':'Голосовое сообщение';});}
function stopVoiceTracks(){voiceStream?.getTracks().forEach(t=>t.stop());voiceStream=null;clearInterval(voiceTimerId);voiceTimerId=0;}
function clearVoiceFile(){voiceFile=null;voiceChunks=[];}
function discardVoice(){voiceCancelled=true;if(mediaRecorder&&mediaRecorder.state!=='inactive'){try{mediaRecorder.stop();}catch(_){}}else{mediaRecorder=null;stopVoiceTracks();clearVoiceFile();setVoiceRecordingUi(false);}}
function completeVoiceStop(){const recorder=mediaRecorder,type=recorder?.mimeType||'audio/webm',blob=new Blob(voiceChunks,{type}),cancelled=voiceCancelled;mediaRecorder=null;stopVoiceTracks();setVoiceRecordingUi(false);let ok=false;if(!cancelled&&blob.size>0){voiceFile=new File([blob],`voice-${Date.now()}.${type.includes('ogg')?'ogg':'webm'}`,{type});ok=true;}else clearVoiceFile();const resolve=voiceStopResolve;voiceStopResolve=null;voiceStopPromise=null;voiceCancelled=false;resolve?.(ok);}
function finalizeVoiceRecording(){if(voiceFile)return Promise.resolve(true);if(!mediaRecorder||mediaRecorder.state!=='recording')return Promise.resolve(false);if(voiceStopPromise)return voiceStopPromise;voiceCancelled=false;voiceStopPromise=new Promise(resolve=>{voiceStopResolve=resolve;});try{mediaRecorder.stop();}catch(_){const resolve=voiceStopResolve;voiceStopResolve=null;voiceStopPromise=null;resolve?.(false);}return voiceStopPromise;}
async function toggleVoice(button){
 if(!navigator.mediaDevices?.getUserMedia||typeof MediaRecorder==='undefined')throw new Error('Запись голоса не поддерживается этим браузером.');
 if(mediaRecorder&&mediaRecorder.state==='recording'){button.closest('form')?.requestSubmit();return;}
 discardVoice();voiceCancelled=false;voiceStream=await navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true,autoGainControl:true}});voiceChunks=[];const options=MediaRecorder.isTypeSupported?.('audio/webm;codecs=opus')?{mimeType:'audio/webm;codecs=opus'}:{};mediaRecorder=new MediaRecorder(voiceStream,options);mediaRecorder.ondataavailable=e=>{if(e.data.size)voiceChunks.push(e.data);};mediaRecorder.onstop=completeVoiceStop;mediaRecorder.start(250);voiceStartedAt=Date.now();const p=voicePanelParts();if(p.timer)p.timer.textContent='0:00';setVoiceRecordingUi(true);voiceTimerId=setInterval(()=>{if(p.timer)p.timer.textContent=formatMediaTime((Date.now()-voiceStartedAt)/1000);},250);p.textarea?.blur();
}
document.addEventListener('click',event=>{if(event.target.closest('[data-composer-attach-wrap]'))return;qsa('[data-composer-attach-menu]').forEach(menu=>menu.hidden=true);qsa('[data-composer-attach-toggle]').forEach(toggle=>toggle.setAttribute('aria-expanded','false'));});
document.addEventListener('keydown',event=>{if(event.key!=='Escape')return;qsa('[data-composer-attach-menu]').forEach(menu=>menu.hidden=true);qsa('[data-composer-attach-toggle]').forEach(toggle=>toggle.setAttribute('aria-expanded','false'));});

function initConversation(){
 messenger=qs('.messenger');initPickers();selectedFiles=[];cropQueue=[];renderFiles();
 const messages=qs('#messages');if(messages){const firstBind=messages.dataset.conversationScrollBound!=='1';messages.dataset.conversationScrollBound='1';bindMessageScroll(messages,firstBind);const focus=Number(messenger?.dataset.focusMessage||0);if(focus){setTimeout(()=>{const m=qs(`#m${focus}`);m?.scrollIntoView({behavior:'auto',block:'center'});m?.classList.add('message-highlight');setTimeout(()=>m?.classList.remove('message-highlight'),1800);},80);messenger.dataset.focusMessage='0';}}
 const comp=qs('#composer');const ta=qs('textarea',comp||document);ta?.addEventListener('input',()=>resizeTextarea(ta));ta?.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();comp?.requestSubmit();}});
 comp?.addEventListener('submit',async e=>{e.preventDefault();const b=qs('button[type=submit]',comp);if(b)b.disabled=true;try{await submitComposer(comp);}catch(err){showToast(err.message,{type:'error'});}finally{if(b)b.disabled=false;}});
 qsa('.file-pick input',comp||document).forEach(input=>input.addEventListener('change',()=>{addFiles(input.files);input.value='';const menu=qs('[data-composer-attach-menu]',comp||document),toggle=qs('[data-composer-attach-toggle]',comp||document);if(menu)menu.hidden=true;if(toggle)toggle.setAttribute('aria-expanded','false');}));const voice=qs('[data-voice]',comp||document);voice?.addEventListener('click',async()=>{try{await toggleVoice(voice);}catch(err){discardVoice();showToast(err.message,{type:'error'});}});qs('[data-voice-cancel]',comp||document)?.addEventListener('click',discardVoice);
 if(messages){['dragenter','dragover'].forEach(n=>messages.addEventListener(n,e=>{e.preventDefault();messages.classList.add('dragging');}));['dragleave','drop'].forEach(n=>messages.addEventListener(n,e=>{e.preventDefault();messages.classList.remove('dragging');}));messages.addEventListener('drop',e=>addFiles(e.dataTransfer.files));}
 initUserPickers(qs('[data-conversation-shell]')||document);initChannelControls();initColumnResizers();
}
async function openChat(id,opt={}){id=Number(id);if(!id||!messenger)return;const shell=qs('[data-conversation-shell]');if(!shell)return;shell.classList.add('loading');try{const r=await jsonFetch(`/ajax/chat/${id}`);shell.innerHTML=r.html;messenger.dataset.chatId=String(id);lastEvent=Math.max(lastEvent,Number(r.event_cursor||0));qsa('[data-chat-item]').forEach(x=>x.classList.toggle('active',Number(x.dataset.chatItem)===id));const badge=qs(`[data-chat-item="${id}"] [data-chat-unread]`);badge?.remove();if(!opt.pop)history.pushState({chatId:id},'',`${r.chat_url}${opt.messageId?`#m${opt.messageId}`:''}`);document.title=`${r.title} — KOVCHEG CMS`;initConversation();if(opt.messageId)setTimeout(()=>{const m=qs(`#m${opt.messageId}`);m?.scrollIntoView({behavior:'smooth',block:'center'});m?.classList.add('message-highlight');setTimeout(()=>m?.classList.remove('message-highlight'),1600);},100);}catch(err){showToast(err.message,{type:'error'});}finally{shell.classList.remove('loading');}}
if(messenger)history.replaceState({chatId:currentChatId()},'',location.href);window.addEventListener('popstate',e=>{const id=Number(e.state?.chatId||qs(`[data-chat-item][href="${CSS.escape(location.pathname)}"]`)?.dataset.chatItem||0);if(id)openChat(id,{pop:true});else if(location.pathname.endsWith('/messages')){const shell=qs('[data-conversation-shell]');if(shell){shell.innerHTML='<section class="conversation"><div class="empty-state"><div class="logo-large">K</div><h2>Выберите переписку</h2><p>Нажмите на коллегу или найдите его через поиск.</p></div></section>';messenger.dataset.chatId='0';qsa('[data-chat-item]').forEach(x=>x.classList.remove('active'));}}});

function updateChat(u){let item=qs(`[data-chat-item="${u.id}"]`);if(!item)return;const p=qs('[data-chat-preview]',item);if(p)p.textContent=u.last_body||'Нет сообщений';const time=qs('[data-chat-time]',item);if(time)time.textContent=u.last_at?new Date(u.last_at.replace(' ','T')).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}):'';let badge=qs('[data-chat-unread]',item),count=Number(u.unread||0);if(count>0){if(!badge){badge=document.createElement('em');badge.dataset.chatUnread='';qs('.chat-side',item)?.append(badge);}badge.textContent=String(count);badge.hidden=false;}else badge?.remove();item.dataset.pinned=String(Number(u.is_pinned||0));item.dataset.muted=String(Number(u.is_muted||0));const list=qs('[data-chat-items]');if(list&&Number(u.is_archived||0)===0)list.prepend(item);}
function urlBase64ToUint8Array(value){const pad='='.repeat((4-value.length%4)%4),base64=(value+pad).replace(/-/g,'+').replace(/_/g,'/'),raw=atob(base64),out=new Uint8Array(raw.length);for(let i=0;i<raw.length;i++)out[i]=raw.charCodeAt(i);return out;}
async function ensurePushSubscription(interactive=false){
 if(!K.pushPublicKey||!('serviceWorker'in navigator)||!('PushManager'in window)||!('Notification'in window))throw new Error('Web Push недоступен в этом браузере.');
 if(!window.isSecureContext)throw new Error('Для Push-уведомлений откройте систему по HTTPS.');
 if(interactive&&Notification.permission!=='granted'){const permission=await Notification.requestPermission();if(permission!=='granted')throw new Error('Разрешение на уведомления не выдано.');}
 if(Notification.permission!=='granted')return null;
 const reg=await navigator.serviceWorker.ready;let sub=await reg.pushManager.getSubscription();if(!sub)sub=await reg.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:urlBase64ToUint8Array(K.pushPublicKey)});
 await post('/ajax/push/subscribe',{subscription:JSON.stringify(sub.toJSON())});return sub;
}
function notificationCopy(n){const mode=K.notifications?.preview||'full';if(mode==='full')return{title:n.sender_name||n.chat_title||'Новое сообщение',text:n.text||''};if(mode==='sender')return{title:n.sender_name||n.chat_title||'Новое сообщение',text:'Текст скрыт'};if(mode==='count')return{title:`Новых сообщений: ${++notificationCount}`,text:'Содержимое скрыто'};return{title:'Новое сообщение',text:'Откройте систему'};}
function playSound(){if(!K.notifications?.sound)return;try{audioContext=audioContext||new(window.AudioContext||window.webkitAudioContext)();const o=audioContext.createOscillator(),g=audioContext.createGain();o.frequency.value=820;g.gain.value=.07;o.connect(g).connect(audioContext.destination);o.start();g.gain.exponentialRampToValueAtTime(.0001,audioContext.currentTime+.18);o.stop(audioContext.currentTime+.2);}catch(_){}}
async function swNotify(title,body,url,icon,tag){if(!K.notifications?.desktop||Notification.permission!=='granted'||!('serviceWorker'in navigator))return;const reg=await navigator.serviceWorker.ready.catch(()=>null);reg?.active?.postMessage({type:'SHOW_NOTIFICATION',payload:{title,body,url,icon,tag}});}
function notify(n){if(!K.notifications?.enabled)return;playSound();const c=notificationCopy(n);const t=document.createElement('div');t.className='message-toast incoming-toast';const a=document.createElement('span');a.className='avatar-sm';if(K.notifications.avatar&&n.avatar_url&&['full','sender'].includes(K.notifications.preview||'full')){a.classList.add('avatar-photo');a.innerHTML=`<img src="${escapeHtml(n.avatar_url)}" alt="">`;}else a.textContent='K';t.innerHTML='';const d=document.createElement('div');d.innerHTML=`<b>${escapeHtml(c.title)}</b><p>${escapeHtml(c.text)}</p>`;const x=document.createElement('button');x.className='toast-close';x.textContent='×';x.onclick=e=>{e.stopPropagation();t.remove();};t.append(a,d,x);t.onclick=()=>{if(messenger)openChat(n.chat_id,{messageId:n.root_id||n.message_id});else location.href=n.url||`${K.baseUrl}/messages`;};toastStack?.append(t);while(toastStack&&toastStack.children.length>Math.max(1,Number(K.notifications.max||3)))toastStack.firstElementChild?.remove();setTimeout(()=>t.remove(),8000);swNotify(c.title,c.text,n.url||`${K.baseUrl}/messages`,K.notifications.avatar?n.avatar_url:'',`chat-${n.chat_id}`);}
window.KovchegIncomingNotify=notify;window.KovchegShowToast=showToast;window.KovchegPlaySound=playSound;
function appendUserNotification(note){const list=qs('[data-notification-list]');if(!list)return;qs('.empty-notes',list)?.remove();if(qs(`[data-notification-id="${note.id}"]`,list))return;const a=document.createElement('a');a.className='notification-note unread';a.dataset.notificationId=note.id;a.href=note.url||`${K.baseUrl}/app`;const icon=note.icon?`<img src="${escapeHtml(note.icon)}" alt="">`:(note.type==='message'?'💬':note.type==='social'?'👥':'🔔');a.innerHTML=`<span class="notification-note-icon">${icon}</span><div><b>${escapeHtml(note.title)}</b><p>${escapeHtml(note.body||'')}</p><small>сейчас</small></div>`;list.prepend(a);}
async function sync(){if(syncBusy||!messenger)return;syncBusy=true;try{const r=await post('/ajax/sync',{after_event:lastEvent,active_chat:currentChatId(),active_thread:activeThread,after_notification:K.notificationLast||0});lastEvent=Math.max(lastEvent,Number(r.last_event||0));(r.events||[]).forEach(ev=>{if(Number(ev.chat_id)!==currentChatId())return;if(ev.type==='message.deleted'){qs(`.message[data-message-id="${ev.message_id}"]`)?.remove();return;}if(ev.comment_html&&activeThread)upsertMessage(ev.comment_html,true,qs('[data-thread-messages]'));else if(ev.html)upsertMessage(ev.html,ev.type==='message.created');if(ev.root_id){const b=qs(`[data-open-comments="${ev.root_id}"] span`);if(b){const n=(Number((b.textContent.match(/\d+/)||[0])[0])||0)+1;b.textContent=`${n} комментариев`;}}});(r.chat_updates||[]).forEach(updateChat);(r.notifications||[]).forEach(notify);const count=qs('[data-notification-bell-count]');if(count){count.textContent=String(r.user_unread||0);count.hidden=Number(r.user_unread||0)===0;}(r.user_notifications||[]).forEach(n=>{K.notificationLast=Math.max(Number(K.notificationLast||0),Number(n.id));appendUserNotification(n);});}catch(_){}finally{syncBusy=false;scheduleSync();}}
function scheduleSync(){clearTimeout(syncTimer);const n=Math.max(3000,Number(K.polling||3000));syncTimer=setTimeout(sync,document.hidden?Math.max(2500,n*3):n);}
function activateConversationPage(){
 clearTimeout(syncTimer);syncTimer=null;syncBusy=false;activeThread=0;messenger=qs('.messenger');
 if(!messenger)return;
 lastEvent=Number(messenger.dataset.eventCursor||lastEvent||0);initConversation();scheduleSync();
 try{history.replaceState({...history.state,chatId:currentChatId()},'',location.href);}catch(_){}
}
activateConversationPage();
document.addEventListener('kovcheg:pagechange',activateConversationPage);
document.addEventListener('visibilitychange',()=>{if(!document.hidden&&messenger)sync();});

document.addEventListener('click',async e=>{
 const back=e.target.closest('.mobile-back');if(back){e.preventDefault();if(messenger){messenger.dataset.chatId='0';qsa('[data-chat-item]').forEach(x=>x.classList.remove('active'));history.pushState({chatId:0},'',`${K.baseUrl}/messages`);}return;}
 const chat=e.target.closest('[data-chat-item]');if(chat&&!e.ctrlKey&&!e.metaKey){e.preventDefault();closeChatContext();openChat(chat.dataset.chatItem);return;}
 const cancel=e.target.closest('[data-context-cancel]');if(cancel){const p=composerParts();if(p.form?.classList.contains('editing')||p.form?.classList.contains('commenting-batch')){if(p.textarea){p.textarea.value='';resizeTextarea(p.textarea);}}clearComposer();return;}
 const emoji=e.target.closest('[data-emoji]');if(emoji){e.stopPropagation();const p=qs('.emoji-picker'),h=p?.hidden;closePickers();if(p)p.hidden=!h;return;}
 const stickers=e.target.closest('[data-stickers]');if(stickers){e.stopPropagation();const p=qs('.sticker-picker'),h=p?.hidden;closePickers();if(p)p.hidden=!h;return;}
 const sticker=e.target.closest('[data-sticker-code]');if(sticker){const fd=new FormData();fd.append('chat_id',String(currentChatId()));fd.append('sticker_code',sticker.dataset.stickerCode||'');try{const r=await post('/ajax/send',fd);upsertMessage(r.html);lastEvent=Math.max(lastEvent,Number(r.event_cursor||0));closePickers();}catch(err){showToast(err.message,{type:'error'});}return;}
 const attachToggle=e.target.closest('[data-composer-attach-toggle]');if(attachToggle){e.preventDefault();e.stopPropagation();const wrap=attachToggle.closest('[data-composer-attach-wrap]'),menu=qs('[data-composer-attach-menu]',wrap||document),open=!!menu?.hidden;closePickers();if(menu)menu.hidden=!open;attachToggle.setAttribute('aria-expanded',String(open));return;}
 const attachAction=e.target.closest('.composer-attach-action');if(attachAction){const menu=attachAction.closest('[data-composer-attach-menu]'),toggle=qs('[data-composer-attach-toggle]',attachAction.closest('[data-composer-attach-wrap]')||document);setTimeout(()=>{if(menu)menu.hidden=true;if(toggle)toggle.setAttribute('aria-expanded','false');},0);}
 const removeFile=e.target.closest('[data-remove-file]');if(removeFile){selectedFiles.splice(Number(removeFile.dataset.removeFile),1);renderFiles();return;}
 const reply=e.target.closest('[data-reply]');if(reply){const m=reply.closest('.message');setComposer('reply',m.dataset.messageId,`Ответ · ${qs('.message-head strong',m)?.textContent||''}`,m.dataset.messageBody||'Вложение');return;}
 const edit=e.target.closest('[data-edit]');if(edit){const m=edit.closest('.message'),t=composerParts().textarea;if(!t)return;t.value=m.dataset.messageBody||'';resizeTextarea(t);setComposer('edit',m.dataset.messageId,'Редактирование',t.value);return;}
 const del=e.target.closest('[data-delete]');if(del){const m=del.closest('.message'),id=m.dataset.messageId;try{await post(`/ajax/message/${id}/delete`,{});m.remove();showToast('Сообщение удалено.',{actionLabel:'Отменить',timeout:9000,action:async()=>{const r=await post(`/ajax/message/${id}/restore`,{});upsertMessage(r.html,false,m.closest('[data-thread-messages]')||qs('#messages'));}});}catch(err){showToast(err.message,{type:'error'});}return;}
 const reactMenu=e.target.closest('[data-reaction-menu]');if(reactMenu){e.stopPropagation();const p=qs('.reaction-menu',reactMenu.closest('.message')),h=p?.hidden;closePickers();if(p)p.hidden=!h;return;}
 const react=e.target.closest('[data-react]');if(react){e.stopPropagation();const m=react.closest('.message');try{const r=await post(`/ajax/message/${m.dataset.messageId}/react`,{emoji:react.dataset.react||'👍'});upsertMessage(r.html,false,m.closest('[data-thread-messages]')||qs('#messages'));}catch(err){showToast(err.message,{type:'error'});}return;}
 const forward=e.target.closest('[data-forward]');if(forward){const f=qs('[data-forward-form]');if(f){f.elements.message_id.value=forward.closest('.message').dataset.messageId;f.elements.user_id.value='';const forwardSearch=qs('[data-user-search]',f),forwardResults=qs('[data-user-results]',f);if(forwardSearch)forwardSearch.value='';if(forwardResults)forwardResults.innerHTML='';qs('[data-user-submit]',f).disabled=true;}openModal(qs('#forward-message'));return;}
 const light=e.target.closest('[data-lightbox-src]');if(light){openLightbox(light.dataset.lightboxSrc,light.dataset.lightboxName||'photo');return;}
 const comments=e.target.closest('[data-open-comments]');if(comments){openThread(Number(comments.dataset.openComments));return;}
 const threadClose=e.target.closest('[data-thread-close]');if(threadClose){closeThread();return;}
 const removeMember=e.target.closest('[data-member-action="remove"]');if(removeMember){try{await post(`/ajax/channel/${currentChatId()}/members/${removeMember.dataset.memberId}/remove`,{});removeMember.closest('.member')?.remove();showToast('Подписчик удалён.',{type:'success'});}catch(err){showToast(err.message,{type:'error'});}return;}
 const join=e.target.closest('[data-join-request]');if(join){try{await post(`/ajax/channel/${currentChatId()}/join-request/${join.dataset.requestId}`,{action:join.dataset.joinRequest});join.closest('.join-request')?.remove();showToast('Заявка обработана.',{type:'success'});}catch(err){showToast(err.message,{type:'error'});}return;}
 const revoke=e.target.closest('[data-invite-revoke]');if(revoke){try{await post(`/ajax/channel/${currentChatId()}/invite/${revoke.dataset.inviteRevoke}/revoke`,{});revoke.closest('.invite-link')?.remove();showToast('Ссылка отключена.',{type:'success'});}catch(err){showToast(err.message,{type:'error'});}return;}
 const copy=e.target.closest('[data-copy-value]');if(copy){navigator.clipboard?.writeText(copy.dataset.copyValue||'');showToast('Ссылка скопирована.',{type:'success'});return;}
 if(!e.target.closest('.picker,.reaction-menu'))closePickers();
});

qs('[data-forward-form]')?.addEventListener('submit',async e=>{e.preventDefault();const f=e.currentTarget,id=f.elements.message_id.value,user=f.elements.user_id.value;if(!user)return showToast('Выберите получателя.',{type:'error'});try{const r=await post(`/ajax/message/${id}/forward`,{user_id:user});closeModal(qs('#forward-message'));showToast('Сообщение переслано.',{type:'success',actionLabel:'Открыть',action:()=>openChat(r.chat_id)});}catch(err){showToast(err.message,{type:'error'});}});
qs('[data-open-direct]')?.addEventListener('submit',async e=>{e.preventDefault();const f=e.currentTarget,user=f.elements.user_id.value;if(!user)return;try{const r=await post('/ajax/chat/direct',{user_id:user});closeModal(qs('#new-direct'));await openChat(r.chat_id);}catch(err){showToast(err.message,{type:'error'});}});
qs('[data-create-channel]')?.addEventListener('submit',async e=>{e.preventDefault();const f=e.currentTarget,b=qs('button[type=submit]',f);if(b)b.disabled=true;try{const r=await post(relativePath(f.action),new FormData(f));closeModal(qs('#new-channel'));f.reset();await openChat(r.chat_id);}catch(err){showToast(err.message,{type:'error'});}finally{if(b)b.disabled=false;}});

function openLightbox(src,name){const box=qs('[data-lightbox]'),img=qs('[data-lightbox-image]'),dl=qs('[data-lightbox-download]');if(!box||!img)return;img.src=src;img.alt=name;if(dl){dl.href=src;dl.download=name;}box.hidden=false;requestAnimationFrame(()=>box.classList.add('open'));}
function closeLightbox(){const b=qs('[data-lightbox]');b?.classList.remove('open');setTimeout(()=>{if(b)b.hidden=true;const i=qs('[data-lightbox-image]');if(i)i.src='';},140);}
qs('[data-lightbox-close]')?.addEventListener('click',closeLightbox);qs('[data-lightbox]')?.addEventListener('click',e=>{if(e.target===e.currentTarget)closeLightbox();});
qs('[data-chat-crop-scale]')?.addEventListener('input',e=>positionCrop(qs('[data-chat-crop-image]'),e.target));qs('[data-chat-crop-skip]')?.addEventListener('click',finishCrop);qs('[data-chat-crop-apply]')?.addEventListener('click',cropApply);

async function openThread(rootId){const d=qs('[data-thread-drawer]');if(!d)return;try{const r=await jsonFetch(`/ajax/message/${rootId}/comments`);activeThread=Number(r.root_id||rootId);qs('[data-thread-title]').textContent=r.title;qs('[data-thread-subtitle]').textContent=r.subtitle;const root=qs('[data-thread-root]');if(root)root.innerHTML=r.root_html||'';qs('[data-thread-messages]').innerHTML=r.html||'<div class="empty-thread">Комментариев пока нет.</div>';const f=qs('[data-thread-form]');f.elements.chat_id.value=r.chat_id;f.elements.reply_to_id.value=activeThread;d.hidden=false;requestAnimationFrame(()=>{d.classList.add('open');qs('[data-thread-messages]')?.scrollTo({top:0});});}catch(err){showToast(err.message,{type:'error'});}}
function closeThread(){activeThread=0;const d=qs('[data-thread-drawer]');d?.classList.remove('open');setTimeout(()=>{if(d)d.hidden=true;},140);}
const threadForm=qs('[data-thread-form]');threadForm?.addEventListener('submit',async e=>{e.preventDefault();const f=e.currentTarget,fd=new FormData(f);fd.append('as_comment','1');try{const r=await post('/ajax/send',fd);qs('.empty-thread',qs('[data-thread-messages]')||document)?.remove();upsertMessage(r.html,true,qs('[data-thread-messages]'));f.reset();f.elements.chat_id.value=currentChatId();f.elements.reply_to_id.value=activeThread;const count=qs(`[data-open-comments="${activeThread}"] span`);if(count){const n=(Number((count.textContent.match(/\d+/)||[0])[0])||0)+1;count.textContent=`${n} комментариев`;} }catch(err){showToast(err.message,{type:'error'});}});threadForm?.querySelector('textarea')?.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();threadForm.requestSubmit();}});

let contextChat=null;
function closeChatContext(){const m=qs('[data-chat-context]');if(m)m.hidden=true;contextChat=null;}
document.addEventListener('contextmenu',e=>{const item=e.target.closest('[data-chat-item]');if(!item)return;e.preventDefault();contextChat=item;const menu=qs('[data-chat-context]');if(!menu)return;const pinned=item.dataset.pinned==='1',muted=item.dataset.muted==='1';qs('[data-chat-action="pin"]',menu).textContent=pinned?'Открепить':'Закрепить';qs('[data-chat-action="pin"]',menu).dataset.actual=pinned?'unpin':'pin';qs('[data-chat-action="mute"]',menu).textContent=muted?'Включить уведомления':'Выключить уведомления';qs('[data-chat-action="mute"]',menu).dataset.actual=muted?'unmute':'mute';menu.style.left=`${clamp(e.clientX,8,innerWidth-230)}px`;menu.style.top=`${clamp(e.clientY,8,innerHeight-260)}px`;menu.hidden=false;});
document.addEventListener('click',async e=>{const b=e.target.closest('[data-chat-action]');if(!b){if(!e.target.closest('[data-chat-context]'))closeChatContext();return;}if(!contextChat)return;const item=contextChat,id=Number(item.dataset.chatItem),action=b.dataset.actual||b.dataset.chatAction;closeChatContext();try{await post(`/ajax/chat/${id}/action`,{action});if(action==='delete'||action==='archive'){item.remove();if(id===currentChatId()){const next=qs('[data-chat-item]');if(next)openChat(next.dataset.chatItem);else qs('[data-conversation-shell]').innerHTML='<section class="conversation"><div class="empty-state"><h2>Нет переписок</h2></div></section>';}}else if(action==='clear'&&id===currentChatId()){qs('#messages').innerHTML='<div class="drop-overlay"><b>Отпустите файлы для отправки</b></div>';const preview=qs('[data-chat-preview]',item);if(preview)preview.textContent='Нет сообщений';qs('[data-chat-unread]',item)?.remove();}else{if(action==='pin'||action==='unpin')item.dataset.pinned=action==='pin'?'1':'0';if(action==='mute'||action==='unmute')item.dataset.muted=action==='mute'?'1':'0';}showToast('Действие выполнено.',{type:'success'});}catch(err){showToast(err.message,{type:'error'});}});

function initGlobalSearch(){const input=qs('[data-global-search]'),results=qs('[data-global-search-results]'),items=qs('[data-chat-items]'),clear=qs('[data-search-clear]');if(!input||input.dataset.ready)return;input.dataset.ready='1';const fallback=`${K.baseUrl}/assets/icons/default-avatar.svg?v=0.15.1`;const render=(r)=>{const users=(r.users||[]).map(u=>`<button type="button" class="search-result" data-search-user="${u.id}"><span class="avatar-sm avatar-photo"><img src="${escapeHtml(u.avatar_url||fallback)}" alt=""></span><span><b>${escapeHtml(u.display_name)}${u.is_verified?'<i class="verified-badge">✓</i>':''}</b><small>@${escapeHtml(u.username)} · ${u.is_colleague?'коллега · ':''}${u.online?'в сети':'не в сети'}</small></span></button>`).join('');const channels=(r.channels||[]).map(c=>`<button type="button" class="search-result" data-search-chat="${c.id}"><span class="avatar-sm avatar-photo"><img src="${escapeHtml(c.avatar_url||fallback)}" alt=""></span><span><b>${escapeHtml(c.title)}</b><small>${c.username?'@'+escapeHtml(c.username)+' · ':''}${c.subscribers} подписчиков</small></span></button>`).join('');const posts=(r.posts||[]).map(p=>`<a class="search-result message-search-result" href="${escapeHtml(p.url)}"><span class="avatar-sm avatar-photo"><img src="${escapeHtml(p.avatar_url||fallback)}" alt=""></span><span><b>${escapeHtml(p.author_name)}</b><small>${escapeHtml(p.preview)}</small></span></a>`).join('');const messages=(r.messages||[]).map(m=>`<button type="button" class="search-result message-search-result" data-search-chat="${m.chat_id}" data-search-message="${m.id}"><span class="search-icon">💬</span><span><b>${escapeHtml(m.chat_title)}</b><small>${escapeHtml(m.preview)}</small></span></button>`).join('');results.innerHTML=`${users?'<h4>Люди</h4>'+users:''}${channels?'<h4>Сообщества</h4>'+channels:''}${posts?'<h4>Записи</h4>'+posts:''}${messages?'<h4>Сообщения</h4>'+messages:''}`||'<p class="muted">Ничего не найдено.</p>';qsa('[data-search-user]',results).forEach(b=>b.onclick=async()=>{try{const r=await post('/ajax/chat/direct',{user_id:b.dataset.searchUser});input.value='';results.hidden=true;items.hidden=false;clear.hidden=true;openChat(r.chat_id);}catch(err){showToast(err.message,{type:'error'});}});qsa('[data-search-chat]',results).forEach(b=>b.onclick=()=>{input.value='';results.hidden=true;items.hidden=false;clear.hidden=true;openChat(b.dataset.searchChat,{messageId:Number(b.dataset.searchMessage||0)});});};const run=debounce(async()=>{const q=input.value.trim();clear.hidden=!q;if(!q){results.hidden=true;items.hidden=false;return;}results.hidden=false;items.hidden=true;results.innerHTML='<p class="muted">Поиск…</p>';try{render(await jsonFetch(`/ajax/search?q=${encodeURIComponent(q)}`));}catch(err){results.innerHTML=`<p class="muted">${escapeHtml(err.message)}</p>`;}},150);input.oninput=run;clear.onclick=()=>{input.value='';clear.hidden=true;results.hidden=true;items.hidden=false;input.focus();};}
initGlobalSearch();

function initUserPickers(root=document){qsa('.user-picker-form',root).forEach(form=>{if(form.dataset.pickerReady)return;form.dataset.pickerReady='1';const search=qs('[data-user-search]',form),results=qs('[data-user-results]',form),submit=qs('[data-user-submit]',form),hidden=qs('input[name="user_id"]',form),selectedBox=qs('[data-selected-users]',form),multi=form.hasAttribute('data-user-multi');if(!search||!results)return;const selected=new Map();const renderSelected=()=>{if(!selectedBox)return;selectedBox.innerHTML=[...selected.values()].map(u=>`<span data-selected-user="${u.id}">${escapeHtml(u.display_name)} <small>@${escapeHtml(u.username)}</small><button type="button">×</button><input type="hidden" name="members[]" value="${u.id}"></span>`).join('');qsa('[data-selected-user] button',selectedBox).forEach(b=>b.onclick=()=>{selected.delete(Number(b.closest('[data-selected-user]').dataset.selectedUser));renderSelected();});};const render=users=>{results.innerHTML=users.map(u=>`<button type="button" class="user-result" data-user-id="${u.id}" data-user-name="${escapeHtml(u.display_name)}" data-user-username="${escapeHtml(u.username)}"><span class="avatar-sm ${u.avatar_path?'avatar-photo':''}">${u.avatar_path?`<img src="${escapeHtml(u.avatar_url)}" alt="">`:escapeHtml((u.display_name||'?')[0])}</span><span><b>${escapeHtml(u.display_name)}${u.is_verified?'<i class="verified-badge">✓</i>':''}</b><small>@${escapeHtml(u.username)} · ${u.online?'в сети':'не в сети'}</small></span></button>`).join('')||'<p class="muted">Ничего не найдено.</p>';qsa('[data-user-id]',results).forEach(b=>b.onclick=()=>{const u={id:Number(b.dataset.userId),display_name:b.dataset.userName,username:b.dataset.userUsername};if(multi){selected.set(u.id,u);renderSelected();search.value='';results.innerHTML='';}else{hidden.value=String(u.id);search.value=`${u.display_name} · @${u.username}`;results.innerHTML='';if(submit)submit.disabled=false;}});};const run=debounce(async()=>{const q=search.value.trim();if(!q){results.innerHTML='';if(!multi&&hidden)hidden.value='';if(submit&&!multi)submit.disabled=true;return;}try{render((await jsonFetch(`/ajax/users/search?q=${encodeURIComponent(q)}`)).data||[]);}catch(err){results.innerHTML=`<p class="muted">${escapeHtml(err.message)}</p>`;}},160);search.oninput=()=>{if(!multi&&hidden)hidden.value='';if(submit&&!multi)submit.disabled=true;run();};});}
initUserPickers();

function initChannelControls(){qsa('[data-channel-tab]').forEach(b=>b.onclick=()=>{qsa('[data-channel-tab]').forEach(x=>x.classList.toggle('active',x===b));qsa('[data-channel-panel]').forEach(x=>x.classList.toggle('active',x.dataset.channelPanel===b.dataset.channelTab));});
 qsa('[data-ajax-form]').forEach(f=>{if(f.dataset.ready)return;f.dataset.ready='1';f.onsubmit=async e=>{e.preventDefault();const submit=qs('button[type=submit]',f);if(submit)submit.disabled=true;try{const r=await post(relativePath(f.action),new FormData(f));showToast(r.message||'Сохранено.',{type:'success'});const title=f.elements?.title?.value?.trim();if(title){const h=qs('.conversation-title h1');if(h)h.firstChild.textContent=title;}try{await openChat(currentChatId(),{pop:true});}catch(refreshError){console.warn('Channel refresh failed after successful save',refreshError);}}catch(err){showToast(err.message||'Не удалось сохранить настройки канала.',{type:'error'});}finally{if(submit)submit.disabled=false;}};});
 const avatar=qs('[data-channel-avatar-form]');if(avatar&&!avatar.dataset.ready){avatar.dataset.ready='1';const input=qs('input[type=file]',avatar),drop=qs('.upload-dropzone',avatar);drop.onclick=e=>{if(e.target!==input)input.click();};['dragenter','dragover'].forEach(n=>drop.addEventListener(n,e=>{e.preventDefault();drop.classList.add('dragging');}));['dragleave','drop'].forEach(n=>drop.addEventListener(n,e=>{e.preventDefault();drop.classList.remove('dragging');}));drop.addEventListener('drop',e=>{input.files=e.dataTransfer.files;avatar.requestSubmit();});input.onchange=()=>avatar.requestSubmit();avatar.onsubmit=async e=>{e.preventDefault();try{await post(relativePath(avatar.action),new FormData(avatar));showToast('Аватар канала обновлён.',{type:'success'});openChat(currentChatId(),{pop:true});}catch(err){showToast(err.message,{type:'error'});}};}
 const add=qs('[data-add-channel-member]');if(add&&!add.dataset.ready){add.dataset.ready='1';add.onsubmit=async e=>{e.preventDefault();try{await post(relativePath(add.action),new FormData(add));showToast('Подписчик добавлен.',{type:'success'});openChat(currentChatId(),{pop:true});}catch(err){showToast(err.message,{type:'error'});}};}
 const invite=qs('[data-invite-create]');if(invite&&!invite.dataset.ready){invite.dataset.ready='1';invite.onsubmit=async e=>{e.preventDefault();try{const r=await post(relativePath(invite.action),new FormData(invite));const list=qs('[data-invite-list]');list.insertAdjacentHTML('afterbegin',`<div class="invite-link" data-invite-id="${r.id}"><div><b>${escapeHtml(r.label)}</b><input readonly value="${escapeHtml(r.url)}"></div><button type="button" data-copy-value="${escapeHtml(r.url)}">Копировать</button><button type="button" data-invite-revoke="${r.id}">Отключить</button></div>`);invite.reset();}catch(err){showToast(err.message,{type:'error'});}};}
}

function initColumnResizers(){if(!messenger)return;const l=Number(localStorage.getItem('kovcheg-left-column')||340),r=Number(localStorage.getItem('kovcheg-right-column')||320);messenger.style.setProperty('--left-column',`${clamp(l,250,520)}px`);messenger.style.setProperty('--right-column',`${clamp(r,240,480)}px`);qsa('[data-column-resizer]').forEach(h=>{if(h.dataset.ready)return;h.dataset.ready='1';h.onpointerdown=e=>{if(innerWidth<900)return;e.preventDefault();h.setPointerCapture(e.pointerId);const rect=messenger.getBoundingClientRect(),move=ev=>{if(h.dataset.columnResizer==='left'){const rail=qs('.site-sidebar',messenger)?.getBoundingClientRect().width||0,w=clamp(ev.clientX-rect.left-rail,250,520);messenger.style.setProperty('--left-column',`${w}px`);localStorage.setItem('kovcheg-left-column',w);}else{const w=clamp(rect.right-ev.clientX,240,480);messenger.style.setProperty('--right-column',`${w}px`);localStorage.setItem('kovcheg-right-column',w);}},up=()=>{removeEventListener('pointermove',move);removeEventListener('pointerup',up);};addEventListener('pointermove',move);addEventListener('pointerup',up,{once:true});};});}
initColumnResizers();

async function initNotificationBell(){const bell=qs('[data-notification-bell]'),panel=qs('[data-notification-bell-panel]');if(!bell||!panel)return;bell.onclick=e=>{e.stopPropagation();panel.hidden=!panel.hidden;bell.setAttribute('aria-expanded',String(!panel.hidden));};document.addEventListener('click',e=>{if(!e.target.closest('.notification-bell-wrap')){panel.hidden=true;bell.setAttribute('aria-expanded','false');}});qsa('[data-notifications-read-all]').forEach(b=>b.addEventListener('click',async e=>{e.stopPropagation();b.disabled=true;try{await markNotificationsRead([],true);if(window.KovchegShowToast)window.KovchegShowToast('Все оповещения прочитаны.');}finally{b.disabled=false;}}));panel.addEventListener('click',e=>{const note=e.target.closest('[data-notification-id]');if(note)markNotificationsRead([note.dataset.notificationId]);});}
async function markNotificationsRead(ids=[],all=false){if(!ids.length&&!all)ids=qsa('[data-notification-id].unread').map(x=>x.dataset.notificationId);try{await post('/ajax/notifications/read',{ids:ids.join(','),all:all?'1':'0'});const notes=all?qsa('[data-notification-id].unread'):ids.map(id=>qs(`[data-notification-id="${id}"]`)).filter(Boolean);notes.forEach(node=>node.classList.remove('unread'));if(!qsa('[data-notification-id].unread').length){const c=qs('[data-notification-bell-count]');if(c){c.hidden=true;c.textContent='0';}}}catch(_){}}
initNotificationBell();

const moduleInput=qs('[data-module-input]'),moduleDrop=qs('[data-module-drop]'),moduleSubmit=qs('[data-module-submit]'),selectedFilesBox=qs('[data-selected-files]');
if(moduleInput&&moduleDrop){const render=()=>{if(selectedFilesBox)selectedFilesBox.textContent=[...moduleInput.files].map(f=>f.name).join(', ');if(moduleSubmit)moduleSubmit.disabled=!moduleInput.files.length;};qs('[data-module-choose]')?.addEventListener('click',()=>moduleInput.click());moduleInput.onchange=render;['dragenter','dragover'].forEach(n=>moduleDrop.addEventListener(n,e=>{e.preventDefault();moduleDrop.classList.add('dragging');}));['dragleave','drop'].forEach(n=>moduleDrop.addEventListener(n,e=>{e.preventDefault();moduleDrop.classList.remove('dragging');}));moduleDrop.addEventListener('drop',e=>{const dt=new DataTransfer();[...e.dataTransfer.files].filter(f=>f.name.toLowerCase().endsWith('.zip')).forEach(f=>dt.items.add(f));moduleInput.files=dt.files;render();});}
qsa('[data-generic-drop]').forEach(form=>{const input=qs('input[type=file]',form);if(!input)return;['dragenter','dragover'].forEach(n=>form.addEventListener(n,e=>{e.preventDefault();form.classList.add('dragging');}));['dragleave','drop'].forEach(n=>form.addEventListener(n,e=>{e.preventDefault();form.classList.remove('dragging');}));form.addEventListener('drop',e=>{input.files=e.dataTransfer.files;});});

const avatarForm=qs('[data-avatar-form]'),avatarInput=qs('[data-avatar-input]'),avatarModal=qs('[data-crop-modal]'),avatarImage=qs('[data-crop-image]'),avatarScale=qs('[data-crop-scale]'),avatarApply=qs('[data-crop-apply]');let avatarUrl='';
function closeAvatarEditor(){if(avatarModal)avatarModal.hidden=true;document.body.classList.remove('avatar-crop-open');if(avatarInput)avatarInput.value='';}
function avatarOpen(file){if(!file?.type.startsWith('image/')||!avatarImage||!avatarModal)return;if(avatarUrl)URL.revokeObjectURL(avatarUrl);avatarUrl=URL.createObjectURL(file);if(avatarModal.parentElement!==document.body)document.body.appendChild(avatarModal);document.body.classList.add('avatar-crop-open');avatarModal.hidden=false;avatarScale.value='1';avatarImage.onload=()=>requestAnimationFrame(()=>positionCrop(avatarImage,avatarScale));avatarImage.src=avatarUrl;}
qs('[data-avatar-choose]')?.addEventListener('click',()=>avatarInput?.click());avatarInput?.addEventListener('change',()=>avatarOpen(avatarInput.files[0]));avatarScale?.addEventListener('input',()=>positionCrop(avatarImage,avatarScale));qs('[data-crop-cancel]')?.addEventListener('click',closeAvatarEditor);avatarModal?.addEventListener('click',event=>{if(event.target===avatarModal)closeAvatarEditor();});avatarApply?.addEventListener('click',()=>{if(!avatarImage?.parentElement||!avatarForm||avatarApply.disabled)return;const stage=avatarImage.parentElement,size=stage.clientWidth;if(!size)return showToast('Редактор фотографии ещё не готов.',{type:'error'});const canvas=document.createElement('canvas');canvas.width=canvas.height=800;const ratio=800/size,ctx=canvas.getContext('2d');if(!ctx)return showToast('Браузер не смог подготовить фотографию.',{type:'error'});ctx.drawImage(avatarImage,parseFloat(avatarImage.style.left||'0')*ratio,parseFloat(avatarImage.style.top||'0')*ratio,parseFloat(avatarImage.style.width||String(size))*ratio,parseFloat(avatarImage.style.height||String(size))*ratio);avatarApply.disabled=true;avatarApply.textContent='Загрузка…';canvas.toBlob(async blob=>{if(!blob){avatarApply.disabled=false;avatarApply.textContent='Обрезать и загрузить';showToast('Не удалось обработать фотографию.',{type:'error'});return;}const fd=new FormData(avatarForm);fd.set('avatar',new File([blob],'avatar.jpg',{type:'image/jpeg'}));try{const result=await post('/profile/avatar',fd),url=String(result.avatar_url||'');if(url){const fresh=url+(url.includes('?')?'&':'?')+`t=${Date.now()}`;qsa('.settings-profile-head img,.vk-profile-avatar img,.kov-vk-old-avatar img,.x-profile-avatar img,.site-user-card img,.top-profile img,.kov-header-account-button img').forEach(image=>image.src=fresh);}closeAvatarEditor();showToast('Фотография профиля обновлена.',{type:'success'});}catch(error){showToast(error.message||'Не удалось загрузить фотографию.',{type:'error'});}finally{avatarApply.disabled=false;avatarApply.textContent='Обрезать и загрузить';}},'image/jpeg',.92);});

qs('.doc-filter')?.addEventListener('input',e=>{const q=e.target.value.toLowerCase();qsa('.docs-layout aside a').forEach(a=>a.hidden=!a.textContent.toLowerCase().includes(q));});
qs('[data-theme-select]')?.addEventListener('change',e=>document.body.dataset.theme=e.target.value);
qsa('[data-request-notifications]').forEach(button=>button.addEventListener('click',async()=>{button.disabled=true;try{await ensurePushSubscription(true);const checkbox=qs('input[name="desktop_notifications"]');if(checkbox)checkbox.checked=true;K.notifications.desktop=true;qs('[data-push-invite]')?.setAttribute('hidden','');showToast('Прямые Push-уведомления подключены к этому устройству.',{type:'success'});}catch(err){showToast(err.message,{type:'error'});}finally{button.disabled=false;}}));

document.addEventListener('pointerdown',()=>{try{audioContext=audioContext||new(window.AudioContext||window.webkitAudioContext)();if(audioContext.state==='suspended')audioContext.resume();}catch(_){}},{once:true});
if('serviceWorker'in navigator)window.addEventListener('load',async()=>{try{await navigator.serviceWorker.register(`${K.baseUrl}/service-worker.js`);if(K.notifications?.desktop&&Notification.permission==='granted')await ensurePushSubscription(false);}catch(_){}});
})();
(()=>{
'use strict';
const K=window.KOVCHEG||{};
const qs=(s,r=document)=>r.querySelector(s),qsa=(s,r=document)=>[...r.querySelectorAll(s)];
const toast=(text,type='success')=>{const stack=qs('#toast-stack');if(!stack)return;const el=document.createElement('div');el.className=`toast toast-${type}`;el.textContent=text;stack.append(el);requestAnimationFrame(()=>el.classList.add('show'));setTimeout(()=>{el.classList.remove('show');setTimeout(()=>el.remove(),250)},3500);};
const request=async(path,data={})=>{const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([k,v])=>Array.isArray(v)?v.forEach(x=>fd.append(k+'[]',x)):fd.append(k,String(v)));if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');const res=await fetch(`${K.baseUrl}${path}`,{method:'POST',body:fd,headers:{Accept:'application/json'},credentials:'same-origin'});let json={};try{json=await res.json();}catch{}if(!res.ok||json.ok===false)throw new Error(json.error||`Ошибка ${res.status}`);return json;};

let selected=new Set();
const messagesBox=()=>qs('#messages');
function refreshSelection(){const box=messagesBox(),bar=qs('[data-message-selection-bar]');if(!box||!bar)return;const count=selected.size;qs('[data-selection-count]',bar).textContent=String(count);const forward=qs('[data-selection-forward]',bar);if(forward)forward.disabled=count===0;qsa('[data-message-id]',box).forEach(m=>m.classList.toggle('selected',selected.has(Number(m.dataset.messageId))));}
function enterSelection(){const box=messagesBox(),bar=qs('[data-message-selection-bar]');if(!box||!bar)return;selected.clear();box.classList.add('selection-mode');bar.hidden=false;refreshSelection();}
function leaveSelection(){const box=messagesBox(),bar=qs('[data-message-selection-bar]');selected.clear();box?.classList.remove('selection-mode');if(bar)bar.hidden=true;qsa('.message.selected').forEach(x=>x.classList.remove('selected'));}
function toggleMessage(id){id=Number(id);if(!id)return;selected.has(id)?selected.delete(id):selected.add(id);refreshSelection();}

async function loadContacts(){const box=qs('[data-forward-contacts]');if(!box||box.dataset.loaded)return;box.dataset.loaded='1';box.innerHTML='<span class="muted">Загружаем коллег…</span>';try{const res=await fetch(`${K.baseUrl}/ajax/contacts`,{credentials:'same-origin',headers:{Accept:'application/json'}});const json=await res.json();const rows=json.data||[];box.innerHTML=rows.map(u=>`<button type="button" class="contact-chip" data-contact-id="${u.id}" data-contact-name="${escapeHtml(u.display_name)}"><span class="avatar-sm">${u.avatar_url?`<img src="${escapeHtml(u.avatar_url)}" alt="">`:escapeHtml((u.display_name||'?')[0])}</span><b>${escapeHtml(u.display_name)}</b></button>`).join('')||'<span class="muted">Коллег пока нет — используйте поиск ниже.</span>';}catch(e){box.innerHTML='<span class="muted">Не удалось загрузить коллег.</span>';}}
const escapeHtml=s=>String(s??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));

function openBatchModal(){if(!selected.size)return;const modal=qs('#forward-batch');if(!modal)return;const ids=qs('input[name="message_ids"]',modal);if(ids)ids.value=[...selected].join(',');const count=qs('[data-forward-batch-count]',modal);if(count)count.textContent=`Выбрано сообщений: ${selected.size}`;modal.hidden=false;loadContacts();qs('input[data-user-search]',modal)?.focus();}

document.addEventListener('click',async e=>{
 const selectBtn=e.target.closest('[data-select-messages]');if(selectBtn){enterSelection();return;}
 const cancel=e.target.closest('[data-selection-cancel]');if(cancel){leaveSelection();return;}
 const forward=e.target.closest('[data-selection-forward]');if(forward){openBatchModal();return;}
 const selector=e.target.closest('[data-message-select]');if(selector){e.preventDefault();e.stopPropagation();toggleMessage(selector.dataset.messageSelect);return;}
 const box=messagesBox();const message=e.target.closest('[data-message-id]');if(box?.classList.contains('selection-mode')&&message&&!e.target.closest('.message-actions,.reaction-menu,a,button:not(.message-select-control),audio,video')){e.preventDefault();toggleMessage(message.dataset.messageId);return;}
 const contact=e.target.closest('[data-contact-id]');if(contact){const modal=contact.closest('.modal');if(!modal)return;const hidden=qs('input[name="user_id"]',modal),search=qs('input[data-user-search]',modal),submit=qs('[data-user-submit]',modal);if(hidden)hidden.value=contact.dataset.contactId;if(search)search.value=contact.dataset.contactName;if(submit)submit.disabled=false;qsa('[data-contact-id]',modal).forEach(x=>x.classList.toggle('active',x===contact));return;}
 const follow=e.target.closest('[data-follow-action]');if(follow){const wrap=follow.closest('[data-profile-actions]');try{const action=follow.dataset.followAction;if(action!=='unfollow')throw new Error('Подписка создаётся только вместе с заявкой в коллеги.');await request(`/ajax/user/${wrap.dataset.userId}/follow`,{action:'unfollow'});follow.remove();toast('Вы отписались от обновлений пользователя.');}catch(err){toast(err.message,'error');}return;}
 const colleague=e.target.closest('[data-colleague-action]');if(colleague){const wrap=colleague.closest('[data-profile-actions]');try{await request(`/ajax/user/${wrap.dataset.userId}/colleague`,{action:colleague.dataset.colleagueAction});toast('Изменение сохранено.');setTimeout(()=>location.reload(),450);}catch(err){toast(err.message,'error');}return;}
 const decision=e.target.closest('[data-colleague-decision]');if(decision){try{await request(`/ajax/user/${decision.dataset.userId}/colleague`,{action:decision.dataset.colleagueDecision});decision.closest('[data-person-card]')?.remove();toast('Готово.');}catch(err){toast(err.message,'error');}return;}
});

document.addEventListener('submit',async e=>{
 const form=e.target.closest('[data-forward-batch-form]');if(!form)return;e.preventDefault();const fd=new FormData(form);const raw=String(fd.get('message_ids')||'');raw.split(',').filter(Boolean).forEach(id=>fd.append('message_ids[]',id));fd.delete('message_ids');try{const r=await request('/ajax/messages/forward-batch',fd);qs('#forward-batch').hidden=true;leaveSelection();toast(`Переслано сообщений: ${r.count}`);if(window.KovchegOpenChat)await window.KovchegOpenChat(r.chat_id);else location.href=r.url;}catch(err){toast(err.message,'error');}
});

const shell=qs('[data-conversation-shell]');if(shell)new MutationObserver(()=>leaveSelection()).observe(shell,{childList:true});
})();
(()=>{
'use strict';
const K=window.KOVCHEG||{};
const qs=(s,r=document)=>r.querySelector(s),qsa=(s,r=document)=>[...r.querySelectorAll(s)];
const endpoint=p=>`${String(K.baseUrl||'').replace(/\/$/,'')}${p}`;
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function post(path,data={}){const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([k,v])=>fd.append(k,String(v)));if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');const response=await fetch(endpoint(path),{method:'POST',body:fd,headers:{Accept:'application/json'},credentials:'same-origin'});let json={};try{json=await response.json();}catch{}if(!response.ok||json.ok===false)throw new Error(json.error||`Ошибка ${response.status}`);return json;}
function toast(text,type='success'){const stack=qs('#toast-stack');if(!stack)return;const node=document.createElement('div');node.className=`toast toast-${type}`;node.textContent=text;stack.append(node);requestAnimationFrame(()=>node.classList.add('show'));setTimeout(()=>{node.classList.remove('show');setTimeout(()=>node.remove(),250)},3200);}

/* Dropdown in the logo area */
const menuButton=qs('[data-brand-menu-button]'),menu=qs('[data-brand-menu]');
function closeMenu(){if(!menu||!menuButton)return;menu.hidden=true;menuButton.setAttribute('aria-expanded','false');}
if(menuButton&&menu){menuButton.addEventListener('click',event=>{event.stopPropagation();menu.hidden=!menu.hidden;menuButton.setAttribute('aria-expanded',String(!menu.hidden));});document.addEventListener('click',event=>{if(!event.target.closest('.brand-menu-wrap'))closeMenu();});document.addEventListener('keydown',event=>{if(event.key==='Escape')closeMenu();});}
qsa('[data-quick-theme]').forEach(button=>button.addEventListener('click',async()=>{const requested=String(button.dataset.quickTheme||'dark');const theme=['light','dark','black'].includes(requested)?requested:'dark';try{await post('/profile/theme',{theme});document.body.dataset.theme=theme;qsa('[data-quick-theme]').forEach(item=>item.classList.toggle('active',item===button));toast(theme==='light'?'Светлая тема включена.':(theme==='black'?'Чёрная тема включена.':'Тёмная тема включена.'));}catch(error){toast(error.message,'error');}}));

/* Profile wall without legacy dialog windows */
document.addEventListener('click',async event=>{
 const like=event.target.closest('[data-wall-like]');
 if(like){event.preventDefault();try{const result=await post(`/profile/wall/${like.dataset.wallLike}/like`);like.classList.toggle('active',!!result.liked);const count=qs('[data-like-count],b',like);if(count)count.textContent=result.count?String(result.count):'';}catch(error){toast(error.message,'error');}return;}
 const remove=event.target.closest('[data-wall-delete]');
 if(remove){event.preventDefault();const article=remove.closest('[data-wall-post]');remove.disabled=true;try{await post(`/profile/wall/${remove.dataset.wallDelete}/delete`);article?.animate([{opacity:1,height:`${article.offsetHeight}px`},{opacity:0,height:'0px',paddingTop:'0',paddingBottom:'0'}],{duration:220,easing:'ease'}).finished.then(()=>article.remove());toast('Запись удалена.');}catch(error){remove.disabled=false;toast(error.message,'error');}return;}
});
qsa('.vk-wall-composer textarea').forEach(area=>area.addEventListener('input',()=>{area.style.height='auto';area.style.height=`${Math.min(150,area.scrollHeight)}px`;}));

/* Bell polling on pages where the messenger sync loop is absent */
const messenger=qs('.messenger');
const bellList=qs('[data-notification-list]'),bellCount=qs('[data-notification-bell-count]');
function notificationIcon(note){if(note.icon)return `<img src="${esc(note.icon)}" alt="">`;if(note.type==='message')return '💬';if(note.type==='social')return '👥';if(note.type==='wall')return '📝';return '🔔';}
function appendNotification(note){if(!bellList||qs(`[data-notification-id="${Number(note.id)}"]`,bellList))return;qs('.empty-notes',bellList)?.remove();const link=document.createElement('a');link.className='notification-note unread';link.href=note.url||endpoint('/messages');link.dataset.notificationId=String(note.id);link.innerHTML=`<span class="notification-note-icon">${notificationIcon(note)}</span><div><b>${esc(note.title)}</b><p>${esc(note.body||'')}</p><small>только что</small></div>`;bellList.prepend(link);while(qsa('[data-notification-id]',bellList).length>60)qsa('[data-notification-id]',bellList).pop()?.remove();}
function setUnread(value){if(!bellCount)return;const count=Math.max(0,Number(value)||0);bellCount.textContent=String(count);bellCount.hidden=count===0;}
async function pollNotifications(){try{const after=Number(K.notificationLast||0);const response=await fetch(endpoint(`/ajax/notifications?after=${after}`),{credentials:'same-origin',headers:{Accept:'application/json'},cache:'no-store'});if(!response.ok)return;const data=await response.json();const items=Array.isArray(data.items)?data.items:[];items.forEach(note=>{appendNotification(note);K.notificationLast=Math.max(Number(K.notificationLast||0),Number(note.id||0));});setUnread(data.unread);if(items.length)toast(items.at(-1).title||'Новое оповещение','success');}catch(_){}}
if(K.userId&&!messenger){setInterval(pollNotifications,7000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)pollNotifications();});setTimeout(pollNotifications,1000);}
})();
(()=>{
'use strict';const K=window.KOVCHEG||{},qs=(s,r=document)=>r.querySelector(s),qsa=(s,r=document)=>[...r.querySelectorAll(s)],base=String(K.baseUrl||'').replace(/\/$/,''),esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function json(url,opt={}){const r=await fetch(base+url,{credentials:'same-origin',headers:{Accept:'application/json',...(opt.headers||{})},...opt}),j=await r.json().catch(()=>({ok:false,error:'Ошибка ответа'}));if(!r.ok||j.ok===false)throw new Error(j.error||`Ошибка ${r.status}`);return j;}
async function post(url,data={}){const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([k,v])=>fd.append(k,String(v)));if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');return json(url,{method:'POST',body:fd});}
const toast=(t,type='success')=>window.KovchegShowToast?.(t,{type})||null;
/* Universal live message popups on every non-messenger page. */
let live=Number(K.liveMessageLast||0),busy=false;async function pollLive(){if(busy||!K.userId||qs('.messenger'))return;busy=true;try{const r=await json(`/ajax/live/messages?after=${live}`);for(const item of r.items||[]){live=Math.max(live,Number(item.id||0));window.KovchegIncomingNotify?.({sender_name:item.display_name,text:item.preview,avatar_url:item.avatar_url,chat_id:item.chat_id,message_id:item.id,url:item.url});}live=Math.max(live,Number(r.last||0));}catch(_){}finally{busy=false;}}if(K.userId&&!qs('.messenger')){const liveInterval=Math.max(3000,Number(K.polling||3000));setInterval(pollLive,liveInterval);setTimeout(pollLive,900);document.addEventListener('visibilitychange',()=>{if(!document.hidden)pollLive();});}
/* Avatar menu/editor/history directly on profile. */
const wrap=qs('[data-profile-avatar]'),menu=qs('[data-avatar-menu]'),file=qs('[data-profile-avatar-input]'),form=qs('[data-profile-avatar-form]'),editor=qs('[data-profile-avatar-editor]'),cropImg=qs('[data-profile-crop-image]'),scale=qs('[data-profile-crop-scale]'),viewer=qs('[data-profile-avatar-viewer]'),historyModal=qs('[data-avatar-history-modal]');let objectUrl='',rotation=0;
function setCrop(){if(!cropImg?.naturalWidth||!cropImg.parentElement)return;const size=cropImg.parentElement.clientWidth,z=Number(scale?.value||1),ratio=Math.max(size/cropImg.naturalWidth,size/cropImg.naturalHeight)*z;cropImg.style.width=`${cropImg.naturalWidth*ratio}px`;cropImg.style.height=`${cropImg.naturalHeight*ratio}px`;cropImg.style.left=`${(size-cropImg.naturalWidth*ratio)/2}px`;cropImg.style.top=`${(size-cropImg.naturalHeight*ratio)/2}px`;cropImg.style.transform=`rotate(${rotation}deg)`;}
function closeProfileEditor(){if(editor)editor.hidden=true;document.body.classList.remove('avatar-crop-open');if(file)file.value='';}
function openEditor(f){if(!f?.type.startsWith('image/')||!editor||!cropImg||!scale)return;if(objectUrl)URL.revokeObjectURL(objectUrl);objectUrl=URL.createObjectURL(f);if(editor.parentElement!==document.body)document.body.appendChild(editor);document.body.classList.add('avatar-crop-open');editor.hidden=false;rotation=0;scale.value='1';cropImg.onload=()=>requestAnimationFrame(setCrop);cropImg.src=objectUrl;}
qs('[data-avatar-menu-button]')?.addEventListener('click',e=>{e.stopPropagation();if(menu)menu.hidden=!menu.hidden;});document.addEventListener('click',e=>{if(menu&&!e.target.closest('[data-profile-avatar]'))menu.hidden=true;});qs('[data-avatar-view]')?.addEventListener('click',()=>{menu.hidden=true;viewer.hidden=false;requestAnimationFrame(()=>viewer.classList.add('open'));});qs('[data-profile-avatar-close]')?.addEventListener('click',()=>{viewer.classList.remove('open');setTimeout(()=>viewer.hidden=true,120);});qs('[data-avatar-replace]')?.addEventListener('click',()=>{menu.hidden=true;file?.click();});file?.addEventListener('change',()=>openEditor(file.files[0]));scale?.addEventListener('input',setCrop);qsa('[data-profile-crop-rotate]').forEach(b=>b.addEventListener('click',()=>{rotation+=Number(b.dataset.profileCropRotate||0);setCrop();}));qs('[data-profile-crop-reset]')?.addEventListener('click',()=>{rotation=0;scale.value='1';setCrop();});qs('[data-profile-crop-cancel]')?.addEventListener('click',closeProfileEditor);editor?.addEventListener('click',event=>{if(event.target===editor)closeProfileEditor();});
qs('[data-profile-crop-save]')?.addEventListener('click',event=>{const button=event.currentTarget,stage=cropImg?.parentElement;if(!stage||!file||!form||button.disabled)return;const size=stage.clientWidth;if(!size)return toast('Редактор фотографии ещё не готов.','error');const c=document.createElement('canvas');c.width=c.height=900;const ctx=c.getContext('2d'),ratio=900/size,w=parseFloat(cropImg.style.width||String(size))*ratio,h=parseFloat(cropImg.style.height||String(size))*ratio,x=parseFloat(cropImg.style.left||'0')*ratio,y=parseFloat(cropImg.style.top||'0')*ratio;if(!ctx)return toast('Браузер не смог подготовить фотографию.','error');ctx.save();ctx.translate(x+w/2,y+h/2);ctx.rotate(rotation*Math.PI/180);ctx.drawImage(cropImg,-w/2,-h/2,w,h);ctx.restore();button.disabled=true;button.textContent='Загрузка…';c.toBlob(async blob=>{if(!blob){button.disabled=false;button.textContent='Сохранить';toast('Не удалось обработать фотографию.','error');return;}const fd=new FormData(form);fd.set('avatar',new File([blob],'avatar.jpg',{type:'image/jpeg'}));try{const r=await post('/profile/avatar',fd),url=String(r.avatar_url||''),fresh=url+(url.includes('?')?'&':'?')+`t=${Date.now()}`;qsa('.vk-profile-avatar img,.kov-vk-old-avatar img,.x-profile-avatar img,.settings-profile-head img,.site-user-card img,.top-profile img,.kov-header-account-button img').forEach(i=>i.src=fresh);closeProfileEditor();toast('Фото профиля обновлено.');}catch(e){toast(e.message,'error');}finally{button.disabled=false;button.textContent='Сохранить';}},'image/jpeg',.92);});
qs('[data-avatar-delete]')?.addEventListener('click',async()=>{menu.hidden=true;try{const r=await post('/profile/avatar/delete');qsa('.vk-profile-avatar img,.site-user-card img,.top-profile img').forEach(i=>i.src=r.avatar_url);toast('Фото удалено.');}catch(e){toast(e.message,'error');}});
qs('[data-avatar-history]')?.addEventListener('click',async()=>{menu.hidden=true;const grid=qs('[data-avatar-history-grid]');historyModal.hidden=false;historyModal.classList.add('open');grid.innerHTML='<p>Загрузка…</p>';try{const r=await json('/ajax/profile/avatar-history');grid.innerHTML=(r.data||[]).map(x=>`<button type="button" data-history-photo="${esc(x.url)}"><img src="${esc(x.url)}" alt=""><small>${esc(x.created_at||'')}</small></button>`).join('')||'<p>История пока пуста.</p>';}catch(e){grid.innerHTML=`<p>${esc(e.message)}</p>`;}});qs('[data-avatar-history-close]')?.addEventListener('click',()=>{historyModal.classList.remove('open');setTimeout(()=>historyModal.hidden=true,120);});document.addEventListener('click',e=>{const b=e.target.closest('[data-history-photo]');if(!b)return;historyModal.hidden=true;const img=qs('[data-profile-avatar-view-image]');if(img)img.src=b.dataset.historyPhoto;viewer.hidden=false;viewer.classList.add('open');});
/* Top global search. */
const top=qs('[data-top-global-search]'),results=qs('[data-top-global-results]');let timer;function row(url,img,title,sub){return `<a href="${esc(url)}"><img src="${esc(img||base+'/assets/icons/default-avatar.svg?v=0.15.1')}" alt=""><span><b>${esc(title)}</b><small>${esc(sub||'')}</small></span></a>`;}top?.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(async()=>{const q=top.value.trim();if(!q){results.hidden=true;results.innerHTML='';return;}try{const r=await json(`/ajax/search?q=${encodeURIComponent(q)}`),chunks=[];if(r.users?.length)chunks.push('<h4>Люди</h4>'+r.users.slice(0,6).map(x=>row(base+`/@${x.username}`,x.avatar_url,x.display_name,`@${x.username}${x.is_colleague?' · коллега':''}`)).join(''));if(r.channels?.length)chunks.push('<h4>Сообщества</h4>'+r.channels.slice(0,5).map(x=>row(x.url||base+`/messages/chat-${x.id}`,x.avatar_url,x.title,x.username?'@'+x.username:`${x.subscribers||0} подписчиков`)).join(''));if(r.posts?.length)chunks.push('<h4>Записи</h4>'+r.posts.slice(0,6).map(x=>row(x.url,x.avatar_url,x.author_name,x.preview)).join(''));if(r.messages?.length)chunks.push('<h4>Сообщения</h4>'+r.messages.slice(0,6).map(x=>row(x.url,base+'/assets/icons/icon.svg',x.chat_title,x.preview)).join(''));results.innerHTML=chunks.join('')||'<p>Ничего не найдено.</p>';results.hidden=false;}catch(e){results.innerHTML=`<p>${esc(e.message)}</p>`;results.hidden=false;}},180);});document.addEventListener('click',e=>{if(results&&!e.target.closest('.top-global-search'))results.hidden=true;});
/* Forward chooser contains colleagues only. */
async function fillContacts(){for(const box of qsa('[data-forward-contacts]')){if(box.dataset.loaded)return;box.dataset.loaded='1';try{const r=await json('/ajax/contacts');box.innerHTML=(r.data||[]).map(x=>`<button type="button" data-contact-id="${x.id}"><img src="${esc(x.avatar_url)}"><span><b>${esc(x.display_name)}</b><small>@${esc(x.username)}</small></span></button>`).join('')||'<p class="muted">Сначала добавьте коллег.</p>';}catch(e){box.innerHTML=`<p>${esc(e.message)}</p>`;}}}document.addEventListener('click',e=>{const b=e.target.closest('[data-contact-id]');if(!b)return;const form=b.closest('form');form.elements.user_id.value=b.dataset.contactId;qsa('[data-contact-id]',form).forEach(x=>x.classList.toggle('selected',x===b));const submit=qs('[data-user-submit]',form);if(submit)submit.disabled=false;});fillContacts();
})();
(()=>{
'use strict';
const K=window.KOVCHEG||{};
const base=String(K.baseUrl||'').replace(/\/$/,'');
const qs=(s,r=document)=>r.querySelector(s);
const qsa=(s,r=document)=>[...r.querySelectorAll(s)];
const endpoint=p=>`${base}${p}`;
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function json(path,options={}){const r=await fetch(endpoint(path),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json',...(options.headers||{})},...options});const j=await r.json().catch(()=>({ok:false,error:`Ошибка ${r.status}`}));if(!r.ok||j.ok===false)throw new Error(j.error||`Ошибка ${r.status}`);return j;}
async function jsonUrl(url,options={}){const r=await fetch(url,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json',...(options.headers||{})},...options});const j=await r.json().catch(()=>({ok:false,error:`Ошибка ${r.status}`}));if(!r.ok||j.ok===false)throw new Error(j.error||`Ошибка ${r.status}`);return j;}
async function post(path,data={}){const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([k,v])=>fd.append(k,String(v)));if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');return json(path,{method:'POST',body:fd});}
function toast(text,type='success'){const stack=qs('#toast-stack');if(!stack)return;const n=document.createElement('div');n.className=`message-toast generic-toast toast-${type}`;n.innerHTML=`<span class="toast-icon">${type==='error'?'!':'✓'}</span><div><b>${type==='error'?'Ошибка':'Готово'}</b><p>${esc(text)}</p></div><button class="toast-close">×</button>`;qs('button',n).onclick=()=>n.remove();stack.append(n);setTimeout(()=>n.remove(),5000);}

/* Message scrolling is handled once by messageScrollStates. */

/* Compact inline status. */
document.addEventListener('submit',async event=>{
 const form=event.target.closest('[data-profile-status-form]');if(!form)return;event.preventDefault();const button=qs('button',form);if(button)button.disabled=true;try{await post('/profile/status',{status_text:form.elements.status_text.value});toast('Статус сохранён.');form.elements.status_text.blur();}catch(error){toast(error.message,'error');}finally{if(button)button.disabled=false;}
});

/* Profile photo reactions. */
document.addEventListener('click',async event=>{
 const react=event.target.closest('[data-avatar-react]');if(!react)return;event.preventDefault();const box=react.closest('[data-avatar-reactions]');const userId=Number(box?.dataset.profileUserId||0);if(!userId)return;react.disabled=true;try{const r=await post(`/profile/${userId}/avatar/react`,{emoji:react.dataset.avatarReact});if(r.html){const template=document.createElement('template');template.innerHTML=r.html.trim();box.replaceWith(template.content.firstElementChild);}}catch(error){toast(error.message,'error');}finally{react.disabled=false;}
});

/* Story creation and viewer. */
let storyFile=null,storyUrl='',storyItems=[],storyIndex=0,storyTimer=0,storyOwner=null;
const storyModal=()=>qs('[data-story-upload-modal]');
const storyInput=()=>qs('[data-story-file-input]');
const storyPreview=()=>qs('[data-story-upload-preview]');
function openStoryCreator(){const modal=storyModal();if(!modal)return;modal.hidden=false;requestAnimationFrame(()=>modal.classList.add('open'));if(!storyFile)storyInput()?.click();}
function closeStoryCreator(){const modal=storyModal();if(!modal)return;modal.classList.remove('open');setTimeout(()=>modal.hidden=true,140);}
function setStoryFile(file){if(!file)return;if(!/^image\/(jpeg|png|webp)$|^video\/(mp4|webm)$/.test(file.type)){toast('Выберите JPG, PNG, WebP, MP4 или WebM.','error');return;}if(file.size>40*1024*1024){toast('История должна быть не больше 40 МБ.','error');return;}storyFile=file;if(storyUrl)URL.revokeObjectURL(storyUrl);storyUrl=URL.createObjectURL(file);const p=storyPreview();if(p)p.innerHTML=file.type.startsWith('video/')?`<video src="${storyUrl}" controls muted playsinline></video>`:`<img src="${storyUrl}" alt="Предпросмотр истории">`;const publish=qs('[data-story-publish]');if(publish)publish.disabled=false;}
function viewer(){return qs('[data-story-viewer]');}
function stopStory(){clearTimeout(storyTimer);storyTimer=0;const video=qs('video',qs('[data-story-stage]')||document);video?.pause();}
function closeStory(){stopStory();const v=viewer();if(v){v.classList.remove('open');setTimeout(()=>v.hidden=true,120);}}
function renderStory(){const v=viewer(),story=storyItems[storyIndex];if(!v||!story)return;stopStory();v.hidden=false;v.classList.add('open');
 const progress=qs('[data-story-progress]',v);progress.innerHTML=storyItems.map((_,i)=>`<i class="${i<storyIndex?'done':i===storyIndex?'active':''}"></i>`).join('');
 const avatar=qs('[data-story-author-avatar]',v);if(avatar)avatar.src=storyOwner.avatar_url||`${base}/assets/icons/default-avatar.svg?v=0.10.0`;qs('[data-story-author-name]',v).textContent=storyOwner.display_name||'';qs('[data-story-time]',v).textContent=story.created_at||'';
 const stage=qs('[data-story-stage]',v);const isVideo=String(story.mime_type||'').startsWith('video/');stage.innerHTML=isVideo?`<video src="${esc(story.media_url)}" autoplay playsinline controls></video>`:`<img src="${esc(story.media_url)}" alt="История">`;
 const caption=qs('[data-story-caption]',v);caption.textContent=story.caption||'';caption.hidden=!story.caption;
 const ownerActions=qs('[data-story-owner-actions]',v);const isOwner=Number(story.user_id||storyOwner.id)===Number(K.userId);if(ownerActions)ownerActions.hidden=!isOwner;
 const del=qs('[data-story-delete]',v);if(del){del.hidden=!isOwner;del.dataset.storyDelete=String(story.id);}
 const viewers=qs('[data-story-viewers]',v);if(viewers){viewers.dataset.storyViewers=String(story.id);viewers.hidden=!isOwner;}
 const viewCount=qs('[data-story-view-count]',v);if(viewCount)viewCount.textContent=String(Number(story.view_count||0));
 const viewersPanel=qs('[data-story-viewers-panel]',v);if(viewersPanel)viewersPanel.hidden=true;
 post(`/ajax/story/${story.id}/view`).then(r=>{if(viewCount&&isOwner)viewCount.textContent=String(Number(r.view_count||story.view_count||0));}).catch(()=>{});
 if(isVideo){const video=qs('video',stage);video.addEventListener('ended',nextStory,{once:true});video.addEventListener('loadedmetadata',()=>{const active=qs('.active',progress);if(active)active.style.setProperty('--story-duration',`${Math.min(30,Math.max(2,video.duration||6))}s`);},{once:true});}
 else storyTimer=setTimeout(nextStory,6000);
}
function nextStory(){if(storyIndex<storyItems.length-1){storyIndex++;renderStory();}else closeStory();}
function previousStory(){if(storyIndex>0){storyIndex--;renderStory();}else renderStory();}
async function openStories(userId){try{const r=await json(`/ajax/stories/${Number(userId)}`);storyItems=Array.isArray(r.stories)?r.stories:[];storyOwner=r.user||{};storyIndex=Math.max(0,storyItems.findIndex(x=>!x.viewed));if(!storyItems.length){toast('Активных историй нет.','error');return;}renderStory();}catch(error){toast(error.message,'error');}}
document.addEventListener('click',async event=>{
 const create=event.target.closest('[data-story-create]');if(create){event.preventDefault();openStoryCreator();return;}
 const open=event.target.closest('[data-story-open]');if(open){event.preventDefault();qs('[data-avatar-menu]')?.setAttribute('hidden','');await openStories(open.dataset.storyOpen);return;}
 if(event.target.closest('[data-story-upload-close]')){closeStoryCreator();return;}
 if(event.target.closest('[data-story-choose]')){storyInput()?.click();return;}
 if(event.target.closest('[data-story-publish]')){if(!storyFile)return;const button=event.target.closest('[data-story-publish]');button.disabled=true;const fd=new FormData();fd.append('_csrf',K.csrf||'');fd.append('story',storyFile,storyFile.name);fd.append('caption',qs('[data-story-caption-input]')?.value||'');try{await json('/profile/story',{method:'POST',body:fd,headers:{'X-CSRF-Token':K.csrf||''}});toast('История опубликована.');closeStoryCreator();storyFile=null;if(storyUrl){URL.revokeObjectURL(storyUrl);storyUrl='';}const input=storyInput();if(input)input.value='';setTimeout(()=>window.KovchegNavigation?.reload?.()||location.reload(),180);}catch(error){toast(error.message,'error');button.disabled=false;}return;}
 if(event.target.closest('[data-story-close]')){closeStory();return;}
 if(event.target.closest('[data-story-next]')){nextStory();return;}
 if(event.target.closest('[data-story-prev]')){previousStory();return;}
 const viewersButton=event.target.closest('[data-story-viewers]');if(viewersButton&&!viewersButton.hidden){stopStory();const panel=qs('[data-story-viewers-panel]',viewer());const list=qs('[data-story-viewers-list]',panel||document);if(panel)panel.hidden=false;if(list)list.innerHTML='<p class="story-viewers-loading">Загрузка…</p>';try{const r=await json(`/ajax/story/${viewersButton.dataset.storyViewers}/viewers`);if(list)list.innerHTML=(r.data||[]).length?(r.data||[]).map(row=>`<a class="story-viewer-person" href="${esc(row.profile_url)}"><img src="${esc(row.avatar_url)}" alt=""><span><b>${esc(row.display_name)}</b><small>@${esc(row.username)} · ${esc(row.viewed_at||'')}</small></span></a>`).join(''):'<p class="story-viewers-empty">Историю пока никто не посмотрел.</p>';const count=qs('[data-story-view-count]',viewer());if(count)count.textContent=String(Number(r.count||0));}catch(error){if(list)list.innerHTML=`<p class="story-viewers-empty">${esc(error.message)}</p>`;}return;}
 if(event.target.closest('[data-story-viewers-close]')){const panel=qs('[data-story-viewers-panel]',viewer());if(panel)panel.hidden=true;renderStory();return;}
 const del=event.target.closest('[data-story-delete]');if(del&&!del.hidden){del.disabled=true;try{await post(`/profile/story/${del.dataset.storyDelete}/delete`);storyItems.splice(storyIndex,1);if(!storyItems.length){closeStory();setTimeout(()=>location.reload(),250);}else{storyIndex=Math.min(storyIndex,storyItems.length-1);renderStory();}toast('История удалена.');}catch(error){toast(error.message,'error');del.disabled=false;}return;}
 const reply=event.target.closest('[data-wall-comment-reply]');if(reply){const scope=reply.closest('[data-wall-comments]')||reply.closest('[data-wall-post]')||document;const form=qs('[data-wall-comment-form]',scope);if(form){form.elements.parent_id.value=reply.dataset.wallCommentReply||'';const context=qs('[data-wall-comment-reply-context]',form);if(context){context.hidden=false;const name=qs('b',context);if(name)name.textContent=reply.dataset.wallCommentAuthor||'пользователю';}const area=qs('textarea',form);if(area){area.placeholder=`Ответить ${reply.dataset.wallCommentAuthor||''}`.trim();area.focus();}}return;}
 if(event.target.closest('[data-wall-comment-reply-cancel]')){const form=event.target.closest('[data-wall-comment-form]');if(form){form.elements.parent_id.value='';const context=qs('[data-wall-comment-reply-context]',form);if(context)context.hidden=true;const area=qs('textarea',form);if(area)area.placeholder='Написать комментарий';}return;}
});
document.addEventListener('change',event=>{const input=event.target.closest?.('[data-story-file-input]');if(input)setStoryFile(input.files?.[0]);});

/* Wall photo selection, previews and drag-and-drop. */
function syncInputFiles(input,files){const dt=new DataTransfer();files.forEach(file=>dt.items.add(file));input.files=dt.files;}
function initWallComposer(form){if(!form||form.dataset.wallReady)return;form.dataset.wallReady='1';const input=qs('[data-wall-photos]',form),preview=qs('[data-wall-photo-preview]',form),area=qs('textarea',form);let files=[];
 const render=()=>{if(!preview)return;preview.hidden=files.length===0;preview.innerHTML='';files.forEach((file,index)=>{const url=URL.createObjectURL(file);const figure=document.createElement('figure');figure.innerHTML=`<img src="${url}" alt=""><button type="button" data-wall-photo-remove="${index}">×</button>`;qs('img',figure).onload=()=>URL.revokeObjectURL(url);preview.append(figure);});if(input)syncInputFiles(input,files);};
 const add=incoming=>{for(const file of [...incoming]){if(files.length>=10)break;if(/^image\/(jpeg|png|webp)$/.test(file.type)&&file.size<=12*1024*1024)files.push(file);}render();};
 input?.addEventListener('change',()=>{files=[];add(input.files);});preview?.addEventListener('click',event=>{const b=event.target.closest('[data-wall-photo-remove]');if(!b)return;files.splice(Number(b.dataset.wallPhotoRemove),1);render();});
 form.addEventListener('dragover',event=>{event.preventDefault();form.classList.add('dragging');});form.addEventListener('dragleave',event=>{if(!form.contains(event.relatedTarget))form.classList.remove('dragging');});form.addEventListener('drop',event=>{event.preventDefault();form.classList.remove('dragging');add(event.dataTransfer.files);});
 area?.addEventListener('input',()=>{area.style.height='auto';area.style.height=`${Math.min(180,area.scrollHeight)}px`;});
 form.addEventListener('kovcheg-wall-reset',()=>{files=[];if(area){area.value='';area.style.height='auto';}if(input)input.value='';render();});
 form.addEventListener('submit',event=>{const hasDocuments=Boolean(qs('[data-wall-documents]',form)?.files?.length);if(!(area?.value.trim())&&!files.length&&!hasDocuments){event.preventDefault();toast('Добавьте текст или вложение.','error');}});
}
function initWallComposers(){qsa('[data-wall-composer]').forEach(initWallComposer);}
initWallComposers();
document.addEventListener('kovcheg:pagechange',initWallComposers);

/* Comments, media viewer and share actions. */
let lightbox=qs('[data-wall-media-lightbox]'),lightboxComments=null,lightboxPlaceholder=null;
function restoreLightboxComments(){if(lightboxComments&&lightboxPlaceholder?.parentNode){lightboxPlaceholder.replaceWith(lightboxComments);lightboxComments.hidden=true;}lightboxComments=null;lightboxPlaceholder=null;}
function closeWallMedia(){if(!lightbox)return;restoreLightboxComments();lightbox.hidden=true;document.body.classList.remove('wall-lightbox-open');}
function openWallMedia(src,postId,postNode=null){
 if(!lightbox){lightbox=document.createElement('div');lightbox.className='wall-media-lightbox wall-media-lightbox-150';lightbox.dataset.wallMediaLightbox='';lightbox.innerHTML='<button class="wall-lightbox-close" type="button" aria-label="Закрыть">×</button><div class="wall-lightbox-layout"><div class="wall-lightbox-image"><img alt="Фотография"></div><aside class="wall-lightbox-discussion"><header><b>Комментарии</b><button type="button" data-wall-lightbox-repost>↗ Репост</button></header><div class="wall-lightbox-comments-slot"></div></aside></div>';document.body.append(lightbox);qs('.wall-lightbox-close',lightbox).onclick=closeWallMedia;lightbox.addEventListener('click',e=>{if(e.target===lightbox)closeWallMedia();});qs('[data-wall-lightbox-repost]',lightbox).onclick=()=>{const id=lightbox.dataset.postId,target=qs(`[data-wall-repost="${id}"]`);closeWallMedia();target?.click();};}
 restoreLightboxComments();const post=postNode||qs(`[data-wall-post="${postId}"]`);const comments=qs(`[data-wall-comments="${postId}"]`,post||document);const slot=qs('.wall-lightbox-comments-slot',lightbox);if(comments&&slot){lightboxPlaceholder=document.createComment('wall-comments-placeholder');comments.replaceWith(lightboxPlaceholder);lightboxComments=comments;comments.hidden=false;slot.append(comments);}else if(slot)slot.innerHTML='<p class="muted">Комментарии недоступны.</p>';
 qs('img',lightbox).src=src;lightbox.dataset.postId=String(postId||'');lightbox.hidden=false;document.body.classList.add('wall-lightbox-open');setTimeout(()=>qs('textarea',lightbox)?.focus(),80);
}
document.addEventListener('click',async event=>{
 const media=event.target.closest('[data-wall-media]');if(media){openWallMedia(media.dataset.wallMedia,media.dataset.wallPostId,media.closest('[data-wall-post]'));return;}
 const toggle=event.target.closest('[data-wall-comments-toggle]');if(toggle){const section=qs(`[data-wall-comments="${toggle.dataset.wallCommentsToggle}"]`);if(section){section.hidden=!section.hidden;if(!section.hidden)qs('textarea',section)?.focus();}return;}
 const share=event.target.closest('[data-wall-share]');if(share){const url=new URL(share.dataset.shareUrl||location.href,location.href).href;try{if(navigator.clipboard?.writeText)await navigator.clipboard.writeText(url);else{const field=document.createElement('textarea');field.value=url;field.style.position='fixed';field.style.opacity='0';document.body.append(field);field.select();document.execCommand('copy');field.remove();}toast('Ссылка на запись скопирована.');}catch(error){toast('Не удалось скопировать ссылку.','error');}return;}
 const remove=event.target.closest('[data-wall-comment-delete]');if(remove){remove.disabled=true;try{await post(`/profile/wall/comment/${remove.dataset.wallCommentDelete}/delete`);const comment=remove.closest('[data-wall-comment]');const section=remove.closest('[data-wall-comments]');const postId=section?.dataset.wallComments||lightbox?.dataset.postId||'';comment?.remove();qsa(`[data-wall-post="${postId}"] [data-wall-comment-count]`).forEach(count=>{const n=Math.max(0,Number(count.textContent||0)-1);count.textContent=n?String(n):'';});toast('Комментарий удалён.');}catch(error){remove.disabled=false;toast(error.message,'error');}return;}
});
document.addEventListener('submit',async event=>{
 const form=event.target.closest('[data-wall-comment-form]');if(!form)return;event.preventDefault();const body=form.elements.body.value.trim();const hasTracks=!!qs('input[data-attached-track]',form);if(!body&&!hasTracks)return;const submit=qs('button[type="submit"]',form);if(submit)submit.disabled=true;try{const r=await jsonUrl(form.action,{method:'POST',body:new FormData(form)});const section=form.closest('[data-wall-comments]');let list=null;if(Number(r.parent_id||0)){const parent=qs(`[data-wall-comment="${Number(r.parent_id)}"]`,section||document);list=qs('[data-wall-comment-replies]',parent||document);}if(!list)list=qs('[data-wall-comments-list]',section||document);if(list&&r.html)list.insertAdjacentHTML('beforeend',r.html);form.elements.body.value='';form.elements.parent_id.value='';document.dispatchEvent(new CustomEvent('kovcheg:track-selection-clear',{detail:{form}}));const context=qs('[data-wall-comment-reply-context]',form);if(context)context.hidden=true;const area=qs('textarea',form);if(area)area.placeholder='Написать комментарий';const postId=section?.dataset.wallComments||lightbox?.dataset.postId||'';qsa(`[data-wall-post="${postId}"] [data-wall-comment-count]`).forEach(count=>count.textContent=r.count?String(r.count):'');}catch(error){toast(error.message,'error');}finally{if(submit)submit.disabled=false;}
});

document.addEventListener('keydown',event=>{const area=event.target.closest?.('[data-wall-comment-form] textarea');if(area&&event.key==='Enter'&&!event.shiftKey){event.preventDefault();area.closest('form')?.requestSubmit();}});

/* Keep the old like handler compatible with the new action markup. */
document.addEventListener('kovcheg-wall-like',()=>{});

document.addEventListener('keydown',event=>{if(event.key==='Escape'){if(viewer()&&!viewer().hidden)closeStory();if(lightbox&&!lightbox.hidden)closeWallMedia();}});
})();
(()=>{
'use strict';
const K=window.KOVCHEG||{};
const base=String(K.baseUrl||'').replace(/\/$/,'');
const qs=(s,r=document)=>r.querySelector(s);
const qsa=(s,r=document)=>[...r.querySelectorAll(s)];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function request(url,opt={}){const response=await fetch(url.startsWith('http')?url:base+url,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json',...(opt.headers||{})},...opt});const data=await response.json().catch(()=>({ok:false,error:`Ошибка ${response.status}`}));if(!response.ok||data.ok===false)throw new Error(data.error||`Ошибка ${response.status}`);return data;}
async function post(url,data={}){const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([key,value])=>fd.append(key,String(value)));if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');return request(url,{method:'POST',body:fd});}
function toast(text,type='success'){if(window.KovchegShowToast)return window.KovchegShowToast(text,{type});const box=qs('#toast-stack');if(!box)return;const item=document.createElement('div');item.className=`message-toast generic-toast toast-${type}`;item.innerHTML=`<div><b>${type==='error'?'Ошибка':'Готово'}</b><p>${esc(text)}</p></div><button type="button">×</button>`;qs('button',item).onclick=()=>item.remove();box.append(item);setTimeout(()=>item.remove(),5000);}

/* Profile menu is controlled by the unified 2.3.10 delegate below. */

/* Wall publication stays on the page, including multiple photos. */
function insertWallPost(form,html){if(!html)return;const shell=form.closest('[data-profile-wall],.feed-page')||document;const feed=qs('[data-wall-feed]',shell)||qs('[data-wall-feed]');if(!feed)return;qs('[data-wall-empty]',feed)?.remove();const template=document.createElement('template');template.innerHTML=html.trim();const postNode=template.content.firstElementChild;if(!postNode)return;postNode.classList.add('wall-post-new');feed.prepend(postNode);setTimeout(()=>postNode.classList.remove('wall-post-new'),500);const counter=qs('[data-wall-post-count]',shell);if(counter){const old=Number((counter.textContent.match(/\d+/)||[0])[0]);counter.textContent=`${old+1} записей`;}}
async function submitAjaxWallComposer(form){if(form.dataset.ajaxSubmitting==='1')return;const body=qs('textarea[name="body"]',form)?.value.trim()||'';const hasFiles=qsa('input[type="file"]',form).some(input=>input.files?.length);const hasTracks=!!qs('input[data-attached-track]',form);if(!body&&!hasFiles&&!hasTracks){toast('Добавьте текст или вложение.','error');return;}const submit=qs('button[type="submit"]',form);form.dataset.ajaxSubmitting='1';if(submit)submit.disabled=true;form.classList.add('is-uploading');try{const result=await request(form.action,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'}});insertWallPost(form,result.html||'');form.dispatchEvent(new CustomEvent('kovcheg-wall-reset',{detail:result}));toast(result.message||'Запись опубликована.');}catch(error){toast(error.message,'error');}finally{form.classList.remove('is-uploading');delete form.dataset.ajaxSubmitting;if(submit)submit.disabled=false;}}
function bindAjaxWallComposer(form){if(!form||form.dataset.ajaxWallReady==='237')return;form.dataset.ajaxWallReady='237';}
document.addEventListener('submit',event=>{const form=event.target.closest?.('[data-wall-composer]');if(!form)return;event.preventDefault();event.stopImmediatePropagation();submitAjaxWallComposer(form);},true);
function bindAllWallComposers(root=document){if(root.matches?.('[data-wall-composer]'))bindAjaxWallComposer(root);qsa('[data-wall-composer]',root).forEach(bindAjaxWallComposer);}
bindAllWallComposers();new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1)bindAllWallComposers(node);}))).observe(document.documentElement,{subtree:true,childList:true});document.addEventListener('kovcheg:pagechange',()=>bindAllWallComposers());

/* Post reactions use the same set as messages. */
function renderPostReaction(card,result){const total=qs('[data-wall-reaction-total]',card);const mine=qs('[data-wall-my-reaction]',card);const main=qs('[data-wall-reaction-menu]',card);if(total)total.textContent=result.total?String(result.total):'';if(mine)mine.textContent=result.mine||'♡';main?.classList.toggle('active',Boolean(result.mine));const summary=qs('[data-wall-reaction-summary]',card);if(summary){summary.hidden=!(result.items||[]).length;summary.innerHTML=(result.items||[]).map(item=>`<button type="button" class="${item.mine?'mine':''}" data-wall-react="${esc(item.emoji)}" data-post-id="${card.dataset.wallPost}">${esc(item.emoji)} <span>${Number(item.count||0)}</span></button>`).join('');}qsa('[data-wall-reaction-picker] button',card).forEach(button=>button.classList.toggle('active',button.dataset.wallReact===result.mine));}
document.addEventListener('click',async event=>{
 const trigger=event.target.closest('[data-wall-reaction-menu]');
 if(trigger){event.preventDefault();const card=trigger.closest('[data-wall-post]');const picker=qs('[data-wall-reaction-picker]',card);qsa('[data-wall-reaction-picker]').forEach(item=>{if(item!==picker)item.hidden=true;});if(picker)picker.hidden=!picker.hidden;return;}
 const reaction=event.target.closest('[data-wall-react]');
 if(reaction){event.preventDefault();const card=reaction.closest('[data-wall-post]');if(!card)return;reaction.disabled=true;try{const result=await post(`/profile/wall/${reaction.dataset.postId}/react`,{emoji:reaction.dataset.wallReact});renderPostReaction(card,result);qs('[data-wall-reaction-picker]',card)?.setAttribute('hidden','');}catch(error){toast(error.message,'error');}finally{reaction.disabled=false;}return;}
 if(!event.target.closest('.wall-reaction-wrap'))qsa('[data-wall-reaction-picker]').forEach(item=>item.hidden=true);
});

/* Global online heartbeat. It updates the current page and the open dialog. */
let presenceBusy=false;
function presenceIds(){return [...new Set(qsa('[data-presence-user]').map(node=>Number(node.dataset.presenceUser||0)).filter(Boolean))];}
function applyPresence(row){qsa(`[data-presence-user="${Number(row.id)}"]`).forEach(node=>{const label=row.label||(row.online?'в сети':'не в сети'),online=Boolean(row.online);if(node.textContent!==label)node.textContent=label;if(node.classList.contains('presence-online')!==online)node.classList.toggle('presence-online',online);if(node.classList.contains('presence-offline')===online)node.classList.toggle('presence-offline',!online);});}
async function heartbeat(){if(presenceBusy||!K.userId)return;presenceBusy=true;try{const result=await post('/ajax/presence',{user_ids:presenceIds().join(',')});(result.data||[]).forEach(applyPresence);}catch(_){ }finally{presenceBusy=false;}}
if(K.userId){setTimeout(heartbeat,120);setInterval(heartbeat,30000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)heartbeat();});window.addEventListener('focus',heartbeat);}

/* Legacy forced chat-bottom observer removed in 2.3.15. */

/* The bell and menus inherit current theme immediately after a theme switch. */
qsa('[data-quick-theme]').forEach(button=>button.addEventListener('click',()=>document.dispatchEvent(new CustomEvent('kovcheg:account-menu-close'))));
})();
(()=>{
'use strict';
const K=window.KOVCHEG||{};
const base=String(K.baseUrl||'').replace(/\/$/,'');
const qs=(s,r=document)=>r.querySelector(s);
const qsa=(s,r=document)=>[...r.querySelectorAll(s)];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function request(path,opt={}){const r=await fetch(path.startsWith('http')?path:base+path,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json',...(opt.headers||{})},...opt});const j=await r.json().catch(()=>({ok:false,error:`Ошибка ${r.status}`}));if(!r.ok||j.ok===false)throw new Error(j.error||`Ошибка ${r.status}`);return j;}
async function post(path,data={}){const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([k,v])=>fd.append(k,String(v)));if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');return request(path,{method:'POST',body:fd});}
function toast(text,type='success'){if(window.KovchegShowToast)return window.KovchegShowToast(text,{type});const stack=qs('#toast-stack');if(!stack)return;const n=document.createElement('div');n.className=`message-toast generic-toast toast-${type}`;n.innerHTML=`<div><b>${type==='error'?'Ошибка':'Готово'}</b><p>${esc(text)}</p></div><button type="button">×</button>`;qs('button',n).onclick=()=>n.remove();stack.append(n);setTimeout(()=>n.remove(),4800);}

/* Repost to the own wall or directly to a colleague. */
let repostPostId=0,repostContactsLoaded=false;
function repostModal(){let modal=qs('[data-repost-modal]');if(modal)return modal;modal=document.createElement('div');modal.className='modal repost-modal-110';modal.dataset.repostModal='';modal.hidden=true;modal.innerHTML=`<div class="modal-card repost-card-110"><header><div><b>Поделиться записью</b><small>На своей стене или в сообщении коллеге</small></div><button type="button" data-repost-close>×</button></header><label class="repost-note-110">Комментарий<textarea rows="3" maxlength="2000" data-repost-comment placeholder="Можно добавить свой текст"></textarea></label><div class="repost-destinations-110"><button type="button" class="repost-own-wall-110" data-repost-wall><span>↗</span><div><b>На своей стене</b><small>Запись появится в вашей ленте</small></div></button><section><header><b>В сообщении коллеге</b><input type="search" data-repost-contact-search placeholder="Найти коллегу"></header><div class="repost-contact-list-110" data-repost-contacts><p>Загрузка коллег…</p></div></section></div></div>`;document.body.append(modal);return modal;}
function closeRepost(){const modal=repostModal();modal.classList.remove('open');setTimeout(()=>modal.hidden=true,120);}
async function loadRepostContacts(){const modal=repostModal(),box=qs('[data-repost-contacts]',modal);if(repostContactsLoaded)return;try{const r=await request('/ajax/contacts');const rows=r.data||[];box.innerHTML=rows.length?rows.map(row=>`<button type="button" data-repost-user="${Number(row.id)}" data-search-text="${esc(`${row.display_name} @${row.username}`.toLowerCase())}"><img src="${esc(row.avatar_url)}" alt=""><span><b>${esc(row.display_name)}</b><small>@${esc(row.username)}${row.online?' · в сети':''}</small></span></button>`).join(''):'<p>В списке коллег пока никого нет.</p>';repostContactsLoaded=true;}catch(error){box.innerHTML=`<p>${esc(error.message)}</p>`;}}
async function submitRepost(destination,userId=0){const modal=repostModal(),comment=qs('[data-repost-comment]',modal)?.value||'';qsa('button',modal).forEach(b=>b.disabled=true);try{const r=await post(`/profile/wall/${repostPostId}/repost`,{destination,user_id:userId,comment});if(destination==='wall'&&r.html){const feed=qs('[data-wall-feed]');if(feed){const tpl=document.createElement('template');tpl.innerHTML=r.html.trim();feed.prepend(tpl.content.firstElementChild);}toast('Запись опубликована на вашей стене.');}else{toast('Запись отправлена коллеге.');}closeRepost();}catch(error){toast(error.message,'error');}finally{qsa('button',modal).forEach(b=>b.disabled=false);}}
document.addEventListener('click',async event=>{
 const trigger=event.target.closest('[data-wall-repost]');if(trigger){event.preventDefault();repostPostId=Number(trigger.dataset.wallRepost||0);const modal=repostModal();modal.hidden=false;requestAnimationFrame(()=>modal.classList.add('open'));await loadRepostContacts();qs('[data-repost-comment]',modal)?.focus();return;}
 if(event.target.closest('[data-repost-close]')||event.target===qs('[data-repost-modal]')){closeRepost();return;}
 if(event.target.closest('[data-repost-wall]')){await submitRepost('wall');return;}
 const person=event.target.closest('[data-repost-user]');if(person){await submitRepost('message',Number(person.dataset.repostUser));return;}
});
document.addEventListener('input',event=>{const input=event.target.closest('[data-repost-contact-search]');if(!input)return;const q=input.value.trim().toLowerCase();qsa('[data-repost-user]',repostModal()).forEach(row=>row.hidden=q!==''&&!String(row.dataset.searchText||'').includes(q));});

/* Legacy repeated forceLatest timers removed in 2.3.15. */

/* Close story viewer lists with Escape without closing the whole viewer first. */
document.addEventListener('keydown',event=>{if(event.key!=='Escape')return;const panel=qs('[data-story-viewers-panel]');if(panel&&!panel.hidden){panel.hidden=true;event.stopImmediatePropagation();}} ,true);
})();
(()=>{
'use strict';
const qs=(s,r=document)=>r.querySelector(s);
const K=window.KOVCHEG||{};

/* Extra client-side guard: never duplicate the currently opened dialogue. */
const previousIncoming=window.KovchegIncomingNotify;
if(typeof previousIncoming==='function'){
 window.KovchegIncomingNotify=(notification)=>{
  const active=Number(qs('.messenger')?.dataset.chatId||0);
  if(active>0&&Number(notification?.chat_id||0)===active)return null;
  return previousIncoming(notification);
 };
}

/* Tell the active service worker to replace its old cache immediately. */
if('serviceWorker' in navigator){
 navigator.serviceWorker.ready.then(reg=>reg.active?.postMessage({type:'SKIP_WAITING'})).catch(()=>{});
}
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const csrf=()=>K.csrf||q('meta[name="csrf-token"]')?.content||'';
function relativeUrl(raw){const u=new URL(raw,location.href),base=new URL(K.baseUrl||location.origin,location.href).pathname.replace(/\/$/,'');return u.pathname.startsWith(base+'/')?u.pathname.slice(base.length)+u.search:u.pathname+u.search;}
async function request(url,data={}){const target=/^https?:\/\//i.test(String(url))?String(url):`${String(K.baseUrl||'').replace(/\/$/,'')}${String(url).startsWith('/')?'':'/'}${String(url)}`;const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([k,v])=>fd.append(k,String(v)));if(!fd.has('_csrf'))fd.append('_csrf',csrf());const r=await fetch(target,{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}});const j=await r.json().catch(()=>({}));if(!r.ok||j.ok===false)throw new Error(j.error||j.message||'Не удалось выполнить действие.');return j;}
function toast(t,type='success'){window.KovchegShowToast?.(t,{type});}
function escapeHtml(v){const d=document.createElement('div');d.textContent=String(v??'');return d.innerHTML;}
// Black list actions.
document.addEventListener('click',async e=>{const b=e.target.closest('[data-block-user],[data-unblock-user],[data-profile-block],[data-profile-unblock]');if(!b)return;e.preventDefault();b.disabled=true;try{const id=b.dataset.blockUser||b.dataset.unblockUser||b.dataset.profileBlock||b.dataset.profileUnblock;const action=(b.dataset.blockUser||b.dataset.profileBlock)?'block':'unblock';await request(`/profile/${id}/${action}`);toast(action==='block'?'Пользователь добавлен в чёрный список.':'Пользователь удалён из чёрного списка.');if(b.closest('.blacklist-row'))b.closest('.blacklist-row').remove();else location.reload();}catch(err){toast(err.message,'error');}finally{b.disabled=false;}});
// Pencil modal shows colleagues immediately.
async function fillDirectContacts(){const box=q('[data-direct-contacts]');if(!box||box.dataset.loaded)return;box.dataset.loaded='1';try{const j=await fetch(`${K.baseUrl}/ajax/contacts`,{headers:{Accept:'application/json'},credentials:'same-origin'}).then(r=>r.json());const rows=j.data||[];box.innerHTML=rows.length?rows.map(u=>`<button type="button" class="direct-contact" data-direct-contact="${u.id}" data-name="${escapeHtml(u.display_name)}" data-username="${escapeHtml(u.username)}"><span class="avatar-sm avatar-photo"><img src="${escapeHtml(u.avatar_url)}" alt=""></span><span><b>${escapeHtml(u.display_name)}</b><small>@${escapeHtml(u.username)}${u.online?' · в сети':''}</small></span></button>`).join(''):'<p class="muted">Сначала добавьте пользователей в коллеги.</p>'; }catch(err){box.innerHTML=`<p class="muted">${escapeHtml(err.message)}</p>`;}}
document.addEventListener('click',e=>{if(e.target.closest('[data-modal="new-direct"]'))setTimeout(fillDirectContacts,0);const b=e.target.closest('[data-direct-contact]');if(!b)return;const form=b.closest('[data-open-direct]'),hidden=q('input[name="user_id"]',form),search=q('[data-user-search]',form),submit=q('[data-user-submit]',form);if(hidden)hidden.value=b.dataset.directContact;if(search)search.value=`${b.dataset.name} · @${b.dataset.username}`;if(submit)submit.disabled=false;qa('.direct-contact',form).forEach(x=>x.classList.toggle('active',x===b));});
// Avatar comments and replies.
document.addEventListener('click',e=>{const reply=e.target.closest('[data-avatar-comment-reply]');if(reply){const scope=reply.closest('[data-profile-avatar-viewer]')||document;const form=q('[data-avatar-comment-form]',scope);if(!form)return;form.elements.parent_id.value=reply.dataset.avatarCommentReply;const ctx=q('[data-avatar-comment-context]',form);ctx.hidden=false;q('b',ctx).textContent=reply.dataset.avatarCommentAuthor||'пользователю';q('textarea',form).focus();}if(e.target.closest('[data-avatar-comment-cancel]')){const form=e.target.closest('[data-avatar-comment-form]')||q('[data-avatar-comment-form]');if(form){form.elements.parent_id.value='';q('[data-avatar-comment-context]',form).hidden=true;}}});
q('[data-avatar-comment-form]')?.addEventListener('submit',async e=>{e.preventDefault();const f=e.currentTarget,b=q('button[type="submit"]',f);b.disabled=true;try{const profileId=Number(f.dataset.avatarProfileId||0);const j=await request(profileId?`/avatar/${profileId}/comment`:f.action,new FormData(f));const scope=f.closest('[data-profile-avatar-viewer]')||document;const list=q('[data-avatar-comments-list]',scope);if(Number(j.parent_id)){const parent=q(`[data-avatar-comment-id="${j.parent_id}"]`,scope),replies=q('.avatar-comment-replies',parent);if(replies)replies.insertAdjacentHTML('beforeend',j.html);else parent?.insertAdjacentHTML('beforeend',`<div class="avatar-comment-replies">${j.html}</div>`);}else list?.insertAdjacentHTML('beforeend',j.html);f.reset();q('[data-avatar-comment-context]',f).hidden=true;const c=q('[data-avatar-comment-count]',scope);if(c)c.textContent=j.count;}catch(err){toast(err.message,'error');}finally{b.disabled=false;}});
document.addEventListener('keydown',e=>{const area=e.target.closest?.('[data-avatar-comment-form] textarea');if(area&&e.key==='Enter'&&!e.shiftKey){e.preventDefault();area.closest('form')?.requestSubmit();}});
document.addEventListener('click',async e=>{const b=e.target.closest('[data-avatar-comment-delete]');if(!b)return;b.disabled=true;try{await request(`/avatar/comment/${b.dataset.avatarCommentDelete}/delete`);b.closest('[data-avatar-comment-id]')?.remove();}catch(err){toast(err.message,'error');b.disabled=false;}});
// Avatar repost.
const repostModal=q('[data-avatar-repost-modal]');document.addEventListener('click',async e=>{if(e.target.closest('[data-avatar-repost-open]')){if(repostModal)repostModal.hidden=false;}const wall=e.target.closest('[data-avatar-repost-wall]');if(wall){wall.disabled=true;try{await request(`/avatar/${wall.dataset.avatarRepostWall}/repost`,{destination:'wall',comment:q('[data-avatar-repost-comment]')?.value||''});toast('Фотография опубликована на вашей стене.');repostModal.hidden=true;}catch(err){toast(err.message,'error');}finally{wall.disabled=false;}}const message=e.target.closest('[data-avatar-repost-message]');if(message){const box=q('[data-avatar-repost-contacts]');box.hidden=false;box.innerHTML='<p class="muted">Загрузка коллег…</p>';try{const j=await fetch(`${K.baseUrl}/ajax/contacts`,{headers:{Accept:'application/json'},credentials:'same-origin'}).then(r=>r.json());box.innerHTML=(j.data||[]).map(u=>`<button type="button" class="direct-contact" data-avatar-repost-target="${u.id}" data-profile="${message.dataset.avatarRepostMessage}"><span class="avatar-sm avatar-photo"><img src="${escapeHtml(u.avatar_url)}" alt=""></span><span><b>${escapeHtml(u.display_name)}</b><small>@${escapeHtml(u.username)}</small></span></button>`).join('')||'<p class="muted">Нет коллег для отправки.</p>';}catch(err){box.innerHTML=`<p class="muted">${escapeHtml(err.message)}</p>`;}}const target=e.target.closest('[data-avatar-repost-target]');if(target){target.disabled=true;try{const j=await request(`/avatar/${target.dataset.profile}/repost`,{destination:'message',user_id:target.dataset.avatarRepostTarget,comment:q('[data-avatar-repost-comment]')?.value||''});toast('Фотография отправлена в сообщение.');repostModal.hidden=true;}catch(err){toast(err.message,'error');}finally{target.disabled=false;}}});
// Custom voice message player.
function fmt(sec){sec=Math.max(0,Math.floor(Number(sec)||0));return `${Math.floor(sec/60)}:${String(sec%60).padStart(2,'0')}`;}
function initVoicePlayer(box){if(box.dataset.ready)return;box.dataset.ready='1';const a=q('audio',box),play=q('[data-voice-message-play]',box),range=q('[data-voice-message-range]',box),time=q('[data-voice-message-time]',box),speed=q('[data-voice-speed]',box);if(!a)return;play.onclick=()=>{if(a.paused){qa('[data-voice-message] audio').forEach(x=>{if(x!==a)x.pause();});a.play();play.textContent='❚❚';}else{a.pause();play.textContent='▶';}};a.onloadedmetadata=()=>{time.textContent=fmt(a.duration)};a.ontimeupdate=()=>{if(a.duration)range.value=Math.round(a.currentTime/a.duration*1000);time.textContent=`${fmt(a.currentTime)} / ${fmt(a.duration)}`;};a.onended=()=>{play.textContent='▶';range.value=0;};range.oninput=()=>{if(a.duration)a.currentTime=Number(range.value)/1000*a.duration;};speed.onclick=()=>{const speeds=[1,1.5,2],next=speeds[(speeds.indexOf(a.playbackRate)+1)%speeds.length];a.playbackRate=next;speed.textContent=`${next}×`;};}
qa('[data-voice-message]').forEach(initVoicePlayer);new MutationObserver(ms=>ms.forEach(m=>m.addedNodes.forEach(n=>{if(n.nodeType!==1)return;if(n.matches?.('[data-voice-message]'))initVoicePlayer(n);qa('[data-voice-message]',n).forEach(initVoicePlayer);}))).observe(document.body,{childList:true,subtree:true});
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/* Compact paperclip menu and document previews on the wall. */
function initWallAttachments(form){
 if(!form||form.dataset.attach150)return;form.dataset.attach150='1';
 const toggle=q('[data-wall-attachment-toggle]',form),menu=q('[data-wall-attachment-menu]',form),documents=q('[data-wall-documents]',form),preview=q('[data-wall-document-preview]',form);
 const close=()=>{if(menu)menu.hidden=true;toggle?.setAttribute('aria-expanded','false');};
 toggle?.addEventListener('click',e=>{e.stopPropagation();if(menu){menu.hidden=!menu.hidden;toggle.setAttribute('aria-expanded',String(!menu.hidden));}});
 menu?.addEventListener('click',e=>{if(e.target.closest('label'))setTimeout(close,0);});
 const render=()=>{if(!preview)return;const files=[...(documents?.files||[])];preview.hidden=!files.length;preview.innerHTML=files.map(file=>`<span class="wall-document-chip"><i>📄</i><b>${esc(file.name)}</b><small>${Math.max(1,Math.round(file.size/1024))} КБ</small></span>`).join('');};
 documents?.addEventListener('change',render);
 form.addEventListener('kovcheg-wall-reset',()=>{if(documents)documents.value='';if(preview){preview.hidden=true;preview.innerHTML='';}close();});
}
qa('[data-wall-composer]').forEach(initWallAttachments);
new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType!==1)return;if(node.matches?.('[data-wall-composer]'))initWallAttachments(node);qa('[data-wall-composer]',node).forEach(initWallAttachments);}))).observe(document.body,{childList:true,subtree:true});
document.addEventListener('click',e=>{if(!e.target.closest('[data-wall-attachment-tools]'))qa('[data-wall-attachment-menu]').forEach(menu=>menu.hidden=true);});
document.addEventListener('keydown',e=>{if(e.key==='Escape')qa('[data-wall-attachment-menu]').forEach(menu=>menu.hidden=true);});

/* Personal weather block. City is stored in user settings; administrator can disable the widget globally. */
const weatherText=code=>({0:'Ясно',1:'Преимущественно ясно',2:'Переменная облачность',3:'Пасмурно',45:'Туман',48:'Изморозь',51:'Лёгкая морось',53:'Морось',55:'Сильная морось',61:'Небольшой дождь',63:'Дождь',65:'Сильный дождь',71:'Небольшой снег',73:'Снег',75:'Сильный снег',77:'Снежные зёрна',80:'Кратковременный дождь',81:'Ливень',82:'Сильный ливень',85:'Снегопад',86:'Сильный снегопад',95:'Гроза',96:'Гроза с градом',99:'Сильная гроза с градом'}[Number(code)]||'Погода');
const weatherIcon=code=>{code=Number(code);if(code===0)return'☀';if(code<=2)return'🌤';if(code===3)return'☁';if([45,48].includes(code))return'🌫';if(code>=51&&code<=67||code>=80&&code<=82)return'🌧';if(code>=71&&code<=77||code>=85&&code<=86)return'❄';if(code>=95)return'⛈';return'◌';};
const weatherFetch=async(url,timeout=9000)=>{const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),timeout);try{const response=await fetch(url,{cache:'no-store',signal:controller.signal,headers:{Accept:'application/json'}});if(!response.ok)throw new Error(`HTTP ${response.status}`);return await response.json();}finally{clearTimeout(timer);}};
function weatherQueries(raw){
 const original=String(raw||'').trim().replace(/\s+/g,' '),withoutType=original.replace(/^(г\.?|город|с\.?|село|д\.?|деревня|п\.?|пос[её]лок|пгт)\s+/iu,'').trim(),withoutOrdinal=withoutType.replace(/\s+\d+\s*[-–—]?\s*(?:е|й|я|ое|ая)?\s*$/iu,'').trim(),firstPart=withoutType.split(',')[0].trim();
 return [...new Set([original,withoutType,withoutOrdinal,firstPart,`${original}, Россия`,`${withoutOrdinal}, Россия`].filter(v=>v.length>=2))];
}
function coordinatesFromCity(value){const match=String(value||'').trim().match(/^(-?\d{1,2}(?:[.,]\d+)?)\s*[,; ]\s*(-?\d{1,3}(?:[.,]\d+)?)$/);if(!match)return null;const latitude=Number(match[1].replace(',','.')),longitude=Number(match[2].replace(',','.'));return Number.isFinite(latitude)&&Number.isFinite(longitude)&&Math.abs(latitude)<=90&&Math.abs(longitude)<=180?{latitude,longitude,name:'Выбранная точка',admin1:''}:null;}
function cacheWeatherPlace(key,place){try{localStorage.setItem(key,JSON.stringify({...place,savedAt:Date.now()}));}catch(_){ }}
async function findWeatherPlace(city){
 const direct=coordinatesFromCity(city);if(direct)return direct;
 const cacheKey=`kovcheg-weather-place:${String(city).toLocaleLowerCase('ru-RU')}`;try{const cached=JSON.parse(localStorage.getItem(cacheKey)||'null');if(cached&&Date.now()-Number(cached.savedAt||0)<7*86400000&&Number.isFinite(Number(cached.latitude)))return cached;}catch(_){ }
 const queries=weatherQueries(city);
 for(const query of queries){
  try{const geo=await weatherFetch(`https://geocoding-api.open-meteo.com/v1/search?name=${encodeURIComponent(query)}&count=10&language=ru&format=json`);const results=Array.isArray(geo.results)?geo.results:[];const place=results.find(item=>String(item.country_code||'').toUpperCase()==='RU')||results[0];if(place){const found={latitude:Number(place.latitude),longitude:Number(place.longitude),name:place.name||query,admin1:place.admin1||place.admin2||'',country:place.country||''};cacheWeatherPlace(cacheKey,found);return found;}}catch(_){ }
 }
 for(const query of queries){
  try{const rows=await weatherFetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&accept-language=ru&q=${encodeURIComponent(query)}`);const row=Array.isArray(rows)?rows.find(item=>item.address?.country_code==='ru')||rows[0]:null;if(row){const address=row.address||{},found={latitude:Number(row.lat),longitude:Number(row.lon),name:address.city||address.town||address.village||address.hamlet||address.municipality||row.name||query,admin1:address.state||address.region||address.county||'',country:address.country||''};cacheWeatherPlace(cacheKey,found);return found;}}catch(_){ }
 }
 for(const query of queries){
  try{const data=await weatherFetch(`https://photon.komoot.io/api/?limit=5&lang=ru&q=${encodeURIComponent(query)}`);const feature=(data.features||[]).find(item=>String(item.properties?.countrycode||'').toUpperCase()==='RU')||(data.features||[])[0];if(feature){const props=feature.properties||{},coords=feature.geometry?.coordinates||[],found={latitude:Number(coords[1]),longitude:Number(coords[0]),name:props.name||props.city||query,admin1:props.state||props.county||'',country:props.country||''};cacheWeatherPlace(cacheKey,found);return found;}}catch(_){ }
 }
 throw new Error('Населённый пункт не найден. Укажите его вместе с областью, например: Никольское 3-е, Воронежская область. Можно также вставить координаты.');
}
async function loadWeather(widget){
 if(widget.dataset.weatherReady)return;widget.dataset.weatherReady='1';const city=widget.dataset.weatherCity?.trim();if(!city)return;
 const loading=q('[data-weather-loading]',widget),content=q('[data-weather-content]',widget),error=q('[data-weather-error]',widget);
 try{
  const place=await findWeatherPlace(city);if(!Number.isFinite(place.latitude)||!Number.isFinite(place.longitude))throw new Error('Не удалось определить координаты населённого пункта.');
  const params=new URLSearchParams({latitude:String(place.latitude),longitude:String(place.longitude),current:'temperature_2m,apparent_temperature,weather_code,wind_speed_10m',daily:'weather_code,temperature_2m_max,temperature_2m_min',timezone:'auto',forecast_days:'3'});
  const data=await weatherFetch(`https://api.open-meteo.com/v1/forecast?${params}`,11000);const current=data.current||{};q('[data-weather-temp]',widget).textContent=`${Math.round(Number(current.temperature_2m)||0)}°`;q('[data-weather-description]',widget).textContent=weatherText(current.weather_code);q('[data-weather-feels]',widget).textContent=`Ощущается как ${Math.round(Number(current.apparent_temperature)||0)}° · ветер ${Math.round(Number(current.wind_speed_10m)||0)} км/ч`;q('[data-weather-icon]',widget).textContent=weatherIcon(current.weather_code);q('[data-weather-city-label]',widget).textContent=[place.name,place.admin1].filter(Boolean).join(', ');q('[data-weather-updated]',widget).textContent='сейчас';
  const days=q('[data-weather-days]',widget),daily=data.daily||{};if(days)days.innerHTML=(daily.time||[]).map((date,i)=>{const label=i===0?'Сегодня':i===1?'Завтра':new Intl.DateTimeFormat('ru-RU',{weekday:'short'}).format(new Date(`${date}T12:00:00`));return `<div><span>${esc(label)}</span><i>${weatherIcon(daily.weather_code?.[i])}</i><b>${Math.round(Number(daily.temperature_2m_max?.[i])||0)}°</b><small>${Math.round(Number(daily.temperature_2m_min?.[i])||0)}°</small></div>`;}).join('');
  if(loading)loading.hidden=true;if(error)error.hidden=true;if(content)content.hidden=false;
 }catch(err){if(loading)loading.hidden=true;if(content)content.hidden=true;if(error){error.hidden=false;error.textContent=err?.name==='AbortError'?'Сервис погоды отвечает слишком долго. Попробуйте ещё раз.':(err.message||'Не удалось загрузить погоду.');}}
}
qa('[data-weather-widget]').forEach(loadWeather);

/* Keep the profile center independently scrollable after dynamic height changes. */
function repairProfileScroll(){qa('.vk-profile-main').forEach(main=>{main.style.minHeight='0';if(main.scrollHeight>main.clientHeight)main.dataset.scrollable='1';});}
repairProfileScroll();window.addEventListener('resize',repairProfileScroll);setTimeout(repairProfileScroll,250);
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},base=String(K.baseUrl||'').replace(/\/$/,''),q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const url=p=>/^https?:/i.test(p)?p:`${base}${p.startsWith('/')?p:`/${p}`}`;
async function api(path,opt={}){const response=await fetch(url(path),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json',...(opt.headers||{})},...opt});const data=await response.json().catch(()=>({ok:false,error:`Ошибка ${response.status}`}));if(!response.ok||data.ok===false)throw new Error(data.error||`Ошибка ${response.status}`);return data;}
async function post(path,data={}){const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([key,value])=>fd.append(key,String(value)));if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');return api(path,{method:'POST',body:fd});}
function toast(text,type='success'){window.KovchegShowToast?.(text,{type});}
function syncFiles(input,files){if(!input)return;const dt=new DataTransfer();files.forEach(file=>dt.items.add(file));input.files=dt.files;}
function hasComposerContent(form){return Boolean(q('textarea[name="body"]',form)?.value.trim()||qa('input[type="file"]',form).some(input=>input.files?.length));}
function insertPost(shell,html){if(!html)return;const feed=q('[data-wall-feed]',shell.closest('[data-profile-wall],.feed-page')||document)||q('[data-wall-feed]');if(!feed)return;q('[data-wall-empty]',feed)?.remove();const tpl=document.createElement('template');tpl.innerHTML=String(html).trim();const node=tpl.content.firstElementChild;if(node){node.classList.add('wall-post-new');feed.prepend(node);setTimeout(()=>node.classList.remove('wall-post-new'),450);}}

function initPublisher(shell){
 if(!shell||shell.dataset.publisher160)return;shell.dataset.publisher160='1';
 const modal=q('[data-wall-publisher-modal]',shell),form=q('[data-wall-publisher]',shell),open=q('[data-wall-composer-open]',shell);if(!form||!modal)return;
 const closeButtons=qa('[data-wall-publisher-close]',modal),next=q('[data-wall-publisher-next]',form),prev=q('[data-wall-publisher-prev]',form),back=q('[data-wall-publisher-back]',form),submit=q('[data-wall-publisher-submit]',form),title=q('[data-wall-publisher-title]',form),steps=qa('[data-wall-publisher-step]',form),schedule=q('[data-wall-schedule-field]',form),dateInput=q('input[name="publish_at"]',form),mediaPicker=q('[data-wall-media-picker]',form),photoInput=q('[data-wall-photos]',form),videoInput=q('[data-wall-videos]',form),videoPreview=q('[data-wall-video-preview]',form),dropzone=q('[data-wall-publisher-dropzone]',form),choose=q('[data-wall-media-choose]',form),body=q('textarea[name="body"]',form);
 let videos=[];
 const showStep=name=>{steps.forEach(step=>{const active=step.dataset.wallPublisherStep===name;step.hidden=!active;step.classList.toggle('active',active);});const settings=name==='settings';if(next)next.hidden=settings;if(submit)submit.hidden=!settings;if(prev)prev.hidden=!settings;if(back)back.hidden=!settings;if(title)title.textContent=settings?'Настройки публикации':'Новый пост';if(settings)updateSubmitText();};
 const openModal=()=>{modal.hidden=false;document.body.classList.add('wall-publisher-open');showStep('content');setTimeout(()=>body?.focus(),80);};
 const closeModal=()=>{modal.hidden=true;document.body.classList.remove('wall-publisher-open');q('.wall-publisher-emoji-menu',form)?.remove();};
 const renderVideos=()=>{if(!videoPreview)return;videoPreview.hidden=!videos.length;videoPreview.innerHTML='';videos.forEach((file,index)=>{const src=URL.createObjectURL(file),figure=document.createElement('figure');figure.innerHTML=`<video src="${src}" muted preload="metadata"></video><button type="button" data-wall-video-remove="${index}" aria-label="Удалить">×</button>`;q('video',figure).onloadedmetadata=()=>URL.revokeObjectURL(src);videoPreview.append(figure);});syncFiles(videoInput,videos);};
 const applyMediaFiles=files=>{const photos=[],newVideos=[];for(const file of [...files]){if(file.type.startsWith('image/')&&photos.length<10&&file.size<=12*1024*1024)photos.push(file);else if(['video/mp4','video/webm'].includes(file.type)&&newVideos.length<4)newVideos.push(file);}if(photos.length){syncFiles(photoInput,photos);photoInput.dispatchEvent(new Event('change',{bubbles:true}));}if(newVideos.length){videos=newVideos;renderVideos();}if(!photos.length&&!newVideos.length)toast('Выберите JPG, PNG, WebP, MP4 или WebM.','error');};
 const updateSubmitText=()=>{const mode=q('input[name="publish_mode"]:checked',form)?.value||'now';if(submit)submit.textContent=mode==='draft'?'Сохранить черновик':mode==='scheduled'?'Запланировать':'Опубликовать';if(schedule)schedule.hidden=mode!=='scheduled';};
 open?.addEventListener('click',openModal);closeButtons.forEach(button=>button.addEventListener('click',closeModal));modal.addEventListener('click',event=>{if(event.target===modal)closeModal();});
 next?.addEventListener('click',()=>{if(!hasComposerContent(form)){toast('Добавьте текст, фотографию, видео или документ.','error');return;}showStep('settings');});
 prev?.addEventListener('click',()=>showStep('content'));back?.addEventListener('click',()=>showStep('content'));
 qa('input[name="publish_mode"]',form).forEach(input=>input.addEventListener('change',updateSubmitText));
 choose?.addEventListener('click',event=>{event.preventDefault();mediaPicker?.click();});mediaPicker?.addEventListener('change',()=>{applyMediaFiles(mediaPicker.files||[]);mediaPicker.value='';});
 dropzone?.addEventListener('dragover',event=>{event.preventDefault();event.stopPropagation();dropzone.classList.add('dragging');});dropzone?.addEventListener('dragleave',event=>{if(!dropzone.contains(event.relatedTarget))dropzone.classList.remove('dragging');});dropzone?.addEventListener('drop',event=>{event.preventDefault();event.stopPropagation();dropzone.classList.remove('dragging');applyMediaFiles(event.dataTransfer.files||[]);});
 videoPreview?.addEventListener('click',event=>{const button=event.target.closest('[data-wall-video-remove]');if(!button)return;videos.splice(Number(button.dataset.wallVideoRemove),1);renderVideos();});
 q('[data-wall-publish-tips]',form)?.addEventListener('click',()=>toast('Используйте качественные изображения, короткое описание и проверьте видимость перед публикацией.'));
 q('.wall-publisher-emoji',form)?.addEventListener('click',event=>{event.preventDefault();let menu=q('.wall-publisher-emoji-menu',form);if(menu){menu.remove();return;}menu=document.createElement('div');menu.className='wall-publisher-emoji-menu';menu.innerHTML=['😀','👍','❤️','🔥','🎉','👏','🙂','💬'].map(emoji=>`<button type="button">${emoji}</button>`).join('');menu.addEventListener('click',e=>{const button=e.target.closest('button');if(!button)return;const start=body.selectionStart??body.value.length,end=body.selectionEnd??start;body.value=body.value.slice(0,start)+button.textContent+body.value.slice(end);body.focus();body.setSelectionRange(start+button.textContent.length,start+button.textContent.length);menu.remove();});form.append(menu);});
 form.addEventListener('submit',event=>{const mode=q('input[name="publish_mode"]:checked',form)?.value||'now';if(mode==='scheduled'&&!dateInput?.value){event.preventDefault();event.stopImmediatePropagation();toast('Выберите дату и время публикации.','error');dateInput?.focus();}} ,true);
 form.addEventListener('kovcheg-wall-reset',event=>{videos=[];renderVideos();if(mediaPicker)mediaPicker.value='';const now=q('input[name="publish_mode"][value="now"]',form),everyone=q('input[name="visibility"][value="everyone"]',form);if(now)now.checked=true;if(everyone)everyone.checked=true;if(dateInput)dateInput.value='';updateSubmitText();showStep('content');closeModal();refreshDraftCount(shell);});
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden)closeModal();});
}

function filterPosts(shell,query){const text=String(query||'').trim().toLocaleLowerCase('ru-RU'),feed=q('[data-wall-feed]',shell.closest('[data-profile-wall],.feed-page')||document)||q('[data-wall-feed]');if(!feed)return;let visible=0;qa('[data-wall-post]',feed).forEach(card=>{const match=!text||card.textContent.toLocaleLowerCase('ru-RU').includes(text);card.hidden=!match;if(match)visible++;});let empty=q('[data-wall-search-empty]',feed);if(text&&!visible){if(!empty){empty=document.createElement('div');empty.className='wall-search-no-results';empty.dataset.wallSearchEmpty='';empty.textContent='По вашему запросу записи не найдены.';feed.prepend(empty);}}else empty?.remove();}
function initPostSearch(shell){const toggle=q('[data-wall-post-search-toggle]',shell),panel=q('[data-wall-post-search]',shell),input=q('[data-wall-post-search-input]',shell),close=q('[data-wall-post-search-close]',shell);toggle?.addEventListener('click',()=>{panel.hidden=!panel.hidden;if(!panel.hidden)setTimeout(()=>input?.focus(),40);else{if(input)input.value='';filterPosts(shell,'');}});input?.addEventListener('input',()=>filterPosts(shell,input.value));close?.addEventListener('click',()=>{panel.hidden=true;if(input)input.value='';filterPosts(shell,'');});}

async function fetchDrafts(shell,openModal=false){const list=q('[data-wall-drafts-list]',shell),modal=q('[data-wall-drafts-modal]',shell),count=q('[data-wall-draft-count]',shell);if(openModal&&modal)modal.hidden=false;if(list&&openModal)list.innerHTML='<p class="muted">Загрузка…</p>';try{const result=await api('/ajax/wall/drafts');if(count){count.textContent=String(result.count||0);count.hidden=!Number(result.count||0);}if(list){const rows=result.data||[];list.innerHTML=rows.length?rows.map(row=>`<article class="wall-draft-item" data-wall-draft="${Number(row.id)}"><div><b>${row.status==='scheduled'?'Отложенная публикация':'Черновик'}</b><p>${esc(row.preview||'Публикация без текста')}</p><small>${esc(row.publish_label||'')} · вложений: ${Number(row.attachment_count||0)}</small></div><div class="wall-draft-actions"><button type="button" class="primary" data-wall-draft-publish="${Number(row.id)}">Опубликовать</button><button type="button" data-wall-draft-delete="${Number(row.id)}">Удалить</button></div></article>`).join(''):'<div class="wall-search-no-results">Черновиков и отложенных публикаций пока нет.</div>';}}catch(error){if(list)list.innerHTML=`<div class="wall-search-no-results">${esc(error.message)}</div>`;}}
function refreshDraftCount(shell){if(q('[data-wall-draft-count]',shell))fetchDrafts(shell,false);}
function initDrafts(shell){const modal=q('[data-wall-drafts-modal]',shell),open=q('[data-wall-drafts-open]',shell),close=q('[data-wall-drafts-close]',shell),list=q('[data-wall-drafts-list]',shell);open?.addEventListener('click',()=>fetchDrafts(shell,true));close?.addEventListener('click',()=>{modal.hidden=true;});modal?.addEventListener('click',event=>{if(event.target===modal)modal.hidden=true;});list?.addEventListener('click',async event=>{const publish=event.target.closest('[data-wall-draft-publish]'),remove=event.target.closest('[data-wall-draft-delete]');if(!publish&&!remove)return;const id=Number((publish||remove).dataset.wallDraftPublish||(publish||remove).dataset.wallDraftDelete||0);(publish||remove).disabled=true;try{if(publish){const result=await post(`/profile/wall/${id}/publish-now`);insertPost(shell,result.html||'');toast(result.message||'Пост опубликован.');}else{await post(`/profile/wall/${id}/draft/delete`);toast('Черновик удалён.');}q(`[data-wall-draft="${id}"]`,list)?.remove();refreshDraftCount(shell);if(!q('[data-wall-draft]',list))list.innerHTML='<div class="wall-search-no-results">Черновиков и отложенных публикаций пока нет.</div>';}catch(error){toast(error.message,'error');(publish||remove).disabled=false;}});refreshDraftCount(shell);}

function initShell(shell){if(shell.dataset.wallShell160)return;shell.dataset.wallShell160='1';initPublisher(shell);initPostSearch(shell);initDrafts(shell);}
qa('[data-wall-create-shell]').forEach(initShell);
new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType!==1)return;if(node.matches?.('[data-wall-create-shell]'))initShell(node);qa('[data-wall-create-shell]',node).forEach(initShell);}))).observe(document.body,{childList:true,subtree:true});
})();
(()=>{'use strict';
const q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
function loadStyle(href){if(q(`link[href="${href}"]`))return;const l=document.createElement('link');l.rel='stylesheet';l.href=href;document.head.append(l);}
function loadScript(src){return new Promise((resolve,reject)=>{if(window.L)return resolve();const old=q(`script[src="${src}"]`);if(old){if(window.L)return resolve();old.addEventListener('load',resolve,{once:true});old.addEventListener('error',reject,{once:true});return;}const s=document.createElement('script');s.src=src;s.onload=resolve;s.onerror=reject;document.head.append(s);});}
function weatherData(page){const node=q('[data-weather-state]',page?.parentElement||document)||q('[data-weather-state]');if(node){try{window.KOVCHEG_WEATHER_PAGE=JSON.parse(node.textContent||'{}');}catch{window.KOVCHEG_WEATHER_PAGE={};}}return window.KOVCHEG_WEATHER_PAGE||{};}
function destroyWeather(){try{window.__kovchegWeatherMap?.remove?.();}catch{}window.__kovchegWeatherMap=null;}
function initWeatherTabs(page){qa('[data-weather-tab]',page).forEach(button=>{if(button.dataset.weatherBound==='1')return;button.dataset.weatherBound='1';button.addEventListener('click',()=>{qa('[data-weather-tab]',page).forEach(x=>x.classList.toggle('active',x===button));qa('[data-weather-panel]',page).forEach(panel=>{const active=panel.dataset.weatherPanel===button.dataset.weatherTab;panel.hidden=!active;panel.classList.toggle('active',active);});if(button.dataset.weatherTab==='radar'&&window.__kovchegWeatherMap)setTimeout(()=>window.__kovchegWeatherMap.invalidateSize(),80);});});}
function rainHoursForDay(data,day){const times=data.hourly?.time||[],prob=data.hourly?.precipitation_probability||[],amount=data.hourly?.precipitation||[],rain=data.hourly?.rain||[],showers=data.hourly?.showers||[];const target=(data.daily?.time||[])[day];return times.map((time,i)=>({time,prob:Number(prob[i]||0),amount:Number(amount[i]||0),rain:Number(rain[i]||0)+Number(showers[i]||0)})).filter(row=>row.time.startsWith(target||''));}
function renderRain(page,day=0){const data=weatherData(page),rows=rainHoursForDay(data,day),strip=q('[data-rain-hours]',page),summary=q('[data-rain-summary]',page);qa('[data-rain-day]',page).forEach(button=>button.classList.toggle('active',Number(button.dataset.rainDay)===day));if(!strip)return;const now=new Date();strip.innerHTML=rows.map(row=>{const hour=row.time.slice(11,16),height=Math.max(3,Math.min(58,row.prob*.58));return `<div class="weather-hour${new Date(row.time)<=now&&new Date(row.time).getHours()===now.getHours()?' current':''}"><time>${hour}</time><span><i style="height:${height}px"></i></span><b>${Math.round(row.prob)}%</b><small>${row.amount.toFixed(1)} мм</small></div>`;}).join('');const wet=rows.filter(r=>r.prob>=30||r.amount>0.05),first=wet[0],last=wet[wet.length-1];summary.textContent=!wet.length?'Существенных осадков не ожидается.':`Осадки вероятны примерно с ${first.time.slice(11,16)} до ${last.time.slice(11,16)}. Максимальная вероятность ${Math.round(Math.max(...wet.map(r=>r.prob)))}%.`;}
async function initRadar(page){
 const mapNode=q('#weather-radar-map',page);if(!mapNode||mapNode.dataset.radarBound==='1')return;mapNode.dataset.radarBound='1';
 const data=weatherData(page),lat=Number(mapNode.dataset.lat||data.place?.latitude||0),lon=Number(mapNode.dataset.lon||data.place?.longitude||0);
 let meta=data.radar||{};if(!meta.radar){try{const response=await fetch(`${window.KOVCHEG?.baseUrl||''}/ajax/weather/radar`,{headers:{Accept:'application/json'},cache:'no-store'});meta=(await response.json()).data||{};}catch(_){}}
 const frames=[...(meta.radar?.past||[]),...(meta.radar?.nowcast||[])];if(!frames.length){mapNode.innerHTML='<div class="weather-map-fallback">Радарные кадры сейчас недоступны.</div>';return;}
 const host=meta.host||'https://tilecache.rainviewer.com',range=q('[data-radar-range]',page),label=q('[data-radar-time]',page),prev=q('[data-radar-prev]',page),next=q('[data-radar-next]',page),play=q('[data-radar-play]',page);let index=Math.max(0,frames.length-1),timer=null;
 const tile=(la,lo,z)=>{const n=2**z,x=Math.floor((lo+180)/360*n),r=la*Math.PI/180,y=Math.floor((1-Math.asinh(Math.tan(r))/Math.PI)/2*n);return{x,y,n}};
 const draw=i=>{index=Math.max(0,Math.min(frames.length-1,i));if(range){range.max=String(frames.length-1);range.value=String(index)};const z=7,c=tile(lat,lon,z),frame=frames[index];mapNode.innerHTML='';const grid=document.createElement('div');grid.className='weather-radar-native-grid';grid.style.cssText='position:absolute;inset:0;overflow:hidden;background:#dde5eb';for(let dy=-1;dy<=1;dy++)for(let dx=-1;dx<=1;dx++){const x=(c.x+dx+c.n)%c.n,y=Math.max(0,Math.min(c.n-1,c.y+dy));const cell=document.createElement('div');cell.style.cssText=`position:absolute;width:33.5%;height:33.5%;left:${(dx+1)*33.333}%;top:${(dy+1)*33.333}%;overflow:hidden`;const baseImg=document.createElement('img');baseImg.src=`https://tile.openstreetmap.org/${z}/${x}/${y}.png`;baseImg.alt='';baseImg.style.cssText='width:100%;height:100%;object-fit:cover;display:block';const rain=document.createElement('img');rain.src=`${host}${frame.path}/256/${z}/${x}/${y}/2/1_1.png`;rain.alt='';rain.style.cssText='position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.82';cell.append(baseImg,rain);grid.appendChild(cell)}mapNode.appendChild(grid);const marker=document.createElement('span');marker.className='weather-radar-marker';marker.style.cssText='position:absolute;z-index:4;left:50%;top:50%;width:13px;height:13px;border:2px solid white;border-radius:50%;background:#7b4df5;transform:translate(-50%,-50%);box-shadow:0 2px 8px #0008';mapNode.appendChild(marker);if(label)label.textContent=new Intl.DateTimeFormat('ru-RU',{hour:'2-digit',minute:'2-digit',day:'2-digit',month:'short'}).format(new Date(frame.time*1000));};
 draw(index);range?.addEventListener('input',()=>draw(Number(range.value)));if(prev)prev.onclick=()=>draw(index-1);if(next)next.onclick=()=>draw(index+1);if(play)play.onclick=()=>{if(timer){clearInterval(timer);timer=null;play.textContent='▶';return}play.textContent='Ⅱ';timer=setInterval(()=>draw(index>=frames.length-1?0:index+1),900)};document.addEventListener('kovcheg:pagebeforechange',()=>{if(timer)clearInterval(timer)},{once:true});
}
function activateWeather(){const page=q('[data-weather-page]');if(!page)return;weatherData(page);initWeatherTabs(page);renderRain(page,0);qa('[data-rain-day]',page).forEach(button=>{if(button.dataset.rainBound==='1')return;button.dataset.rainBound='1';button.addEventListener('click',()=>renderRain(page,Number(button.dataset.rainDay||0)));});initRadar(page);}
activateWeather();document.addEventListener('kovcheg:pagechange',activateWeather);document.addEventListener('kovcheg:pagebeforechange',()=>{if(q('[data-weather-page]'))destroyWeather();});
})();
(()=>{'use strict';
const q=(s,r=document)=>r.querySelector(s);
document.addEventListener('click',event=>{
 const open=event.target.closest('[data-avatar-view]');if(open)setTimeout(()=>{const viewer=q('[data-profile-avatar-viewer]');if(!viewer||viewer.hidden)return;viewer.classList.add('open');q('[data-avatar-comments-list]',viewer)?.scrollTo({top:q('[data-avatar-comments-list]',viewer).scrollHeight,behavior:'instant'});},40);
 const close=event.target.closest('[data-profile-avatar-close]');if(close)q('[data-profile-avatar-viewer]')?.classList.remove('open');
});
/* A resized weather tab must redraw Leaflet tiles instead of showing a blank grey map. */
document.addEventListener('click',event=>{if(event.target.closest('[data-weather-tab="radar"]'))setTimeout(()=>window.__kovchegWeatherMap?.invalidateSize?.({pan:false}),120);});
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const csrf=()=>K.csrf||q('meta[name="csrf-token"]')?.content||'';
const endpoint=path=>/^https?:\/\//i.test(String(path))?String(path):`${String(K.baseUrl||'').replace(/\/$/,'')}${String(path).startsWith('/')?'':'/'}${path}`;
async function post(path,data={}){const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([key,value])=>{if(Array.isArray(value))value.forEach(item=>fd.append(`${key}[]`,String(item)));else fd.append(key,String(value));});if(!fd.has('_csrf'))fd.append('_csrf',csrf());const response=await fetch(endpoint(path),{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}});const json=await response.json().catch(()=>({}));if(!response.ok||json.ok===false)throw new Error(json.error||json.message||`Ошибка ${response.status}`);return json;}
const toast=(text,type='success')=>window.KovchegShowToast?.(text,{type});

/* Context menu for a concrete message. */
let contextMessage=null;const menu=q('[data-message-context]');
function closeMessageMenu(){if(menu)menu.hidden=true;contextMessage=null;}
document.addEventListener('contextmenu',event=>{const message=event.target.closest('.message[data-message-id]');if(!message||!menu)return;event.preventDefault();event.stopPropagation();contextMessage=message;const has=selector=>!!q(selector,message);q('[data-message-context-action="edit"]',menu).hidden=!has('[data-edit]');q('[data-message-context-action="delete"]',menu).hidden=!has('[data-delete]');q('[data-message-context-action="forward"]',menu).hidden=!has('[data-forward]');const important=q('[data-message-context-action="important"]',menu);if(important)important.textContent=message.dataset.messageImportant==='1'?'Убрать из важного':'Отметить как важное';menu.style.left=`${Math.max(8,Math.min(event.clientX,innerWidth-250))}px`;menu.style.top=`${Math.max(8,Math.min(event.clientY,innerHeight-330))}px`;menu.hidden=false;});
document.addEventListener('click',async event=>{const action=event.target.closest('[data-message-context-action]');if(!action){if(!event.target.closest('[data-message-context]'))closeMessageMenu();return;}if(!contextMessage)return;const message=contextMessage,id=Number(message.dataset.messageId),kind=action.dataset.messageContextAction;closeMessageMenu();try{
 if(kind==='reply')q('[data-reply]',message)?.click();
 else if(kind==='forward')q('[data-forward]',message)?.click();
 else if(kind==='edit')q('[data-edit]',message)?.click();
 else if(kind==='delete')q('[data-delete]',message)?.click();
 else if(kind==='copy'){await navigator.clipboard.writeText(message.dataset.messageBody||'');toast('Текст скопирован.');}
 else if(kind==='select'){q('[data-select-messages]')?.click();setTimeout(()=>q('[data-message-select]',message)?.click(),0);}
 else if(kind==='important'){const result=await post(`/ajax/message/${id}/important`);message.dataset.messageImportant=result.important?'1':'0';const mark=q('[data-message-important-mark]',message);if(mark)mark.hidden=!result.important;toast(result.important?'Сообщение отмечено как важное.':'Сообщение убрано из важного.');}
 }catch(error){toast(error.message,'error');}
});

/* Comment a selected chain in the current composer. */
function selectedMessages(){return qa('#messages .message.selected[data-message-id]');}
function refreshSelectionExtra(){const button=q('[data-selection-comment]');if(button)button.disabled=selectedMessages().length===0;}
document.addEventListener('click',event=>{if(event.target.closest('[data-message-select],[data-select-messages],[data-selection-cancel]'))setTimeout(refreshSelectionExtra,0);const comment=event.target.closest('[data-selection-comment]');if(!comment)return;const rows=selectedMessages();if(!rows.length)return;const form=q('#composer'),textarea=q('textarea[name="body"]',form||document),batch=q('input[name="comment_message_ids"]',form||document);if(!form||!textarea||!batch)return;batch.value=rows.map(row=>row.dataset.messageId).join(',');form.classList.add('commenting-batch');const context=q('[data-composer-context]',form),title=q('[data-context-title]',form),text=q('[data-context-text]',form);if(title)title.textContent=`Комментарий к сообщениям: ${rows.length}`;if(text)text.textContent='Выбранные сообщения будут отправлены настоящей цепочкой, а ваш текст — отдельным сообщением.';if(context)context.hidden=false;q('[data-selection-cancel]')?.click();textarea.value='';textarea.dispatchEvent(new Event('input',{bubbles:true}));textarea.focus();toast('Напишите комментарий. Выбранные сообщения будут добавлены как настоящая цепочка.');});
new MutationObserver(refreshSelectionExtra).observe(document.body,{subtree:true,childList:true,attributes:true,attributeFilter:['class']});

/* Three-dot wall menu. */
function closeWallMenus(except=null){qa('[data-wall-menu]').forEach(item=>{if(item!==except)item.hidden=true;});}
document.addEventListener('click',async event=>{const toggle=event.target.closest('[data-wall-menu-toggle]');if(toggle){event.stopPropagation();const current=q(`[data-wall-menu="${CSS.escape(toggle.dataset.wallMenuToggle)}"]`);const was=current?.hidden;closeWallMenus(current);if(current)current.hidden=!was;return;}const link=event.target.closest('[data-wall-menu-link]');if(link){event.preventDefault();try{await navigator.clipboard.writeText(link.dataset.shareUrl||location.href);toast('Ссылка на запись скопирована.');}catch{toast('Не удалось скопировать ссылку.','error');}closeWallMenus();return;}if(!event.target.closest('[data-wall-menu]'))closeWallMenus();});

/* Keep relationship cards consistent after an AJAX action. */
document.addEventListener('click',event=>{const action=event.target.closest('[data-colleague-action],[data-follow-action],[data-profile-block],[data-profile-unblock]');if(action)setTimeout(()=>{if(!document.body.contains(action))return;},0);});

/* Guest-profile weather links keep the viewed user id. */
qa('[data-weather-widget][data-weather-user]').forEach(widget=>{const id=Number(widget.dataset.weatherUser||0);if(id&&widget.href&&!widget.href.includes('user='))widget.href+=`${widget.href.includes('?')?'&':'?'}user=${id}`;});
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const endpoint=path=>/^https?:\/\//i.test(String(path))?String(path):`${String(K.baseUrl||'').replace(/\/$/,'')}${String(path).startsWith('/')?'':'/'}${path}`;
const esc=value=>String(value??'').replace(/[&<>'"]/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
const toast=(text,type='success')=>window.KovchegShowToast?.(text,{type});
let modal=null,body=null,title=null,currentPost=0;
function ensureModal(){if(modal)return modal;modal=document.createElement('div');modal.className='shared-post-viewer-181';modal.hidden=true;modal.innerHTML='<section class="shared-post-viewer-card-181" role="dialog" aria-modal="true" aria-label="Просмотр публикации"><header class="shared-post-viewer-head-181"><div><b data-shared-post-title>Запись на стене</b><small>Открыта из личного сообщения</small></div><button type="button" data-shared-post-close aria-label="Закрыть">×</button></header><div data-shared-post-body></div></section>';document.body.append(modal);body=q('[data-shared-post-body]',modal);title=q('[data-shared-post-title]',modal);return modal;}
function closeModal(){if(!modal)return;modal.hidden=true;body.innerHTML='';currentPost=0;document.documentElement.classList.remove('modal-open');}
async function openPost(id,trigger=null){const shell=ensureModal();currentPost=Number(id||0);shell.hidden=false;document.documentElement.classList.add('modal-open');const template=trigger?.closest('.message')?.querySelector(`template[data-shared-post-template="${currentPost}"]`)||document.querySelector(`template[data-shared-post-template="${currentPost}"]`);if(template){body.replaceChildren(template.content.cloneNode(true));const author=q('.wall-author-info b',body)?.textContent?.trim();if(author&&title)title.textContent=`Запись ${author}`;body.scrollTop=0;return;}body.innerHTML='<div class="shared-post-viewer-loading-181">Загрузка записи…</div>';try{const response=await fetch(endpoint(`/ajax/wall-post/${currentPost}`),{credentials:'same-origin',headers:{Accept:'application/json'}});const json=await response.json().catch(()=>({}));if(!response.ok||json.ok===false)throw new Error(json.error||json.message||`Ошибка ${response.status}`);body.innerHTML=json.html||'<div class="shared-post-viewer-loading-181">Запись недоступна.</div>';const author=q('.wall-author-info b',body)?.textContent?.trim();if(author&&title)title.textContent=`Запись ${author}`;body.scrollTop=0;}catch(error){body.innerHTML=`<div class="shared-post-viewer-loading-181">${esc(error.message||'Не удалось открыть запись.')}</div>`;toast(error.message||'Не удалось открыть запись.','error');}}
document.addEventListener('click',event=>{const trigger=event.target.closest('[data-shared-post-open]');if(trigger){event.preventDefault();event.stopPropagation();openPost(trigger.dataset.sharedPostOpen,trigger);return;}if(event.target.closest('[data-shared-post-close]')||event.target===modal)closeModal();});
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&modal&&!modal.hidden)closeModal();});
})();
(()=>{'use strict';
const q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];

/* Enter sends a comment, Shift+Enter creates a new line. */
document.addEventListener('keydown',event=>{
 const area=event.target.closest?.('[data-wall-comment-form] textarea,[data-avatar-comment-form] textarea,[data-thread-form] textarea');
 if(!area||event.key!=='Enter'||event.shiftKey||event.isComposing)return;
 event.preventDefault();
 area.closest('form')?.requestSubmit();
});

/* Opening a channel settings window always starts on the main tab. */
document.addEventListener('click',event=>{
 const button=event.target.closest('[data-modal^="channel-settings-"]');
 if(!button)return;
 const modal=document.getElementById(button.dataset.modal);
 if(!modal)return;
 qa('[data-channel-tab]',modal).forEach((tab,index)=>tab.classList.toggle('active',index===0));
 qa('[data-channel-panel]',modal).forEach((panel,index)=>panel.classList.toggle('active',index===0));
});

/* Keep post menus above any media even in old cached markup. */
new MutationObserver(records=>{
 for(const record of records)for(const node of record.addedNodes){
  if(node.nodeType!==1)continue;
  const menus=node.matches?.('[data-wall-menu]')?[node]:qa('[data-wall-menu]',node);
  menus.forEach(menu=>{menu.style.opacity='1';menu.style.backgroundColor=getComputedStyle(document.body).getPropertyValue('--panel').trim()||'#172633';});
 }
}).observe(document.body,{childList:true,subtree:true});
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const endpoint=path=>`${String(K.baseUrl||'').replace(/\/$/,'')}${String(path).startsWith('/')?'':'/'}${path}`;
async function request(path,data={}){const fd=data instanceof FormData?data:new FormData();if(!(data instanceof FormData))Object.entries(data).forEach(([k,v])=>Array.isArray(v)?v.forEach(x=>fd.append(k+'[]',String(x))):fd.append(k,String(v)));if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');const response=await fetch(endpoint(path),{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}});const text=await response.text();let json={};try{json=JSON.parse(text);}catch{throw new Error(response.ok?'Сервер вернул некорректный ответ.':`Ошибка ${response.status}`);}if(!response.ok||json.ok===false)throw new Error(json.error||json.message||`Ошибка ${response.status}`);return json;}
const toast=(text,type='success')=>window.KovchegShowToast?.(text,{type});

/* Post menu is positioned against the viewport and cannot be clipped by the card. */
function positionWallMenu(button,menu){const r=button.getBoundingClientRect(),width=Math.max(190,menu.offsetWidth||190);menu.style.position='fixed';menu.style.zIndex='1600';menu.style.top=`${Math.min(innerHeight-(menu.offsetHeight||160)-10,r.bottom+6)}px`;menu.style.left=`${Math.max(8,Math.min(innerWidth-width-8,r.right-width))}px`;menu.style.right='auto';}
document.addEventListener('click',event=>{const button=event.target.closest('[data-wall-menu-toggle]');if(button){setTimeout(()=>{const menu=q(`[data-wall-menu="${CSS.escape(button.dataset.wallMenuToggle)}"]`);if(menu&&!menu.hidden)positionWallMenu(button,menu);},0);}});
addEventListener('resize',()=>qa('[data-wall-menu]').forEach(menu=>menu.hidden=true));

/* Direct colleague invitations from channel settings. */
document.addEventListener('input',event=>{const input=event.target.closest('[data-channel-invite-search]');if(!input)return;const term=input.value.trim().toLowerCase();qa('[data-channel-invite-person]',input.closest('[data-channel-invite-panel]')).forEach(row=>row.hidden=term!==''&&!String(row.dataset.search||'').includes(term));});
document.addEventListener('click',async event=>{const button=event.target.closest('[data-channel-invite-user]');if(!button)return;const panel=button.closest('[data-channel-invite-panel]');const chatId=Number(panel?.dataset.channelInvitePanel||0),userId=Number(button.dataset.channelInviteUser||0);if(!chatId||!userId)return;button.disabled=true;try{await request(`/ajax/channel/${chatId}/members/add`,{user_id:userId});button.closest('[data-channel-invite-person]')?.remove();toast('Коллега приглашён в канал.');if(!q('[data-channel-invite-person]',panel)){const p=document.createElement('p');p.className='muted';p.textContent='Все ваши коллеги уже состоят в канале.';q('.channel-invite-list',panel)?.append(p);}}catch(error){toast(error.message,'error');button.disabled=false;}});

/* Dedicated channel-post comment composer. */
const form=q('[data-channel-post-comment-form]');if(form){const area=q('textarea',form);area?.addEventListener('keydown',event=>{if(event.key==='Enter'&&!event.shiftKey&&!event.isComposing){event.preventDefault();form.requestSubmit();}});form.addEventListener('submit',async event=>{event.preventDefault();const body=area?.value.trim()||'';if(!body)return;const submit=q('button[type=submit]',form);if(submit)submit.disabled=true;try{const fd=new FormData(form);fd.append('as_comment','1');const result=await request('/ajax/send',fd);q('[data-channel-comments-empty]')?.remove();q('[data-channel-post-comments-list]')?.insertAdjacentHTML('beforeend',result.html||'');area.value='';const count=q('[data-channel-comment-count]');if(count)count.textContent=String(Number(count.textContent||0)+1);}catch(error){toast(error.message,'error');}finally{if(submit)submit.disabled=false;}});}
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)],base=String(K.baseUrl||'').replace(/\/$/,'');
const endpoint=p=>/^https?:/i.test(String(p))?String(p):`${base}${String(p).startsWith('/')?'':'/'}${p}`;
const toast=(t,type='success')=>window.KovchegShowToast?.(t,{type});
async function request(path,body){const r=await fetch(endpoint(path),{method:body?'POST':'GET',body,credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-Token':K.csrf||''}});const j=await r.json().catch(()=>({}));if(!r.ok||j.ok===false)throw new Error(j.error||`Ошибка ${r.status}`);return j;}

/* Hover-only publication menu. The menu is portalled to body and is always removed on scroll. */
let activeMenu=null,hideTimer=0;
function hideMenu(){clearTimeout(hideTimer);if(activeMenu){activeMenu.hidden=true;activeMenu=null;}}
function showMenu(button){hideMenu();const menu=q(`[data-wall-menu="${CSS.escape(button.dataset.wallMenuToggle)}"]`);if(!menu)return;if(menu.parentElement!==document.body)document.body.append(menu);menu.hidden=false;activeMenu=menu;const r=button.getBoundingClientRect();const w=Math.max(205,menu.offsetWidth||205),h=Math.max(120,menu.offsetHeight||120);menu.style.left=`${Math.max(8,Math.min(innerWidth-w-8,r.right-w))}px`;menu.style.top=`${Math.max(8,Math.min(innerHeight-h-8,r.bottom+5))}px`;menu.onmouseenter=()=>clearTimeout(hideTimer);menu.onmouseleave=()=>hideTimer=setTimeout(hideMenu,120);}
document.addEventListener('pointerover',e=>{const b=e.target.closest('[data-wall-menu-toggle]');if(b)showMenu(b);});
document.addEventListener('pointerout',e=>{const b=e.target.closest('[data-wall-menu-toggle]');if(b&&!e.relatedTarget?.closest?.('[data-wall-menu]'))hideTimer=setTimeout(hideMenu,150);});
document.addEventListener('click',e=>{if(e.target.closest('[data-wall-menu-toggle]')){e.preventDefault();e.stopImmediatePropagation();}},true);
addEventListener('scroll',hideMenu,true);addEventListener('resize',hideMenu);

/* Channel comment tree, context menu, emoji, voice and files. */
const form=q('[data-channel-comment-form]'),commentMenu=q('[data-channel-comment-menu]');let contextComment=null,recorder=null,chunks=[],stream=null,voiceBlob=null,voiceStart=0,voiceTimer=0;
const emojis=['😀','😃','😄','😁','😆','😅','😂','🤣','😊','🙂','😉','😍','🥰','😘','😎','🤔','🙄','😢','😭','😡','🥳','👍','👎','👏','🙏','🔥','❤️','🎉','✅','👀','⚡'];
function setReply(id,name){if(!form)return;form.elements.parent_id.value=String(id);const box=q('[data-channel-comment-reply-context]',form);if(box){box.hidden=false;q('b',box).textContent=name||'пользователю';}q('textarea',form)?.focus();}
function cancelReply(){if(!form)return;form.elements.parent_id.value=form.elements.root_id.value;const box=q('[data-channel-comment-reply-context]',form);if(box)box.hidden=true;}
function stopTracks(){stream?.getTracks().forEach(t=>t.stop());stream=null;clearInterval(voiceTimer);voiceTimer=0;}
function voiceUi(on){if(!form)return;q('textarea',form).hidden=on;q('[data-channel-voice-state]',form).hidden=!on;q('[data-channel-voice]',form)?.classList.toggle('recording',on);}
async function startVoice(){if(!navigator.mediaDevices?.getUserMedia||typeof MediaRecorder==='undefined')throw new Error('Запись голоса не поддерживается.');stream=await navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true}});chunks=[];voiceBlob=null;const opt=MediaRecorder.isTypeSupported?.('audio/webm;codecs=opus')?{mimeType:'audio/webm;codecs=opus'}:{};recorder=new MediaRecorder(stream,opt);recorder.ondataavailable=e=>{if(e.data.size)chunks.push(e.data)};recorder.start(250);voiceStart=Date.now();voiceUi(true);voiceTimer=setInterval(()=>{const s=Math.floor((Date.now()-voiceStart)/1000),el=q('[data-channel-voice-time]',form);if(el)el.textContent=`${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`;},250);}
function stopVoice(cancel=false){return new Promise(resolve=>{if(!recorder||recorder.state==='inactive'){if(cancel)voiceBlob=null;stopTracks();voiceUi(false);return resolve();}recorder.onstop=()=>{if(!cancel&&chunks.length)voiceBlob=new Blob(chunks,{type:recorder.mimeType||'audio/webm'});else voiceBlob=null;recorder=null;stopTracks();voiceUi(false);resolve();};recorder.stop();});}
if(form){const picker=q('[data-channel-emoji-picker]',form);if(picker)picker.innerHTML=emojis.map(x=>`<button type="button">${x}</button>`).join('');q('[data-channel-emoji]',form)?.addEventListener('click',()=>picker.hidden=!picker.hidden);picker?.addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;const t=q('textarea',form),a=t.selectionStart??t.value.length,z=t.selectionEnd??a;t.setRangeText(b.textContent,a,z,'end');t.focus();picker.hidden=true;});q('[data-channel-voice]',form)?.addEventListener('click',async()=>{try{if(recorder?.state==='recording')form.requestSubmit();else await startVoice();}catch(e){toast(e.message,'error');}});q('[data-channel-voice-cancel]',form)?.addEventListener('click',()=>stopVoice(true));q('[data-channel-comment-reply-cancel]',form)?.addEventListener('click',cancelReply);q('[data-channel-comment-files]',form)?.addEventListener('change',e=>{const box=q('[data-channel-comment-file-list]',form);box.innerHTML=[...e.target.files].map(f=>`<span>${f.name}</span>`).join('');});q('textarea',form)?.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){e.preventDefault();form.requestSubmit();}});form.addEventListener('submit',async e=>{e.preventDefault();const submit=q('button[type=submit]',form);submit.disabled=true;try{if(recorder?.state==='recording')await stopVoice(false);const fd=new FormData(form);if(voiceBlob)fd.append('voice',voiceBlob,`voice-${Date.now()}.webm`);const result=await request('/ajax/channel-comment/send',fd);q('[data-channel-comments-empty]')?.remove();const parent=Number(result.parent_id||0),root=Number(result.root_id||0);let target=parent===root?q('[data-channel-post-comments-list]'):q(`[data-channel-comment-children="${parent}"]`);if(target&&result.html)target.insertAdjacentHTML('beforeend',result.html);q('textarea',form).value='';q('[data-channel-comment-files]',form).value='';q('[data-channel-comment-file-list]',form).innerHTML='';voiceBlob=null;cancelReply();const count=q('[data-channel-comment-count]');if(count)count.textContent=String(result.count||0);}catch(err){toast(err.message,'error');}finally{submit.disabled=false;}});}
document.addEventListener('click',e=>{const b=e.target.closest('[data-channel-comment-reply]');if(b)setReply(Number(b.dataset.channelCommentReply),b.dataset.channelCommentAuthor);});
document.addEventListener('contextmenu',e=>{const node=e.target.closest('[data-channel-comment-node]');if(!node||!commentMenu)return;e.preventDefault();contextComment=node;const msg=q('.message',node);q('[data-channel-comment-action="edit"]',commentMenu).hidden=!q('[data-edit]',msg);q('[data-channel-comment-action="delete"]',commentMenu).hidden=!q('[data-delete]',msg);commentMenu.hidden=false;commentMenu.style.left=`${Math.max(8,Math.min(e.clientX,innerWidth-210))}px`;commentMenu.style.top=`${Math.max(8,Math.min(e.clientY,innerHeight-220))}px`;});
document.addEventListener('click',async e=>{const action=e.target.closest('[data-channel-comment-action]');if(!action){if(!e.target.closest('[data-channel-comment-menu]')&&commentMenu)commentMenu.hidden=true;return;}if(!contextComment)return;const msg=q('.message',contextComment),kind=action.dataset.channelCommentAction;commentMenu.hidden=true;try{if(kind==='reply')setReply(Number(contextComment.dataset.channelCommentNode),contextComment.dataset.channelCommentAuthor);else if(kind==='copy')await navigator.clipboard.writeText(msg?.dataset.messageBody||'');else if(kind==='edit')q('[data-edit]',msg)?.click();else if(kind==='delete')q('[data-delete]',msg)?.click();}catch(err){toast(err.message,'error');}});

/* One persistent audio engine for the entire user shell. */
const player=q('[data-global-player]'),audio=q('[data-player-audio]',player||document);
const PLAYER_KEY='kovcheg-player-v6';
let queue=[],index=-1,manualQueue=false,wantsPlayback=false,lastSavedAt=0,resumeUnlockBound=false,repeatMode='off',lastVolume=1,pausedAt=0;
const fmt=value=>{const sec=Math.max(0,Math.floor(Number(value)||0));return `${Math.floor(sec/60)}:${String(sec%60).padStart(2,'0')}`;};
const normalizeSrc=value=>{try{return new URL(String(value||''),location.href).href;}catch{return String(value||'');}};
const emptyTrackTitle=value=>/^(?:музыка\s+не\s+выбрана|трек\s+не\s+выбран|music\s+not\s+selected)$/iu.test(String(value||'').trim());
const cleanTrackTitle=(value,fallback='Аудиотрек')=>{const title=String(value||'').trim();return !title||emptyTrackTitle(title)?fallback:title;};
const titleFromSrc=value=>{try{const part=decodeURIComponent(new URL(String(value||''),location.href).pathname.split('/').pop()||'').replace(/\.[a-z0-9]{2,5}$/i,'').trim();return part&&!/^stream$/i.test(part)?part:'Аудиотрек';}catch{return 'Аудиотрек';}};
const uniqueTracks=list=>{const seen=new Set();return (Array.isArray(list)?list:[]).map(track=>{const src=normalizeSrc(track?.src),fallback=titleFromSrc(src);return{src,title:cleanTrackTitle(track?.title,fallback),author:String(track?.author||'').trim(),cover:String(track?.cover||'')};}).filter(track=>track.src&&!seen.has(track.src)&&(seen.add(track.src),true));};
function playerNodes(){return {
 title:q('[data-player-title]',player),author:q('[data-player-author]',player),play:q('[data-player-play]',player),progress:q('[data-player-progress]',player),clock:q('[data-player-time]',player),prev:q('[data-player-prev]',player),next:q('[data-player-next]',player),repeat:q('[data-player-repeat]',player),volume:q('[data-player-volume]',player),mute:q('[data-player-mute]',player),close:q('[data-player-close]',player)
};}
function pageTracks(){return uniqueTracks(qa('[data-player-track]').map(row=>({src:row.dataset.src,title:row.dataset.title||q('b',row)?.textContent||'Аудио',author:row.dataset.artist||q('small',row)?.textContent||'',cover:row.dataset.cover||q('img',row)?.src||''})));}
function scanQueue(){if(manualQueue&&queue.length)return;const found=pageTracks();if(found.length){const current=audio?.currentSrc||audio?.src||'';queue=found;index=Math.max(0,queue.findIndex(item=>normalizeSrc(item.src)===normalizeSrc(current)));}}
function hasTrack(){return Boolean(audio?.getAttribute('src')||audio?.currentSrc);}
function currentTrack(){const src=audio?.currentSrc||audio?.src||'';return queue[index]||{src,title:cleanTrackTitle(playerNodes().title?.textContent,titleFromSrc(src)),author:playerNodes().author?.textContent||''};}
function repeatLabel(){return repeatMode==='one'?'Повторять один трек':repeatMode==='all'?'Повторять плейлист':'Повтор выключен';}
function setRepeat(mode){repeatMode=['off','all','one'].includes(mode)?mode:'off';const nodes=playerNodes();if(nodes.repeat){nodes.repeat.dataset.repeatMode=repeatMode;nodes.repeat.classList.toggle('active',repeatMode!=='off');nodes.repeat.classList.toggle('repeat-one',repeatMode==='one');nodes.repeat.textContent=repeatMode==='one'?'↻¹':'↻';nodes.repeat.title=repeatLabel();nodes.repeat.setAttribute('aria-label',repeatLabel());}syncMirrors();writeState();}
function setIdle(idle=true){if(!player)return;const nodes=playerNodes();player.hidden=false;player.classList.toggle('is-idle',idle);document.body?.classList.toggle('global-player-active',!idle);[nodes.prev,nodes.play,nodes.next,nodes.progress,nodes.repeat,nodes.volume,nodes.mute].forEach(control=>{if(control)control.disabled=idle;});if(nodes.close)nodes.close.hidden=idle;if(idle){if(nodes.title)nodes.title.textContent='';if(nodes.author)nodes.author.textContent='';if(nodes.progress)nodes.progress.value='0';if(nodes.clock)nodes.clock.textContent='0:00 / 0:00';if(nodes.play)nodes.play.textContent='▶';}syncMirrors();syncRows();}
function readState(){try{return JSON.parse(localStorage.getItem(PLAYER_KEY)||'null');}catch{return null;}}
function writeState(forcePlaying=null){if(!hasTrack()){localStorage.removeItem(PLAYER_KEY);return;}const nodes=playerNodes(),playing=forcePlaying===null?(!audio.paused&&!audio.ended):Boolean(forcePlaying),src=audio.currentSrc||audio.src;const state={src,title:cleanTrackTitle(nodes.title?.textContent,titleFromSrc(src)),author:nodes.author?.textContent||'',time:Number(audio.currentTime||pausedAt||0),playing,queue,index,manualQueue,volume:Number(audio.volume||1),muted:Boolean(audio.muted),repeatMode,savedAt:Date.now()};try{localStorage.setItem(PLAYER_KEY,JSON.stringify(state));}catch{}lastSavedAt=Date.now();}
function bindResumeUnlock(){if(resumeUnlockBound)return;resumeUnlockBound=true;const attempt=()=>{resumeUnlockBound=false;if(wantsPlayback&&hasTrack())audio.play().catch(bindResumeUnlock);};document.addEventListener('pointerdown',attempt,{once:true,capture:true});document.addEventListener('keydown',attempt,{once:true,capture:true});document.addEventListener('touchstart',attempt,{once:true,capture:true,passive:true});}
function tryResume(){if(!wantsPlayback||!hasTrack()||!audio?.paused)return;audio.play().then(()=>{resumeUnlockBound=false;}).catch(bindResumeUnlock);}
function updateMediaSession(track){if(!('mediaSession'in navigator))return;try{navigator.mediaSession.metadata=new MediaMetadata({title:track?.title||'Аудио',artist:track?.author||'',album:'KOVCHEG CMS'});}catch{}}
function syncRows(){const src=audio?.currentSrc||audio?.src||'';qa('[data-player-track]').forEach(row=>{const active=Boolean(src&&normalizeSrc(row.dataset.src)===normalizeSrc(src));row.classList.toggle('is-playing',active&&!audio?.paused);row.classList.toggle('is-current',active);const button=q('[data-player-track-play]',row),icon=q('i',button);if(icon)icon.textContent=active&&!audio?.paused?'❚❚':'▶';if(button){button.setAttribute('aria-label',active&&!audio?.paused?'Пауза':'Воспроизвести');button.title=active&&!audio?.paused?'Пауза':'Воспроизвести';}});}
function syncMirrors(){const nodes=playerNodes(),idle=!hasTrack();qa('[data-player-mirror]').forEach(mirror=>{mirror.classList.toggle('is-idle',idle);mirror.classList.toggle('is-playing',!idle&&!audio.paused);const title=q('[data-player-mirror-title]',mirror),author=q('[data-player-mirror-author]',mirror),play=q('[data-player-mirror-play]',mirror),progress=q('[data-player-mirror-progress]',mirror),time=q('[data-player-mirror-time]',mirror),repeat=q('[data-player-mirror-repeat]',mirror),volume=q('[data-player-mirror-volume]',mirror),mute=q('[data-player-mirror-mute]',mirror);if(title)title.textContent=nodes.title?.textContent||'';if(author)author.textContent=nodes.author?.textContent||'';if(play){play.textContent=!idle&&!audio.paused?'❚❚':'▶';play.disabled=idle;}if(progress){progress.value=nodes.progress?.value||'0';progress.disabled=idle;}if(time)time.textContent=nodes.clock?.textContent||'0:00 / 0:00';if(repeat){repeat.textContent=repeatMode==='one'?'↻¹':'↻';repeat.classList.toggle('active',repeatMode!=='off');repeat.title=repeatLabel();repeat.disabled=idle;}if(volume)volume.value=String(Math.round((audio?.volume??1)*100));if(mute){mute.textContent=audio?.muted||audio?.volume===0?'🔇':'🔊';mute.disabled=idle;}});}
function syncUi(){if(!player||!audio)return;const nodes=playerNodes(),duration=Number(audio.duration||0),current=Number(audio.currentTime||0),idle=!hasTrack(),finished=!idle&&audio.paused&&duration>0&&current>=duration-.25;player.classList.toggle('has-no-track',idle);player.classList.toggle('is-finished',finished);player.classList.toggle('is-idle',idle);if(nodes.title&&emptyTrackTitle(nodes.title.textContent))nodes.title.textContent=idle?'':cleanTrackTitle('',titleFromSrc(audio.currentSrc||audio.src));if(nodes.progress)nodes.progress.value=duration?String(Math.round(current/duration*1000)):'0';if(nodes.clock)nodes.clock.textContent=`${fmt(current)} / ${fmt(duration)}`;if(nodes.play)nodes.play.textContent=!idle&&!audio.paused?'❚❚':'▶';if(nodes.volume)nodes.volume.value=String(Math.round(audio.volume*100));if(nodes.mute)nodes.mute.textContent=audio.muted||audio.volume===0?'🔇':'🔊';syncMirrors();syncRows();}
function loadTrack(track,autoplay=true,keepTime=0){if(!player||!audio||!track?.src)return;const nodes=playerNodes();wantsPlayback=Boolean(autoplay);const source=normalizeSrc(track.src),sameSource=normalizeSrc(audio.currentSrc||audio.src)===source;if(!sameSource){audio.src=source;audio.load();pausedAt=0;}else if(audio.ended||(Number.isFinite(audio.duration)&&audio.duration>0&&audio.currentTime>=audio.duration-.25)){try{audio.currentTime=0;pausedAt=0;}catch{}}track={...track,src:source,title:cleanTrackTitle(track.title,titleFromSrc(source))};if(nodes.title)nodes.title.textContent=track.title;if(nodes.author)nodes.author.textContent=String(track.author||'').trim();index=queue.findIndex(item=>normalizeSrc(item.src)===normalizeSrc(track.src));setIdle(false);updateMediaSession(track);const start=()=>{if(keepTime>0&&Number.isFinite(keepTime)){try{audio.currentTime=Math.min(keepTime,Math.max(0,(audio.duration||keepTime+1)-.1));pausedAt=audio.currentTime;}catch{}}if(autoplay)tryResume();syncUi();writeState(autoplay);};if(audio.readyState>=1)start();else audio.addEventListener('loadedmetadata',start,{once:true});}
function playIndex(nextIndex,autoplay=true){if(!queue.length)return;index=Math.max(0,Math.min(queue.length-1,Number(nextIndex)||0));loadTrack(queue[index],autoplay);}
function togglePlay(){if(!hasTrack())return;if(audio.paused){if(audio.ended||(Number.isFinite(audio.duration)&&audio.duration>0&&audio.currentTime>=audio.duration-.05)){try{audio.currentTime=0;pausedAt=0;}catch{}}wantsPlayback=true;audio.play().then(()=>{resumeUnlockBound=false;}).catch(bindResumeUnlock);}else{pausedAt=Number(audio.currentTime||0);wantsPlayback=false;audio.pause();syncUi();writeState(false);}}
function previous(){scanQueue();if(!queue.length)return;if(audio.currentTime>4){audio.currentTime=0;syncUi();return;}if(index>0)playIndex(index-1,true);else if(repeatMode==='all')playIndex(queue.length-1,true);}
function following(manual=true){scanQueue();if(!queue.length)return;if(index<queue.length-1)playIndex(index+1,true);else if(repeatMode==='all'||manual)playIndex(0,true);else{wantsPlayback=false;audio.pause();try{audio.currentTime=0;}catch{}syncUi();writeState(false);}}
function cycleRepeat(){setRepeat(repeatMode==='off'?'all':repeatMode==='all'?'one':'off');}
function setVolume(value){const number=Math.max(0,Math.min(1,Number(value)));audio.volume=number;if(number>0){lastVolume=number;audio.muted=false;}syncUi();writeState();}
function toggleMute(){if(audio.muted||audio.volume===0){audio.muted=false;if(audio.volume===0)audio.volume=lastVolume||1;}else{lastVolume=audio.volume||1;audio.muted=true;}syncUi();writeState();}
function clear(){wantsPlayback=false;pausedAt=0;audio.pause();audio.removeAttribute('src');audio.load();queue=[];index=-1;manualQueue=false;localStorage.removeItem(PLAYER_KEY);setIdle(true);}
function prepareNavigation(){writeState(wantsPlayback||(!audio.paused&&hasTrack()));}
window.KovchegPlayer={playTrack(track){const page=pageTracks();queue=page.length?page:uniqueTracks([track]);manualQueue=queue.length>1;const found=queue.findIndex(item=>normalizeSrc(item.src)===normalizeSrc(track?.src));loadTrack(queue[Math.max(0,found)],true);},playPlaylist(tracks,start=0){queue=uniqueTracks(tracks);manualQueue=true;if(queue.length)playIndex(start,true);},add(track){queue=uniqueTracks([...queue,track]);},clearQueue:clear,prepareNavigation,togglePlay,previous,next:()=>following(true),cycleRepeat,setRepeat,setVolume,toggleMute,state(){return {queue:[...queue],index,playing:wantsPlayback,hasTrack:hasTrack(),repeatMode,volume:audio?.volume??1,muted:audio?.muted??false,currentTime:audio?.currentTime??0,track:currentTrack()};},sync:syncUi};
const nodes=playerNodes();nodes.play?.addEventListener('click',togglePlay);nodes.prev?.addEventListener('click',previous);nodes.next?.addEventListener('click',()=>following(true));nodes.repeat?.addEventListener('click',cycleRepeat);nodes.progress?.addEventListener('input',()=>{if(audio.duration)audio.currentTime=Number(nodes.progress.value)/1000*audio.duration;syncUi();writeState();});nodes.volume?.addEventListener('input',()=>setVolume(Number(nodes.volume.value)/100));nodes.mute?.addEventListener('click',toggleMute);nodes.close?.addEventListener('click',clear);
document.addEventListener('click',event=>{const button=event.target.closest('[data-player-mirror-play],[data-player-mirror-prev],[data-player-mirror-next],[data-player-mirror-repeat],[data-player-mirror-mute]');if(!button)return;if(button.matches('[data-player-mirror-play]'))togglePlay();else if(button.matches('[data-player-mirror-prev]'))previous();else if(button.matches('[data-player-mirror-next]'))following(true);else if(button.matches('[data-player-mirror-repeat]'))cycleRepeat();else toggleMute();});
document.addEventListener('input',event=>{const range=event.target.closest('[data-player-mirror-progress],[data-player-mirror-volume]');if(!range)return;if(range.matches('[data-player-mirror-progress]')&&audio.duration)audio.currentTime=Number(range.value)/1000*audio.duration;else if(range.matches('[data-player-mirror-volume]'))setVolume(Number(range.value)/100);syncUi();writeState();});
audio?.addEventListener('play',()=>{wantsPlayback=true;setIdle(false);syncUi();writeState(true);});audio?.addEventListener('pause',()=>{if(!audio.ended)pausedAt=Number(audio.currentTime||0);syncUi();writeState(false);});audio?.addEventListener('timeupdate',()=>{if(!audio.paused&&!audio.ended)pausedAt=Number(audio.currentTime||0);syncUi();if(Date.now()-lastSavedAt>1000)writeState(wantsPlayback&&!audio.paused);});audio?.addEventListener('volumechange',syncUi);audio?.addEventListener('loadedmetadata',syncUi);audio?.addEventListener('ended',()=>{pausedAt=0;if(repeatMode==='one'){audio.currentTime=0;wantsPlayback=true;audio.play().catch(bindResumeUnlock);return;}if(index<queue.length-1||repeatMode==='all'){following(false);return;}wantsPlayback=false;try{audio.currentTime=0;}catch{}syncUi();writeState(false);});audio?.addEventListener('canplay',tryResume);
if('mediaSession'in navigator){try{navigator.mediaSession.setActionHandler('play',()=>{wantsPlayback=true;tryResume();});navigator.mediaSession.setActionHandler('pause',()=>{wantsPlayback=false;audio.pause();});navigator.mediaSession.setActionHandler('previoustrack',previous);navigator.mediaSession.setActionHandler('nexttrack',()=>following(true));navigator.mediaSession.setActionHandler('seekto',details=>{if(Number.isFinite(details.seekTime))audio.currentTime=details.seekTime;});}catch{}}
const state=readState();if(state?.src&&player&&audio){queue=uniqueTracks(state.queue);index=Number.isInteger(state.index)?state.index:-1;manualQueue=Boolean(state.manualQueue);wantsPlayback=Boolean(state.playing);repeatMode=['off','all','one'].includes(state.repeatMode)?state.repeatMode:'off';audio.volume=Math.max(0,Math.min(1,Number(state.volume??1)));audio.muted=Boolean(state.muted);const saved=queue.find(item=>normalizeSrc(item.src)===normalizeSrc(state.src));const restoredTitle=cleanTrackTitle(state.title,saved?.title||titleFromSrc(state.src));pausedAt=Number(state.time||0);loadTrack({src:state.src,title:restoredTitle,author:state.author||saved?.author||''},wantsPlayback,pausedAt);}else setIdle(true);
setRepeat(repeatMode);syncUi();
document.addEventListener('kovcheg:pagechange',()=>{scanQueue();syncUi();});document.addEventListener('visibilitychange',()=>{if(document.hidden)writeState(wantsPlayback||(!audio?.paused&&hasTrack()));});addEventListener('pagehide',prepareNavigation);addEventListener('beforeunload',prepareNavigation);
})();
(()=>{'use strict';
const q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];

/*
 * Надёжное меню публикации.
 * Исходное меню внутри карточки используется только как шаблон. Визуальный экземпляр
 * всегда создаётся непосредственно в body, поэтому ни одна колонка и overflow его не обрежут.
 */
let portal=null,anchor=null,closeTimer=0;
const clearTimer=()=>{if(closeTimer){clearTimeout(closeTimer);closeTimer=0;}};
function closeWallPortal(){clearTimer();portal?.remove();portal=null;anchor=null;}
function scheduleClose(delay=170){clearTimer();closeTimer=setTimeout(closeWallPortal,delay);}
function sourceMenu(button){
 const id=String(button?.dataset?.wallMenuToggle||'');
 if(!id)return null;
 try{return q(`[data-wall-menu="${CSS.escape(id)}"]`);}catch{return null;}
}
function placePortal(button,pop){
 const rect=button.getBoundingClientRect();
 const margin=8,gap=6;
 const width=Math.max(210,Math.ceil(pop.getBoundingClientRect().width||210));
 const height=Math.max(80,Math.ceil(pop.getBoundingClientRect().height||80));
 let left=rect.right-width;
 left=Math.max(margin,Math.min(left,window.innerWidth-width-margin));
 let top=rect.bottom+gap;
 if(top+height>window.innerHeight-margin)top=rect.top-height-gap;
 top=Math.max(margin,Math.min(top,window.innerHeight-height-margin));
 pop.style.left=`${Math.round(left)}px`;
 pop.style.top=`${Math.round(top)}px`;
}
function openWallPortal(button){
 if(anchor===button&&portal?.isConnected){clearTimer();return;}
 const source=sourceMenu(button);if(!source)return;
 closeWallPortal();
 anchor=button;
 portal=document.createElement('div');
 portal.className='wall-menu-portal-191';
 portal.setAttribute('role','menu');
 portal.setAttribute('aria-label','Действия с публикацией');
 portal.innerHTML=source.innerHTML;
 document.body.append(portal);
 placePortal(button,portal);
 portal.addEventListener('pointerenter',clearTimer);
 portal.addEventListener('pointerleave',()=>scheduleClose(130));
 portal.addEventListener('click',()=>setTimeout(closeWallPortal,0));
}

document.addEventListener('pointerover',event=>{
 const button=event.target.closest?.('[data-wall-menu-toggle]');
 if(button)openWallPortal(button);
},true);
document.addEventListener('pointerout',event=>{
 const button=event.target.closest?.('[data-wall-menu-toggle]');
 if(button&&!portal?.contains(event.relatedTarget))scheduleClose();
},true);
document.addEventListener('click',event=>{
 const button=event.target.closest?.('[data-wall-menu-toggle]');
 if(button){event.preventDefault();event.stopImmediatePropagation();openWallPortal(button);return;}
 if(portal&&!portal.contains(event.target))closeWallPortal();
},true);
document.addEventListener('scroll',closeWallPortal,true);
window.addEventListener('wheel',closeWallPortal,{capture:true,passive:true});
window.addEventListener('touchmove',closeWallPortal,{capture:true,passive:true});
window.addEventListener('resize',closeWallPortal,{passive:true});
window.addEventListener('blur',closeWallPortal);

/* Обсуждение канала прокручивается внутри своей области, поле ввода остаётся на месте. */
const list=q('[data-channel-post-comments-list]');
if(list){
 const nearBottom=()=>list.scrollHeight-list.scrollTop-list.clientHeight<110;
 let shouldFollow=true;
 list.addEventListener('scroll',()=>{shouldFollow=nearBottom();},{passive:true});
 const observer=new MutationObserver(()=>{
  if(shouldFollow||nearBottom())requestAnimationFrame(()=>{list.scrollTop=list.scrollHeight;});
 });
 observer.observe(list,{childList:true,subtree:true});
 requestAnimationFrame(()=>{list.scrollTop=list.scrollHeight;});
}
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const base=String(K.baseUrl||'').replace(/\/$/,'');
const url=p=>/^https?:/i.test(String(p))?String(p):`${base}${String(p).startsWith('/')?'':'/'}${p}`;
const toast=(text,type='success')=>window.KovchegShowToast?.(text,{type});
async function post(path,data={}){
 const fd=data instanceof FormData?data:new FormData();
 if(!(data instanceof FormData))Object.entries(data).forEach(([key,value])=>fd.append(key,String(value)));
 if(!fd.has('_csrf'))fd.append('_csrf',K.csrf||'');
 const response=await fetch(url(path),{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-Token':K.csrf||''}});
 const result=await response.json().catch(()=>({}));
 if(!response.ok||result.ok===false)throw new Error(result.error||result.message||`Ошибка ${response.status}`);
 return result;
}

/* Replies collapse consistently on wall, avatar and channel discussions. */
function directCommentChildren(container){return [...container.children].filter(el=>el.matches?.('.wall-comment,.avatar-comment,.channel-comment-node'));}
function branchToggleFor(container){
 let branch=container.closest('[data-comment-branch]');
 if(branch)return q('[data-comment-branch-toggle]',branch);
 return container.previousElementSibling?.matches?.('[data-comment-branch-toggle]')?container.previousElementSibling:null;
}
function setBranch(container,open){
 const toggle=branchToggleFor(container);container.hidden=!open;
 if(toggle){const count=directCommentChildren(container).length;toggle.textContent=open?'Скрыть ответы':`Показать ${count} ${count===1?'ответ':count<5?'ответа':'ответов'}`;toggle.setAttribute('aria-expanded',open?'true':'false');}
}
function normalizeBranch(container){
 if(!(container instanceof HTMLElement))return;
 const children=directCommentChildren(container),count=children.length;
 let toggle=branchToggleFor(container);
 if(count>1){
   if(!toggle){toggle=document.createElement('button');toggle.type='button';toggle.className='comment-branch-toggle';toggle.dataset.commentBranchToggle='';container.before(toggle);}
   if(!container.dataset.branchInitialized){container.dataset.branchInitialized='1';setBranch(container,false);}else if(container.hidden){setBranch(container,false);}else{toggle.textContent='Скрыть ответы';toggle.setAttribute('aria-expanded','true');}
 }else{
   if(toggle)toggle.remove();container.hidden=false;container.dataset.branchInitialized='1';
 }
}
function normalizeAllBranches(root=document){qa('.wall-comment-replies,.avatar-comment-replies,.channel-comment-children',root).forEach(normalizeBranch);}
normalizeAllBranches();
document.addEventListener('click',event=>{
 const toggle=event.target.closest('[data-comment-branch-toggle]');if(!toggle)return;
 event.preventDefault();
 const branch=toggle.closest('[data-comment-branch]');
 const children=branch?q('.comment-branch-children',branch):toggle.nextElementSibling;
 if(children)setBranch(children,children.hidden);
});
new MutationObserver(records=>records.forEach(record=>{
 if(record.target instanceof HTMLElement&&record.target.matches('.wall-comment-replies,.avatar-comment-replies,.channel-comment-children'))normalizeBranch(record.target);
 record.addedNodes.forEach(node=>{if(node.nodeType===1)normalizeAllBranches(node);});
})).observe(document.body,{childList:true,subtree:true});

/* Reactions on every wall/avatar comment and nested reply. */
function renderReactionSummary(wrap,result){
 const summary=q('[data-comment-reaction-summary]',wrap);if(!summary)return;
 const items=Array.isArray(result.items)?result.items:[];
 summary.innerHTML=items.map(item=>`<button type="button" data-comment-react="${String(item.emoji).replace(/"/g,'&quot;')}" class="${item.mine?'mine':''}">${item.emoji} <span>${Number(item.count)||0}</span></button>`).join('');
 summary.hidden=!items.length;
 qa('[data-comment-reaction-picker] button',wrap).forEach(button=>button.classList.toggle('active',button.dataset.commentReact===result.mine));
}
document.addEventListener('click',async event=>{
 const trigger=event.target.closest('[data-comment-react-trigger]');
 if(trigger){event.preventDefault();const wrap=trigger.closest('[data-comment-reaction-wrap]'),picker=q('[data-comment-reaction-picker]',wrap);qa('[data-comment-reaction-picker]').forEach(x=>{if(x!==picker)x.hidden=true;});if(picker)picker.hidden=!picker.hidden;return;}
 const reaction=event.target.closest('[data-comment-react]');
 if(reaction){
   const wrap=reaction.closest('[data-comment-reaction-wrap]');if(!wrap)return;
   event.preventDefault();
   try{const result=await post(`/comment/${encodeURIComponent(wrap.dataset.commentContext)}/${Number(wrap.dataset.commentId)}/react`,{emoji:reaction.dataset.commentReact});renderReactionSummary(wrap,result);const picker=q('[data-comment-reaction-picker]',wrap);if(picker)picker.hidden=true;}catch(error){toast(error.message,'error');}
   return;
 }
 if(!event.target.closest('[data-comment-reaction-wrap]'))qa('[data-comment-reaction-picker]').forEach(x=>x.hidden=true);
});

/* A broken media preview falls back without inline script handlers (CSP-safe). */
document.addEventListener('error',event=>{
 const img=event.target;if(!(img instanceof HTMLImageElement)||!img.closest('.image-attachment'))return;
 img.hidden=true;const fallback=img.nextElementSibling;if(fallback)fallback.style.display='block';
},true);

/* Theme metadata and black mode are updated instantly. */
function syncThemeMeta(){const theme=document.body.dataset.theme;const color=theme==='black'?'#000000':theme==='light'?'#eef3f8':'#172431';q('meta[name="theme-color"]')?.setAttribute('content',color);}
document.addEventListener('click',event=>{if(event.target.closest('[data-quick-theme]'))setTimeout(syncThemeMeta,0);});syncThemeMeta();

/* Keep the channel-post composer below comments and never over them. */
function fitChannelPost(){const page=q('[data-channel-post-page]');if(!page)return;const top=q('.topbar')?.getBoundingClientRect().height||0;page.style.setProperty('--topbar-height',`${Math.ceil(top)}px`);}
addEventListener('resize',fitChannelPost,{passive:true});fitChannelPost();
})();
(()=>{'use strict';
const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
const base=String(K.baseUrl||'').replace(/\/$/,'');
const url=p=>/^https?:/i.test(String(p))?String(p):`${base}${String(p).startsWith('/')?'':'/'}${p}`;
const toast=(text,type='success')=>window.KovchegShowToast?.(text,{type});
const allowed=['👍','❤️','🔥','😂','👏','😮','😢','👎'];
async function post(path,data={}){
 const fd=new FormData();Object.entries(data).forEach(([key,value])=>fd.append(key,String(value)));fd.append('_csrf',K.csrf||'');
 const response=await fetch(url(path),{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-Token':K.csrf||''}});
 const result=await response.json().catch(()=>({}));if(!response.ok||result.ok===false)throw new Error(result.error||result.message||`Ошибка ${response.status}`);return result;
}

/* A single viewport popover avoids overflow clipping and does not change comment layout. */
const popover=document.createElement('div');popover.className='comment-reaction-popover';popover.hidden=true;popover.setAttribute('role','menu');popover.innerHTML=allowed.map(emoji=>`<button type="button" data-modern-comment-react="${emoji}" aria-label="${emoji}">${emoji}</button>`).join('');document.body.append(popover);
let activeWrap=null,activeTrigger=null;
function closePopover(){popover.hidden=true;activeWrap=null;activeTrigger=null;qa('[data-comment-react-trigger][aria-expanded="true"]').forEach(el=>el.setAttribute('aria-expanded','false'));}
function placePopover(){if(!activeTrigger||popover.hidden)return;const r=activeTrigger.getBoundingClientRect(),gap=8,w=popover.offsetWidth,h=popover.offsetHeight;let left=r.left+(r.width-w)/2;left=Math.max(8,Math.min(left,innerWidth-w-8));let top=r.top-h-gap;if(top<8)top=Math.min(innerHeight-h-8,r.bottom+gap);popover.style.left=`${Math.round(left)}px`;popover.style.top=`${Math.round(top)}px`;}
function openPopover(trigger,wrap){if(activeWrap===wrap&&!popover.hidden){closePopover();return;}closePopover();activeWrap=wrap;activeTrigger=trigger;const mine=qa('[data-comment-reaction-summary] button.mine',wrap)[0]?.dataset.commentReact||'';qa('button',popover).forEach(button=>button.classList.toggle('active',button.dataset.modernCommentReact===mine));popover.hidden=false;trigger.setAttribute('aria-expanded','true');requestAnimationFrame(placePopover);}
function renderSummary(wrap,result){const summary=q('[data-comment-reaction-summary]',wrap);if(!summary)return;const items=Array.isArray(result.items)?result.items:[];summary.innerHTML=items.map(item=>`<button type="button" data-comment-react="${String(item.emoji).replace(/"/g,'&quot;')}" class="${item.mine?'mine':''}">${item.emoji}<span>${Number(item.count)||0}</span></button>`).join('');summary.hidden=!items.length;}
async function react(wrap,emoji){if(!wrap)return;try{const result=await post(`/comment/${encodeURIComponent(wrap.dataset.commentContext||'')}/${Number(wrap.dataset.commentId||0)}/react`,{emoji});renderSummary(wrap,result);}catch(error){toast(error.message,'error');}finally{closePopover();}}

document.addEventListener('click',event=>{
 const trigger=event.target.closest('[data-comment-react-trigger]');
 if(trigger){event.preventDefault();event.stopImmediatePropagation();const wrap=trigger.closest('[data-comment-reaction-wrap]');if(wrap)openPopover(trigger,wrap);return;}
 const summaryButton=event.target.closest('[data-comment-reaction-summary] [data-comment-react]');
 if(summaryButton){event.preventDefault();event.stopImmediatePropagation();react(summaryButton.closest('[data-comment-reaction-wrap]'),summaryButton.dataset.commentReact);return;}
 if(!event.target.closest('.comment-reaction-popover'))closePopover();
},true);
popover.addEventListener('click',event=>{const button=event.target.closest('[data-modern-comment-react]');if(!button||!activeWrap)return;event.preventDefault();react(activeWrap,button.dataset.modernCommentReact);});
addEventListener('scroll',closePopover,{capture:true,passive:true});addEventListener('resize',closePopover,{passive:true});document.addEventListener('keydown',event=>{if(event.key==='Escape')closePopover();});

/* Remove the old measured inset and keep the channel shell visually stable. */
function stabilizeChannel(){const page=q('[data-channel-post-page]');if(!page)return;page.style.removeProperty('--topbar-height');page.classList.add('channel-ui-stable');qa('[data-comment-reaction-picker]',page).forEach(picker=>picker.hidden=true);}
stabilizeChannel();new MutationObserver(records=>{if(records.some(record=>[...record.addedNodes].some(node=>node.nodeType===1)))stabilizeChannel();}).observe(document.body,{childList:true,subtree:true});
})();
(()=>{'use strict';
const q=(s,r=document)=>r.querySelector(s);

function channelPageFromTarget(target){
 if(!(target instanceof Element))return null;
 return target.closest('[data-channel-post-page]');
}
function mainScroller(page){return q('[data-channel-post-scroll]',page);}
function pixelDelta(event,scroller){
 let value=Number(event.deltaY)||0;
 if(event.deltaMode===1)value*=24;
 else if(event.deltaMode===2)value*=Math.max(240,scroller.clientHeight*.88);
 return value;
}
function canScroll(el,delta){
 if(!el||Math.abs(delta)<.1)return false;
 const max=Math.max(0,el.scrollHeight-el.clientHeight);
 return max>1&&(delta<0?el.scrollTop>0:el.scrollTop<max-1);
}
function nestedScrollable(target,root){
 let node=target instanceof Element?target:null;
 while(node&&node!==root){
  if(node.matches('textarea,[contenteditable="true"]')){
   if(node.scrollHeight>node.clientHeight+1)return node;
  }
  const style=getComputedStyle(node);
  if(/auto|scroll/.test(style.overflowY)&&node.scrollHeight>node.clientHeight+1)return node;
  node=node.parentElement;
 }
 return null;
}
function move(scroller,delta){
 const max=Math.max(0,scroller.scrollHeight-scroller.clientHeight);
 if(max<2)return false;
 const before=scroller.scrollTop;
 const next=Math.max(0,Math.min(max,before+delta));
 if(Math.abs(next-before)<.5)return false;
 scroller.scrollTop=next;
 return true;
}

/* Route wheel/touchpad deltas to the actual channel stream. This also works when the cursor is
   over a comment, avatar, reaction or the fixed composer. Native scrolling is left alone for a
   genuinely scrollable textarea or media control. */
document.addEventListener('wheel',event=>{
 const page=channelPageFromTarget(event.target);if(!page||event.ctrlKey)return;
 const scroller=mainScroller(page);if(!scroller)return;
 const delta=pixelDelta(event,scroller);if(Math.abs(delta)<.1)return;
 const nested=nestedScrollable(event.target,scroller);
 if(nested&&canScroll(nested,delta))return;
 if(move(scroller,delta))event.preventDefault();
},{capture:true,passive:false});

function prepare(page){
 const scroller=mainScroller(page);if(!scroller)return;
 scroller.setAttribute('tabindex','0');
 scroller.setAttribute('role','region');
 scroller.setAttribute('aria-label','Публикация и комментарии канала');
}
function prepareAll(){document.querySelectorAll('[data-channel-post-page]').forEach(prepare);}
prepareAll();
new MutationObserver(records=>{
 if(records.some(record=>[...record.addedNodes].some(node=>node.nodeType===1)))prepareAll();
}).observe(document.body,{subtree:true,childList:true});
})();
(()=>{'use strict';const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)],csrf=()=>document.querySelector('meta[name="csrf-token"]')?.content||'',base=()=>document.querySelector('meta[name="app-base"]')?.content||'';const close=()=>document.querySelectorAll('.reaction-menu-modern.is-portalled').forEach(el=>{el.hidden=true;el.classList.remove('is-portalled');el.style.cssText='';el.__home?.appendChild(el)});const place=(b,m)=>{close();m.__home=m.parentElement;document.body.appendChild(m);m.hidden=false;m.classList.add('is-portalled');m.style.visibility='hidden';const br=b.getBoundingClientRect(),mr=m.getBoundingClientRect(),mine=!!b.closest('.message.mine');let top=br.top-mr.height-8;if(top<8)top=br.bottom+8;let left=mine?br.right-mr.width:br.left;left=Math.max(8,Math.min(innerWidth-mr.width-8,left));m.style.left=left+'px';m.style.top=Math.max(8,top)+'px';m.style.visibility='visible'};document.addEventListener('click',async e=>{const t=e.target.closest('[data-reaction-menu]');if(t){e.preventDefault();e.stopImmediatePropagation();const m=t.closest('.bubble')?.querySelector('.reaction-menu-modern');if(m)place(t,m);return}const more=e.target.closest('[data-reaction-more]');if(more){e.preventDefault();e.stopImmediatePropagation();const g=more.closest('.reaction-menu-modern')?.querySelector('.reaction-more-grid');if(g)g.hidden=!g.hidden;return}const rr=e.target.closest('[data-message-react-modern]');if(rr){e.preventDefault();e.stopImmediatePropagation();const msg=rr.closest('.reaction-menu-modern')?.__home?.closest('.message'),id=msg?.dataset.messageId;if(!id)return;const fd=new FormData();fd.append('_csrf',csrf());fd.append('emoji',rr.dataset.messageReactModern);const res=await fetch(base()+'/ajax/message/'+id+'/react',{method:'POST',body:fd,headers:{Accept:'application/json'}});if(res.ok){const d=await res.json();if(d.html){const x=document.createElement('template');x.innerHTML=d.html.trim();msg.replaceWith(x.content.firstElementChild)}}close();return}const ex=e.target.closest('[data-wall-comments-expand]');if(ex){const list=ex.previousElementSibling;if(list){list.classList.remove('is-collapsed');ex.remove()}return}if(!e.target.closest('.reaction-menu-modern'))close()},true);addEventListener('scroll',close,true);addEventListener('resize',close);document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});const cats={recent:['😀','😂','😍','👍','❤️','🔥','👏','🎉'],faces:['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','🤐','🤢','🤮','🤧','😷','🤒','🤕'],gestures:['👍','👎','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝️','✋','🤚','🖐️','🖖','👋','🤝','👏','🙌','👐','🤲','🙏','✍️','💪','🫶'],hearts:['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝'],animals:['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🐧','🐦','🦄','🐝','🦋','🐞','🐟','🐬'],food:['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍒','🍑','🥭','🍍','🥝','🍅','🥑','🍕','🍔','🍟','🌭','🍿','🍰','☕'],objects:['⚡','🔥','✨','⭐','🌟','💫','🎉','🎊','🎯','🏆','🥇','🎵','🎶','🎤','🎧','📷','💻','📱','💡','✅','❌','⚠️','💯','🚀']},icons={recent:'🕘',faces:'😀',gestures:'👍',hearts:'❤️',animals:'🐱',food:'🍕',objects:'⭐'};const initPicker=p=>{if(p.dataset.modernized)return;p.dataset.modernized='1';p.innerHTML='<div class="emoji-picker-modern"><div class="emoji-picker-toolbar"></div><input class="emoji-search" type="search" placeholder="Найти эмодзи"><div class="emoji-grid-modern"></div></div>';const tb=$('.emoji-picker-toolbar',p),gr=$('.emoji-grid-modern',p),search=$('.emoji-search',p);let cur='recent';const render=()=>{const list=search.value.trim()?[...new Set(Object.values(cats).flat())]:cats[cur];gr.innerHTML=list.map(x=>'<button type="button" data-emoji-modern="'+x+'">'+x+'</button>').join('')};Object.keys(cats).forEach(k=>{const b=document.createElement('button');b.type='button';b.textContent=icons[k];b.classList.toggle('active',k===cur);b.onclick=ev=>{ev.stopPropagation();cur=k;$$('button',tb).forEach(x=>x.classList.remove('active'));b.classList.add('active');search.value='';render()};tb.appendChild(b)});search.oninput=render;gr.addEventListener('click',ev=>{const b=ev.target.closest('[data-emoji-modern]');if(!b)return;ev.stopImmediatePropagation();const f=p.closest('form')?.querySelector('textarea[name="body"],textarea');if(f){const a=f.selectionStart||f.value.length;f.setRangeText(b.dataset.emojiModern,a,f.selectionEnd||a,'end');f.focus()}p.hidden=true});render()};const init=()=>document.querySelectorAll('.emoji-picker').forEach(initPicker);document.addEventListener('DOMContentLoaded',init);new MutationObserver(init).observe(document.documentElement,{subtree:true,childList:true})})();
/* KOVCHEG CMS 3.0. */
(()=>{
 'use strict';
 let resizeBound=false;
 const initAdminShell=()=>{
  const shell=document.querySelector('[data-admin-shell]');
  if(!shell||shell.dataset.adminReady==='1')return;
  shell.dataset.adminReady='1';
  const toggle=shell.querySelector('[data-admin-nav-toggle]');
  const close=shell.querySelector('[data-admin-nav-close]');
  const overlay=shell.querySelector('[data-admin-nav-overlay]');
  const nav=shell.querySelector('[data-admin-nav]');
  const setOpen=(open)=>{
   shell.classList.toggle('admin-nav-open',open);
   document.documentElement.classList.toggle('admin-nav-lock',open);
   if(toggle)toggle.setAttribute('aria-expanded',open?'true':'false');
   if(overlay)overlay.hidden=!open;
   if(open&&nav){const current=nav.querySelector('a.active');(current||nav.querySelector('summary'))?.focus({preventScroll:true});}
  };
  toggle?.addEventListener('click',()=>setOpen(!shell.classList.contains('admin-nav-open')));
  close?.addEventListener('click',()=>setOpen(false));
  overlay?.addEventListener('click',()=>setOpen(false));
  nav?.addEventListener('click',(event)=>{if(event.target.closest('a')&&matchMedia('(max-width:900px)').matches)setOpen(false);});
  shell.addEventListener('keydown',(event)=>{if(event.key==='Escape'&&shell.classList.contains('admin-nav-open'))setOpen(false);});
  if(!resizeBound){resizeBound=true;addEventListener('resize',()=>{const current=document.querySelector('[data-admin-shell]');if(innerWidth>900&&current){current.classList.remove('admin-nav-open');document.documentElement.classList.remove('admin-nav-lock');const o=current.querySelector('[data-admin-nav-overlay]');if(o)o.hidden=true;}},{passive:true});}

  const groups=[...shell.querySelectorAll('.admin-nav-group')];
  groups.forEach((group)=>group.addEventListener('toggle',()=>{
   if(!group.open)return;
   groups.forEach((other)=>{if(other!==group)other.open=false;});
  }));

  const footer=shell.querySelector('.admin-footer');
  const syncFooterHeight=()=>{
   if(!footer)return;
   const height=Math.max(44,Math.ceil(footer.getBoundingClientRect().height));
   document.documentElement.style.setProperty('--admin-footer-height',`${height}px`);
  };
  syncFooterHeight();
  if(footer&&'ResizeObserver'in window)new ResizeObserver(syncFooterHeight).observe(footer);
 };
 const syncViewport=()=>{
  const height=window.visualViewport?.height||window.innerHeight;
  document.documentElement.style.setProperty('--mobile-viewport-height',`${Math.round(height)}px`);
 };
 const boot=()=>{initAdminShell();syncViewport();};
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
 document.addEventListener('kovcheg:pagechange',boot);
 window.visualViewport?.addEventListener('resize',syncViewport,{passive:true});
 window.addEventListener('orientationchange',syncViewport,{passive:true});
})();
/* KOVCHEG CMS 3.0. */
(()=>{
 'use strict';
 const ready=(fn)=>document.readyState==='loading'?document.addEventListener('DOMContentLoaded',fn,{once:true}):fn();
 ready(()=>{
  const K=window.KOVCHEG||{};
  const messenger=document.querySelector('.messenger');
  const mobile=()=>window.matchMedia('(max-width:900px)').matches;
  const root=document.documentElement;
  const body=document.body;
  const syncViewport=()=>{
   const vv=window.visualViewport;
   const height=Math.max(240,Math.round(vv?.height||window.innerHeight));
   root.style.setProperty('--mobile-viewport-height',`${height}px`);
   const keyboard=mobile()&&Boolean(vv)&&window.innerHeight-height>120;
   root.classList.toggle('mobile-keyboard-open',keyboard);
  };
  syncViewport();
  window.visualViewport?.addEventListener('resize',syncViewport,{passive:true});
  window.visualViewport?.addEventListener('scroll',syncViewport,{passive:true});
  window.addEventListener('resize',syncViewport,{passive:true});
  window.addEventListener('orientationchange',()=>setTimeout(syncViewport,120),{passive:true});


  const favicon=document.querySelector('#kovcheg-favicon');
  let updatingTitle=false;
  const plainTitle=()=>document.title.replace(/^\(\d+\)\s*/,'');
  let unread=Math.max(0,Number(K.messageUnread||0));
  let blinkTimer=0;
  let blinkOn=false;
  let baseImage=null;
  const baseHref=favicon?.dataset.baseFavicon||favicon?.href||`${K.baseUrl||''}/assets/icons/icon.svg`;
  const loadBase=()=>new Promise((resolve)=>{
   if(baseImage?.complete)return resolve(baseImage);
   const image=new Image();image.crossOrigin='anonymous';image.onload=()=>{baseImage=image;resolve(image);};image.onerror=()=>resolve(null);image.src=baseHref;baseImage=image;
  });
  const badgeFavicon=async(count)=>{
   const canvas=document.createElement('canvas');canvas.width=64;canvas.height=64;const ctx=canvas.getContext('2d');
   const image=await loadBase();
   if(image){try{ctx.drawImage(image,0,0,64,64);}catch(_){ctx.fillStyle='#3390ec';ctx.fillRect(0,0,64,64);}}
   else{ctx.fillStyle='#3390ec';ctx.fillRect(0,0,64,64);ctx.fillStyle='#fff';ctx.font='bold 38px sans-serif';ctx.textAlign='center';ctx.textBaseline='middle';ctx.fillText('K',32,34);}
   ctx.beginPath();ctx.fillStyle='#e53935';ctx.arc(47,17,17,0,Math.PI*2);ctx.fill();ctx.lineWidth=3;ctx.strokeStyle='#fff';ctx.stroke();
   const label=count>99?'99+':String(count);ctx.fillStyle='#fff';ctx.font=`bold ${label.length>2?16:21}px sans-serif`;ctx.textAlign='center';ctx.textBaseline='middle';ctx.fillText(label,47,18);
   return canvas.toDataURL('image/png');
  };
  const renderUnread=async()=>{
   const title=plainTitle();updatingTitle=true;document.title=unread>0?`(${unread}) ${title}`:title;queueMicrotask(()=>{updatingTitle=false;});
   if(!favicon)return;
   window.clearInterval(blinkTimer);blinkTimer=0;blinkOn=false;
   if(unread<=0){favicon.href=baseHref;return;}
   const badged=await badgeFavicon(unread);
   favicon.href=badged;
   blinkTimer=window.setInterval(()=>{blinkOn=!blinkOn;favicon.href=blinkOn?baseHref:badged;},850);
  };
  const setUnread=(count)=>{const next=Math.max(0,Number(count||0));if(next===unread&&blinkTimer)return;unread=next;K.messageUnread=next;renderUnread();};
  const titleNode=document.querySelector('title');if(titleNode)new MutationObserver(()=>{if(!updatingTitle)renderUnread();}).observe(titleNode,{childList:true,characterData:true,subtree:true});
  const sumChatBadges=()=>[...document.querySelectorAll('[data-chat-unread]')].reduce((sum,node)=>sum+(Number(String(node.textContent||'').replace(/\D/g,''))||0),0);
  if(messenger){
   const list=document.querySelector('[data-chat-items]');
   if(list)new MutationObserver(()=>setUnread(sumChatBadges())).observe(list,{subtree:true,childList:true,characterData:true});
   setUnread(sumChatBadges());
  }else{
   let pollBusy=false;
   const poll=async()=>{if(pollBusy||document.hidden)return;pollBusy=true;try{const response=await fetch(`${K.baseUrl||''}/ajax/messages/unread-count`,{credentials:'same-origin',headers:{Accept:'application/json'},cache:'no-store'});const data=await response.json();if(response.ok&&data.ok)setUnread(data.count);}catch(_){}finally{pollBusy=false;}};
   setUnread(unread);poll();window.setInterval(poll,7000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)poll();});window.addEventListener('focus',poll);
  }
 });
})();
(()=>{'use strict';
const q=(s,r=document)=>r.querySelector(s),isMobile=()=>matchMedia('(max-width:900px)').matches;
function syncSticky(){document.documentElement.style.setProperty('--mobile-topbar-height',`${Math.max(54,q('.topbar')?.getBoundingClientRect().height||58)}px`)}
addEventListener('resize',syncSticky,{passive:true});addEventListener('orientationchange',syncSticky,{passive:true});document.addEventListener('DOMContentLoaded',syncSticky);syncSticky();
// Keep the fixed mobile header opaque after theme or route changes.
new MutationObserver(syncSticky).observe(document.documentElement,{attributes:true,attributeFilter:['class','data-theme']});
// Avoid browsers restoring a translucent compositor layer on bottom navigation.
if(isMobile())document.documentElement.classList.add('mobile-solid-navigation');
})();
(()=>{
'use strict';
const q=(s,r=document)=>r.querySelector(s);
function syncPageClasses(){document.body.classList.toggle('profile-page-active',!!q('#kovcheg-page-content .vk-profile-page'));}
syncPageClasses();
new MutationObserver(syncPageClasses).observe(q('#kovcheg-page-content')||document.body,{childList:true,subtree:false});
document.addEventListener('kovcheg:pagechange',syncPageClasses);

/* Keep player intent before every normal same-tab navigation. update-190 performs the actual restore. */
function isNavigationLink(link){
 if(!link||link.target||link.hasAttribute('download'))return false;
 if(link.closest('[data-modal-open],[data-archive-preview-file]'))return false;
 try{const u=new URL(link.href,location.href);return u.origin===location.origin&&u.href!==location.href&&!u.hash.startsWith('#');}catch{return false;}
}
document.addEventListener('pointerdown',e=>{const link=e.target.closest?.('a[href]');if(isNavigationLink(link))window.KovchegPlayer?.prepareNavigation?.();},true);
document.addEventListener('submit',()=>window.KovchegPlayer?.prepareNavigation?.(),true);
})();
(()=>{
'use strict';
const root=document.documentElement,content=document.querySelector('[data-kovcheg-page-content]');
if(!content)return;
const base=String(window.KOVCHEG?.baseUrl||'').replace(/\/$/,'');
const basePath=(()=>{try{return new URL(base||'/',location.href).pathname.replace(/\/$/,'');}catch{return '';}})();
let controller=null,navigationId=0,busyTimer=0;
const cache=new Map(),prefetching=new Map();
function localPath(url){let path=url.pathname;if(basePath&&basePath!=='/'&&(path===basePath||path.startsWith(basePath+'/')))path=path.slice(basePath.length)||'/';return path||'/';}
function excluded(url,link=null){
 const path=localPath(url);
 if(url.origin!==location.origin)return true;
 if(link&&(link.target||link.hasAttribute('download')||link.closest('[data-no-soft-nav],[data-chat-item],[data-lightbox-src],[data-modal],[data-modal-open]')))return true;
 if(url.hash&&url.pathname===location.pathname&&url.search===location.search)return true;
 if(/\.(?:zip|rar|7z|pdf|docx?|xlsx?|pptx?|mp3|m4a|aac|ogg|opus|wav|flac|mp4|webm|avi|mov|mkv|jpg|jpeg|png|webp|gif|svg)(?:$|\?)/i.test(url.href))return true;
 if(/^\/(?:login(?:\/|$)|logout(?:\/|$)|register(?:\/|$)|install\.php|webdav\.php|brand(?:\/|$)|module\/[^/]+\/assets\/|assets\/|storage\/|robots\.txt|sitemap\.xml)/.test(path))return true;
 if(/\/(?:download|stream|preview)(?:\/|$)/.test(path)||/^\/story\/\d+\/media/.test(path)||/^\/avatar(?:\/|$)/.test(path))return true;
 return false;
}
function normalizeTitle(title){const site=document.querySelector('.brand-site-name-170')?.textContent?.trim()||'KOVCHEG CMS';const clean=String(title||'').trim();return clean.includes(' — ')?clean:`${clean||site} — ${site}`;}
async function parseResponse(response){
 const type=String(response.headers.get('content-type')||'');
 if(type.includes('application/json')){const data=await response.json().catch(()=>null);if(!data?.ok||typeof data.html!=='string')throw new Error(data?.error||'Сервер не вернул страницу.');return data;}
 const html=await response.text(),doc=new DOMParser().parseFromString(html,'text/html'),next=doc.querySelector('[data-kovcheg-page-content]');
 if(!next)throw new Error('Сервер не вернул содержимое страницы.');
 return {ok:true,html:next.innerHTML,title:doc.title,url:response.url};
}
async function requestPage(url,signal=null){
 const key=url.href,hit=cache.get(key);if(hit&&Date.now()-hit.time<120000)return hit.data;
 if(prefetching.has(key))return prefetching.get(key);
 const task=fetch(key,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json,text/html;q=0.9','X-Kovcheg-Soft-Navigation':'1'},signal}).then(async response=>{if(!response.ok)throw new Error(`HTTP ${response.status}`);const data=await parseResponse(response);cache.set(key,{time:Date.now(),data});while(cache.size>40)cache.delete(cache.keys().next().value);return data;}).finally(()=>prefetching.delete(key));
 prefetching.set(key,task);return task;
}
function prepareFragment(html){const template=document.createElement('template');template.innerHTML=html;const state=template.content.querySelector('[data-weather-state]');if(state){try{window.KOVCHEG_WEATHER_PAGE=JSON.parse(state.textContent||'{}');}catch{window.KOVCHEG_WEATHER_PAGE={};}}return template.content;}
function updateDocument(data,url,push,scroll){
 document.dispatchEvent(new CustomEvent('kovcheg:pagebeforechange',{detail:{url:url.href}}));
 content.replaceChildren(prepareFragment(data.html));
 document.title=normalizeTitle(data.title||document.title);
 if(push){history.replaceState({...history.state,kovchegSoft:true,scrollY:window.scrollY},'',location.href);history.pushState({kovchegSoft:true,scrollY:0},'',url.href);}
 document.body.classList.remove('mobile-messenger-page');
 document.dispatchEvent(new CustomEvent('kovcheg:pagechange',{detail:{url:url.href,path:localPath(url)}}));
 if(scroll!==false)requestAnimationFrame(()=>scrollTo({top:Number(scroll)||0,behavior:'auto'}));
}
function showBusy(){clearTimeout(busyTimer);busyTimer=setTimeout(()=>root.classList.add('kovcheg-soft-navigating'),90);}
function hideBusy(){clearTimeout(busyTimer);root.classList.remove('kovcheg-soft-navigating');}
async function navigate(value,{push=true,scroll=0}={}){
 const url=new URL(value,location.href);if(excluded(url)){location.href=url.href;return false;}
 const id=++navigationId;controller?.abort();controller=new AbortController();showBusy();window.KovchegPlayer?.prepareNavigation?.();const timeout=setTimeout(()=>controller?.abort('navigation-timeout'),12000);
 try{const data=await requestPage(url,controller.signal);if(id!==navigationId)return false;updateDocument(data,url,push,scroll);return true;}
 catch(error){if(error.name!=='AbortError'){console.error('KOVCHEG navigation failed',error);window.KovchegShowToast?.(`Не удалось открыть страницу: ${error.message}`,{type:'error'});location.assign(url.href);}return false;}
 finally{clearTimeout(timeout);if(id===navigationId)hideBusy();}
}
function linkFromEvent(event){const link=event.target.closest?.('a[href]');if(!link||event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return null;try{const url=new URL(link.href,location.href);return excluded(url,link)?null:{link,url};}catch{return null;}}
document.addEventListener('click',event=>{const target=linkFromEvent(event);if(!target)return;event.preventDefault();navigate(target.url,{push:true,scroll:0});},false);
window.addEventListener('popstate',event=>{navigate(location.href,{push:false,scroll:Number(event.state?.scrollY||0)});});
/* 2.3.15: ordinary links use native browser navigation. This prevents a stale SPA controller from blocking menus after refresh. */
function prefetchLink(link){if(!link)return;try{const url=new URL(link.href,location.href);if(excluded(url,link)||cache.has(url.href)||prefetching.has(url.href))return;requestPage(url).catch(()=>{});}catch{}}
/* 2.2.9: navigation is loaded only after an explicit click. Hover/focus prefetch overloaded PHP/MySQL in the admin panel. */
/* 2.3.15: native navigation is authoritative. No popstate fetch and no stale SPA state after refresh. */
window.KovchegNavigation={
 navigate:value=>{const url=new URL(value,location.href);window.KovchegPlayer?.prepareNavigation?.();location.assign(url.href);return Promise.resolve(true);},
 reload:()=>{location.reload();return Promise.resolve(true);},
 refresh:()=>{location.reload();return Promise.resolve(true);},
 clearCache:()=>cache.clear(),
 prefetch:()=>Promise.resolve(null)
};
})();


/* KOVCHEG CMS 3.0. */

/* KOVCHEG CMS 3.0. */
(()=>{
 'use strict';
 const q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
 function returnToChatList(){
  const messenger=q('.messenger');if(!messenger)return;
  messenger.dataset.chatId='0';
  qa('[data-chat-item]',messenger).forEach(item=>item.classList.remove('active'));
  document.body.classList.remove('mobile-chat-active');
  document.documentElement.classList.remove('mobile-chat-active');
  const list=q('.chat-list',messenger),shell=q('[data-conversation-shell]',messenger);
  if(list){list.hidden=false;list.style.removeProperty('display');}
  if(shell){shell.style.removeProperty('display');}
  const target=`${String(window.KOVCHEG?.baseUrl||'').replace(/\/$/,'')}/messages`;
  if(location.pathname.replace(/\/$/,'')!==new URL(target,location.href).pathname.replace(/\/$/,''))history.pushState({chatId:0},'',target);
  else history.replaceState({chatId:0},'',target);
  document.title=`Сообщения — KOVCHEG CMS`;
  requestAnimationFrame(()=>q('[data-global-search]',list)?.focus({preventScroll:true}));
 }
 document.addEventListener('click',event=>{
  const back=event.target.closest?.('[data-mobile-chat-back]');if(!back)return;
  event.preventDefault();event.stopPropagation();event.stopImmediatePropagation();returnToChatList();
 },true);
 window.KovchegMobileChatBack=returnToChatList;

 const CATS={
  recent:['😀','😂','🤣','😍','🥰','😘','😎','🤩','🥳','👍','👏','🙏','❤️','🔥','🎉','💯','✅','🤔','😭','😡'],
  faces:['😀','😃','😄','😁','😆','😅','😂','🤣','🥲','☺️','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🥸','🤩','🥳','🙂‍↕️','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😮‍💨','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🫣','🤭','🫢','🫡','🤫','🫠','🤥','😶','🫥','😐','🫤','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','😵‍💫','🤐','🥴','🤢','🤮','🤧','😷','🤒','🤕','🤑','🤠','😈','👿','👹','👺','🤡','💩','👻','💀','☠️','👽','👾','🤖'],
  gestures:['👋','🤚','🖐️','✋','🖖','🫱','🫲','🫳','🫴','👌','🤌','🤏','✌️','🤞','🫰','🤟','🤘','🤙','👈','👉','👆','👇','☝️','🫵','👍','👎','✊','👊','🤛','🤜','👏','🙌','🫶','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦾','🦿','🦵','🦶','👂','🦻','👃','🧠','🫀','🫁','🦷','🦴','👀','👁️','👅','👄','🫦'],
  people:['👶','🧒','👦','👧','🧑','👱','👨','🧔','👩','🧓','👴','👵','🙍','🙎','🙅','🙆','💁','🙋','🧏','🙇','🤦','🤷','👮','👷','💂','🕵️','👩‍⚕️','👨‍🌾','👩‍🍳','👨‍🎓','👩‍🎤','👨‍🏫','👩‍🏭','👨‍💻','👩‍💼','👨‍🔧','👩‍🔬','👨‍🎨','👩‍🚒','👨‍✈️','👩‍🚀','👨‍⚖️','🦸','🦹','🧙','🧚','🧛','🧜','🧝','🧞','🧟','💆','💇','🚶','🧍','🧎','🏃','💃','🕺','🕴️','👯','🧖','🧗','🤺','🏇','⛷️','🏂','🏌️','🏄','🚣','🏊','⛹️','🏋️','🚴','🤸','🤼','🤽','🤾','🤹','🧘','🛀','🛌'],
  hearts:['❤️','🩷','🧡','💛','💚','💙','🩵','💜','🤎','🖤','🩶','🤍','💔','❤️‍🔥','❤️‍🩹','❣️','💕','💞','💓','💗','💖','💘','💝','💟','💌','💋','🌹','🥀','💐','🌸','🌺','🌷','🪻','🌻'],
  animals:['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐻‍❄️','🐨','🐯','🦁','🐮','🐷','🐽','🐸','🐵','🙈','🙉','🙊','🐒','🐔','🐧','🐦','🐤','🐣','🐥','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🪲','🐛','🦋','🐌','🐞','🐜','🪰','🪱','🦟','🦗','🕷️','🦂','🐢','🐍','🦎','🐙','🦑','🦀','🦞','🦐','🐠','🐟','🐡','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🫏','🦬','🐘','🦛','🦏','🐪','🐫','🦒','🦘','🦬','🐃','🐂','🐄','🐎','🐖','🐏','🐑','🦙','🐐','🦌','🫎','🐕','🐩','🦮','🐕‍🦺','🐈','🐈‍⬛','🪿','🦃','🦚','🦜','🪽','🐇','🦝','🦨','🦡','🦫','🦦','🦥','🐁','🐀','🐿️','🦔'],
  food:['🍏','🍎','🍐','🍊','🍋','🍋‍🟩','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🫛','🥬','🥒','🌶️','🫑','🌽','🥕','🫒','🧄','🧅','🥔','🍠','🫘','🥐','🥯','🍞','🥖','🥨','🧀','🥚','🍳','🧈','🥞','🧇','🥓','🥩','🍗','🍖','🌭','🍔','🍟','🍕','🫓','🥪','🥙','🧆','🌮','🌯','🫔','🥗','🥘','🫕','🥫','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🦪','🍤','🍙','🍚','🍘','🍥','🥠','🥮','🍢','🍡','🍧','🍨','🍦','🥧','🧁','🍰','🎂','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','🥜','🍯','🥛','🍼','🫖','☕','🍵','🧃','🥤','🧋','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🧉','🍾'],
  activities:['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🪀','🏓','🏸','🏒','🏑','🥍','🏏','🪃','🥅','⛳','🪁','🏹','🎣','🤿','🥊','🥋','🎽','🛹','🛼','🛷','⛸️','🥌','🎿','⛷️','🏂','🪂','🏋️','🤼','🤸','⛹️','🤺','🤾','🏌️','🏇','🧘','🎯','🪩','🎮','🕹️','🎰','🎲','🧩','♟️','🎭','🎨','🧵','🪡','🧶','🎼','🎵','🎶','🎙️','🎤','🎧','🎷','🪗','🎸','🎹','🎺','🎻','🪕','🥁','🪘','🪇'],
  travel:['🚗','🚕','🚙','🚌','🚎','🏎️','🚓','🚑','🚒','🚐','🛻','🚚','🚛','🚜','🏍️','🛵','🚲','🛴','🛹','🛼','🚨','🚔','🚍','🚘','🚖','🚡','🚠','🚟','🚃','🚋','🚞','🚝','🚄','🚅','🚈','🚂','🚆','🚇','🚊','🚉','✈️','🛫','🛬','🛩️','💺','🛰️','🚀','🛸','🚁','🛶','⛵','🚤','🛥️','🛳️','⛴️','🚢','⚓','🛟','⛽','🚧','🚦','🚥','🗺️','🗿','🗽','🗼','🏰','🏯','🏟️','🎡','🎢','🎠','⛲','⛺','🌁','🌃','🏙️','🌄','🌅','🌆','🌇','🌉','♨️','🎑','🏞️','🏖️','🏝️','🏜️','🌋','⛰️','🏕️'],
  objects:['⌚','📱','📲','💻','⌨️','🖥️','🖨️','🖱️','🖲️','💽','💾','💿','📀','🧮','🎥','🎞️','📽️','🎬','📺','📷','📸','📹','📼','🔍','🔎','🕯️','💡','🔦','🏮','🪔','📔','📕','📖','📗','📘','📙','📚','📓','📒','📃','📜','📄','📰','🗞️','📑','🔖','🏷️','💰','🪙','💴','💵','💶','💷','💸','💳','🧾','✉️','📧','📨','📩','📤','📥','📦','📫','📪','📬','📭','📮','🗳️','✏️','✒️','🖋️','🖊️','🖌️','🖍️','📝','💼','📁','📂','🗂️','📅','📆','🗒️','🗓️','📇','📈','📉','📊','📋','📌','📍','📎','🖇️','📏','📐','✂️','🗃️','🗄️','🗑️','🔒','🔓','🔏','🔐','🔑','🗝️','🔨','🪓','⛏️','⚒️','🛠️','🗡️','⚔️','🛡️','🔧','🪛','🔩','⚙️','🗜️','⚖️','🔗','⛓️','🧰','🧲','🪜','🧪','🧫','🧬','🔬','🔭','📡','💉','🩸','💊','🩹','🩺','🚪','🪞','🪟','🛏️','🛋️','🪑','🚽','🪠','🚿','🛁','🧴','🧷','🧹','🧺','🧻','🪣','🧼','🫧','🪥','🧽'],
  symbols:['❤️','💛','💚','💙','💜','🖤','🤍','💯','💢','💥','💫','💦','💨','🕳️','💣','💬','👁️‍🗨️','🗨️','🗯️','💭','💤','✅','❌','⭕','🚫','❗','❕','❓','❔','‼️','⁉️','⚠️','♻️','⚜️','🔱','📛','🔰','⬆️','↗️','➡️','↘️','⬇️','↙️','⬅️','↖️','↕️','↔️','↩️','↪️','⤴️','⤵️','🔃','🔄','🔙','🔚','🔛','🔜','🔝','🛐','⚛️','🕉️','✡️','☸️','☯️','✝️','☦️','☪️','☮️','🕎','🔯','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓','⛎','▶️','⏩','⏭️','⏯️','◀️','⏪','⏮️','🔼','⏫','🔽','⏬','⏸️','⏹️','⏺️','⏏️','🎦','🔅','🔆','📶','🛜','📳','📴','♀️','♂️','⚧️','✖️','➕','➖','➗','🟰','♾️','‼️','™️','©️','®️','〰️','➰','➿','✔️','☑️','🔘','🔴','🟠','🟡','🟢','🔵','🟣','🟤','⚫','⚪','🟥','🟧','🟨','🟩','🟦','🟪','🟫','⬛','⬜'],
  flags:['🏁','🚩','🎌','🏴','🏳️','🏳️‍🌈','🏳️‍⚧️','🏴‍☠️','🇷🇺','🇫🇷','🇮🇱','🇰🇿','🇺🇸','🇬🇧','🇩🇪','🇮🇹','🇪🇸','🇵🇹','🇨🇳','🇯🇵','🇰🇷','🇮🇳','🇧🇷','🇦🇷','🇨🇦','🇦🇺','🇳🇿','🇹🇷','🇬🇷','🇨🇾','🇬🇪','🇦🇲','🇦🇿','🇺🇦','🇧🇾','🇲🇩','🇷🇸','🇧🇬','🇵🇱','🇨🇿','🇸🇰','🇭🇺','🇷🇴','🇳🇱','🇧🇪','🇨🇭','🇦🇹','🇸🇪','🇳🇴','🇫🇮','🇩🇰','🇮🇸','🇮🇪','🇲🇽','🇨🇺','🇿🇦','🇪🇬','🇲🇦','🇦🇪','🇸🇦','🇮🇷','🇮🇶','🇸🇾','🇱🇧','🇯🇴']
 };
 const ICONS={recent:'🕘',faces:'😀',gestures:'👍',people:'🧑',hearts:'❤️',animals:'🐱',food:'🍕',activities:'⚽',travel:'🚗',objects:'💡',symbols:'✅',flags:'🏳️'};
 const recentKey='kovcheg-recent-emoji-v2';
 const recent=()=>{try{const list=JSON.parse(localStorage.getItem(recentKey)||'[]');return Array.isArray(list)&&list.length?list:CATS.recent}catch{return CATS.recent}};
 const remember=emoji=>{const list=[emoji,...recent().filter(x=>x!==emoji)].slice(0,30);try{localStorage.setItem(recentKey,JSON.stringify(list))}catch{};CATS.recent=list};
 function initPicker(p){
  if(p.dataset.emoji233==='1')return;p.dataset.emoji233='1';CATS.recent=recent();let current='recent';
  p.innerHTML='<div class="emoji-picker-modern"><div class="emoji-picker-toolbar" role="tablist"></div><div class="emoji-grid-modern"></div></div>';
  const toolbar=q('.emoji-picker-toolbar',p),grid=q('.emoji-grid-modern',p);
  const render=()=>{const list=current==='recent'?recent():CATS[current];grid.innerHTML=list.map(emoji=>`<button type="button" data-emoji233="${emoji}" aria-label="${emoji}">${emoji}</button>`).join('')};
  Object.keys(CATS).forEach(key=>{const button=document.createElement('button');button.type='button';button.textContent=ICONS[key];button.title=key;button.classList.toggle('active',key===current);button.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();current=key;qa('button',toolbar).forEach(x=>x.classList.remove('active'));button.classList.add('active');render()});toolbar.append(button)});
  grid.addEventListener('click',event=>{const button=event.target.closest('[data-emoji233]');if(!button)return;event.preventDefault();event.stopPropagation();const emoji=button.dataset.emoji233,field=p.closest('form')?.querySelector('textarea[name="body"],textarea,input[type="text"]');if(field){const start=field.selectionStart??field.value.length,end=field.selectionEnd??start;field.setRangeText(emoji,start,end,'end');field.dispatchEvent(new Event('input',{bubbles:true}));field.focus()}remember(emoji);p.hidden=true});
  render();
 }
 function scan(){qa('.emoji-picker').forEach(initPicker);qa('.mobile-user-menu-button>.avatar-xs').forEach(a=>{a.style.display='grid';a.removeAttribute('hidden')})}
 document.addEventListener('DOMContentLoaded',scan);document.addEventListener('kovcheg:pagechange',scan);new MutationObserver(scan).observe(document.documentElement,{subtree:true,childList:true});scan();
})();

/* KOVCHEG CMS 3.0. */
(()=>{
 'use strict';
 const K=window.KOVCHEG||{};
 const q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
 const isMobile=()=>matchMedia('(max-width:900px)').matches;
 const sameClass=(el,name,on)=>{if(!el)return;if(el.classList.contains(name)!==Boolean(on))el.classList.toggle(name,Boolean(on));};
 const setHidden=(el,on)=>{if(!el)return;on=Boolean(on);if(el.hidden!==on)el.hidden=on;const aria=on?'true':'false';if(el.getAttribute('aria-hidden')!==aria)el.setAttribute('aria-hidden',aria);};
 let uiFrame=0;
 function scheduleUi(){cancelAnimationFrame(uiFrame);uiFrame=requestAnimationFrame(syncUi);}
 function currentConversationActive(){
  const messenger=q('.messenger');if(!messenger||!isMobile())return false;
  const chatId=Number(messenger.dataset.chatId||0);
  const conversation=q('[data-conversation]',messenger);
  const hasComposer=Boolean(conversation&&q('#composer,.read-only-composer',conversation));
  return chatId>0||Boolean(hasComposer&&/\/messages\/(?:@|chat-)/.test(location.pathname));
 }
 let observedMessenger=null,messengerObserver=null;
 function bindMessengerObserver(){
  const messenger=q('.messenger');
  if(messenger===observedMessenger)return;
  messengerObserver?.disconnect();observedMessenger=messenger;
  if(messenger){messengerObserver=new MutationObserver(scheduleUi);messengerObserver.observe(messenger,{attributes:true,attributeFilter:['data-chat-id'],childList:true,subtree:false});}
 }
 function syncUi(){
  bindMessengerObserver();
  const messenger=q('.messenger'),active=currentConversationActive();
  sameClass(document.body,'mobile-messenger-page',Boolean(isMobile()&&messenger));
  sameClass(document.body,'mobile-chat-active',active);sameClass(document.documentElement,'mobile-chat-active',active);
  qa('.mobile-bottom-nav,[data-mobile-bottom-nav]').forEach(nav=>{
   setHidden(nav,active);
   const display=active?'none':'';if(nav.style.display!==display)nav.style.display=display;
   const pointer=active?'none':'auto';if(nav.style.pointerEvents!==pointer)nav.style.pointerEvents=pointer;
  });
  syncPlayerIdle();bindPlayerTitleNodes();paintPlayerRanges();
 }
 document.addEventListener('click',event=>{
  const item=event.target.closest?.('[data-chat-item]');
  if(item&&isMobile()){
   const messenger=item.closest('.messenger');if(messenger)messenger.dataset.chatId=String(Number(item.dataset.chatItem||1));scheduleUi();
  }
  if(event.target.closest?.('[data-mobile-chat-back]')){
   const messenger=q('.messenger');if(messenger)messenger.dataset.chatId='0';scheduleUi();
  }
 },true);
 document.addEventListener('kovcheg:pagechange',scheduleUi);addEventListener('popstate',scheduleUi);addEventListener('resize',scheduleUi,{passive:true});

 /* Account menu is controlled by the unified 2.3.10 delegate below. */

 /* Title marquee is observed per title only and never rebuilds an unchanged title. */
 const emptyTitle=value=>!String(value||'').trim()||/^(музыка не выбрана|трек не выбран)$/i.test(String(value||'').trim());
 function renderTitle(el){
  if(!el||el.dataset.titleRendering==='1')return;
  const strip=el.querySelector('.track-title-marquee');
  const raw=String(strip?.dataset.rawTitle||el.dataset.rawTitle||el.textContent||'').replace(/\s+/g,' ').trim();
  if(emptyTitle(raw)){if(el.textContent!=='')el.textContent='';el.dataset.rawTitle='';el.classList.remove('track-title-long','track-title-short');return;}
  const longTitle=raw.split(' ').filter(Boolean).length>2;
  if(longTitle&&strip?.dataset.rawTitle===raw&&el.dataset.renderedTitle===raw)return;
  if(!longTitle&&!strip&&el.dataset.renderedTitle===raw&&el.textContent===raw)return;
  el.dataset.titleRendering='1';el.dataset.rawTitle=raw;el.dataset.renderedTitle=raw;
  if(!longTitle){el.classList.add('track-title-short');el.classList.remove('track-title-long');if(el.textContent!==raw)el.textContent=raw;}
  else{
   el.classList.remove('track-title-short');el.classList.add('track-title-long');el.style.setProperty('--track-marquee-duration',`${Math.max(8,Math.min(20,raw.length*.24))}s`);
   el.replaceChildren();const marquee=document.createElement('span');marquee.className='track-title-marquee';marquee.dataset.rawTitle=raw;
   const first=document.createElement('span'),second=document.createElement('span');first.textContent=raw;second.textContent=raw;marquee.append(first,second);el.append(marquee);
  }
  delete el.dataset.titleRendering;
 }
 function bindPlayerTitleNodes(){qa('[data-player-title]').forEach(el=>{
  renderTitle(el);if(el.dataset.titleObserver238==='1')return;el.dataset.titleObserver238='1';
  const observer=new MutationObserver(()=>{if(el.dataset.titleRendering!=='1')requestAnimationFrame(()=>renderTitle(el));});observer.observe(el,{childList:true,characterData:true,subtree:true});
 });}

 /* Range painting is event driven instead of rewriting every range every 350 ms. */
 function paint(range){if(!(range instanceof HTMLInputElement)||range.type!=='range')return;const min=Number(range.min||0),max=Number(range.max||100),value=Number(range.value||0),pct=max>min?Math.max(0,Math.min(100,(value-min)/(max-min)*100)):0;const next=pct+'%';if(range.style.getPropertyValue('--range-pct')!==next)range.style.setProperty('--range-pct',next);if(range.style.getPropertyValue('--range-progress')!==next)range.style.setProperty('--range-progress',next);}
 function paintPlayerRanges(){qa('input[type="range"]').forEach(paint);}
 document.addEventListener('input',event=>paint(event.target));document.addEventListener('change',event=>paint(event.target));
 document.addEventListener('click',event=>{const button=event.target.closest('[data-player-play],[data-player-mirror-play],[data-player-track-play]');if(!button)return;const audio=q('[data-player-audio]');if(audio&&(audio.ended||(Number.isFinite(audio.duration)&&audio.duration>0&&audio.currentTime>=audio.duration-.25))){try{audio.currentTime=0;}catch(_){}}},true);

 let boundAudio=null;
 function syncPlayerIdle(){
  const player=q('[data-global-player]'),audio=q('[data-player-audio]',player||document);if(!player||!audio)return;
  const idle=!(audio.getAttribute('src')||audio.currentSrc);sameClass(player,'is-idle',idle);sameClass(player,'has-no-track',idle);
  if(idle){const title=q('[data-player-title]',player),author=q('[data-player-author]',player),progress=q('[data-player-progress]',player),clock=q('[data-player-time]',player);if(title&&title.textContent)title.textContent='';if(author&&author.textContent)author.textContent='';if(progress&&progress.value!=='0')progress.value='0';if(clock&&clock.textContent)clock.textContent='';}
  if(audio!==boundAudio){boundAudio=audio;['timeupdate','loadedmetadata','durationchange','volumechange','play','pause','ended','emptied'].forEach(name=>audio.addEventListener(name,()=>{paintPlayerRanges();syncPlayerIdle();},{passive:true}));}
 }

 /* Automatic module installer: boot only on load/page change, never on every DOM mutation. */
 const toast=(text,type='success')=>window.KovchegShowToast?.(text,{type})||console.log(text);
 async function sendPackage(form,file,row){
  const fd=new FormData();fd.append('_csrf',K.csrf||'');fd.append('modules[]',file,file.name);row.classList.add('is-running');q('small',row).textContent='Проверка пакета…';
  const response=await fetch(form.action,{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-Token':K.csrf||''}});const data=await response.json().catch(()=>({ok:false,error:`Ошибка ${response.status}`}));
  if(!response.ok&&!Array.isArray(data.results))throw new Error(data.error||`Ошибка ${response.status}`);const item=(data.results||[])[0]||{ok:false,file:file.name,message:data.error||'Не удалось установить пакет.'};
  row.classList.remove('is-running');row.classList.add(item.ok?'is-done':'is-error');q('small',row).textContent=item.message||'';
  if(item.ok&&data.apply_required&&data.apply_url){q('small',row).textContent='Применяем обновление…';try{const apply=await fetch(data.apply_url,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Kovcheg-Update-Apply':'1','X-Requested-With':'XMLHttpRequest'}});const body=await apply.json().catch(()=>({ok:apply.ok,message:'Обновление применено.'}));if(!apply.ok||body.ok===false)throw new Error(body.error||`Ошибка ${apply.status}`);q('small',row).textContent=body.message||'Обновление применено.';}catch(_){q('small',row).textContent='Пакет установлен. Применение завершится при следующем переходе.';}}
  return item;
 }
 function bootModuleInstaller(){
  const form=q('[data-module-installer]');if(!form||form.dataset.autoBound==='238')return;form.dataset.autoBound='238';const input=q('[data-module-input]',form),surface=q('[data-module-choose]',form),queue=q('[data-module-install-queue]',form),selected=q('[data-selected-files]',form);if(!input||!surface||!queue)return;let running=false;
  const process=async files=>{if(running)return;const list=[...files].filter(file=>file.name.toLowerCase().endsWith('.zip'));if(!list.length){toast('Выберите ZIP-пакет.','error');return;}running=true;queue.hidden=false;queue.replaceChildren();if(selected)selected.textContent='';for(const file of list){const row=document.createElement('div');row.className='module-install-row';const name=document.createElement('b'),status=document.createElement('small'),line=document.createElement('i');name.textContent=file.name;status.textContent='Ожидает проверки';row.append(name,status,line);queue.append(row);try{const result=await sendPackage(form,file,row);toast(result.message||'Пакет установлен.',result.ok?'success':'error');}catch(error){row.classList.remove('is-running');row.classList.add('is-error');status.textContent=error.message;toast(error.message,'error');}}input.value='';running=false;window.KovchegNavigation?.clearCache?.();};
  surface.addEventListener('click',()=>input.click());input.addEventListener('change',()=>process(input.files||[]));['dragenter','dragover'].forEach(name=>surface.addEventListener(name,event=>{event.preventDefault();surface.classList.add('dragging');}));['dragleave','drop'].forEach(name=>surface.addEventListener(name,event=>{event.preventDefault();surface.classList.remove('dragging');}));surface.addEventListener('drop',event=>process(event.dataTransfer?.files||[]));form.addEventListener('submit',event=>{event.preventDefault();process(input.files||[]);});
 }

 document.documentElement.classList.add('kovcheg-layout-stable');
 document.addEventListener('DOMContentLoaded',()=>{bootModuleInstaller();scheduleUi();},{once:true});document.addEventListener('kovcheg:pagechange',()=>{bootModuleInstaller();scheduleUi();});
 syncPlayerIdle();bindPlayerTitleNodes();paintPlayerRanges();scheduleUi();
})();






/* Message scrolling is bound inside the messenger controller. */



/* KOVCHEG CMS 3.0. */
(()=>{
 'use strict';
 if(window.__KOVCHEG_SHELL_255__)return;window.__KOVCHEG_SHELL_255__=true;
 const K=window.KOVCHEG||{},q=(s,r=document)=>r.querySelector(s),qa=(s,r=document)=>[...r.querySelectorAll(s)];
 const mobile=()=>matchMedia('(max-width:760px)').matches;
 let frame=0,drawerTimer=0;
 const closeMenus=except=>qa('[data-kov-header-menu]').forEach(menu=>{if(menu!==except)menu.open=false});
 document.addEventListener('toggle',event=>{const menu=event.target.closest?.('[data-kov-header-menu]');if(menu?.open)closeMenus(menu);},true);
 document.addEventListener('click',event=>{if(!event.target.closest?.('[data-kov-header-menu]'))closeMenus(null);},true);
 document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeMenus(null);closeDrawer();}});
 document.addEventListener('kovcheg:pagebeforechange',()=>{closeMenus(null);closeDrawer();});
 const closeDrawer=()=>{const drawer=q('[data-mobile-side-drawer]'),overlay=q('[data-mobile-drawer-overlay]');drawer?.classList.remove('open');drawer?.setAttribute('aria-hidden','true');if(overlay){overlay.classList.remove('visible');clearTimeout(drawerTimer);drawerTimer=setTimeout(()=>{if(!overlay.classList.contains('visible'))overlay.hidden=true},190)}document.documentElement.classList.remove('mobile-drawer-open');document.body?.classList.remove('mobile-drawer-open')};
 const openDrawer=()=>{if(!mobile())return;const drawer=q('[data-mobile-side-drawer]'),overlay=q('[data-mobile-drawer-overlay]');if(!drawer||!overlay)return;clearTimeout(drawerTimer);overlay.hidden=false;requestAnimationFrame(()=>{overlay.classList.add('visible');drawer.classList.add('open')});drawer.setAttribute('aria-hidden','false');document.documentElement.classList.add('mobile-drawer-open');document.body.classList.add('mobile-drawer-open')};
 document.addEventListener('click',event=>{if(event.target.closest?.('[data-mobile-drawer-open]')){event.preventDefault();openDrawer();return}if(event.target.closest?.('[data-mobile-drawer-close],[data-mobile-drawer-overlay],.mobile-side-drawer a'))closeDrawer()},true);
 const syncViewport=()=>{const vv=visualViewport,height=Math.max(280,Math.round(vv?.height||innerHeight));document.documentElement.style.setProperty('--kov-app-height',height+'px');document.documentElement.classList.toggle('mobile-keyboard-open',Boolean(mobile()&&vv&&innerHeight-height>120));};
 const syncShell=()=>{cancelAnimationFrame(frame);frame=requestAnimationFrame(()=>{syncViewport();const page=q('#kovcheg-page-content'),messenger=q('.messenger',page||document),admin=q('.admin-shell',page||document),profile=q('.vk-profile-page',page||document),feed=q('.feed-page',page||document);const active=Boolean(mobile()&&messenger&&Number(messenger.dataset.chatId||0)>0);document.body.classList.toggle('page-messenger',Boolean(messenger));document.body.classList.toggle('page-admin',Boolean(admin));document.body.classList.toggle('profile-page-active',Boolean(profile));document.body.classList.toggle('feed-page-active',Boolean(feed));document.body.classList.toggle('mobile-messenger-page',Boolean(mobile()&&messenger));document.body.classList.toggle('mobile-chat-active',active);document.documentElement.classList.toggle('mobile-chat-active',active);qa('[data-mobile-bottom-nav],.mobile-bottom-nav').forEach(nav=>{nav.hidden=active;nav.toggleAttribute('hidden',active);});});};
 document.addEventListener('click',event=>{const item=event.target.closest?.('[data-chat-item]');if(item&&mobile()){const messenger=item.closest('.messenger');if(messenger)messenger.dataset.chatId=String(Number(item.dataset.chatItem||1));setTimeout(syncShell,0)}if(event.target.closest?.('[data-mobile-chat-back],.mobile-back')){const messenger=q('.messenger');if(messenger)messenger.dataset.chatId='0';setTimeout(syncShell,0)}},true);
 document.addEventListener('DOMContentLoaded',syncShell,{once:true});document.addEventListener('kovcheg:pagechange',syncShell);addEventListener('resize',syncShell,{passive:true});addEventListener('orientationchange',()=>setTimeout(syncShell,100),{passive:true});addEventListener('popstate',syncShell,{passive:true});visualViewport?.addEventListener('resize',syncShell,{passive:true});visualViewport?.addEventListener('scroll',syncViewport,{passive:true});
 document.addEventListener('click',async event=>{const button=event.target.closest?.('[data-notifications-read-all]');if(!button)return;event.preventDefault();button.disabled=true;try{const fd=new FormData();fd.set('_csrf',K.csrf||'');fd.set('all','1');const response=await fetch(String(K.baseUrl||'').replace(/\/$/,'')+'/ajax/notifications/read',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body:fd});const data=await response.json().catch(()=>({ok:false}));if(!response.ok||data.ok===false)throw new Error(data.error||'Не удалось обновить оповещения.');qa('[data-notification-id].unread').forEach(note=>note.classList.remove('unread'));const count=q('[data-notification-bell-count]');if(count){count.hidden=true;count.textContent='0'}}catch(error){window.KovchegShowToast?.(error.message||'Не удалось обновить оповещения.',{type:'error'})}finally{button.disabled=false}},true);
 document.addEventListener('click',event=>{const note=event.target.closest?.('[data-notification-id]');if(!note)return;const fd=new FormData();fd.set('_csrf',K.csrf||'');fd.set('ids',String(note.dataset.notificationId||''));fetch(String(K.baseUrl||'').replace(/\/$/,'')+'/ajax/notifications/read',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body:fd}).catch(()=>{})},true);
 syncShell();
})();

}
