/* Panel admin: diálogos de edición, filtros en vivo, paleta Ctrl+K y avisos */
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // Menú lateral en móvil
  var b = $('#burger'), side = $('#side');
  if (b && side) b.addEventListener('click', function () { side.classList.toggle('open'); });

  // Aviso (toast)
  var t = $('#toast');
  if (t) setTimeout(function () { t.classList.add('out'); setTimeout(function () { t.remove(); }, 500); }, 4200);

  // Filtro en vivo: <input data-filtro="#contenedor"> filtra los .fila del contenedor
  $$('[data-filtro]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var q = inp.value.toLowerCase().trim();
      $$('.fila', $(inp.getAttribute('data-filtro'))).forEach(function (f) {
        f.style.display = !q || f.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
      });
    });
  });

  // Diálogos de crear/editar: botón [data-abrir="idDialogo"] con data-row (JSON de la fila)
  function preview(sel) { var img = $(sel.getAttribute('data-preview')); if (img) img.src = '../imagenes/' + sel.value; }
  function abrir(id, row) {
    var d = document.getElementById(id); if (!d) return;
    var f = $('form', d), nuevo = !row.id;
    f.reset();
    $$('input, textarea, select', f).forEach(function (el) {
      var n = el.name; if (!n || n === 'csrf' || n === 'accion') return;
      if (el.type === 'checkbox') { el.checked = nuevo ? (n === 'activo') : String(row[n]) === '1'; return; }
      if (row[n] !== undefined && row[n] !== null) el.value = row[n];
      else if (n === 'id') el.value = 0;
    });
    f.elements.id.value = row.id || 0;
    $$('[data-solo-nuevo]', f).forEach(function (l) { l.style.display = nuevo ? '' : 'none'; });
    var h = $('[data-titulo]', f); if (h) { var p = h.getAttribute('data-titulo').split('|'); h.textContent = nuevo ? p[0] : p[1]; }
    $$('select[data-preview]', f).forEach(preview);
    d.showModal();
  }
  $$('[data-abrir]').forEach(function (btn) {
    btn.addEventListener('click', function () { var r = {}; try { r = JSON.parse(btn.getAttribute('data-row') || '{}'); } catch (e) {} abrir(btn.getAttribute('data-abrir'), r); });
  });
  $$('[data-cerrar]').forEach(function (btn) { btn.addEventListener('click', function () { btn.closest('dialog').close(); }); });
  $$('select[data-preview]').forEach(function (s) { s.addEventListener('change', function () { preview(s); }); });
  $$('dialog.dlg').forEach(function (d) { d.addEventListener('click', function (e) { if (e.target === d) d.close(); }); });

  // Atajos desde la paleta (?nueva=1 / ?nuevo=1)
  var qs = new URLSearchParams(location.search);
  if (qs.get('nueva')) { var bc = $('[data-abrir=dlgCom]'); if (bc) bc.click(); }
  if (qs.get('nuevo')) { var be = $('[data-abrir=dlgEv]'); if (be) be.click(); }

  // Paleta de comandos Ctrl/Cmd + K
  var pal = $('#paleta'), palIn = $('#palIn'), items = $$('#palLista li'), sel = 0;
  function marcar() { items.forEach(function (li, i) { var a = $('a', li); a.classList.toggle('sel', i === sel); }); }
  function filtrar() {
    var q = palIn.value.toLowerCase().trim(); var vis = [];
    items.forEach(function (li) { var ok = !q || li.textContent.toLowerCase().indexOf(q) > -1; li.style.display = ok ? '' : 'none'; if (ok) vis.push(li); });
    sel = 0; items.forEach(function (li) { $('a', li).classList.remove('sel'); }); if (vis[0]) $('a', vis[0]).classList.add('sel');
  }
  function abrirPaleta() { if (!pal) return; palIn.value = ''; filtrar(); pal.showModal(); palIn.focus(); }
  var bp = $('#abrirPaleta'); if (bp) bp.addEventListener('click', abrirPaleta);
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); abrirPaleta(); }
  });
  if (pal) {
    palIn.addEventListener('input', filtrar);
    palIn.addEventListener('keydown', function (e) {
      var vis = items.filter(function (li) { return li.style.display !== 'none'; });
      var idx = vis.findIndex(function (li) { return $('a', li).classList.contains('sel'); });
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault(); idx = (idx + (e.key === 'ArrowDown' ? 1 : -1) + vis.length) % vis.length;
        items.forEach(function (li) { $('a', li).classList.remove('sel'); }); if (vis[idx]) $('a', vis[idx]).classList.add('sel');
      }
      if (e.key === 'Enter') { var cur = vis[Math.max(idx, 0)]; if (cur) location.href = $('a', cur).href; }
    });
    pal.addEventListener('click', function (e) { if (e.target === pal) pal.close(); });
  }
})();
