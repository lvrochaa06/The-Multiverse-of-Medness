<?php
// Conexión con la base de datos (XAMPP: usuario root sin contraseña)
// Mantiene el comportamiento clásico: si una consulta falla devuelve false en vez de lanzar excepción.
mysqli_report(MYSQLI_REPORT_OFF);

$conexion = mysqli_connect("localhost", "root", "", "multiverse");

if (!$conexion) {
    http_response_code(500);
    die("Error al conectar con la base de datos: " . mysqli_connect_error());
}

// Permite trabajar con tildes y caracteres especiales
mysqli_set_charset($conexion, "utf8mb4");
