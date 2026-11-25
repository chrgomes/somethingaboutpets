/* Something About Pets – SVG pet animations (see assets/css/pets-anim.css) */
(function () {
  'use strict';
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var svgNS = 'http://www.w3.org/2000/svg';

  function onVisible(el, cb, threshold) {
    if (!('IntersectionObserver' in window)) { cb(); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { io.disconnect(); cb(); }
      });
    }, { threshold: threshold || 0.4 });
    io.observe(el);
  }

  /* 1. Marks below the fold wait until they are on screen before drawing. */
  document.querySelectorAll('.pet-mark--draw[data-draw-on-scroll]').forEach(function (mark) {
    if (reduce) { return; }
    mark.classList.add('pet-mark--waiting');
    onVisible(mark, function () { mark.classList.remove('pet-mark--waiting'); }, 0.6);
  });

  /* 3. Paw-print trail: builds alternating left/right paws along a gentle wave, then walks on scroll. */
  function pawPath() {
    var g = document.createElementNS(svgNS, 'g');
    var pad = document.createElementNS(svgNS, 'path');
    pad.setAttribute('d', 'M0 4c-5 0-10 5-10 10 0 3.5 2.5 5.5 5.5 5.5 2 0 3-1 4.5-1s2.5 1 4.5 1c3 0 5.5-2 5.5-5.5C10 9 5 4 0 4z');
    g.appendChild(pad);
    [[-9, -1, -25], [-3.5, -6, -8], [3.5, -6, 8], [9, -1, 25]].forEach(function (t) {
      var el = document.createElementNS(svgNS, 'ellipse');
      el.setAttribute('cx', 0); el.setAttribute('cy', 0); el.setAttribute('rx', 3.2); el.setAttribute('ry', 4.4);
      el.setAttribute('transform', 'translate(' + t[0] + ' ' + t[1] + ') rotate(' + t[2] + ')');
      g.appendChild(el);
    });
    return g;
  }

  document.querySelectorAll('.paw-trail').forEach(function (trail) {
    var svg = document.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', '0 0 1100 90');
    svg.setAttribute('aria-hidden', 'true');
    svg.setAttribute('focusable', 'false');
    var steps = 14;
    for (var i = 0; i < steps; i++) {
      var x = 40 + i * (1020 / (steps - 1));
      var side = i % 2 === 0 ? -1 : 1;
      var y = 45 + Math.sin(i / 2.2) * 10 + side * 11;
      var paw = pawPath();
      paw.setAttribute('class', 'paw');
      paw.setAttribute('transform', 'translate(' + x.toFixed(1) + ' ' + y.toFixed(1) + ') rotate(90) scale(1.05)');
      paw.style.animationDelay = (i * 0.17) + 's';
      // Position on an outer group so the CSS pop-in scale only affects the paw itself.
      var outer = document.createElementNS(svgNS, 'g');
      outer.setAttribute('transform', paw.getAttribute('transform'));
      paw.removeAttribute('transform');
      outer.appendChild(paw);
      svg.appendChild(outer);
    }
    trail.appendChild(svg);
    if (reduce) { return; }
    onVisible(trail, function () { trail.classList.add('is-walking'); }, 0.8);
  });

  /* 4. Hearts float up when someone hovers or taps a testimonial. */
  if (!reduce) {
    var HEART = 'M12 21s-7.5-4.6-10-9.3C.3 8.4 2 4.5 5.8 4.1 8.2 3.9 10.4 5.3 12 7.4c1.6-2.1 3.8-3.5 6.2-3.3C22 4.5 23.7 8.4 22 11.7 19.5 16.4 12 21 12 21z';
    var lastBurst = 0;
    document.querySelectorAll('.h2-testimonial-area .testimonial-wrap').forEach(function (card) {
      function burst() {
        var now = Date.now();
        if (now - lastBurst < 900) { return; }
        lastBurst = now;
        for (var i = 0; i < 5; i++) {
          var s = document.createElementNS(svgNS, 'svg');
          s.setAttribute('viewBox', '0 0 24 24');
          s.setAttribute('class', 'sap-heart');
          s.setAttribute('aria-hidden', 'true');
          var p = document.createElementNS(svgNS, 'path');
          p.setAttribute('d', HEART); p.setAttribute('fill', 'currentColor');
          s.appendChild(p);
          s.style.left = (30 + Math.random() * 40) + '%';
          s.style.top = (35 + Math.random() * 25) + '%';
          s.style.setProperty('--dx', (Math.random() * 60 - 30).toFixed(0) + 'px');
          s.style.setProperty('--rot', (Math.random() * 40 - 20).toFixed(0) + 'deg');
          s.style.animationDelay = (i * 0.12) + 's';
          s.addEventListener('animationend', function () { this.remove(); });
          (card.querySelector('.testimonial-content') || card).appendChild(s);
        }
      }
      card.addEventListener('mouseenter', burst);
      card.addEventListener('touchstart', burst, { passive: true });
    });
  }

  /* 5. Yarn-ball back-to-top button. */
  var yarn = document.querySelector('.yarn-top');
  if (yarn) {
    var toggle = function () { yarn.classList.toggle('is-visible', window.scrollY > 600); };
    window.addEventListener('scroll', toggle, { passive: true });
    toggle();
    yarn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
  }
})();
