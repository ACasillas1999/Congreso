<?php
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

function resolverRutaGafete(?string $rutaGuardada, int $id): ?string
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

if (!isset($_GET['token'])) {
    http_response_code(400);
    exit("Token no proporcionado.");
}
$token = $_GET['token'];
// Por si algún cliente u otro lugar del código rompió la codificación
$token = str_replace(' ', '+', $token);

$id = openssl_decrypt($token, METODO_CIFRADO, CLAVE_SECRETA, 0, VECTOR);

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    exit("Token invalido.");
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

if (!($row = $result->fetch_assoc())) {
    $stmt->close();
    $conn->close();
    http_response_code(404);
    exit("Participante no encontrado.");
}

$ruta = resolverRutaGafete($row['Ruta_Gafete'] ?? '', (int)$id);
if ($ruta === null) {
    $stmt->close();
    $conn->close();
    http_response_code(404);
    exit("El archivo no existe.");
}

if (($row['Ruta_Gafete'] ?? '') !== $ruta) {
    $upd = $conn->prepare("UPDATE participante SET Ruta_Gafete = ? WHERE ID = ?");
    if ($upd) {
        $upd->bind_param("si", $ruta, $id);
        $upd->execute();
        $upd->close();
    }
}

$stmt->close();
$conn->close();

while (ob_get_level()) {
    ob_end_clean();
}

$ext = strtolower((string)pathinfo($ruta, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if (in_array($ext, ['jpg', 'jpeg'], true)) {
    $mime = 'image/jpeg';
} elseif ($ext === 'png') {
    $mime = 'image/png';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="gafete_' . $id . '.' . $ext . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($ruta);
exit;
