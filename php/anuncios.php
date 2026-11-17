<?php
// Anuncios "próximamente" que Yalu muestra en el inicio (se editan desde el panel admin).
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/conexion.php';
$f = q_all($conexion, "SELECT CONCAT('a', id) AS id, icono, etiqueta, titulo, texto FROM anuncios WHERE activo = 1 ORDER BY orden, id LIMIT 5");
json_out($f);
