<?php
// Descargar Horario - Versión Simplificada
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

function renderHorarioError(string $titulo, string $mensaje, ?string $link = null, ?string $linkLabel = null): void {
    ob_start();
    include __DIR__ . "/header_css.php";
    $themeCss = ob_get_clean();

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1'>{$themeCss}<style>
    body{margin:0;padding:24px;font-family:'Segoe UI',sans-serif;background:radial-gradient(circle at center,var(--theme-primary,#202040),var(--theme-primary-dark,#121212));color:var(--theme-text,#fff);display:flex;justify-content:center;align-items:center;min-height:100vh}
    .card{max-width:680px;width:100%;background:var(--theme-surface-strong,#1e1e2f);border:1px solid var(--theme-border,rgba(255,255,255,.12));border-radius:18px;padding:28px;box-shadow:var(--theme-shadow,0 10px 28px rgba(0,0,0,.35))}
    h2{margin-top:0;color:var(--theme-title,#7cecff)}
    a{display:inline-block;margin-top:18px;padding:12px 18px;border-radius:10px;background:linear-gradient(135deg,var(--naranja,#ff8c00),var(--theme-accent,#21a1f3));color:var(--theme-text,#fff);text-decoration:none;font-weight:700}
    code{word-break:break-all}
    </style></head><body><div class='card'><h2>{$titulo}</h2><p>{$mensaje}</p>";
    if ($link && $linkLabel) {
        echo "<p><a href='" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8') . "</a></p>";
    }
    echo "</div></body></html>";
    exit;
}

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
        renderHorarioError("Horario no disponible", "El horario para este participante aún no ha sido generado.", "debug_horario.php?id=$id", "Generar horario");
    }
    
    // Usar el archivo más reciente
    $rutaHorario = end($files);
}

// Verificar si el archivo existe
if (!file_exists($rutaHorario) || !is_readable($rutaHorario)) {
    http_response_code(404);
    renderHorarioError("Archivo no encontrado", "El archivo del horario no existe o no es accesible.<br><strong>Ruta buscada:</strong> <code>" . htmlspecialchars($rutaHorario) . "</code>", "debug_horario.php?id=$id", "Depurar problema");
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
