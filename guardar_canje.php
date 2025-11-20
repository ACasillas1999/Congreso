<?php
session_name("CON"); session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . "/Conexiones/Conexion.php";

function salir($ok, $html, $id_evento){
  $tit   = $ok ? "🎉 Canje realizado" : "❌ No se pudo canjear";
  $grad1 = $ok ? "#34d399" : "#fb7185";   // verdes/rojos
  $grad2 = $ok ? "#059669" : "#b91c1c";

  $id_evento = intval($id_evento);
  $volverCanje = "canjear.php?id=".$id_evento;
  $irEvento    = "Evento_inicio.php?id=".$id_evento;

  echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>$tit</title>
  <style>
    :root{
      --bg1:#0b1535; --bg2:#142455; --card:rgba(255,255,255,.06);
      --text:#fff; --ok1:#34d399; --ok2:#059669; --err1:#fb7185; --err2:#b91c1c;
      --brand:#ff8c00; --brand2:#ff5722;
    }
    *{box-sizing:border-box}
    body{
      margin:0; padding:24px 14px; min-height:100dvh;
      font-family:"Segoe UI", system-ui, Arial, sans-serif; color:var(--text);
      background: radial-gradient(circle at 20% 0%, var(--bg2) 0%, var(--bg1) 70%);
      display:flex; align-items:center; justify-content:center;
    }
    .wrap{
      width:min(92vw, 760px);
      background:var(--card);
      border-radius:16px;
      padding:clamp(16px, 4vw, 28px);
      box-shadow:0 14px 32px rgba(0,0,0,.45);
      text-align:left;
      position:relative;
      overflow:hidden;
    }
    .wrap::before{
      content:"";
      position:absolute; inset:-2px -2px auto -2px; height:8px;
      background: linear-gradient(90deg, $grad1, $grad2);
    }
    h2{
      margin:0 0 10px; font-size:clamp(20px, 5vw, 28px);
      text-shadow:0 0 8px rgba(255,255,255,.25);
    }
    .content{ font-size:16px; line-height:1.45; margin-top:8px; }
    .content ul{ margin:8px 0 0 20px; }
    .actions{
      margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;
    }
    .btn{
      display:inline-flex; align-items:center; gap:8px;
      padding:12px 16px; border-radius:12px; color:#fff; text-decoration:none;
      font-weight:700; font-size:16px; cursor:pointer;
      box-shadow:0 8px 18px rgba(0,0,0,.35), inset 0 -2px 0 rgba(255,255,255,.12);
      transition:transform .15s ease, filter .2s ease, background .25s ease;
    }
    .btn:hover{ transform:translateY(-1px) scale(1.01); }
    .btn:active{ transform:translateY(0) scale(.98); }

    .btn-back{
      background: linear-gradient(135deg, #21a1f3, #1976d2);
    }
    .btn-back:hover{
      background: linear-gradient(135deg, #289cf6, #1e88e5);
    }
    .btn-home{
      background: linear-gradient(135deg, #ff8c00, #ff5722);
    }
    .btn-home:hover{
      background: linear-gradient(135deg, #ff5722, #e64a19);
    }

    /* cinta de estado al lado del título */
    .badge{
      display:inline-block; margin-left:8px;
      padding:4px 10px; border-radius:999px; font-size:13px; font-weight:800;
      background: linear-gradient(135deg, $grad1, $grad2);
    }

    @media (prefers-reduced-motion: reduce){
      *{transition:none !important}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <h2>$tit <span class="badge">Estado</span></h2>
    <div class="content">$html</div>
    <div class="actions">
      <a class="btn btn-back" href="$volverCanje">⬅️ Volver a canje</a>
      <a class="btn btn-home" href="$irEvento">🏠 Ir al evento</a>
    </div>
  </div>
</body>
</html>
HTML;
  exit;
}

try{
  $id_evento       = (int)($_POST['id_evento']       ?? 0);
  $id_participante = (int)($_POST['id_participante'] ?? 0);
  $canje           = $_POST['canje'] ?? [];

  if ($id_evento<=0 || $id_participante<=0 || empty($canje)) {
    salir(false, "Datos inválidos (evento, participante o cantidades vacías).", $id_evento);
  }

  // 1) Participante (para RFC y validar evento)
  $stmt = $conn->prepare("SELECT ID, Nombre, RFC, ID_Evento FROM participante WHERE ID=?");
  $stmt->bind_param("i",$id_participante);
  $stmt->execute();
  $p = $stmt->get_result()->fetch_assoc();
  if (!$p)                           salir(false, "El participante no existe.", $id_evento);
  if ((int)$p['ID_Evento']!=$id_evento) salir(false, "El participante no pertenece a este evento.", $id_evento);
  if (empty($p['RFC']))              salir(false, "El participante no tiene RFC registrado.", $id_evento);
  $rfc = $p['RFC'];

  // 2) Normaliza pedidos
  $req=[]; foreach($canje as $idPremio=>$cant){
    $idPremio=(int)$idPremio; $cant=(int)$cant;
    if ($idPremio>0 && $cant>0) $req[$idPremio]=$cant;
  }
  if (!$req) salir(false,"Todas las cantidades son 0.", $id_evento);

  // 3) Cargar premios del evento
  $ids = array_keys($req);
  $ph  = implode(',', array_fill(0,count($ids),'?'));
  $types = str_repeat('i', count($ids)+1); // id_evento + N ids

  $q = "SELECT ID, NombrePremio, PuntosNecesarios, Disponible
        FROM premios_evento
        WHERE ID_Evento=? AND ID IN ($ph)";
  $stmt = $conn->prepare($q);
  $params = array_merge([$id_evento], $ids);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();

  $premios=[]; while($row=$res->fetch_assoc()){ $premios[(int)$row['ID']]=$row; }

  // 4) Costeo total
  $total=0; $detalle=[];
  foreach ($req as $idPremio=>$cant){
    if (!isset($premios[$idPremio]))                  salir(false,"Premio #$idPremio no pertenece al evento o no existe.", $id_evento);
    if ((int)$premios[$idPremio]['Disponible'] !== 1) salir(false,"El premio '{$premios[$idPremio]['NombrePremio']}' no está disponible.", $id_evento);
    $coste = $cant * (int)$premios[$idPremio]['PuntosNecesarios'];
    $total += $coste;
    $detalle[] = "{$cant} × ".htmlspecialchars($premios[$idPremio]['NombrePremio'])." ({$coste} pts)";
  }
  if ($total<=0) salir(false,"El total de puntos a usar es 0.", $id_evento);

  // 5) Transacción: bloquear wallet y descontar
  $conn->begin_transaction();
  try{
    // Asegura fila de wallet
    $ins0 = $conn->prepare("INSERT IGNORE INTO puntos_rfc (RFC, ID_Evento, Puntos) VALUES (?, ?, 0)");
    $ins0->bind_param("si", $rfc, $id_evento);
    $ins0->execute();

    // Bloquea saldo
    $lock = $conn->prepare("SELECT Puntos FROM puntos_rfc WHERE RFC=? AND ID_Evento=? FOR UPDATE");
    $lock->bind_param("si", $rfc, $id_evento);
    $lock->execute();
    $row = $lock->get_result()->fetch_assoc();
    $saldo = (int)($row['Puntos'] ?? 0);

    if ($total > $saldo) {
      $conn->rollback();
      salir(false, "Puntos insuficientes. Wallet RFC $rfc tiene {$saldo} pts y necesitas {$total} pts.", $id_evento);
    }

    // Inserta canjes
    $ins = $conn->prepare("INSERT INTO canjes (ID_Evento, ID_Participante, ID_Premio, Cantidad, Fecha)
                           VALUES (?,?,?,?, NOW())");
    foreach ($req as $idPremio=>$cant){
      $ins->bind_param("iiii", $id_evento, $id_participante, $idPremio, $cant);
      $ins->execute();
    }

    // Descuenta del wallet
    $upd = $conn->prepare("UPDATE puntos_rfc SET Puntos = Puntos - ? WHERE RFC=? AND ID_Evento=?");
    $upd->bind_param("isi", $total, $rfc, $id_evento);
    $upd->execute();

    $conn->commit();

  } catch(Throwable $e){
    $conn->rollback();
    error_log("Canje tx error: ".$e->getMessage());
    salir(false,"Error al registrar el canje. Intenta de nuevo.", $id_evento);
  }

  $restante = $saldo - $total;
  $lista = "<ul style='margin:8px 0 0 18px'>".implode('', array_map(fn($x)=>"<li>$x</li>", $detalle))."</ul>";
  salir(true, "Se canjearon:<br>$lista<br><br><strong>Usaste:</strong> $total pts<br><strong>Quedan en cartera (RFC $rfc):</strong> $restante pts", $id_evento);

} catch (Throwable $e){
  error_log("Canje fatal: ".$e->getMessage());
  salir(false, "Error inesperado. Intenta de nuevo.", (int)($_POST['id_evento']??0));
}
