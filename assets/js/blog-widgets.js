(() => {
  'use strict';

  const zones = [...document.querySelectorAll('[data-widget-zone]')];
  const pool = document.querySelector('[data-widget-zone="__pool"]');
  let dragged = null;

  const refreshRegionStates = () => {
    document.querySelectorAll('[data-zone-card]').forEach((card) => {
      const zone = card.querySelector(':scope > [data-widget-zone]');
      card.classList.toggle('has-widgets', Boolean(zone?.querySelector(':scope > .widget-card')));
    });

    document.querySelectorAll('[data-blueprint-region]').forEach((region) => {
      const hasWidgets = region.querySelector('[data-widget-zone] > .widget-card') !== null;
      region.classList.toggle('has-widgets', hasWidgets);
    });
  };

  const refreshEmptyStates = () => {
    zones.forEach((zone) => {
      const empty = zone.querySelector(':scope > .widget-zone__empty');
      if (!empty) return;
      empty.hidden = zone.querySelector(':scope > .widget-card') !== null;
    });
    refreshRegionStates();
  };

  const moveCard = (card, zone, before = null) => {
    if (!card || !zone) return;
    if (before && before !== card) zone.insertBefore(card, before);
    else zone.appendChild(card);
    refreshEmptyStates();
  };

  document.querySelectorAll('.widget-card[draggable="true"]').forEach((card) => {
    card.addEventListener('dragstart', (event) => {
      dragged = card;
      card.classList.add('is-dragging');
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.widgetId || '');
      }
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('is-dragging');
      zones.forEach((zone) => zone.classList.remove('is-drop-target'));
      dragged = null;
      refreshEmptyStates();
    });
  });

  zones.forEach((zone) => {
    zone.addEventListener('dragover', (event) => {
      if (!dragged) return;
      event.preventDefault();
      zone.classList.add('is-drop-target');
      const cards = [...zone.querySelectorAll(':scope > .widget-card:not(.is-dragging)')];
      const before = cards.find((card) => {
        const box = card.getBoundingClientRect();
        return event.clientY < box.top + box.height / 2;
      });
      moveCard(dragged, zone, before || null);
    });
    zone.addEventListener('dragleave', (event) => {
      if (!zone.contains(event.relatedTarget)) zone.classList.remove('is-drop-target');
    });
    zone.addEventListener('drop', (event) => {
      if (!dragged) return;
      event.preventDefault();
      zone.classList.remove('is-drop-target');
      moveCard(dragged, zone);
    });
  });

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-widget-up],[data-widget-down],[data-widget-pool]');
    if (!button) return;
    const card = button.closest('.widget-card');
    const zone = card?.parentElement;
    if (!card || !zone) return;

    if (button.hasAttribute('data-widget-pool')) {
      moveCard(card, pool);
      return;
    }

    if (button.hasAttribute('data-widget-up')) {
      const previous = card.previousElementSibling;
      if (previous?.classList.contains('widget-card')) zone.insertBefore(card, previous);
    } else {
      const next = card.nextElementSibling;
      if (next?.classList.contains('widget-card')) zone.insertBefore(next, card);
    }
    refreshEmptyStates();
  });

  document.querySelectorAll('[data-layout-save-form]').forEach((form) => {
    form.addEventListener('submit', () => {
      const placements = [];
      document.querySelectorAll('[data-widget-zone]:not([data-widget-zone="__pool"])').forEach((zone) => {
        zone.querySelectorAll(':scope > .widget-card').forEach((card, index) => {
          placements.push({
            widget_id: Number(card.dataset.widgetId || 0),
            zone: zone.dataset.widgetZone || '',
            sort_order: (index + 1) * 10,
          });
        });
      });
      const input = form.querySelector('[data-placements-json]');
      if (input) input.value = JSON.stringify(placements);
    });
  });

  document.querySelectorAll('[data-widget-menu-button]').forEach((button) => {
    const id = button.getAttribute('aria-controls');
    const menu = id ? document.getElementById(id) : null;
    if (!menu) return;
    button.addEventListener('click', () => {
      const open = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', open ? 'false' : 'true');
      menu.classList.toggle('is-open', !open);
    });
  });

  refreshEmptyStates();
})();