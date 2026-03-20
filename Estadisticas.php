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

require_once __DIR__ . "/Conexiones/Conexion.php";

/* ====== ENTRADA ====== */
$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_evento <= 0) { http_response_code(400); exit("Falta parámetro id de evento."); }

/* ====== CONFIG ====== */
$ID_AGENDA_KIT = 564;
$AGENDAS_ASISTENCIA_POR_INSCRIPCION = [564, 565];

/* ====== FECHAS EVENTO & SCOPE ====== */
$sql_evento = "SELECT DATE(fecha_inicio) AS fi, DATE(fecha_fin) AS ff FROM evento WHERE id = ?";
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

function cond_asistencia_mixta(string $aliasClase='c', string $aliasAgenda='ag'): string {
  global $AGENDAS_ASISTENCIA_POR_INSCRIPCION;
  $ids = array_map('intval', $AGENDAS_ASISTENCIA_POR_INSCRIPCION);
  $in = $ids ? implode(',', $ids) : '';
  $parteAgenda = $in ? " OR {$aliasAgenda}.ID IN ($in)" : "";
  return " ( COALESCE({$aliasClase}.Tipo_Inscripcion,0) = 1 OR {$aliasClase}.Asistio = 1{$parteAgenda} ) ";
}

/* 1) Por clase */
$sql_por_clase = "
SELECT ag.ID AS ID_Agenda, ag.Actividad, ag.Salon, ag.Fecha, ag.Horario, COUNT(DISTINCT c.ID_Participante) AS AsistentesUnicos
FROM clase c JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ? AND ".cond_asistencia_mixta('c','ag').($scopeDate ? cond_fecha('ag') : '')."
GROUP BY ag.ID ORDER BY ag.Fecha, ag.Horario;";
$types = $scopeDate ? 'is' : 'i';
$params = $scopeDate ? [$id_evento, $scopeDate] : [$id_evento];
$res = exec_stmt($conn, $sql_por_clase, $types, $params);
$por_clase = $res->fetch_all(MYSQLI_ASSOC);

/* 2) Únicos del evento */
$sql_unicos_evento = "
SELECT COUNT(DISTINCT c.ID_Participante) AS AsistentesUnicosEvento
FROM clase c JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ? AND ".cond_asistencia_mixta('c','ag').($scopeDate ? cond_fecha('ag') : '').";";
$res = exec_stmt($conn, $sql_unicos_evento, $types, $params);
$unicos_evento = (int)($res->fetch_assoc()['AsistentesUnicosEvento'] ?? 0);

/* 3) Por día */
$sql_por_dia = "
SELECT DATE(ag.Fecha) AS fecha, COUNT(DISTINCT c.ID_Participante) AS total_participantes
FROM clase c JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ? AND ".cond_asistencia_mixta('c','ag').($scopeDate ? cond_fecha('ag') : '')."
GROUP BY fecha ORDER BY fecha;";
$res = exec_stmt($conn, $sql_por_dia, $types, $params);
$por_dia = $res->fetch_all(MYSQLI_ASSOC);

/* 4) Por hora */
$sql_por_hora = "
SELECT DATE(ag.Fecha) AS fecha, HOUR(STR_TO_DATE(SUBSTRING_INDEX(ag.Horario, '-', 1), '%H:%i')) AS hora, COUNT(DISTINCT c.ID_Participante) AS total_participantes
FROM clase c JOIN agenda ag ON ag.ID = c.ID_Agenda
WHERE ag.ID_Evento = ? AND ".cond_asistencia_mixta('c','ag').($scopeDate ? cond_fecha('ag') : '')."
GROUP BY fecha, hora ORDER BY fecha, hora;";
$res = exec_stmt($conn, $sql_por_hora, $types, $params);
$por_hora = $res->fetch_all(MYSQLI_ASSOC);

/* 5.1/5.2/5.3 KITs */
$res = exec_stmt($conn, "SELECT DISTINCT c.ID_Participante, p.Nombre FROM clase c JOIN participante p ON p.ID = c.ID_Participante WHERE c.ID_Agenda = ? ORDER BY p.Nombre", 'i', [$ID_AGENDA_KIT]);
$lista_kit = $res->fetch_all(MYSQLI_ASSOC);

