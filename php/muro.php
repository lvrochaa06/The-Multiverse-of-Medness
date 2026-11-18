<?php
// Muro público de contacto: solo mensajes publicados por el equipo y con respuesta.
// Nunca expone el correo; el nombre se acorta (María L.).
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/conexion.php';
$filas = q_all($conexion,
    "SELECT m.nombre, m.tipo, m.asunto, m.mensaje, m.respuesta, m.fecha, u.nombre AS autor
     FROM mensajes_contacto m
     LEFT JOIN usuarios u ON u.id = m.respondido_por
     WHERE m.publico = 1 AND m.acepta_publicar = 1 AND m.respuesta IS NOT NULL AND m.respuesta <> ''
     ORDER BY m.fecha_respuesta DESC, m.id DESC LIMIT 20");
foreach ($filas as &$f) { $f['nombre'] = nombre_publico($f['nombre']); }
unset($f);
json_out($filas);
