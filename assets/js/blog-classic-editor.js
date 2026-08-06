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
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  if (!shell || !form || !jsonField || !modeField || !visual || !source) return;

  const autosaveUrl = form.dataset.autosaveUrl || '';
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
    classicPanel.hidden = !classic;
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
      if (!response.ok || !data.ok) throw new Error('autosave');
      classicDirty = false;
      if (state) state.textContent = `Автокопия ${data.saved_at || 'сохранена'}`;
    } catch (_) {
      if (state) state.textContent = 'Автосохранение не выполнено';
    }
  }

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

  document.querySelector('[data-classic-toolbar]')?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-command],[data-action]');
    if (!button) return;
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
      document.querySelector('[data-classic-media-modal]')?.removeAttribute('hidden');
    } else if (action === 'fullscreen') {
      shell.classList.toggle('is-fullscreen');
      document.body.classList.toggle('classic-editor-fullscreen', shell.classList.contains('is-fullscreen'));
      button.setAttribute('aria-pressed', shell.classList.contains('is-fullscreen') ? 'true' : 'false');
    } else if (action === 'preview') {
      syncPayload();
      const preview = document.querySelector('[data-classic-preview]');
      const frame = preview?.querySelector('iframe');
      if (!preview || !frame) return;
      frame.srcdoc = `<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{max-width:900px;margin:0 auto;padding:48px 28px;font:18px/1.7 Georgia,serif;color:#202124}h2,h3,h4{font-family:Arial,sans-serif;line-height:1.2}img{max-width:100%;height:auto}blockquote{margin:24px 0;padding:16px 24px;border-left:4px solid #2271b1;background:#f6f7f7}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccd0d4;padding:8px}.text-align-center{text-align:center}.text-align-right{text-align:right}.text-align-justify{text-align:justify}</style><body>${visual.innerHTML}</body></html>`;
      preview.hidden = false;
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

  document.querySelectorAll('[data-classic-media-item]').forEach((button) => {
    button.addEventListener('click', () => {
      const url = button.dataset.mediaUrl || '';
      if (!url) return;
      const alt = button.dataset.mediaAlt || button.dataset.mediaTitle || '';
      const caption = button.dataset.mediaCaption || '';
      const image = `<img src="${escapeHtml(url)}" alt="${escapeHtml(alt)}" loading="lazy">`;
      insertHtml(caption ? `<figure>${image}<figcaption>${escapeHtml(caption)}</figcaption></figure><p><br></p>` : `<p>${image}</p><p><br></p>`);
      document.querySelector('[data-classic-media-modal]')?.setAttribute('hidden', '');
    });
  });

  document.querySelectorAll('[data-close-classic-media]').forEach((button) => {
    button.addEventListener('click', () => document.querySelector('[data-classic-media-modal]')?.setAttribute('hidden', ''));
  });

  document.querySelector('[data-close-classic-preview]')?.addEventListener('click', () => {
    document.querySelector('[data-classic-preview]')?.setAttribute('hidden', '');
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelector('[data-classic-media-modal]')?.setAttribute('hidden', '');
    document.querySelector('[data-classic-preview]')?.setAttribute('hidden', '');
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
