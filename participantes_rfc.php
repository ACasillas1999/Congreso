<?php
/* participantes_por_rfc.php */
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || ($_SESSION["Rol"] ?? "") !== "Admin") {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    exit("ID de evento inválido.");
}

/* === Consulta global para contar === */
$stmt_count = $conn->prepare("
  SELECT 
      COUNT(DISTINCT p.RFC) AS total_rfc,
      COUNT(*) AS total_participantes
  FROM participante p
  WHERE p.ID_Evento = ?
    AND p.RFC IS NOT NULL
    AND p.RFC <> ''
");
$stmt_count->bind_param("i", $id);
$stmt_count->execute();
$stats = $stmt_count->get_result()->fetch_assoc() ?: ['total_rfc' => 0, 'total_participantes' => 0];
$total_rfc = (int)$stats['total_rfc'];
$total_participantes = (int)$stats['total_participantes'];
$stmt_count->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Participantes por RFC</title>
<link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
:root{
  --neon:#ff7f00; --bg1:#0d1c3b; --bg2:#1e2a78;
  --card:#141821; --text:#f4f6fb; --muted:rgba(255,255,255,.7);
}
body{
  margin:0; font-family:system-ui,Segoe UI,Roboto;
  color:var(--text);
  background: radial-gradient(1000px 600px at 50% -10%, var(--bg2) 0%, var(--bg1) 45%, #09142a 100%);
  padding:20px;
}
.wrap{ max-width:1100px; margin:0 auto; }
.btn-volver{
  display:inline-block; text-decoration:none;
  background:var(--neon); color:#111; font-weight:700;
  padding:10px 16px; border-radius:10px;
  box-shadow:0 0 10px rgba(255,127,0,.5);
  margin-bottom:18px;
}
.btn-volver:hover{ filter:brightness(1.05); }
h2.titulo{
  margin:0 0 14px 0; font-size:clamp(20px,3vw,28px);
  text-shadow:0 0 12px rgba(255,127,0,.22);
}
.resumen{
  display:flex; gap:20px; flex-wrap:wrap; align-items:center;
  background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.08);
  border-radius:12px; padding:12px 16px; margin-bottom:20px;
  font-size:1rem;
}
.resumen span{
  display:inline-flex; align-items:center; gap:6px;
  background:rgba(255,127,0,.1);
  border-radius:999px; padding:6px 14px;
  box-shadow:0 0 10px rgba(255,127,0,.25) inset;
}
.resumen .icon{ font-size:18px; color:var(--neon); }

.buscador{ margin: 14px 0 22px; }
#buscarRFC{
  width:100%; max-width:460px; font-size:16px;
  padding:12px 14px; border-radius:10px; border:none;
  background:#0e1627; color:var(--text);
  box-shadow: inset 0 0 0 1px rgba(255,255,255,.06);
}
.grupo{ background:linear-gradient(180deg,#141b2a,#0f1625);
  margin-bottom:14px; border-radius:14px; overflow:hidden;
  box-shadow:0 8px 28px rgba(0,0,0,.28),0 0 0 1px rgba(255,255,255,.04) inset;
}
.grupo h3{
  margin:0; padding:14px; display:flex; align-items:center; justify-content:space-between; gap:10px;
  background:linear-gradient(180deg,rgba(255,127,0,.09),rgba(255,127,0,.02));
  border-bottom:1px solid rgba(255,255,255,.06); cursor:pointer;
}
.h3-left{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.rfc-text{ color:var(--neon); font-weight:800; letter-spacing:.3px; }
.badge{
  display:inline-block; min-width:26px; padding:2px 9px;
  border-radius:999px; background:var(--neon); color:#111;
  font-weight:800; font-size:.9rem; text-align:center;
  box-shadow:0 0 12px rgba(255,127,0,.55);
}
.chip{ display:inline-block; padding:3px 11px; border-radius:999px; font-weight:700; font-size:.9rem; }
.chip.puntos{
  background:linear-gradient(90deg,#0dcaf0,#1e90ff);
  color:#00121f; box-shadow:0 0 12px rgba(13,202,240,.4);
}
.caret{ opacity:.9; transition: transform .2s ease; }
.grupo.open .caret{ transform:rotate(180deg); }
.participantes{ display:none; padding:12px 12px 16px; }
.grupo.open .participantes{ display:block; }
table{ width:100%; border-collapse:collapse; font-size:.95rem; background:rgba(0,0,0,.12); }
th,td{ padding:10px 12px; border-bottom:1px solid rgba(255,255,255,.06); text-align:left; }
th{ color:var(--muted); background:rgba(255,255,255,.03); }
tr:hover td{ background:rgba(255,255,255,.03); }
.empty{ padding:16px; color:var(--muted); font-style:italic; }
@media(max-width:680px){
 th:nth-child(3),td:nth-child(3),
 th:nth-child(4),td:nth-child(4){ display:none; }
}
</style>
</head>
<body>
<div class="wrap">

  <a class="btn-volver" href="Participantes.php?id=<?php echo $id; ?>">← Volver</a>
  <h2 class="titulo">Participantes por grupo (RFC)</h2>

  <!-- Nuevo bloque resumen -->
  <div class="resumen">
    <span><span class="icon">👥</span> Total participantes: <strong><?php echo $total_participantes; ?></strong></span>
    <span><span class="icon">🧾</span> Total RFCs: <strong><?php echo $total_rfc; ?></strong></span>
  </div>

  <div class="buscador">
    <input type="text" id="buscarRFC" placeholder="Buscar RFC...">
  </div>

<?php
/* Consulta por grupo RFC */
$stmt = $conn->prepare("
  SELECT 
      p.RFC,
      COUNT(*) AS total,
      COALESCE(MAX(pr.Puntos), 0) AS puntos
  FROM participante p
  LEFT JOIN puntos_rfc pr
         ON pr.RFC = p.RFC
        AND pr.ID_Evento = p.ID_Evento
  WHERE p.ID_Evento = ?
    AND p.RFC IS NOT NULL
    AND p.RFC <> ''
  GROUP BY p.RFC
  ORDER BY p.RFC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0){
    echo '<div class="empty">No hay participantes agrupados por RFC en este evento.</div>';
} else {
    while ($g = $res->fetch_assoc()){
        $rfc    = $g['RFC'];
        $total  = (int)$g['total'];
        $puntos = (int)$g['puntos'];

        $rfc_attr = htmlspecialchars($rfc, ENT_QUOTES, 'UTF-8');
        $rfc_show = htmlspecialchars($rfc, ENT_NOQUOTES, 'UTF-8');

        echo '<div class="grupo" data-rfc="'.$rfc_attr.'">';
        echo '  <h3 onclick="toggleGrupo(\''.$rfc_attr.'\', this)">';
        echo '    <span class="h3-left">';
        echo '      <span class="rfc-text">RFC: <strong>'.$rfc_show.'</strong></span>';
        echo '      <span class="badge" title="Participantes en este RFC">'.$total.'</span>';
        echo '      <span class="chip puntos" title="Puntos del grupo">⭐ '.$puntos.'</span>';
        echo '    </span>';
        echo '    <span class="caret">▼</span>';
        echo '  </h3>';

        $stmt2 = $conn->prepare("
            SELECT Nombre, Telefono, Vendedor, Sucursal
            FROM participante
            WHERE RFC = ? AND ID_Evento = ?
            ORDER BY Nombre
        ");
        $stmt2->bind_param("si", $rfc, $id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();

        echo '  <div class="participantes" id="grupo_'.$rfc_attr.'">';
        if ($res2->num_rows === 0){
            echo '<div class="empty">Sin participantes en este grupo.</div>';
        } else {
            echo '    <table>';
            echo '      <tr><th>Nombre</th><th>Teléfono</th><th>Vendedor</th><th>Sucursal</th></tr>';
            while ($row = $res2->fetch_assoc()){
                echo '      <tr>';
                echo '        <td>'.htmlspecialchars($row['Nombre'] ?? '', ENT_NOQUOTES, 'UTF-8').'</td>';
                echo '        <td>'.htmlspecialchars($row['Telefono'] ?? '', ENT_NOQUOTES, 'UTF-8').'</td>';
                echo '        <td>'.htmlspecialchars($row['Vendedor'] ?? '', ENT_NOQUOTES, 'UTF-8').'</td>';
                echo '        <td>'.htmlspecialchars($row['Sucursal'] ?? '', ENT_NOQUOTES, 'UTF-8').'</td>';
                echo '      </tr>';
            }
            echo '    </table>';
        }
        echo '  </div>';
        echo '</div>';
        $stmt2->close();
    }
}
$stmt->close();
$conn->close();
?>

</div>

<script>
const input = document.getElementById("buscarRFC");
input.addEventListener("input", function(){
  const val = this.value.trim().toLowerCase();
  const grupos = document.querySelectorAll(".grupo");
  grupos.forEach(g=>{
    const rfc = (g.getAttribute("data-rfc")||"").toLowerCase();
    const match = rfc.includes(val);
    g.style.display = match ? "block":"none";
    const panel = g.querySelector(".participantes");
    if(panel && val && rfc===val){ g.classList.add("open"); panel.style.display="block"; }
  });
});
function toggleGrupo(rfc, el){
  const cont = document.getElementById("grupo_"+rfc);
  const grupo = el? el.closest(".grupo"): null;
  if(!cont) return;
  const show = cont.style.display !== "block";
  cont.style.display = show ? "block" : "none";
  if(grupo){ grupo.classList.toggle("open", show); }
}
</script>
</body>
</html>
