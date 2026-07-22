(() => {
  'use strict';

  const reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

  document.querySelectorAll('[data-pmw-carousel]').forEach((root) => {
    const track = root.querySelector('[data-pmw-track]');
    const slides = Array.from(track?.children ?? []);
    const dots = Array.from(root.querySelectorAll('[data-pmw-dot]'));
    const previous = root.querySelector('[data-pmw-prev]');
    const next = root.querySelector('[data-pmw-next]');
    if (!track || slides.length < 1) return;

    let index = 0;
    let timer = 0;
    const interval = Math.max(2000, Math.min(30000, Number(root.dataset.interval || 6000)));
    const autoplay = root.dataset.autoplay === '1' && !reducedMotion && slides.length > 1;

    const update = (nextIndex, smooth = true) => {
      index = (nextIndex + slides.length) % slides.length;
      const slide = slides[index];
      track.scrollTo({ left: slide.offsetLeft, behavior: smooth ? 'smooth' : 'auto' });
      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('is-active', dotIndex === index);
        dot.setAttribute('aria-current', dotIndex === index ? 'true' : 'false');
      });
    };

    const stop = () => {
      if (timer) window.clearInterval(timer);
      timer = 0;
    };

    const start = () => {
      stop();
      if (autoplay) timer = window.setInterval(() => update(index + 1), interval);
    };

    previous?.addEventListener('click', () => { update(index - 1); start(); });
    next?.addEventListener('click', () => { update(index + 1); start(); });
    dots.forEach((dot, dotIndex) => dot.addEventListener('click', () => { update(dotIndex); start(); }));

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);
    root.addEventListener('touchstart', stop, { passive: true });
    root.addEventListener('touchend', start, { passive: true });

    let resizeTimer = 0;
    window.addEventListener('resize', () => {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(() => update(index, false), 120);
    });

    update(0, false);
    start();
  });
})();
