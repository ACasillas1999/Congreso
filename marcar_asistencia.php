<?php
// Marcar_Asistencia.php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

require_once __DIR__ . "/Conexiones/Conexion.php";

$id_clase = (int)($_POST['id_clase'] ?? 0);        // ID de la AGENDA (sesión)
$id_part  = (int)($_POST['id_participante'] ?? 0); // ID del PARTICIPANTE

if (!$id_clase || !$id_part) {
  http_response_code(400);
  echo "Datos incompletos.";
  exit;
}

// 1) Verificar inscripción a la clase
$sel = $conn->prepare("
  SELECT ID, Asistio, Asistencia_Fecha
  FROM clase
  WHERE ID_Agenda = ? AND ID_Participante = ?
  LIMIT 1
");
$sel->bind_param("ii", $id_clase, $id_part);
$sel->execute();
$res = $sel->get_result();

if (!$res->num_rows) {
  echo "El participante no está inscrito en esta clase.";
  exit;
}
$row = $res->fetch_assoc();

if ((int)$row['Asistio'] === 1) {
  echo "Asistencia ya registrada: " . $row['Asistencia_Fecha'];
  exit;
}

// 2) Marcar asistencia
$usuario_id = isset($_SESSION['ID']) ? (int)$_SESSION['ID'] : null;
$upd = $conn->prepare("
  UPDATE clase
  SET Asistio = 1,
      Asistencia_Fecha = NOW(),
      Asistencia_Usuario = ?
  WHERE ID = ?
");
$upd->bind_param("ii", $usuario_id, $row['ID']);
$ok = $upd->execute();

if (!$ok) {
  echo "Error al registrar asistencia.";
  exit;
}

// 3) Traer RFC, Evento y Puntos de la sesión (agenda)
/*$q = $conn->prepare("
  SELECT
    p.RFC,
    p.ID_Evento,
    COALESCE(a.Puntos_Asistencia, 0) AS Puntos_Clase
  FROM clase c
  JOIN participante p ON p.ID = c.ID_Participante
  LEFT JOIN agenda a   ON a.ID = c.ID_Agenda
  WHERE c.ID = ?
  LIMIT 1
");*/
$q = $conn->prepare("
SELECT
p.RFC,
    p.ID_Evento,
    act.Puntos_Default AS Puntos_Clase
  FROM clase c
  JOIN participante   p   ON p.ID  = c.ID_Participante
  JOIN agenda         ag  ON ag.ID = c.ID_Agenda
  JOIN actividades    act ON act.Actividad = ag.Actividad
  WHERE c.ID = ?
  LIMIT 1;
  ");
  
$q->bind_param("i", $row['ID']);
$q->execute();
$rp = $q->get_result()->fetch_assoc();

$RFC       = $rp['RFC'] ?? null;
$ID_Evento = isset($rp['ID_Evento']) ? (int)$rp['ID_Evento'] : 0;
$puntos    = isset($rp['Puntos_Clase']) ? (int)$rp['Puntos_Clase'] : 0;

if (!$RFC || !$ID_Evento) {
  echo "Asistencia registrada ✅ (sin RFC/Evento, no se acreditaron puntos).";
  exit;
}

if ($puntos === 0) {
  echo "Asistencia registrada ✅ | Esta clase no otorga puntos.";
  exit;
}

// 4) Acreditar puntos a la billetera grupal (puntos_rfc) por RFC+Evento
$chk = $conn->prepare("
  SELECT ID, Puntos
  FROM puntos_rfc
  WHERE RFC = ? AND ID_Evento = ?
  LIMIT 1
");
$chk->bind_param("si", $RFC, $ID_Evento);
$chk->execute();
$rs = $chk->get_result();

if ($rs->num_rows) {
  $rowPR = $rs->fetch_assoc();
  $nuevo = (int)$rowPR['Puntos'] + $puntos;

  $updPR = $conn->prepare("UPDATE puntos_rfc SET Puntos = ? WHERE ID = ?");
  $updPR->bind_param("ii", $nuevo, $rowPR['ID']);
  $ok2 = $updPR->execute();
} else {
  $insPR = $conn->prepare("INSERT INTO puntos_rfc (RFC, ID_Evento, Puntos) VALUES (?, ?, ?)");
  $insPR->bind_param("sii", $RFC, $ID_Evento, $puntos);
  $ok2 = $insPR->execute();
}

echo $ok2
  ? "Asistencia registrada ✅ | Puntos acreditados: +{$puntos}"
  : "Asistencia registrada ✅ | No se pudieron acreditar puntos.";