$res = exec_stmt($conn, "SELECT DISTINCT c.ID_Participante, p.Nombre FROM clase c JOIN agenda ag ON ag.ID = c.ID_Agenda JOIN participante p ON p.ID = c.ID_Participante WHERE ag.ID_Evento = ? AND ".cond_asistencia_mixta('c','ag')." AND c.ID_Participante NOT IN (SELECT c2.ID_Participante FROM clase c2 WHERE c2.ID_Agenda = ?) ORDER BY p.Nombre", 'ii', [$id_evento, $ID_AGENDA_KIT]);
$lista_no_kit = $res->fetch_all(MYSQLI_ASSOC);

$res = exec_stmt($conn, "SELECT k.ID_Participante, p.Nombre FROM (SELECT DISTINCT c.ID_Participante FROM clase c WHERE c.ID_Agenda = ?) k JOIN participante p ON p.ID = k.ID_Participante WHERE NOT EXISTS (SELECT 1 FROM clase c3 JOIN agenda ag3 ON ag3.ID = c3.ID_Agenda WHERE ag3.ID_Evento = ? AND c3.ID_Agenda <> ? AND ".cond_asistencia_mixta('c3','ag3')." AND c3.ID_Participante = k.ID_Participante) ORDER BY p.Nombre", 'iii', [$ID_AGENDA_KIT, $id_evento, $ID_AGENDA_KIT]);
$lista_solo_kit = $res->fetch_all(MYSQLI_ASSOC);

/* 6) Inscritos vs Asistieron */
$sql_ins_vs_asist = "
SELECT ag.ID, ag.Actividad, ag.Salon, ag.Fecha, ag.Horario, COALESCE(i.inscritos,0) AS inscritos, COALESCE(a.asistieron,0) AS asistieron, ROUND(100 * COALESCE(a.asistieron,0) / NULLIF(COALESCE(i.inscritos,0),0), 1) AS pct_asistencia
FROM agenda ag
LEFT JOIN (SELECT c.ID_Agenda, COUNT(DISTINCT c.ID_Participante) AS inscritos FROM clase c JOIN agenda agi ON agi.ID = c.ID_Agenda WHERE agi.ID_Evento = ?".($scopeDate ? " AND DATE(agi.Fecha)=? " : "")." GROUP BY c.ID_Agenda) i ON i.ID_Agenda = ag.ID
LEFT JOIN (SELECT c.ID_Agenda, COUNT(DISTINCT c.ID_Participante) AS asistieron FROM clase c JOIN agenda aga ON aga.ID = c.ID_Agenda WHERE aga.ID_Evento = ? AND ".cond_asistencia_mixta('c','aga').($scopeDate ? " AND DATE(aga.Fecha)=? " : "")." GROUP BY c.ID_Agenda) a ON a.ID_Agenda = ag.ID
WHERE ag.ID_Evento = ?".($scopeDate ? " AND DATE(ag.Fecha)=? " : "")." ORDER BY ag.Fecha, ag.Horario;";
$params_ins = $scopeDate ? [$id_evento, $scopeDate, $id_evento, $scopeDate, $id_evento, $scopeDate] : [$id_evento, $id_evento, $id_evento];
$types_ins = $scopeDate ? 'isisi' : 'iii'; // Wait, Step 1264 said isisis, let me re-count
// $id_evento, $scopeDate (2), $id_evento, $scopeDate (2), $id_evento, $scopeDate (2) = 6 params.
$types_ins = $scopeDate ? 'isisis' : 'iii'; 
$res = exec_stmt($conn, $sql_ins_vs_asist, $types_ins, $params_ins);
$tabla_ins_vs_asist = $res->fetch_all(MYSQLI_ASSOC);

