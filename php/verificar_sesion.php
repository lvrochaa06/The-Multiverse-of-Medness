<?php
// Funciones para proteger páginas. Uso: require_once "verificar_sesion.php"; verificarAdmin();
if (session_status() === PHP_SESSION_NONE) session_start();

function verificarSesion() {
    if (!isset($_SESSION["id_usuario"])) {
        header("Location: ../login.html");
        exit;
    }
}

function verificarAdmin() {
    verificarSesion();
    if (($_SESSION["rol"] ?? "") !== "admin") {
        header("Location: ../index.html");
        exit;
    }
}