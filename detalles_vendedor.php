<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "Conexiones/Conexion.php";

$evento_id = isset($_GET['evento']) ? intval($_GET['evento']) : 0;
$vendedor  = $_GET['vendedor'] ?? '';
if ($evento_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Evento inválido']);
    exit;
}

// El label "(Sin vendedor)" se usa cuando el campo viene vacío
$matchSinVendedor = ($vendedor === '(Sin vendedor)');

// Traer participantes de ese vendedor + conteo de asistencias (≥0)
$sql = "
SELECT 
    p.ID,
    p.Nombre,
    p.Telefono,
    p.RFC,
    p.Sucursal,
    COALESCE(SUM(CASE WHEN c.Asistio = 1 THEN 1 ELSE 0 END), 0) AS asistencias
FROM participante p
LEFT JOIN clase c  ON c.ID_Participante = p.ID
LEFT JOIN agenda ag ON ag.ID = c.ID_Agenda
    AND ag.ID_Evento = p.ID_Evento
WHERE p.ID_Evento = ?
  AND ( 
        ( ? = 1 AND (p.Vendedor IS NULL OR p.Vendedor = '') )
        OR
        ( ? = 0 AND p.Vendedor = ? )
      )
GROUP BY p.ID, p.Nombre, p.Telefono, p.RFC, p.Sucursal
ORDER BY p.Nombre
";
$stmt = $conn->prepare($sql);
$flag = $matchSinVendedor ? 1 : 0;
$stmt->bind_param("iiss", $evento_id, $flag, $flag, $vendedor);
$stmt->execute();
$res = $stmt->get_result();

$participantes = [];
$total = 0;
$con = 0;
$sin = 0;

while ($row = $res->fetch_assoc()) {
    $row['asistencias'] = (int)$row['asistencias'];
    $participantes[] = $row;
    $total++;
    if ($row['asistencias'] > 0) $con++; else $sin++;
}
$stmt->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'vendedor' => $vendedor,
    'total' => $total,
    'con_asistencia' => $con,
    'sin_asistencia' => $sin,
    'participantes' => $participantes
], JSON_UNESCAPED_UNICODE);
