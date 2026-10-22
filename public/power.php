<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

/* Interactive version of the "Where the MDE comes from" figure (Lecture 5).
   The two bell curves are drawn in points of the outcome, on a fixed axis, so
   changing the sample size visibly narrows them. Everything runs in the
   browser: no data are sent anywhere. */

require_login();

page_header('Power and MDE', 'materials.php');
page_head('Power and the MDE', 'Move the sliders to see how the sample size, the noise in the outcome and the size of the true effect change what an experiment can detect. Defaults: our grant lottery from Lecture 5.');
?>

      <div class="sim-controls pel-power-controls">
        <div class="slider-group">
          <label class="slider-label" for="pw-n">Firms in the experiment (N) <span id="pw-n-out"></span></label>
          <input type="range" id="pw-n" min="0" max="1000" step="1">
          <div class="pel-power-presets">
            <button type="button" data-n="100">100</button>
            <button type="button" data-n="290">290</button>
            <button type="button" data-n="800">800</button>
            <button type="button" data-n="1500">1,500</button>
          </div>
        </div>
        <div class="slider-group">
          <label class="slider-label" for="pw-tau">True effect (points) <span id="pw-tau-out"></span></label>
          <input type="range" id="pw-tau" min="0" max="10" step="0.1">
        </div>
        <div class="slider-group">
          <label class="slider-label" for="pw-sigma">Noise: s.d. of the outcome (σ) <span id="pw-sigma-out"></span></label>
          <input type="range" id="pw-sigma" min="2" max="20" step="0.1">
        </div>
        <div class="slider-group">
          <label class="slider-label" for="pw-p">Share of firms treated (P) <span id="pw-p-out"></span></label>
          <input type="range" id="pw-p" min="0.1" max="0.9" step="0.05">
        </div>
        <div class="slider-group">
          <label class="slider-label" for="pw-alpha">Significance level (α)</label>
          <select id="pw-alpha" class="pel-power-select">
            <option value="0.10">10%</option>
            <option value="0.05" selected>5%</option>
            <option value="0.01">1%</option>
          </select>
        </div>
        <div class="slider-group">
          <label class="slider-label" for="pw-target">Target power for the MDE</label>
          <select id="pw-target" class="pel-power-select">
            <option value="0.80" selected>80%</option>
            <option value="0.90">90%</option>
          </select>
        </div>
      </div>

      <div class="pel-power-chart">
        <svg id="pw-svg" viewBox="0 0 760 350" role="img" aria-label="Sampling distributions of the estimate with no effect and with the true effect"></svg>
        <p class="pel-power-legend">
          <span class="pel-power-key pel-power-key-null"></span> no effect: what the estimate looks like if the programme does nothing
          <span class="pel-power-key pel-power-key-alt"></span> true effect: what the estimate looks like if the effect is real
        </p>
      </div>

      <div class="pel-cards pel-power-stats">
        <article class="pel-card">
          <p class="pel-kicker">Standard error</p>
          <p class="pel-power-num" id="pw-se"></p>
          <p class="pel-card-text">σ · √(1 / (P(1−P)·N))</p>
        </article>
        <article class="pel-card">
          <p class="pel-kicker">Minimum detectable effect</p>
          <p class="pel-power-num" id="pw-mde"></p>
          <p class="pel-card-text" id="pw-mde-text"></p>
        </article>
        <article class="pel-card">
          <p class="pel-kicker">Power for the true effect</p>
          <p class="pel-power-num" id="pw-power"></p>
          <p class="pel-card-text" id="pw-power-text"></p>
        </article>
        <article class="pel-card">
          <p class="pel-kicker">Firms needed</p>
          <p class="pel-power-num" id="pw-need"></p>
          <p class="pel-card-text" id="pw-need-text"></p>
        </article>
      </div>

      <div class="callout callout-info">
        <p><strong>How to read it.</strong> The dashed line is the significance threshold: an estimate to its right is called significant. The shaded orange area is the share of experiments that detect the true effect (the power); the orange area to the left of the line is the share that miss it. A larger N shrinks both curves around their centres, so the line moves towards zero and more of the orange curve clears it.</p>
      </div>

