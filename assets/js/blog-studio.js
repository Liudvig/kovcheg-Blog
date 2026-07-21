(() => {
  'use strict';

  const sidebar = document.querySelector('.studio-sidebar');
  const overlay = document.querySelector('[data-studio-overlay]');
  const openButton = document.querySelector('[data-studio-open]');
  const closeButton = document.querySelector('[data-studio-close]');
  const setSidebar = (open) => {
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('is-open', open);
    overlay.hidden = !open;
  };
  openButton?.addEventListener('click', () => setSidebar(true));
  closeButton?.addEventListener('click', () => setSidebar(false));
  overlay?.addEventListener('click', () => setSidebar(false));

  const editor = document.querySelector('[data-block-editor]');
  const jsonField = document.querySelector('[data-block-json]');
  const form = document.querySelector('[data-entry-form]');
  const toolbar = document.querySelector('[data-block-toolbar]');

  const uid = () => Math.random().toString(36).slice(2, 10);
  const normalizeBlock = (block = {}) => ({
    id: String(block.id || uid()),
    type: String(block.type || 'paragraph'),
    data: block.data && typeof block.data === 'object' ? block.data : {},
  });

  let blocks = [];
  if (jsonField) {
    try {
      const parsed = JSON.parse(jsonField.value || '[]');
      blocks = Array.isArray(parsed) ? parsed.map(normalizeBlock) : [];
    } catch (_) {
      blocks = [];
    }
  }
  if (!blocks.length && editor) blocks.push(normalizeBlock({ type: 'paragraph', data: { text: '' } }));

  const labels = {
    paragraph: 'Текст', heading: 'Заголовок', image: 'Изображение', quote: 'Цитата',
    list: 'Список', code: 'Код', button: 'Кнопка', separator: 'Разделитель',
    columns: 'Колонки', video: 'Видео',
  };

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
  })[char]);

  const controlHeader = (block, index) => `
    <header><b>${escapeHtml(labels[block.type] || block.type)}</b>
      <div class="block-controls">
        <button type="button" data-block-up="${index}" title="Выше">↑</button>
        <button type="button" data-block-down="${index}" title="Ниже">↓</button>
        <button type="button" data-block-remove="${index}" title="Удалить">×</button>
      </div>
    </header>`;

  const field = (name, value, placeholder = '', type = 'text') => `<input type="${type}" data-block-field="${escapeHtml(name)}" value="${escapeHtml(value)}" placeholder="${escapeHtml(placeholder)}">`;
  const textarea = (name, value, placeholder = '') => `<textarea data-block-field="${escapeHtml(name)}" placeholder="${escapeHtml(placeholder)}">${escapeHtml(value)}</textarea>`;

  const blockMarkup = (block, index) => {
    const data = block.data || {};
    let body = '';
    if (block.type === 'paragraph') body = textarea('text', data.text || '', 'Введите текст абзаца');
    else if (block.type === 'heading') body = `<div class="form-grid">${field('text', data.text || '', 'Заголовок')}
      <select data-block-field="level"><option value="2" ${Number(data.level || 2) === 2 ? 'selected' : ''}>H2</option><option value="3" ${Number(data.level) === 3 ? 'selected' : ''}>H3</option><option value="4" ${Number(data.level) === 4 ? 'selected' : ''}>H4</option></select></div>`;
    else if (block.type === 'image') body = `${field('url', data.url || '', '/storage/uploads/... или https://')}${field('alt', data.alt || '', 'Описание изображения')}${field('caption', data.caption || '', 'Подпись')}`;
    else if (block.type === 'quote') body = `${textarea('text', data.text || '', 'Текст цитаты')}${field('caption', data.caption || '', 'Автор или источник')}`;
    else if (block.type === 'code') body = textarea('text', data.text || '', 'Код или технический фрагмент');
    else if (block.type === 'button') body = `<div class="form-grid">${field('text', data.text || '', 'Текст кнопки')}${field('url', data.url || '', 'https:// или /page')}</div>`;
    else if (block.type === 'list') body = `${textarea('itemsText', Array.isArray(data.items) ? data.items.join('\n') : (data.itemsText || ''), 'Каждый пункт с новой строки')}<label class="check-row"><input type="checkbox" data-block-field="ordered" ${data.ordered ? 'checked' : ''}> Нумерованный список</label>`;
    else if (block.type === 'columns') body = `${textarea('columnsText', Array.isArray(data.columns) ? data.columns.join('\n---\n') : (data.columnsText || ''), 'Разделяйте колонки строкой ---')}`;
    else if (block.type === 'video') body = field('url', data.url || '', 'Ссылка на видео');
    else if (block.type === 'separator') body = '<p>Горизонтальный разделитель</p>';
    else body = textarea('text', data.text || '', 'Содержимое');
    return `<article class="block-item" data-block-index="${index}">${controlHeader(block, index)}${body}</article>`;
  };

  const serialize = () => {
    if (!editor || !jsonField) return;
    editor.querySelectorAll('[data-block-index]').forEach((node) => {
      const index = Number(node.dataset.blockIndex);
      const block = blocks[index];
      if (!block) return;
      node.querySelectorAll('[data-block-field]').forEach((input) => {
        const key = input.dataset.blockField;
        if (!key) return;
        if (input.type === 'checkbox') block.data[key] = input.checked;
        else block.data[key] = input.value;
      });
      if (block.type === 'list') {
        block.data.items = String(block.data.itemsText || '').split(/\r?\n/).map((v) => v.trim()).filter(Boolean);
        delete block.data.itemsText;
      }
      if (block.type === 'columns') {
        block.data.columns = String(block.data.columnsText || '').split(/\r?\n---\r?\n/).map((v) => v.trim()).filter(Boolean).slice(0, 3);
        delete block.data.columnsText;
      }
    });
    jsonField.value = JSON.stringify(blocks);
  };

  const render = () => {
    if (!editor) return;
    editor.innerHTML = blocks.map(blockMarkup).join('');
    serialize();
  };

  toolbar?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-add-block]');
    if (!button) return;
    serialize();
    const type = button.dataset.addBlock || 'paragraph';
    const defaults = {
      paragraph: { text: '' }, heading: { text: '', level: 2 }, image: { url: '', alt: '', caption: '' },
      quote: { text: '', caption: '' }, list: { items: [], ordered: false }, code: { text: '' },
      button: { text: 'Подробнее', url: '' }, separator: {}, columns: { columns: ['', ''] }, video: { url: '' },
    };
    blocks.push(normalizeBlock({ type, data: defaults[type] || {} }));
    render();
    editor.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  editor?.addEventListener('input', serialize);
  editor?.addEventListener('change', serialize);
  editor?.addEventListener('click', (event) => {
    const remove = event.target.closest('[data-block-remove]');
    const up = event.target.closest('[data-block-up]');
    const down = event.target.closest('[data-block-down]');
    if (!remove && !up && !down) return;
    serialize();
    const index = Number((remove || up || down).dataset.blockRemove ?? (remove || up || down).dataset.blockUp ?? (remove || up || down).dataset.blockDown);
    if (!Number.isInteger(index) || !blocks[index]) return;
    if (remove) blocks.splice(index, 1);
    else if (up && index > 0) [blocks[index - 1], blocks[index]] = [blocks[index], blocks[index - 1]];
    else if (down && index < blocks.length - 1) [blocks[index + 1], blocks[index]] = [blocks[index], blocks[index + 1]];
    if (!blocks.length) blocks.push(normalizeBlock({ type: 'paragraph', data: { text: '' } }));
    render();
  });

  form?.addEventListener('submit', serialize);
  if (editor) render();

  const title = document.querySelector('[data-entry-title]');
  const slug = document.querySelector('[data-entry-slug]');
  let slugTouched = Boolean(slug?.value);
  slug?.addEventListener('input', () => { slugTouched = true; });
  title?.addEventListener('input', () => {
    if (!slug || slugTouched) return;
    slug.value = String(title.value || '').toLowerCase().trim()
      .replace(/[а-яё]/g, (letter) => ({а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'e',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'c',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya'})[letter] || letter)
      .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  });

  const featurePath = document.querySelector('[data-feature-path]');
  document.querySelectorAll('[data-media-path]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-media-path]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      if (featurePath) featurePath.value = button.dataset.mediaPath || '';
    });
  });

  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('submit', (event) => {
      if (!window.confirm(element.dataset.confirm || 'Подтвердить действие?')) event.preventDefault();
    });
  });
})();
