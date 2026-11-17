<?php
// Foros: temas por comunidad, respuestas y moderación básica.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/conexion.php';

$uid   = (int) ($_SESSION['id_usuario'] ?? 0);
$rol   = $_SESSION['rol'] ?? '';
$esMod = in_array($rol, ['admin', 'moderador'], true);

$aviso = $_SESSION['aviso_foro'] ?? '';
unset($_SESSION['aviso_foro']);

function volver($url, $msg = '') {
    if ($msg !== '') $_SESSION['aviso_foro'] = $msg;
    header('Location: ' . $url);
    exit;
}

/* ---------- Acciones (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { http_response_code(403); exit('Solicitud no válida.'); }
    if (!$uid) volver('../login.html');
    $acc = $_POST['accion'] ?? '';

    if ($acc === 'nuevo_hilo') {
        $titulo = trim($_POST['titulo'] ?? '');
        $cont   = trim($_POST['contenido'] ?? '');
        $com    = (int) ($_POST['comunidad_id'] ?? 0);
        if (mb_strlen($titulo) < 4 || mb_strlen($titulo) > 150 || mb_strlen($cont) < 5 || mb_strlen($cont) > 5000) {
            volver('foros.php', 'Escribe un título de al menos 4 letras y un mensaje de al menos 5.');
        }
        $ok = q_run($conexion, "INSERT INTO foro_hilos (comunidad_id, usuario_id, titulo, contenido) VALUES (NULLIF(?, 0), ?, ?, ?)",
                    'iiss', [$com, $uid, $titulo, $cont]);
        if (!$ok) volver('foros.php', 'No se pudo publicar el tema. Inténtalo de nuevo.');
        volver('foros.php?hilo=' . mysqli_insert_id($conexion), 'Tema publicado.');
    }

    if ($acc === 'responder') {
        $hilo = (int) ($_POST['hilo_id'] ?? 0);
        $cont = trim($_POST['contenido'] ?? '');
        if (mb_strlen($cont) < 2 || mb_strlen($cont) > 3000) volver('foros.php?hilo=' . $hilo, 'Escribe una respuesta de entre 2 y 3000 caracteres.');
        if (!q_one($conexion, "SELECT id FROM foro_hilos WHERE id = ?", 'i', [$hilo])) volver('foros.php', 'Ese tema ya no existe.');
        q_run($conexion, "INSERT INTO foro_respuestas (hilo_id, usuario_id, contenido) VALUES (?, ?, ?)", 'iis', [$hilo, $uid, $cont]);
        $nuevo = mysqli_insert_id($conexion);
        q_run($conexion, "UPDATE foro_hilos SET ultima_actividad = NOW() WHERE id = ?", 'i', [$hilo]);
        volver('foros.php?hilo=' . $hilo . '#r' . $nuevo);
    }

    if ($acc === 'borrar_hilo') {
        $id = (int) ($_POST['id'] ?? 0);
        $h = q_one($conexion, "SELECT usuario_id FROM foro_hilos WHERE id = ?", 'i', [$id]);
        if ($h && ($esMod || (int) $h['usuario_id'] === $uid)) {
            q_run($conexion, "DELETE FROM foro_hilos WHERE id = ?", 'i', [$id]);
            volver('foros.php', 'Tema eliminado.');
        }
        volver('foros.php');
    }

    if ($acc === 'borrar_respuesta') {
        $id = (int) ($_POST['id'] ?? 0);
        $r = q_one($conexion, "SELECT usuario_id, hilo_id FROM foro_respuestas WHERE id = ?", 'i', [$id]);
        if ($r && ($esMod || (int) $r['usuario_id'] === $uid)) {
            q_run($conexion, "DELETE FROM foro_respuestas WHERE id = ?", 'i', [$id]);
            volver('foros.php?hilo=' . (int) $r['hilo_id'], 'Respuesta eliminada.');
        }
        volver('foros.php');
    }

    if ($acc === 'fijar' && $esMod) {
        $id = (int) ($_POST['id'] ?? 0);
        q_run($conexion, "UPDATE foro_hilos SET fijado = 1 - fijado WHERE id = ?", 'i', [$id]);
        volver('foros.php?hilo=' . $id);
    }
    volver('foros.php');
}

/* ---------- Datos ---------- */
$comunidades = q_all($conexion, "SELECT id, slug, nombre, simbolo FROM comunidades ORDER BY nombre");
$hiloId = (int) ($_GET['hilo'] ?? 0);
$hilo = null; $respuestas = []; $lista = []; $filtro = $_GET['c'] ?? '';

