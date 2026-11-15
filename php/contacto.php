<?php
// Guarda los mensajes del formulario de contacto (html/contacto.html).
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/contacto.html");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$asunto = trim($_POST["asunto"] ?? "");
$mensaje = trim($_POST["mensaje"] ?? "");

if ($nombre === "" || $asunto === "" || $mensaje === "") {
    header("Location: ../html/contacto.html?error=vacio#formulario");
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../html/contacto.html?error=correo_invalido#formulario");
    exit;
}

$stmt = mysqli_prepare($conexion, "INSERT INTO mensajes_contacto (nombre, correo, asunto, mensaje) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssss", $nombre, $correo, $asunto, $mensaje);
$ok = mysqli_stmt_execute($stmt);

header("Location: ../html/contacto.html?" . ($ok ? "enviado=1" : "error=servidor") . "#formulario");
exit;