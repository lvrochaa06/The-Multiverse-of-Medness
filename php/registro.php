<?php
// Procesa el formulario de registro (unete.html).
session_start();
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../unete.html");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";

if ($nombre === "" || $correo === "" || $password === "") {
    header("Location: ../unete.html?error=vacio");
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../unete.html?error=correo_invalido");
    exit;
}
if (strlen($password) < 6) {
    header("Location: ../unete.html?error=password_corta");
    exit;
}

// ¿Correo ya registrado?
$stmt = mysqli_prepare($conexion, "SELECT id FROM usuarios WHERE correo = ?");
mysqli_stmt_bind_param($stmt, "s", $correo);
mysqli_stmt_execute($stmt);
if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
    header("Location: ../unete.html?error=correo");
    exit;
}

// Nunca se guarda la contraseña en texto plano
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conexion, "INSERT INTO usuarios (nombre, correo, password_hash) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $nombre, $correo, $hash);

if (mysqli_stmt_execute($stmt)) {
    header("Location: ../login.html?registro=exitoso");
} else {
    header("Location: ../unete.html?error=servidor");
}
exit;