
<?php
session_start();
require_once "conexion.php";

// Recibir los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $correo = trim($_POST["correo"]);
    $password = $_POST["password"];

    // Comprobar que los campos no esten vacios
    if (empty($nombre) || empty($correo) || empty($password)) {
        header("Location: ../registro.html?error=vacio");
        exit;
    }

    // Comprobar si el correo ya esta registrado
    $consulta = "SELECT id FROM usuarios WHERE correo = ?";
    $stmt = mysqli_prepare($conexion, $consulta);

    mysqli_stmt_bind_param($stmt, "s", $correo);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) > 0) {
        header("Location: ../registro.html?error=correo");
        exit;
    }

    // Guardar la contraseña de forma segura
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Registrar el nuevo usuario
    $consulta = "INSERT INTO usuarios (nombre, correo, password_hash)
                 VALUES (?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $consulta);

    mysqli_stmt_bind_param($stmt, "sss", $nombre, $correo, $password_hash);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../iniciarsesion.html?registro=exitoso");
        exit;
    } else {
        echo "Error al registrar el usuario.";
    }

} else {
    header("Location: ../registro.html");
    exit;
}
?>
