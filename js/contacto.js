/* Contacto: contador de caracteres, aviso de envío y muro público */
(function () {
  var msg = document.getElementById('mensaje'), cnt = document.getElementById('ctCount');
  if (msg && cnt) {
    var upd = function () { cnt.textContent = msg.value.length + ' / 1000'; };
    msg.addEventListener('input', upd); upd();
  }

  // Si el envío salió bien, mostramos la animación y ocultamos el formulario
  var params = new URLSearchParams(location.search);
  if (params.get('enviado')) {
    var ok = document.getElementById('enviado'), form = document.getElementById('ctForm'), caja = document.getElementById('mensaje-form');
    if (ok && form) { ok.hidden = false; form.hidden = true; if (caja) caja.hidden = true; }
  }

  // Botón "Pregúntale a Yalu"
  var y = document.getElementById('abrirYalu');
  if (y) y.addEventListener('click', function () { var f = document.getElementById('yaluFab'); if (f) f.click(); });

  // Muro público (php/muro.php)
  var lista = document.getElementById('muroLista'), todos = [], filtro = '';
  var TIPOS = { duda: 'Duda', idea: 'Idea', comunidad: 'Comunidad', problema: 'Problema', otro: 'Otro' };
  function el(tag, cls, txt) { var e = document.createElement(tag); if (cls) e.className = cls; if (txt != null) e.textContent = txt; return e; }
  function fechaCorta(s) { var d = new Date(s.replace(' ', 'T')); return isNaN(d) ? '' : d.toLocaleDateString('es-CO', { day: 'numeric', month: 'short', year: 'numeric' }); }

  function pintar() {
    lista.innerHTML = '';
    var items = todos.filter(function (m) { return !filtro || m.tipo === filtro; });
    if (!items.length) { lista.appendChild(el('p', 'ct-empty', todos.length ? 'No hay mensajes de este tipo todavía.' : 'Aún no hay mensajes publicados. ¡Escribe el primero!')); return; }
    items.forEach(function (m) {
      var post = el('article', 'ct-post');
      var q = el('div', 'ct-post__q'), top = el('div', 'ct-post__top');
      top.appendChild(el('div', 'ct-av', (m.nombre || '?').charAt(0).toUpperCase()));
      var who = el('div', 'ct-post__who'); who.appendChild(el('strong', '', m.nombre)); who.appendChild(el('span', '', fechaCorta(m.fecha)));
      top.appendChild(who); top.appendChild(el('span', 'ct-tag', TIPOS[m.tipo] || 'Mensaje'));
      q.appendChild(top); q.appendChild(el('div', 'ct-post__subject', m.asunto)); q.appendChild(el('p', 'ct-post__msg', m.mensaje));
      var a = el('div', 'ct-post__a'), h = el('header');
      var img = document.createElement('img'); img.src = '../imagenes/yalu-avatar.png'; img.alt = '';
      h.appendChild(img); h.appendChild(document.createTextNode('Respuesta de ' + (m.autor || 'el equipo') + ' · equipo Multiverse'));
      a.appendChild(h); a.appendChild(el('p', '', m.respuesta));
      post.appendChild(q); post.appendChild(a); lista.appendChild(post);
    });
  }

  document.querySelectorAll('.ct-filters button').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('.ct-filters button').forEach(function (x) { x.classList.remove('is-on'); });
      b.classList.add('is-on'); filtro = b.getAttribute('data-f'); pintar();
    });
  });

  if (lista) {
    fetch('../php/muro.php').then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .then(function (d) { todos = Array.isArray(d) ? d : []; pintar(); })
      .catch(function () { lista.innerHTML = ''; lista.appendChild(el('p', 'ct-empty', 'El muro se muestra cuando el sitio corre con PHP y la base de datos (XAMPP).')); });
  }
})();
