<?php
// Iniciar la sesion y verificar el inicio de sesion del usuario
/*
ini_set('session.cookie_httponly', true); // Solo permitir cookies de sesion via HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesion a traves de conexiones HTTPS
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}
*/

require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

function resolverRutaGafeteDescarga(?string $rutaGuardada, int $id): ?string
{
    $candidatas = [];
    $rutaGuardada = trim((string)$rutaGuardada);

    if ($rutaGuardada !== '') {
        $candidatas[] = $rutaGuardada;

        $normalizada = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rutaGuardada);
        if ($normalizada !== $rutaGuardada) {
            $candidatas[] = $normalizada;
        }

        $base = basename($rutaGuardada);
        if ($base !== '') {
            $candidatas[] = rtrim(GAFETES_OUTPUT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $base;
        }
    }

    $candidatas[] = rtrim(GAFETES_OUTPUT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Gafete_personalizado_' . $id . '.jpg';

    foreach (array_unique($candidatas) as $ruta) {
        if ($ruta !== '' && file_exists($ruta) && is_readable($ruta)) {
            return $ruta;
        }
    }

    return null;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    exit("ID invalido.");
}

$sql = "SELECT Ruta_Gafete FROM participante WHERE ID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("Error al preparar la consulta.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    http_response_code(404);
    exit("No se encontro el participante.");
}

$row = $result->fetch_assoc();
$stmt->close();

$filePath = resolverRutaGafeteDescarga($row["Ruta_Gafete"] ?? '', $id);
if ($filePath === null) {
    $conn->close();
    http_response_code(404);
    exit("El archivo no existe.");
}

if (($row["Ruta_Gafete"] ?? '') !== $filePath) {
    $upd = $conn->prepare("UPDATE participante SET Ruta_Gafete = ? WHERE ID = ?");
    if ($upd) {
        $upd->bind_param("si", $filePath, $id);
        $upd->execute();
        $upd->close();
    }
}

$conn->close();

while (ob_get_level()) {
    ob_end_clean();
}

$ext = strtolower((string)pathinfo($filePath, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if (in_array($ext, ['jpg', 'jpeg'], true)) {
    $mime = 'image/jpeg';
} elseif ($ext === 'png') {
    $mime = 'image/png';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($filePath);
exit;