/* 8) Totales Tipo */
$sql_totales_tipo = "SELECT COALESCE(c.Tipo_Inscripcion,0) AS Tipo, COUNT(DISTINCT c.ID_Participante) AS Inscritos, COUNT(DISTINCT CASE WHEN ".cond_asistencia_mixta('c','ag')." THEN c.ID_Participante END) AS Asistieron FROM clase c JOIN agenda ag ON ag.ID = c.ID_Agenda WHERE ag.ID_Evento = ? ".($scopeDate ? cond_fecha('ag') : '')." GROUP BY Tipo ORDER BY Tipo;";
$res = exec_stmt($conn, $sql_totales_tipo, $types, $params);
$totales_tipo = $res->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Estadísticas - Evento #<?=h($id_evento)?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
  <?php include "header_css.php"; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body, button, input, select, textarea {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }
    @media (min-width: 768px) {
      body { padding-right: var(--sidebar-width, 280px) !important; }
    }
    .stats-container {
      width: 98%;
      margin: 20px auto;
      padding-bottom: 60px;
    }
    .page-header {
      background: var(--theme-surface-strong);
      border: 1px solid var(--theme-border);
      padding: 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }
    .header-left .page-title {
      margin: 0;
      color: var(--theme-title);
      font-size: 26px;
      text-shadow: 0 0 10px rgba(124, 236, 255, 0.3);
    }
    .header-left .page-subtitle {
      margin: 4px 0 0;
      color: var(--theme-text-soft);
      font-size: 13px;
    }
    .segmented-control {
      display: flex;
      background: rgba(0,0,0,0.2);
      padding: 4px;
      border-radius: 30px;
      border: 1px solid var(--theme-border);
    }
    .seg-btn {
      padding: 8px 20px;
      border-radius: 25px;
      color: var(--theme-text-soft);
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .seg-btn:hover { color: white; }
    .seg-btn.active {
      background: var(--theme-primary);
      color: white;
      box-shadow: 0 4px 12px rgba(28, 169, 220, 0.3);
    }
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: var(--theme-surface-strong);
      border: 1px solid var(--theme-border);
      border-radius: 12px;
      padding: 24px;
      position: relative;
      overflow: hidden;
      transition: transform 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; width: 4px; height: 100%;
      background: var(--theme-primary);
    }
    .stat-card .label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--theme-text-soft);
      margin-bottom: 8px;
    }
    .stat-card .value {
      font-size: 32px;
      font-weight: 800;
      color: var(--theme-title);
    }
    .chart-section {
      background: var(--theme-surface-strong);
      border: 1px solid var(--theme-border);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: var(--theme-shadow);
      overflow: hidden;
    }
    .section-title {
      color: var(--theme-title);
      font-size: 18px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .section-title::before {
      content: '';
      width: 4px; height: 18px;
      background: var(--theme-primary);
      border-radius: 2px;
    }
    .data-flex {
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
    }
    .chart-box { flex: 1.5; min-width: 300px; }
    .table-box { flex: 1; min-width: 400px; max-height: 500px; overflow-y: auto; }
    .badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 800;
    }
    .btn-debug {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      text-decoration: none;
      border: 1px solid var(--theme-border);
      color: var(--theme-text-soft);
    }
    .btn-debug:hover { background: var(--theme-border); color: white; }
  </style>
