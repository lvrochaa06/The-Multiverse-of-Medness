
<?php
session_start();
require_once "conexion.php";

// Comprobar que el formulario se haya enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo = trim($_POST["correo"]);
    $password = $_POST["password"];

    // Buscar el usuario por su correo
    $consulta = "SELECT id, nombre, correo, password_hash FROM usuarios WHERE correo = ?";
    $stmt = mysqli_prepare($conexion, $consulta);

    mysqli_stmt_bind_param($stmt, "s", $correo);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    // Comprobar si existe el usuario
    if (mysqli_num_rows($resultado) == 1) {

        $usuario = mysqli_fetch_assoc($resultado);

        // Verificar la contraseña
        if (password_verify($password, $usuario["password_hash"])) {

            $_SESSION["id_usuario"] = $usuario["id"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["correo"] = $usuario["correo"];

            header("Location: ../index.html");
            exit;

        } else {
            header("Location: ../iniciarsesion.html?error=password");
            exit;
        }

    } else {
        header("Location: ../iniciarsesion.html?error=usuario");
        exit;
    }

} else {
    header("Location: ../iniciarsesion.html");
    exit;
}
?>
