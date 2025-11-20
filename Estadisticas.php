<?php
/* ======================================================
   Estadísticas de Evento — SCOPE + Reglas:
   - Botonera Día 1 / Día 2 / Total
   - Asistencia mixta:
       * Tipo_Inscripcion=1 (desde evento) => cuenta sin Asistio
       * Tipo_Inscripcion=0 (desde registro) => requiere Asistio=1
       * Agendas por inscripción (564,565) => cuentan sin Asistio
   ====================================================== */

$DEBUG = isset($_GET['debug']) && $_GET['debug'] == '1';
error_reporting(E_ALL);
ini_set('display_errors', $DEBUG ? '1' : '0');
ini_set('display_startup_errors', $DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/errores_evento.log');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

set_exception_handler(function(Throwable $e) use ($DEBUG) {
  http_response_code(500);
  echo '<div style="background:#fee2e2;color:#7f1d1d;padding:14px;border:1px solid #fecaca;border-radius:8px;margin:12px">';
  echo '<b>Excepción:</b> '.htmlspecialchars($e->getMessage());
  if ($DEBUG) echo '<pre style="white-space:pre-wrap;margin-top:8px">'.htmlspecialchars($e).'</pre>';
  echo '</div>';
  error_log('[EXCEPTION] '.$e);
});
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($DEBUG) {
  if (!(error_reporting() & $errno)) return false;
  $msg = "PHP[$errno] $errstr en $errfile:$errline";
  if ($DEBUG) {
    echo '<div style="background:#fffbeb;color:#854d0e;padding:12px;border:1px solid #fde68a;border-radius:8px;margin:12px">';
    echo '<b>Error PHP:</b> '.htmlspecialchars($msg).'</div>';
  }
  error_log($msg);
  return true;
});

/* ====== HELPERS ====== */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function exec_stmt(mysqli $conn, string $sql, ?string $types = null, array $params = []) : mysqli_result {
  $stmt = $conn->prepare($sql);
  if ($types && $params) {
    $refs = [];
    foreach ($params as $k => $v) $refs[$k] = &$params[$k];
    $stmt->bind_param($types, ...$refs);
  }
  $stmt->execute();
  return $stmt->get_result();
}
function debug_sql(string $label, string $sql, ?string $types, array $params, bool $DEBUG){
  if(!$DEBUG) return;
  echo '<details style="margin:10px;padding:8px;background:#f1f5f9;border-radius:8px"><summary><b>DEBUG SQL:</b> '.h($label).'</summary>';
  echo '<pre style="white-space:pre-wrap">'.h($sql)."</pre>";
  if ($types !== null) {
    echo '<div><b>Types:</b> '.h($types).'</div>';
    echo '<div><b>Params:</b> '.h(json_encode($params, JSON_UNESCAPED_UNICODE)).'</div>';
  }
  echo '</details>';
}

/* ====== SESIÓN Y CONEXIÓN ====== */
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || ($_SESSION["Rol"] ?? '') === "Vendedor") {
  header("location: /Congreso/Sesion/login.html");
  exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php"; // $conn = new mysqli(...)

/* ====== ENTRADA ====== */
$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_evento <= 0) { http_response_code(400); exit("Falta parámetro id de evento."); }

/* ====== CONFIG ====== */
$ID_AGENDA_KIT = 564;
$AGENDAS_ASISTENCIA_POR_INSCRIPCION = [564, 565]; // cuentan como asistencia por inscripción

if ($DEBUG) {
  echo '<div style="margin:10px;color:#334155;background:#e2e8f0;padding:8px;border-radius:8px">';
  echo 'MySQL/MariaDB server: '.h($conn->server_info).'</div>';
}

/* ====== FECHAS EVENTO & SCOPE ====== */
$sql_evento = "SELECT DATE(fecha_inicio) AS fi, DATE(fecha_fin) AS ff FROM evento WHERE id = ?";
debug_sql('Fechas evento', $sql_evento, 'i', [$id_evento], $DEBUG);
$res = exec_stmt($conn, $sql_evento, 'i', [$id_evento]);
$rowE = $res->fetch_assoc();
$fecha_inicio = $rowE['fi'] ?? null;
$fecha_fin    = $rowE['ff'] ?? null;
$dia1 = $fecha_inicio;
$dia2 = $fecha_inicio ? date('Y-m-d', strtotime($fecha_inicio.' +1 day')) : null;

$scope = isset($_GET['scope']) ? strtolower($_GET['scope']) : 'total';
if (!in_array($scope, ['total','d1','d2'], true)) $scope = 'total';
$scopeDate = null;
$scopeLabel = 'Total';
if ($scope === 'd1') { $scopeDate = $dia1; $scopeLabel = 'Día 1'; }
if ($scope === 'd2') { $scopeDate = $dia2; $scopeLabel = 'Día 2'; }

function cond_fecha(string $aliasAg = 'ag'): string { return " AND DATE($aliasAg.Fecha) = ? "; }

/**
 * Asistencia Mixta:
 *  - Desde evento (Tipo_Inscripcion=1) => cuenta sin Asistio
 *  - Desde registro (Tipo_Inscripcion=0) => requiere Asistio=1
 *  - Agendas especiales (por inscripción) => cuentan sin Asistio
 * SQL: ( COALESCE(c.Tipo_Inscripcion,0)=1 OR c.Asistio=1 OR ag.ID IN (...))
 */
function cond_asistencia_mixta(string $aliasClase='c', string $aliasAgenda='ag'): string {
  global $AGENDAS_ASISTENCIA_POR_INSCRIPCION;
  $ids = array_map('intval', $AGENDAS_ASISTENCIA_POR_INSCRIPCION);
  $in = $ids ? implode(',', $ids) : '';
  $parteAgenda = $in ? " OR {$aliasAgenda}.ID IN ($in)" : "";
  return " ( COALESCE({$aliasClase}.Tipo_Inscripcion,0) = 1 OR {$aliasClase}.Asistio = 1{$parteAgenda} ) ";
}

