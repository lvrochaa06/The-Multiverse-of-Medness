
<?php
session_start();

// Eliminar los datos de la sesión
session_unset();
session_destroy();

// Regresar al inicio
header("Location: ../index.html");
exit;
?>
