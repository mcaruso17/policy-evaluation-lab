/* ─── Shared JS — theme, nav, reveal animations, KaTeX, highlight ───────────── */

(function () {
  'use strict';

  /* ── Theme (light by default) ─────────────────────────────────────────────── */

  const html        = document.documentElement;
  const toggleBtn   = document.getElementById('dark-toggle');
  const hljsTheme   = document.getElementById('hljs-theme');

  const HLJS_LIGHT  = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css';
  const HLJS_DARK   = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css';

  function applyTheme(theme) {
    html.setAttribute('data-theme', theme);
    if (hljsTheme) {
      hljsTheme.href = theme === 'dark' ? HLJS_DARK : HLJS_LIGHT;
    }
    if (toggleBtn) {
      toggleBtn.textContent = theme === 'dark' ? 'Light' : 'Dark';
    }
    // Anything drawn in colours computed in JS rather than read from CSS
    // variables — the hue-generated bears on the DiD and synth pages — has to
    // be told to repaint, since a var() swap cannot reach it.
    document.dispatchEvent(new CustomEvent('econvis:theme', { detail: { theme: theme } }));
  }

  // Initialise from localStorage, defaulting to LIGHT (warm editorial look).
  // Uses a fresh key ('mc-theme') so any older stored preference from a
  // previous version of the site cannot force dark mode on first load.
  const THEME_KEY = 'mc-theme';
  const saved = localStorage.getItem(THEME_KEY) || 'light';
  applyTheme(saved);

  if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      localStorage.setItem(THEME_KEY, next);
      applyTheme(next);
    });
  }

  /* ── Mobile nav toggle (injected so every page gets it) ────────────────────── */

  const navEl    = document.querySelector('nav');
  const navLinks = document.querySelector('.nav-links');

  if (navEl && navLinks) {
    const navToggle = document.createElement('button');
    navToggle.className = 'nav-toggle';
    navToggle.setAttribute('aria-label', 'Toggle menu');
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.innerHTML = '<span></span><span></span><span></span>';

    if (toggleBtn) {
      navEl.insertBefore(navToggle, toggleBtn);
    } else {
      navEl.appendChild(navToggle);
    }

    navToggle.addEventListener('click', function () {
      const open = navEl.classList.toggle('nav-open');
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    navLinks.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        navEl.classList.remove('nav-open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ── Scroll-reveal animation ───────────────────────────────────────────────── */
  /* Content gently fades up as it enters the viewport. Elements already on     */
  /* screen at load animate in a short staggered sequence. Fully disabled when  */
  /* the visitor prefers reduced motion, and degrades gracefully without JS     */
  /* (the .reveal class is only ever added here, so no-JS visitors see          */
  /* everything immediately).                                                   */

  const REVEAL_SELECTOR = [
    '.home-hero > *',
    '.home-intro > *',
    '.hero > *',
    '.page-wrap > *',
    '.profile-hero > *',
    '.model-card',
    '.paper-item',
    '.course-item',
    '.event-item',
    '.contact-card',
    '.ref-item',
    'article > section',
    'article > header'
  ].join(', ');

  function initReveal() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion || !('IntersectionObserver' in window)) return;

    const targets = document.querySelectorAll(REVEAL_SELECTOR);
    if (!targets.length) return;

    targets.forEach(function (el) {
      el.classList.add('reveal');
    });

    const io = new IntersectionObserver(function (entries) {
      // Stagger elements that become visible in the same batch
      let delay = 0;
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        el.style.transitionDelay = delay + 'ms';
        el.classList.add('in');
        io.unobserve(el);
        delay = Math.min(delay + 70, 350);
      });
    }, {
      threshold: 0.08,
      rootMargin: '0px 0px -5% 0px'
    });

    targets.forEach(function (el) {
      io.observe(el);
    });
  }

  /* ── On DOM ready ──────────────────────────────────────────────────────────── */

  document.addEventListener('DOMContentLoaded', function () {

    initReveal();

    /* Footer mascot — pages opt in simply by loading js/bear.js */
    if (window.EconVisBear) {
      window.EconVisBear.mountFooter();
      window.EconVisBear.mountAll();
    }

    /* KaTeX auto-render */
    if (typeof renderMathInElement !== 'undefined') {
      renderMathInElement(document.body, {
        delimiters: [
          { left: '$$', right: '$$', display: true  },
          { left: '$',  right: '$',  display: false }
        ],
        throwOnError: false
      });
    }

    /* Highlight.js */
    if (typeof hljs !== 'undefined') {
      hljs.highlightAll();
    }

    /* Nav active state */
    const currentPath = window.location.pathname.replace(/\\/g, '/');
    document.querySelectorAll('.nav-links a').forEach(function (link) {
      const linkPath = link.getAttribute('href');
      if (!linkPath) return;

      const currentFile = currentPath.split('/').pop() || 'index.html';
      const linkFile    = linkPath.split('/').pop();

      if (currentFile === linkFile) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  });

})();
