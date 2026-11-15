<?php
// Panel de administrador: mensajes de contacto, comunidades y usuarios.
// Solo accesible con rol "admin" (ver verificar_sesion.php).
require_once __DIR__ . '/verificar_sesion.php';
require_once __DIR__ . '/conexion.php';
verificarAdmin();

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

// Token CSRF: evita que otra página envíe acciones en nombre del admin
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$aviso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }
    $accion = $_POST['accion'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($accion === 'leido') {
        $s = mysqli_prepare($conexion, "UPDATE mensajes_contacto SET leido = 1 WHERE id = ?");
        mysqli_stmt_bind_param($s, "i", $id); mysqli_stmt_execute($s);
    } elseif ($accion === 'borrar_mensaje') {
        $s = mysqli_prepare($conexion, "DELETE FROM mensajes_contacto WHERE id = ?");
        mysqli_stmt_bind_param($s, "i", $id); mysqli_stmt_execute($s);
    } elseif ($accion === 'borrar_comunidad') {
        $s = mysqli_prepare($conexion, "DELETE FROM comunidades WHERE id = ?"); // borra también sus items (ON DELETE CASCADE)
        mysqli_stmt_bind_param($s, "i", $id); mysqli_stmt_execute($s);
        $aviso = 'Comunidad eliminada.';
    } elseif ($accion === 'nueva_comunidad') {
        $slug = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['slug'] ?? '')));
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $imagen = basename(trim($_POST['imagen'] ?? ''));
        $desc = trim($_POST['descripcion'] ?? '');
        if ($slug === '' || $nombre === '' || $categoria === '' || $imagen === '' || $desc === '') {
            $aviso = 'Completa todos los campos de la comunidad.';
        } else {
            $s = mysqli_prepare($conexion, "INSERT INTO comunidades
                (slug,nombre,titulo,simbolo,texto_union,imagen,descripcion,descripcion2,miembros,fecha_creacion,categoria,sobre,fans)
                VALUES (?,?,?,'✦',?,?,?,'',0,CURDATE(),?,?,?)");
            $union = "¿Te interesa $categoria? Esta comunidad es para ti.";
            $fans = "fans de $categoria";
            mysqli_stmt_bind_param($s, "sssssssss", $slug, $nombre, $categoria, $union, $imagen, $desc, $categoria, $desc, $fans);
            $aviso = mysqli_stmt_execute($s) ? 'Comunidad creada.' : 'No se pudo crear (¿el slug ya existe?).';
        }
    }
}

$mensajes = mysqli_fetch_all(mysqli_query($conexion, "SELECT * FROM mensajes_contacto ORDER BY fecha DESC"), MYSQLI_ASSOC);
$comunidades = mysqli_fetch_all(mysqli_query($conexion, "SELECT id, slug, nombre, categoria, miembros FROM comunidades ORDER BY nombre"), MYSQLI_ASSOC);
$usuarios = mysqli_fetch_all(mysqli_query($conexion, "SELECT nombre, correo, rol, fecha_registro FROM usuarios ORDER BY fecha_registro DESC"), MYSQLI_ASSOC);
$sinLeer = count(array_filter($mensajes, fn($m) => !$m['leido']));

// Botón pequeño reutilizable para acciones POST
function boton($accion, $id, $texto, $csrf) {
    return '<form method="post" class="inline"><input type="hidden" name="csrf" value="' . e($csrf) . '">'
         . '<input type="hidden" name="accion" value="' . e($accion) . '"><input type="hidden" name="id" value="' . (int) $id . '">'
         . '<button type="submit">' . e($texto) . '</button></form>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel admin - The Multiverse of Madness</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<header class="admin-top">
    <h1>Panel de administrador</h1>
    <div>
        <span>Hola, <?= e($_SESSION['nombre']) ?></span>
        <a href="../index.html">Ver sitio</a>
        <a href="cerrar.php">Cerrar sesión</a>
    </div>
</header>

<main class="admin-main">
    <?php if ($aviso): ?><p class="aviso"><?= e($aviso) ?></p><?php endif; ?>

    <section class="card">
        <h2>Mensajes de contacto <small>(<?= $sinLeer ?> sin leer)</small></h2>
        <?php if (!$mensajes): ?><p>Todavía no hay mensajes.</p><?php endif; ?>
        <?php foreach ($mensajes as $m): ?>
            <article class="mensaje <?= $m['leido'] ? 'leido' : '' ?>">
                <strong><?= e($m['asunto']) ?></strong>
                <span><?= e($m['nombre']) ?> · <a href="mailto:<?= e($m['correo']) ?>"><?= e($m['correo']) ?></a> · <?= e($m['fecha']) ?></span>
                <p><?= nl2br(e($m['mensaje'])) ?></p>
                <?php if (!$m['leido']) echo boton('leido', $m['id'], 'Marcar leído', $_SESSION['csrf']); ?>
                <?= boton('borrar_mensaje', $m['id'], 'Eliminar', $_SESSION['csrf']) ?>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h2>Comunidades</h2>
        <div class="tabla-wrap"><table>
            <tr><th>Nombre</th><th>Categoría</th><th>Miembros</th><th>Página</th><th></th></tr>
            <?php foreach ($comunidades as $c): ?>
                <tr>
                    <td><?= e($c['nombre']) ?></td><td><?= e($c['categoria']) ?></td><td><?= (int) $c['miembros'] ?></td>
                    <td><a href="comunidad.php?slug=<?= e($c['slug']) ?>">Ver</a></td>
                    <td><?= boton('borrar_comunidad', $c['id'], 'Eliminar', $_SESSION['csrf']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table></div>

        <h3>Nueva comunidad</h3>
        <form method="post" class="form-nueva">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="accion" value="nueva_comunidad">
            <input name="slug" placeholder="slug (ej: manga)" required>
            <input name="nombre" placeholder="Nombre" required>
            <input name="categoria" placeholder="Categoría" required>
            <input name="imagen" placeholder="Imagen en /imagenes (ej: anime.jpg)" required>
            <textarea name="descripcion" placeholder="Descripción" required></textarea>
            <button type="submit">Crear comunidad</button>
        </form>
    </section>

    <section class="card">
        <h2>Usuarios registrados (<?= count($usuarios) ?>)</h2>
        <div class="tabla-wrap"><table>
            <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Registro</th></tr>
            <?php foreach ($usuarios as $u): ?>
                <tr><td><?= e($u['nombre']) ?></td><td><?= e($u['correo']) ?></td><td><?= e($u['rol']) ?></td><td><?= e($u['fecha_registro']) ?></td></tr>
            <?php endforeach; ?>
        </table></div>
    </section>
</main>
</body>
</html>