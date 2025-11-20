<?php

// Iniciar la sesión de forma segura

ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {

  // Si no está logeado, redirigir al formulario de inicio de sesión
  header("location: /Congreso/Sesion/login.html");
  exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clase</title>
  <link rel="stylesheet" type="text/css" href="styles_clase.css">
  <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
  <script src="https://unpkg.com/@zxing/browser@latest"></script>

</head>

<body class="fade-in has-topbar">

<header class="topbar">
  <button class="topbar__back" type="button" onclick="history.back()" aria-label="Volver">←</button>
  <div class="topbar__title titulo"><h2>Participantes</h2></div>
  <div class="topbar__actions"><!-- (opcional) botones extra --></div>
</header>


  <div id = "sidebar" class="sidebar">
    <ul>
      <!-- <li><a href="Agregar_Participante.php">Agregar participante</a></li>-->
      <li class="corner-left-bottom">
        <a href="javascript:window.history.back();">Volver</a>
      </li>
    </ul>
  </div>

  <div class="container">
    <!--<h2 class="titulo">Participantes</h2>-->

    <!-- Campo de búsqueda -->
    <input style='Display:none;' type="text" id="busqueda" placeholder="Buscar por nombre, sucursal, vendedor, etc." disabled>

    <p></p>


    <h2 class="titulo">Escaneo / Control</h2>

    <div style="margin:10px 0; color:#e0e0e0">
      <label><input type="radio" name="modo" value="asistencia" checked> Tomar asistencia</label>
      <label style="margin-left:16px"><input type="radio" name="modo" value="agregar"> Agregar a la clase</label>
    </div>

    <!-- === QR Scanner UI (solo se muestra en móvil/tablet por CSS) === -->
    <div id="qrWrap" aria-hidden="true">
      <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:10px;">
        <strong style="color:#e0e0e0">Lector QR (cámara)</strong>
        <select id="cameraSelect" style="padding:8px;border-radius:8px;border:1px solid #444;background:#0f1838;color:#e0e0e0"></select>
        <button id="btnStartQR" type="button" class="button" style="padding:10px 14px">Iniciar cámara</button>
        <button id="btnStopQR" type="button" class="button" style="padding:10px 14px;background:#666" disabled>Detener</button>
        <button id="btnTorch" type="button" class="button" style="padding:10px 14px;background:#444" disabled>Linterna</button>
        <!--<button id="btnFlip" type="button" class="button" style="padding:10px 14px;background:#444">Cambiar cámara</button>
        <button id="btnFlip" type="button" class="button" style="padding:10px 14px;background:#444">Cambiar cámara</button>-->

      </div>

      <div style="position:relative; max-width:520px; border-radius:12px; overflow:hidden; box-shadow:0 8px 24px rgba(0,0,0,.35)">
        <video id="qrVideo" playsinline style="width:100%; height:auto; background:#000"></video>
        <div style="position:absolute; inset:0; pointer-events:none; box-shadow:0 0 0 9999px rgba(0,0,0,.35); border:2px solid rgba(255,255,255,.6); border-radius:14px; margin:6%"></div>
      </div>

      <div id="qrMsg" style="margin-top:10px;color:#9fd3ff;font-size:14px"></div>
    </div>


    <form id="scanForm">
      <input type="text" id="idParticipante" placeholder="Escanea el QR aquí" required>
      <input type="hidden" id="idClase" value="<?php echo $id; ?>">
      <button class="button" type="submit">Procesar</button>
    </form>
    <div id="resultado" class="table-responsive">
      <!-- Aquí se mostrarán los resultados de la búsqueda -->
    </div>

    <!-- Formulario para agregar participante -->



  </div>
<script src="https://unpkg.com/@zxing/browser@latest"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const busquedaInput = document.getElementById("busqueda");
  const idParticipanteInput = document.getElementById("idParticipante");
  const scanForm = document.getElementById("scanForm");
  const idClase = document.getElementById("idClase").value;

  /* =========================
     LISTADO / INICIO
  ========================== */
  function realizarBusqueda() {
    const busqueda = busquedaInput ? busquedaInput.value : '';
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "Consultar_Clase.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
      if (xhr.readyState == 4 && xhr.status == 200) {
        document.getElementById("resultado").innerHTML = xhr.responseText;
      }
    };
    xhr.send("busqueda=" + encodeURIComponent(busqueda) + "&id=" + encodeURIComponent(idClase));
  }
  realizarBusqueda();

  /* =========================
     EXTRAER ID DESDE TEXTO/QR
  ========================== */
  function extraerId(cadena) {
    if (!cadena) return '';
    const tokens = cadena.split(/Ñ|\n|\r/).map(s => s.trim()).filter(Boolean);
    for (let i = 0; i < tokens.length; i++) {
      if (/^ID$/i.test(tokens[i])) {
        const next = tokens[i + 1] || '';
        const m = next.match(/\d+/);
        if (m) return m[0];
      }
    }
    for (const t of tokens) {
      const m = t.match(/ID[:\s]*([0-9]+)/i);
      if (m) return m[1];
    }
    let m = cadena.match(/ID\D*?(\d+)/i);
    if (m) return m[1];
    m = cadena.match(/\b(\d{3,})\b/);
    return m ? m[1] : '';
  }

  /* =========================
     XHR: AGREGAR / ASISTENCIA
  ========================== */
  function agregarParticipante(idParticipante, done) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "Agregar_Participante_Manual.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
      if (xhr.readyState == 4) {
        let ok = (xhr.status >= 200 && xhr.status < 300), msg = xhr.responseText || 'Hecho';
        try { const data = JSON.parse(xhr.responseText); ok = !!data.ok; msg = data.msg || msg; } catch(_) {}
        toast(msg, ok, ok ? 1400 : 2200);
        ok ? beepOK() : beepError();
        done?.();
      }
    };
    xhr.send("id_participante=" + encodeURIComponent(idParticipante) + "&id_clase=" + encodeURIComponent(idClase));
  }

  function marcarAsistencia(idParticipante, done) {
    // ⚠️ Asegura que aquí coincida con tu archivo real (sensible a mayúsculas)
    const MARCAR_URL = "marcar_asistencia.php"; // Cambia a "Marcar_Asistencia.php" si así se llama el archivo
    const xhr = new XMLHttpRequest();
    xhr.open("POST", MARCAR_URL, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
      if (xhr.readyState == 4) {
        let ok = (xhr.status >= 200 && xhr.status < 300), msg = xhr.responseText || 'Asistencia registrada';
        try { const data = JSON.parse(xhr.responseText); ok = !!data.ok; msg = data.msg || msg; } catch(_) {}
        toast(msg, ok, ok ? 1200 : 2200);
        ok ? beepOK() : beepError();
        done?.();
      }
    };
    xhr.send("id_participante=" + encodeURIComponent(idParticipante) + "&id_clase=" + encodeURIComponent(idClase));
  }

  /* =========================
     UNIFICADOR (INPUT + QR)
  ========================== */
  let enviando = false;
  function procesarIDoTexto(raw, { yaEsId = false } = {}) {
    if (!raw || enviando) return;
    const texto = String(raw).trim();
    const id = yaEsId ? texto : (extraerId(texto) || (texto.match(/\b(\d{3,})\b/)?.[1] || ''));
    if (!id) { toast('No se pudo leer el ID del QR', false, 1800); beepError(); return; }

    const modo = document.querySelector('input[name="modo"]:checked').value;
    enviando = true;

    const finalizar = () => {
      enviando = false;
      if (idParticipanteInput) {
        idParticipanteInput.value = '';
        idParticipanteInput.focus();
      }
      realizarBusqueda();
    };

    if (modo === 'agregar') agregarParticipante(id, finalizar);
    else                    marcarAsistencia(id, finalizar);
  }

  /* =========================
     ESCRITORIO: SUBMIT FORM
  ========================== */
  if (scanForm) {
    scanForm.addEventListener("submit", function(e) {
      e.preventDefault();
      const raw = idParticipanteInput.value.trim();
      if (!raw) return;
      procesarIDoTexto(raw, { yaEsId: false });
    });
  }

  /* =========================
     MÓVIL/TABLET: CÁMARA QR
  ========================== */
  const isMobileLayout = () => window.matchMedia("(max-width: 991px)").matches;
  const qrWrap = document.getElementById('qrWrap');
  const qrVideo = document.getElementById('qrVideo');
  const cameraSelect = document.getElementById('cameraSelect');
  const btnStartQR = document.getElementById('btnStartQR');
  const btnStopQR  = document.getElementById('btnStopQR');
  const btnTorch   = document.getElementById('btnTorch');
  const qrMsg = document.getElementById('qrMsg');

  if (isMobileLayout() && qrWrap) {
    qrWrap.setAttribute('aria-hidden', 'false');

    const { BrowserMultiFormatReader, BrowserCodeReader } = ZXingBrowser;
    const codeReader = new BrowserMultiFormatReader();
    let currentStream = null;
    let currentDeviceId = null;
    let trackWithTorch = null;
    let scanning = false;
    let torchOn = false;

    // === Pide permiso breve para desbloquear labels en iOS/Safari ===
async function ensureLabels() {
  try {
    // Abre stream con "environment" para forzar permiso
    const tmp = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false
    });
    // Cierra de inmediato
    tmp.getTracks().forEach(t => t.stop());
  } catch (e) {
    // Si falla, seguimos: algunos navegadores igual mostrarán algo
  }
}