/* ======================================================
   CONSULTAS (todas respetan SCOPE + Asistencia Mixta)
   ====================================================== */

/* 1) Por clase */
$sql_por_clase = "
SELECT 
  ag.ID AS ID_Agenda,
  ag.Actividad,
  ag.Salon,
  ag.Fecha,
  ag.Horario,
  COUNT(DISTINCT c.ID_Participante) AS AsistentesUnicos
FROM clase c
JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ?
  AND ".cond_asistencia_mixta('c','ag').
($scopeDate ? cond_fecha('ag') : '')."
GROUP BY ag.ID, ag.Actividad, ag.Salon, ag.Fecha, ag.Horario
ORDER BY ag.Fecha, ag.Horario;";
$types = $scopeDate ? 'is' : 'i';
$params = $scopeDate ? [$id_evento, $scopeDate] : [$id_evento];
debug_sql('Por clase (scope '.$scope.')', $sql_por_clase, $types, $params, $DEBUG);
$res = exec_stmt($conn, $sql_por_clase, $types, $params);
$por_clase = [];
while ($r = $res->fetch_assoc()) $por_clase[] = $r;

/* 2) Únicos del evento */
$sql_unicos_evento = "
SELECT COUNT(DISTINCT c.ID_Participante) AS AsistentesUnicosEvento
FROM clase c
JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ?
  AND ".cond_asistencia_mixta('c','ag').
($scopeDate ? cond_fecha('ag') : '').";";
debug_sql('Únicos evento (scope '.$scope.')', $sql_unicos_evento, $types, $params, $DEBUG);
$res = exec_stmt($conn, $sql_unicos_evento, $types, $params);
$unicos_evento = (int)($res->fetch_assoc()['AsistentesUnicosEvento'] ?? 0);

/* 3) Por día */
$sql_por_dia = "
SELECT 
  DATE(ag.Fecha) AS fecha,
  COUNT(DISTINCT c.ID_Participante) AS total_participantes
FROM clase c
JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ?
  AND ".cond_asistencia_mixta('c','ag').
($scopeDate ? cond_fecha('ag') : '')."
GROUP BY fecha
ORDER BY fecha;";
debug_sql('Por día (scope '.$scope.')', $sql_por_dia, $types, $params, $DEBUG);
$res = exec_stmt($conn, $sql_por_dia, $types, $params);
$por_dia = [];
while ($r = $res->fetch_assoc()) $por_dia[] = $r;

/* 4) Por hora */
$sql_por_hora = "
SELECT 
  DATE(ag.Fecha) AS fecha,
  HOUR(STR_TO_DATE(SUBSTRING_INDEX(ag.Horario, '-', 1), '%H:%i')) AS hora,
  COUNT(DISTINCT c.ID_Participante) AS total_participantes
FROM clase c
JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ?
  AND ".cond_asistencia_mixta('c','ag').
($scopeDate ? cond_fecha('ag') : '')."
GROUP BY fecha, hora
ORDER BY fecha, hora;";
debug_sql('Por hora (scope '.$scope.')', $sql_por_hora, $types, $params, $DEBUG);
$res = exec_stmt($conn, $sql_por_hora, $types, $params);
$por_hora = [];
while ($r = $res->fetch_assoc()) $por_hora[] = $r;

/* ===== KIT y derivados (se muestran SOLO en Total) ===== */

/* 5.1) Inscritos en Entrega de kit (por inscripción) */
$sql_kit_inscritos = "
SELECT DISTINCT c.ID_Participante, p.Nombre
FROM clase c
JOIN participante p ON p.ID = c.ID_Participante
WHERE c.ID_Agenda = ?
ORDER BY p.Nombre;";
debug_sql('Kit (por inscripción)', $sql_kit_inscritos, 'i', [$ID_AGENDA_KIT], $DEBUG);
$res = exec_stmt($conn, $sql_kit_inscritos, 'i', [$ID_AGENDA_KIT]);
$lista_kit = [];
while ($r = $res->fetch_assoc()) $lista_kit[] = $r;

/* 5.2) NO inscritos en kit pero asistieron a otras (asistencia mixta) */
$sql_no_kit = "
SELECT DISTINCT c.ID_Participante, p.Nombre
FROM clase c
JOIN agenda ag ON ag.ID = c.ID_Agenda
JOIN participante p ON p.ID = c.ID_Participante
WHERE ag.ID_Evento = ?
  AND ".cond_asistencia_mixta('c','ag')."
  AND c.ID_Participante NOT IN (
    SELECT c2.ID_Participante
    FROM clase c2
    WHERE c2.ID_Agenda = ?
  )
ORDER BY p.Nombre;";
debug_sql('NO Kit (otras asistencias, excluye inscritos en kit)', $sql_no_kit, 'ii', [$id_evento, $ID_AGENDA_KIT], $DEBUG);
$res = exec_stmt($conn, $sql_no_kit, 'ii', [$id_evento, $ID_AGENDA_KIT]);
$lista_no_kit = [];
while ($r = $res->fetch_assoc()) $lista_no_kit[] = $r;

