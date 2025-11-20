<?php
// (Opcional) Autenticación
/*
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}
*/

require_once __DIR__ . "/Conexiones/Conexion.php";

// ===== Helpers =====
function is_url($s) {
    return (bool)preg_match('#^https?://#i', $s);
}
function looks_like_host_prefix($s) {
    // ej: "192.168.60.194/Congreso/..." o "mi-dominio.com/ruta/..."
    return (bool)preg_match('#^[A-Za-z0-9\.\-]+/[^/].*#', $s);
}
function resolve_public_to_abs($publicPath) {
    $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    $cands = [];

    if ($publicPath !== '' && $publicPath[0] === '/') {
        // tal cual
        $cands[] = $docroot . $publicPath;

        // fallback mayúsculas/minúsculas del primer segmento
        if (stripos($publicPath, '/congreso/') === 0) {
            $cands[] = $docroot . '/Congreso/' . ltrim(substr($publicPath, strlen('/congreso/')), '/');
        } elseif (stripos($publicPath, '/Congreso/') === 0) {
            $cands[] = $docroot . '/congreso/' . ltrim(substr($publicPath, strlen('/Congreso/')), '/');
        }
    } else {
        // relativa: probar respecto a este script y al docroot
        $cands[] = __DIR__ . '/' . ltrim($publicPath, '/');
        if ($docroot) $cands[] = $docroot . '/' . ltrim($publicPath, '/');
    }

    foreach ($cands as $abs) {
        $real = realpath($abs);
        if ($real && is_file($real) && is_readable($real)) return $real;
    }
    return null;
}
function output_file_download($absPath, $downloadName = null) {
    if (!is_file($absPath) || !is_readable($absPath)) {
        http_response_code(404);
        exit("El archivo no existe o no es legible.");
    }
    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) { $d = finfo_file($f, $absPath); if ($d) $mime = $d; finfo_close($f); }
    }
    if (!$downloadName) $downloadName = basename($absPath);

    while (ob_get_level()) { ob_end_clean(); }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($absPath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    readfile($absPath);
    exit;
}

// ===== Entrada =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit("ID inválido."); }

// ===== Consulta =====
$sql = "SELECT Ruta_Horario FROM participante WHERE ID = ? AND COALESCE(Ruta_Horario,'') <> ''";
$stmt = $conn->prepare($sql);
if (!$stmt) { http_response_code(500); exit("Error al preparar consulta."); }
$stmt->bind_param("i", $id);
if (!$stmt->execute()) { http_response_code(500); exit("Error al ejecutar consulta."); }
$res = $stmt->get_result();
if ($res->num_rows === 0) { http_response_code(404);
header("Location: /Congreso/no-horario.html?id=" . urlencode($id));
exit; }
$row = $res->fetch_assoc();
$stmt->close();

$stored = trim($row['Ruta_Horario']);

if ($stored !== '' && $stored[0] === '/' && is_file($stored) && is_readable($stored)) {
    $downloadName = 'Horario_' . $id . '.' . pathinfo($stored, PATHINFO_EXTENSION);
    output_file_download($stored, $downloadName);
}

// Normalización de lo almacenado
// Caso 1: URL completa (http/https)
if (is_url($stored)) {
    $path = parse_url($stored, PHP_URL_PATH) ?: '';
    $abs  = $path ? resolve_public_to_abs($path) : null;

    if ($abs) {
        $downloadName = 'Horario_' . $id . '.' . pathinfo($abs, PATHINFO_EXTENSION);
        output_file_download($abs, $downloadName);
    } else {
        // No se pudo mapear al FS: redirigir
        header("Location: " . $stored);
        exit;
    }
}

// Caso 2: cadena tipo "host/ruta" (ej. "192.168.60.194/Congreso/.../archivo.png")
if (looks_like_host_prefix($stored)) {
    $slashPos = strpos($stored, '/');
    $publicPath = substr($stored, $slashPos); // desde el primer "/" -> "/Congreso/..."
    $abs = resolve_public_to_abs($publicPath);
    if ($abs) {
        $downloadName = 'Horario_' . $id . '.' . pathinfo($abs, PATHINFO_EXTENSION);
        output_file_download($abs, $downloadName);
    } else {
        // como fallback, redirige intentando http://
        $url = 'https://' . $stored;
        header("Location: " . $url);
        exit;
    }
}

// Caso 3: ya es ruta pública (/Congreso/...) o relativa
$abs = resolve_public_to_abs($stored);
if ($abs) {
    $downloadName = 'Horario_' . $id . '.' . pathinfo($abs, PATHINFO_EXTENSION);
    output_file_download($abs, $downloadName);
}

// Último intento: si empieza con /Congreso/ pero físicamente es /congreso/ (o al revés)
if (stripos($stored, '/congreso/') === 0) {
    $alt = '/Congreso/' . ltrim(substr($stored, strlen('/congreso/')), '/');
    $abs = resolve_public_to_abs($alt);
    if ($abs) {
        $downloadName = 'Horario_' . $id . '.' . pathinfo($abs, PATHINFO_EXTENSION);
        output_file_download($abs, $downloadName);
    }
} elseif (stripos($stored, '/Congreso/') === 0) {
    $alt = '/congreso/' . ltrim(substr($stored, strlen('/Congreso/')), '/');
    $abs = resolve_public_to_abs($alt);
    if ($abs) {
        $downloadName = 'Horario_' . $id . '.' . pathinfo($abs, PATHINFO_EXTENSION);
        output_file_download($abs, $downloadName);
    }
}

// Si nada funcionó
http_response_code(404);
exit("No se pudo resolver/descargar el horario.");
