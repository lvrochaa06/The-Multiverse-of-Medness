<?php
// Guarda los mensajes del formulario de contacto (html/contacto.html).
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/contacto.html");
    exit;
}

$nombre  = trim($_POST["nombre"] ?? "");
$correo  = trim($_POST["correo"] ?? "");
$asunto  = trim($_POST["asunto"] ?? "");
$mensaje = trim($_POST["mensaje"] ?? "");
$tipo    = $_POST["tipo"] ?? "duda";
$acepta  = isset($_POST["acepta_publicar"]) ? 1 : 0;

if (!in_array($tipo, ['duda', 'idea', 'comunidad', 'problema', 'otro'], true)) $tipo = 'duda';

if ($nombre === "" || $asunto === "" || $mensaje === "") {
    header("Location: ../html/contacto.html?error=vacio#formulario");
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../html/contacto.html?error=correo_invalido#formulario");
    exit;
}

$nombre  = mb_substr($nombre, 0, 80);
$asunto  = mb_substr($asunto, 0, 120);
$mensaje = mb_substr($mensaje, 0, 1000);

$ok = q_run($conexion,
    "INSERT INTO mensajes_contacto (nombre, correo, asunto, mensaje, tipo, acepta_publicar) VALUES (?, ?, ?, ?, ?, ?)",
    "sssssi", [$nombre, $correo, $asunto, $mensaje, $tipo, $acepta]);

header("Location: ../html/contacto.html?" . ($ok ? "enviado=1" : "error=servidor") . "#formulario");
exit;