/* 5.3) Solo kit (si estuvo en otras bajo regla mixta, ya no es solo kit) */
$sql_solo_kit = "
SELECT k.ID_Participante, p.Nombre
FROM (
  SELECT DISTINCT c.ID_Participante
  FROM clase c
  WHERE c.ID_Agenda = ?
) k
JOIN participante p ON p.ID = k.ID_Participante
WHERE NOT EXISTS (
  SELECT 1
  FROM clase c3
  JOIN agenda ag3 ON ag3.ID = c3.ID_Agenda
  WHERE ag3.ID_Evento = ?
    AND c3.ID_Agenda <> ?
    AND ".cond_asistencia_mixta('c3','ag3')."
    AND c3.ID_Participante = k.ID_Participante
)
ORDER BY p.Nombre;";
debug_sql('Solo Kit (sin asistir a otras, regla mixta)', $sql_solo_kit, 'iii', [$ID_AGENDA_KIT, $id_evento, $ID_AGENDA_KIT], $DEBUG);
$res = exec_stmt($conn, $sql_solo_kit, 'iii', [$ID_AGENDA_KIT, $id_evento, $ID_AGENDA_KIT]);
$lista_solo_kit = [];
while ($r = $res->fetch_assoc()) $lista_solo_kit[] = $r;

/* 6) Inscritos vs Asistieron por clase */
$sql_ins_vs_asist = "
SELECT 
  ag.ID, ag.Actividad, ag.Salon, ag.Fecha, ag.Horario,
  COALESCE(i.inscritos,0)  AS inscritos,
  COALESCE(a.asistieron,0) AS asistieron,
  ROUND(100 * COALESCE(a.asistieron,0) / NULLIF(COALESCE(i.inscritos,0),0), 1) AS pct_asistencia
FROM agenda ag
LEFT JOIN (
  SELECT c.ID_Agenda, COUNT(DISTINCT c.ID_Participante) AS inscritos
  FROM clase c
  JOIN agenda agi ON agi.ID = c.ID_Agenda
  WHERE agi.ID_Evento = ?".($scopeDate ? " AND DATE(agi.Fecha)=? " : "")."
  GROUP BY c.ID_Agenda
) i ON i.ID_Agenda = ag.ID
LEFT JOIN (
  SELECT c.ID_Agenda, COUNT(DISTINCT c.ID_Participante) AS asistieron
  FROM clase c
  JOIN agenda aga ON aga.ID = c.ID_Agenda
  WHERE aga.ID_Evento = ? AND ".cond_asistencia_mixta('c','aga').
  ($scopeDate ? " AND DATE(aga.Fecha)=? " : "")."
  GROUP BY c.ID_Agenda
) a ON a.ID_Agenda = ag.ID
WHERE ag.ID_Evento = ?".($scopeDate ? " AND DATE(ag.Fecha)=? " : "")."
ORDER BY ag.Fecha, ag.Horario;";
if ($scopeDate){
  $types = 'is' . 'is' . 'is';
  $params = [$id_evento, $scopeDate, $id_evento, $scopeDate, $id_evento, $scopeDate];
} else {
  $types = 'iii';
  $params = [$id_evento, $id_evento, $id_evento];
}
debug_sql('Inscritos vs Asistieron (scope '.$scope.')', $sql_ins_vs_asist, $types, $params, $DEBUG);
$res = exec_stmt($conn, $sql_ins_vs_asist, $types, $params);
$tabla_ins_vs_asist = [];
while ($r = $res->fetch_assoc()) $tabla_ins_vs_asist[] = $r;

/* 7) Tipo por clase (incluye % correctos con regla mixta) */
$sql_tipo_clase = "
SELECT
  ag.ID         AS ID_Agenda,
  ag.Actividad,
  ag.Salon,
  ag.Fecha,
  ag.Horario,
  COUNT(DISTINCT c.ID_Participante) AS Inscritos_Total,

  /* Asistencia total con regla mixta */
  COUNT(DISTINCT CASE WHEN ".cond_asistencia_mixta('c','ag')." THEN c.ID_Participante END) AS Asistieron_Total,

  /* T0 = Desde registro: inscritos y asistieron (solo si Asistio=1 o agenda especial) */
  COUNT(DISTINCT CASE WHEN COALESCE(c.Tipo_Inscripcion,0) = 0 THEN c.ID_Participante END) AS Inscritos_T0,
  COUNT(DISTINCT CASE WHEN COALESCE(c.Tipo_Inscripcion,0) = 0 AND (c.Asistio=1 OR ag.ID IN (".implode(',', $AGENDAS_ASISTENCIA_POR_INSCRIPCION).")) THEN c.ID_Participante END) AS Asistieron_T0,

  /* T1 = Desde evento: asistentes cuentan por registro (siempre) o agenda especial */
  COUNT(DISTINCT CASE WHEN COALESCE(c.Tipo_Inscripcion,0) = 1 THEN c.ID_Participante END) AS Inscritos_T1,
  COUNT(DISTINCT CASE WHEN COALESCE(c.Tipo_Inscripcion,0) = 1 THEN c.ID_Participante END) AS Asistieron_T1

FROM agenda ag
LEFT JOIN clase c ON c.ID_Agenda = ag.ID
WHERE ag.ID_Evento = ?".
($scopeDate ? cond_fecha('ag') : '')."
GROUP BY ag.ID, ag.Actividad, ag.Salon, ag.Fecha, ag.Horario
ORDER BY ag.Fecha, ag.Horario;";
$types = $scopeDate ? 'is' : 'i';
$params = $scopeDate ? [$id_evento, $scopeDate] : [$id_evento];
debug_sql('Tipo por clase (scope '.$scope.')', $sql_tipo_clase, $types, $params, $DEBUG);
$res = exec_stmt($conn, $sql_tipo_clase, $types, $params);
$tipo_por_clase = [];
while ($r = $res->fetch_assoc()) $tipo_por_clase[] = $r;

