(() => {
  'use strict';

  const shell = document.querySelector('[data-classic-editor-shell]');
  const form = document.querySelector('[data-entry-form]');
  const jsonField = document.querySelector('[data-block-json]');
  const modeField = document.querySelector('[data-editor-mode]');
  const visual = document.querySelector('[data-classic-visual]');
  const source = document.querySelector('[data-classic-source]');
  const builderPanel = document.querySelector('[data-builder-panel]');
  const classicPanel = document.querySelector('[data-classic-panel]');
  const blockEditor = document.querySelector('[data-block-editor]');
  const state = document.querySelector('[data-autosave-state]');
  const mediaModal = document.querySelector('[data-classic-media-modal]');
  const previewModal = document.querySelector('[data-classic-preview]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  if (!shell || !form || !jsonField || !modeField || !visual || !source) return;

  document.querySelector('.classic-editor-intro p')?.remove();

  const autosaveUrl = form.dataset.autosaveUrl || '';
  const appBase = new URL(form.action, window.location.href).pathname.replace(/\/studio\/content\/save\/?$/, '');
  const inlineUploadUrl = `${appBase}/studio/media/upload-inline`;
  let classicAutosaveTimer = 0;
  let classicDirty = false;
  let activeSurface = 'visual';

  const payload = () => JSON.stringify([{
    id: shell.dataset.classicId || `classic-${Math.random().toString(36).slice(2, 10)}`,
    type: 'classic',
    data: {html: visual.innerHTML.trim()},
  }]);

  const syncVisualToSource = () => { source.value = visual.innerHTML.trim(); };
  const syncSourceToVisual = () => { visual.innerHTML = source.value; };

  const syncPayload = () => {
    if (modeField.value !== 'classic') return;
    if (activeSurface === 'text') syncSourceToVisual();
    syncVisualToSource();
    jsonField.value = payload();
  };

  const signalBuilderDirtyFlag = () => {
    if (!blockEditor) return;
    blockEditor.dispatchEvent(new Event('input', {bubbles: true}));
    syncPayload();
  };

  const scheduleClassicAutosave = () => {
    classicDirty = true;
    if (state) state.textContent = 'Есть несохранённые изменения';
    window.clearTimeout(classicAutosaveTimer);
    classicAutosaveTimer = window.setTimeout(classicAutosave, 5000);
  };

  const changed = () => {
    syncPayload();
    signalBuilderDirtyFlag();
    scheduleClassicAutosave();
    updateCount();
  };

  const switchEditorMode = (mode) => {
    const classic = mode !== 'builder';
    modeField.value = classic ? 'classic' : 'builder';
    if (classicPanel) classicPanel.hidden = !classic;
    if (builderPanel) builderPanel.hidden = classic;
    document.querySelectorAll('[data-editor-tab]').forEach((button) => {
      const active = button.dataset.editorTab === (classic ? 'classic' : 'builder');
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    if (classic) {
      form.dataset.autosaveUrl = '';
      syncPayload();
      visual.focus({preventScroll: true});
    } else {
      form.dataset.autosaveUrl = autosaveUrl;
      window.clearTimeout(classicAutosaveTimer);
      classicDirty = false;
    }
  };

  const switchSurface = (surface) => {
    if (surface === 'text') {
      syncVisualToSource();
      visual.hidden = true;
      source.hidden = false;
      activeSurface = 'text';
      source.focus();
    } else {
      syncSourceToVisual();
      source.hidden = true;
      visual.hidden = false;
      activeSurface = 'visual';
      visual.focus();
    }
    document.querySelectorAll('[data-classic-surface]').forEach((button) => {
      button.classList.toggle('active', button.dataset.classicSurface === surface);
    });
  };

  const runCommand = (command, value = null) => {
    if (activeSurface !== 'visual') switchSurface('visual');
    visual.focus();
    document.execCommand(command, false, value);
    changed();
  };

  const safePromptUrl = (label, current = '') => {
    const value = window.prompt(label, current);
    if (value === null) return null;
    const url = value.trim();
    if (url === '' || /^(?:https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) return url;
    return `https://${url}`;
  };

  const insertHtml = (html) => {
    if (activeSurface !== 'visual') switchSurface('visual');
    visual.focus();
    document.execCommand('insertHTML', false, html);
    changed();
  };

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
    '&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;',
  })[char]);

  const updateCount = () => {
    const text = (visual.innerText || '').replace(/\s+/g, ' ').trim();
    const words = text === '' ? 0 : text.split(' ').length;
    const counter = document.querySelector('[data-classic-count]');
    if (counter) counter.textContent = `${words} слов · ${text.length} знаков`;
  };

  async function classicAutosave() {
    if (!classicDirty || modeField.value !== 'classic' || autosaveUrl === '') return;
    syncPayload();
    if (state) state.textContent = 'Автосохранение…';
    const body = new URLSearchParams({
      _csrf: csrf,
      entry_id: form.querySelector('[name="id"]')?.value || '0',
      title: form.querySelector('[name="title"]')?.value || '',
      excerpt: form.querySelector('[name="excerpt"]')?.value || '',
      content_json: jsonField.value,
    });
    try {
      const response = await fetch(autosaveUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        body,
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || 'autosave');
      classicDirty = false;
      if (state) state.textContent = `Автокопия ${data.saved_at || 'сохранена'}`;
    } catch (_) {
      if (state) state.textContent = 'Автосохранение не выполнено';
    }
  }

  const closeMediaModal = () => mediaModal?.setAttribute('hidden', '');
  const closePreviewModal = () => previewModal?.setAttribute('hidden', '');

  const ensureMediaGrid = () => {
    if (!mediaModal) return null;
    let grid = mediaModal.querySelector('.classic-editor-media-grid');
    if (grid) return grid;
    mediaModal.querySelector('.empty-state')?.remove();
    grid = document.createElement('div');
    grid.className = 'classic-editor-media-grid';
    mediaModal.querySelector('.classic-editor-modal__dialog')?.append(grid);
    return grid;
  };

  const addMediaButton = (item, first = true) => {
    const grid = ensureMediaGrid();
    if (!grid || !item?.url) return;
    if (grid.querySelector(`[data-media-id="${String(item.id)}"]`)) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.dataset.classicMediaItem = '';
    button.dataset.mediaId = String(item.id || '');
    button.dataset.mediaUrl = String(item.url || '');
    button.dataset.mediaTitle = String(item.title || '');
    button.dataset.mediaAlt = String(item.alt || '');
    button.dataset.mediaCaption = String(item.caption || '');

    const image = document.createElement('img');
    image.src = String(item.url || '');
    image.alt = String(item.alt || '');
    image.loading = 'lazy';

    const label = document.createElement('span');
    label.textContent = String(item.title || 'Изображение');
    button.append(image, label);
    if (first) grid.prepend(button); else grid.append(button);
  };

  const ensureInlineUploader = () => {
    if (!mediaModal || mediaModal.querySelector('[data-inline-media-upload]')) return;
    const dialog = mediaModal.querySelector('.classic-editor-modal__dialog');
    const head = mediaModal.querySelector('.classic-editor-modal__head');
    if (!dialog || !head) return;

    const uploadForm = document.createElement('form');
    uploadForm.className = 'classic-editor-inline-upload';
    uploadForm.dataset.inlineMediaUpload = '';
    uploadForm.enctype = 'multipart/form-data';

    const file = document.createElement('input');
    file.type = 'file';
    file.name = 'media';
    file.accept = 'image/jpeg,image/png,image/webp';
    file.required = true;

    const folderSource = form.querySelector('select[name="featured_folder_id"]');
    const folder = document.createElement('select');
    folder.name = 'folder_id';
    if (folderSource) folder.innerHTML = folderSource.innerHTML;
    else folder.innerHTML = '<option value="0">Без папки</option>';

    const submit = document.createElement('button');
    submit.type = 'submit';
    submit.className = 'button primary small';
    submit.textContent = 'Загрузить';

    const message = document.createElement('span');
    message.dataset.inlineUploadState = '';
    message.textContent = 'JPEG, PNG или WebP';

    uploadForm.append(file, folder, submit, message);
    head.insertAdjacentElement('afterend', uploadForm);

    uploadForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const selected = file.files?.[0];
      if (!selected) return;
      submit.disabled = true;
      message.textContent = 'Загрузка…';
      const body = new FormData();
      body.append('_csrf', csrf);
      body.append('media', selected);
      body.append('folder_id', folder.value || '0');

      try {
        const response = await fetch(inlineUploadUrl, {
          method: 'POST',
          headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
          body,
        });
        const data = await response.json();
        if (!response.ok || !data.ok || !data.item) throw new Error(data.message || 'upload');
        addMediaButton(data.item, true);
        file.value = '';
        message.textContent = 'Файл загружен. Нажмите на него ниже, чтобы вставить.';
      } catch (error) {
        message.textContent = error instanceof Error && error.message !== 'upload'
          ? error.message
          : 'Не удалось загрузить файл.';
      } finally {
        submit.disabled = false;
      }
    });
  };

  const openMediaModal = () => {
    if (!mediaModal) return;
    ensureInlineUploader();
    mediaModal.removeAttribute('hidden');
    mediaModal.querySelector('input[type="file"]')?.focus();
  };

  const openPreview = () => {
    syncPayload();
    const frame = previewModal?.querySelector('iframe');
    if (!previewModal || !frame) return;
    frame.setAttribute('sandbox', '');
    frame.srcdoc = `<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{max-width:860px;margin:0 auto;padding:30px 24px;font:16px/1.65 Georgia,serif;color:#202124}h2,h3,h4{font-family:Arial,sans-serif;line-height:1.25}img{max-width:100%;height:auto}blockquote{margin:20px 0;padding:12px 18px;border-left:4px solid #2271b1;background:#f6f7f7}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccd0d4;padding:7px}.text-align-center{text-align:center}.text-align-right{text-align:right}.text-align-justify{text-align:justify}</style></head><body>${visual.innerHTML}</body></html>`;
    previewModal.removeAttribute('hidden');
  };

  document.querySelectorAll('[data-editor-tab]').forEach((button) => {
    button.addEventListener('click', () => switchEditorMode(button.dataset.editorTab || 'classic'));
  });

  document.querySelectorAll('[data-classic-surface]').forEach((button) => {
    button.addEventListener('click', () => switchSurface(button.dataset.classicSurface || 'visual'));
  });

  document.querySelector('[data-classic-format]')?.addEventListener('change', (event) => {
    const tag = event.target.value || 'p';
    runCommand('formatBlock', tag);
    event.target.value = '';
  });

  shell.addEventListener('click', (event) => {
    const button = event.target.closest('[data-command],[data-action]');
    if (!button || !shell.contains(button)) return;
    event.preventDefault();

    if (button.dataset.command) {
      runCommand(button.dataset.command, button.dataset.value || null);
      return;
    }

    const action = button.dataset.action;
    if (action === 'link') {
      const url = safePromptUrl('Адрес ссылки');
      if (url) runCommand('createLink', url);
    } else if (action === 'unlink') {
      runCommand('unlink');
    } else if (action === 'media') {
      openMediaModal();
    } else if (action === 'fullscreen') {
      shell.classList.toggle('is-fullscreen');
      document.body.classList.toggle('classic-editor-fullscreen', shell.classList.contains('is-fullscreen'));
      button.setAttribute('aria-pressed', shell.classList.contains('is-fullscreen') ? 'true' : 'false');
    } else if (action === 'preview') {
      openPreview();
    }
  });

  visual.addEventListener('input', changed);
  source.addEventListener('input', changed);
  visual.addEventListener('blur', syncVisualToSource);

  visual.addEventListener('paste', (event) => {
    const html = event.clipboardData?.getData('text/html') || '';
    const text = event.clipboardData?.getData('text/plain') || '';
    if (!html) return;
    event.preventDefault();
    const cleaned = html
      .replace(/<!--\[if[\s\S]*?<!\[endif\]-->/gi, '')
      .replace(/<o:p>[\s\S]*?<\/o:p>/gi, '')
      .replace(/\s(?:class|style|lang|dir|data-[\w-]+)=("[^"]*"|'[^']*')/gi, '')
      .replace(/<\/?(?:font|meta|link|style|script)[^>]*>/gi, '');
    document.execCommand('insertHTML', false, cleaned || escapeHtml(text).replace(/\n/g, '<br>'));
    changed();
  });

  visual.addEventListener('keydown', (event) => {
    const modifier = event.ctrlKey || event.metaKey;
    if (!modifier) return;
    const key = event.key.toLowerCase();
    if (key === 'b') { event.preventDefault(); runCommand('bold'); }
    else if (key === 'i') { event.preventDefault(); runCommand('italic'); }
    else if (key === 'u') { event.preventDefault(); runCommand('underline'); }
    else if (key === 'k') { event.preventDefault(); const url = safePromptUrl('Адрес ссылки'); if (url) runCommand('createLink', url); }
    else if (key === 's') { event.preventDefault(); syncPayload(); form.requestSubmit(); }
  });

  mediaModal?.addEventListener('click', (event) => {
    const item = event.target.closest('[data-classic-media-item]');
    if (item) {
      const url = item.dataset.mediaUrl || '';
      if (!url) return;
      const alt = item.dataset.mediaAlt || item.dataset.mediaTitle || '';
      const caption = item.dataset.mediaCaption || '';
      const image = `<img src="${escapeHtml(url)}" alt="${escapeHtml(alt)}" loading="lazy">`;
      insertHtml(caption ? `<figure>${image}<figcaption>${escapeHtml(caption)}</figcaption></figure><p><br></p>` : `<p>${image}</p><p><br></p>`);
      closeMediaModal();
      return;
    }
    if (event.target.closest('[data-close-classic-media]') || event.target === mediaModal) closeMediaModal();
  });

  previewModal?.addEventListener('click', (event) => {
    if (event.target.closest('[data-close-classic-preview]') || event.target === previewModal) closePreviewModal();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    closeMediaModal();
    closePreviewModal();
    if (shell.classList.contains('is-fullscreen')) {
      shell.classList.remove('is-fullscreen');
      document.body.classList.remove('classic-editor-fullscreen');
    }
  });

  form.addEventListener('submit', () => {
    if (modeField.value === 'classic') syncPayload();
    classicDirty = false;
  });

  document.querySelector('[data-restore-autosave]')?.addEventListener('click', () => {
    let autosave = {};
    try { autosave = JSON.parse(document.querySelector('[data-autosave-data]')?.value || '{}'); } catch (_) {}
    const first = Array.isArray(autosave.content) ? autosave.content[0] : null;
    if (first?.type === 'classic' && typeof first?.data?.html === 'string') {
      visual.innerHTML = first.data.html;
      syncVisualToSource();
      switchEditorMode('classic');
      changed();
    }
  });

  form.dataset.autosaveUrl = '';
  switchEditorMode(modeField.value || 'classic');
  syncVisualToSource();
  syncPayload();
  updateCount();
})();
