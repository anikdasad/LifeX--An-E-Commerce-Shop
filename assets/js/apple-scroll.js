(() => {
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('is-in');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.reveal, .scale').forEach(el => io.observe(el));

  // simple parallax (Apple-like)
  const parallaxEls = Array.from(document.querySelectorAll('.parallax'));
  if (parallaxEls.length) {
    const onScroll = () => {
      const y = window.scrollY || 0;
      parallaxEls.forEach(el => {
        const speed = parseFloat(el.dataset.speed || '0.12');
        const rect = el.getBoundingClientRect();
        const offset = (rect.top + y) * speed;
        el.style.setProperty('--py', `${-offset}px`);
      });
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
})();