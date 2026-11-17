<?php
// Panel de administración del Multiverse: resumen, mensajes con respuesta pública,
// comunidades, eventos, foros, usuarios y anuncios de Yalu. Solo para rol "admin".
require_once __DIR__ . '/verificar_sesion.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/conexion.php';
verificarAdmin();

$yo = (int) $_SESSION['id_usuario'];
$CATEGORIAS = ['Anime', 'Cosplay', 'Videojuegos', 'Tecnología', 'Libros', 'Cine', 'Música', 'Deportes'];

function lista_imagenes() {
    $o = [];
    foreach (scandir(__DIR__ . '/../imagenes') ?: [] as $f) {
        if (preg_match('/\.(jpe?g|png|webp|avif|gif)$/i', $f)) $o[] = $f;
    }
    sort($o);
    return $o;
}
function ir($vista, $msg = '') {
    if ($msg !== '') $_SESSION['aviso_admin'] = $msg;
    header('Location: admin.php?v=' . urlencode($vista));
    exit;
}
function url_ok($u) { return $u === '' || preg_match('#^https?://#i', $u); }

/* =====================  ACCIONES (POST)  ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { http_response_code(403); exit('Solicitud no válida.'); }
    $a  = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    /* ---- Mensajes ---- */
    if ($a === 'mensaje_responder') {
        $resp = trim($_POST['respuesta'] ?? '');
        $pub  = isset($_POST['publicar']) ? 1 : 0;
        if ($resp === '') ir('mensajes', 'Escribe una respuesta antes de guardar.');
        $ok = q_run($conexion,
            "UPDATE mensajes_contacto SET respuesta = ?, fecha_respuesta = NOW(), respondido_por = ?, leido = 1,
                    publico = IF(? = 1 AND acepta_publicar = 1, 1, 0) WHERE id = ?", 'siii', [$resp, $yo, $pub, $id]);
        $m = q_one($conexion, "SELECT acepta_publicar, publico FROM mensajes_contacto WHERE id = ?", 'i', [$id]);
        if ($pub && $m && !$m['acepta_publicar']) ir('mensajes', 'Respuesta guardada. No se publicó: la persona no aceptó que se muestre en el muro.');
        ir('mensajes', $ok ? ($pub ? 'Respuesta guardada y publicada en el muro.' : 'Respuesta guardada.') : 'No se pudo guardar la respuesta.');
    }
    if ($a === 'mensaje_leido')  { q_run($conexion, "UPDATE mensajes_contacto SET leido = 1 WHERE id = ?", 'i', [$id]); ir('mensajes', 'Marcado como leído.'); }
    if ($a === 'mensaje_quitar') { q_run($conexion, "UPDATE mensajes_contacto SET publico = 0 WHERE id = ?", 'i', [$id]); ir('mensajes', 'Quitado del muro.'); }
    if ($a === 'mensaje_borrar') { q_run($conexion, "DELETE FROM mensajes_contacto WHERE id = ?", 'i', [$id]); ir('mensajes', 'Mensaje eliminado.'); }

    /* ---- Comunidades ---- */
    if ($a === 'comunidad_guardar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $cat    = trim($_POST['categoria'] ?? '');
        $img    = basename(trim($_POST['imagen'] ?? ''));
        $desc   = trim($_POST['descripcion'] ?? '');
        $desc2  = trim($_POST['descripcion2'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '') ?: $nombre;
        $simb   = trim($_POST['simbolo'] ?? '') ?: '✦';
        $union  = trim($_POST['texto_union'] ?? '') ?: "¿Te apasiona $cat? Esta comunidad es para ti.";
        $sobre  = trim($_POST['sobre'] ?? '') ?: $desc;
        $fans   = trim($_POST['fans'] ?? '') ?: "fans de $cat";
        $miem   = max(0, (int) ($_POST['miembros'] ?? 0));
        if ($nombre === '' || $cat === '' || $img === '' || $desc === '') ir('comunidades', 'Completa nombre, categoría, imagen y descripción.');
        if ($id > 0) {
            $ok = q_run($conexion, "UPDATE comunidades SET nombre=?, titulo=?, simbolo=?, texto_union=?, imagen=?, descripcion=?, descripcion2=?, miembros=?, categoria=?, sobre=?, fans=? WHERE id=?",
                        'sssssssisssi', [$nombre, $titulo, $simb, $union, $img, $desc, $desc2, $miem, $cat, $sobre, $fans, $id]);
            ir('comunidades', $ok ? 'Comunidad actualizada.' : 'No se pudo actualizar.');
        }
        $slug = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace([' ', '-'], '_', trim($_POST['slug'] ?? ''))));
        if ($slug === '') ir('comunidades', 'El identificador (slug) solo admite letras, números y guion bajo.');
        $ok = q_run($conexion, "INSERT INTO comunidades (slug,nombre,titulo,simbolo,texto_union,imagen,descripcion,descripcion2,miembros,fecha_creacion,categoria,sobre,fans)
                                VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),?,?,?)",
                    'ssssssssisss', [$slug, $nombre, $titulo, $simb, $union, $img, $desc, $desc2, $miem, $cat, $sobre, $fans]);
        if (!$ok) ir('comunidades', 'No se pudo crear (¿ese identificador ya existe?).');
        $nid = mysqli_insert_id($conexion);
        foreach (['Conversaciones y recomendaciones', 'Eventos y novedades del tema', 'Gente con tus mismos intereses'] as $i => $t) {
            q_run($conexion, "INSERT INTO comunidad_items (comunidad_id, texto, orden) VALUES (?, ?, ?)", 'isi', [$nid, $t, $i + 1]);
        }
        ir('comunidades', 'Comunidad creada. Ya aparece en el listado.');
    }
    if ($a === 'comunidad_borrar') { q_run($conexion, "DELETE FROM comunidades WHERE id = ?", 'i', [$id]); ir('comunidades', 'Comunidad eliminada.'); }

    /* ---- Eventos ---- */
    if ($a === 'evento_guardar') {
        $t   = trim($_POST['titulo'] ?? '');
        $cat = trim($_POST['categoria'] ?? '');
        $ci  = trim($_POST['ciudad'] ?? '');
        $lu  = trim($_POST['lugar'] ?? '');
        $fi  = trim($_POST['fecha_inicio'] ?? '');
        $ff  = trim($_POST['fecha_fin'] ?? '');
        $ft  = trim($_POST['fecha_texto'] ?? '');
        $img = basename(trim($_POST['imagen'] ?? '')) ?: 'imagen_inicio.jpg';
        $url = trim($_POST['enlace'] ?? '');
        $d   = trim($_POST['descripcion'] ?? '');
        $c1  = isset($_POST['confirmado']) ? 1 : 0;
        $c2  = isset($_POST['destacado']) ? 1 : 0;
        $c3  = isset($_POST['activo']) ? 1 : 0;
        if ($t === '' || $cat === '' || $ci === '' || $lu === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fi)) ir('eventos', 'Completa título, categoría, ciudad, lugar y fecha de inicio.');
        if ($ff !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ff)) ir('eventos', 'La fecha final no es válida.');
        if (!url_ok($url)) ir('eventos', 'El enlace debe empezar por http:// o https://');
        if ($ft === '') $ft = date('d/m/Y', strtotime($fi));
        $par = [$t, $cat, $ci, $lu, $fi, $ff, $ft, $img, $url, $d, $c1, $c2, $c3];
        if ($id > 0) {
            $ok = q_run($conexion, "UPDATE eventos SET titulo=?, categoria=?, ciudad=?, lugar=?, fecha_inicio=?, fecha_fin=NULLIF(?, ''), fecha_texto=?, imagen=?, enlace=?, descripcion=?, confirmado=?, destacado=?, activo=? WHERE id=?",
                        'ssssssssssiiii', array_merge($par, [$id]));
            ir('eventos', $ok ? 'Evento actualizado.' : 'No se pudo actualizar el evento.');
        }
        $ok = q_run($conexion, "INSERT INTO eventos (titulo,categoria,ciudad,lugar,fecha_inicio,fecha_fin,fecha_texto,imagen,enlace,descripcion,confirmado,destacado,activo)
                                VALUES (?,?,?,?,?,NULLIF(?, ''),?,?,?,?,?,?,?)", 'ssssssssssiii', $par);
        ir('eventos', $ok ? 'Evento creado. Ya se ve en la agenda.' : 'No se pudo crear el evento.');
    }
    if ($a === 'evento_borrar') { q_run($conexion, "DELETE FROM eventos WHERE id = ?", 'i', [$id]); ir('eventos', 'Evento eliminado.'); }
    if ($a === 'evento_toggle') { q_run($conexion, "UPDATE eventos SET activo = 1 - activo WHERE id = ?", 'i', [$id]); ir('eventos'); }

    /* ---- Usuarios ---- */
    if ($a === 'usuario_rol') {
        $rol = $_POST['rol'] ?? '';
        if ($id === $yo) ir('usuarios', 'No puedes cambiar tu propio rol.');
        if (in_array($rol, ['usuario', 'moderador', 'admin'], true)) q_run($conexion, "UPDATE usuarios SET rol = ? WHERE id = ?", 'si', [$rol, $id]);
        ir('usuarios', 'Rol actualizado.');
    }
    if ($a === 'usuario_borrar') {
        if ($id === $yo) ir('usuarios', 'No puedes eliminar tu propia cuenta.');
        q_run($conexion, "DELETE FROM usuarios WHERE id = ?", 'i', [$id]);
        ir('usuarios', 'Usuario eliminado.');
    }

    /* ---- Foros ---- */
    if ($a === 'hilo_borrar') { q_run($conexion, "DELETE FROM foro_hilos WHERE id = ?", 'i', [$id]); ir('foros', 'Tema eliminado.'); }
    if ($a === 'hilo_fijar')  { q_run($conexion, "UPDATE foro_hilos SET fijado = 1 - fijado WHERE id = ?", 'i', [$id]); ir('foros'); }

    /* ---- Anuncios de Yalu ---- */
    if ($a === 'anuncio_guardar') {
        $ic = trim($_POST['icono'] ?? '') ?: '✨';
        $et = trim($_POST['etiqueta'] ?? '') ?: 'Próximamente';
        $ti = trim($_POST['titulo'] ?? '');
        $tx = trim($_POST['texto'] ?? '');
        $or = (int) ($_POST['orden'] ?? 0);
        $ac = isset($_POST['activo']) ? 1 : 0;
        if ($ti === '') ir('anuncios', 'El anuncio necesita un título.');
        if ($id > 0) q_run($conexion, "UPDATE anuncios SET icono=?, etiqueta=?, titulo=?, texto=?, orden=?, activo=? WHERE id=?", 'ssssiii', [$ic, $et, $ti, $tx, $or, $ac, $id]);
        else         q_run($conexion, "INSERT INTO anuncios (icono, etiqueta, titulo, texto, orden, activo) VALUES (?,?,?,?,?,?)", 'ssssii', [$ic, $et, $ti, $tx, $or, $ac]);
        ir('anuncios', 'Anuncio guardado.');
    }
    if ($a === 'anuncio_borrar') { q_run($conexion, "DELETE FROM anuncios WHERE id = ?", 'i', [$id]); ir('anuncios', 'Anuncio eliminado.'); }
    ir('resumen');
}

