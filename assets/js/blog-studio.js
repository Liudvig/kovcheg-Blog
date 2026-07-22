(() => {
  'use strict';

  const sidebar = document.querySelector('.studio-sidebar');
  const overlay = document.querySelector('[data-studio-overlay]');
  const setSidebar = (open) => {
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('is-open', open);
    overlay.hidden = !open;
  };
  document.querySelector('[data-studio-open]')?.addEventListener('click', () => setSidebar(true));
  document.querySelector('[data-studio-close]')?.addEventListener('click', () => setSidebar(false));
  overlay?.addEventListener('click', () => setSidebar(false));

  const editor = document.querySelector('[data-block-editor]');
  const jsonField = document.querySelector('[data-block-json]');
  const form = document.querySelector('[data-entry-form]');
  const toolbar = document.querySelector('[data-block-toolbar]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const uid = () => Math.random().toString(36).slice(2, 10);
  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'})[char]);
  const normalizeBlock = (block = {}) => ({id:String(block.id || uid()),type:String(block.type || 'paragraph'),data:block.data && typeof block.data === 'object' ? block.data : {}});

  let blocks = [];
  if (jsonField) {
    try { const parsed=JSON.parse(jsonField.value || '[]'); blocks=Array.isArray(parsed)?parsed.map(normalizeBlock):[]; }
    catch (_) { blocks=[]; }
  }
  if (!blocks.length && editor) blocks.push(normalizeBlock({type:'paragraph',data:{text:''}}));

  let patternLibrary = {};
  try { patternLibrary=JSON.parse(document.querySelector('[data-pattern-library]')?.value || '{}'); }
  catch (_) { patternLibrary={}; }

  const labels = {
    paragraph:'Текст',heading:'Заголовок',image:'Изображение',gallery:'Галерея',quote:'Цитата',list:'Список',columns:'Колонки',button:'Кнопка',video:'Видео',audio:'Аудио',code:'Код',separator:'Линия',spacer:'Отступ',hero:'Первый экран',notice:'Выделенный блок',stats:'Показатели',timeline:'Этапы',testimonial:'Отзыв',cards:'Карточки',contact:'Контакты',
  };

  const field = (name, value, placeholder='', type='text') => `<input type="${type}" data-block-field="${escapeHtml(name)}" value="${escapeHtml(value)}" placeholder="${escapeHtml(placeholder)}">`;
  const textarea = (name, value, placeholder='') => `<textarea data-block-field="${escapeHtml(name)}" placeholder="${escapeHtml(placeholder)}">${escapeHtml(value)}</textarea>`;
  const select = (name, value, options) => `<select data-block-field="${escapeHtml(name)}">${Object.entries(options).map(([key,label])=>`<option value="${escapeHtml(key)}" ${String(value)===String(key)?'selected':''}>${escapeHtml(label)}</option>`).join('')}</select>`;
  const rowsToText = (rows, keys) => Array.isArray(rows) ? rows.map((row)=>keys.map((key)=>String(row?.[key] ?? '')).join(' | ')).join('\n') : '';

  const header = (block,index) => `<header class="block-item__head"><span class="drag-handle" draggable="true" data-block-drag="${index}" title="Перетащить">⋮⋮</span><b>${escapeHtml(labels[block.type] || block.type)}</b><div class="block-controls"><button type="button" data-block-duplicate="${index}" title="Копировать">⧉</button><button type="button" data-block-up="${index}" title="Выше">↑</button><button type="button" data-block-down="${index}" title="Ниже">↓</button><button type="button" data-block-remove="${index}" title="Удалить">×</button></div></header>`;

  const blockMarkup = (block,index) => {
    const d=block.data || {}; let body='';
    if(block.type==='paragraph') body=textarea('text',d.text||'','Введите текст абзаца');
    else if(block.type==='heading') body=`<div class="form-grid">${field('text',d.text||'','Заголовок')}${select('level',d.level||2,{2:'H2',3:'H3',4:'H4'})}</div>`;
    else if(block.type==='image') body=`${field('url',d.url||'','/media/ID или https://')}${field('alt',d.alt||'','Описание изображения')}${field('caption',d.caption||'','Подпись')}`;
    else if(block.type==='gallery') body=`${textarea('itemsText',Array.isArray(d.items)?d.items.join('\n'):(d.itemsText||''),'Каждая ссылка на изображение с новой строки')}<div class="field compact"><label>Колонки</label>${select('columns',d.columns||3,{2:'2',3:'3',4:'4'})}</div>`;
    else if(block.type==='quote') body=`${textarea('text',d.text||'','Текст цитаты')}${field('caption',d.caption||'','Автор или источник')}`;
    else if(block.type==='code') body=`${field('language',d.language||'','Язык, например php')}${textarea('text',d.text||'','Код или технический фрагмент')}`;
    else if(block.type==='button') body=`<div class="form-grid">${field('text',d.text||'Подробнее','Текст кнопки')}${field('url',d.url||'','https:// или /page')}</div>${select('style',d.style||'primary',{primary:'Основная',outline:'Контурная',link:'Ссылка'})}`;
    else if(block.type==='list') body=`${textarea('itemsText',Array.isArray(d.items)?d.items.join('\n'):(d.itemsText||''),'Каждый пункт с новой строки')}<label class="check-row"><input type="checkbox" data-block-field="ordered" ${d.ordered?'checked':''}> Нумерованный список</label>`;
    else if(block.type==='columns') body=textarea('columnsText',Array.isArray(d.columns)?d.columns.join('\n---\n'):(d.columnsText||''),'Разделяйте колонки строкой ---');
    else if(block.type==='video') body=`${field('url',d.url||'','YouTube, Vimeo, Rutube или прямая ссылка')}${field('caption',d.caption||'','Подпись')}`;
    else if(block.type==='audio') body=`${field('url',d.url||'','Ссылка на MP3/OGG')}${field('title',d.title||'','Название')}${field('caption',d.caption||'','Исполнитель или подпись')}`;
    else if(block.type==='spacer') body=`<div class="field compact"><label>Высота, px</label>${field('size',d.size||64,'64','number')}</div>`;
    else if(block.type==='hero') body=`<div class="form-grid">${field('eyebrow',d.eyebrow||'','Надпись над заголовком')}${select('align',d.align||'left',{left:'Слева',center:'По центру'})}</div>${field('title',d.title||'','Главный заголовок')}${textarea('text',d.text||'','Описание')}${field('image_url',d.image_url||'','Фоновое изображение') }<div class="form-grid">${field('button_text',d.button_text||'','Текст кнопки')}${field('button_url',d.button_url||'','Ссылка кнопки')}</div>`;
    else if(block.type==='notice') body=`${field('title',d.title||'','Заголовок')}${textarea('text',d.text||'','Важная информация')}${select('tone',d.tone||'info',{info:'Информация',success:'Успех',warning:'Предупреждение',dark:'Тёмный'})}`;
    else if(block.type==='stats') body=`${textarea('itemsText',rowsToText(d.items,['value','label']),'Каждая строка: значение | подпись')}<small>Пример: 120 | завершённых проектов</small>`;
    else if(block.type==='timeline') body=`${textarea('itemsText',rowsToText(d.items,['title','text']),'Каждая строка: этап | описание')}<small>Пример: Подготовка | Обсуждаем задачу</small>`;
    else if(block.type==='testimonial') body=`${textarea('text',d.text||'','Текст отзыва')}${field('name',d.name||'','Имя')}${field('role',d.role||'','Должность или статус')}${field('avatar_url',d.avatar_url||'','Ссылка на фото')}`;
    else if(block.type==='cards') body=`${textarea('itemsText',rowsToText(d.items,['title','text','url']),'Каждая строка: заголовок | описание | ссылка')}<small>Ссылка необязательна.</small>`;
    else if(block.type==='contact') body=`${field('title',d.title||'Связаться','Заголовок')}${textarea('text',d.text||'','Описание')}${field('email',d.email||'','Email','email')}${field('phone',d.phone||'','Телефон')}<div class="form-grid">${field('button_text',d.button_text||'','Текст кнопки')}${field('button_url',d.button_url||'','Ссылка')}</div>`;
    else if(block.type==='separator') body='<p class="block-placeholder">Горизонтальный разделитель</p>';
    else body=textarea('text',d.text||'','Содержимое');
    return `<article class="block-item" data-block-index="${index}" data-block-type="${escapeHtml(block.type)}">${header(block,index)}<div class="block-item__body">${body}</div></article>`;
  };

  const parseRows = (value,keys,max=24) => String(value||'').split(/\r?\n/).map((line)=>line.trim()).filter(Boolean).slice(0,max).map((line)=>{const cells=line.split('|').map((v)=>v.trim());return Object.fromEntries(keys.map((key,i)=>[key,cells[i]||'']));});

  const serialize = () => {
    if(!editor||!jsonField)return;
    editor.querySelectorAll('[data-block-index]').forEach((node)=>{
      const index=Number(node.dataset.blockIndex);const block=blocks[index];if(!block)return;
      node.querySelectorAll('[data-block-field]').forEach((input)=>{const key=input.dataset.blockField;if(!key)return;block.data[key]=input.type==='checkbox'?input.checked:input.value;});
      if(block.type==='list'){block.data.items=String(block.data.itemsText||'').split(/\r?\n/).map((v)=>v.trim()).filter(Boolean);delete block.data.itemsText;}
      if(block.type==='columns'){block.data.columns=String(block.data.columnsText||'').split(/\r?\n---\r?\n/).map((v)=>v.trim()).filter(Boolean).slice(0,4);delete block.data.columnsText;}
      if(block.type==='gallery'){block.data.items=String(block.data.itemsText||'').split(/\r?\n/).map((v)=>v.trim()).filter(Boolean).slice(0,60);delete block.data.itemsText;}
      if(block.type==='stats'){block.data.items=parseRows(block.data.itemsText,['value','label'],12);delete block.data.itemsText;}
      if(block.type==='timeline'){block.data.items=parseRows(block.data.itemsText,['title','text'],20);delete block.data.itemsText;}
      if(block.type==='cards'){block.data.items=parseRows(block.data.itemsText,['title','text','url'],12);delete block.data.itemsText;}
    });
    jsonField.value=JSON.stringify(blocks);
  };

  const render = () => { if(!editor)return;editor.innerHTML=blocks.map(blockMarkup).join('');serialize(); };
  const defaults = {
    paragraph:{text:''},heading:{text:'',level:2},image:{url:'',alt:'',caption:''},gallery:{items:[],columns:3},quote:{text:'',caption:''},list:{items:[],ordered:false},columns:{columns:['','']},button:{text:'Подробнее',url:'',style:'primary'},video:{url:'',caption:''},audio:{url:'',title:'',caption:''},code:{text:'',language:''},separator:{},spacer:{size:64},hero:{eyebrow:'',title:'',text:'',button_text:'',button_url:'',image_url:'',align:'left'},notice:{title:'',text:'',tone:'info'},stats:{items:[{value:'100+',label:'проектов'}]},timeline:{items:[{title:'Этап 1',text:'Описание'}]},testimonial:{text:'',name:'',role:'',avatar_url:''},cards:{items:[{title:'Карточка',text:'Описание',url:''}]},contact:{title:'Связаться',text:'',email:'',phone:'',button_text:'',button_url:''},
  };

  toolbar?.addEventListener('click',(event)=>{const button=event.target.closest('[data-add-block]');if(!button)return;serialize();const type=button.dataset.addBlock||'paragraph';blocks.push(normalizeBlock({type,data:structuredClone(defaults[type]||{})}));render();editor.lastElementChild?.scrollIntoView({behavior:'smooth',block:'center'});markChanged();});

  editor?.addEventListener('input',()=>{serialize();markChanged();});
  editor?.addEventListener('change',()=>{serialize();markChanged();});
  editor?.addEventListener('click',(event)=>{
    const action=event.target.closest('[data-block-remove],[data-block-up],[data-block-down],[data-block-duplicate]');if(!action)return;serialize();
    const index=Number(action.dataset.blockRemove??action.dataset.blockUp??action.dataset.blockDown??action.dataset.blockDuplicate);if(!Number.isInteger(index)||!blocks[index])return;
    if(action.hasAttribute('data-block-remove'))blocks.splice(index,1);
    else if(action.hasAttribute('data-block-up')&&index>0)[blocks[index-1],blocks[index]]=[blocks[index],blocks[index-1]];
    else if(action.hasAttribute('data-block-down')&&index<blocks.length-1)[blocks[index+1],blocks[index]]=[blocks[index],blocks[index+1]];
    else if(action.hasAttribute('data-block-duplicate'))blocks.splice(index+1,0,normalizeBlock(structuredClone(blocks[index])));
    if(!blocks.length)blocks.push(normalizeBlock({type:'paragraph',data:{text:''}}));render();markChanged();
  });

  let draggedIndex=-1;
  editor?.addEventListener('dragstart',(event)=>{const handle=event.target.closest('[data-block-drag]');if(!handle)return;serialize();draggedIndex=Number(handle.dataset.blockDrag);event.dataTransfer.effectAllowed='move';});
  editor?.addEventListener('dragover',(event)=>{if(draggedIndex<0)return;event.preventDefault();event.dataTransfer.dropEffect='move';event.target.closest('[data-block-index]')?.classList.add('drag-over');});
  editor?.addEventListener('dragleave',(event)=>event.target.closest('[data-block-index]')?.classList.remove('drag-over'));
  editor?.addEventListener('drop',(event)=>{event.preventDefault();const target=event.target.closest('[data-block-index]');editor.querySelectorAll('.drag-over').forEach((node)=>node.classList.remove('drag-over'));if(!target||draggedIndex<0)return;const to=Number(target.dataset.blockIndex);const [moved]=blocks.splice(draggedIndex,1);blocks.splice(to,0,moved);draggedIndex=-1;render();markChanged();});
  editor?.addEventListener('dragend',()=>{draggedIndex=-1;editor.querySelectorAll('.drag-over').forEach((node)=>node.classList.remove('drag-over'));});

  document.querySelectorAll('[data-insert-pattern]').forEach((button)=>button.addEventListener('click',()=>{const pattern=patternLibrary[button.dataset.insertPattern];if(!pattern||!Array.isArray(pattern.blocks))return;serialize();blocks.push(...pattern.blocks.map((block)=>normalizeBlock(structuredClone(block))));render();markChanged();editor.lastElementChild?.scrollIntoView({behavior:'smooth',block:'center'});}));

  document.querySelector('[data-save-pattern]')?.addEventListener('click',async()=>{serialize();const name=window.prompt('Название шаблона секций');if(!name)return;const description=window.prompt('Короткое описание шаблона')||'';const body=new URLSearchParams({_csrf:csrf,name,description,blocks_json:JSON.stringify(blocks)});const response=await fetch(`${location.origin}${document.body.dataset.basePath||''}/studio/patterns`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body});if(response.ok){window.alert('Шаблон сохранён. Он появится после обновления страницы.');}else{window.alert('Не удалось сохранить шаблон.');}});

  const title=document.querySelector('[data-entry-title]');const slug=document.querySelector('[data-entry-slug]');let slugTouched=Boolean(slug?.value);slug?.addEventListener('input',()=>{slugTouched=true;markChanged();});title?.addEventListener('input',()=>{if(slug&&!slugTouched)slug.value=String(title.value||'').toLowerCase().trim().replace(/[а-яё]/g,(letter)=>({а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'e',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'c',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya'})[letter]||letter).replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');markChanged();});

  const featurePath=document.querySelector('[data-feature-path]');document.querySelectorAll('[data-media-path]').forEach((button)=>button.addEventListener('click',()=>{document.querySelectorAll('[data-media-path]').forEach((item)=>item.classList.remove('active'));button.classList.add('active');if(featurePath)featurePath.value=button.dataset.mediaPath||'';markChanged();}));

  let dirty=false;let autosaveTimer=0;const state=document.querySelector('[data-autosave-state]');
  function markChanged(){dirty=true;if(state)state.textContent='Есть несохранённые изменения';window.clearTimeout(autosaveTimer);autosaveTimer=window.setTimeout(autosave,8000);}
  async function autosave(){if(!dirty||!form||!form.dataset.autosaveUrl)return;serialize();const body=new URLSearchParams({_csrf:csrf,entry_id:form.querySelector('[name="id"]')?.value||'0',title:title?.value||'',excerpt:form.querySelector('[name="excerpt"]')?.value||'',content_json:jsonField?.value||'[]'});if(state)state.textContent='Автосохранение…';try{const response=await fetch(form.dataset.autosaveUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body});const data=await response.json();if(!response.ok||!data.ok)throw new Error();dirty=false;if(state)state.textContent=`Автокопия ${data.saved_at||'сохранена'}`;}catch(_){if(state)state.textContent='Автосохранение не выполнено';}}
  window.setInterval(autosave,30000);
  form?.addEventListener('submit',()=>{serialize();dirty=false;});

  let autosaveData={};try{autosaveData=JSON.parse(document.querySelector('[data-autosave-data]')?.value||'{}');}catch(_){}
  document.querySelector('[data-restore-autosave]')?.addEventListener('click',()=>{if(Array.isArray(autosaveData.content)){blocks=autosaveData.content.map(normalizeBlock);if(title&&autosaveData.title)title.value=autosaveData.title;const excerpt=form?.querySelector('[name="excerpt"]');if(excerpt&&autosaveData.excerpt)excerpt.value=autosaveData.excerpt;render();markChanged();document.querySelector('[data-autosave-restore]')?.remove();}});
  document.querySelector('[data-dismiss-autosave]')?.addEventListener('click',()=>document.querySelector('[data-autosave-restore]')?.remove());

  const preview=document.querySelector('[data-builder-preview]');const iframe=preview?.querySelector('iframe');
  document.querySelector('[data-preview-builder]')?.addEventListener('click',()=>{serialize();if(!preview||!iframe)return;const html=blocks.map((block)=>{const d=block.data||{};if(block.type==='heading')return `<h2>${escapeHtml(d.text||'')}</h2>`;if(block.type==='image'&&d.url)return `<img src="${escapeHtml(d.url)}" alt="">`;if(block.type==='gallery')return `<div class="gallery">${(d.items||[]).map((url)=>`<img src="${escapeHtml(url)}">`).join('')}</div>`;if(block.type==='hero')return `<section class="hero"><small>${escapeHtml(d.eyebrow||'')}</small><h1>${escapeHtml(d.title||'')}</h1><p>${escapeHtml(d.text||'')}</p></section>`;if(block.type==='quote'||block.type==='testimonial')return `<blockquote>${escapeHtml(d.text||'')}</blockquote>`;if(block.type==='stats')return `<div class="stats">${(d.items||[]).map((v)=>`<b>${escapeHtml(v.value||'')}<small>${escapeHtml(v.label||'')}</small></b>`).join('')}</div>`;return d.text?`<p>${escapeHtml(d.text)}</p>`:'';}).join('');iframe.srcdoc=`<!doctype html><meta charset="utf-8"><style>body{max-width:980px;margin:0 auto;padding:60px 30px;font:18px/1.6 system-ui;background:#f5f4f0;color:#191919}h1{font:700 64px/1 Georgia}h2{font:700 40px Georgia}img{max-width:100%;border-radius:18px}.hero{padding:60px;border-radius:28px;background:#171f1c;color:#fff}.gallery,.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.gallery img{width:100%;aspect-ratio:1;object-fit:cover}.stats b{padding:24px;background:white;border-radius:16px;font-size:32px}.stats small{display:block;font:14px system-ui;color:#666}blockquote{padding:28px;border-left:5px solid #ef6b49;background:white}</style>${html}`;preview.hidden=false;});
  document.querySelector('[data-close-preview]')?.addEventListener('click',()=>{if(preview)preview.hidden=true;});

  document.querySelectorAll('[data-confirm]').forEach((element)=>element.addEventListener('submit',(event)=>{if(!window.confirm(element.dataset.confirm||'Подтвердить действие?'))event.preventDefault();}));
  if(editor)render();
})();
