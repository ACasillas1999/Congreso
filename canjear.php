<?php


ini_set('display_errors', 1); // Muestra errores en pantalla
ini_set('display_startup_errors', 1); // Muestra errores durante el inicio de PHP
error_reporting(E_ALL); // Reporta todos los errores

require_once __DIR__ . "/Conexiones/Conexion.php";

$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_participante = null;
$participante = null;
$puntos = 0;
$premios = [];

// Buscar puntos del participante si se ha enviado
function extraer_id_participante(?string $qr): int
{
  $qr = (string)$qr;
  $qr = trim($qr);

  // Quitar BOM (unicode) y NBSP/espacios no rompibles
  $qr = preg_replace('/^\xEF\xBB\xBF/u', '', $qr);
  $qr = str_replace(["\xC2\xA0", "\xE2\x80\xAF"], ' ', $qr); // NBSP/NNBSP → espacio

  if ($qr === '') return 0;

  // Solo toma el número después de "ID" con separador opcional [: - Ñ/ñ N] y espacios.
  // Funciona con: "IDÑ1202", "IDÑ 1202", "ID:1202", "ID-1202", "id 1202"
  // y aunque siga pegado: "IDÑ 1202EventoÑ 3NombreÑ 123..."
  if (preg_match('/(?i)\bID\b\h*[:\-NÑ]?\h*(\d{1,10})(?=\D|$)/u', $qr, $m)) {
    return (int)$m[1];
  }

  // Alternativa sin \b por si hay caracteres raros alrededor de "ID"
  if (preg_match('/(?i)ID\h*[:\-NÑ]?\h*(\d{1,10})(?=\D|$)/u', $qr, $m)) {
    return (int)$m[1];
  }

  // Sin fallback: si no hay "ID", no devolvemos nada
  return 0;
}
// Quita / comenta la línea que tenías:
// $id_participante = extraer_id_participante($_POST["qr"]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr'])) {
  $qrRaw = $_POST['qr'] ?? '';

  // Seguridad: evita procesar vacío
  if (trim($qrRaw) === '') {
    echo "<p style='color:orange;'>No se recibió texto del QR.</p>";
  } else {
    $id_participante = extraer_id_participante($qrRaw);

    if ($id_participante <= 0) {
      echo "<p style='color:orange;'>No se pudo extraer el ID del código escaneado.</p>";
    } else {

      // ==== Obtener datos del participante (con o sin id_evento) ====
      if ($id_evento > 0) {
        $sql = "SELECT * FROM participante WHERE ID = ? AND ID_Evento = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_participante, $id_evento);
      } else {
        $sql = "SELECT * FROM participante WHERE ID = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_participante);
      }
      $stmt->execute();
      $result = $stmt->get_result();
      $participante = $result->fetch_assoc();
      $stmt->close();

      if ($participante) {
        if ($id_evento === 0) {
          $id_evento = (int)($participante['ID_Evento'] ?? 0);
        }

        // === Cartera grupal (RFC + Evento) ===
        $rfc = $participante['RFC'] ?? '';
        $sqlCartera = "SELECT COALESCE(Puntos,0) AS Puntos
                               FROM puntos_rfc
                               WHERE RFC = ? AND ID_Evento = ?";
        $stmt = $conn->prepare($sqlCartera);
        $stmt->bind_param("si", $rfc, $id_evento);
        $stmt->execute();
        $cartera = (int)($stmt->get_result()->fetch_assoc()['Puntos'] ?? 0);
        $stmt->close();

        $puntos = $cartera;

        // Premios disponibles
        $sql_premios = "SELECT * FROM premios_evento WHERE ID_Evento = ? AND Disponible = 1";
        $stmt = $conn->prepare($sql_premios);
        $stmt->bind_param("i", $id_evento);
        $stmt->execute();
        $result = $stmt->get_result();
        $premios = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
      } else {
        echo "<p style='color:red;'>Participante no encontrado.</p>";
      }
    }
  }
}
// Si no es POST, no se hace nada: solo se muestra el formulario/lector

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon" />

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <title>Canjear Premios</title>
  <link rel="stylesheet" href="styles.css">
  <script src="https://unpkg.com/@zxing/browser@latest"></script>
  <style>
    /* ===== Variables base (modo oscuro actual) ===== */
    :root {
      --bg-grad: radial-gradient(circle at 20% 0%, #142455 0%, #0b1535 70%);
      --panel: rgba(255, 255, 255, .08);
      --panel-strong: rgba(255, 255, 255, .14);
      --text: #fff;
      --muted: rgba(255, 255, 255, .75);
      --line: rgba(255, 255, 255, .18);
      --accent: #1976d2;
      --accent-2: #21a1f3;
      --warn: #ff8c00;
      --danger: #ff5252;
    }

    /* ===== Reset móvil y fondo ===== */
    * {
      box-sizing: border-box;
    }

    html,
    body {
      height: 100%;
    }

    body {
      margin: 0;
      color: var(--text);
      font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
      background: var(--bg-grad);
      -webkit-tap-highlight-color: transparent;
      padding: env(safe-area-inset-top) 14px env(safe-area-inset-bottom);
    }

    /* ===== Layout contenedor ===== */
    .container-page {
      width: min(100%, 900px);
      margin: 0 auto;
      padding: 16px 0 28px;
    }

    /* ===== Títulos adaptativos ===== */
    h1 {
      margin: 4px 0 10px;
      font-size: clamp(20px, 5.8vw, 32px);
      line-height: 1.15;
      text-align: center;
      text-shadow: 0 0 8px rgba(255, 255, 255, .28);
    }

    h2 {
      font-size: clamp(18px, 4.4vw, 24px);
      line-height: 1.2;
      text-align: center;
      margin: 12px 0 8px;
    }

    /* ===== Panel / Form ===== */
    form {
      width: 100%;
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 14px;
      box-shadow: 0 10px 24px rgba(0, 0, 0, .35);
      padding: clamp(50px, 4vw, 20px);
      display: grid;
      gap: 10px;
    }

    label {
      font-weight: 600;
      opacity: .95;
    }

    input[type="text"],
    input[type="number"] {
      width: 100%;
      padding: 14px 12px;
      /* 48px táctil con line-height */
      min-height: 48px;
      border: 1px solid rgba(255, 255, 255, .12);
      border-radius: 12px;
      background: #1f2a4d;
      color: var(--text);
      font-size: 16px;
      /* evita zoom raro en iOS */
      line-height: 1.35;
      box-shadow: inset 0 0 6px rgba(0, 0, 0, .35);
      transition: background .2s, outline-color .2s, border-color .2s;
    }

    input[type="text"]:focus,
    input[type="number"]:focus {
      background: #27356a;
      border-color: rgba(33, 161, 243, .5);
      outline: none;
    }

    /* ===== Botones táctiles ===== */
    button,
    .button,
    .scan-trigger {
      appearance: none;
      border: none;
      border-radius: 12px;
      min-height: 48px;
      /* objetivo táctil */
      padding: 12px 14px;
      font-weight: 700;
      font-size: 16px;
      color: #fff;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      box-shadow: 0 6px 16px rgba(0, 0, 0, .35), inset 0 -2px 0 rgba(255, 255, 255, .12);
      cursor: pointer;
      transition: transform .12s ease, filter .2s ease, background .25s ease;
      touch-action: manipulation;
    }

    button:hover {
      transform: translateY(-1px) scale(1.01);
    }

    button:active {
      transform: translateY(0) scale(.98);
    }

    button[disabled] {
      opacity: .6;
      filter: grayscale(.2);
      cursor: not-allowed;
    }

    button.is-secondary {
      background: linear-gradient(135deg, #666, #4a4a4a);
    }

    /* Botonera responsiva bajo el input */
    .form-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    @media (max-width: 440px) {
      .form-actions {
        grid-template-columns: 1fr;
      }
    }

    /* ===== Lector QR ===== */
    #qrWrap {
      margin-top: 12px;
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 12px;
    }

    #qrControls {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      margin-bottom: 10px;
    }

    #qrControls select {
      flex: 1 1 160px;
      min-height: 44px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, .14);
      background: #0f1838;
      color: #e0e0e0;
      padding: 8px 10px;
      font-size: 15px;
    }

    #qrBox {
      position: relative;
      width: 100%;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(0, 0, 0, .35);
      background: #000;
    }

    #qrVideo {
      display: block;
      width: 100%;
      height: auto;
      max-height: min(60vh, 420px);
      /* evita que “desborde” en móvil */
      object-fit: cover;
    }

    #qrFrame {
      position: absolute;
      inset: 0;
      pointer-events: none;
      box-shadow: 0 0 0 9999px rgba(0, 0, 0, .32);
      border: 2px solid rgba(255, 255, 255, .6);
      border-radius: 14px;
      margin: 6%;
    }

    #qrMsg {
      margin-top: 10px;
      color: #9fd3ff;
      font-size: 14px;
    }

    /* ===== Tabla -> Tarjetas en móvil ===== */
    table.mi-tabla {
      width: 100%;
      border-collapse: collapse;
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 18px rgba(0, 0, 0, .45);
    }

    @media (max-width: 720px) {
      .mi-tabla thead {
        display: none;
      }

      .mi-tabla,
      .mi-tabla tbody,
      .mi-tabla tr,
      .mi-tabla td {
        display: block;
        width: 100%;
      }

      .mi-tabla tr {
        border-bottom: 1px solid var(--line);
        padding: 12px 12px 4px;
        background: transparent;
      }

      .mi-tabla td {
        border: none;
        padding: 8px 6px;
        position: relative;
        font-size: 15px;
        color: var(--text);
      }

      .mi-tabla td::before {
        content: attr(data-label);
        display: block;
        font-size: 12px;
        letter-spacing: .2px;
        color: var(--muted);
        margin-bottom: 4px;
        text-transform: uppercase;
      }

      .mi-tabla input[type="number"] {
        width: 100%;
        min-height: 44px;
        font-size: 16px;
      }
    }

    /* ===== Enlaces de navegación ===== */
    p:last-of-type a {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      min-height: 48px;
      padding: 10px 16px;
      border-radius: 12px;
      text-decoration: none;
      color: #fff;
      font-weight: 700;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
    }

    /* ===== A11y y “smoothness” ===== */
    @media (prefers-reduced-motion: reduce) {
      * {
        animation-duration: .001ms !important;
        animation-iteration-count: 1 !important;
        transition: none !important;
      }
    }

    /* ===== Soporte claro/oscuro del SO (opcional) ===== */
    @media (prefers-color-scheme: light) {
      :root {
        --bg-grad: #f5f7fb;
        --panel: #fff;
        --panel-strong: #f2f5fb;
        --text: #0e1b3b;
        --muted: #506188;
        --line: #e6ebf5;
      }

      body {
        color: var(--text);
      }

      #qrFrame {
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, .08);
        border-color: #0e1b3b33;
      }
    }

    /* Oculto por defecto en todos los tamaños */
    #qrWrap {
      display: none;
    }

    /* El botón para abrir la cámara también oculto por defecto */
    #btnToggleScanner {
      display: none;
    }

    /* SOLO en móvil (<= 991px) se puede ver */
    @media (max-width: 991px) {

      /* muestra el botón para abrir el lector */
      #btnToggleScanner {
        display: inline-flex;
      }

      /* clase que sí habilita el lector en móvil */
      #qrWrap.mobile-visible {
        display: block;
      }
    }

    /* Oculta los controles del lector en desktop */
    #qrControls {
      display: none;
    }

    @media (max-width: 991px) {
      #qrControls {
        display: flex;
      }
    }

    /* Nuevo estilo */

    /* ============ APP MOBILE LOOK ============ */
    body {
      font-family: "Inter", system-ui, -apple-system, Segoe UI, Arial, sans-serif;
    }

    .container-page {
      width: min(100%, 820px);
      margin-inline: auto;
      padding: 12px 12px 96px;
    }

    /* Header compacto y centrado */
    .app-header {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 6px 0 10px;
    }

    .app-header__emoji {
      font-size: 22px;
    }

    .app-header__title {
      font-weight: 800;
      letter-spacing: .2px;
      font-size: clamp(18px, 5.2vw, 22px);
    }

    /* Tarjetas secciones */
    .section-card {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 14px;
      box-shadow: 0 8px 28px rgba(0, 0, 0, .35);
      margin-bottom: 12px;
    }

    /* Etiqueta + input grandes */
    .form-field {
      display: grid;
      gap: 8px;
    }

    label {
      font-size: 14px;
      color: var(--muted);
    }

    .input {
      width: 100%;
      min-height: 52px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, .15);
      background: #1f2a4d;
      color: #fff;
      font-size: 16px;
      padding: 12px 14px;
    }

    /* Botones primarios/secundarios grandes */
    .btn {
      appearance: none;
      border: 0;
      border-radius: 14px;
      min-height: 52px;
      padding: 13px 16px;
      font-weight: 800;
      font-size: 16px;
      color: #fff;
      cursor: pointer;
      box-shadow: 0 8px 18px rgba(0, 0, 0, .35), inset 0 -2px 0 rgba(255, 255, 255, .12);
      transition: transform .12s ease, opacity .2s ease, filter .2s ease;
    }

    .btn:active {
      transform: translateY(1px) scale(.99);
    }

    .btn-primary {
      background: linear-gradient(135deg, #21a1f3, #1976d2);
    }

    .btn-secondary {
      background: linear-gradient(135deg, #666, #4a4a4a);
    }

    .btn-danger {
      background: linear-gradient(135deg, #ff6a6a, #f44336);
    }

    /* Botonera en dos columnas (1 en pantallas chicas) */
    .actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    @media (max-width: 420px) {
      .actions {
        grid-template-columns: 1fr;
      }
    }

    /* Lector QR: compacto y limpio */
    #qrWrap {
      padding: 12px;
      border-radius: 16px;
    }

    .qr-controls {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 8px;
    }

    .qr-controls select {
      flex: 1 1 180px;
      min-height: 46px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, .14);
      background: #0f1838;
      color: #e0e0e0;
      padding: 10px 12px;
      font-size: 15px;
    }

    #qrBox {
      border-radius: 14px;
      overflow: hidden;
      background: #000;
    }

    #qrVideo {
      width: 100%;
      height: auto;
      max-height: min(58vh, 420px);
      object-fit: cover;
    }

    #qrFrame {
      margin: 4%;
      border-radius: 14px;
      border: 2px solid rgba(255, 255, 255, .65);
      box-shadow: 0 0 0 9999px rgba(0, 0, 0, .33);
    }

    /* KPI de participante */
    .kpi {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      font-weight: 800;
      font-size: 18px;
      padding: 8px 0;
    }

    .kpi .chip {
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(33, 161, 243, .18);
      border: 1px solid rgba(33, 161, 243, .35);
      font-size: 14px;
      font-weight: 700;
    }

    /* Tabla -> tarjetas en móvil (mejoras visuales) */
    table.mi-tabla {
      border-radius: 16px;
      overflow: hidden;
    }

    @media (max-width: 740px) {
      .mi-tabla thead {
        display: none;
      }

      .mi-tabla,
      .mi-tabla tbody,
      .mi-tabla tr,
      .mi-tabla td {
        display: block;
        width: 100%;
      }

      .mi-tabla tr {
        background: transparent;
        border-bottom: 1px solid var(--line);
        padding: 12px 12px 6px;
        margin-bottom: 6px;
      }

      .mi-tabla td {
        border: 0;
        padding: 8px 6px;
        color: var(--text);
        font-size: 16px;
      }

      .mi-tabla td::before {
        content: attr(data-label);
        display: block;
        color: var(--muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .2px;
        margin-bottom: 4px;
      }

      .mi-tabla input[type="number"] {
        width: 100%;
        min-height: 48px;
        border-radius: 12px;
      }
    }

    /* CTA fija inferior (confirmar canje) */
    .sticky-cta {
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(180deg, rgba(10, 16, 38, 0), rgba(10, 16, 38, .7) 18%, rgba(10, 16, 38, .9) 65%);
      backdrop-filter: blur(6px);
    }

    .sticky-cta__inner {
      width: min(100%, 820px);
      margin-inline: auto;
      display: grid;
      gap: 8px;
    }

    .app-header {
      display: grid;
      grid-template-columns: auto 1fr auto;
      align-items: center;
      gap: 10px;
      padding: 4px 0 10px;
    }

    .app-header__title {
      text-align: center;
      font-weight: 800;
      font-size: clamp(18px, 5.2vw, 22px);
    }

    .app-header__emoji {
      margin-right: 6px;
    }

    .app-header__spacer {
      width: 80px;
    }

    /* compensa el ancho del botón Volver para centrar título */
    .back-btn {
      min-width: 80px;
      text-align: center;
      padding-inline: 10px;
    }
  </style>
</head>

<body>
  <!--<h1>🎁 Canje de Premios - Evento #<?= $id_evento ?></h1>-->

  <header class="app-header">
    <a class="back-btn btn btn-secondary" href="Evento_inicio.php?id=<?= $id_evento ?>">← Volver</a>
    <div class="app-header__title"><span class="app-header__emoji">🎁</span> Canje de Premios — Evento #<?= $id_evento ?></div>
    <span class="app-header__spacer"></span>
  </header>


  <!--<form method="POST">
    <label>Escanea QR del Participante:</label>
    <input type="text" name="qr" required autofocus>
    <button type="submit">Buscar</button>
</form>-->
  <!--
<form id="form-qr" method="POST" autocomplete="off">
  <label>Escanea QR del Participante:</label>
  <input id="qr-input" type="text" name="qr" required autofocus placeholder="Apunta la cámara o pega el código aquí">
  <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:6px;">
    <button class="button" type="submit">Buscar</button>
    <button class="button" type="button" id="btnToggleScanner" style="background:#1976d2">📷 Usar cámara</button>
  </div>
</form>-->
  <div class="section-card">
    <form id="form-qr" method="POST" autocomplete="off" class="form-field">
      <label for="qr-input">Escanea o pega el código del participante</label>
      <input id="qr-input" class="input" type="text" name="qr" required autofocus placeholder="Apunta la cámara o pega el código aquí">
      <div class="actions">
        <button class="btn btn-primary" type="submit">Buscar</button>
        <button class="btn btn-secondary" type="button" id="btnToggleScanner">📷 Usar cámara</button>
      </div>
    </form>
  </div>


  <!-- Lector QR (igual UX a la otra ventana) -->
  <!--<div id="qrWrap" aria-hidden="true">
  <div id="qrControls">
    <strong style="color:#e0e0e0">Lector QR (cámara)</strong>
    <select id="cameraSelect"></select>
    <button id="btnStartQR" type="button" class="button" style="background:#2e7d32">Iniciar cámara</button>
    <button id="btnStopQR"  type="button" class="button" style="background:#666" disabled>Detener</button>
    <button id="btnTorch"   type="button" class="button" style="background:#444" disabled>Linterna</button>
  </div>

  <div id="qrBox">
    <video id="qrVideo" playsinline></video>
    <div id="qrFrame"></div>
  </div>

  <div id="qrMsg"></div>
</div>-->

  <div id="qrWrap" class="section-card" aria-hidden="true">
    <div class="qr-controls" id="qrControls">
      <select id="cameraSelect" aria-label="Seleccionar cámara"></select>
      <button id="btnStartQR" type="button" class="btn btn-primary">Iniciar cámara</button>
      <button id="btnStopQR" type="button" class="btn btn-secondary" disabled>Detener</button>
      <button id="btnTorch" type="button" class="btn btn-secondary" disabled>Linterna</button>
    </div>
    <div id="qrBox">
      <video id="qrVideo" playsinline></video>
      <div id="qrFrame"></div>
    </div>
    <div id="qrMsg" style="margin-top:8px;color:#9fd3ff;font-size:14px"></div>
  </div>



  <?php if ($participante): ?>
    <!--  <hr>
    <h2>👤 <?= htmlspecialchars($participante["Nombre"]) ?> — <?= $puntos ?> puntos</h2>
-->
    <?php if ($participante): ?>
      <div class="section-card">
        <div class="kpi">
          <span>👤 <?= htmlspecialchars($participante["Nombre"] ?? $id_participante) ?></span>
          <span class="chip"><?= $puntos ?> pts</span>
        </div>
      </div>
    <?php endif; ?>

    <?php if (count($premios) === 0): ?>
      <p>No hay premios disponibles.</p>
    <?php else: ?>
      <form id="form-canje" action="guardar_canje.php" method="POST">
        <input type="hidden" name="id_evento" value="<?= $id_evento ?>">
        <input type="hidden" name="id_participante" value="<?= $id_participante ?>">
        <table class="mi-tabla">
          <thead>
            <tr>
              <th>Premio</th>
              <th>Puntos</th>
              <th>Máx. Canjeable</th>
              <th>Cantidad a Canjear</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($premios as $premio): $max = floor($puntos / $premio["PuntosNecesarios"]); ?>
              <tr>
                <td data-label="Premio"><?= htmlspecialchars($premio["NombrePremio"]) ?></td>
                <td data-label="Puntos"><?= $premio["PuntosNecesarios"] ?></td>
                <td data-label="Máx. Canjeable"><?= $max ?></td>
                <td data-label="Cantidad a Canjear">
                  <input class="input" type="number" name="canje[<?= $premio["ID"] ?>]" value="0" min="0" max="<?= $max ?>">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!--<button type="submit">✅ Confirmar Canje</button>-->

        <div class="sticky-cta">
          <div class="sticky-cta__inner">
            <button type="submit" form="form-canje" class="btn btn-primary">✅ Confirmar Canje</button>
          </div>
        </div>

      </form>
    <?php endif; ?>
  <?php endif; ?>



  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const formQR = document.getElementById('form-qr');
      const inputQR = document.getElementById('qr-input');

      // Toggles del lector
      const qrWrap = document.getElementById('qrWrap');
      const btnToggle = document.getElementById('btnToggleScanner');
      const cameraSelect = document.getElementById('cameraSelect');
      const btnStartQR = document.getElementById('btnStartQR');
      const btnStopQR = document.getElementById('btnStopQR');
      const btnTorch = document.getElementById('btnTorch');
      const qrVideo = document.getElementById('qrVideo');
      const qrMsg = document.getElementById('qrMsg');

      // === Utilidades (beeps como en tu ejemplo) ===
      function beepOK() {
        try {
          const ctx = new(window.AudioContext || window.webkitAudioContext)();
          const o = ctx.createOscillator(),
            g = ctx.createGain();
          o.type = 'sine';
          o.frequency.value = 880;
          o.connect(g);
          g.connect(ctx.destination);
          g.gain.setValueAtTime(0.0001, ctx.currentTime);
          g.gain.exponentialRampToValueAtTime(0.3, ctx.currentTime + 0.01);
          g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.12);
          o.start();
          o.stop(ctx.currentTime + 0.13);
        } catch (e) {}
      }

      function beepError() {
        try {
          const ctx = new(window.AudioContext || window.webkitAudioContext)();
          const o = ctx.createOscillator(),
            g = ctx.createGain();
          o.type = 'square';
          o.frequency.value = 200;
          o.connect(g);
          g.connect(ctx.destination);
          g.gain.setValueAtTime(0.0001, ctx.currentTime);
          g.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + 0.01);
          g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.25);
          o.start();
          o.stop(ctx.currentTime + 0.26);
        } catch (e) {}
      }

      // === Mostrar/ocultar bloque del lector ===
      // Helper: detectar layout móvil (<= 991px)
      const isMobileLayout = () => window.matchMedia('(max-width: 991px)').matches;

      btnToggle?.addEventListener('click', async () => {
        if (!isMobileLayout()) return;
        const showing = qrWrap.classList.toggle('mobile-visible');
        qrWrap.setAttribute('aria-hidden', showing ? 'false' : 'true');
        if (!showing) {
          try {
            await stopQR();
          } catch (_) {}
        }
      });

      // Si cambia a escritorio, oculta y detén la cámara
      window.matchMedia('(max-width: 991px)').addEventListener('change', async (e) => {
        if (!e.matches) {
          qrWrap.classList.remove('mobile-visible');
          qrWrap.setAttribute('aria-hidden', 'true');
          try {
            await stopQR();
          } catch (_) {}
        }
      });

      // Cierra cámara al salir de la página (iOS Safari)
      window.addEventListener('pagehide', () => {
        try {
          stopQR();
        } catch (_) {}
      });



      // === ZXing setup (igual patrón que en “Clase”) ===
      const {
        BrowserMultiFormatReader,
        BrowserCodeReader
      } = ZXingBrowser;
      const codeReader = new BrowserMultiFormatReader();

      let currentStream = null;
      let currentDeviceId = null;
      let trackWithTorch = null;
      let scanning = false;
      let torchOn = false;

      async function ensureLabels() {
        try {
          const tmp = await navigator.mediaDevices.getUserMedia({
            video: {
              facingMode: {
                ideal: 'environment'
              }
            },
            audio: false
          });
          tmp.getTracks().forEach(t => t.stop());
        } catch (_) {}
      }

      function pickRearDevice(devices) {
        const regex = /(back|rear|environment|tr[aá]sera|facing\s*back)/i;
        let rear = devices.find(d => regex.test(d.label || ''));
        if (rear) return rear;
        rear = devices.find(d => /back/i.test(d.label || ''));
        if (rear) return rear;
        if (devices.length > 1) return devices[devices.length - 1];
        return devices[0] || null;
      }

      async function fillCameras() {
        cameraSelect.innerHTML = '';
        try {
          await ensureLabels();
          const devices = await BrowserCodeReader.listVideoInputDevices();
          const preferred = pickRearDevice(devices);
          devices.forEach((d, i) => {
            const opt = document.createElement('option');
            opt.value = d.deviceId;
            opt.textContent = d.label || `Cámara ${i+1}`;
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
        if (!isMobileLayout()) return; // ⬅️ bloquea en desktop
        if (scanning) return;
        scanning = true;
        btnStartQR.disabled = true;
        btnStopQR.disabled = false;
        qrMsg.textContent = 'Iniciando cámara...';
        try {
          if (!currentDeviceId) await fillCameras();

          const constraints = {
            video: currentDeviceId ? {
              deviceId: {
                exact: currentDeviceId
              }
            } : {
              facingMode: {
                ideal: 'environment'
              }
            },
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
          beepError();
        }
      }

      async function loopDecode() {
        if (!scanning) return;
        try {
          const result = await codeReader.decodeOnceFromVideoElement(qrVideo);
          if (result && result.text) {
            beepOK();
            qrMsg.textContent = 'QR leído ✔';
            // Rellenamos input y enviamos TU formulario PHP:
            inputQR.value = result.text.trim();
            // Opcional: cerrar cámara tras leer 1 QR
            await stopQR();
            formQR.submit();
            return; // salimos
          }
        } catch (_) {
          /* frame sin código */ }
        if (scanning) requestAnimationFrame(loopDecode);
      }

      async function stopQR() {
        scanning = false;
        btnStartQR.disabled = false;
        btnStopQR.disabled = true;
        btnTorch.disabled = true;
        qrMsg.textContent = 'Cámara detenida';
        try {
          if (currentStream) {
            currentStream.getTracks().forEach(t => t.stop());
            currentStream = null;
          }
          qrVideo.srcObject = null;
        } catch (_) {}
      }

      async function toggleTorch() {
        if (!trackWithTorch) return;
        try {
          torchOn = !torchOn;
          await trackWithTorch.applyConstraints({
            advanced: [{
              torch: torchOn
            }]
          });
          btnTorch.style.background = torchOn ? '#f39c12' : '#444';
        } catch (_) {
          qrMsg.textContent = 'Tu dispositivo no permite linterna en navegador.';
          btnTorch.disabled = true;
        }
      }

      // Eventos
      btnStartQR?.addEventListener('click', async () => {
        await fillCameras();
        startQR();
      });
      btnStopQR?.addEventListener('click', stopQR);
      btnTorch?.addEventListener('click', toggleTorch);

      // Submit manual (si teclean o pegan código)
      formQR?.addEventListener('submit', (e) => {
        const raw = inputQR.value.trim();
        if (!raw) {
          e.preventDefault();
        }
      });

      // Prellenar lista si el navegador lo permite
      fillCameras().catch(() => {});
    });
  </script>

</body>

</html>