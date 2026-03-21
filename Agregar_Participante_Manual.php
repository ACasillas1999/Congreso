<?php
header('Content-Type: application/json; charset=utf-8');

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/qr_helper.php";

// Obtener el ID del participante y de la clase desde la solicitud POST
$idParticipante = extraer_id_participante_qr($_POST['id_participante'] ?? null);
$idClase = isset($_POST['id_clase']) ? intval($_POST['id_clase']) : 0;

if ($idParticipante <= 0 || $idClase <= 0) {
    echo json_encode(['ok' => false, 'code' => 'missing', 'msg' => 'Modo Lectura de QR.']);
    exit;
}

// Verificar si el participante ya está en la clase
$sqlVerificar = "SELECT COUNT(*) FROM clase WHERE ID_Participante = ? AND ID_Agenda = ?";
$stmtVerificar = $conn->prepare($sqlVerificar);
$stmtVerificar->bind_param("ii", $idParticipante, $idClase);
$stmtVerificar->execute();
$stmtVerificar->bind_result($count);
$stmtVerificar->fetch();
$stmtVerificar->close();

if ($count > 0) {
    echo json_encode(['ok' => false, 'code' => 'duplicate', 'msg' => 'El participante ya está agregado en esta clase.']);
    $conn->close();
    exit;
}

// Obtener capacidad y ocupación
$sqlCapacidad = "
  SELECT A.capacidad, COUNT(C.ID) AS total_participantes
  FROM agenda B
  JOIN actividades A
    ON A.Actividad = B.Actividad
   AND A.ID_Evento = B.ID_Evento
  LEFT JOIN clase C
    ON B.ID = C.ID_Agenda
  WHERE B.ID = ?
  GROUP BY A.capacidad
";
$stmtCapacidad = $conn->prepare($sqlCapacidad);
$stmtCapacidad->bind_param("i", $idClase);
$stmtCapacidad->execute();
$resultCapacidad = $stmtCapacidad->get_result();

if ($resultCapacidad->num_rows <= 0) {
    echo json_encode(['ok' => false, 'code' => 'notfound', 'msg' => 'No se encontró la clase especificada.']);
    $stmtCapacidad->close();
    $conn->close();
    exit;
}

$rowCapacidad = $resultCapacidad->fetch_assoc();
$capacidadMaxima = (int)$rowCapacidad['capacidad'];
$totalParticipantes = (int)$rowCapacidad['total_participantes'];
$stmtCapacidad->close();

if ($totalParticipantes >= $capacidadMaxima) {
    echo json_encode(['ok' => false, 'code' => 'full', 'msg' => 'No se puede agregar más participantes, la clase está llena.']);
    $conn->close();
    exit;
}

// Insertar
$sqlAgregar = "INSERT INTO clase (ID_Participante, ID_Agenda, Tipo_Inscripcion) VALUES (?, ?, 1)";
$stmtAgregar = $conn->prepare($sqlAgregar);
$stmtAgregar->bind_param("ii", $idParticipante, $idClase);

if ($stmtAgregar->execute()) {
    echo json_encode(['ok' => true, 'code' => 'added', 'msg' => "Participante agregado exitosamente. Presione 'Aceptar' o ingrese otro código."]);
} else {
    echo json_encode(['ok' => false, 'code' => 'dberror', 'msg' => 'Error al agregar el participante: ' . $conn->error]);
}

$stmtAgregar->close();
$conn->close();
