(() => {
 'use strict';

 const shell = document.querySelector('[data-admin-shell]');
 if (!shell) return;

 document.documentElement.classList.add('has-admin-shell');
 document.body.classList.add('admin-shell-active');

 const nav = shell.querySelector('[data-admin-nav]');
 const openButton = shell.querySelector('[data-admin-nav-toggle]');
 const closeButton = shell.querySelector('[data-admin-nav-close]');
 const overlay = shell.querySelector('[data-admin-nav-overlay]');

 const setOpen = (open) => {
  shell.classList.toggle('admin-nav-open', open);
  if (openButton) openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
  if (overlay) overlay.hidden = !open;
  document.documentElement.classList.toggle('admin-menu-locked', open);
 };

 openButton?.addEventListener('click', () => setOpen(!shell.classList.contains('admin-nav-open')));
 closeButton?.addEventListener('click', () => setOpen(false));
 overlay?.addEventListener('click', () => setOpen(false));
 nav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));

 document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') setOpen(false);
 });

 const media = window.matchMedia('(min-width: 981px)');
 const resetDesktop = (event) => {
  if (event.matches) setOpen(false);
 };
 if (typeof media.addEventListener === 'function') media.addEventListener('change', resetDesktop);
 else if (typeof media.addListener === 'function') media.addListener(resetDesktop);

 // Normalize legacy user-facing wording left by the former universal CMS shell.
 const replacements = new Map([
  ['KOVCHEG CMS', 'KOVCHEG Blog'],
  ['без изменения ядра, данных и модулей', 'без изменения системы, данных и модулей'],
  ['Без изменения ядра, данных и модулей', 'Без изменения системы, данных и модулей'],
  ['Версия ядра', 'Версия KOVCHEG Blog'],
  ['Системная панель ядра', 'Панель управления KOVCHEG Blog'],
 ]);

 const walker = document.createTreeWalker(shell, NodeFilter.SHOW_TEXT, {
  acceptNode(node) {
   if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
   const parent = node.parentElement;
   if (!parent || parent.closest('script,style,textarea,input,select,code,pre')) return NodeFilter.FILTER_REJECT;
   return NodeFilter.FILTER_ACCEPT;
  },
 });

 const nodes = [];
 while (walker.nextNode()) nodes.push(walker.currentNode);
 nodes.forEach((node) => {
  let value = node.nodeValue;
  replacements.forEach((replacement, legacy) => {
   value = value.split(legacy).join(replacement);
  });
  node.nodeValue = value;
 });
})();
