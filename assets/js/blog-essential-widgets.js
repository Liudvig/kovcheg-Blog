(() => {
  'use strict';

  const initSlider = (root) => {
    const slides = Array.from(root.querySelectorAll('[data-essential-slide]'));
    if (!slides.length) return;

    let index = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
    const counter = root.querySelector('[data-slider-counter]');
    let timer = 0;

    const show = (next) => {
      index = (next + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === index));
      if (counter) counter.textContent = `${index + 1} / ${slides.length}`;
      slides.forEach((slide, slideIndex) => {
        slide.querySelectorAll('video,audio').forEach((media) => {
          if (slideIndex !== index && typeof media.pause === 'function') media.pause();
        });
      });
    };

    const restart = () => {
      if (timer) window.clearInterval(timer);
      if (root.dataset.autoplay === '1' && slides.length > 1) {
        timer = window.setInterval(() => show(index + 1), 6500);
      }
    };

    root.querySelector('[data-slider-prev]')?.addEventListener('click', () => {
      show(index - 1);
      restart();
    });
    root.querySelector('[data-slider-next]')?.addEventListener('click', () => {
      show(index + 1);
      restart();
    });
    root.addEventListener('mouseenter', () => timer && window.clearInterval(timer));
    root.addEventListener('mouseleave', restart);
    root.addEventListener('focusin', () => timer && window.clearInterval(timer));
    root.addEventListener('focusout', restart);

    show(index);
    restart();
  };

  document.querySelectorAll('[data-essential-slider]').forEach(initSlider);
})();