/* =====================  DATOS PARA LA VISTA  ===================== */
$VISTAS = [
    'resumen'     => ['Resumen', '🏠'],
    'mensajes'    => ['Mensajes', '💬'],
    'comunidades' => ['Comunidades', '🪐'],
    'eventos'     => ['Eventos', '🗓️'],
    'foros'       => ['Foros', '🧵'],
    'usuarios'    => ['Usuarios', '👥'],
    'anuncios'    => ['Anuncios de Yalu', '📣'],
];
$v = $_GET['v'] ?? 'resumen';
if (!isset($VISTAS[$v])) $v = 'resumen';

$aviso = $_SESSION['aviso_admin'] ?? '';
unset($_SESSION['aviso_admin']);

$sinLeer     = (int) (q_one($conexion, "SELECT COUNT(*) n FROM mensajes_contacto WHERE leido = 0")['n'] ?? 0);
$sinResponder = (int) (q_one($conexion, "SELECT COUNT(*) n FROM mensajes_contacto WHERE respuesta IS NULL OR respuesta = ''")['n'] ?? 0);

if ($v === 'resumen') {
    $k = [
        'usuarios'    => (int) (q_one($conexion, "SELECT COUNT(*) n FROM usuarios")['n'] ?? 0),
        'comunidades' => (int) (q_one($conexion, "SELECT COUNT(*) n FROM comunidades")['n'] ?? 0),
        'eventos'     => (int) (q_one($conexion, "SELECT COUNT(*) n FROM eventos WHERE activo = 1 AND COALESCE(fecha_fin, fecha_inicio) >= CURDATE()")['n'] ?? 0),
        'hilos'       => (int) (q_one($conexion, "SELECT COUNT(*) n FROM foro_hilos")['n'] ?? 0),
    ];
    $barras   = q_all($conexion, "SELECT nombre, simbolo, miembros FROM comunidades ORDER BY miembros DESC LIMIT 8");
    $maxMiem  = 1; foreach ($barras as $b) $maxMiem = max($maxMiem, (int) $b['miembros']);
    $pendientes = q_all($conexion, "SELECT id, nombre, asunto, tipo, fecha FROM mensajes_contacto WHERE respuesta IS NULL OR respuesta = '' ORDER BY fecha DESC LIMIT 5");
    $proximos = q_all($conexion, "SELECT titulo, ciudad, fecha_texto FROM eventos WHERE activo = 1 AND COALESCE(fecha_fin, fecha_inicio) >= CURDATE() ORDER BY fecha_inicio LIMIT 4");
}
if ($v === 'mensajes') {
    $f = $_GET['f'] ?? '';
    $where = '';
    if ($f === 'pendientes') $where = "WHERE m.respuesta IS NULL OR m.respuesta = ''";
    if ($f === 'publicados') $where = "WHERE m.publico = 1";
    $mensajes = q_all($conexion, "SELECT m.*, u.nombre AS resp_por FROM mensajes_contacto m LEFT JOIN usuarios u ON u.id = m.respondido_por $where
                                  ORDER BY (m.respuesta IS NULL OR m.respuesta = '') DESC, m.fecha DESC");
}
if ($v === 'comunidades') {
    $comunidades = q_all($conexion, "SELECT * FROM comunidades ORDER BY nombre");
    $imagenes = lista_imagenes();
}
if ($v === 'eventos') {
    $eventos = q_all($conexion, "SELECT * FROM eventos ORDER BY fecha_inicio");
    $imagenes = lista_imagenes();
}
if ($v === 'usuarios') {
    $usuarios = q_all($conexion, "SELECT id, nombre, correo, rol, fecha_registro FROM usuarios ORDER BY FIELD(rol,'admin','moderador','usuario'), fecha_registro DESC");
}
if ($v === 'foros') {
    $hilos = q_all($conexion, "SELECT h.id, h.titulo, h.fijado, h.ultima_actividad, u.nombre AS autor, c.nombre AS comunidad,
                               (SELECT COUNT(*) FROM foro_respuestas r WHERE r.hilo_id = h.id) AS respuestas
                               FROM foro_hilos h JOIN usuarios u ON u.id = h.usuario_id LEFT JOIN comunidades c ON c.id = h.comunidad_id
                               ORDER BY h.ultima_actividad DESC LIMIT 100");
}
if ($v === 'anuncios') {
    $anuncios = q_all($conexion, "SELECT * FROM anuncios ORDER BY orden, id");
}

function btn_form($accion, $id, $texto, $clase = 'btn-sec', $confirmar = '') {
    $c = $confirmar !== '' ? ' onsubmit="return confirm(\'' . e($confirmar) . '\')"' : '';
    return '<form method="post" class="inl"' . $c . '><input type="hidden" name="csrf" value="' . e(csrf()) . '">'
         . '<input type="hidden" name="accion" value="' . e($accion) . '"><input type="hidden" name="id" value="' . (int) $id . '">'
         . '<button type="submit" class="btn ' . e($clase) . '">' . e($texto) . '</button></form>';
}
$TIPOS = ['duda' => 'Duda', 'idea' => 'Idea', 'comunidad' => 'Comunidad', 'problema' => 'Problema', 'otro' => 'Otro'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel - <?= e($VISTAS[$v][0]) ?> · Multiverse</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Orbitron:wght@600;800&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="shell">

  <aside class="side" id="side">
    <a class="brand" href="admin.php"><span class="brand__ring"></span><span class="brand__t">MULTIVERSE<small>PANEL</small></span></a>
    <nav>
      <?php foreach ($VISTAS as $k2 => $info): ?>
        <a href="admin.php?v=<?= e($k2) ?>" class="nav-i<?= $k2 === $v ? ' on' : '' ?>">
          <span class="nav-i__ic"><?= $info[1] ?></span><?= e($info[0]) ?>
          <?php if ($k2 === 'mensajes' && $sinLeer > 0): ?><b class="pill"><?= $sinLeer ?></b><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="side__foot">
      <a href="../index.html" class="nav-i"><span class="nav-i__ic">🌐</span>Ver el sitio</a>
      <a href="cerrar.php" class="nav-i"><span class="nav-i__ic">🚪</span>Cerrar sesión</a>
    </div>
  </aside>

  <div class="main">
    <header class="top">
      <button type="button" class="burger" id="burger" aria-label="Menú">☰</button>
      <h1><?= $VISTAS[$v][1] ?> <?= e($VISTAS[$v][0]) ?></h1>
      <button type="button" class="kbd-btn" id="abrirPaleta">Buscar o ir a… <kbd>Ctrl</kbd><kbd>K</kbd></button>
      <span class="who"><span class="av"><?= e(inicial($_SESSION['nombre'])) ?></span><?= e($_SESSION['nombre']) ?></span>
    </header>

    <main class="content">

<?php if ($v === 'resumen'): ?>
      <section class="hello">
        <div>
          <h2>Hola, <?= e($_SESSION['nombre']) ?> 👋</h2>
          <p><?= $sinResponder > 0 ? "Tienes <strong>$sinResponder</strong> " . ($sinResponder === 1 ? 'mensaje' : 'mensajes') . ' por responder.' : 'Todo al día: no hay mensajes pendientes.' ?></p>
        </div>
        <div class="quick">
          <a class="btn" href="admin.php?v=mensajes&f=pendientes">Responder mensajes</a>
          <a class="btn btn-sec" href="admin.php?v=eventos#nuevo">Nuevo evento</a>
        </div>
      </section>

      <section class="kpis">
        <div class="kpi"><span>👥</span><b><?= $k['usuarios'] ?></b><small>Usuarios</small></div>
        <div class="kpi"><span>🪐</span><b><?= $k['comunidades'] ?></b><small>Comunidades</small></div>
        <div class="kpi"><span>💬</span><b><?= $sinLeer ?></b><small>Mensajes sin leer</small></div>
        <div class="kpi"><span>🗓️</span><b><?= $k['eventos'] ?></b><small>Eventos por venir</small></div>
        <div class="kpi"><span>🧵</span><b><?= $k['hilos'] ?></b><small>Temas en foros</small></div>
      </section>

      <div class="cols">
        <section class="panel">
          <h3>Miembros por comunidad</h3>
          <?php foreach ($barras as $b): ?>
            <div class="bar"><span class="bar__n"><?= e($b['simbolo'] . ' ' . $b['nombre']) ?></span>
              <span class="bar__t"><i style="width:<?= max(3, round(((int) $b['miembros'] / $maxMiem) * 100)) ?>%"></i></span>
              <span class="bar__v"><?= (int) $b['miembros'] ?></span></div>
          <?php endforeach; ?>
        </section>
        <section class="panel">
          <h3>Por responder</h3>
          <?php if (!$pendientes): ?><p class="muted">Nada pendiente. ✨</p><?php endif; ?>
          <?php foreach ($pendientes as $m): ?>
            <a class="mini" href="admin.php?v=mensajes&f=pendientes#m<?= (int) $m['id'] ?>">
              <span class="av sm"><?= e(inicial($m['nombre'])) ?></span>
              <span><strong><?= e($m['asunto']) ?></strong><small><?= e($m['nombre']) ?> · <?= e(hace($m['fecha'])) ?></small></span>
            </a>
          <?php endforeach; ?>
          <h3 class="mt">Próximos eventos</h3>
          <?php foreach ($proximos as $p): ?>
            <div class="mini"><span class="av sm">📍</span><span><strong><?= e($p['titulo']) ?></strong><small><?= e($p['ciudad']) ?> · <?= e($p['fecha_texto']) ?></small></span></div>
          <?php endforeach; ?>
        </section>
      </div>

<?php elseif ($v === 'mensajes'): ?>
      <div class="tabs">
        <a href="admin.php?v=mensajes" class="<?= ($f ?? '') === '' ? 'on' : '' ?>">Todos</a>
        <a href="admin.php?v=mensajes&f=pendientes" class="<?= ($f ?? '') === 'pendientes' ? 'on' : '' ?>">Por responder <?php if ($sinResponder): ?><b class="pill"><?= $sinResponder ?></b><?php endif; ?></a>
        <a href="admin.php?v=mensajes&f=publicados" class="<?= ($f ?? '') === 'publicados' ? 'on' : '' ?>">En el muro</a>
        <input type="search" class="filtro" data-filtro="#lista-m" placeholder="Filtrar por nombre, correo o texto…">
      </div>
      <div id="lista-m" class="stack">
        <?php if (!$mensajes): ?><div class="empty">No hay mensajes en esta vista.</div><?php endif; ?>
        <?php foreach ($mensajes as $m): $resp = trim((string) $m['respuesta']) !== ''; ?>
          <article class="msg fila<?= $m['leido'] ? '' : ' nuevo' ?>" id="m<?= (int) $m['id'] ?>">
            <header>
              <span class="av"><?= e(inicial($m['nombre'])) ?></span>
              <div class="msg__who"><strong><?= e($m['nombre']) ?></strong>
                <a href="mailto:<?= e($m['correo']) ?>"><?= e($m['correo']) ?></a> <small>· <?= e(hace($m['fecha'])) ?></small></div>
              <div class="chips">
                <span class="chip"><?= e($TIPOS[$m['tipo']] ?? 'Mensaje') ?></span>
                <?php if (!$m['leido']): ?><span class="chip chip-new">Nuevo</span><?php endif; ?>
                <?php if ($resp): ?><span class="chip chip-ok">Respondido</span><?php endif; ?>
                <?php if ($m['publico']): ?><span class="chip chip-pub">En el muro</span><?php endif; ?>
                <?php if ($m['acepta_publicar']): ?><span class="chip" title="La persona aceptó que se publique">Acepta publicar</span><?php endif; ?>
              </div>
            </header>
            <h4><?= e($m['asunto']) ?></h4>
            <p class="msg__txt"><?= nl2br(e($m['mensaje'])) ?></p>

            <form method="post" class="reply">
              <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
              <input type="hidden" name="accion" value="mensaje_responder">
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <label for="r<?= (int) $m['id'] ?>">Tu respuesta<?php if ($resp && $m['resp_por']): ?> <small>(última por <?= e($m['resp_por']) ?>)</small><?php endif; ?></label>
              <textarea id="r<?= (int) $m['id'] ?>" name="respuesta" rows="3" placeholder="Escribe la respuesta que verá la persona y, si la publicas, todo el mundo…"><?= e($m['respuesta']) ?></textarea>
              <div class="reply__row">
                <label class="check<?= $m['acepta_publicar'] ? '' : ' off' ?>">
                  <input type="checkbox" name="publicar" value="1" <?= $m['publico'] ? 'checked' : '' ?> <?= $m['acepta_publicar'] ? '' : 'disabled' ?>>
                  <span><?= $m['acepta_publicar'] ? 'Publicar en el muro de Contacto' : 'No aceptó publicar: solo respuesta privada' ?></span>
                </label>
                <button type="submit" class="btn">Guardar respuesta</button>
              </div>
            </form>
            <footer class="msg__acts">
              <?php if (!$m['leido']) echo btn_form('mensaje_leido', $m['id'], 'Marcar leído'); ?>
              <?php if ($m['publico']) echo btn_form('mensaje_quitar', $m['id'], 'Quitar del muro'); ?>
              <?= btn_form('mensaje_borrar', $m['id'], 'Eliminar', 'btn-danger', '¿Eliminar este mensaje?') ?>
              <span class="hint">Para responder por correo: <a href="mailto:<?= e($m['correo']) ?>?subject=<?= rawurlencode('Re: ' . $m['asunto']) ?>">abrir correo</a></span>
            </footer>
          </article>
        <?php endforeach; ?>
      </div>

<?php elseif ($v === 'comunidades'): ?>
      <div class="bar-top">
        <input type="search" class="filtro" data-filtro="#grid-c" placeholder="Filtrar comunidades…">
        <button type="button" class="btn" data-abrir="dlgCom" data-row="{}">+ Nueva comunidad</button>
      </div>
      <div class="grid" id="grid-c">
        <?php foreach ($comunidades as $c): ?>
          <article class="card fila">
            <div class="card__img"><img src="../imagenes/<?= e($c['imagen']) ?>" alt="" loading="lazy"><span class="card__sym"><?= e($c['simbolo']) ?></span></div>
            <div class="card__b">
              <span class="chip"><?= e($c['categoria']) ?></span>
              <h4><?= e($c['nombre']) ?></h4>
              <p><?= e(mb_substr($c['descripcion'], 0, 90)) ?></p>
              <small class="muted"><?= (int) $c['miembros'] ?> miembros · <code><?= e($c['slug']) ?></code></small>
              <div class="card__acts">
                <button type="button" class="btn btn-sec" data-abrir="dlgCom" data-row="<?= e(json_encode($c, JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                <a class="btn btn-sec" href="comunidad.php?slug=<?= e($c['slug']) ?>" target="_blank">Ver</a>
                <?= btn_form('comunidad_borrar', $c['id'], 'Eliminar', 'btn-danger', '¿Eliminar la comunidad ' . $c['nombre'] . '? Se borran también sus datos.') ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

<?php elseif ($v === 'eventos'): ?>
      <div class="bar-top" id="nuevo">
        <input type="search" class="filtro" data-filtro="#grid-e" placeholder="Filtrar eventos…">
        <button type="button" class="btn" data-abrir="dlgEv" data-row="{}">+ Nuevo evento</button>
      </div>
      <div class="grid" id="grid-e">
        <?php foreach ($eventos as $ev): $pasado = strtotime($ev['fecha_fin'] ?: $ev['fecha_inicio']) < strtotime('today'); ?>
          <article class="card fila<?= ($ev['activo'] && !$pasado) ? '' : ' apagado' ?>">
            <div class="card__img"><img src="../imagenes/<?= e($ev['imagen']) ?>" alt="" loading="lazy">
              <span class="card__sym"><?= e(date('d/m', strtotime($ev['fecha_inicio']))) ?></span></div>
            <div class="card__b">
              <div class="chips"><span class="chip"><?= e($ev['categoria']) ?></span>
                <?php if ($ev['confirmado']): ?><span class="chip chip-ok">Confirmado</span><?php else: ?><span class="chip chip-warn">Estimado</span><?php endif; ?>
                <?php if ($pasado): ?><span class="chip">Pasado</span><?php endif; ?>
                <?php if (!$ev['activo']): ?><span class="chip">Oculto</span><?php endif; ?></div>
              <h4><?= e($ev['titulo']) ?></h4>
              <p>📍 <?= e($ev['lugar']) ?>, <?= e($ev['ciudad']) ?><br>🗓️ <?= e($ev['fecha_texto']) ?></p>
              <div class="card__acts">
                <button type="button" class="btn btn-sec" data-abrir="dlgEv" data-row="<?= e(json_encode($ev, JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                <?= btn_form('evento_toggle', $ev['id'], $ev['activo'] ? 'Ocultar' : 'Mostrar') ?>
                <?= btn_form('evento_borrar', $ev['id'], 'Eliminar', 'btn-danger', '¿Eliminar este evento?') ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

<?php elseif ($v === 'usuarios'): ?>
      <div class="bar-top"><input type="search" class="filtro" data-filtro="#tabla-u" placeholder="Filtrar por nombre o correo…">
        <span class="muted"><?= count($usuarios) ?> cuentas · los cambios de rol se aplican al instante</span></div>
      <div class="panel tabla-wrap">
        <table id="tabla-u">
          <thead><tr><th>Persona</th><th>Correo</th><th>Rol</th><th>Registro</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($usuarios as $u): $soyYo = (int) $u['id'] === $yo; ?>
            <tr class="fila">
              <td><span class="av sm"><?= e(inicial($u['nombre'])) ?></span> <?= e($u['nombre']) ?><?= $soyYo ? ' <small class="muted">(tú)</small>' : '' ?></td>
              <td><?= e($u['correo']) ?></td>
              <td>
                <?php if ($soyYo): ?><span class="chip chip-pub"><?= e($u['rol']) ?></span>
                <?php else: ?>
                  <form method="post" class="inl"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="usuario_rol"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <select name="rol" onchange="this.form.submit()" class="sel">
                      <?php foreach (['usuario', 'moderador', 'admin'] as $r): ?><option value="<?= $r ?>"<?= $u['rol'] === $r ? ' selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
                    </select></form>
                <?php endif; ?>
              </td>
              <td><?= e(date('d/m/Y', strtotime($u['fecha_registro']))) ?></td>
              <td><?php if (!$soyYo) echo btn_form('usuario_borrar', $u['id'], 'Eliminar', 'btn-danger', '¿Eliminar a ' . $u['nombre'] . '? También se borran sus temas y respuestas.'); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

<?php elseif ($v === 'foros'): ?>
      <div class="bar-top"><input type="search" class="filtro" data-filtro="#tabla-f" placeholder="Filtrar temas…"><a class="btn btn-sec" href="foros.php" target="_blank">Abrir los foros</a></div>
      <div class="panel tabla-wrap">
        <table id="tabla-f">
          <thead><tr><th>Tema</th><th>Dónde</th><th>Autor</th><th>Resp.</th><th>Actividad</th><th></th></tr></thead>
          <tbody>
          <?php if (!$hilos): ?><tr><td colspan="6" class="muted">Todavía no hay temas.</td></tr><?php endif; ?>
          <?php foreach ($hilos as $h): ?>
            <tr class="fila">
              <td><a href="foros.php?hilo=<?= (int) $h['id'] ?>" target="_blank"><?= $h['fijado'] ? '📌 ' : '' ?><?= e($h['titulo']) ?></a></td>
              <td><?= e($h['comunidad'] ?: 'General') ?></td>
              <td><?= e($h['autor']) ?></td>
              <td><?= (int) $h['respuestas'] ?></td>
              <td><?= e(hace($h['ultima_actividad'])) ?></td>
              <td class="acts"><?= btn_form('hilo_fijar', $h['id'], $h['fijado'] ? 'Quitar fijado' : 'Fijar') ?>
                <?= btn_form('hilo_borrar', $h['id'], 'Eliminar', 'btn-danger', '¿Eliminar este tema y sus respuestas?') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

<?php elseif ($v === 'anuncios'): ?>
      <p class="muted lead">Estos son los avisos que Yalu muestra arriba del botón de chat en el inicio. Los visitantes pueden cerrarlos. Se muestran máximo 3, en el orden indicado.</p>
      <div class="grid grid-sm">
        <?php foreach ($anuncios as $an): ?>
          <form method="post" class="card card--form">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="anuncio_guardar"><input type="hidden" name="id" value="<?= (int) $an['id'] ?>">
            <div class="f-row"><input name="icono" value="<?= e($an['icono']) ?>" maxlength="10" class="w-ic" aria-label="Icono"><input name="etiqueta" value="<?= e($an['etiqueta']) ?>" maxlength="30" aria-label="Etiqueta"></div>
            <input name="titulo" value="<?= e($an['titulo']) ?>" maxlength="80" required aria-label="Título">
            <textarea name="texto" rows="2" maxlength="160" aria-label="Texto"><?= e($an['texto']) ?></textarea>
            <div class="f-row"><input type="number" name="orden" value="<?= (int) $an['orden'] ?>" class="w-ic" aria-label="Orden" title="Orden">
              <label class="check"><input type="checkbox" name="activo" value="1" <?= $an['activo'] ? 'checked' : '' ?>><span>Visible</span></label></div>
            <div class="card__acts"><button class="btn" type="submit">Guardar</button>
              <button class="btn btn-danger" type="submit" form="del-a<?= (int) $an['id'] ?>" onclick="return confirm('¿Eliminar este anuncio?')">Eliminar</button></div>
          </form>
          <form method="post" id="del-a<?= (int) $an['id'] ?>" hidden><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="anuncio_borrar"><input type="hidden" name="id" value="<?= (int) $an['id'] ?>"></form>
        <?php endforeach; ?>
        <form method="post" class="card card--form card--nuevo">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="anuncio_guardar"><input type="hidden" name="id" value="0">
          <h4>+ Nuevo anuncio</h4>
          <div class="f-row"><input name="icono" placeholder="✨" maxlength="10" class="w-ic" aria-label="Icono"><input name="etiqueta" placeholder="Próximamente" maxlength="30" aria-label="Etiqueta"></div>
          <input name="titulo" placeholder="Título" maxlength="80" required aria-label="Título">
          <textarea name="texto" rows="2" maxlength="160" placeholder="Una frase corta que explique la novedad" aria-label="Texto"></textarea>
          <div class="f-row"><input type="number" name="orden" value="<?= count($anuncios) + 1 ?>" class="w-ic" aria-label="Orden"><label class="check"><input type="checkbox" name="activo" value="1" checked><span>Visible</span></label></div>
          <button class="btn" type="submit">Crear anuncio</button>
        </form>
      </div>
<?php endif; ?>

    </main>
  </div>
</div>

<?php if ($v === 'comunidades'): ?>
<dialog id="dlgCom" class="dlg">
  <form method="post" class="dlg__f">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="comunidad_guardar"><input type="hidden" name="id" value="0">
    <header><h3 data-titulo="Nueva comunidad|Editar comunidad">Nueva comunidad</h3><button type="button" class="x" data-cerrar aria-label="Cerrar">×</button></header>
    <div class="dlg__body">
      <div class="f-grid">
        <label>Nombre<input name="nombre" required maxlength="80"></label>
        <label>Categoría<input name="categoria" required maxlength="50" list="cats"></label>
        <label data-solo-nuevo>Identificador (slug)<input name="slug" maxlength="50" placeholder="ej: manga"></label>
        <label>Símbolo<input name="simbolo" maxlength="10" placeholder="✦"></label>
        <label>Título en la página<input name="titulo" maxlength="120"></label>
        <label>Miembros<input name="miembros" type="number" min="0" value="0"></label>
        <label class="full">Imagen
          <select name="imagen" data-preview="#prevCom"><?php foreach ($imagenes as $im): ?><option value="<?= e($im) ?>"><?= e($im) ?></option><?php endforeach; ?></select>
        </label>
        <div class="full prev"><img id="prevCom" alt="Vista previa" src=""></div>
        <label class="full">Descripción corta<textarea name="descripcion" rows="2" required></textarea></label>
        <label class="full">Descripción (segunda línea)<textarea name="descripcion2" rows="2"></textarea></label>
        <label class="full">Texto de invitación<input name="texto_union" maxlength="255"></label>
        <label class="full">Sobre la comunidad<textarea name="sobre" rows="3"></textarea></label>
        <label class="full">Quiénes son sus fans<input name="fans" maxlength="120" placeholder="ej: otakus de todo el universo"></label>
      </div>
      <datalist id="cats"><?php foreach ($CATEGORIAS as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
    </div>
    <footer><button type="button" class="btn btn-sec" data-cerrar>Cancelar</button><button type="submit" class="btn">Guardar</button></footer>
  </form>
</dialog>
<?php endif; ?>

<?php if ($v === 'eventos'): ?>
<dialog id="dlgEv" class="dlg">
  <form method="post" class="dlg__f">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="evento_guardar"><input type="hidden" name="id" value="0">
    <header><h3 data-titulo="Nuevo evento|Editar evento">Nuevo evento</h3><button type="button" class="x" data-cerrar aria-label="Cerrar">×</button></header>
    <div class="dlg__body">
      <div class="f-grid">
        <label class="full">Título<input name="titulo" required maxlength="150"></label>
        <label>Categoría<select name="categoria"><?php foreach ($CATEGORIAS as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></label>
        <label>Ciudad<input name="ciudad" required maxlength="60" placeholder="Bogotá"></label>
        <label class="full">Lugar<input name="lugar" required maxlength="120" placeholder="Corferias"></label>
        <label>Fecha de inicio<input type="date" name="fecha_inicio" required></label>
        <label>Fecha final (opcional)<input type="date" name="fecha_fin"></label>
        <label class="full">Texto de la fecha<input name="fecha_texto" maxlength="60" placeholder="ej: 19–21 mar 2027 (si lo dejas vacío se genera)"></label>
        <label class="full">Imagen
          <select name="imagen" data-preview="#prevEv"><?php foreach ($imagenes as $im): ?><option value="<?= e($im) ?>"><?= e($im) ?></option><?php endforeach; ?></select>
        </label>
        <div class="full prev"><img id="prevEv" alt="Vista previa" src=""></div>
        <label class="full">Enlace oficial<input name="enlace" maxlength="255" placeholder="https://…"></label>
        <label class="full">Descripción<textarea name="descripcion" rows="3"></textarea></label>
        <div class="full checks">
          <label class="check"><input type="checkbox" name="confirmado" value="1"><span>Fecha confirmada oficialmente</span></label>
          <label class="check"><input type="checkbox" name="destacado" value="1"><span>Destacado</span></label>
          <label class="check"><input type="checkbox" name="activo" value="1" checked><span>Visible en el sitio</span></label>
        </div>
      </div>
    </div>
    <footer><button type="button" class="btn btn-sec" data-cerrar>Cancelar</button><button type="submit" class="btn">Guardar</button></footer>
  </form>
</dialog>
<?php endif; ?>

<!-- Paleta de comandos (Ctrl+K) -->
<dialog id="paleta" class="pal">
  <input type="text" id="palIn" placeholder="Ir a… (mensajes, eventos, usuarios, nueva comunidad)" autocomplete="off">
  <ul id="palLista">
    <?php foreach ($VISTAS as $k2 => $info): ?><li><a href="admin.php?v=<?= e($k2) ?>"><?= $info[1] ?> <?= e($info[0]) ?></a></li><?php endforeach; ?>
    <li><a href="admin.php?v=comunidades&nueva=1">➕ Nueva comunidad</a></li>
    <li><a href="admin.php?v=eventos&nuevo=1">➕ Nuevo evento</a></li>
    <li><a href="admin.php?v=mensajes&f=pendientes">✉️ Mensajes por responder</a></li>
    <li><a href="../index.html">🌐 Ver el sitio</a></li>
  </ul>
</dialog>

<?php if ($aviso): ?><div class="toast" id="toast" role="status"><?= e($aviso) ?></div><?php endif; ?>
<script src="../js/admin.js"></script>
</body>
</html>