// === Heurística para detectar cámara trasera ===
function pickRearDevice(devices) {
  // 1) Buscar por label
  const regex = /(back|rear|environment|tr[aá]sera|facing\s*back)/i;
  let rear = devices.find(d => regex.test(d.label || ''));
  if (rear) return rear;

  // 2) Buscar por "facingMode" estilo Chrome labels (a veces incluyen "Facing back")
  rear = devices.find(d => /back/i.test(d.label || ''));
  if (rear) return rear;

  // 3) Si hay más de una, suele ser la última la trasera en móviles
  if (devices.length > 1) return devices[devices.length - 1];

  // 4) Fallback: la primera
  return devices[0] || null;
}

    async function fillCameras() {
  cameraSelect.innerHTML = '';
  try {
    // Asegura labels visibles
    await ensureLabels();

    const devices = await ZXingBrowser.BrowserCodeReader.listVideoInputDevices();

    // Elegir trasera por defecto
    const preferred = pickRearDevice(devices);
    devices.forEach((d, i) => {
      const opt = document.createElement('option');
      opt.value = d.deviceId;
      const nice = d.label || `Cámara ${i + 1}`;
      opt.textContent = nice;
      cameraSelect.appendChild(opt);
    });

    if (preferred) {
      currentDeviceId = preferred.deviceId;
      cameraSelect.value = preferred.deviceId;
    } else if (devices.length) {
      currentDeviceId = devices[0].deviceId;
      cameraSelect.value = currentDeviceId;
    }

    cameraSelect.onchange = () => currentDeviceId = cameraSelect.value;
  } catch (e) {
    qrMsg.textContent = 'No se pudieron listar cámaras.';
  }
}


   async function startQR() {
  if (scanning) return;
  scanning = true;
  btnStartQR.disabled = true;
  btnStopQR.disabled = false;
  qrMsg.textContent = 'Iniciando cámara...';

  try {
    if (!currentDeviceId) await fillCameras();

    // Abre con deviceId exacto (más confiable que facingMode)
    const constraints = {
      video: currentDeviceId
        ? { deviceId: { exact: currentDeviceId } }
        : { facingMode: { ideal: 'environment' } },
      audio: false
    };

    currentStream = await navigator.mediaDevices.getUserMedia(constraints);
    qrVideo.srcObject = currentStream;
    await qrVideo.play();

    const [track] = currentStream.getVideoTracks();
    trackWithTorch = (track.getCapabilities && track.getCapabilities().torch) ? track : null;
    btnTorch.disabled = !trackWithTorch;

    qrMsg.textContent = 'Apunta el QR al recuadro';
    loopDecode();
  } catch (e) {
    scanning = false;
    btnStartQR.disabled = false;
    btnStopQR.disabled = true;
    btnTorch.disabled = true;
    qrMsg.textContent = 'No fue posible abrir la cámara. ' + (e?.message || '');
    toast('No se pudo acceder a la cámara', false, 2500);
  }
}

    async function loopDecode(){
      if (!scanning) return;
      try{
        const result = await codeReader.decodeOnceFromVideoElement(qrVideo);
        if (result && result.text) {
          beepOK();
          qrMsg.textContent = 'QR leído ✔';
          // MISMO RECORRIDO QUE EL INPUT:
          procesarIDoTexto(result.text, { yaEsId: false });
          await new Promise(r => setTimeout(r, 600)); // evita dobles lecturas
        }
      }catch(_){
        // frame sin código
      }finally{
        if (scanning) requestAnimationFrame(loopDecode);
      }
    }

    function stopQR(){
      scanning = false;
      btnStartQR.disabled = false; btnStopQR.disabled = true; btnTorch.disabled = true;
      qrMsg.textContent = 'Cámara detenida';
      try{
        if (currentStream){ currentStream.getTracks().forEach(t => t.stop()); currentStream = null; }
        qrVideo.srcObject = null;
      }catch(_){}
    }

    async function toggleTorch(){
      if (!trackWithTorch) return;
      try{
        torchOn = !torchOn;
        await trackWithTorch.applyConstraints({ advanced: [{ torch: torchOn }] });
        btnTorch.style.background = torchOn ? '#f39c12' : '#444';
      }catch(_){
        toast('Tu dispositivo no permite linterna en navegador', false, 2000);
      }
    }

    // Eventos cámara
    btnStartQR?.addEventListener('click', async () => { await fillCameras(); startQR(); });
    btnStopQR?.addEventListener('click', stopQR);
    btnTorch?.addEventListener('click', toggleTorch);

    // Prellenar lista si el navegador lo permite
    fillCameras().catch(()=>{});
  }

  /* =========================
     TOAST / BEEPS
  ========================== */
  function toast(msg, isOk=true, ttl=1600){
    const t = document.createElement('div');
    t.className = 'ga-toast' + (isOk ? '' : ' error');
    t.textContent = msg || (isOk ? 'Listo' : 'Ocurrió un error');
    document.body.appendChild(t);
    setTimeout(()=>{ t.classList.add('fadeout'); }, ttl);
    setTimeout(()=>{ t.remove(); }, ttl + 450);
  }
  function beepOK(){
    try{
      const ctx = new (window.AudioContext||window.webkitAudioContext)();
      const o = ctx.createOscillator(), g = ctx.createGain();
      o.type='sine'; o.frequency.value=880; o.connect(g); g.connect(ctx.destination);
      g.gain.setValueAtTime(0.0001, ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.3, ctx.currentTime+0.01);
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime+0.12);
      o.start(); o.stop(ctx.currentTime+0.13);
    }catch(e){}
  }
  function beepError(){
    try{
      const ctx = new (window.AudioContext||window.webkitAudioContext)();
      const o = ctx.createOscillator(), g = ctx.createGain();
      o.type='square'; o.frequency.value=200; o.connect(g); g.connect(ctx.destination);
      g.gain.setValueAtTime(0.0001, ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime+0.01);
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime+0.25);
      o.start(); o.stop(ctx.currentTime+0.26);
    }catch(e){}
  }