/* 8) Totales del evento por tipo (regla mixta) */
$sql_totales_tipo = "
SELECT
  COALESCE(c.Tipo_Inscripcion,0) AS Tipo,
  COUNT(DISTINCT c.ID_Participante) AS Inscritos,
  COUNT(DISTINCT CASE WHEN ".cond_asistencia_mixta('c','ag')." THEN c.ID_Participante END) AS Asistieron
FROM clase c
JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ?".
($scopeDate ? cond_fecha('ag') : '')."
GROUP BY COALESCE(c.Tipo_Inscripcion,0)
ORDER BY Tipo;";
debug_sql('Totales por tipo (scope '.$scope.')', $sql_totales_tipo, $types, $params, $DEBUG);
$res = exec_stmt($conn, $sql_totales_tipo, $types, $params);
$totales_tipo = [];
while ($r = $res->fetch_assoc()) $totales_tipo[] = $r;

/* 9) Totales por día y tipo (solo en Total) */
$sql_totales_por_dia_tipo = "
SELECT
  DATE(ag.Fecha) AS fecha,
  COALESCE(c.Tipo_Inscripcion,0) AS tipo,
  COUNT(DISTINCT c.ID_Participante) AS inscritos,
  COUNT(DISTINCT CASE WHEN ".cond_asistencia_mixta('c','ag')." THEN c.ID_Participante END) AS asistieron
FROM clase c
JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ? AND DATE(ag.Fecha) = ?
GROUP BY DATE(ag.Fecha), COALESCE(c.Tipo_Inscripcion,0)
ORDER BY tipo;";
$totales_d1 = $totales_d2 = [];
if ($dia1) { debug_sql('Totales Día 1 x tipo', $sql_totales_por_dia_tipo, 'is', [$id_evento, $dia1], $DEBUG);
  $res = exec_stmt($conn, $sql_totales_por_dia_tipo, 'is', [$id_evento, $dia1]); while ($r = $res->fetch_assoc()) $totales_d1[] = $r; }
if ($dia2) { debug_sql('Totales Día 2 x tipo', $sql_totales_por_dia_tipo, 'is', [$id_evento, $dia2], $DEBUG);
  $res = exec_stmt($conn, $sql_totales_por_dia_tipo, 'is', [$id_evento, $dia2]); while ($r = $res->fetch_assoc()) $totales_d2[] = $r; }

/* 10) Clases por día con T0/T1 (solo en Total) */
$sql_clases_por_dia_tipo = "
SELECT
  ag.ID         AS ID_Agenda,
  ag.Actividad,
  ag.Salon,
  ag.Fecha,
  ag.Horario,
  COUNT(DISTINCT c.ID_Participante) AS Inscritos_Total,
  COUNT(DISTINCT CASE WHEN ".cond_asistencia_mixta('c','ag')." THEN c.ID_Participante END) AS Asistieron_Total,

  COUNT(DISTINCT CASE WHEN COALESCE(c.Tipo_Inscripcion,0) = 0 THEN c.ID_Participante END) AS Inscritos_T0,
  COUNT(DISTINCT CASE WHEN COALESCE(c.Tipo_Inscripcion,0) = 0 AND (c.Asistio=1 OR ag.ID IN (".implode(',', $AGENDAS_ASISTENCIA_POR_INSCRIPCION).")) THEN c.ID_Participante END) AS Asistieron_T0,

  COUNT(DISTINCT CASE WHEN COALESCE(c.Tipo_Inscripcion,0) = 1 THEN c.ID_Participante END) AS Inscritos_T1,
  COUNT(DISTINCT CASE WHEN COALESCE(c.Tipo_Inscripcion,0) = 1 THEN c.ID_Participante END) AS Asistieron_T1

FROM agenda ag
LEFT JOIN clase c ON c.ID_Agenda = ag.ID
WHERE ag.ID_Evento = ? AND DATE(ag.Fecha) = ?
GROUP BY ag.ID, ag.Actividad, ag.Salon, ag.Fecha, ag.Horario
ORDER BY ag.Fecha, ag.Horario;";
$clases_d1 = $clases_d2 = [];
if ($dia1) { debug_sql('Clases Día 1 con T0/T1', $sql_clases_por_dia_tipo, 'is', [$id_evento, $dia1], $DEBUG);
  $res = exec_stmt($conn, $sql_clases_por_dia_tipo, 'is', [$id_evento, $dia1]); while ($r = $res->fetch_assoc()) $clases_d1[] = $r; }
if ($dia2) { debug_sql('Clases Día 2 con T0/T1', $sql_clases_por_dia_tipo, 'is', [$id_evento, $dia2], $DEBUG);
  $res = exec_stmt($conn, $sql_clases_por_dia_tipo, 'is', [$id_evento, $dia2]); while ($r = $res->fetch_assoc()) $clases_d2[] = $r; }

