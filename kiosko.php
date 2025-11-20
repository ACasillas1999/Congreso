<?php include "Conexiones/Conexion.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
      <link rel="icon" type="image/png" href="/congreso/educacion.png">

  <meta charset="UTF-8">
  <title>Kiosko de Puntos</title>
  <style>
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap');

/* ===== Escala global: 1vmin como unidad base ===== */
:root{
  --u: 1vmin;                 /* unidad de escala (min(viewport)/100) */
  --frame: calc(.4 * var(--u));
  --radius: calc(2 * var(--u));
  --glow: calc(1.2 * var(--u));
  --gap: calc(5 * var(--u));
  --padK: calc(5 * var(--u));
  --kioskoW: calc(80 * var(--u));   /* ancho target de la caja */
  --logoW: calc(38 * var(--u));     /* ancho target del logo */

  --t-word: calc(10 * var(--u));    /* “CONEXIÓN” */
  --t-year: calc(8.6 * var(--u));   /* “2025” */
  --t-h2:   calc(5 * var(--u));
  --t-name: calc(4.2 * var(--u));
  --t-pts:  calc(5.2 * var(--u));
  --t-err:  calc(2.2 * var(--u));
  --t-hist: calc(2.2 * var(--u));
}

/* ===== Lienzo base ===== */
html, body{
  margin:0; padding:0;
  height:100vh; height:100svh;      /* svh para móviles */
  width:100vw;
  font-family:'Segoe UI', sans-serif;
  background: radial-gradient(circle at center, #1a2c4e 0%, #0c1221 100%);
  display:grid;
  place-items:center;
  overflow:hidden;
  position:relative;
}

/* Marco naranja */
body::before{
  content:'';
  position:absolute; inset: calc(2 * var(--u));
  border: var(--frame) solid #ff7f00;
  border-radius: var(--radius);
  box-shadow: 0 0 var(--glow) #ff7f00;
  pointer-events:none;
  z-index:0;
}

/* ===== Stacking principal ===== */
.contenedor-principal{
  position:relative; z-index:2;
  display:grid;
  grid-auto-rows: max-content;
  row-gap: var(--gap);
  width:100%;
  height:100%;
  padding: calc(6 * var(--u)) 0;
  place-items:center;
  text-align:center;
}

/* ===== Título neón en 2 líneas ===== */
.neon-title{
  display:grid; row-gap: calc(1.4 * var(--u));
  margin:0;
  animation: neonPulse 4.8s ease-in-out infinite,
             neonFlicker 7.2s linear infinite;
  will-change: opacity, text-shadow;
}
.neon-word,
.neon-year{
  font-family:'Orbitron', sans-serif;
  color:#ff7f00;
  line-height:1;
  text-shadow:
    0 0 .3vmin #ff7f00,
    0 0 .8vmin #ff7f00,
    0 0 1.5vmin #cc6600;
}
.neon-word{ font-size: clamp(28px, var(--t-word), 14vmin); letter-spacing:.02em; }
.neon-year{ font-size: clamp(24px, var(--t-year), 12vmin); letter-spacing:.06em; }

/* ===== Logo ===== */
.logo{
  width: min(var(--logoW), 80vw);
  height:auto; display:block; margin:0;
  display: ruby;
}

/* ===== Caja/Kiosko ===== */
.kiosko{
    margin-top: 59vw;
  width: min(var(--kioskoW), 92vw);
  background: linear-gradient(to bottom, #111927, #0c101b);
  border: var(--frame) solid #ff7f00;
  border-radius: var(--radius);
  box-shadow: 0 0 calc(2 * var(--u)) rgba(255,127,0,.3),
              0 0 calc(3 * var(--u)) rgba(255,127,0,.1);
  padding: var(--padK);
}
.kiosko h2{
  font-size: clamp(18px, var(--t-h2), 8vmin);
  color:#ff7f00; margin:0;
}

/* ===== Datos dinámicos ===== */
.nombre{ font-size: clamp(16px, var(--t-name), 6.5vmin); color:#fff; margin-top: calc(2 * var(--u)); }
.puntos{ font-size: clamp(18px, var(--t-pts), 7.5vmin); font-weight:700; color:#00ffcc; margin-top: calc(1 * var(--u)); }
.error { font-size: clamp(14px, var(--t-err), 4.2vmin); font-weight:700; color:#ff4d4d; margin-top: calc(2 * var(--u)); }

/* ===== Historial ===== */
.historial{
  margin-top: calc(2 * var(--u));
  font-size: clamp(12px, var(--t-hist), 3.6vmin);
  color:#ccc; text-align:left;
  background: rgba(255,255,255,.05);
  padding: calc(2 * var(--u)) calc(2 * var(--u));
  border-radius: calc(1 * var(--u));
  max-height: 26vmin; overflow:auto;
}

/* Check ✔ */
#check-animacion{
  position:fixed; inset:auto;
  top:50%; left:50%; transform:translate(-50%, -50%);
  background:#4caf50; color:#fff;
  font-size: clamp(24px, calc(6 * var(--u)), 8vmin);
  width: clamp(70px, calc(12 * var(--u)), 16vmin);
  height: clamp(70px, calc(12 * var(--u)), 16vmin);
  border-radius:50%; display:none; place-items:center;
  z-index:999; animation: pop-check .6s ease;
}
@keyframes pop-check{
  0%{ transform:translate(-50%, -50%) scale(.5); opacity:0; }
  50%{ transform:translate(-50%, -50%) scale(1.1); opacity:1; }
  100%{ transform:translate(-50%, -50%) scale(1); opacity:1; }
}

/* ===== Canvas detrás del contenido ===== */
.particles-canvas{
  position:absolute; inset:0; width:100%; height:100%;
  pointer-events:none; z-index:1;
}
.contenedor-principal{ z-index:3; }
body::before{ z-index:4; }

@media (prefers-reduced-motion: reduce){
  .particles-canvas{ display:none !important; }
  .neon-title{ animation:none !important; }
}

/* ===== Animación neón ===== */
@keyframes neonPulse{
  0%,100%{
    text-shadow:
      0 0 .20vmin #ff7f00, 0 0 .60vmin #ff7f00,
      0 0 1.20vmin #cc6600, 0 0 2.00vmin rgba(255,127,0,.30);
    opacity:1;
  }
  50%{
    text-shadow:
      0 0 .12vmin #ff7f00, 0 0 .40vmin #ff7f00,
      0 0 .90vmin #cc6600, 0 0 1.60vmin rgba(255,127,0,.22);
    opacity:.98;
  }
}
@keyframes neonFlicker{
  0%,13%,16%,22%,80%,83%,100%{ opacity:1; }
  14%{ opacity:.86; } 15%{ opacity:.98; }
  23%{ opacity:.92; } 81%{ opacity:.88; } 82%{ opacity:1; }
}

/* ===== Layout EXACTO estilo póster: título | logo | gap | kiosko ===== */
.contenedor-principal{
  display:grid;
  grid-template-areas:
    "."
    "title"
    "."
    "logo"
    "."
    "kiosk"
    ".";
  /* top | title | gap1 | logo | gap2 | kiosko | bottom  */
  grid-template-rows:
    1fr       /* margen superior dentro del marco */
    auto
    0.8fr     /* separa título del logo */
    auto
    2.2fr     /* empuja el botón hacia abajo */
    auto
    1fr;      /* margen inferior dentro del marco */
  width:100%;
  height:100%;
  place-items:center;
  row-gap:0;
  padding:0;
}
.neon-title{ grid-area:title; }
.top-logo{ grid-area:logo; place-self:center; }
.kiosko{ grid-area:kiosk; place-self:center; }
.contenedor{ display:contents; } /* deja que los hijos entren al grid */

/* ===== Tamaños para calzar la proporción de la captura ===== */
.neon-word{ font-size: 14vw; letter-spacing:.02em; }
.neon-year{ font-size: 12vw;  letter-spacing:.06em; }

.logo{
  display:block;               /* corrige display:ruby */
  width: clamp(240px, 40vmin, 56vmin); /* como en la imagen */
  height:auto;
  margin:0;
}

.kiosko{
  margin:0;
  width: clamp(280px, 70vmin, 88vw);   /* ancho del “botón” */
  padding: clamp(18px, 3.4vmin, 28px); /* grosor del botón */
  margin-bottom: 28vw;
}
.kiosko h2{
  font-size: clamp(20px, 3.4vmin, 3.8vmin);
  margin:0;
}

/* Marco y brillo mantienen proporción en cualquier pantalla */
body::before{
  inset: 2vmin;
  border-width: .4vmin;
  border-radius: 2vmin;
  box-shadow: 0 0 1.2vmin #ff7f00;
}


</style>

</head>
<body>
<canvas id="particles" class="particles-canvas" aria-hidden="true"></canvas>


<div class="contenedor-principal">

 <div class="neon-title">
  <span class="neon-word">CONEXIÓN</span>
  <span class="neon-year">2025</span>
</div>



        <div class="contenedor">
    <div class="top-logo">
      <img src="img/Diseno_55-sf.png" alt="Grupo Ascencio" class="logo">
    </div>
    <div class="kiosko" id="kiosko">
    <h2>Escanea tu código</h2>
      <!-- <form method="POST" action="" id="formulario">
<textarea name="codigo" id="codigo" autocomplete="off" autofocus rows="1" style="opacity:0; position:absolute;"></textarea>
    </form>
-->
    <form method="POST" action="" id="formulario" accept-charset="UTF-8">
  <textarea name="codigo" id="codigo" autocomplete="off" autofocus rows="1" maxlength="200" style="opacity:0; position:absolute;"></textarea>
</form>

    <div id="resultado">
<?php
// Robusto: forzamos UTF-8 y capturamos errores de mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (isset($conn) && $conn instanceof mysqli) { $conn->set_charset('utf8mb4'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Estado para que el JS sepa qué pasó
  $scanState = ['status' => null];

  // Parseo tolerante: IDÑ 123 | ID 123 | ID-123 | ID:123
 function extraer_id_qr($cadena) {
  // Normaliza, quita control chars
  $s = mb_convert_encoding((string)$cadena, 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
  $s = preg_replace('/[\x00-\x1F\x7F]+/u', '', $s);

  // Caso general: "ID" + (cualquier no-dígito, incl. Ñ, :, -, espacios) + número
  if (preg_match('/ID\D{0,12}(\d{1,10})/ui', $s, $m)) {
    return (int)$m[1];
  }

  // Tolerancia extra (por si el escáner manda "ID:1153" o "ID-1153")
  if (preg_match('/ID\s*[:\-]?\s*(\d{1,10})/ui', $s, $m)) {
    return (int)$m[1];
  }

  // Último recurso: primer bloque de 3–10 dígitos
  if (preg_match('/(\d{3,10})/u', $s, $m)) {
    return (int)$m[1];
  }
  return 0;
}

  try {
    $codigo_raw = $_POST['codigo'] ?? '';
    $codigo_id  = extraer_id_qr($codigo_raw);

    if ($codigo_id <= 0) {
      $scanState['status'] = 'invalid';
      echo "<div class='error'>⚠️ Código inválido</div>";
    } else {
      // Un solo SELECT trae Nombre, Evento y RFC
      $stmt = $conn->prepare("SELECT Nombre, ID_Evento, RFC FROM participante WHERE ID = ?");
      $stmt->bind_param("i", $codigo_id);
      $stmt->execute();
      $res = $stmt->get_result();

      if ($res->num_rows === 0) {
        $scanState['status'] = 'not_found';
        echo "<div class='error'>❌ Participante no encontrado</div>";
      } else {
        $row       = $res->fetch_assoc();
        $nombre    = $row['Nombre'] ?? '';
        $id_evento = (int)($row['ID_Evento'] ?? 0);
        $rfc       = (string)($row['RFC'] ?? '');

        if ($id_evento === 0 || $rfc === '') {
          $scanState['status'] = 'no_rfc_event';
          echo "<div class='error'>❌ RFC o evento no encontrado para este participante.</div>";
        } else {
          // Puntos por RFC y evento
          $stmt2 = $conn->prepare("SELECT Puntos FROM puntos_rfc WHERE RFC = ? AND ID_Evento = ?");
          $stmt2->bind_param("si", $rfc, $id_evento);
          $stmt2->execute();
          $puntos = (int)($stmt2->get_result()->fetch_assoc()['Puntos'] ?? 0);

          // Éxito
          echo "<div class='nombre success'>👤 " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</div>";
          echo "<div class='puntos success'>⭐ " . $puntos . " puntos</div>";
          echo "<div class='nombre'>Grupo: " . htmlspecialchars($rfc, ENT_QUOTES, 'UTF-8') . "</div>";

          // Historial (últimos 5)
          $hist = $conn->prepare("SELECT puntos, usuario, fecha FROM puntos_proveedor WHERE id_participante = ? ORDER BY fecha DESC LIMIT 2");
          $hist->bind_param("i", $codigo_id);
          $hist->execute();
          $hist_res = $hist->get_result();
          if ($hist_res->num_rows > 0) {
            echo "<div class='historial'><strong>Últimos registros:</strong>";
            while ($h = $hist_res->fetch_assoc()) {
              $pts = (int)$h['puntos'];
              $usr = htmlspecialchars($h['usuario'], ENT_QUOTES, 'UTF-8');
              $fec = date('d/m/Y H:i', strtotime($h['fecha']));
              echo "<p>➕ {$pts} pts por <em>{$usr}</em> - {$fec}</p>";
            }
            echo "</div>";
          }

          $scanState['status'] = 'ok';
        }
      }
    }
  } catch (Throwable $e) {
    error_log('Kiosko error: ' . $e->getMessage());
    $scanState['status'] = 'server_error';
    echo "<div class='error'>⚠️ Ocurrió un error. Intenta de nuevo.</div>";
  }

  // Expone estado para el JS (animaciones y auto-reset)
  echo "<script>window.__scanState = " . json_encode($scanState, JSON_UNESCAPED_UNICODE) . ";</script>";
}
?>
</div>

  </div>
<script>
(() => {
  const canvas = document.getElementById('particles');
  if (!canvas) return;
  const ctx = canvas.getContext('2d', { alpha: true });

  let W, H, dpr = 1;
  let nodes = [];
  let grid = Object.create(null);
  let beams = [];
  let meteors = [];   // ⭐ NUEVO
  let lastTs = 0;
  let running = true;

  // ====== Parámetros (ajustables) — preset kiosko rápido y enlaces marcados ======
  const MAX_NODES_CAP  = 150;
  const DENSITY_DIV    = 110000;        // cantidad de nodos (igual)
  const LINK_DIST      = 130;           // un poco más de rango de enlace
  const LINK_DIST2     = LINK_DIST * LINK_DIST;
  const NODE_R_MINMAX  = [3.2, 6.5];    // puntos más grandes (opcional)
  const VEL_MINMAX     = [0.06, 0.16];  // más velocidad que antes
  const FPS            = 40;
  const ORANGE         = '255,127,0';
  const MARGIN         = 36;
  const CENTER_PULL    = 0;             // sin atracción al centro
  const LINE_WIDTH     = 1.6;           // enlaces más gruesos

  // (Opcional) base para el alpha del enlace
  const BASE_ALPHA     = 0.24;

  // “Wander” (si lo activas más adelante)
  const WANDER_STRENGTH = 0.002;
  const WANDER_FREQ_MIN = 0.04;
  const WANDER_FREQ_MAX = 0.10;

  const BEAM_RATE_MS    = 1100;         // más chispas
  const BEAM_SPEED      = 0.009;        // chispas un poco más rápidas
  const BEAM_PER_BURST  = 6;

  // ===== Estrellas fugaces (meteors) =====
  const METEOR_CAP        = 4;      // máx estrellas activas
  const METEOR_RATE_MS    = 4200;   // cada cuánto intentar crear una
  const METEOR_SPEED_MIN  = 6;      // px/frame a ~60fps
  const METEOR_SPEED_MAX  = 12;
  const METEOR_TRAIL_LEN  = 24;     // longitud de estela (segmentos)
  const METEOR_HEAD_W     = 3.8;    // grosor cabeza
  const METEOR_TAIL_W     = 0.8;    // grosor cola
  const METEOR_GLOW       = 16;     // halo
  const METEOR_ALPHA_HEAD = 0.90;   // opacidad cabeza
  const METEOR_ALPHA_TAIL = 0.12;   // opacidad cola
  const METEOR_CURVE_G    = 0.03;   // curvatura (aceleración vertical)

  function choose(min, max){ return Math.random()*(max-min)+min; }

  function nodeCount(){
    return Math.min(MAX_NODES_CAP, Math.round((innerWidth*innerHeight)/DENSITY_DIV));
  }

  function resize(){
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = canvas.clientWidth = innerWidth;
    H = canvas.clientHeight = innerHeight;
    canvas.width  = Math.floor(W * dpr);
    canvas.height = Math.floor(H * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    spawnNodes();
  }

  function spawnNodes(){
    const N = nodeCount();
    nodes = Array.from({length:N}, () => ({
      x: choose(MARGIN, W-MARGIN),
      y: choose(MARGIN, H-MARGIN),
      vx: (Math.random()<.5?-1:1) * choose(VEL_MINMAX[0], VEL_MINMAX[1]),
      vy: (Math.random()<.5?-1:1) * choose(VEL_MINMAX[0], VEL_MINMAX[1]),
      r:  choose(NODE_R_MINMAX[0], NODE_R_MINMAX[1]),
      a:  choose(0.18, 0.35) // alpha punto
    }));
    beams = [];
    meteors = []; // limpia estrellas al redimensionar
  }

  // Grid uniforme para reducir O(N^2)
  const cell = LINK_DIST;
  function rebuildGrid(){
    grid = Object.create(null);
    for (let i=0;i<nodes.length;i++){
      const n = nodes[i];
      const gx = (n.x / cell) | 0;
      const gy = (n.y / cell) | 0;
      const key = gx + ',' + gy;
      (grid[key] || (grid[key]=[])).push(i);
    }
  }
  function neighborsIdx(ix){
    const res = [];
    const n = nodes[ix];
    const gx = (n.x / cell) | 0;
    const gy = (n.y / cell) | 0;
    for (let yy = gy-1; yy <= gy+1; yy++){
      for (let xx = gx-1; xx <= gx+1; xx++){
        const arr = grid[xx+','+yy];
        if (arr) res.push(arr);
      }
    }
    return res;
  }

  function step(dt){
    const cx = W*0.5, cy = H*0.5;

    for (const n of nodes){
      n.x += n.vx * dt;
      n.y += n.vy * dt;

      // Rebotar en bordes con margen
      if (n.x < MARGIN){ n.x = MARGIN; n.vx *= -1; }
      else if (n.x > W-MARGIN){ n.x = W-MARGIN; n.vx *= -1; }
      if (n.y < MARGIN){ n.y = MARGIN; n.vy *= -1; }
      else if (n.y > H-MARGIN){ n.y = H-MARGIN; n.vy *= -1; }

      // (opcional) ligera atracción al centro
      n.vx += (cx - n.x) * CENTER_PULL * dt;
      n.vy += (cy - n.y) * CENTER_PULL * dt;

      // Fricción suave
      n.vx *= (1 - 0.0005*dt);
      n.vy *= (1 - 0.0005*dt);
    }
  }

  // ===== Estrellas fugaces =====
  function spawnMeteor(){
    const side = (Math.random()*4)|0;
    let x, y, vx, vy;

    const speed = Math.random()*(METEOR_SPEED_MAX - METEOR_SPEED_MIN) + METEOR_SPEED_MIN;
    const slope = (Math.random()*0.6 - 0.3); // inclinación leve

    if (side === 0){ // izquierda → derecha
      x = -MARGIN; y = Math.random()*(H - 2*MARGIN) + MARGIN;
      vx = speed;   vy = speed * slope;
    } else if (side === 1){ // derecha → izquierda
      x = W + MARGIN; y = Math.random()*(H - 2*MARGIN) + MARGIN;
      vx = -speed;   vy = speed * slope;
    } else if (side === 2){ // arriba → abajo
      x = Math.random()*(W - 2*MARGIN) + MARGIN; y = -MARGIN;
      vx = speed * slope; vy = speed;
    } else { // abajo → arriba
      x = Math.random()*(W - 2*MARGIN) + MARGIN; y = H + MARGIN;
      vx = speed * slope; vy = -speed;
    }

    meteors.push({
      x, y,
      vx, vy,
      ax: 0,                 // curva en X si quieres (p.ej. 0.01)
      ay: METEOR_CURVE_G,    // “gravedad” para curvar suavemente
      trail: []
    });
    if (meteors.length > METEOR_CAP) meteors.shift();
  }

  function drawMeteors(dt){
    ctx.lineCap   = 'round';
    ctx.lineJoin  = 'round';

    for (let i = meteors.length - 1; i >= 0; i--){
      const m = meteors[i];

      // Actualiza velocidad/posición con curvatura
      m.vx += m.ax * dt;
      m.vy += m.ay * dt;
      m.x  += m.vx * dt;
      m.y  += m.vy * dt;

      // Guarda posición al inicio (cabeza)
      m.trail.unshift({x:m.x, y:m.y});
      if (m.trail.length > METEOR_TRAIL_LEN) m.trail.pop();

      // Si salió de pantalla del todo, se irá cuando su estela se acabe
      if (m.x < -MARGIN*2 || m.x > W + MARGIN*2 || m.y < -MARGIN*2 || m.y > H + MARGIN*2){
        if (m.trail.length <= 1){ meteors.splice(i,1); continue; }
      }

      // Cola (segmentos con grosor/alpha decreciente)
      for (let k = 0; k < m.trail.length - 1; k++){
        const pA = m.trail[k];
        const pB = m.trail[k+1];
        const t  = k / (m.trail.length - 1);                 // 0 cabeza → 1 cola
        const a  = METEOR_ALPHA_HEAD*(1 - t) + METEOR_ALPHA_TAIL*t;
        const w  = METEOR_HEAD_W*(1 - t) + METEOR_TAIL_W*t;

        ctx.strokeStyle = `rgba(${ORANGE},${a})`;
        ctx.lineWidth   = w;
        ctx.shadowColor = `rgba(${ORANGE},${a})`;
        ctx.shadowBlur  = METEOR_GLOW * (1 - t);

        ctx.beginPath();
        ctx.moveTo(pA.x, pA.y);
        ctx.lineTo(pB.x, pB.y);
        ctx.stroke();
      }

      // Cabeza brillante
      if (m.trail.length){
        const head = m.trail[0];
        ctx.beginPath();
        ctx.arc(head.x, head.y, METEOR_HEAD_W + 0.8, 0, Math.PI*2);
        ctx.fillStyle  = `rgba(${ORANGE},${METEOR_ALPHA_HEAD})`;
        ctx.shadowColor= `rgba(${ORANGE},${METEOR_ALPHA_HEAD})`;
        ctx.shadowBlur = METEOR_GLOW * 1.2;
        ctx.fill();
      }
      ctx.shadowBlur = 0;
    }
  }

  // ===== Dibujo principal =====
  function draw(ts){
    if (!running) return;
    requestAnimationFrame(draw);

    if (lastTs && ts - lastTs < 1000/FPS) return;
    const dt = Math.min(60, (ts - (lastTs||ts)) / (1000/60)); // normaliza ~60fps
    lastTs = ts;

    ctx.clearRect(0,0,W,H);

    // Estrellas fugaces (debajo de partículas y enlaces)
    ctx.save();
    ctx.globalCompositeOperation = 'lighter';
    drawMeteors(dt);
    ctx.restore();

    // Puntos
    for (const n of nodes){
      ctx.beginPath();
      ctx.arc(n.x, n.y, n.r, 0, Math.PI*2);
      ctx.fillStyle = `rgba(${ORANGE},${n.a})`;
      ctx.shadowColor = `rgba(${ORANGE},${n.a*0.8})`;
      ctx.shadowBlur = 6;
      ctx.fill();
      ctx.shadowBlur = 0;
    }

    // Enlaces (usando grid)
    rebuildGrid();
    ctx.lineWidth = LINE_WIDTH;
    for (let i=0;i<nodes.length;i++){
      const a = nodes[i];
      const neighBuckets = neighborsIdx(i);
      for (const bucket of neighBuckets){
        for (const j of bucket){
          if (j <= i) continue;
          const b = nodes[j];
          const dx = a.x - b.x;
          const dy = a.y - b.y;
          const d2 = dx*dx + dy*dy;
          if (d2 < LINK_DIST2){
            const alpha = 0.14 * (1 - d2 / LINK_DIST2);
            ctx.strokeStyle = `rgba(${ORANGE},${alpha})`;
            ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();

            // chispas sobre enlaces
            if (Math.random() < 0.0008){
              beams.push({
                ax:a.x, ay:a.y, bx:b.x, by:b.y,
                t:0, v: BEAM_SPEED * choose(0.8, 1.4), life: 1
              });
            }
          }
        }
      }
    }

    // Chispas que viajan por los enlaces
    for (let k=beams.length-1;k>=0;k--){
      const bm = beams[k];
      bm.t += bm.v * dt;
      bm.life -= 0.005 * dt;
      if (bm.t >= 1 || bm.life <= 0){
        beams.splice(k,1);
        continue;
      }
      const x = bm.ax + (bm.bx - bm.ax) * bm.t;
      const y = bm.ay + (bm.by - bm.ay) * bm.t;

      ctx.beginPath();
      ctx.arc(x, y, 2.6, 0, Math.PI*2);
      const a = Math.max(0, Math.min(1, 0.6 * bm.life));
      ctx.fillStyle = `rgba(${ORANGE},${a})`;
      ctx.shadowColor = `rgba(${ORANGE},${a})`;
      ctx.shadowBlur = 14;
      ctx.fill();
      ctx.shadowBlur = 0;
    }

    // Física al final
    step(dt);
  }

  // “Chisporroteo” automático cada cierto tiempo
  let lastBeam = 0;
  setInterval(() => {
    const now = performance.now();
    if (now - lastBeam < BEAM_RATE_MS) return;
    lastBeam = now;
    // elige 1-3 pares cercanos al azar y crea beams
    for (let n=0;n<3;n++){
      const i = (Math.random()*nodes.length)|0;
      const a = nodes[i];
      // busca vecino más cercano rápido
      let best=null, bestD2 = LINK_DIST2;
      const neighBuckets = neighborsIdx(i);
      for (const bucket of neighBuckets){
        for (const j of bucket){
          if (j===i) continue;
          const b = nodes[j];
          const dx=a.x-b.x, dy=a.y-b.y, d2=dx*dx+dy*dy;
          if (d2 < bestD2){ bestD2 = d2; best = b; }
        }
      }
      if (best){
        beams.push({ ax:a.x, ay:a.y, bx:best.x, by:best.y, t:0, v:BEAM_SPEED, life:1 });
      }
    }
  }, BEAM_RATE_MS);

  // Spawner de estrellas fugaces
  setInterval(() => {
    if (meteors.length < METEOR_CAP) spawnMeteor();
  }, METEOR_RATE_MS);

  // Pausa si pestaña no visible
  document.addEventListener('visibilitychange', () => {
    running = !document.hidden;
    if (running) requestAnimationFrame(draw);
  });

  // Respeta motion reducido
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    canvas.style.display = 'none';
    return;
  }

  window.addEventListener('resize', resize);
  resize();
  requestAnimationFrame(draw);
})();
</script>

<script>
(() => {
  const input     = document.getElementById('codigo');
  const form      = document.getElementById('formulario');
  const resultado = document.getElementById('resultado');
  const check     = document.getElementById('check-animacion');

  // REGEX más tolerante: "ID" + separadores no dígito (Ñ, :, -, espacios...) + número
  const SCAN_REGEX   = /ID\D{0,12}(\d{1,10})/i;

  // Tiempos
  const GAP_MS       = 80;   // silencio que marca fin del escaneo
  const COOLDOWN_MS  = 900;  // evita doble envío
  const RESET_MS     = 6000;

  let processing = false;
  let lastSent   = '';   // evita reenvío del mismo buffer
  let gapTimer   = null;

  // Siempre enfocado (lectores HID tipo teclado)
  const keepFocus = () => { if (document.activeElement !== input) input.focus(); };
  setInterval(keepFocus, 300);

  // Limpia control chars que a veces envía el lector
  const sanitize = s => (s || '').replace(/[\u0000-\u001F\u007F]+/g, '');

  const startCooldown = () => {
    processing = true;
    setTimeout(() => { processing = false; }, COOLDOWN_MS);
  };

  function tryProcess(raw){
    if (processing) return;
    const t = sanitize(raw);
    if (!/ID/i.test(t)) return;                        // ignora ruido si no hay "ID"
    if (t === lastSent) return;

    // Opcional: validar preliminar (no obligatorio; PHP hará la definitiva)
    const m = t.match(SCAN_REGEX);
    if (!m) return;                                    // aún no termina de llegar

    lastSent = t;
    startCooldown();
    form.submit();
  }

  // 1) Si viene Enter al final, procesa de inmediato
  input.addEventListener('keydown', e => {
    if (processing) return;
    if (e.key === 'Enter') {
      e.preventDefault();
      tryProcess(input.value);
    }
  });

  // 2) En cada input, espera un pequeño silencio (fin de ráfaga)
  input.addEventListener('input', () => {
    if (processing) return;
    clearTimeout(gapTimer);
    gapTimer = setTimeout(() => tryProcess(input.value), GAP_MS);
  });

  // 3) Pegar también cuenta
  input.addEventListener('paste', e => {
    const t = (e.clipboardData || window.clipboardData).getData('text') || '';
    clearTimeout(gapTimer);
    gapTimer = setTimeout(() => tryProcess(t), GAP_MS);
  });

  // Evita el diálogo de reenvío al volver atrás/recargar
  if (history.replaceState) history.replaceState(null, '', location.pathname + location.search);

  // Post-resultado desde PHP
  (function postScanUI(){
    const st = window.__scanState || {};
    if (!st.status) return;

    if (st.status === 'ok') {
      check.style.display = 'flex';
      setTimeout(() => { check.style.display = 'none'; }, 1200);
    }
    setTimeout(() => {
      resultado.innerHTML = '';
      input.value = '';
      lastSent = '';
      processing = false;
      keepFocus();
    }, RESET_MS);
  })();
})();
</script>



  <div id="check-animacion">✔</div>

</body>
</html>
