<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

header('Content-Type: application/json');
require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Datos del formulario
$fecha        = trim($_POST['fecha']        ?? '');
$hora         = trim($_POST['hora']         ?? '');
$salon        = trim($_POST['salon']        ?? '');
$id_actividad = (int)($_POST['id_actividad'] ?? 0);
// Opcional (si ya lo envías desde el form). No es estrictamente necesario con el JOIN.
$id_evento    = isset($_POST['id_evento']) ? (int)$_POST['id_evento'] : 0;

if ($fecha === '' || $hora === '' || $salon === '' || !$id_actividad) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$conn->begin_transaction();

try {
    // 1) Obtener actividad y defaults
    if ($id_evento) {
        $qa = $conn->prepare("SELECT ID_Evento, Actividad, COALESCE(Puntos_Default,0) AS Puntos_Default
                              FROM actividades WHERE ID=? AND ID_Evento=? LIMIT 1");
        $qa->bind_param("ii", $id_actividad, $id_evento);
    } else {
        $qa = $conn->prepare("SELECT ID_Evento, Actividad, COALESCE(Puntos_Default,0) AS Puntos_Default
                              FROM actividades WHERE ID=? LIMIT 1");
        $qa->bind_param("i", $id_actividad);
    }
    $qa->execute();
    $act = $qa->get_result()->fetch_assoc();
    if (!$act) {
        throw new Exception("Actividad no encontrada.");
    }
    $evt             = (int)$act['ID_Evento'];
    $nombreActividad = $act['Actividad'];
    $pdef            = (int)$act['Puntos_Default'];

    // 2) Intentar actualizar slot existente (mismo evento, fecha, hora y salón)
    //    - Cambiamos Actividad
    //    - Si Puntos_Asistencia estaba 0/NULL => copiar default de la actividad
    $upd = $conn->prepare("
        UPDATE agenda AS ag
        JOIN actividades AS ac ON ac.ID = ? AND ac.ID_Evento = ag.ID_Evento
        SET
          ag.Actividad = ac.Actividad,
          ag.Puntos_Asistencia = CASE
              WHEN ag.Puntos_Asistencia IS NULL OR ag.Puntos_Asistencia = 0
                   THEN COALESCE(ac.Puntos_Default, 0)
              ELSE ag.Puntos_Asistencia
          END
        WHERE ag.ID_Evento = ?
          AND ag.Fecha = ?
          AND ag.Horario = ?
          AND ag.Salon = ?
        LIMIT 1
    ");
    $upd->bind_param("iisss", $id_actividad, $evt, $fecha, $hora, $salon);
    $upd->execute();

    if ($upd->affected_rows === 0) {
        // 3) Si no existía el slot, lo insertamos con los puntos por defecto
        $ins = $conn->prepare("
            INSERT INTO agenda (ID_Evento, Salon, Fecha, Horario, Actividad, Puntos_Asistencia)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ins->bind_param("issssi", $evt, $salon, $fecha, $hora, $nombreActividad, $pdef);
        if (!$ins->execute()) {
            throw new Exception("No se pudo insertar en agenda.");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
