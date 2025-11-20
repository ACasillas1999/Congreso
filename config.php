<?php
define('CLAVE_SECRETA', 'MiClaveSuperSegura1234'); // cámbiala por algo más complejo si quieres
define('METODO_CIFRADO', 'AES-256-CBC');
define('VECTOR', substr(hash('sha256', 'vectorConexion2025'), 0, 16));
?>
