/* =========================================================
   YALU — asistente del Multiverse of Madness
   - Botón flotante (logo + nombre) abajo a la derecha
   - Anuncios cerrables de lo que viene (solo en el inicio)
   - Chat tipo mensajería: responde dudas y recomienda comunidades
   Funciona sin servidor: el "cerebro" son reglas por palabras clave.
   Uso:  <script src="js/yalu.js" data-news="true" defer></script>
   ========================================================= */
(function () {
  'use strict';

  var script = document.currentScript;
  var BASE = script.src.replace(/js\/yalu\.js.*$/, '');      // raíz del sitio, funcione desde / o /html/
  var SHOW_NEWS = script.getAttribute('data-news') === 'true';
  var AVATAR = BASE + 'imagenes/yalu-avatar.png';

  /* ---------- Conocimiento: comunidades (coincide con la tabla `comunidades`) ---------- */
  var COMS = [
    { page: 'anime', nombre: 'Templo Sagrado', icon: '⛩️', tema: 'Anime y manga',
      desc: 'Anime, manga y cultura otaku.',
      kw: ['anime', 'manga', 'otaku', 'naruto', 'one piece', 'goku', 'dragon ball', 'shonen', 'waifu', 'japon', 'jujutsu', 'ghibli', 'cultura japonesa'] },
    { page: 'cosplay', nombre: 'Cosplayers', icon: '🎭', tema: 'Cosplay',
      desc: 'Cosplay, maquillaje y confección de trajes.',
      kw: ['cosplay', 'disfraz', 'disfrazar', 'maquillaje', 'traje', 'peluca', 'props', 'confeccion', 'costura', 'caracterizacion', 'cosplayer'] },
    { page: 'video_juegos', nombre: 'Gamers', icon: '🎮', tema: 'Videojuegos',
      desc: 'Videojuegos en consola, PC y móvil.',
      kw: ['juego', 'juegos', 'jugar', 'videojuego', 'gamer', 'gaming', 'ps5', 'ps4', 'xbox', 'nintendo', 'switch', 'minecraft', 'fortnite', 'valorant', 'free fire', 'lol', 'esports', 'consola', 'streamer', 'roblox', 'zelda', 'pokemon'] },
    { page: 'tecnologia', nombre: 'Mundo Informático', icon: '💻', tema: 'Tecnología',
      desc: 'Tecnología, programación y gadgets.',
      kw: ['tecnologia', 'programar', 'programacion', 'codigo', 'python', 'java', 'javascript', 'html', 'pc', 'computador', 'gadget', 'celular', 'app', 'web', 'inteligencia artificial', 'ia', 'robotica', 'hacker', 'software', 'hardware', 'arduino', 'desarrollo'] },
    { page: 'libros', nombre: 'Biblioteca', icon: '📚', tema: 'Libros',
      desc: 'Lectura, novelas y recomendaciones.',
      kw: ['libro', 'libros', 'leer', 'lectura', 'novela', 'wattpad', 'saga', 'escribir', 'escritor', 'poesia', 'fantasia', 'cuento', 'autor', 'literatura', 'lector'] },
    { page: 'peliculas', nombre: 'Cine Mundial', icon: '🎬', tema: 'Cine',
      desc: 'Películas, series y cine de autor.',
      kw: ['pelicula', 'peliculas', 'cine', 'serie', 'series', 'netflix', 'marvel', 'director', 'actor', 'terror', 'documental', 'cinefilo', 'estreno', 'hbo', 'disney'] },
    { page: 'musica', nombre: 'Frecuencia', icon: '🎵', tema: 'Música',
      desc: 'Música, conciertos y festivales.',
      kw: ['musica', 'cancion', 'canciones', 'concierto', 'festival', 'banda', 'cantar', 'guitarra', 'rock', 'reggaeton', 'pop', 'kpop', 'k pop', 'rap', 'salsa', 'playlist', 'cantante', 'dj', 'piano'] },
    { page: 'deportes', nombre: 'Zona Atlética', icon: '🏅', tema: 'Deportes',
      desc: 'Deporte, entrenamiento y competencia.',
      kw: ['deporte', 'deportes', 'futbol', 'gym', 'gimnasio', 'correr', 'entrenar', 'entrenamiento', 'basket', 'baloncesto', 'ciclismo', 'natacion', 'atletismo', 'voleibol', 'tenis', 'fitness', 'competencia'] }
  ];

  /* ---------- Anuncios por defecto (el admin puede cambiarlos desde php/admin.php) ---------- */
  var NEWS_DEFAULT = [
    { id: 'n-universos', icono: '🪐', etiqueta: 'Próximamente', titulo: 'Cuatro universos nuevos', texto: 'Arte y Dibujo, Ciencia y Espacio, Manualidades y Culturas del Mundo.' },
    { id: 'n-plus', icono: '✨', etiqueta: 'Próximamente', titulo: 'Multiverse Plus', texto: 'Personaliza tu perfil y lleva tus comunidades más lejos.' },
    { id: 'n-chat', icono: '💬', etiqueta: 'En camino', titulo: 'Chat entre miembros', texto: 'Conversaciones en tiempo real dentro de cada comunidad.' }
  ];

  var EVENTS_FALLBACK = [
    { titulo: 'Comic Con Medellín', ciudad: 'Medellín', lugar: 'Plaza Mayor', fecha_texto: '27–29 nov 2026', fecha_inicio: '2026-11-27', fecha_fin: '2026-11-29', categoria: 'Anime' },
    { titulo: 'Festival Estéreo Picnic', ciudad: 'Bogotá', lugar: 'Parque Simón Bolívar', fecha_texto: '19–21 mar 2027', fecha_inicio: '2027-03-19', fecha_fin: '2027-03-21', categoria: 'Música' },
    { titulo: 'Festival Internacional de Cine de Cartagena', ciudad: 'Cartagena', lugar: 'Cartagena de Indias', fecha_texto: '6–11 abr 2027', fecha_inicio: '2027-04-06', fecha_fin: '2027-04-11', categoria: 'Cine' }
  ];

  /* ---------- Utilidades ---------- */
  function norm(s) {
    return ' ' + String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim() + ' ';
  }
  function has(n, words) {
    for (var i = 0; i < words.length; i++) { if (n.indexOf(' ' + words[i]) > -1) return true; }
    return false;
  }
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }
  // **negrita** y [texto](enlace) -> HTML seguro
  function fmt(s) {
    return esc(s)
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (m, t, u) {
        var href = /^https?:/.test(u) ? u : BASE + u;
        var ext = /^https?:/.test(u) && u.indexOf(BASE) !== 0;
        return '<a href="' + href + '"' + (ext ? ' target="_blank" rel="noopener"' : '') + '>' + t + '</a>';
      });
  }
  function hora() { return new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }); }
  function store(k, v) { try { if (v === undefined) return JSON.parse(sessionStorage.getItem(k)); sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) { return null; } }
  function lstore(k, v) { try { if (v === undefined) return JSON.parse(localStorage.getItem(k)); localStorage.setItem(k, JSON.stringify(v)); } catch (e) { return null; } }
  function comUrl(c) { return 'html/' + c.page + '.html'; }

  var TICK = '<svg class="yalu-tick" viewBox="0 0 16 10" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 5.5 4.5 9 11 1.5M6.5 7l1.5 1.5L14.5 1.5"/></svg>';
  var BOT_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="8" width="16" height="12" rx="3"/><path d="M12 8V4M9 13v1.5M15 13v1.5"/><circle cx="12" cy="3.5" r="1"/></svg>';

  /* ---------- Construcción de la interfaz ---------- */
  var root = document.createElement('div');
  root.className = 'yalu';
  root.innerHTML =
    '<div class="yalu-news" id="yaluNews" aria-live="polite"></div>' +
    '<div class="yalu-win" role="dialog" aria-label="Chat con Yalu, asistente del Multiverso">' +
      '<header class="yalu-head">' +
        '<div class="yalu-head__av"><img src="' + AVATAR + '" alt=""><span class="yalu-dot"></span></div>' +
        '<div class="yalu-head__info">' +
          '<div class="yalu-head__row"><span class="yalu-head__name">Yalu</span><span class="yalu-badge">' + BOT_ICON + 'robot</span></div>' +
          '<div class="yalu-head__status">en línea · te responde al instante</div>' +
        '</div>' +
        '<button type="button" class="yalu-iconbtn" data-act="reset" aria-label="Reiniciar conversación" title="Reiniciar conversación"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg></button>' +
        '<button type="button" class="yalu-iconbtn" data-act="close" aria-label="Cerrar chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>' +
      '</header>' +
      '<div class="yalu-body" id="yaluBody"><div class="yalu-wall" aria-hidden="true"></div><div class="yalu-scroll" id="yaluScroll" role="log" aria-live="polite"><div class="yalu-day">Hoy</div></div></div>' +
      '<div class="yalu-chips" id="yaluChips"></div>' +
      '<form class="yalu-form" id="yaluForm" autocomplete="off">' +
        '<input class="yalu-input" id="yaluInput" type="text" maxlength="300" placeholder="Escribe tu mensaje…" aria-label="Escribe tu mensaje">' +
        '<button class="yalu-send" type="submit" aria-label="Enviar"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.4 20.4 21 12 3.4 3.6 3.3 10l12.6 2-12.6 2z"/></svg></button>' +
      '</form>' +
    '</div>' +
    '<button type="button" class="yalu-fab" id="yaluFab" aria-label="Abrir chat con Yalu">' +
      '<img src="' + AVATAR + '" alt="">' +
      '<span class="yalu-fab__txt"><span class="yalu-fab__name">Yalu</span><span class="yalu-fab__sub"><span class="yalu-dot"></span>asistente</span></span>' +
    '</button>';
  document.body.appendChild(root);

  var elScroll = root.querySelector('#yaluScroll'), elBody = root.querySelector('#yaluBody'),
      elChips = root.querySelector('#yaluChips'), elForm = root.querySelector('#yaluForm'),
      elInput = root.querySelector('#yaluInput'), elNews = root.querySelector('#yaluNews'),
      elFab = root.querySelector('#yaluFab');

  /* ---------- Anuncios ---------- */
  function renderNews(list) {
    var hidden = lstore('yalu_news_hidden') || [];
    list.filter(function (n) { return hidden.indexOf(n.id) < 0; }).slice(0, 3).forEach(function (n, i) {
      var a = document.createElement('article');
      a.className = 'yalu-news__item';
      a.style.animationDelay = (1.2 + i * 0.45) + 's';
      a.innerHTML =
        '<div class="yalu-news__icon">' + esc(n.icono || '✨') + '</div>' +
        '<div><span class="yalu-news__tag">' + esc(n.etiqueta || 'Próximamente') + '</span>' +
        '<div class="yalu-news__title">' + esc(n.titulo) + '</div>' +
        '<p class="yalu-news__text">' + esc(n.texto || '') + '</p></div>' +
        '<button type="button" class="yalu-news__close" aria-label="Cerrar anuncio">×</button>';
      a.querySelector('button').addEventListener('click', function () {
        var h = lstore('yalu_news_hidden') || []; h.push(n.id); lstore('yalu_news_hidden', h);
        a.classList.add('is-leaving'); setTimeout(function () { a.remove(); }, 300);
      });
      a.style.opacity = 0;                       // se muestra cuando arranca su animación
      a.addEventListener('animationstart', function () { a.style.opacity = ''; });
      elNews.appendChild(a);
    });
  }
  if (SHOW_NEWS) {
    var ctl = ('AbortController' in window) ? new AbortController() : null;
    var timer = setTimeout(function () { if (ctl) ctl.abort(); }, 2500);
    fetch(BASE + 'php/anuncios.php', ctl ? { signal: ctl.signal } : {})
      .then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .then(function (d) { clearTimeout(timer); renderNews(Array.isArray(d) && d.length ? d : NEWS_DEFAULT); })
      .catch(function () { clearTimeout(timer); renderNews(NEWS_DEFAULT); });
  }

  /* ---------- Eventos (js/eventos.json, el mismo que usa la página de eventos) ---------- */
  var events = null;
  function loadEvents(cb) {
    if (events) return cb(events);
    fetch(BASE + 'php/api_eventos.php').then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .catch(function () { return fetch(BASE + 'js/eventos.json').then(function (r) { if (!r.ok) throw 0; return r.json(); }); })
      .then(function (d) { if (!d || !d.length) throw 0; events = d; cb(events); })
      .catch(function () { events = EVENTS_FALLBACK; cb(events); });
  }

  /* ---------- Mensajes ---------- */
  var history = store('yalu_hist') || [];
  var busy = false;

  function addBubble(from, inner, opts) {
    opts = opts || {};
    var ts = opts.ts || hora();
    var m = document.createElement('div');
    m.className = 'yalu-msg yalu-msg--' + (from === 'u' ? 'user' : 'bot');
    var last = elScroll.lastElementChild;
    var tail = !(last && last.classList.contains('yalu-msg--' + (from === 'u' ? 'user' : 'bot')));
    m.innerHTML = '<div class="yalu-bub' + (tail ? ' has-tail' : '') + '">' + inner +
      '<div class="yalu-meta"><span>' + ts + '</span>' + (from === 'u' ? TICK : '') + '</div></div>';
    elScroll.appendChild(m);
    elBody.scrollTop = elBody.scrollHeight;
    if (!opts.restore) { history.push({ f: from, h: inner, ts: ts }); store('yalu_hist', history.slice(-40)); }
    return m;
  }

  function partToHtml(p) {
    if (typeof p === 'string') return '<p>' + fmt(p).replace(/\n/g, '<br>') + '</p>';
    var h = '';
    if (p.text) h += '<p>' + fmt(p.text).replace(/\n/g, '<br>') + '</p>';
    if (p.cards) p.cards.forEach(function (c) {
      h += '<a class="yalu-card" href="' + BASE + comUrl(c) + '"><span class="yalu-card__ico">' + c.icon + '</span>' +
        '<span><div class="yalu-card__t">' + esc(c.nombre) + '</div><div class="yalu-card__d">' + esc(c.desc) + '</div></span>' +
        '<span class="yalu-card__go">↗</span></a>';
    });
    if (p.btn) h += '<a class="yalu-btnlink" href="' + (/^https?:/.test(p.btn.u) ? p.btn.u : BASE + p.btn.u) + '">' + esc(p.btn.t) + '</a>';
    return h;
  }

  function setChips(list) {
    elChips.innerHTML = '';
    (list || []).forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'yalu-chip'; b.textContent = t;
      b.addEventListener('click', function () { send(t); });
      elChips.appendChild(b);
    });
  }

  function typing() {
    var m = document.createElement('div');
    m.className = 'yalu-msg yalu-msg--bot';
    m.innerHTML = '<div class="yalu-bub has-tail yalu-typing" aria-label="Yalu está escribiendo"><i></i><i></i><i></i></div>';
    elScroll.appendChild(m); elBody.scrollTop = elBody.scrollHeight;
    return m;
  }

  // Yalu responde: una o varias burbujas con "escribiendo…" entre cada una
  function botSay(parts, chips, done) {
    busy = true; setChips([]);
    var i = 0;
    (function next() {
      if (i >= parts.length) { busy = false; setChips(chips); if (done) done(); return; }
      var t = typing();
      var html = partToHtml(parts[i]);
      var wait = Math.min(1300, 380 + html.length * 3);
      setTimeout(function () { t.remove(); addBubble('b', html); i++; next(); }, wait);
    })();
  }

  /* ---------- Cerebro ---------- */
  var MAIN_CHIPS = ['Recomiéndame una comunidad', '¿Qué eventos hay en Colombia?', '¿Cómo me uno?', '¿Qué es Multiverse?', 'Hablar con el equipo'];

  function scoreComs(n) {
    return COMS.map(function (c) {
      var s = 0;
      c.kw.forEach(function (k) { if (n.indexOf(' ' + k + ' ') > -1) s += 3; else if (n.indexOf(' ' + k) > -1) s += 1; });
      if (n.indexOf(' ' + norm(c.nombre).trim() + ' ') > -1) s += 4;
      return { c: c, s: s };
    }).filter(function (x) { return x.s > 0; }).sort(function (a, b) { return b.s - a.s; });
  }

  function recommend(ranked) {
    var top = ranked.slice(0, 3).map(function (x) { return x.c; });
    var intro = top.length === 1
      ? 'Por lo que cuentas, **' + top[0].nombre + '** va contigo 🎯'
      : 'Mezclas varios universos, ¡me encanta! Mira estas opciones:';
    return [{ text: intro, cards: top },
      'Unirte es gratis: solo necesitas una cuenta. ¿Te cuento cómo?'];
  }

  function eventsAnswer(n, cb) {
    loadEvents(function (list) {
      var hoy = new Date(); hoy.setHours(0, 0, 0, 0);
      var arr = list.filter(function (e) { return new Date((e.fecha_fin || e.fecha_inicio) + 'T23:59:59') >= hoy; })
        .sort(function (a, b) { return a.fecha_inicio < b.fecha_inicio ? -1 : 1; });
      var ciudades = ['bogota', 'medellin', 'cali', 'cartagena', 'barranquilla', 'bucaramanga', 'pereira', 'manizales'], ciudad = null;
      ciudades.forEach(function (c) { if (n.indexOf(' ' + c) > -1) ciudad = c; });
      var cat = scoreComs(n)[0];
      var f = arr, etiqueta = '';
      if (ciudad) { f = arr.filter(function (e) { return norm(e.ciudad).indexOf(ciudad) > -1; }); etiqueta = ' en ' + ciudad.charAt(0).toUpperCase() + ciudad.slice(1); }
      else if (cat) { f = arr.filter(function (e) { return norm(e.categoria).indexOf(norm(cat.c.tema).trim().split(' ')[0].slice(0, 4)) > -1; }); etiqueta = ' de ' + cat.c.tema.toLowerCase(); }
      if (!f.length) { f = arr; etiqueta = ''; }
      var lines = f.slice(0, 3).map(function (e) {
        return '• **' + e.titulo + '** — ' + e.fecha_texto + ' · ' + e.lugar + ', ' + e.ciudad;
      });
      cb([{ text: 'Esto es lo que viene' + etiqueta + ' en Colombia 🇨🇴' },
        { text: lines.join('\n') },
        { text: 'Tengo más fechas por ciudad y categoría en la agenda completa.', btn: { t: 'Ver todos los eventos', u: 'html/eventos.html' } }]);
    });
  }

  function reply(text, cb) {
    var n = norm(text);
    var ranked = scoreComs(n);

    if (/^ (hola|holi|hey|buenas|buenos dias|buen dia|buenas tardes|buenas noches|saludos|hi|hello) /.test(n) && n.split(' ').length < 6)
      return cb(['¡Hola! 👋 Qué bueno verte por el Multiverso.', 'Cuéntame qué te gusta y te digo a qué comunidad unirte, o pregúntame lo que quieras de la página.'], MAIN_CHIPS);
    if (has(n, ['gracias', 'te agradezco', 'genial', 'perfecto', 'excelente', 'super']))
      return cb(['¡Con gusto! 💜 Si surge otra duda, aquí estoy.'], ['Recomiéndame una comunidad', '¿Qué eventos hay en Colombia?']);
    if (has(n, ['chao', 'adios', 'hasta luego', 'nos vemos', 'bye']))
      return cb(['¡Hasta pronto, viajero! Que tengas un buen viaje por el multiverso ✨'], []);
    if (has(n, ['quien eres', 'como te llamas', 'tu nombre', 'eres un robot', 'eres real', 'que eres', 'que haces', 'que puedes', 'yalu']))
      return cb(['Soy **Yalu**, el robot asistente del Multiverse of Madness 🤖. No soy una persona, pero conozco la página al derecho y al revés.',
        'Puedo recomendarte comunidades, contarte de eventos en Colombia, explicarte cómo registrarte y llevarte con el equipo si necesitas una persona.'], MAIN_CHIPS);

    if (has(n, ['unirme', 'unirse', 'registr', 'crear cuenta', 'crear una cuenta', 'inscrib', 'sumarme', 'como entro', 'como me uno', 'hacer parte', 'ser miembro']))
      return cb(['Unirte es gratis y toma un minuto:',
        { text: '1. Crea tu cuenta en **Únete**.\n2. Inicia sesión.\n3. Entra a la comunidad que te guste y participa en los foros.', btn: { t: 'Crear mi cuenta', u: 'unete.html' } }],
        ['Recomiéndame una comunidad', 'Ya tengo cuenta']);
    if (has(n, ['ya tengo cuenta', 'iniciar sesion', 'inicio de sesion', 'login', 'ingresar', 'contrasena', 'password', 'clave', 'olvide']))
      return cb(['Para entrar usa tu correo y contraseña en **Iniciar sesión**.',
        { text: 'Si olvidaste la contraseña, todavía no hay recuperación automática. Escríbenos desde Contacto y el equipo te ayuda.', btn: { t: 'Iniciar sesión', u: 'login.html' } }], ['Hablar con el equipo']);

    if (has(n, ['evento', 'eventos', 'festival', 'concierto', 'feria', 'agenda', 'calendario', 'convencion', 'que hay para hacer', 'proximo', 'torneo', 'colombia']) || has(n, ['en bogota', 'en medellin', 'en cali', 'en cartagena']))
      return eventsAnswer(n, function (parts) { cb(parts, ['Recomiéndame una comunidad', 'Eventos en Medellín', 'Eventos en Bogotá']); });

    if (has(n, ['foro', 'foros', 'hilo', 'debate', 'conversar', 'conversacion', 'publicar', 'comentar']))
      return cb(['Los **foros** son el espacio para conversar: cada comunidad tiene sus hilos, puedes abrir temas y responder a otros miembros. Para escribir necesitas tener cuenta.',
        { text: 'Echa un vistazo:', btn: { t: 'Ir a los foros', u: 'php/foros.php' } }], ['¿Cómo me uno?']);

    if (has(n, ['contacto', 'contactar', 'equipo', 'persona', 'humano', 'soporte', 'ayuda', 'reportar', 'queja', 'sugerencia', 'proponer', 'proponerles', 'idea', 'hablar con']))
      return cb(['Claro, te paso con el equipo. Escríbenos desde la página de contacto: respondemos tus dudas y las respuestas más útiles se publican en el muro para que otros también las vean.',
        { text: '¿Quieres proponer una comunidad nueva? También se hace por ahí.', btn: { t: 'Ir a Contacto', u: 'html/contacto.html' } }], MAIN_CHIPS.slice(0, 3));

    if (has(n, ['gratis', 'cuesta', 'costo', 'precio', 'pagar', 'cobran', 'plus', 'premium', 'suscripcion']))
      return cb(['Unirte a las comunidades es **gratis**. 🎉',
        'Estamos preparando **Multiverse Plus**, una experiencia con más personalización. Todavía no está disponible, pero será opcional.'], ['¿Qué viene próximamente?', '¿Cómo me uno?']);

    if (has(n, ['proximamente', 'novedad', 'novedades', 'que viene', 'actualizacion', 'actualizaciones', 'nuevo', 'nuevas', 'futuro', 'pronto']))
      return cb(['Esto es lo que estamos cocinando 🧪',
        '• **Cuatro universos nuevos:** Arte y Dibujo, Ciencia y Espacio, Manualidades y Culturas del Mundo.\n• **Multiverse Plus:** más personalización.\n• **Chat entre miembros** dentro de cada comunidad.'], ['Recomiéndame una comunidad', 'Hablar con el equipo']);

    if (has(n, ['que es multiverse', 'que es esto', 'de que trata', 'para que sirve', 'que es la pagina', 'que es the multiverse', 'plataforma', 'multiverse']) && !ranked.length)
      return cb(['**The Multiverse of Madness** es un punto de encuentro para gente que vive sus pasiones a full: gamers, otakus, cinéfilos, lectores, cosplayers, músicos, atletas y makers.',
        'Hay **8 comunidades** activas, foros para conversar y una agenda de eventos en Colombia.'], ['Recomiéndame una comunidad', '¿Qué eventos hay en Colombia?']);

    if (has(n, ['cuantas comunidades', 'que comunidades', 'lista de comunidades', 'todas las comunidades', 'comunidades hay', 'comunidades tienen', 'comunidades']) && !ranked.length)
      return cb([{ text: 'Hoy tenemos **8 universos**:', cards: COMS.slice(0, 4) }, { cards: COMS.slice(4) }, 'Y vienen 4 más muy pronto. ¿Quieres que te recomiende una según tus gustos?'], ['Recomiéndame una comunidad']);

    if (has(n, ['recomienda', 'recomiendas', 'recomiendame', 'recomendacion', 'cual me', 'a cual', 'que comunidad', 'donde me uno', 'no se cual', 'elegir', 'sugiere', 'sugiereme', 'no se que']) && !ranked.length)
      return cb(['¡Vamos a encontrar tu universo! 🧭 ¿Qué te gusta hacer en tu tiempo libre?',
        'Puedes escoger una opción o contarme con tus palabras (por ejemplo: "me gusta dibujar y ver series").'],
        ['Jugar videojuegos', 'Ver anime', 'Leer', 'Escuchar música', 'Programar', 'Hacer deporte', 'Ver películas', 'Disfrazarme']);

    if (ranked.length) return cb(recommend(ranked), ['¿Cómo me uno?', 'Otra recomendación', '¿Qué eventos hay en Colombia?']);

    if (has(n, ['otra recomendacion', 'otra opcion', 'otra']))
      return cb(['Dime algo más que te guste y busco otra opción. ¿Música, cine, tecnología…?'], ['Escuchar música', 'Ver películas', 'Programar', 'Leer']);

    return cb(['Mmm, no estoy seguro de haber entendido 🤔. Soy un robot con respuestas limitadas, pero puedo ayudarte con esto:',
      { text: 'Si prefieres hablar con una persona, el equipo responde desde Contacto.', btn: { t: 'Escribir al equipo', u: 'html/contacto.html' } }], MAIN_CHIPS);
  }

  function send(text) {
    text = (text || '').trim();
    if (!text || busy) return;
    addBubble('u', esc(text));
    elInput.value = '';
    setChips([]);
    busy = true;
    setTimeout(function () { busy = false; reply(text, function (p, c) { botSay(p, c); }); }, 350);
  }

  /* ---------- Apertura / cierre ---------- */
  function restore() {
    elScroll.querySelectorAll('.yalu-msg').forEach(function (e) { e.remove(); });
    history.forEach(function (m) { addBubble(m.f, m.h, { restore: true, ts: m.ts }); });
  }
  function openChat() {
    root.classList.add('is-open');
    store('yalu_open', 1);
    if (!history.length) botSay(['¡Hola! Soy **Yalu**, el robot del Multiverso 🤖✨', 'Resuelvo dudas de la página y te recomiendo a qué comunidad unirte. ¿Por dónde empezamos?'], MAIN_CHIPS);
    else { restore(); setChips(MAIN_CHIPS); }
    setTimeout(function () { elInput.focus(); }, 350);
  }
  function closeChat() { root.classList.remove('is-open'); store('yalu_open', 0); elFab.focus(); }

  elFab.addEventListener('click', openChat);
  root.querySelector('[data-act=close]').addEventListener('click', closeChat);
  root.querySelector('[data-act=reset]').addEventListener('click', function () {
    history = []; store('yalu_hist', []); busy = false;
    elScroll.querySelectorAll('.yalu-msg').forEach(function (e) { e.remove(); });
    botSay(['Conversación reiniciada ✨ ¿En qué te ayudo?'], MAIN_CHIPS);
  });
  elForm.addEventListener('submit', function (e) { e.preventDefault(); send(elInput.value); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('is-open')) closeChat(); });

  // Si el chat estaba abierto en otra página, se mantiene abierto
  if (store('yalu_open') === 1 && history.length) { openChat(); }
})();
