<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$proveedor = isset($_POST['proveedor']) ? trim($_POST['proveedor']) : '';
$idClase = isset($_POST['id_clase']) ? intval($_POST['id_clase']) : 0;

if (empty($nombre) || empty($telefono) || $idClase <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Faltan datos obligatorios']);
    exit;
}

// 1. Obtener el ID_Evento desde la agenda
$stmtAg = $conn->prepare("SELECT ID_Evento FROM agenda WHERE ID = ?");
$stmtAg->bind_param("i", $idClase);
$stmtAg->execute();
$resAg = $stmtAg->get_result();
$rowAg = $resAg->fetch_assoc();
$idEvento = $rowAg ? intval($rowAg['ID_Evento']) : 0;

if ($idEvento <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'No se encontró el evento asociado']);
    exit;
}

// 2. Verificar duplicidad de teléfono en este evento
$stmtCheck = $conn->prepare("SELECT ID FROM participante WHERE Telefono = ? AND ID_Evento = ?");
$stmtCheck->bind_param("si", $telefono, $idEvento);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    echo json_encode(['ok' => false, 'msg' => 'El número de teléfono ya está registrado en este evento']);
    exit;
}

// 3. Insertar participante
$sqlPart = "INSERT INTO participante (ID_Evento, Nombre, Telefono, Proveedor, Sucursal, Vendedor) VALUES (?, ?, ?, ?, 'Registro Rápido', 'Admin')";
$stmtPart = $conn->prepare($sqlPart);
$stmtPart->bind_param("isss", $idEvento, $nombre, $telefono, $proveedor);

if ($stmtPart->execute()) {
    $nuevoId = $conn->insert_id;
    
    // 3. Inscribir en la clase y marcar asistencia
    $sqlClase = "INSERT INTO clase (ID_Participante, ID_Agenda, Asistio, Asistencia_Fecha, Tipo_Inscripcion) VALUES (?, ?, 1, NOW(), 1)";
    $stmtClase = $conn->prepare($sqlClase);
    $stmtClase->bind_param("ii", $nuevoId, $idClase);
    
    if ($stmtClase->execute()) {
        echo json_encode(['ok' => true, 'msg' => 'Registro completo exitoso']);
    } else {
        echo json_encode(['ok' => true, 'msg' => 'Participante creado pero hubo error al inscribir: ' . $conn->error]);
    }
} else {
    echo json_encode(['ok' => false, 'msg' => 'Error al crear participante: ' . $conn->error]);
}

$conn->close();
