<?php
// Eventos activos en formato JSON (los usan html/eventos.html y Yalu).
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/conexion.php';
$f = q_all($conexion, "SELECT id, titulo, categoria, ciudad, lugar, fecha_inicio, fecha_fin, fecha_texto, imagen, enlace, descripcion, confirmado, destacado
                       FROM eventos WHERE activo = 1 ORDER BY fecha_inicio");
foreach ($f as &$r) { $r['id'] = (int) $r['id']; $r['confirmado'] = (int) $r['confirmado']; $r['destacado'] = (int) $r['destacado']; }
unset($r);
json_out($f);
