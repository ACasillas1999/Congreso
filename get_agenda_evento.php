<?php
session_name("CON");
session_start();                 // <-- FALTABA

require_once __DIR__ . "/Conexiones/Conexion.php";
header('Content-Type: text/html; charset=UTF-8');

$evento_id = intval($_GET['evento'] ?? 0);
if (!$evento_id) { echo "<p>Evento inv√°lido.</p>"; exit; }

$rol = $_SESSION['Rol'] ?? '';

if ($rol === 'Vendedor') {
  // Vendedor: solo NO exclusivas
  $sql = "
    SELECT ag.ID, ag.Actividad, ag.Fecha, ag.Horario, ag.Salon
    FROM agenda ag
    LEFT JOIN actividades a
      ON a.ID_Evento = ag.ID_Evento AND a.Actividad = ag.Actividad
    WHERE ag.ID_Evento = ?
      AND ag.Actividad <> 'Vacio'
      AND COALESCE(a.Exclusiva,0) = 0
    ORDER BY ag.Fecha, SUBSTRING(ag.Horario,1,5), ag.Salon
  ";
} else {
  // Gerente/Admin/Evento: todo
  $sql = "
    SELECT ID, Actividad, Fecha, Horario, Salon
    FROM agenda
    WHERE ID_Evento = ?
      AND Actividad <> 'Vacio'
    ORDER BY Fecha, SUBSTRING(Horario,1,5), Salon
  ";
}

$q = $conn->prepare($sql);
$q->bind_param("i", $evento_id);
$q->execute();
$res = $q->get_result();

// --- NUEVO: mapa Actividad -> Exclusiva (sin cambiar tus queries)
$mapExclusiva = [];
$qe = $conn->prepare("SELECT Actividad, Exclusiva FROM actividades WHERE ID_Evento = ?");
$qe->bind_param("i", $evento_id);
$qe->execute();
$re = $qe->get_result();
while ($r = $re->fetch_assoc()) {
  // Exclusiva = 1 (exclusiva), 0 (normal)
  $mapExclusiva[$r['Actividad']] = (int)$r['Exclusiva'];
}


// --- FIN NUEVO





// --- NUEVO BLOQUE B: capacidad por actividad + inscritos por clase y reconstruir $agenda

// 2.1) Volcamos el result en un arreglo (lo usaremos varias veces)
$filas = [];
while ($row = $res->fetch_assoc()) { $filas[] = $row; }

// 2.2) Capacidad por Actividad (mismo evento)
$mapCapacidad = [];
$qc = $conn->prepare("SELECT Actividad, capacidad FROM actividades WHERE ID_Evento = ?");
$qc->bind_param("i", $evento_id);
$qc->execute();
$rc = $qc->get_result();
while ($r = $rc->fetch_assoc()) {
  $mapCapacidad[$r['Actividad']] = (int)($r['capacidad'] ?? 0);
}

// 2.3) Inscritos por clase (ID_Agenda en tabla 'clase')
$mapInscritos = [];
$ids = array_map(fn($x) => (int)$x['ID'], $filas);
if ($ids) {
  $in = implode(',', $ids);
  $rsCnt = $conn->query("SELECT ID_Agenda, COUNT(*) AS inscritos
                         FROM clase
                         WHERE ID_Agenda IN ($in)
                         GROUP BY ID_Agenda");
  while ($c = $rsCnt->fetch_assoc()) {
    $mapInscritos[(int)$c['ID_Agenda']] = (int)$c['inscritos'];
  }
}

// 2.4) Reconstruimos $agenda agrupando por Fecha/Horario
$agenda = [];
foreach ($filas as $s) {
  $agenda[$s['Fecha']][$s['Horario']][] = $s;
}

if (empty($agenda)) {
  echo "<p>No hay actividades programadas a√∫n para este evento.</p>";
  exit;
}

// Render (mismo formato que ya usas)
foreach ($agenda as $fecha => $porHora) {
  echo "<div style='border-left:4px solid #3da0ff;padding:8px 12px;margin:10px 0'>";
  echo "<div style='font-weight:600;margin-bottom:6px'>fecha " . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . "</div>";

  foreach ($porHora as $hora => $items) {
    echo "<div style='font-weight:600;margin:6px 0'>hora " . htmlspecialchars($hora, ENT_QUOTES, 'UTF-8') . "</div>";
    echo "<div style='display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))'>";

    foreach ($items as $it) {
    $id    = (int)$it['ID'];
$act   = (string)$it['Actividad'];
$salon = htmlspecialchars($it['Salon'], ENT_QUOTES, 'UTF-8');
$hor   = htmlspecialchars($it['Horario'], ENT_QUOTES, 'UTF-8');

// calculamos cupo
$ins   = (int)($mapInscritos[$id] ?? 0);
$cap   = (int)($mapCapacidad[$act] ?? 0);
$full  = ($cap > 0 && $ins >= $cap);

// exclusiva real (del cat®¢logo 'actividades')
$esExclusiva = !empty($mapExclusiva[$act]);

$clasesSlot = 'slot';
if ($esExclusiva) $clasesSlot .= ' exclusiva';
if ($full)        $clasesSlot .= ' lleno is-disabled';

echo "<label class='{$clasesSlot}'>";

// si est®¢ lleno, lo deshabilitamos y dejamos marca para JS
echo "<input type='checkbox'
             class='chk-slot'
             name='actividades[]'
             value='{$id}'
             data-fecha='" . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . "'
             data-horario='{$hor}'"
     . ($full ? " data-lock='capacity' disabled" : "")
     . ">";

echo "<div style='display:flex;flex-direction:column;gap:4px'>";
  echo "<div><strong>" . htmlspecialchars($act, ENT_QUOTES, 'UTF-8') . "</strong></div>";
  echo "<div style='font-size:12px;opacity:.85'>Salon: {$salon}</div>";
  echo "<div style='font-size:12px;opacity:.85'>Horario: {$hor}</div>";
  // mostramos cupo
  $cupoTxt = ($cap > 0) ? "{$ins} / {$cap}" : "{$ins}";
  echo "<div style='font-size:12px;opacity:.9'>Cupo: {$cupoTxt}</div>";
echo "</div>";

if ($esExclusiva) {
  echo "<span class='badge-exclusiva' title='Actividad exclusiva'>Exclusiva</span>";
}
if ($full) {
  echo "<span class='badge-cupo lleno' title='No hay lugares'>Cupo lleno</span>";
}

echo "</label>";

    }

    echo "</div>";
  }
  echo "</div>";
}