</head>
<body class="fade-in">
  <?php include "sidebar.php"; ?>

  <div class="stats-container">
    <header class="page-header">
      <div class="header-left">
        <h1 class="page-title">Estadísticas del Evento</h1>
        <p class="page-subtitle"><?=h($scopeLabel)?> · Evento #<?=h($id_evento)?></p>
      </div>

      <div class="segmented-control">
        <a class="seg-btn <?= $scope==='d1'?'active':'' ?>" href="?id=<?=h($id_evento)?>&scope=d1<?= $DEBUG ? '&debug=1' : '' ?>">Día 1</a>
        <a class="seg-btn <?= $scope==='d2'?'active':'' ?>" href="?id=<?=h($id_evento)?>&scope=d2<?= $DEBUG ? '&debug=1' : '' ?>">Día 2</a>
        <a class="seg-btn <?= $scope==='total'?'active':'' ?>" href="?id=<?=h($id_evento)?>&scope=total<?= $DEBUG ? '&debug=1' : '' ?>">Total</a>
      </div>

      <div class="header-actions" style="display:flex; gap:10px; align-items:center;">
        <a href="Evento_inicio.php?id=<?=h($id_evento)?>" class="btn-debug" style="background:var(--theme-chip); color:var(--theme-title); border-color:var(--theme-border);">Volver</a>
        <?php if(!$DEBUG): ?><a href="?id=<?=h($id_evento)?>&scope=<?=h($scope)?>&debug=1" class="btn-debug">DEBUG ON</a>
        <?php else: ?><a href="?id=<?=h($id_evento)?>&scope=<?=h($scope)?>" class="btn-debug" style="background:#7f1d1d; color:white; border:none;">DEBUG OFF</a><?php endif; ?>
      </div>
    </header>

    <section class="cards-grid">
      <div class="stat-card">
        <div class="label">Asistencia Única</div>
        <div class="value"><?=number_format($unicos_evento)?></div>
      </div>
      <?php if ($scope === 'total'): ?>
        <div class="stat-card" style="--theme-primary: #ffc14d;">
          <div class="label">Inscritos Kit</div>
          <div class="value"><?=number_format(count($lista_kit))?></div>
        </div>
        <div class="stat-card" style="--theme-primary: #a78bfa;">
          <div class="label">Asist. Sin Kit</div>
          <div class="value"><?=number_format(count($lista_no_kit))?></div>
        </div>
        <div class="stat-card" style="--theme-primary: #4ade80;">
          <div class="label">Solo Kit</div>
          <div class="value"><?=number_format(count($lista_solo_kit))?></div>
        </div>
      <?php endif; ?>
    </section>

    <section class="chart-section">
      <h2 class="section-title">Asistencia única por clase</h2>
      <div class="data-flex">
        <div class="chart-box"><canvas id="chartPorClase"></canvas></div>
        <div class="table-box">
          <table class="mi-tabla">
            <thead>
              <tr><th>Actividad</th><th>Horario</th><th>Asistentes</th></tr>
            </thead>
            <tbody>
              <?php foreach ($por_clase as $r): ?>
              <tr>
                <td style="text-align:left;"><?=h($r['Actividad'])?></td>
                <td><?=h($r['Horario'])?></td>
                <td style="font-weight:700; color:var(--theme-title);"><?=h($r['AsistentesUnicos'])?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap:24px;">
      <section class="chart-section">
        <h2 class="section-title">Asistencia por Día</h2>
        <canvas id="chartPorDia" style="max-height:300px;"></canvas>
      </section>
      <section class="chart-section">
        <h2 class="section-title">Asistencia por Hora</h2>
        <canvas id="chartPorHora" style="max-height:300px;"></canvas>
      </section>
    </div>

    <?php if ($scope === 'total'): ?>
      <section class="chart-section">
        <h2 class="section-title">KPI de Entrega de Kits</h2>
        <div class="data-flex">
          <div class="table-box">
             <h4 style="color:var(--theme-text-soft);">Inscritos Kit (Top 10)</h4>
             <table class="mi-tabla">
               <?php foreach ($lista_kit as $r): ?><tr><td><?=h($r['Nombre'])?></td></tr><?php endforeach; ?>
             </table>
          </div>
          <div class="table-box">
             <h4 style="color:var(--theme-text-soft);">Asistieron Sin Kit</h4>
             <table class="mi-tabla">
               <?php foreach ($lista_no_kit as $r): ?><tr><td><?=h($r['Nombre'])?></td></tr><?php endforeach; ?>
             </table>
          </div>
          <div class="table-box">
             <h4 style="color:var(--theme-text-soft);">Solo Kit</h4>
             <table class="mi-tabla">
               <?php foreach ($lista_solo_kit as $r): ?><tr><td><?=h($r['Nombre'])?></td></tr><?php endforeach; ?>
             </table>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <section class="chart-section">
      <h2 class="section-title">Inscritos vs Asistieron</h2>
      <div class="data-flex">
        <div class="chart-box"><canvas id="chartPctAsistencia"></canvas></div>
        <div class="table-box">
          <table class="mi-tabla">
            <thead>
              <tr><th>Actividad</th><th>Inscritos</th><th>Asistieron</th><th>%</th></tr>
            </thead>
            <tbody>
              <?php foreach ($tabla_ins_vs_asist as $r): ?>
              <tr>
                <td style="text-align:left;"><?=h($r['Actividad'])?></td>
                <td><?=h($r['inscritos'])?></td>
                <td><?=h($r['asistieron'])?></td>
                <td><span class="badge" style="background:rgba(74,222,128,0.1); color:#4ade80;"><?=h($r['pct_asistencia'])?>%</span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="chart-section">
      <h2 class="section-title">Totales por Origen</h2>
      <div class="data-flex">
        <div class="chart-box" style="flex:1;"><canvas id="chartTotalesTipo"></canvas></div>
        <div class="table-box" style="flex:2;">
          <table class="mi-tabla">
            <thead><tr><th>Origen</th><th>Inscritos</th><th>Asistieron</th><th>%</th></tr></thead>
            <?php foreach ($totales_tipo as $t):
              $p = ($t['Inscritos']?:0) ? round($t['Asistieron']*100/$t['Inscritos'],1) : 0;
              $label = ($t['Tipo']==0 ? 'Desde Registro' : 'Desde Evento');
            ?>
            <tr><td><?=h($label)?></td><td><?=h($t['Inscritos'])?></td><td><?=h($t['Asistieron'])?></td><td><?=$p?>%</td></tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
    </section>
  </div>

  <script>
    Chart.defaults.color = 'rgba(255,255,255,0.7)';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.1)';
    Chart.defaults.font.family = "'Segoe UI', sans-serif";
    
    const themeColors = {
      primary: '#1ca9dc',
      secondary: '#7cecff',
      accent: '#ffc14d',
      purple: '#a78bfa',
      green: '#4ade80'
    };

    const POR_CLASE     = <?=json_encode($por_clase, JSON_UNESCAPED_UNICODE)?>;
    const POR_DIA       = <?=json_encode($por_dia, JSON_UNESCAPED_UNICODE)?>;
    const POR_HORA      = <?=json_encode($por_hora, JSON_UNESCAPED_UNICODE)?>;
    const INS_VS_ASIST  = <?=json_encode($tabla_ins_vs_asist, JSON_UNESCAPED_UNICODE)?>;
    const TOTALES_TIPO  = <?=json_encode($totales_tipo, JSON_UNESCAPED_UNICODE)?>;

    new Chart(document.getElementById('chartPorClase'), {
      type: 'bar',
      data: {
        labels: POR_CLASE.map(r => r.Actividad),
        datasets: [{
          label: 'Asistentes Únicos',
          data: POR_CLASE.map(r => r.AsistentesUnicos),
          backgroundColor: themeColors.secondary,
          borderRadius: 6
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('chartPorDia'), {
      type: 'line',
      data: {
        labels: POR_DIA.map(r => r.fecha),
        datasets: [{
          label: 'Participantes',
          data: POR_DIA.map(r => r.total_participantes),
          borderColor: themeColors.secondary,
          backgroundColor: 'rgba(124, 236, 255, 0.1)',
          fill: true,
          tension: 0.4,
          pointRadius: 6,
          pointBackgroundColor: themeColors.secondary
        }]
      },
      options: { responsive: true }
    });

    new Chart(document.getElementById('chartPorHora'), {
      type: 'bar',
      data: {
        labels: POR_HORA.map(r => `${r.hora}:00`),
        datasets: [{
          label: 'Asistencia',
          data: POR_HORA.map(r => r.total_participantes),
          backgroundColor: themeColors.primary,
          borderRadius: 4
        }]
      },
      options: { responsive: true }
    });

    new Chart(document.getElementById('chartPctAsistencia'), {
      type: 'bar',
      data: {
        labels: INS_VS_ASIST.map(r => r.Actividad),
        datasets: [
          { label: 'Inscritos', data: INS_VS_ASIST.map(r => r.inscritos), backgroundColor: 'rgba(255,255,255,0.1)' },
          { label: 'Asistieron', data: INS_VS_ASIST.map(r => r.asistieron), backgroundColor: themeColors.green }
        ]
      },
      options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: false } } }
    });

    new Chart(document.getElementById('chartTotalesTipo'), {
      type: 'doughnut',
      data: {
        labels: TOTALES_TIPO.map(r => (Number(r.Tipo)===0 ? 'Registro' : 'Evento')),
        datasets: [{
          data: TOTALES_TIPO.map(r => r.Asistieron),
          backgroundColor: [themeColors.primary, themeColors.purple],
          borderWidth: 0
        }]
      },
      options: { responsive: true, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
    });
  </script>
</body>
</html>
