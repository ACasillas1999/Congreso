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
    die("Token no proporcionado.");
}

$token = urldecode($_GET['token']);
$id = openssl_decrypt($token, METODO_CIFRADO, CLAVE_SECRETA, 0, VECTOR);

if (!$id || !is_numeric($id)) {
    die("Token invalido.");
}

$sql = "SELECT Ruta_Gafete FROM participante WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $ruta = resolverRutaGafete($row['Ruta_Gafete'] ?? '', (int)$id);

    if ($ruta !== null) {
        if (($row['Ruta_Gafete'] ?? '') !== $ruta) {
            $upd = $conn->prepare("UPDATE participante SET Ruta_Gafete = ? WHERE ID = ?");
            if ($upd) {
                $upd->bind_param("si", $ruta, $id);
                $upd->execute();
                $upd->close();
            }
        }

        header('Content-Description: File Transfer');
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="gafete_' . $id . '.jpg"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    echo "El archivo no existe.";
} else {
    echo "Participante no encontrado.";
}

$conn->close();
