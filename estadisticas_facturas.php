<?php
require_once __DIR__ . '/portal_puntos/conexion_facturas.php';

// Parámetros opcionales
$rfc = isset($_GET['rfc']) ? trim($_GET['rfc']) : '';
$desde = isset($_GET['desde']) ? $_GET['desde'] : ''; // opcional YYYY-MM-DD
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : ''; // opcional YYYY-MM-DD

// Validación básica de fechas
$useDates = false;
if ($desde !== '' && $hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    $useDates = true;
}

// --------- VISTA DETALLE POR RFC ---------
if ($rfc !== '') {
    $sql = "
        SELECT Tienda, NoFactura, FechaFactura, Total
        FROM facturascnx
        WHERE EstatusCanjeada = 1
          AND RFCCanjeada = ?
          " . ($useDates ? " AND FechaFactura BETWEEN ? AND ? " : "") . "
        ORDER BY FechaFactura DESC, Tienda, NoFactura
    ";
    $stmt = $conn_facturas->prepare($sql);
    if ($useDates) {
        $stmt->bind_param("sss", $rfc, $desde, $hasta);
    } else {
        $stmt->bind_param("s", $rfc);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    // Totales
    $count = 0;
    $total = 0.0;
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
        $count++;
        $total += (float)$row['Total'];
    }
    $stmt->close();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Detalle de facturas canjeadas · <?= htmlspecialchars($rfc) ?></title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <style>
        body{margin:0;padding:20px;font-family:'Segoe UI',sans-serif;color:#fff;background: radial-gradient(circle at center,#0d1c3b,#1e2a78);}
        h2{margin:0 0 10px;text-align:center;color:#ff7f00;text-shadow:0 0 10px #ff7f00;}
        .sub{text-align:center;margin-bottom:14px;opacity:.9}
        .toolbar{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin:10px auto 16px;}
        .btn{background:#ff7f00;color:#fff;border:none;border-radius:8px;padding:10px 14px;text-decoration:none;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.35);display:inline-block}
        .btn.alt{background:#1565c0}
        .wrap{max-width:1000px;margin:0 auto}
        .filters{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:10px}
        .filters input{padding:6px 8px;border-radius:6px;border:1px solid #294;outline:none}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse;min-width:680px}
        th,td{padding:10px;text-align:center;border-bottom:1px solid rgba(255,255,255,.15)}
        th{background:#1f1f3b;color:#ffae42}
        td{background:#2a2a40}
        tfoot td{background:#1b1b2e;font-weight:700}
        .right{text-align:right}
      </style>
      <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    </head>
    <body>
      <div class="wrap">
        <h2>📄 Detalle por RFC</h2>
        <div class="sub">RFC: <strong><?= htmlspecialchars($rfc) ?></strong></div>

        <form class="filters" method="get">
          <input type="hidden" name="rfc" value="<?= htmlspecialchars($rfc) ?>">
          <label>Desde: <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"></label>
          <label>Hasta: <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"></label>
          <button class="btn alt" type="submit">Filtrar</button>
        </form>

        <div class="toolbar">
          <a href="<?= strtok($_SERVER['REQUEST_URI'],'?') ?>" class="btn">← Volver al resumen</a>
          <?php if ($count>0): ?>
            <button class="btn alt" onclick="exportCSV()">Exportar CSV</button>
          <?php endif; ?>
        </div>

        <div class="table-wrap">
          <table id="tabla">
            <thead>
              <tr>
                <th>Tienda</th>
                <th>No. Factura</th>
                <th>Fecha</th>
                <th>Monto</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($count>0): foreach ($rows as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['Tienda']) ?></td>
                  <td><?= htmlspecialchars($r['NoFactura']) ?></td>
                  <td><?= htmlspecialchars($r['FechaFactura']) ?></td>
                  <td class="right">$<?= number_format((float)$r['Total'],2) ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="4">Sin facturas canjeadas<?= $useDates?' en el rango':'' ?>.</td></tr>
              <?php endif; ?>
            </tbody>
            <?php if ($count>0): ?>
            <tfoot>
              <tr>
                <td class="right" colspan="2">Totales:</td>
                <td><?= $count ?> factura(s)</td>
                <td class="right">$<?= number_format($total,2) ?></td>
              </tr>
            </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>

      <script>
        function exportCSV(){
          const rows = [["Tienda","NoFactura","Fecha","Monto"]];
          document.querySelectorAll("#tabla tbody tr").forEach(tr=>{
            const tds=[...tr.querySelectorAll("td")].map(td=>td.innerText.replace(/[\$,]/g,''));
            if(tds.length) rows.push(tds);
          });
          const csv = rows.map(r=>r.map(v=>`"${(v??"").toString().replaceAll('"','""')}"`).join(",")).join("\n");
          const blob = new Blob([csv], {type:"text/csv;charset=utf-8;"});
          const url = URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.download = "detalle_facturas_<?= preg_replace('/[^A-Z0-9]/i','',$rfc) ?>.csv";
          document.body.appendChild(a); a.click(); a.remove();
          URL.revokeObjectURL(url);
        }
      </script>
    </body>
    </html>
    <?php
    exit;
}

// --------- VISTA RESUMEN (AGRUPADA POR RFCCanjeada) ---------
$sql = "
  SELECT RFCCanjeada AS RFC,
         COUNT(*)                AS facturas_canjeadas,
         COALESCE(SUM(Total),0) AS monto_total
  FROM facturascnx
  WHERE EstatusCanjeada = 1
    AND RFCCanjeada IS NOT NULL
    AND RFCCanjeada <> ''
  " . ($useDates ? " AND FechaFactura BETWEEN ? AND ? " : "") . "
  GROUP BY RFCCanjeada
  ORDER BY monto_total DESC
";
$stmt = $conn_facturas->prepare($sql);
if ($useDates) {
    $stmt->bind_param("ss", $desde, $hasta);
}
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$total_fact = 0; $total_monto = 0.0;
while ($r = $res->fetch_assoc()) {
  $rows[] = $r;
  $total_fact  += (int)$r['facturas_canjeadas'];
  $total_monto += (float)$r['monto_total'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte por RFC Canjeado</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body{margin:0;padding:20px;font-family:'Segoe UI',sans-serif;color:#fff;background: radial-gradient(circle at center,#0d1c3b,#1e2a78);}
  h2{text-align:center;color:#ff7f00;text-shadow:0 0 10px #ff7f00;margin:0 0 10px;}
  .wrap{max-width:1000px;margin:0 auto}
  .filters{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin:8px 0 14px}
  .filters input{padding:6px 8px;border-radius:6px;border:1px solid #294;outline:none}
  .btn{background:#ff7f00;color:#fff;border:none;border-radius:8px;padding:10px 14px;text-decoration:none;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.35);display:inline-block}
  .btn.alt{background:#1565c0}
  .table-wrap{overflow-x:auto}
  table{width:100%;border-collapse:collapse;min-width:560px}
  th,td{padding:10px;text-align:center;border-bottom:1px solid rgba(255,255,255,.15)}
  th{background:#1f1f3b;color:#ff7f00}
  td{background:#2a2a40}
  tfoot td{background:#1b1b2e;font-weight:700}
  .right{text-align:right}
  .toolbar{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin:10px 0}
  a.link{color:#ffd699}
</style>
<link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
</head>
<body>
<div class="wrap">
  <h2>📊 Facturas Canjeadas por RFC</h2>

  <form class="filters" method="get">
    <label>Desde: <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"></label>
    <label>Hasta: <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"></label>
    <button class="btn alt" type="submit">Filtrar</button>
    <?php if ($useDates): ?><a class="btn" href="<?= strtok($_SERVER['REQUEST_URI'],'?') ?>">Quitar filtro</a><?php endif; ?>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>RFC Canjeado</th>
          <th>Facturas Canjeadas</th>
          <th>Monto Total</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): foreach ($rows as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['RFC']) ?></td>
            <td><?= number_format($row['facturas_canjeadas']) ?></td>
            <td class="right">$<?= number_format((float)$row['monto_total'],2) ?></td>
            <td><a class="link" href="?rfc=<?= urlencode($row['RFC']) ?><?= $useDates ? '&desde='.urlencode($desde).'&hasta='.urlencode($hasta) : '' ?>">Ver detalles →</a></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="4">No hay facturas canjeadas<?= $useDates?' en el rango':'' ?>.</td></tr>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($rows)): ?>
      <tfoot>
        <tr>
          <td class="right">Totales:</td>
          <td><?= number_format($total_fact) ?></td>
          <td class="right">$<?= number_format($total_monto,2) ?></td>
          <td></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>

  <div class="toolbar">
    <a href="#" class="btn" onclick="history.back(); return false;">← Volver</a>
  </div>
</div>
</body>
</html>
