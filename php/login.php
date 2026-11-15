<?php
// Procesa el formulario de login (login.html). Guarda los datos del usuario en sesión.
session_start();
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.html");
    exit;
}

$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";

// Consulta preparada: evita inyección SQL
$stmt = mysqli_prepare($conexion, "SELECT id, nombre, correo, password_hash, rol FROM usuarios WHERE correo = ?");
mysqli_stmt_bind_param($stmt, "s", $correo);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$usuario) {
    header("Location: ../login.html?error=usuario");
    exit;
}
if (!password_verify($password, $usuario["password_hash"])) {
    header("Location: ../login.html?error=password");
    exit;
}

session_regenerate_id(true); // evita fijación de sesión
$_SESSION["id_usuario"] = $usuario["id"];
$_SESSION["nombre"] = $usuario["nombre"];
$_SESSION["correo"] = $usuario["correo"];
$_SESSION["rol"] = $usuario["rol"];

// Los administradores van directo al panel
header("Location: " . ($usuario["rol"] === "admin" ? "admin.php" : "../index.html"));
exit;