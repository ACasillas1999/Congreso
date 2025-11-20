<?php
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

if (!isset($_GET['token'])) {
    die("❌ Token no proporcionado.");
}

$token = urldecode($_GET['token']);
$id = openssl_decrypt($token, METODO_CIFRADO, CLAVE_SECRETA, 0, VECTOR);

if (!$id || !is_numeric($id)) {
    die("❌ Token inválido.");
}

// Buscar el gafete
$sql = "SELECT Ruta_Gafete FROM participante WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $ruta = $row['Ruta_Gafete'];

    if (file_exists($ruta)) {
        header('Content-Description: File Transfer');
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="gafete_' . $id . '.jpg"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    } else {
        echo "⚠️ El archivo no existe.";
    }
} else {
    echo "⚠️ Participante no encontrado.";
}

$conn->close();
