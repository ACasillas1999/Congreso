<?php
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || strtolower($_SESSION["Rol"]) !== "proveedor") {
    header("Location: ../Sesion/login.html");
    exit;
}

$usuario = $_SESSION['username'];

require_once __DIR__ . "/../Conexiones/Conexion.php";

// Obtener cuántos puntos da este proveedor y en qué evento
$puntos = "N/A";
$evento = "Sin asignar";

$sql = "SELECT pe.Puntos, e.name_evento 
        FROM proveedor_evento pe
        JOIN evento e ON e.ID = pe.ID_Evento
        WHERE pe.NombreProveedor = ? AND pe.Activo = 1
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $puntos = $row['Puntos'];
    $evento = $row['name_evento'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Escaneo QR - <?= htmlspecialchars($usuario) ?></title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include "../header_css.php"; ?>
    <style>
       /* ====== Base / Fondo ====== */
:root{
  --bg1:var(--theme-primary-dark, #0b1535); --bg2:var(--theme-primary, #142455);
  --card:var(--theme-surface-soft, rgba(255,255,255,.06));
  --field:var(--theme-surface, #233055); --field2:var(--theme-surface-strong, #2a3a6a);
  --ok:var(--theme-title, #22c55e); --warn:var(--naranja, #f59e0b); --info:var(--theme-accent, #21a1f3);
  --brand1:var(--naranja, #ff8c00); --brand2:var(--theme-accent, #ff5722);
  --text:var(--theme-text, #fff);
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  padding: max(16px, env(safe-area-inset-top)) max(14px, env(safe-area-inset-right))
           max(16px, env(safe-area-inset-bottom)) max(14px, env(safe-area-inset-left));
  min-height:100dvh;
  font-family:'Segoe UI', system-ui, Arial, sans-serif;
  color:var(--text);
  background: radial-gradient(circle at 20% 0%, var(--bg2) 0%, var(--bg1) 70%);
  display:flex; flex-direction:column; align-items:center;
  gap: 16px;
  overflow-x:hidden;
}

/* ====== Encabezado ====== */
h2{
  margin: 6px 0 2px;
  font-size: clamp(20px, 5vw, 30px);
  text-align:center;
  text-shadow:0 0 8px rgba(255,255,255,.25);
}

/* ====== Tarjeta de info proveedor/evento ====== */
.info{
  margin-top: 6px;
  background: var(--card);
  color:#d1fae5;
  padding: 12px 16px;
  font-size: clamp(15px, 3.8vw, 17px);
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0,0,0,.35);
  max-width: min(92vw, 620px);
  text-align:center;
  border-left: 6px solid var(--ok);
}

/* ====== Texto guía ====== */
p{
  margin: 8px 0 0;
  font-size: clamp(16px, 4vw, 18px);
  color:#e5e7eb;
  text-align:center;
}

/* ====== Lector (html5-qrcode) ====== */
#reader{
  width: clamp(280px, 80vw, 460px);
  aspect-ratio: 1 / 1;           /* cuadrado flexible */
  margin: 16px auto 6px;
  border-radius: 16px;
  overflow: hidden;
  background:#000;
  padding: 8px;
  box-shadow:
    0 0 0 6px #0d6efd33,
    0 14px 32px rgba(0,0,0,.55);
  position: relative;
}
#reader > div{ width:100%!important; height:100%!important; }
#reader *{ border-radius: 12px !important; }

/* ====== Resultado / Mensajes ====== */
.resultado{
  margin-top: 10px;
  font-size: clamp(15px, 3.8vw, 17px);
  background: var(--card);
  color:#e5e7eb;
  padding: 12px 16px;
  border-radius: 12px;
  border-left: 6px solid var(--info);
  max-width: min(92vw, 620px);
  box-shadow: 0 8px 20px rgba(0,0,0,.35);
  animation: fadeIn .4s ease;
}
@keyframes fadeIn{ from{opacity:0; transform:translateY(6px)} to{opacity:1; transform:none} }

/* ====== Botones cámara ====== */
#btnStart, #btnSwitch{
  appearance:none; border:none; cursor:pointer; user-select:none;
  padding: 12px 16px; margin: 0 6px;
  font-weight:700; font-size:16px; color:#fff;
  border-radius: 12px;
  box-shadow: 0 8px 18px rgba(0,0,0,.35), inset 0 -2px 0 rgba(255,255,255,.12);
  transition: transform .15s ease, background .25s ease, filter .2s ease;
}
#btnStart{  background: linear-gradient(135deg, var(--brand1), var(--brand2)); }
#btnStart:hover{ background: linear-gradient(135deg, var(--brand2), #e64a19); transform: translateY(-1px) scale(1.01); }
#btnStart:active{ transform: translateY(0) scale(.98); }

#btnSwitch{ background: linear-gradient(135deg, #21a1f3, #1976d2); }
#btnSwitch:hover{ background: linear-gradient(135deg, #289cf6, #1e88e5); transform: translateY(-1px) scale(1.01); }
#btnSwitch:active{ transform: translateY(0) scale(.98); }
#btnSwitch:disabled{
  opacity:.45; filter:grayscale(.3);
  cursor:not-allowed; transform:none !important;
}

/* ====== Logout ====== */
.logout-btn{
  position: fixed; top: max(16px, env(safe-area-inset-top)); right: max(16px, env(safe-area-inset-right));
  background: linear-gradient(135deg, #ef4444, #b91c1c);
  color:#fff; padding:10px 14px; text-decoration:none; border-radius:10px;
  font-weight:700; z-index:1000;
  box-shadow: 0 10px 22px rgba(0,0,0,.45), inset 0 -2px 0 rgba(255,255,255,.12);
  transition: transform .15s ease, background .25s ease;
}
.logout-btn:hover{ background: linear-gradient(135deg, #f87171, #dc2626); transform: translateY(-1px) }

/* ====== Responsive ====== */
@media (max-width: 600px){
  #reader{ padding:6px; border-radius:14px }
}
@media (prefers-reduced-motion: reduce){
  *{ transition:none !important; animation-duration:.001ms !important; animation-iteration-count:1 !important; }
}
/* ====== Controles responsivos (incluye logout) ====== */
.controls{
  width: min(92vw, 620px);
  margin: 10px auto 16px;
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}

/* Estilo base para TODOS los botones (button y <a>) */
.controls .btn{
  appearance: none;
  border: none;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;

  flex: 1 1 260px;          /* mínimo 260px; crecen parejo */
  max-width: 320px;

  padding: 12px 16px;
  min-height: 46px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 16px;
  color: #fff;
  cursor: pointer;

  box-shadow: 0 8px 18px rgba(0,0,0,.35), inset 0 -2px 0 rgba(255,255,255,.12);
  transition: transform .15s ease, background .25s ease, filter .2s ease, opacity .2s ease;
}

.controls .btn:hover{ transform: translateY(-1px) scale(1.01); }
.controls .btn:active{ transform: translateY(0) scale(.98); }
.controls .btn:focus-visible{
  outline: 2px solid rgba(33,150,243,.6);
  outline-offset: 3px;
  border-radius: 14px;
}

/* Variantes de color (coherentes con el resto del sistema) */
.btn-start  { background: linear-gradient(135deg, #ff8c00, #ff5722); }
.btn-start:hover  { background: linear-gradient(135deg, #ff5722, #e64a19); }

.btn-switch { background: linear-gradient(135deg, #21a1f3, #1976d2); }
.btn-switch:hover { background: linear-gradient(135deg, #289cf6, #1e88e5); }

/* Logout en rojo */
.btn-logout { background: linear-gradient(135deg, #ef4444, #b91c1c); }
.btn-logout:hover { background: linear-gradient(135deg, #f87171, #dc2626); }

/* Estado disabled (para Cambiar cámara cuando no haya segunda cámara) */
.controls .btn[disabled]{
  opacity: .45;
  filter: grayscale(.3);
  cursor: not-allowed;
  transform: none !important;
}

/* Pila vertical en pantallas pequeñas */
@media (max-width: 560px){
  .controls{ flex-direction: column; gap: 10px; }
  .controls .btn{ flex: 1 1 auto; max-width: none; width: 100%; }
}

.info{
  color: var(--theme-title, #d1fae5);
}

p,
.resultado{
  color: var(--theme-text-soft, #e5e7eb);
}

#btnSwitch,
.btn-switch{
  background: linear-gradient(135deg, var(--theme-primary, #21a1f3), var(--theme-primary-dark, #1976d2));
}

#btnSwitch:hover,
.btn-switch:hover{
  background: linear-gradient(135deg, var(--theme-accent, #289cf6), var(--theme-primary, #1e88e5));
}

    </style>
</head>
<body>
      
    <h2>Proveedor: <?= htmlspecialchars($usuario) ?></h2>
    <div class="info">Estás dando <strong><?= $puntos ?></strong> puntos por escaneo en el evento <strong><?= htmlspecialchars($evento) ?></strong>.</div>

    <p>Escanea el QR del participante</p>
<div id="reader"></div>
<div class="resultado" id="resultado">Pulsa “Abrir cámara”…</div>

<div class="controls">
  <button id="btnStart" class="btn btn-start">📷 Abrir cámara</button>
  <button id="btnSwitch" class="btn btn-switch" disabled>🔄 Cambiar cámara</button>
  <a href="../logout.php" class="btn btn-logout">🚪 Cerrar sesión</a>
</div>

<script>
  const html5QrCode = new Html5Qrcode("reader");
  let cams = [];
  let currentCamId = null;
  let scanning = false;

  const $res = s => document.getElementById("resultado").innerText = s;

  async function listarCamaras() {
    cams = await Html5Qrcode.getCameras();
    if (!cams.length) throw new Error("No se detectó cámara.");
    // Preferir trasera
    const back = cams.find(c => /back|rear|environment/i.test(c.label));
    currentCamId = (back || cams[0]).id;
    document.getElementById("btnSwitch").disabled = cams.length < 2;
  }

  async function startScan() {
    if (scanning) return;
    scanning = true;
    try {
      if (!cams.length) await listarCamaras();

      await html5QrCode.start(
        { deviceId: { exact: currentCamId } },      // más compatible que facingMode
        { fps: 10, qrbox: { width: 250, height: 250 } },
        onScanSuccess,
        onScanError
      );
      $res("Apunta al código…");
    } catch (e) {
      $res(msgDeError(e));
      scanning = false;
    }
  }

  // Pausa al leer, procesa y reanuda
  async function onScanSuccess(text) {
    try {
      await html5QrCode.pause(true);
    } catch(_) {}
    $res("Procesando…");

    try {
      const body = "codigo=" + encodeURIComponent(text);
      const r = await fetch("asignar_puntos.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body
      });
      $res(await r.text());
    } catch (e) {
      $res("Error enviando el código.");
      console.error(e);
    } finally {
      setTimeout(async () => {
        try { await html5QrCode.resume(); $res("Apunta al código…"); } catch(_) {}
      }, 1500);
    }
  }

  function onScanError(err) {
    // errores de enfoque/decodificación: los ignoramos para no “spamear”
  }

  async function switchCam() {
    if (cams.length < 2) return;
    try {
      const idx = cams.findIndex(c => c.id === currentCamId);
      currentCamId = cams[(idx + 1) % cams.length].id;
      await html5QrCode.stop();     // parar limpio
      await html5QrCode.clear();
      scanning = false;
      startScan();
    } catch (e) {
      $res("No se pudo cambiar de cámara.");
      console.error(e);
    }
  }

  function msgDeError(e) {
    const s = String(e || "");
    if (location.protocol !== "https:" && !location.hostname.match(/^(localhost|127\.0\.0\.1)$/))
      return "Necesitas abrir esta página en HTTPS para usar la cámara.";
    if (s.includes("NotAllowedError")) return "Permiso de cámara denegado. Revisa los permisos del navegador.";
    if (s.includes("NotFoundError"))  return "No se encontró cámara.";
    return "No se pudo abrir la cámara.";
  }

  // 🚩 Importante: iniciar por gesto del usuario
  document.getElementById("btnStart").addEventListener("click", startScan);
  document.getElementById("btnSwitch").addEventListener("click", switchCam);
</script>

</body>
</html>
