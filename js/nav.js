/* Menú hamburguesa para pantallas pequeñas. No requiere cambios en el HTML. */
(function () {
  var bar = document.querySelector('.navbar');
  var inner = bar && bar.querySelector('.navbar__inner');
  if (!inner || !inner.querySelector('.navbar__nav')) return;
  var b = document.createElement('button');
  b.type = 'button'; b.className = 'navbar__burger'; b.setAttribute('aria-label', 'Abrir menú'); b.setAttribute('aria-expanded', 'false');
  b.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>';
  b.addEventListener('click', function () {
    var open = bar.classList.toggle('is-open');
    b.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  var nav = inner.querySelector('.navbar__nav');
  inner.insertBefore(b, nav.nextSibling);
  document.addEventListener('click', function (e) { if (!bar.contains(e.target)) bar.classList.remove('is-open'); });
})();
