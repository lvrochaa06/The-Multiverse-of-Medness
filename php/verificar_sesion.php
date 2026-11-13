
<?php
session_start();

// Comprobar si hay una sesión iniciada
function verificarSesion() {
    if (!isset($_SESSION["id_usuario"])) {
        header("Location: ../iniciarsesion.html");
        exit;
    }
}

// Comprobar si el usuario es administrador
function verificarAdmin() {
    if (!isset($_SESSION["rol"]) || $_SESSION["rol"] != "admin") {
        header("Location: ../index.html");
        exit;
    }
}
?>
