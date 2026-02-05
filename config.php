<?php
// ============================================
// CONFIGURACIÓN DE CIFRADO
// ============================================
define('CLAVE_SECRETA', 'MiClaveSuperSegura1234');
define('METODO_CIFRADO', 'AES-256-CBC');
define('VECTOR', substr(hash('sha256', 'vectorConexion2025'), 0, 16));

// ============================================
// DETECCIÓN AUTOMÁTICA DE ENTORNO
// ============================================
// Detecta si estamos en desarrollo local o producción
$isLocal = (
    $_SERVER['SERVER_ADDR'] === '192.168.60.194' || 
    $_SERVER['HTTP_HOST'] === '192.168.60.194' ||
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
    strpos($_SERVER['HTTP_HOST'], '192.168.') !== false
);

define('IS_LOCAL', $isLocal);
define('IS_PRODUCTION', !$isLocal);

// ============================================
// RUTAS SEGÚN ENTORNO
// ============================================
if (IS_LOCAL) {
    // DESARROLLO LOCAL
    define('BASE_PATH', __DIR__);
    define('MACHOTE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'Machote');
    define('GAFETES_OUTPUT', BASE_PATH . DIRECTORY_SEPARATOR . 'Machote' . DIRECTORY_SEPARATOR . 'Generados');
    define('HORARIOS_OUTPUT', BASE_PATH . DIRECTORY_SEPARATOR . 'Machote' . DIRECTORY_SEPARATOR . 'Horarios_Generados');
    define('QR_OUTPUT', BASE_PATH . DIRECTORY_SEPARATOR . 'qrcodes');
    
    // URLs públicas (para enlaces)
    define('BASE_URL', 'http://192.168.60.194/Congreso');
    define('GAFETES_URL', BASE_URL . '/Machote/Generados');
    define('HORARIOS_URL', BASE_URL . '/Machote/Horarios_Generados');
    
} else {
    // PRODUCCIÓN
    define('BASE_PATH', '/var/www/html/Congreso');
    define('MACHOTE_PATH', BASE_PATH . '/Machote');
    define('GAFETES_OUTPUT', BASE_PATH . '/Machote/Generados');
    define('HORARIOS_OUTPUT', BASE_PATH . '/Machote/Horarios_Generados');
    define('QR_OUTPUT', BASE_PATH . '/qrcodes');
    
    // URLs públicas (para enlaces)
    define('BASE_URL', 'https://congresos.grupoascencio.com.mx/Congreso');
    define('GAFETES_URL', BASE_URL . '/Machote/Generados');
    define('HORARIOS_URL', BASE_URL . '/Machote/Horarios_Generados');
}

// ============================================
// PLANTILLAS (MACHOTES)
// ============================================
define('TEMPLATE_GAFETE', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Gafetes_visitantes.jpg');
define('TEMPLATE_HORARIO_PORTRAIT', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Machote_Horario.png');
define('TEMPLATE_HORARIO_LANDSCAPE', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Machote_Horario_L.png');

// ============================================
// FUENTES
// ============================================
define('FONT_NEXA', BASE_PATH . DIRECTORY_SEPARATOR . 'Machote' . DIRECTORY_SEPARATOR . 'Font' . DIRECTORY_SEPARATOR . 'nexa-book.ttf');
define('FONT_ROBOTO', BASE_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'Roboto_Condensed-Black.ttf');

// ============================================
// CREAR DIRECTORIOS SI NO EXISTEN
// ============================================
$directories = [GAFETES_OUTPUT, HORARIOS_OUTPUT, QR_OUTPUT];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// ============================================
// FUNCIÓN HELPER: Obtener ruta completa
// ============================================
function getFullPath($relativePath) {
    return BASE_PATH . '/' . ltrim($relativePath, '/');
}

// ============================================
// FUNCIÓN HELPER: Obtener URL pública
// ============================================
function getPublicUrl($relativePath) {
    return BASE_URL . '/' . ltrim($relativePath, '/');
}
?>
