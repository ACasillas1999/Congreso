<?php
// ============================================
// CONFIGURACION DE CIFRADO
// ============================================
define('CLAVE_SECRETA', 'MiClaveSuperSegura1234');
define('METODO_CIFRADO', 'AES-256-CBC');
define('VECTOR', substr(hash('sha256', 'vectorConexion2025'), 0, 16));

// ============================================
// DETECCION AUTOMATICA DE ENTORNO
// ============================================
$serverAddr = (string)($_SERVER['SERVER_ADDR'] ?? '');
$httpHost = (string)($_SERVER['HTTP_HOST'] ?? '');
$serverPort = (string)($_SERVER['SERVER_PORT'] ?? '');
$httpsFlag = (string)($_SERVER['HTTPS'] ?? '');
$scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
$projectRoot = realpath(__DIR__) ?: __DIR__;
$isHttps = ($httpsFlag !== '' && strtolower($httpsFlag) !== 'off') || $serverPort === '443';

$isLocal = (
    $serverAddr === '192.168.60.194' ||
    $httpHost === '192.168.60.194' ||
    strpos($httpHost, 'localhost') !== false ||
    strpos($httpHost, '127.0.0.1') !== false ||
    strpos($httpHost, '192.168.') !== false
);

define('IS_LOCAL', $isLocal);
define('IS_PRODUCTION', !$isLocal);

// ============================================
// RUTAS DE DISCO
// ============================================
// La raiz real del proyecto debe salir del filesystem actual, no del host.
define('BASE_PATH', $projectRoot);
define('MACHOTE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'Machote');
define('GAFETES_OUTPUT', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Generados');
define('HORARIOS_OUTPUT', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Horarios_Generados');
define('QR_OUTPUT', BASE_PATH . DIRECTORY_SEPARATOR . 'qrcodes');

// ============================================
// URLS PUBLICAS
// ============================================
$scheme = $isHttps ? 'https' : 'http';
$basePath = str_replace('\\', '/', dirname($scriptName));
if ($basePath === '' || $basePath === '.') {
    $basePath = '/Congreso';
}

if ($httpHost !== '') {
    define('BASE_URL', $scheme . '://' . $httpHost . rtrim($basePath, '/'));
} elseif (IS_LOCAL) {
    define('BASE_URL', 'http://192.168.60.194/Congreso');
} else {
    define('BASE_URL', 'https://congresos.grupoascencio.com.mx/Congreso');
}

define('GAFETES_URL', BASE_URL . '/Machote/Generados');
define('HORARIOS_URL', BASE_URL . '/Machote/Horarios_Generados');

// ============================================
// PLANTILLAS (MACHOTES)
// ============================================
define('TEMPLATE_GAFETE', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Gafetes_visitantes.jpg');
define('TEMPLATE_HORARIO_PORTRAIT', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Machote_Horario.png');
define('TEMPLATE_HORARIO_LANDSCAPE', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Machote_Horario_L.png');

// ============================================
// FUENTES
// ============================================
define('FONT_NEXA', MACHOTE_PATH . DIRECTORY_SEPARATOR . 'Font' . DIRECTORY_SEPARATOR . 'nexa-book.ttf');
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
// HELPERS
// ============================================
function getFullPath($relativePath) {
    return BASE_PATH . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
}

function getPublicUrl($relativePath) {
    return BASE_URL . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
}

function buildAppUrl($relativePath) {
    return getPublicUrl($relativePath);
}
?>
