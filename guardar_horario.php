<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

$id_evento = intval($_POST['id_evento'] ?? 0);
$id_agenda = intval($_POST['id_agenda'] ?? 0);
$actividad = trim($_POST['actividad'] ?? '');
$fecha     = $_POST['fecha'] ?? '';
$horario   = strtoupper(trim($_POST['horario'] ?? ''));
$salon     = trim($_POST['salon'] ?? '');

if (!$id_evento || !$actividad || !$fecha || !$horario || !$salon) {
  die("Faltan datos");
}

// parse HH:MM a minutos
function toMin($hhmm){ [$h,$m]=array_map('intval', explode(':',$hhmm)); return $h*60+$m; }

if (!preg_match('/^(\d{2}):(\d{2})-(\d{2}):(\d{2})$/', $horario, $m)) {
  die("Horario inválido. Usa HH:MM-HH:MM");
}
$ini = toMin($m[1].":".$m[2]);
$fin = toMin($m[3].":".$m[4]);
if ($fin <= $ini) die("Rango horario inválido");

if ($id_agenda > 0) {
  $own = $conn->prepare("
    SELECT ID
    FROM agenda
    WHERE ID = ? AND ID_Evento = ?
    LIMIT 1
  ");
  $own->bind_param("ii", $id_agenda, $id_evento);
  $own->execute();
  $own_rs = $own->get_result();
  if (!$own_rs || !$own_rs->num_rows) {
    die("Registro de agenda inválido");
  }
  $own->close();
}

// validar solape en mismo salón/fecha
$chk = $conn->prepare("
  SELECT ID, Horario
  FROM agenda
  WHERE ID_Evento = ? AND Fecha = ? AND Salon = ?
    AND Actividad <> 'Vacio'
    AND (? = 0 OR ID <> ?)
");
$chk->bind_param("issii", $id_evento, $fecha, $salon, $id_agenda, $id_agenda);
$chk->execute();
$rs = $chk->get_result();
while ($r = $rs->fetch_assoc()) {
  if (preg_match('/^(\d{2}):(\d{2})-(\d{2}):(\d{2})$/', $r['Horario'], $mm)) {
    $a = toMin($mm[1].":".$mm[2]); $b = toMin($mm[3].":".$mm[4]);
    if ($ini < $b && $a < $fin) {
      die("⚠️ Solapa con un slot existente ($r[Horario]) en $salon el $fecha.");
    }
  }
}

if ($id_agenda > 0) {
  $ins = $conn->prepare("
    UPDATE agenda
    SET Salon = ?, Fecha = ?, Horario = ?, Actividad = ?
    WHERE ID = ? AND ID_Evento = ?
  ");
  $ins->bind_param("ssssii", $salon, $fecha, $horario, $actividad, $id_agenda, $id_evento);
  $ins->execute();
} else {
  $ins = $conn->prepare("
    INSERT INTO agenda (ID_Evento, Salon, Fecha, Horario, Actividad)
    VALUES (?,?,?,?,?)
  ");
  $ins->bind_param("issss", $id_evento, $salon, $fecha, $horario, $actividad);
  $ins->execute();
}

// limpiar VACIO que se empalmen en ese salón/fecha
$vac = $conn->prepare("
  SELECT ID, Horario
  FROM agenda
  WHERE ID_Evento = ? AND Fecha = ? AND Salon = ? AND Actividad = 'Vacio'
");
$vac->bind_param("iss", $id_evento, $fecha, $salon);
$vac->execute();
$rv = $vac->get_result();

function toMin2($hhmm){ [$h,$m]=array_map('intval', explode(':',$hhmm)); return $h*60+$m; }
preg_match('/^(\d{2}):(\d{2})-(\d{2}):(\d{2})$/', $horario, $mmNew);
$newIni = toMin2($mmNew[1].":".$mmNew[2]);
$newFin = toMin2($mmNew[3].":".$mmNew[4]);

while ($row = $rv->fetch_assoc()) {
  if (preg_match('/^(\d{2}):(\d{2})-(\d{2}):(\d{2})$/', $row['Horario'], $mmV)) {
    $a = toMin2($mmV[1].":".$mmV[2]);
    $b = toMin2($mmV[3].":".$mmV[4]);
    if ($newIni < $b && $a < $newFin) {
      $conn->query("DELETE FROM agenda WHERE ID=".(int)$row['ID']." LIMIT 1");
    }
  }
}


header("Location: editar_agenda_evento.php?id=".$id_evento);
