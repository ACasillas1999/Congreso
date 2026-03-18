<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

$idParticipante = isset($_POST['id_participante']) ? intval($_POST['id_participante']) : 0;
$idClase = isset($_POST['id_clase']) ? intval($_POST['id_clase']) : 0;

if ($idParticipante <= 0 || $idClase <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
    exit;
}

// 1. Inscribir en la clase
$stmt = $conn->prepare("INSERT IGNORE INTO clase (ID_Participante, ID_Agenda, Asistio, Asistencia_Fecha, Tipo_Inscripcion) VALUES (?, ?, 1, NOW(), 1)");
$stmt->bind_param("ii", $idParticipante, $idClase);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['ok' => true, 'msg' => 'Inscrito y asistencia marcada']);
    } else {
        // Ya estaba inscrito, solo marcar asistencia
        $stmtAsis = $conn->prepare("UPDATE clase SET Asistio = 1, Asistencia_Fecha = NOW() WHERE ID_Participante = ? AND ID_Agenda = ?");
        $stmtAsis->bind_param("ii", $idParticipante, $idClase);
        $stmtAsis->execute();
        echo json_encode(['ok' => true, 'msg' => 'Asistencia marcada (ya estaba inscrito)']);
    }
} else {
    echo json_encode(['ok' => false, 'msg' => 'Error al procesar: ' . $conn->error]);
}

$conn->close();