<script>
(function () {
  'use strict';

  /* Standard normal cdf (Abramowitz-Stegun 7.1.26 via erf, error < 1.5e-7). */
  function cdf(x) {
    var t = 1 / (1 + 0.3275911 * Math.abs(x) / Math.SQRT2);
    var y = 1 - (((((1.061405429 * t - 1.453152027) * t) + 1.421413741) * t - 0.284496736) * t + 0.254829592) * t * Math.exp(-x * x / 2);
    return x >= 0 ? (1 + y) / 2 : (1 - y) / 2;
  }
  /* Inverse standard normal (Acklam), enough for 0.5 < p < 0.9995. */
  function inv(p) {
    var a = [-39.69683028665376, 220.9460984245205, -275.9285104469687, 138.3577518672690, -30.66479806614716, 2.506628274631000];
    var b = [-54.47609879822406, 161.5858368580409, -155.6989798598866, 66.80131188771972, -13.28068155288572];
    var c = [-0.007784894002430293, -0.3223964580411365, -2.400758277161838, -2.549732539343734, 4.374664141464968, 2.938163982698783];
    var d = [0.007784695709041462, 0.3224671290700398, 2.445134137142996, 3.754408661907416];
    var q, r;
    if (p > 0.97575) {
      q = Math.sqrt(-2 * Math.log(1 - p));
      return -(((((c[0] * q + c[1]) * q + c[2]) * q + c[3]) * q + c[4]) * q + c[5]) / ((((d[0] * q + d[1]) * q + d[2]) * q + d[3]) * q + 1);
    }
    q = p - 0.5; r = q * q;
    return (((((a[0] * r + a[1]) * r + a[2]) * r + a[3]) * r + a[4]) * r + a[5]) * q / (((((b[0] * r + b[1]) * r + b[2]) * r + b[3]) * r + b[4]) * r + 1);
  }

  /* N slider is logarithmic: 20 to 3000 firms. */
  var NMIN = 20, NMAX = 3000;
  function nFromPos(pos) { return Math.round(NMIN * Math.pow(NMAX / NMIN, pos / 1000)); }
  function posFromN(n) { return Math.round(1000 * Math.log(n / NMIN) / Math.log(NMAX / NMIN)); }
  var nExact = null;   /* set by the preset buttons, cleared when the slider moves */

  var el = function (id) { return document.getElementById(id); };
  var inN = el('pw-n'), inTau = el('pw-tau'), inSigma = el('pw-sigma'), inP = el('pw-p'),
      inAlpha = el('pw-alpha'), inTarget = el('pw-target'), svg = el('pw-svg');

  inN.value = posFromN(100); inTau.value = 3; inSigma.value = 9.1; inP.value = 0.5;

  var fmt = function (x, d) { return x.toLocaleString('en-GB', { minimumFractionDigits: d, maximumFractionDigits: d }); };
  var pct = function (x) { return Math.round(100 * x) + '%'; };
  var pctPow = function (x) { return x > 0.995 ? '>99%' : pct(x); };

  /* Fixed horizontal axis, in points, so the curves visibly narrow with N. */
  var XMIN = -12, XMAX = 18, W = 760, H = 350, L = 30, R = 20, T = 34, B = 64;
  var sx = function (x) { return L + (x - XMIN) / (XMAX - XMIN) * (W - L - R); };
  var sy = function (f) { return H - B - f * (H - B - T); };   /* f in [0,1]: share of the peak height */
  var NS = 'http://www.w3.org/2000/svg';

  function node(tag, attrs, text) {
    var e = document.createElementNS(NS, tag);
    for (var k in attrs) e.setAttribute(k, attrs[k]);
    if (text !== undefined) e.textContent = text;
    svg.appendChild(e);
    return e;
  }
  /* Bell curve centred on m with spread se, scaled so that its peak has height 1. */
  function path(m, se, from, to, closed) {
    from = Math.max(from, XMIN); to = Math.min(to, XMAX);
    if (to <= from) return '';
    var d = '', steps = 240;
    for (var i = 0; i <= steps; i++) {
      var x = from + (to - from) * i / steps, z = (x - m) / se;
      d += (i ? 'L' : 'M') + sx(x).toFixed(1) + ' ' + sy(Math.exp(-z * z / 2)).toFixed(1);
    }
    if (closed) d += 'L' + sx(to).toFixed(1) + ' ' + sy(0) + 'L' + sx(from).toFixed(1) + ' ' + sy(0) + 'Z';
    return d;
  }

  function draw() {
    var n = nExact || nFromPos(+inN.value), tau = +inTau.value, sigma = +inSigma.value, p = +inP.value,
        alpha = +inAlpha.value, target = +inTarget.value;
    var za = inv(1 - alpha / 2), zb = inv(target);
    var se = sigma * Math.sqrt(1 / (p * (1 - p) * n));
    var crit = za * se, mde = (za + zb) * se;
    var power = tau > 0 ? (1 - cdf(za - tau / se)) + cdf(-za - tau / se) : alpha;
    var need = tau > 0 ? Math.ceil(Math.pow(za + zb, 2) * sigma * sigma / (p * (1 - p) * tau * tau)) : Infinity;

    el('pw-n-out').textContent = fmt(n, 0);
    el('pw-tau-out').textContent = fmt(tau, 1);
    el('pw-sigma-out').textContent = fmt(sigma, 1);
    el('pw-p-out').textContent = pct(p);

    el('pw-se').textContent = fmt(se, 2) + ' points';
    el('pw-mde').textContent = fmt(mde, 1) + ' points';
    el('pw-mde-text').textContent = '(' + fmt(za, 2) + ' + ' + fmt(zb, 2) + ') × s.e. = ' + fmt(za + zb, 2) + ' × s.e.: the smallest effect detected ' + pct(target) + ' of the time';
    el('pw-power').textContent = pctPow(power);
    el('pw-power-text').textContent = tau > 0
      ? 'Share of experiments with ' + fmt(n, 0) + ' firms that would find a true ' + fmt(tau, 1) + '-point effect significant'
      : 'With no true effect, this is just the false-positive rate α';
    el('pw-need').textContent = isFinite(need) ? fmt(need, 0) : '—';
    el('pw-need-text').textContent = isFinite(need)
      ? 'To detect ' + fmt(tau, 1) + ' points with ' + pct(target) + ' power at α = ' + pct(alpha)
      : 'Set a true effect above zero';

    while (svg.firstChild) svg.removeChild(svg.firstChild);
    var cs = getComputedStyle(document.documentElement);
    var col = function (v, fb) { return (cs.getPropertyValue(v) || fb).trim(); };
    var cNull = col('--chart-contrast', '#3E6C8E'), cAlt = col('--accent', '#C0552B'),
        cText = col('--text', '#211F1B'), cMuted = col('--text-muted', '#6E6A5E'), cBorder = col('--border', '#DED8C9');

    /* axis and ticks */
    node('line', { x1: sx(XMIN), x2: sx(XMAX), y1: sy(0), y2: sy(0), stroke: cBorder, 'stroke-width': 1 });
    for (var t = -10; t <= 18; t += 2) {
      node('line', { x1: sx(t), x2: sx(t), y1: sy(0), y2: sy(0) + 5, stroke: cBorder });
      node('text', { x: sx(t), y: sy(0) + 18, 'text-anchor': 'middle', 'font-size': 11, fill: cMuted }, t);
    }
    node('text', { x: sx((XMIN + XMAX) / 2), y: H - 6, 'text-anchor': 'middle', 'font-size': 12, fill: cMuted }, 'estimated effect on sales growth (points)');

    /* shaded areas: power (orange, right of the line) and false positives (blue, right tail) */
    if (tau > 0) node('path', { d: path(tau, se, crit, tau + 6 * se, true), fill: cAlt, 'fill-opacity': 0.18, stroke: 'none' });
    node('path', { d: path(0, se, crit, 6 * se, true), fill: cNull, 'fill-opacity': 0.30, stroke: 'none' });

    /* the two curves */
    node('path', { d: path(0, se, -6 * se, 6 * se, false), fill: 'none', stroke: cNull, 'stroke-width': 2.2 });
    if (tau > 0) node('path', { d: path(tau, se, tau - 6 * se, tau + 6 * se, false), fill: 'none', stroke: cAlt, 'stroke-width': 2.2 });

    /* significance line */
    if (crit < XMAX) {
      node('line', { x1: sx(crit), x2: sx(crit), y1: sy(0), y2: sy(1.08), stroke: cText, 'stroke-width': 1.2, 'stroke-dasharray': '5 4' });
      node('text', { x: sx(crit), y: sy(1.08) - 6, 'text-anchor': 'middle', 'font-size': 11, fill: cText }, 'significance line: ' + fmt(crit, 1));
    }
    /* MDE marker on the axis */
    if (mde < XMAX) {
      node('line', { x1: sx(mde), x2: sx(mde), y1: sy(0), y2: sy(0) + 24, stroke: cText, 'stroke-width': 1 });
      node('path', { d: 'M' + sx(mde) + ' ' + (sy(0) + 22) + 'l-5 9h10z', fill: cText });
      node('text', { x: sx(mde) + 8, y: sy(0) + 34, 'text-anchor': 'start', 'font-size': 11, 'font-weight': 600, fill: cText }, 'MDE ' + fmt(mde, 1));
    }
    /* curve labels and the power share */
    node('text', { x: sx(Math.max(XMIN + 1.5, -1.2 * se - 1)), y: sy(0.92), 'text-anchor': 'end', 'font-size': 12, fill: cNull }, 'no effect');
    if (tau > 0 && tau < XMAX) {
      node('text', { x: sx(Math.min(tau + 0.9 * se + 0.6, XMAX - 0.5)), y: sy(0.92), 'text-anchor': 'start', 'font-size': 12, fill: cAlt }, 'true effect = ' + fmt(tau, 1));
      if (crit < XMAX) node('text', { x: Math.min(sx(Math.max(crit, tau - 0.3 * se)) + 10, sx(XMAX) - 90), y: sy(0.32), 'text-anchor': 'start', 'font-size': 12, 'font-weight': 600, fill: cAlt }, 'power ' + pctPow(power));
    }
  }

  inN.addEventListener('input', function () { nExact = null; draw(); });
  [inTau, inSigma, inP].forEach(function (i) { i.addEventListener('input', draw); });
  [inAlpha, inTarget].forEach(function (i) { i.addEventListener('change', draw); });
  Array.prototype.forEach.call(document.querySelectorAll('.pel-power-presets button'), function (b) {
    b.addEventListener('click', function () { nExact = +b.getAttribute('data-n'); inN.value = posFromN(nExact); draw(); });
  });
  document.addEventListener('econvis:theme', draw);   /* repaint after the dark-mode toggle */
  draw();
})();
</script>

<?php page_footer();
