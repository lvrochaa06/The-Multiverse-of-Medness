<?php
// Lista de comunidades (para mostrar en el listado las que se crean desde el panel admin).
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/conexion.php';
json_out(q_all($conexion, "SELECT slug, nombre, titulo, simbolo, imagen, descripcion, categoria, miembros FROM comunidades ORDER BY id"));
