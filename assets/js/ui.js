(() => {
  // -------------------- Theme toggle --------------------
  const root = document.documentElement;
  const btn = document.getElementById('theme-toggle');

  const applyTheme = (t) => {
    if (t === 'light') root.dataset.theme = 'light';
    else delete root.dataset.theme;

    try { localStorage.setItem('lifex_theme', t); } catch (_) {}
    if (btn) {
      const icon = btn.querySelector('.theme-icon');
      if (icon) icon.textContent = (t === 'light') ? '☀️' : '🌙';
      btn.setAttribute('aria-label', t === 'light' ? 'Switch to dark mode' : 'Switch to light mode');
    }
  };

  // Init theme: saved > system preference > default (dark)
  let saved = null;
  try { saved = localStorage.getItem('lifex_theme'); } catch (_) {}
  if (saved === 'light' || saved === 'dark') {
    applyTheme(saved);
  } else {
    const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
    applyTheme(prefersLight ? 'light' : 'dark');
  }

  if (btn) {
    btn.addEventListener('click', () => {
      const current = root.dataset.theme === 'light' ? 'light' : 'dark';
      applyTheme(current === 'light' ? 'dark' : 'light');
    });
  }

  // -------------------- Laptop scrolly landing --------------------
  const screen = document.getElementById('laptop-screen');
  const steps = Array.from(document.querySelectorAll('.feature-step'));
  if (screen && steps.length) {
    const titleEl = screen.querySelector('.screen-title');
    const descEl = screen.querySelector('.screen-desc');
    const pillsEl = screen.querySelector('.screen-pills');

    const renderPills = (pillsStr) => {
      if (!pillsEl) return;
      pillsEl.innerHTML = '';
      (pillsStr || '').split('|').map(s => s.trim()).filter(Boolean).slice(0, 4).forEach(t => {
        const span = document.createElement('span');
        span.className = 'pill';
        span.textContent = t;
        pillsEl.appendChild(span);
      });
    };

    const setActive = (idx) => {
      steps.forEach((s, i) => s.classList.toggle('is-active', i === idx));
      const s = steps[idx];
      if (!s) return;
      const t = s.dataset.title || '';
      const d = s.dataset.desc || '';
      const p = s.dataset.pills || '';
      if (titleEl) titleEl.textContent = t;
      if (descEl) descEl.textContent = d;
      renderPills(p);
    };

    // Initial render
    const initial = Math.max(0, steps.findIndex(s => s.classList.contains('is-active')));
    setActive(initial);

    const io = new IntersectionObserver((entries) => {
      // Pick the entry with highest intersection ratio
      const visible = entries
        .filter(e => e.isIntersecting)
        .sort((a, b) => (b.intersectionRatio - a.intersectionRatio))[0];
      if (!visible) return;
      const idx = steps.indexOf(visible.target);
      if (idx >= 0) setActive(idx);
    }, { threshold: [0.35, 0.55, 0.75] });

    steps.forEach(s => io.observe(s));
  }
})();