/* ===== Cerrar conexión ===== */
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Estadísticas del Evento</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root{ --azul-oscuro:#1e2a78; --azul-medio:#2c3e94; --azul-claro:#3a5fcd; --bg:#0b1127; --r:16px; --shadow:0 10px 20px rgba(0,0,0,.18); }
    *{box-sizing:border-box}
    body{margin:0;font-family:Segoe UI,system-ui,Arial,sans-serif;color:#0f172a;background:radial-gradient(ellipse at top,#1e2a78 0%,#000c2d 100%);min-height:100vh}
    .header{position:sticky;top:0;z-index:5;display:flex;gap:16px;align-items:center;justify-content:space-between;padding:14px 20px;background:linear-gradient(135deg,var(--azul-oscuro),var(--azul-medio));color:#fff;box-shadow:var(--shadow)}
    .wrap{width:95%;max-width:1200px;margin:24px auto}
    .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}
    .card{background:#fff;border-radius:var(--r);box-shadow:var(--shadow);padding:18px}
    .card .k{font-size:13px;color:#64748b}.card .v{font-size:26px;font-weight:700;color:#111827}
    .Grafica{background:#fff;border-radius:var(--r);box-shadow:var(--shadow);padding:22px;margin-top:20px;overflow:auto}
    .titulo{margin:0 0 12px;font-size:20px;color:#111827}
    .mi-tabla{width:100%;border-collapse:collapse;margin-top:10px}
    .mi-tabla th,.mi-tabla td{border-bottom:1px solid #e5e7eb;padding:10px;text-align:center}
    .mi-tabla th{background:var(--azul-oscuro);color:#fff}
    .mi-tabla tr:nth-child(even) td{background:#f8fafc}
    canvas{max-width:100%;height:auto !important}
    .cols{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    @media (max-width:900px){.cols{grid-template-columns:1fr}}
    .note{font-size:12px;color:#64748b;margin-top:8px}
    .header a{color:#fff;text-decoration:none;padding:8px 12px;border-radius:10px}
    .segmented{display:flex;gap:8px;align-items:center}
    .segbtn{color:#fff;text-decoration:none;background:rgba(255,255,255,.15);padding:8px 14px;border-radius:999px;border:1px solid rgba(255,255,255,.25);backdrop-filter: blur(4px); font-weight:600; transition:.18s}
    .segbtn:hover{transform:translateY(-1px)}
    .segbtn.active{background:#fff;color:#0b1127;border-color:#fff}
  </style>
</head>
<body>
<header class="header">
  <div class="logo">Estadísticas — Evento #<?=h($id_evento)?> · <?=h($scopeLabel)?></div>
  <nav class="segmented">
    <a class="segbtn <?= $scope==='d1'?'active':'' ?>" href="?id=<?=h($id_evento)?>&scope=d1<?= $DEBUG ? '&debug=1' : '' ?>">Día 1</a>
    <a class="segbtn <?= $scope==='d2'?'active':'' ?>" href="?id=<?=h($id_evento)?>&scope=d2<?= $DEBUG ? '&debug=1' : '' ?>">Día 2</a>
    <a class="segbtn <?= $scope==='total'?'active':'' ?>" href="?id=<?=h($id_evento)?>&scope=total<?= $DEBUG ? '&debug=1' : '' ?>">Total</a>
  </nav>
  <nav>
    <a href="Evento_inicio.php?id=<?=h($id_evento)?>">Volver</a>
    <?php if(!$DEBUG): ?><a href="?id=<?=h($id_evento)?>&scope=<?=h($scope)?>&debug=1">DEBUG ON</a>
    <?php else: ?><a href="?id=<?=h($id_evento)?>&scope=<?=h($scope)?>">DEBUG OFF</a><?php endif; ?>
  </nav>
</header>

<div class="wrap">

  <section class="cards">
    <div class="card"><div class="k">Asistencia única (≥1 sesión) — <?=h($scopeLabel)?></div><div class="v"><?=number_format($unicos_evento)?></div></div>
    <?php if ($scope === 'total'): ?>
      <div class="card"><div class="k">Inscritos “Entrega de kit” (ID <?=h($ID_AGENDA_KIT)?>)</div><div class="v"><?=number_format(count($lista_kit))?></div></div>
      <div class="card"><div class="k">Asistieron a otras, NO inscritos en kit</div><div class="v"><?=number_format(count($lista_no_kit))?></div></div>
      <div class="card"><div class="k">Solo kit</div><div class="v"><?=number_format(count($lista_solo_kit))?></div></div>
    <?php endif; ?>
  </section>

  <section class="Grafica">
    <h2 class="titulo">Asistencia única por clase — <?=h($scopeLabel)?></h2>
    <div class="cols">
      <div><canvas id="chartPorClase"></canvas></div>
      <div>
        <table class="mi-tabla">
          <tr><th>ID_Agenda</th><th>Actividad</th><th>Salón</th><th>Fecha</th><th>Horario</th><th>Asistentes únicos</th></tr>
          <?php foreach ($por_clase as $r): ?>
          <tr>
            <td><?=h($r['ID_Agenda'])?></td><td><?=h($r['Actividad'])?></td><td><?=h($r['Salon'])?></td>
            <td><?=h($r['Fecha'])?></td><td><?=h($r['Horario'])?></td><td><?=h($r['AsistentesUnicos'])?></td>
          </tr><?php endforeach; ?>
        </table>
      </div>
    </div>
  </section>

  <section class="Grafica"><h2 class="titulo">Asistencia única por día — <?=h($scopeLabel)?></h2><canvas id="chartPorDia"></canvas></section>

  <section class="Grafica">
    <h2 class="titulo">Asistencia única por hora (inicio de bloque) — <?=h($scopeLabel)?></h2>
    <canvas id="chartPorHora"></canvas>
    <div class="note">
      La asistencia aplica: Tipo=1 (evento) cuenta sin Asistio; Tipo=0 (registro) requiere Asistio; agendas <?=h(implode(', ', $AGENDAS_ASISTENCIA_POR_INSCRIPCION))?> cuentan por inscripción.
    </div>
  </section>

  <?php if ($scope === 'total'): ?>
    <section class="Grafica">
      <h2 class="titulo">Inscritos en “Entrega de kit”</h2>
      <table class="mi-tabla"><tr><th>ID Participante</th><th>Nombre</th></tr>
        <?php foreach ($lista_kit as $r): ?><tr><td><?=h($r['ID_Participante'])?></td><td><?=h($r['Nombre'])?></td></tr><?php endforeach; ?>
      </table>
    </section>

    <section class="Grafica">
      <h2 class="titulo">Asistieron a otras, NO inscritos en kit</h2>
      <table class="mi-tabla"><tr><th>ID Participante</th><th>Nombre</th></tr>
        <?php foreach ($lista_no_kit as $r): ?><tr><td><?=h($r['ID_Participante'])?></td><td><?=h($r['Nombre'])?></td></tr><?php endforeach; ?>
      </table>
    </section>

    <section class="Grafica">
      <h2 class="titulo">Solo kit</h2>
      <table class="mi-tabla"><tr><th>ID Participante</th><th>Nombre</th></tr>
        <?php foreach ($lista_solo_kit as $r): ?><tr><td><?=h($r['ID_Participante'])?></td><td><?=h($r['Nombre'])?></td></tr><?php endforeach; ?>
      </table>
    </section>
  <?php endif; ?>

  <section class="Grafica">
    <h2 class="titulo">Inscritos vs Asistieron por clase — <?=h($scopeLabel)?></h2>
    <div class="cols">
      <div><canvas id="chartPctAsistencia"></canvas></div>
      <div>
        <table class="mi-tabla">
          <tr><th>ID_Agenda</th><th>Actividad</th><th>Inscritos</th><th>Asistieron</th><th>%</th></tr>
          <?php foreach ($tabla_ins_vs_asist as $r): ?>
          <tr>
            <td><?=h($r['ID'])?></td><td><?=h($r['Actividad'])?></td>
            <td><?=h($r['inscritos'])?></td><td><?=h($r['asistieron'])?></td><td><?=h($r['pct_asistencia'])?>%</td>
          </tr><?php endforeach; ?>
        </table>
      </div>
    </div>
  </section>

  <section class="Grafica">
    <h2 class="titulo">Tipo de inscripción por clase — <?=h($scopeLabel)?></h2>
    <div class="cols">
      <div><canvas id="chartTipoClase"></canvas></div>
      <div>
        <table class="mi-tabla">
          <tr>
            <th>ID_Agenda</th><th>Actividad</th><th>Fecha</th><th>Horario</th>
            <th>Inscritos (Desde registro)</th><th>Asistieron (Desde registro)</th><th>%</th>
            <th>Inscritos (Desde evento)</th><th>Asistieron (Desde evento)</th><th>%</th>
            <th>Inscritos Total</th><th>Asistieron Total</th><th>%</th>
          </tr>
          <?php foreach ($tipo_por_clase as $r):
            $p0 = ($r['Inscritos_T0'] ?: 0) ? round($r['Asistieron_T0']*100/$r['Inscritos_T0'],1) : 0;
            $p1 = ($r['Inscritos_T1'] ?: 0) ? round($r['Asistieron_T1']*100/$r['Inscritos_T1'],1) : 0;
            $pt = ($r['Inscritos_Total'] ?: 0) ? round($r['Asistieron_Total']*100/$r['Inscritos_Total'],1) : 0;
          ?>
          <tr>
            <td><?=h($r['ID_Agenda'])?></td><td><?=h($r['Actividad'])?></td><td><?=h($r['Fecha'])?></td><td><?=h($r['Horario'])?></td>
            <td><?=h($r['Inscritos_T0'])?></td><td><?=h($r['Asistieron_T0'])?></td><td><?=$p0?>%</td>
            <td><?=h($r['Inscritos_T1'])?></td><td><?=h($r['Asistieron_T1'])?></td><td><?=$p1?>%</td>
            <td><?=h($r['Inscritos_Total'])?></td><td><?=h($r['Asistieron_Total'])?></td><td><?=$pt?>%</td>
          </tr><?php endforeach; ?>
        </table>
      </div>
    </div>
    <div class="note">Tipo 0 = Desde registro (requiere Asistio); Tipo 1 = Desde evento (cuenta por registro). Agendas <?=h(implode(', ', $AGENDAS_ASISTENCIA_POR_INSCRIPCION))?> cuentan por inscripción.</div>
  </section>

  <section class="Grafica">
    <h2 class="titulo">Totales del evento por tipo — <?=h($scopeLabel)?></h2>
    <div class="cols">
      <div><canvas id="chartTotalesTipo"></canvas></div>
      <div>
        <table class="mi-tabla">
          <tr><th>Tipo</th><th>Inscritos</th><th>Asistieron</th><th>%</th></tr>
          <?php foreach ($totales_tipo as $t):
            $p = ($t['Inscritos']?:0) ? round($t['Asistieron']*100/$t['Inscritos'],1) : 0;
            $label = ($t['Tipo']==0 ? 'Desde registro' : 'Desde evento');
          ?>
          <tr><td><?=h($label)?></td><td><?=h($t['Inscritos'])?></td><td><?=h($t['Asistieron'])?></td><td><?=$p?>%</td></tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </section>

  <?php if ($scope === 'total'): ?>
    <section class="Grafica">
      <h2 class="titulo">Día 1 (<?=h($dia1 ?? '-')?>) — Totales por tipo</h2>
      <div class="cols">
        <div><canvas id="chartTotalesD1"></canvas></div>
        <div>
          <table class="mi-tabla">
            <tr><th>Tipo</th><th>Inscritos</th><th>Asistieron</th><th>%</th></tr>
            <?php foreach ($totales_d1 as $t):
              $p = ($t['inscritos']?:0) ? round($t['asistieron']*100/$t['inscritos'],1) : 0;
              $label = ($t['tipo']==0 ? 'Desde registro' : 'Desde evento');
            ?>
            <tr><td><?=h($label)?></td><td><?=h($t['inscritos'])?></td><td><?=h($t['asistieron'])?></td><td><?=$p?>%</td></tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
    </section>

    <section class="Grafica">
      <h2 class="titulo">Día 1 (<?=h($dia1 ?? '-')?>) — Clases (T0/T1)</h2>
      <table class="mi-tabla">
        <tr>
          <th>ID_Agenda</th><th>Actividad</th><th>Salón</th><th>Fecha</th><th>Horario</th>
          <th>Inscritos T0</th><th>Asistieron T0</th><th>% T0</th>
          <th>Inscritos T1</th><th>Asistieron T1</th><th>% T1</th>
          <th>Inscritos Total</th><th>Asistieron Total</th><th>% Total</th>
        </tr>
        <?php foreach ($clases_d1 as $r):
          $p0 = ($r['Inscritos_T0'] ?: 0) ? round($r['Asistieron_T0']*100/$r['Inscritos_T0'],1) : 0;
          $p1 = ($r['Inscritos_T1'] ?: 0) ? round($r['Asistieron_T1']*100/$r['Inscritos_T1'],1) : 0;
          $pt = ($r['Inscritos_Total'] ?: 0) ? round($r['Asistieron_Total']*100/$r['Inscritos_Total'],1) : 0;
        ?>
        <tr>
          <td><?=h($r['ID_Agenda'])?></td><td><?=h($r['Actividad'])?></td><td><?=h($r['Salon'])?></td>
          <td><?=h($r['Fecha'])?></td><td><?=h($r['Horario'])?></td>
          <td><?=h($r['Inscritos_T0'])?></td><td><?=h($r['Asistieron_T0'])?></td><td><?=$p0?>%</td>
          <td><?=h($r['Inscritos_T1'])?></td><td><?=h($r['Asistieron_T1'])?></td><td><?=$p1?>%</td>
          <td><?=h($r['Inscritos_Total'])?></td><td><?=h($r['Asistieron_Total'])?></td><td><?=$pt?>%</td>
        </tr><?php endforeach; ?>
      </table>
    </section>

    <section class="Grafica">
      <h2 class="titulo">Día 2 (<?=h($dia2 ?? '-')?>) — Totales por tipo</h2>
      <div class="cols">
        <div><canvas id="chartTotalesD2"></canvas></div>
        <div>
          <table class="mi-tabla">
            <tr><th>Tipo</th><th>Inscritos</th><th>Asistieron</th><th>%</th></tr>
            <?php foreach ($totales_d2 as $t):
              $p = ($t['inscritos']?:0) ? round($t['asistieron']*100/$t['inscritos'],1) : 0;
              $label = ($t['tipo']==0 ? 'Desde registro' : 'Desde evento');
            ?>
            <tr><td><?=h($label)?></td><td><?=h($t['inscritos'])?></td><td><?=h($t['asistieron'])?></td><td><?=$p?>%</td></tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
    </section>

    <section class="Grafica">
      <h2 class="titulo">Día 2 (<?=h($dia2 ?? '-')?>) — Clases (T0/T1)</h2>
      <table class="mi-tabla">
        <tr>
          <th>ID_Agenda</th><th>Actividad</th><th>Salón</th><th>Fecha</th><th>Horario</th>
          <th>Inscritos T0</th><th>Asistieron T0</th><th>% T0</th>
          <th>Inscritos T1</th><th>Asistieron T1</th><th>% T1</th>
          <th>Inscritos Total</th><th>Asistieron Total</th><th>% Total</th>
        </tr>
        <?php foreach ($clases_d2 as $r):
          $p0 = ($r['Inscritos_T0'] ?: 0) ? round($r['Asistieron_T0']*100/$r['Inscritos_T0'],1) : 0;
          $p1 = ($r['Inscritos_T1'] ?: 0) ? round($r['Asistieron_T1']*100/$r['Inscritos_T1'],1) : 0;
          $pt = ($r['Inscritos_Total'] ?: 0) ? round($r['Asistieron_Total']*100/$r['Inscritos_Total'],1) : 0;
        ?>
        <tr>
          <td><?=h($r['ID_Agenda'])?></td><td><?=h($r['Actividad'])?></td><td><?=h($r['Salon'])?></td>
          <td><?=h($r['Fecha'])?></td><td><?=h($r['Horario'])?></td>
          <td><?=h($r['Inscritos_T0'])?></td><td><?=h($r['Asistieron_T0'])?></td><td><?=$p0?>%</td>
          <td><?=h($r['Inscritos_T1'])?></td><td><?=h($r['Asistieron_T1'])?></td><td><?=$p1?>%</td>
          <td><?=h($r['Inscritos_Total'])?></td><td><?=h($r['Asistieron_Total'])?></td><td><?=$pt?>%</td>
        </tr><?php endforeach; ?>
      </table>
    </section>
  <?php endif; ?>

</div>

<script>
  const POR_CLASE     = <?=json_encode($por_clase, JSON_UNESCAPED_UNICODE)?>;
  const POR_DIA       = <?=json_encode($por_dia, JSON_UNESCAPED_UNICODE)?>;
  const POR_HORA      = <?=json_encode($por_hora, JSON_UNESCAPED_UNICODE)?>;
  const INS_VS_ASIST  = <?=json_encode($tabla_ins_vs_asist, JSON_UNESCAPED_UNICODE)?>;
  const TIPO_CLASE    = <?=json_encode($tipo_por_clase, JSON_UNESCAPED_UNICODE)?>;
  const TOTALES_TIPO  = <?=json_encode($totales_tipo, JSON_UNESCAPED_UNICODE)?>;
  const D1            = <?=json_encode($totales_d1, JSON_UNESCAPED_UNICODE)?>;
  const D2            = <?=json_encode($totales_d2, JSON_UNESCAPED_UNICODE)?>;

  (function(){
    const labels = POR_CLASE.map(r => `${r.Fecha} ${r.Horario} — ${r.Actividad}`);
    const data   = POR_CLASE.map(r => Number(r.AsistentesUnicos||0));
    new Chart(document.getElementById('chartPorClase').getContext('2d'), {
      type: 'bar', data: { labels, datasets: [{ label:'Asistentes únicos', data, borderWidth:1 }] },
      options: { responsive:true, scales: { y: { beginAtZero:true } } }
    });
  })();

  (function(){
    const labels = POR_DIA.map(r => r.fecha);
    const data   = POR_DIA.map(r => Number(r.total_participantes||0));
    new Chart(document.getElementById('chartPorDia').getContext('2d'), {
      type: 'line',
      data: { labels, datasets: [{ label:'Asistentes únicos por día', data, fill:false, tension:.2, borderWidth:2 }] },
      options: { responsive:true, scales: { y:{ beginAtZero:true } } }
    });
  })();

  (function(){
    const labels = POR_HORA.map(r => `${r.fecha} ${String(r.hora).padStart(2,'0')}:00`);
    const data   = POR_HORA.map(r => Number(r.total_participantes||0));
    new Chart(document.getElementById('chartPorHora').getContext('2d'), {
      type: 'bar',
      data: { labels, datasets: [{ label:'Asistentes únicos por hora (inicio)', data, borderWidth:1 }] },
      options: { responsive:true, scales: { y:{ beginAtZero:true } } }
    });
  })();

  (function(){
    const labels = INS_VS_ASIST.map(r => `${r.Fecha} ${r.Horario} — ${r.Actividad}`);
    const dIns   = INS_VS_ASIST.map(r => Number(r.inscritos||0));
    const dAsis  = INS_VS_ASIST.map(r => Number(r.asistieron||0));
    new Chart(document.getElementById('chartPctAsistencia').getContext('2d'), {
      type: 'bar',
      data: { labels, datasets: [{ label:'Inscritos', data:dIns, borderWidth:1 }, { label:'Asistieron', data:dAsis, borderWidth:1 }] },
      options: { responsive:true, scales: { y:{ beginAtZero:true } } }
    });
  })();

  (function(){
    const labels = TIPO_CLASE.map(r => `${r.Fecha} ${r.Horario} — ${r.Actividad}`);
    const desdeRegistro = TIPO_CLASE.map(r => Number(r.Asistieron_T0 || 0));
    const desdeEvento   = TIPO_CLASE.map(r => Number(r.Asistieron_T1 || 0));
    new Chart(document.getElementById('chartTipoClase').getContext('2d'), {
      type: 'bar',
      data: { labels, datasets: [
        { label:'Asistieron (Desde registro)', data:desdeRegistro, borderWidth:1 },
        { label:'Asistieron (Desde evento)',   data:desdeEvento,  borderWidth:1 }
      ]},
      options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
    });
  })();

  (function(){
    const labels = TOTALES_TIPO.map(r => (Number(r.Tipo)===0 ? 'Desde registro' : 'Desde evento'));
    const ins = TOTALES_TIPO.map(r => Number(r.Inscritos || 0));
    const asi = TOTALES_TIPO.map(r => Number(r.Asistieron || 0));
    new Chart(document.getElementById('chartTotalesTipo').getContext('2d'), {
      type:'bar',
      data:{ labels, datasets:[
        { label:'Inscritos', data:ins, borderWidth:1 },
        { label:'Asistieron', data:asi, borderWidth:1 }
      ]},
      options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
    });
  })();

  (function(){
    const el = document.getElementById('chartTotalesD1');
    if (!el) return;
    const labels = D1.map(r => (Number(r.tipo)===0 ? 'Desde registro' : 'Desde evento'));
    const ins = D1.map(r => Number(r.inscritos || 0));
    const asi = D1.map(r => Number(r.asistieron || 0));
    new Chart(el.getContext('2d'), {
      type:'bar',
      data:{ labels, datasets:[
        { label:'Inscritos', data:ins, borderWidth:1 },
        { label:'Asistieron', data:asi, borderWidth:1 }
      ]},
      options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
    });
  })();

  (function(){
    const el = document.getElementById('chartTotalesD2');
    if (!el) return;
    const labels = D2.map(r => (Number(r.tipo)===0 ? 'Desde registro' : 'Desde evento'));
    const ins = D2.map(r => Number(r.inscritos || 0));
    const asi = D2.map(r => Number(r.asistieron || 0));
    new Chart(el.getContext('2d'), {
      type:'bar',
      data:{ labels, datasets:[
        { label:'Inscritos', data:ins, borderWidth:1 },
        { label:'Asistieron', data:asi, borderWidth:1 }
      ]},
      options:{ responsive:true, scales:{ y:{ beginAtZero:true } } }
    });
  })();
</script>

<?php if ($DEBUG): ?>
<script>
  window.addEventListener('error', (e) => {
    const box = document.createElement('div');
    box.style = 'position:fixed;bottom:10px;right:10px;background:#fee2e2;color:#7f1d1d;padding:10px;border:1px solid #fecaca;border-radius:8px;max-width:40vw;z-index:99999';
    box.textContent = 'JS Error: ' + (e.error?.message || e.message);
    document.body.appendChild(box); setTimeout(()=>box.remove(), 8000);
  });
  window.addEventListener('unhandledrejection', (e) => {
    const box = document.createElement('div');
    box.style = 'position:fixed;bottom:10px;right:10px;background:#fffbeb;color:#854d0e;padding:10px;border:1px solid #fde68a;border-radius:8px;max-width:40vw;z-index:99999';
    box.textContent = 'Promise Rejection: ' + (e.reason?.message || e.reason);
    document.body.appendChild(box); setTimeout(()=>box.remove(), 8000);
  });
</script>
<?php endif; ?>
</body>
</html>
