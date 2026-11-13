
<?php
// Plantilla para mostrar las comunidades
require_once __DIR__ . '/conexion.php';

// Funcion para proteger los textos que se muestran en HTML
function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Recibir el nombre de la comunidad
$slug = $_GET['slug'] ?? '';

// Consultar la comunidad
$consulta = "SELECT * FROM comunidades WHERE slug = ?";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$c = mysqli_fetch_assoc($resultado);

// Comprobar si existe la comunidad
if (!$c) {
    http_response_code(404);
    echo "Esa comunidad no existe. <a href='../html/comunidad.html'>Volver a comunidades</a>";
    exit;
}

// Consultar los elementos de la comunidad
$consulta = "SELECT texto FROM comunidad_items WHERE comunidad_id = ? ORDER BY orden";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $c['id']);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$items = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

// Consultar los miembros destacados
$consulta = "SELECT emoji, nombre, rol FROM miembros_destacados WHERE comunidad_id = ? ORDER BY orden";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $c['id']);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$destacados = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

// Organizar los parrafos de la comunidad
$parrafos = array_filter(array_map('trim', explode("\n", $c['sobre'])));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($c['nombre']) ?> - The Multiverse of Madness</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/comunidad.css">
</head>
<body>

    <div class="cosmic-bg">
        <div class="stars"></div>
        <div class="orb orb--purple"></div>
        <div class="orb orb--blue"></div>
        <div class="orb orb--cyan"></div>
    </div>

    <header class="navbar">
        <div class="navbar__inner">
            <a href="../index.html" class="navbar__logo">
                <span class="navbar__logo-ring"></span>
                <span class="navbar__logo-text">MULTIVERSE <span class="navbar__logo-accent">OF MADNESS</span></span>
            </a>
            <nav class="navbar__nav">
                <a href="../index.html" class="navbar__link">Inicio</a>
                <a href="../html/comunidad.html" class="navbar__link">Comunidad</a>
                <a href="../html/eventos.html" class="navbar__link">Eventos</a>
                <a href="../html/contacto.html" class="navbar__link">Contacto</a>
            </nav>
        </div>
    </header>

    <main class="community-page">

        <div class="community-heading">
            <a href="../html/comunidad.html" class="community-back">← Volver a comunidades</a>
            <p class="eyebrow">THE MULTIVERSE OF MADNESS</p>
            <h1 class="section-title"><?= e($c['titulo']) ?></h1>
        </div>

        <div class="community-layout">

            <aside class="community-left">
                <section class="community-panel join-panel">
                    <div class="join-panel__symbol"><?= e($c['simbolo']) ?></div>
                    <p class="eyebrow">ENCUENTRA TU UNIVERSO</p>
                    <h2>¡Te estamos esperando!</h2>
                    <p><?= e($c['texto_union']) ?></p>
                    <a href="../iniciarsesion.html" class="btn btn--primary">Unirme a la comunidad</a>
                    <small>Para unirte necesitas tener una cuenta.</small>
                </section>

                <section class="community-panel comments-panel">
                    <div class="panel-heading">
                        <h2>Comentarios</h2>
                        <span>🔒</span>
                    </div>
                    <p>Comparte tus opiniones con los integrantes de esta comunidad.</p>
                    <div class="comment-list">
                        <p>Todavía no hay comentarios.</p>
                    </div>
                    <form class="comment-form">
                        <textarea placeholder="Únete para escribir un comentario..." disabled></textarea>
                        <button type="button" class="btn btn--primary" disabled>Comentar</button>
                    </form>
                    <small>Debes unirte a la comunidad para comentar.</small>
                </section>
            </aside>

            <section class="community-panel community-info">
                <div class="community-cover">
                    <img src="../imagenes/<?= e($c['imagen']) ?>" alt="Portada de la comunidad <?= e($c['nombre']) ?>">
                    <div class="community-cover__content"><span>COMUNIDAD OFICIAL</span></div>
                </div>

                <div class="community-info__body">
                    <p class="eyebrow">BIENVENIDO A</p>
                    <h2><?= e($c['nombre']) ?></h2>
                    <p class="community-description"><?= e($c['descripcion']) ?> <?= e($c['descripcion2']) ?></p>

                    <div class="community-stats">
                        <div class="stat-item">
                            <strong><?= (int) $c['miembros'] ?></strong>
                            <span>Miembros</span>
                        </div>
                        <div class="stat-item">
                            <strong><?= e(date('d/m/Y', strtotime($c['fecha_creacion']))) ?></strong>
                            <span>Fecha de creación</span>
                        </div>
                        <div class="stat-item">
                            <strong><?= e($c['categoria']) ?></strong>
                            <span>Categoría</span>
                        </div>
                    </div>

                    <div class="community-details">
                        <h3>Sobre esta comunidad</h3>
                        <p><?= e(implode(' ', $parrafos)) ?></p>

                        <h3>¿Qué encontrarás aquí?</h3>
                        <ul>
                            <?php foreach ($items as $i): ?>
                                <li><?= e($i['texto']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <section class="community-posts">
                        <div class="panel-heading">
                            <h3>Publicaciones recientes</h3>
                            <span>🔒 Solo para miembros</span>
                        </div>
                        <div class="locked-posts">
                            <div>🔐</div>
                            <h3>¡Este espacio te está esperando!</h3>
                            <p>Las publicaciones de esta comunidad están reservadas para sus integrantes.
                               Únete para descubrir conversaciones, compartir tus opiniones y conocer a <?= e($c['fans']) ?>.</p>
                        </div>
                    </section>
                </div>
            </section>

            <aside class="community-panel stars-panel">
                <div class="panel-heading">
                    <h2>Miembros destacados</h2>
                    <span>★</span>
                </div>
                <p>Conoce a algunos de los integrantes que hacen especial esta comunidad.</p>
                <?php foreach ($destacados as $m): ?>
                    <div class="featured-member">
                        <div class="featured-avatar"><?= e($m['emoji']) ?></div>
                        <div class="featured-member__info">
                            <strong><?= e($m['nombre']) ?></strong>
                            <span><?= e($m['rol']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </aside>

        </div>
    </main>

</body>
</html>
