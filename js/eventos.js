/* Agenda de eventos: lee php/api_eventos.php y, si no hay PHP, js/eventos.json */
(function () {
  var CATS = ['Todos', 'Anime', 'Cosplay', 'Videojuegos', 'Tecnología', 'Libros', 'Cine', 'Música', 'Deportes'];
  var MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  var MES3 = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  var data = [], cat = 'Todos';
  var $ = function (id) { return document.getElementById(id); };
  function el(t, c, x) { var e = document.createElement(t); if (c) e.className = c; if (x != null) e.textContent = x; return e; }
  function parse(s) { var p = String(s).split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }

  function cargar() {
    return fetch('../php/api_eventos.php').then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .catch(function () { return fetch('../js/eventos.json').then(function (r) { return r.json(); }); });
  }

  function chips() {
    var box = $('evCats'); box.innerHTML = '';
    CATS.forEach(function (c) {
      var b = el('button', c === cat ? 'is-on' : '', c); b.type = 'button';
      b.addEventListener('click', function () { cat = c; chips(); pintar(); });
      box.appendChild(b);
    });
  }

  function pintar() {
    var hoy = new Date(); hoy.setHours(0, 0, 0, 0);
    var ciudad = $('evCiudad').value, q = $('evBuscar').value.trim().toLowerCase(), soloOk = $('evOk').checked;
    var lista = data.filter(function (e) { return parse(e.fecha_fin || e.fecha_inicio) >= hoy; })
      .filter(function (e) { return cat === 'Todos' || e.categoria === cat; })
      .filter(function (e) { return !ciudad || e.ciudad === ciudad; })
      .filter(function (e) { return !soloOk || +e.confirmado === 1; })
      .filter(function (e) { return !q || (e.titulo + ' ' + e.lugar + ' ' + e.ciudad).toLowerCase().indexOf(q) > -1; })
      .sort(function (a, b) { return a.fecha_inicio < b.fecha_inicio ? -1 : 1; });

    $('evCount').textContent = lista.length + (lista.length === 1 ? ' evento' : ' eventos') + ' por venir';
    var cont = $('evLista'); cont.innerHTML = '';
    if (!lista.length) {
      var v = el('div', 'ev-empty', 'No hay eventos con esos filtros.');
      var r = el('button', '', 'Quitar filtros'); r.type = 'button';
      r.addEventListener('click', function () { cat = 'Todos'; $('evCiudad').value = ''; $('evBuscar').value = ''; $('evOk').checked = false; chips(); pintar(); });
      v.appendChild(document.createElement('br')); v.appendChild(r); cont.appendChild(v); return;
    }
    var grupos = {}, orden = [];
    lista.forEach(function (e) {
      var d = parse(e.fecha_inicio), k = d.getFullYear() + '-' + d.getMonth();
      if (!grupos[k]) { grupos[k] = { t: MESES[d.getMonth()] + ' ' + d.getFullYear(), items: [] }; orden.push(k); }
      grupos[k].items.push(e);
    });
    orden.forEach(function (k) {
      var sec = el('div', 'ev-month'); sec.appendChild(el('h2', '', grupos[k].t));
      var grid = el('div', 'ev-grid');
      grupos[k].items.forEach(function (e) { grid.appendChild(tarjeta(e)); });
      sec.appendChild(grid); cont.appendChild(sec);
    });
  }

  function tarjeta(e) {
    var d = parse(e.fecha_inicio), c = el('article', 'ev-card');
    var im = el('div', 'ev-card__img'), img = document.createElement('img');
    img.src = '../imagenes/' + e.imagen; img.alt = ''; img.loading = 'lazy'; im.appendChild(img);
    var dt = el('div', 'ev-date'); dt.appendChild(el('strong', '', d.getDate())); dt.appendChild(el('span', '', MES3[d.getMonth()])); im.appendChild(dt);
    im.appendChild(el('span', 'ev-badge ' + (+e.confirmado ? 'ev-badge--ok' : 'ev-badge--est'), +e.confirmado ? '✓ Fecha confirmada' : 'Fecha estimada'));
    var b = el('div', 'ev-card__body');
    b.appendChild(el('div', 'ev-card__cat', e.categoria));
    b.appendChild(el('h3', '', e.titulo));
    b.appendChild(el('div', 'ev-card__where', '📍 ' + e.lugar + ', ' + e.ciudad));
    b.appendChild(el('div', 'ev-card__when', '🗓️ ' + e.fecha_texto));
    b.appendChild(el('p', 'ev-card__desc', e.descripcion));
    if (e.enlace) { var a = el('a', 'ev-card__cta', 'Ver sitio oficial'); a.href = e.enlace; a.target = '_blank'; a.rel = 'noopener'; b.appendChild(a); }
    else b.appendChild(el('span', 'ev-card__nolink', 'Aún sin enlace oficial'));
    c.appendChild(im); c.appendChild(b); return c;
  }

  cargar().then(function (d) {
    data = d;
    var ciudades = {}; d.forEach(function (e) { ciudades[e.ciudad] = 1; });
    Object.keys(ciudades).sort().forEach(function (c) { var o = el('option', '', c); o.value = c; $('evCiudad').appendChild(o); });
    chips(); pintar();
  }).catch(function () { $('evLista').appendChild(el('div', 'ev-empty', 'No se pudieron cargar los eventos.')); });

  ['evCiudad', 'evOk'].forEach(function (i) { $(i).addEventListener('change', pintar); });
  $('evBuscar').addEventListener('input', pintar);
})();
