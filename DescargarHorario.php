<?php
// Descargar Horario - Versión Simplificada
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit("ID inválido.");
}

// Consultar ruta del horario
$sql = "SELECT Ruta_Horario FROM participante WHERE ID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("Error al preparar consulta.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit("No se encontró el participante.");
}

$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

$rutaHorario = $row['Ruta_Horario'];

// Si no hay ruta guardada, intentar buscar el archivo
if (empty($rutaHorario)) {
    // Buscar archivos de horario para este participante
    $pattern = HORARIOS_OUTPUT . '/Horario_' . $id . '_*.png';
    $files = glob($pattern);
    
    if (empty($files)) {
        http_response_code(404);
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
        echo "<h2>❌ Horario no disponible</h2>";
        echo "<p>El horario para este participante aún no ha sido generado.</p>";
        echo "<p><a href='debug_horario.php?id=$id'>🔧 Generar Horario</a></p>";
        echo "</body></html>";
        exit;
    }
    
    // Usar el archivo más reciente
    $rutaHorario = end($files);
}

// Verificar si el archivo existe
if (!file_exists($rutaHorario) || !is_readable($rutaHorario)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
    echo "<h2>❌ Archivo no encontrado</h2>";
    echo "<p>El archivo del horario no existe o no es accesible.</p>";
    echo "<p><strong>Ruta buscada:</strong> <code>" . htmlspecialchars($rutaHorario) . "</code></p>";
    echo "<p><a href='debug_horario.php?id=$id'>🔧 Depurar Problema</a></p>";
    echo "</body></html>";
    exit;
}

// Limpiar cualquier salida previa
while (ob_get_level()) {
    ob_end_clean();
}

// Determinar tipo MIME
$mime = 'application/octet-stream';
$ext = strtolower(pathinfo($rutaHorario, PATHINFO_EXTENSION));
if ($ext === 'png') {
    $mime = 'image/png';
} elseif ($ext === 'pdf') {
    $mime = 'application/pdf';
}

// Nombre del archivo para descarga
$downloadName = 'Horario_' . $id . '.' . $ext;

// Enviar headers
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($rutaHorario));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Enviar archivo
readfile($rutaHorario);
exit;
?>