// === Sidebar off-canvas ===
const menuBtn   = document.getElementById('menuBtn');
const sidebar   = document.getElementById('sidebar');
const backdrop  = document.getElementById('sidebarBackdrop');

function setMenu(open){
  sidebar.classList.toggle('open', open);
  document.body.classList.toggle('noscroll', open);
  backdrop.classList.toggle('show', open);
  if (open) backdrop.removeAttribute('hidden'); else backdrop.setAttribute('hidden','');
  menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
}
menuBtn?.addEventListener('click', ()=> setMenu(!sidebar.classList.contains('open')));
backdrop?.addEventListener('click', ()=> setMenu(false));

// Cierra el menú si se rota o cambia el ancho a escritorio
window.matchMedia('(min-width: 992px)').addEventListener('change', e=>{
  if (e.matches) setMenu(false);
});


});
</script>


</body>

<style>
  .ga-toast {
    position: fixed;
    right: 18px;
    bottom: 18px;
    padding: 12px 14px;
    border-radius: 10px;
    color: #0b2e13;
    background: #e8f7ee;
    border: 1px solid #8ed1a6;
    font-size: 14px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, .15);
    z-index: 9999;
    max-width: 380px;
  }

  .ga-toast.error {
    color: #6b1111;
    background: #fdecea;
    border-color: #f5a4a4;
  }

  .ga-toast.fadeout {
    opacity: 0;
    transition: opacity .4s ease;
  }
</style>


</style>

</html>
