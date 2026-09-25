/* ─── EconVis bear ─────────────────────────────────────────────────────────
   The site mascot as a reusable sprite. The pixel data below was extracted
   mechanically from the original hand-drawn bear in index.html (107 one-pixel
   -tall runs on a 20 x 22 grid, the cream backdrop plaque dropped so the bear
   sits on any background).

   Colours are never baked in: every run carries a palette *key*, and the caller
   supplies the palette. The default palette resolves from CSS custom properties
   so dark mode retints it for free; palette(hue) generates a whole family of
   differently coloured bears from a single hue, which is what the synthetic
   control page uses to show a donor pool.

   No model logic and no page ids live here — same discipline as comic.js.
   D3 is required for draw(); markup() works without it. */
(function () {
  'use strict';

  /* Sprite geometry, in sprite pixels. */
  const W = 20;
  const H = 22;

  /* Palette keys: 0 outline, 1 fur, 2 highlight, 3 muzzle, 4 eye, then the three
     keys the section props use — 5 prop (dark line work), 6 propAlt (the pale
     face of a prop: glass, paper, mesh), 7 accent (the one warm highlight). */
  const KEYS = ['outline', 'fur', 'highlight', 'muzzle', 'eye', 'prop', 'propAlt', 'accent'];

  /* [row, x, width, key] — one entry per horizontal run of identical pixels. */
  const RUNS = [
    [0,2,4,0],[0,14,4,0],
    [1,1,1,0],[1,2,4,1],[1,6,1,0],[1,13,1,0],[1,14,4,1],[1,18,1,0],
    [2,1,1,0],[2,2,1,1],[2,3,2,2],[2,5,1,1],[2,6,1,0],[2,13,1,0],[2,14,1,1],[2,15,2,2],[2,17,1,1],[2,18,1,0],
    [3,1,1,0],[3,2,1,1],[3,3,2,2],[3,5,1,1],[3,6,8,0],[3,14,1,1],[3,15,2,2],[3,17,1,1],[3,18,1,0],
    [4,0,1,0],[4,1,18,1],[4,19,1,0],
    [5,0,1,0],[5,1,18,1],[5,19,1,0],
    [6,0,1,0],[6,1,4,1],[6,5,1,4],[6,6,1,0],[6,7,6,1],[6,13,1,4],[6,14,1,0],[6,15,4,1],[6,19,1,0],
    [7,0,1,0],[7,1,4,1],[7,5,2,0],[7,7,6,1],[7,13,2,0],[7,15,4,1],[7,19,1,0],
    [8,0,1,0],[8,1,18,1],[8,19,1,0],
    [9,0,1,0],[9,1,6,1],[9,7,6,3],[9,13,6,1],[9,19,1,0],
    [10,0,1,0],[10,1,6,1],[10,7,1,3],[10,8,4,0],[10,12,1,3],[10,13,6,1],[10,19,1,0],
    [11,0,1,0],[11,1,6,1],[11,7,2,3],[11,9,2,0],[11,11,2,3],[11,13,6,1],[11,19,1,0],
    [12,0,1,0],[12,1,18,1],[12,19,1,0],
    [13,1,18,0],
    [14,2,16,0],
    [15,1,1,0],[15,2,4,1],[15,6,8,3],[15,14,4,1],[15,18,1,0],
    [16,0,1,0],[16,1,5,1],[16,6,8,3],[16,14,5,1],[16,19,1,0],
    [17,0,1,0],[17,1,5,1],[17,6,8,3],[17,14,5,1],[17,19,1,0],
    [18,1,1,0],[18,2,5,1],[18,7,6,3],[18,13,5,1],[18,18,1,0],
    [19,2,1,0],[19,3,14,1],[19,17,1,0],
    [20,2,1,0],[20,3,2,2],[20,5,4,1],[20,9,2,0],[20,11,4,1],[20,15,2,2],[20,17,1,0],
    [21,3,14,0]
  ];

  /* Rows 19–21 are the legs and paws: the walk cycle lifts one side at a time
     by re-emitting these rows with a small x offset, so callers need to know
     where the legs start. */
  const LEG_ROW = 19;

  /* ── Section variants ──────────────────────────────────────────────────
     The same bear turns up on every section of the site, each time holding
     the tool of that section. A variant is just more runs in the same
     [row, x, width, key] language, drawn on top of the base sprite, plus the
     view box it needs — props are allowed to spill outside the 20 x 22 grid,
     and rows may be negative (the mortarboard sits above the ears).

     Keys 5/6/7 are prop / propAlt / accent, so a variant never names a colour. */
  const VARIANTS = {

    /* Research — a magnifying glass, lens up and handle running down into the
       paw, which is why the last three runs land on top of the fur. */
    research: {
      view: [0, -4, 26, 26],
      runs: [
        [10,21,2,5],
        [11,20,1,5],[11,21,2,6],[11,23,1,5],
        [12,19,1,5],[12,20,4,6],[12,24,1,5],
        [13,19,1,5],[13,20,4,6],[13,24,1,5],
        [14,20,1,5],[14,21,2,6],[14,23,1,5],
        [15,21,2,5],
        [16,19,2,5],
        [17,18,2,5],
        [18,17,2,5]
      ]
    },

    /* Teaching — a mortarboard between the ears: flat plate on top, crown
       below it, tassel hanging clear of the right ear so it stays readable. */
    teaching: {
      view: [0, -4, 21, 26],
      runs: [
        [0,7,6,5],[1,7,6,5],[2,7,6,5],
        [-1,2,17,5],
        [-2,3,15,5],
        [0,18,1,5],[1,18,1,5],[2,18,1,5],[3,18,1,5],
        [4,17,2,7],[5,17,2,7]
      ]
    },

    /* Events — a stand microphone at the bear's side: mesh head, thin stem,
       wide base. Kept one pixel clear of the body outline so the stem does not
       read as part of the bear. */
    events: {
      view: [0, -4, 26, 26],
      runs: [
        [10,21,2,5],
        [11,20,1,5],[11,21,2,6],[11,23,1,5],
        [12,20,1,5],[12,21,2,6],[12,23,1,5],
        [13,20,1,5],[13,21,2,6],[13,23,1,5],
        [14,21,2,5],
        [15,21,2,5],[16,21,2,5],[17,21,2,5],
        [18,20,4,5]
      ]
    },

    /* Contacts — an envelope held against the chest, flap folded down. */
    contacts: {
      view: [0, -4, 20, 26],
      runs: [
        [13,5,10,5],
        [14,5,1,5],[14,6,8,6],[14,14,1,5],
        [15,5,1,5],[15,6,8,6],[15,14,1,5],
        [16,5,1,5],[16,6,8,6],[16,14,1,5],
        [17,5,1,5],[17,6,8,6],[17,14,1,5],
        [18,5,10,5],
        [14,6,1,5],[14,13,1,5],
        [15,7,1,5],[15,12,1,5],
        [16,8,1,5],[16,11,1,5],
        [17,9,2,5]
      ]
    },

    /* EconVis — a three-bar chart on the ground beside the bear, rising to the
       right, which is the one shape every simulation on that site produces. */
    charts: {
      view: [0, -4, 28, 26],
      runs: [
        [9,26,2,7],[10,26,2,7],[11,26,2,7],
        [12,23,2,7],[13,23,2,7],[14,23,2,7],
        [12,26,2,7],[13,26,2,7],[14,26,2,7],
        [15,20,2,7],[15,23,2,7],[15,26,2,7],
        [16,20,2,7],[16,23,2,7],[16,26,2,7],
        [17,20,2,7],[17,23,2,7],[17,26,2,7],
        [18,20,8,5]
      ]
    },

    /* The Good Economics — an open book, spine down the middle, two lines of
       type on each page. */
    book: {
      view: [0, -4, 20, 26],
      runs: [
        [13,4,12,5],
        [14,4,5,6],[14,9,2,5],[14,11,5,6],
        [15,4,5,6],[15,9,2,5],[15,11,5,6],
        [16,4,5,6],[16,9,2,5],[16,11,5,6],
        [17,4,5,6],[17,9,2,5],[17,11,5,6],
        [18,4,12,5],
        [15,5,3,7],[15,12,3,7],
        [17,5,2,7],[17,12,2,7]
      ]
    }
  };

  /* Resolve a variant name to {runs, view}; an unknown or missing name gives
     the plain bear on the plain grid. */
  function variant(name) {
    const v = (name && VARIANTS[name]) || null;
    return {
      runs: RUNS.concat(v ? v.runs : []),
      view: v ? v.view : [0, 0, W, H]
    };
  }


  /* The default palette defers to CSS, so [data-theme="dark"] can lift the
     outline without any JS. The fallbacks are the original homepage hexes, so
     a page that never defines the variables still gets the right bear. */
  const DEFAULT_PALETTE = {
    outline:   'var(--bear-outline, #2A1D14)',
    fur:       'var(--bear-fur, #8A5A38)',
    highlight: 'var(--bear-highlight, #C79A72)',
    muzzle:    'var(--bear-muzzle, #E3CBAE)',
    eye:       '#FFFFFF',
    prop:      'var(--bear-prop, #2A1D14)',
    propAlt:   'var(--bear-prop-alt, #EFE9DC)',
    accent:    'var(--bear-prop-accent, #C2703D)'
  };

  function isDark() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
  }

  /* Build a palette from one hue. The lightness/saturation ramp is read off the
     original bear (fur #8A5A38 is hsl(25, 42%, 38%)), so palette(25) reproduces
     it almost exactly. The outline lifts in dark mode, otherwise a 12%-light
     silhouette would vanish into the near-black background. */
  function palette(hue, opts) {
    opts = opts || {};
    const sat  = opts.sat  == null ? 1 : opts.sat;   // saturation multiplier
    const lift = opts.lift == null ? 0 : opts.lift;  // lightness offset, points
    const outlineL = opts.outlineL != null ? opts.outlineL : (isDark() ? 26 : 12);
    function hsl(h, s, l) {
      return 'hsl(' + ((h % 360) + 360) % 360 + ',' +
             Math.max(0, Math.min(100, s * sat)).toFixed(1) + '%,' +
             Math.max(0, Math.min(100, l + lift)).toFixed(1) + '%)';
    }
    return {
      outline:   hsl(hue,     35, outlineL),
      fur:       hsl(hue,     42, 38),
      highlight: hsl(hue + 3, 43, 61),
      muzzle:    hsl(hue + 8, 49, 79),
      eye:       '#FFFFFF',
      // Props stay in the default ink so a hue-shifted bear still reads as
      // holding the same object rather than a tinted copy of itself.
      prop:      DEFAULT_PALETTE.prop,
      propAlt:   DEFAULT_PALETTE.propAlt,
      accent:    DEFAULT_PALETTE.accent
    };
  }

  /* A flat palette — every part of the bear in one colour. Used for the target
     silhouette on the synthetic control page. */
  function monochrome(colour) {
    const pal = {};
    KEYS.forEach(function (k) { pal[k] = colour; });
    return pal;
  }

  /* ── Drawing ──────────────────────────────────────────────────────────── */

  /* Append the sprite to a D3 selection as a single <g>. The g is transformed,
     never the rects, so animating a bear means writing one transform attribute
     rather than re-emitting 107 elements. */
  function draw(parent, opts) {
    opts = opts || {};
    const pal  = opts.palette || DEFAULT_PALETTE;
    const size = opts.size == null ? 22 : opts.size;   // rendered height, px
    const v    = variant(opts.variant);
    const vx = v.view[0], vy = v.view[1], vw = v.view[2], vh = v.view[3];
    // A variant with a prop is taller or wider than the bare sprite, so size is
    // read against the variant's own box — a 40px research bear and a 40px
    // teaching bear then occupy the same height on the page.
    const scale = size / vh;
    const x     = opts.x || 0;
    const y     = opts.y || 0;

    const g = parent.append('g').attr('class', 'bear' + (opts.class ? ' ' + opts.class : ''));
    if (opts.opacity != null) g.attr('opacity', opts.opacity);

    // Inner group holds the artwork in sprite coordinates; the outer group owns
    // position and scale so callers can move a bear without touching its size.
    const inner = g.append('g')
      .attr('class', 'bear-art')
      .attr('transform', 'translate(' + x + ',' + y + ') scale(' +
            (opts.flip ? -scale : scale) + ',' + scale + ')' +
            ' translate(' + (opts.flip ? -(vx + vw) : -vx) + ',' + (-vy) + ')');

    v.runs.forEach(function (r) {
      inner.append('rect')
        .attr('x', r[1]).attr('y', r[0])
        .attr('width', r[2]).attr('height', 1)
        .attr('shape-rendering', 'crispEdges')
        .attr('fill', pal[KEYS[r[3]]]);
    });

    g.node().__bearPal  = pal;
    g.node().__bearRuns = v.runs;
    return g;
  }

  /* Repaint an already-drawn bear in a new palette — used when the weights or
     the theme change and redrawing from scratch would be wasteful. */
  function repaint(g, pal, duration) {
    // The rects were appended in RUNS order, so the selection index is the run
    // index and the palette key can be looked up without storing data joins.
    const runs = g.node().__bearRuns || RUNS;
    g.selectAll('rect').each(function (d, i) {
      const fill = pal[KEYS[runs[i][3]]];
      const r = d3.select(this);
      if (duration) r.transition().duration(duration).attr('fill', fill);
      else r.attr('fill', fill);
    });
    g.node().__bearPal = pal;
    return g;
  }

  /* Standalone SVG string — for the footer, where pulling in D3 would be silly. */
  function markup(opts) {
    opts = opts || {};
    const pal  = opts.palette || DEFAULT_PALETTE;
    const size = opts.size == null ? 26 : opts.size;
    const v    = variant(opts.variant);
    let out = '<svg viewBox="' + v.view.join(' ') + '" width="' +
      (size * v.view[2] / v.view[3]).toFixed(1) + '" height="' + size +
      '" shape-rendering="crispEdges" focusable="false" aria-hidden="true">';
    v.runs.forEach(function (r) {
      out += '<rect x="' + r[1] + '" y="' + r[0] + '" width="' + r[2] +
             '" height="1" fill="' + pal[KEYS[r[3]]] + '"/>';
    });
    return out + '</svg>';
  }

  /* ── Footer mascot ────────────────────────────────────────────────────── */

  /* Called by main.js on every page that loads this file. Idempotent, so a page
     that also calls it directly cannot end up with two bears. */
  function mountFooter() {
    const footer = document.querySelector('footer');
    if (!footer || footer.querySelector('.footer-bear')) return null;
    const span = document.createElement('span');
    span.className = 'footer-bear';
    span.setAttribute('aria-hidden', 'true');
    span.innerHTML = markup({ size: 26 });
    footer.insertBefore(span, footer.firstChild);
    return span;
  }

  /* ── Section mascots ──────────────────────────────────────────────────── */

  /* Declarative mounting: a page writes
         <span class="page-mascot" data-bear="research"></span>
     next to its heading and this fills it in. Idempotent, so calling it again
     after a theme change or a late insert cannot double up. */
  function mountAll(root) {
    const nodes = (root || document).querySelectorAll('[data-bear]');
    Array.prototype.forEach.call(nodes, function (el) {
      if (el.firstElementChild) return;
      el.setAttribute('aria-hidden', 'true');
      el.innerHTML = markup({
        variant: el.getAttribute('data-bear'),
        size: Number(el.getAttribute('data-bear-size')) || 56
      });
    });
    return nodes.length;
  }

  window.EconVisBear = {
    W: W,
    H: H,
    LEG_ROW: LEG_ROW,
    RUNS: RUNS,
    KEYS: KEYS,
    defaultPalette: DEFAULT_PALETTE,
    palette: palette,
    monochrome: monochrome,
    isDark: isDark,
    draw: draw,
    repaint: repaint,
    markup: markup,
    variants: VARIANTS,
    variant: variant,
    mountFooter: mountFooter,
    mountAll: mountAll
  };
})();
