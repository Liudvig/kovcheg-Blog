const CACHE='kovcheg-assets-3.0-r1';
const STATIC_ASSETS=[
  './assets/css/kovcheg-core.css?v=3.0-r1',
  './assets/css/modern-upload.css?v=3.0-r1',
  './assets/css/template-polish.css?v=3.0-r1',
  './assets/css/layout-repair.css?v=3.0-r1',
  './assets/css/vk-structural-fix.css?v=3.0-r1',
  './assets/css/vk-header-clean.css?v=3.0-r1',
  './assets/css/vk-reference-layout.css?v=3.0-r1',
  './assets/css/vk-reference-fixes.css?v=3.0-r1',
  './assets/js/kovcheg-core.js?v=3.0-r1',
  './assets/js/post-submit-guard.js?v=3.0-r1',
  './assets/js/modern-upload.js?v=3.0-r1',
  './assets/js/layout-repair.js?v=3.0-r1',
  './assets/js/vk-structural-fix.js?v=3.0-r1',
  './assets/js/vk-profile-fixes.js?v=3.0-r1',
  './assets/js/social-templates.js?v=3.0-r1',
  './assets/js/social-template-fixes.js?v=3.0-r1',
  './assets/js/vk-media.js?v=3.0-r1',
  './assets/css/templates/default.css?v=3.0-r1',
  './assets/css/templates/vk.css?v=3.0-r1',
  './assets/css/templates/vk-fixes.css?v=3.0-r1',
  './assets/css/templates/x.css?v=3.0-r1',
  './assets/css/templates/x-fixes.css?v=3.0-r1',
  './assets/icons/icon.svg',
  './assets/icons/default-avatar.svg?v=2.2.7',
  './login',
  './register'
];
self.addEventListener('install',event=>event.waitUntil((async()=>{const cache=await caches.open(CACHE);await Promise.all(STATIC_ASSETS.map(asset=>cache.add(asset).catch(()=>null)));await self.skipWaiting();})()));
self.addEventListener('activate',event=>event.waitUntil((async()=>{const keys=await caches.keys();await Promise.all(keys.filter(key=>key!==CACHE).map(key=>caches.delete(key)));await self.clients.claim();})()));
self.addEventListener('fetch',event=>{const request=event.request;if(request.method!=='GET')return;const url=new URL(request.url);if(url.origin!==self.location.origin)return;if(request.mode==='navigate'){event.respondWith(fetch(request,{cache:'no-store'}).catch(()=>caches.match('./login')));return;}if(/\.(?:css|js|svg|png|webp|jpg|jpeg|gif|woff2?)(?:\?|$)/i.test(url.pathname)){event.respondWith((async()=>{const cached=await caches.match(request);const network=fetch(request).then(response=>{if(response&&response.ok){const copy=response.clone();caches.open(CACHE).then(cache=>cache.put(request,copy));}return response;}).catch(()=>null);return cached||await network||Response.error();})());}});
function chatIdentity(raw){try{const path=new URL(raw,self.location.href).pathname.replace(/\/$/,'');let m=path.match(/\/messages\/@([^/]+)/);if(m)return `user:${m[1].toLowerCase()}`;m=path.match(/\/messages\/chat-(\d+)/);if(m)return `chat:${m[1]}`;return '';}catch(_){return '';}}
async function visibleChatIsOpen(raw){const target=chatIdentity(raw);if(!target)return false;const windows=await clients.matchAll({type:'window',includeUncontrolled:true});return windows.some(client=>client.visibilityState==='visible'&&chatIdentity(client.url)===target);}
self.addEventListener('message',event=>{if(event.data?.type==='SKIP_WAITING'){self.skipWaiting();return;}if(event.data?.type!=='SHOW_NOTIFICATION')return;const p=event.data.payload||{};event.waitUntil((async()=>{if(await visibleChatIsOpen(p.url||'./messages'))return;await self.registration.showNotification(p.title||'KOVCHEG CMS',{body:p.body||'',icon:p.icon||'./assets/icons/icon.svg',badge:'./assets/icons/icon.svg',tag:p.tag||'kovcheg-notification',data:{url:p.url||'./messages'},renotify:true});})());});
self.addEventListener('push',event=>{event.waitUntil((async()=>{try{const response=await fetch('./ajax/push/pending',{credentials:'include',headers:{Accept:'application/json'},cache:'no-store'});const data=await response.json();const items=Array.isArray(data.notifications)?data.notifications:[];for(const item of items){if(await visibleChatIsOpen(item.url||'./messages'))continue;await self.registration.showNotification(item.title||'KOVCHEG CMS',{body:item.body||'',icon:item.icon||'./assets/icons/icon.svg',badge:'./assets/icons/icon.svg',tag:item.tag||`kovcheg-${item.id}`,renotify:true,data:{url:item.url||'./messages'}});}}catch(_){}})());});
self.addEventListener('notificationclick',event=>{event.notification.close();const url=event.notification.data?.url||'./messages';event.waitUntil(clients.matchAll({type:'window',includeUncontrolled:true}).then(list=>{for(const client of list){if('focus'in client){client.navigate(url);return client.focus();}}return clients.openWindow?clients.openWindow(url):undefined;}));});