if ($hiloId) {
    $hilo = q_one($conexion,
        "SELECT h.*, u.nombre AS autor, u.rol AS autor_rol, c.nombre AS com_nombre, c.simbolo, c.slug
         FROM foro_hilos h JOIN usuarios u ON u.id = h.usuario_id LEFT JOIN comunidades c ON c.id = h.comunidad_id WHERE h.id = ?", 'i', [$hiloId]);
    if ($hilo) {
        $respuestas = q_all($conexion,
            "SELECT r.id, r.contenido, r.fecha, r.usuario_id, u.nombre AS autor, u.rol AS autor_rol
             FROM foro_respuestas r JOIN usuarios u ON u.id = r.usuario_id WHERE r.hilo_id = ? ORDER BY r.fecha, r.id", 'i', [$hiloId]);
    }
} else {
    $sql = "SELECT h.id, h.titulo, h.contenido, h.fijado, h.ultima_actividad, u.nombre AS autor, u.rol AS autor_rol,
                   c.nombre AS com_nombre, c.simbolo, c.slug,
                   (SELECT COUNT(*) FROM foro_respuestas r WHERE r.hilo_id = h.id) AS respuestas
            FROM foro_hilos h JOIN usuarios u ON u.id = h.usuario_id LEFT JOIN comunidades c ON c.id = h.comunidad_id";
    $tipos = ''; $par = [];
    if ($filtro === 'general') { $sql .= " WHERE h.comunidad_id IS NULL"; }
    elseif ($filtro !== '')    { $sql .= " WHERE c.slug = ?"; $tipos = 's'; $par = [$filtro]; }
    $sql .= " ORDER BY h.fijado DESC, h.ultima_actividad DESC LIMIT 40";
    $lista = q_all($conexion, $sql, $tipos, $par);
}

function chip_rol($rol) {
    if ($rol === 'admin') return '<span class="foro-rol foro-rol--admin">Equipo</span>';
    if ($rol === 'moderador') return '<span class="foro-rol foro-rol--mod">Moderación</span>';
    return '';
}
function form_borrar($accion, $id, $texto) {
    return '<form method="post" class="foro-inline" onsubmit="return confirm(\'¿Eliminar definitivamente?\')">'
         . '<input type="hidden" name="csrf" value="' . e(csrf()) . '"><input type="hidden" name="accion" value="' . e($accion) . '">'
         . '<input type="hidden" name="id" value="' . (int) $id . '"><button type="submit" class="foro-link foro-link--peligro">' . e($texto) . '</button></form>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $hilo ? e($hilo['titulo']) . ' - Foros' : 'Foros' ?> - The Multiverse of Madness</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Orbitron:wght@600;800;900&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/foros.css">
<link rel="stylesheet" href="../css/nav-movil.css">
<link rel="stylesheet" href="../css/yalu.css">
</head>
<body>
<header class="navbar">
  <div class="navbar__inner">
    <a href="../index.html" class="navbar__logo">
      <span class="navbar__logo-ring" aria-hidden="true"></span>
      <span class="navbar__logo-text">MULTIVERSE<span class="navbar__logo-accent">OF MADNESS</span></span>
    </a>
    <nav class="navbar__nav" aria-label="Navegación principal">
      <a href="../index.html" class="navbar__link">Inicio</a>
      <a href="../html/eventos.html" class="navbar__link">Eventos</a>
      <a href="../html/comunidad.html" class="navbar__link">Comunidades</a>
      <a href="foros.php" class="navbar__link is-active">Foros</a>
      <a href="../html/contacto.html" class="navbar__link">Contacto</a>
    </nav>
    <?php if ($uid): ?>
      <span class="foro-user"><span class="foro-av"><?= e(inicial($_SESSION['nombre'] ?? '')) ?></span><?= e($_SESSION['nombre'] ?? '') ?> · <a href="cerrar.php">Salir</a></span>
    <?php else: ?>
      <a href="../login.html" class="btn btn--ghost btn--small navbar__cta">Entrar</a>
    <?php endif; ?>
  </div>
</header>

<main class="foro">
<?php if ($hiloId && !$hilo): ?>
  <div class="foro-wrap">
    <div class="foro-vacio"><h1>Ese tema no existe</h1><p>Puede que lo hayan eliminado.</p><a class="foro-btn" href="foros.php">Volver a los foros</a></div>
  </div>

<?php elseif ($hilo): ?>
  <div class="foro-wrap foro-wrap--estrecho">
    <a href="foros.php<?= $hilo['slug'] ? '?c=' . e($hilo['slug']) : '' ?>" class="foro-volver">← Volver a los foros</a>
    <?php if ($aviso): ?><p class="foro-aviso"><?= e($aviso) ?></p><?php endif; ?>

    <article class="foro-post foro-post--principal">
      <div class="foro-tags">
        <span class="foro-tag"><?= $hilo['com_nombre'] ? e($hilo['simbolo'] . ' ' . $hilo['com_nombre']) : '🌐 General' ?></span>
        <?php if ($hilo['fijado']): ?><span class="foro-tag foro-tag--fijo">📌 Fijado</span><?php endif; ?>
      </div>
      <h1><?= e($hilo['titulo']) ?></h1>
      <div class="foro-autor">
        <span class="foro-av"><?= e(inicial($hilo['autor'])) ?></span>
        <div><strong><?= e($hilo['autor']) ?></strong> <?= chip_rol($hilo['autor_rol']) ?><br><small><?= e(hace($hilo['fecha'])) ?></small></div>
      </div>
      <div class="foro-texto"><?= nl2br(e($hilo['contenido'])) ?></div>
      <?php if ($esMod || (int) $hilo['usuario_id'] === $uid): ?>
        <div class="foro-acciones">
          <?php if ($esMod): ?>
            <form method="post" class="foro-inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="accion" value="fijar"><input type="hidden" name="id" value="<?= (int) $hilo['id'] ?>"><button class="foro-link" type="submit"><?= $hilo['fijado'] ? 'Quitar fijado' : 'Fijar arriba' ?></button></form>
          <?php endif; ?>
          <?= form_borrar('borrar_hilo', $hilo['id'], 'Eliminar tema') ?>
        </div>
      <?php endif; ?>
    </article>

    <h2 class="foro-sub"><?= count($respuestas) ?> <?= count($respuestas) === 1 ? 'respuesta' : 'respuestas' ?></h2>
    <?php foreach ($respuestas as $r): ?>
      <article class="foro-post" id="r<?= (int) $r['id'] ?>">
        <div class="foro-autor">
          <span class="foro-av"><?= e(inicial($r['autor'])) ?></span>
          <div><strong><?= e($r['autor']) ?></strong> <?= chip_rol($r['autor_rol']) ?><br><small><?= e(hace($r['fecha'])) ?></small></div>
        </div>
        <div class="foro-texto"><?= nl2br(e($r['contenido'])) ?></div>
        <?php if ($esMod || (int) $r['usuario_id'] === $uid): ?><div class="foro-acciones"><?= form_borrar('borrar_respuesta', $r['id'], 'Eliminar') ?></div><?php endif; ?>
      </article>
    <?php endforeach; ?>

    <?php if ($uid): ?>
      <form method="post" class="foro-form">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="accion" value="responder">
        <input type="hidden" name="hilo_id" value="<?= (int) $hilo['id'] ?>">
        <label for="resp">Tu respuesta</label>
        <textarea id="resp" name="contenido" rows="4" maxlength="3000" required placeholder="Aporta algo a la conversación…"></textarea>
        <button class="foro-btn" type="submit">Responder</button>
      </form>
    <?php else: ?>
      <div class="foro-login"><p>Para responder necesitas una cuenta.</p><a class="foro-btn" href="../login.html">Iniciar sesión</a> <a class="foro-btn foro-btn--sec" href="../unete.html">Crear cuenta</a></div>
    <?php endif; ?>
  </div>

<?php else: ?>
  <section class="foro-hero">
    <div class="foro-wrap">
      <p class="eyebrow">Foros</p>
      <h1>Conversaciones del multiverso</h1>
      <p class="foro-lead">Abre un tema, responde a otros y encuentra a tu gente. Cada comunidad tiene su propio espacio, y hay uno general para todo lo demás.</p>
    </div>
  </section>

  <div class="foro-wrap foro-layout">
    <div>
      <?php if ($aviso): ?><p class="foro-aviso"><?= e($aviso) ?></p><?php endif; ?>
      <div class="foro-filtros" role="group" aria-label="Filtrar por comunidad">
        <a href="foros.php" class="<?= $filtro === '' ? 'is-on' : '' ?>">Todos</a>
        <a href="foros.php?c=general" class="<?= $filtro === 'general' ? 'is-on' : '' ?>">🌐 General</a>
        <?php foreach ($comunidades as $c): ?>
          <a href="foros.php?c=<?= e($c['slug']) ?>" class="<?= $filtro === $c['slug'] ? 'is-on' : '' ?>"><?= e($c['simbolo'] . ' ' . $c['nombre']) ?></a>
        <?php endforeach; ?>
      </div>

      <?php if (!$lista): ?>
        <div class="foro-vacio"><h2>Aquí todavía no hay temas</h2><p>Sé la primera persona en abrir uno.</p></div>
      <?php endif; ?>
      <?php foreach ($lista as $h): ?>
        <a class="foro-item" href="foros.php?hilo=<?= (int) $h['id'] ?>">
          <span class="foro-av foro-av--lg"><?= e(inicial($h['autor'])) ?></span>
          <span class="foro-item__main">
            <span class="foro-tags">
              <span class="foro-tag"><?= $h['com_nombre'] ? e($h['simbolo'] . ' ' . $h['com_nombre']) : '🌐 General' ?></span>
              <?php if ($h['fijado']): ?><span class="foro-tag foro-tag--fijo">📌 Fijado</span><?php endif; ?>
            </span>
            <strong class="foro-item__t"><?= e($h['titulo']) ?></strong>
            <span class="foro-item__x"><?= e(mb_substr($h['contenido'], 0, 120)) ?><?= mb_strlen($h['contenido']) > 120 ? '…' : '' ?></span>
            <small><?= e($h['autor']) ?> <?= chip_rol($h['autor_rol']) ?> · <?= e(hace($h['ultima_actividad'])) ?></small>
          </span>
          <span class="foro-item__n"><b><?= (int) $h['respuestas'] ?></b><small>resp.</small></span>
        </a>
      <?php endforeach; ?>
    </div>

    <aside class="foro-side">
      <?php if ($uid): ?>
        <form method="post" class="foro-form foro-form--lado" id="nuevo">
          <h2>Nuevo tema</h2>
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <input type="hidden" name="accion" value="nuevo_hilo">
          <label for="com">Dónde publicar</label>
          <select id="com" name="comunidad_id">
            <option value="0">🌐 General</option>
            <?php foreach ($comunidades as $c): ?>
              <option value="<?= (int) $c['id'] ?>"<?= $filtro === $c['slug'] ? ' selected' : '' ?>><?= e($c['simbolo'] . ' ' . $c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <label for="tit">Título</label>
          <input id="tit" name="titulo" type="text" maxlength="150" required placeholder="¿De qué quieres hablar?">
          <label for="cont">Mensaje</label>
          <textarea id="cont" name="contenido" rows="5" maxlength="5000" required placeholder="Cuéntanos más…"></textarea>
          <button class="foro-btn" type="submit">Publicar tema</button>
        </form>
      <?php else: ?>
        <div class="foro-login foro-login--lado">
          <h2>¿Quieres participar?</h2>
          <p>Puedes leer todo sin cuenta. Para abrir temas y responder, inicia sesión.</p>
          <a class="foro-btn" href="../login.html">Iniciar sesión</a>
          <a class="foro-btn foro-btn--sec" href="../unete.html">Crear cuenta</a>
        </div>
      <?php endif; ?>
      <div class="foro-reglas">
        <h3>Reglas del multiverso</h3>
        <ul><li>Respeta a las demás personas.</li><li>Nada de spoilers sin avisar.</li><li>Usa la comunidad que corresponde.</li></ul>
      </div>
    </aside>
  </div>
<?php endif; ?>
</main>

<script src="../js/nav.js" defer></script>
<script src="../js/yalu.js" data-news="false" defer></script>
</body>
</html>
