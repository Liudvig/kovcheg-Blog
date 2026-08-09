const CACHE='kovcheg-blog-3.9.0';
const STATIC_ASSETS=[
  './assets/css/kovcheg-shell.css?v=3.9.0-core-cleanup',
  './assets/icons/icon.svg',
  './assets/icons/default-avatar.svg',
  './manifest.webmanifest',
  './login'
];

self.addEventListener('install',event=>{
  event.waitUntil((async()=>{
    const cache=await caches.open(CACHE);
    await Promise.all(STATIC_ASSETS.map(asset=>cache.add(asset).catch(()=>null)));
    await self.skipWaiting();
  })());
});

self.addEventListener('activate',event=>{
  event.waitUntil((async()=>{
    const keys=await caches.keys();
    await Promise.all(keys.filter(key=>key!==CACHE).map(key=>caches.delete(key)));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch',event=>{
  const request=event.request;
  if(request.method!=='GET')return;
  const url=new URL(request.url);
  if(url.origin!==self.location.origin)return;

  if(request.mode==='navigate'){
    event.respondWith(fetch(request,{cache:'no-store'}).catch(async()=>{
      return await caches.match('./login') || Response.error();
    }));
    return;
  }

  if(/\.(?:css|js|svg|png|webp|jpg|jpeg|gif|woff2?)(?:\?|$)/i.test(url.pathname)){
    event.respondWith((async()=>{
      const cached=await caches.match(request);
      const network=fetch(request).then(response=>{
        if(response&&response.ok){
          const copy=response.clone();
          caches.open(CACHE).then(cache=>cache.put(request,copy));
        }
        return response;
      }).catch(()=>null);
      return cached || await network || Response.error();
    })());
  }
});

self.addEventListener('message',event=>{
  if(event.data?.type==='SKIP_WAITING')self.skipWaiting();
});
