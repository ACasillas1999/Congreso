<?php
/* stats_vendedores.php */
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "Conexiones/Conexion.php";

$evento_id = isset($_GET['evento']) ? intval($_GET['evento']) : 0;
$sucursal  = isset($_GET['sucursal']) ? trim($_GET['sucursal']) : 'TODAS';

if ($evento_id <= 0) { http_response_code(400); echo json_encode(['error'=>'Evento inválido']); exit; }

// Filtro sucursal
$useSucursal = (strcasecmp($sucursal,'TODAS') !== 0);
$sucComp = strtoupper($sucursal);

// === Agregados por vendedor (registrados y con ≥1 asistencia) ===
$sqlBase = "
  SELECT
    COALESCE(NULLIF(p.Vendedor,''), '(Sin vendedor)') AS Vendedor,
    COUNT(*) AS total_clientes,
    SUM(
      CASE WHEN EXISTS (
        SELECT 1
        FROM clase c
        JOIN agenda ag ON ag.ID = c.ID_Agenda
        WHERE c.ID_Participante = p.ID
          AND ag.ID_Evento = p.ID_Evento
          AND c.Asistio = 1
      ) THEN 1 ELSE 0 END
    ) AS con_asistencia
  FROM participante p
  WHERE p.ID_Evento = ?
";
$params = [$evento_id];
$types  = "i";

if ($useSucursal) {
  $sqlBase .= " AND UPPER(COALESCE(NULLIF(p.Sucursal,''),'(SIN SUCURSAL)')) = ? ";
  $params[] = $sucComp;
  $types   .= "s";
}
$sqlBase .= "
  GROUP BY COALESCE(NULLIF(p.Vendedor,''), '(Sin vendedor)')
  ORDER BY total_clientes DESC
";
$stmt = $conn->prepare($sqlBase);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rs = $stmt->get_result();

$vendedores = [];
$top_general = null;
while ($row = $rs->fetch_assoc()) {
  $t = (int)$row['total_clientes'];
  $a = (int)$row['con_asistencia'];
  $row['tasa'] = ($t>0) ? round(($a/$t)*100) : 0;
  $vendedores[] = $row;
  if ($top_general === null || $t > $top_general['total']) {
    $top_general = ['vendedor' => $row['Vendedor'], 'total' => $t];
  }
}
$stmt->close();

// === Top por sucursal (solo si piden TODAS) ===
$tops_por_sucursal = [];
$top_sucursal = null;
if (!$useSucursal) {
  $sql = "
    SELECT
      UPPER(COALESCE(NULLIF(p.Sucursal,''),'(SIN SUCURSAL)')) AS Sucursal,
      COALESCE(NULLIF(p.Vendedor,''), '(Sin vendedor)') AS Vendedor,
      COUNT(*) AS total
    FROM participante p
    WHERE p.ID_Evento = ?
    GROUP BY Sucursal, Vendedor
    ORDER BY Sucursal, total DESC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $evento_id);
  $stmt->execute();
  $rs = $stmt->get_result();

  $rows = [];
  while ($r = $rs->fetch_assoc()) { $rows[] = $r; }
  $stmt->close();

  // Reducir para obtener top por sucursal
  $bucket = [];
  foreach ($rows as $r) {
    $suc = $r['Sucursal'];
    if (!isset($bucket[$suc]) || (int)$r['total'] > (int)$bucket[$suc]['total']) {
      $bucket[$suc] = ['sucursal'=>$suc, 'vendedor'=>$r['Vendedor'], 'total'=>(int)$r['total']];
    }
  }
  $tops_por_sucursal = array_values($bucket);
} else {
  // Si es sucursal específica, también devolvemos su top (por si lo quieren mostrar en resumen)
  $sql = "
    SELECT
      COALESCE(NULLIF(p.Vendedor,''), '(Sin vendedor)') AS Vendedor,
      COUNT(*) AS total
    FROM participante p
    WHERE p.ID_Evento = ?
      AND UPPER(COALESCE(NULLIF(p.Sucursal,''),'(SIN SUCURSAL)')) = ?
    GROUP BY Vendedor
    ORDER BY total DESC
    LIMIT 1
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("is", $evento_id, $sucComp);
  $stmt->execute();
  $r = $stmt->get_result()->fetch_assoc();
  if ($r) {
    $top_sucursal = ['sucursal'=>$sucComp, 'vendedor'=>$r['Vendedor'], 'total'=>(int)$r['total']];
  }
  $stmt->close();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'vendedores' => $vendedores,
  'top_general' => $top_general,
  'top_sucursal' => $top_sucursal,
  'tops_por_sucursal' => $tops_por_sucursal
], JSON_UNESCAPED_UNICODE);
