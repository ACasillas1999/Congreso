<?php
// Iniciar la sesión
session_name("CON");
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al usuario al formulario de inicio de sesión
header("location: /Congreso/Sesion/login.html");
exit;
?>
