
<?php
// Conexion con la base de datos
$conexion = mysqli_connect("localhost", "root", "", "multiverse");

if (!$conexion) {
    die("Error al conectar con la base de datos: " . mysqli_connect_error());
}

// Permite trabajar con tildes y caracteres especiales
mysqli_set_charset($conexion, "utf8mb4");
?>

