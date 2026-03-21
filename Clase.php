<?php
// Clase.php - Reconstrucción final con lógica y estilo corregido
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
require_once __DIR__ . "/Conexiones/Conexion.php";

$id_evento = 0;
$detalle_clase = [
    'Actividad' => 'Sesion',
    'Salon' => 'Sin salon',
    'Fecha' => '',
    'Horario' => '',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clase</title>
    <link rel="stylesheet" type="text/css" href="styles_clase.css?v=<?php echo time(); ?>">
    <?php include "header_css.php"; ?>
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    <script src="https://unpkg.com/@zxing/browser@latest"></script>
    <?php
    if ($id > 0) {
        $stmt_e = $conn->prepare("SELECT ID_Evento, Actividad, Salon, Fecha, Horario FROM agenda WHERE ID = ?");
        $stmt_e->bind_param("i", $id);
        $stmt_e->execute();
        $agenda = $stmt_e->get_result()->fetch_assoc();
        $stmt_e->close();

        if ($agenda) {
            $id_evento = (int)($agenda['ID_Evento'] ?? 0);
            $detalle_clase['Actividad'] = $agenda['Actividad'] ?? $detalle_clase['Actividad'];
            $detalle_clase['Salon'] = $agenda['Salon'] ?? $detalle_clase['Salon'];
            $detalle_clase['Fecha'] = $agenda['Fecha'] ?? $detalle_clase['Fecha'];
            $detalle_clase['Horario'] = $agenda['Horario'] ?? $detalle_clase['Horario'];
        }
    }
    ?>
    <style>
        .container {
            margin-top: 10px !important;
        }

        .clase-shell {
            display: grid;
            gap: 18px;
            position: relative;
            overflow: hidden;
        }

        .clase-shell::before {
            content: "";
            position: absolute;
            top: -100px;
            left: -80px;
            width: 240px;
            height: 240px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(56, 217, 255, 0.22) 0%, rgba(56, 217, 255, 0) 72%);
            pointer-events: none;
        }

        .hero-panel,
        .workspace-card,
        .result-card {
            position: relative;
            background: linear-gradient(180deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.03) 100%);
            border: 1px solid var(--theme-border);
            border-radius: 18px;
            box-shadow: var(--theme-shadow);
            overflow: hidden;
        }

        .hero-panel {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.95fr);
            gap: 18px;
            align-items: end;
            padding: 24px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.08);
            color: var(--theme-title);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 11px;
            font-weight: 700;
        }

        .titulo-clase {
            margin: 12px 0 8px;
            font-size: clamp(28px, 3.4vw, 40px);
            line-height: 1.05;
        }

        .hero-summary {
            margin: 0;
            max-width: 760px;
            color: var(--theme-text-soft);
            font-size: 15px;
            line-height: 1.6;
        }

        .hero-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .meta-chip {
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(2, 16, 35, 0.42);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .meta-chip span {
            display: block;
            margin-bottom: 6px;
            color: var(--theme-text-soft);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .meta-chip strong {
            display: block;
            color: #fff;
            font-size: 15px;
            line-height: 1.35;
        }

        .workspace-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.85fr);
            gap: 18px;
        }

        .workspace-card,
        .result-card {
            padding: 20px;
        }

        .section-head,
        .result-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .section-kicker {
            display: inline-block;
            margin-bottom: 6px;
            color: var(--theme-title);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .section-head h3,
        .result-head h3,
        .control-panel h3 {
            color: var(--theme-title) !important;
            margin: 0 0 4px;
            font-size: 22px;
            text-shadow: 0 0 8px rgba(255, 215, 0, 0.12);
        }

        .section-head p,
        .result-head p {
            margin: 0;
            color: var(--theme-text-soft);
            line-height: 1.5;
        }

        .search-shell {
            position: relative;
            margin-bottom: 18px;
        }

        .search-shell svg {
            position: absolute;
            left: 16px;
            top: 15px;
            color: var(--theme-text-soft);
        }

        .input-search {
            width: 100%;
            max-width: none;
            padding: 14px 20px 14px 48px !important;
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid var(--theme-border) !important;
            color: #fff !important;
            border-radius: 14px !important;
            font-size: 16px;
            margin-bottom: 0;
            transition: all 0.3s ease;
        }

        .input-search:focus {
            background: rgba(255,255,255,0.1) !important;
            border-color: var(--theme-primary) !important;
        }

        .control-panel {
            margin: 0;
            padding: 20px;
            background: rgba(255,255,255,0.03);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .control-panel label {
            color: var(--theme-text);
            font-weight: 500;
            cursor: pointer;
            display: block;
        }

        .mode-selector {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .mode-option {
            position: relative;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.02);
            transition: all 0.25s ease;
        }

        .mode-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .mode-option-content {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 14px 16px;
            min-height: 100%;
        }

        .mode-option strong {
            color: #fff;
            font-size: 15px;
        }

        .mode-option span {
            color: var(--theme-text-soft);
            font-size: 13px;
            line-height: 1.45;
        }

        .mode-option.is-active {
            background: linear-gradient(180deg, rgba(56, 217, 255, 0.18) 0%, rgba(56, 217, 255, 0.08) 100%);
            border-color: rgba(56, 217, 255, 0.36);
            box-shadow: 0 12px 30px rgba(2, 16, 35, 0.28);
            transform: translateY(-1px);
        }

        .mode-hint,
        .scan-hint {
            margin: 0;
            color: var(--theme-text-soft);
            font-size: 13px;
            line-height: 1.5;
        }

        .scan-stack {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }

        .scan-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: stretch;
        }

        .scan-field {
            width: 100%;
            min-width: 0;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-size: 16px;
        }

        .scan-field:focus {
            outline: none;
            border-color: var(--theme-primary);
            background: rgba(255,255,255,0.08);
        }

        .button {
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 700;
            letter-spacing: 0.02em;
            box-shadow: 0 12px 24px rgba(17, 30, 66, 0.25);
        }

        .button.button-muted {
            background: rgba(255,255,255,0.12);
        }

        .button.button-muted:hover {
            background: rgba(255,255,255,0.18);
        }

        .qr-zone {
            display: grid;
            gap: 12px;
            padding: 14px;
            border-radius: 16px;
            background: rgba(0,0,0,0.18);
            border: 1px dashed rgba(255,255,255,0.14);
        }

        .qr-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
            justify-content: center;
        }

        .qr-video-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--theme-border);
            background: #000;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.06);
        }

        .qr-status {
            min-height: 20px;
            color: var(--theme-title);
            font-weight: 600;
            text-align: center;
        }

        .helper-card {
            display: grid;
            gap: 16px;
        }

        .helper-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .helper-list li {
            display: grid;
            gap: 4px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
        }

        .helper-list strong {
            color: #fff;
            font-size: 14px;
        }

        .helper-list span {
            color: var(--theme-text-soft);
            font-size: 13px;
            line-height: 1.45;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }

        .status-pill::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--theme-title);
            box-shadow: 0 0 10px rgba(124, 236, 255, 0.5);
        }

        .status-pill.is-loading::before {
            background: #f7b955;
            box-shadow: 0 0 12px rgba(247, 185, 85, 0.6);
        }

        .status-pill.is-error::before {
            background: #ff7a7a;
            box-shadow: 0 0 12px rgba(255, 122, 122, 0.6);
        }

        #resultado {
            margin-top: 0;
        }

        .result-placeholder {
            display: grid;
            gap: 8px;
            justify-items: center;
            text-align: center;
            padding: 40px 20px;
            border-radius: 16px;
            border: 1px dashed rgba(255,255,255,0.12);
            color: var(--theme-text-soft);
            background: rgba(255,255,255,0.02);
        }

        .ga-toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            padding: 12px 20px;
            border-radius: 14px;
            color: #fff;
            background: var(--theme-surface-strong);
            border: 1px solid var(--theme-border);
            z-index: 100000;
            backdrop-filter: blur(10px);
            box-shadow: var(--theme-shadow);
            animation: slideIn 0.3s ease-out;
        }

        .ga-toast.error {
            border-color: rgba(255, 122, 122, 0.45);
        }

        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 1100px) {
            .hero-panel,
            .workspace-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 991px) {
            .clase-shell {
                gap: 14px;
            }

            .hero-panel,
            .workspace-card,
            .result-card,
            .control-panel {
                padding: 16px;
            }

            .hero-meta,
            .mode-selector,
            .scan-form {
                grid-template-columns: 1fr;
            }

            .section-head,
            .result-head {
                flex-direction: column;
                align-items: stretch;
            }

            .qr-zone {
                padding: 12px;
            }
        }

        @media (max-width: 640px) {
            .titulo-clase {
                font-size: 28px;
            }
        }
    </style>
</head>
<body class="fade-in">
<?php include "sidebar.php"; ?>

<div class="container clase-shell">
    <section class="hero-panel">
        <div>
            <span class="eyebrow">Clase / Control operativo</span>
            <h1 class="titulo titulo-clase"><?php echo htmlspecialchars($detalle_clase['Actividad']); ?></h1>
            <p class="hero-summary">Administra inscripciones, toma asistencia y registra participantes desde una sola pantalla. El lector QR se mantiene habilitado para uso movil y el listado inferior se actualiza automaticamente despues de cada accion.</p>
        </div>
        <div class="hero-meta">
            <div class="meta-chip">
                <span>Salon</span>
                <strong><?php echo htmlspecialchars($detalle_clase['Salon']); ?></strong>
            </div>
            <div class="meta-chip">
                <span>Horario</span>
                <strong><?php echo htmlspecialchars($detalle_clase['Horario'] ?: 'Sin horario'); ?></strong>
            </div>
            <div class="meta-chip">
                <span>Fecha</span>
                <strong><?php echo htmlspecialchars($detalle_clase['Fecha'] ?: 'Sin fecha'); ?></strong>
            </div>
            <div class="meta-chip">
                <span>ID de agenda</span>
                <strong>#<?php echo (int)$id; ?></strong>
            </div>
        </div>
    </section>

    <div class="workspace-grid">
        <section class="workspace-card">
            <div class="section-head">
                <div>
                    <span class="section-kicker">Panel de operacion</span>
                    <h3>Buscar, escanear y procesar</h3>
                    <p>Busca por nombre, telefono, proveedor o ID. En movil, el QR del gafete acelera el flujo de asistencia e inscripcion.</p>
                </div>
                <div class="status-pill" id="estadoBusqueda">Listo</div>
            </div>

            <div class="search-shell">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="busqueda" placeholder="Buscar por nombre, telefono, proveedor o ID..." class="input-search">
            </div>

            <div class="control-panel">
                <h3>Modo de registro</h3>
                <div class="mode-selector">
                    <label class="mode-option is-active">
                        <input type="radio" name="modo" value="asistencia" checked>
                        <span class="mode-option-content">
                            <strong>Tomar asistencia</strong>
                            <span>Registra acceso para participantes que ya estan inscritos en esta sesion.</span>
                        </span>
                    </label>
                    <label class="mode-option">
                        <input type="radio" name="modo" value="agregar">
                        <span class="mode-option-content">
                            <strong>Inscribir en clase</strong>
                            <span>Agrega al participante a la sesion y valida la capacidad disponible.</span>
                        </span>
                    </label>
                </div>

                <p id="modoHint" class="mode-hint">Modo actual: registrar asistencia para participantes previamente inscritos.</p>

                <div class="scan-stack">
                    <div id="qrWrap" class="qr-zone" style="display:none; flex-direction:column; align-items:center;">
                        <p class="scan-hint">Activa la camara y centra el QR dentro del recuadro para procesarlo automaticamente.</p>
                        <div class="qr-controls">
                            <select id="cameraSelect" class="scan-field" style="max-width:320px;"></select>
                            <button id="btnStartQR" type="button" class="button">Encender camara</button>
                            <button id="btnStopQR" type="button" class="button button-muted" disabled>Apagar</button>
                        </div>
                        <div class="qr-video-container">
                            <video id="qrVideo" playsinline style="width:100%; height:auto;"></video>
                            <div style="position:absolute; inset:0; pointer-events:none; border:2px solid var(--theme-title); margin:15%; opacity:0.6; border-radius:14px; box-shadow: 0 0 0 1000px rgba(0,0,0,0.4);"></div>
                        </div>
                        <div id="qrMsg" class="qr-status"></div>
                    </div>

                    <form id="scanForm" class="scan-form" data-no-fade="1" action="#" method="post" onsubmit="return false;">
                        <input type="text" id="idParticipante" class="scan-field" placeholder="Escribe ID o escanea el QR del gafete..." required>
                        <input type="hidden" id="idClase" value="<?php echo $id; ?>">
                        <button id="btnProcesarScan" class="button" type="button">Procesar</button>
                    </form>
                </div>
            </div>
        </section>

        <aside class="workspace-card helper-card">
            <div>
                <span class="section-kicker">Guia rapida</span>
                <h3>Flujo recomendado</h3>
                <p>La pantalla ya esta ordenada para que el operador entienda rapido que debe hacer en piso.</p>
            </div>

            <div class="status-pill">Escaneo movil</div>

            <ul class="helper-list">
                <li>
                    <strong>1. Busca o escanea</strong>
                    <span>Usa el buscador si conoces el nombre o proveedor. En telefono, el QR reduce pasos.</span>
                </li>
                <li>
                    <strong>2. Elige el modo</strong>
                    <span>Asistencia para inscritos existentes o inscripcion para agregar participantes a la sesion.</span>
                </li>
                <li>
                    <strong>3. Confirma en el listado</strong>
                    <span>La tabla inferior se refresca sola y los mensajes emergentes te confirman el resultado.</span>
                </li>
            </ul>
        </aside>
    </div>

    <section class="result-card">
        <div class="result-head">
            <div>
                <span class="section-kicker">Estado de la sesion</span>
                <h3>Participantes e inscripciones</h3>
                <p>El contenido se actualiza automaticamente despues de cada accion, sin recargar toda la pagina.</p>
            </div>
        </div>

        <div id="resultado">
            <div class="result-placeholder">
                <strong>Cargando informacion de la clase...</strong>
                <span>Espera un momento mientras se consultan inscritos, asistencia y disponibilidad.</span>
            </div>
        </div>
    </section>
</div>

<?php if (false): ?>
<div class="container">
    <h2 class="titulo">Gestión de Participantes</h2>
    
    <div class="search-container" style="position:relative;">
        <svg style="position:absolute; left:15px; top:15px; color:var(--txt-dim);" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" id="busqueda" placeholder="Buscar por nombre, teléfono o proveedor..." class="input-search">
    </div>

    <div class="control-panel">
        <h3>Modo de Registro</h3>
        <div style="display:flex; gap:30px; margin-bottom:15px;">
            <label><input type="radio" name="modo" value="asistencia" checked> Tomar asistencia</label>
            <label><input type="radio" name="modo" value="agregar"> Inscribir en clase</label>
        </div>

        <div id="qrWrap" style="display:none; flex-direction:column; align-items:center;">
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px; width:100%; justify-content:center;">
                <select id="cameraSelect" style="padding:10px; border-radius:10px; border:1px solid rgba(255,255,255,0.2); background:rgba(0,0,0,0.3); color:#fff; flex:1; max-width:300px; font-size:14px;"></select>
                <button id="btnStartQR" type="button" class="button">Encender Cámara</button>
                <button id="btnStopQR" type="button" class="button" style="background:#555" disabled>Apagar</button>
            </div>
            <div class="qr-video-container">
                <video id="qrVideo" playsinline style="width:100%; height:auto;"></video>
                <div style="position:absolute; inset:0; pointer-events:none; border:2px solid var(--accent); margin:15%; opacity:0.6; border-radius:10px; box-shadow: 0 0 0 1000px rgba(0,0,0,0.4);"></div>
            </div>
        </div>

        <form id="scanForm" data-no-fade="1" action="#" method="post" onsubmit="return false;" style="display:flex; gap:12px; margin-top:10px;">
            <input type="text" id="idParticipante" placeholder="Escribe ID o escanea código..." required style="flex:1; padding:14px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#fff; font-size:16px;">
            <input type="hidden" id="idClase" value="<?php echo $id; ?>">
            <button id="btnProcesarScan" class="button" type="button" style="padding:0 30px;">Procesar</button>
        </form>
    </div>

    <div id="resultado">
        <!-- Dinámico -->
    </div>
</div>
<?php endif; ?>

<!-- Modal Registro Rápido (Global) -->
<div id="modalRegistro" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModalRegistro()">&times;</span>
        <h3 style="color:var(--theme-title); margin-bottom:25px; border-bottom:1px solid var(--theme-border); padding-bottom:10px;">Registro Nuevo Participante</h3>
        <form id="formRegistroRapido" data-no-fade="1">
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; font-size:14px;">Nombre Completo:</label>
                <input type="text" id="regNombre" name="nombre" required style="width:100%; padding:12px; border-radius:var(--theme-radius); border:1px solid var(--theme-border); background:rgba(0,0,0,0.2); color:#fff;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; font-size:14px;">WhatsApp/Teléfono (10 dígitos):</label>
                <input type="tel" id="regTelefono" name="telefono" required style="width:100%; padding:12px; border-radius:var(--theme-radius); border:1px solid var(--theme-border); background:rgba(0,0,0,0.2); color:#fff;" pattern="[0-9]{10}">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px; font-size:14px;">Empresa / Proveedor:</label>
                <input type="text" id="regProveedor" name="proveedor" required style="width:100%; padding:12px; border-radius:var(--theme-radius); border:1px solid var(--theme-border); background:rgba(0,0,0,0.2); color:#fff;">
            </div>
            <button type="submit" class="button" style="width:100%; padding:15px; font-weight:700;">REGISTRAR E INSCRIBIR</button>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const busInput = document.getElementById("busqueda");
    const resDiv = document.getElementById("resultado");
    const idClase = document.getElementById("idClase").value;
    const scanInput = document.getElementById("idParticipante");
    const scanForm = document.getElementById("scanForm");
    const btnProcesarScan = document.getElementById("btnProcesarScan");
    const estadoBusqueda = document.getElementById("estadoBusqueda");
    const qrMsg = document.getElementById("qrMsg");
    const modoHint = document.getElementById("modoHint");
    const modoOptions = Array.from(document.querySelectorAll(".mode-option"));
    const modoInputs = Array.from(document.querySelectorAll('input[name="modo"]'));

    function extraerIdParticipante(raw) {
        const limpio = String(raw || '').trim();
        if (!limpio) return 0;
        if (/^\d+$/.test(limpio)) return parseInt(limpio, 10);

        let match = limpio.match(/\bID\b\s*[:\-NÑ]?\s*(\d{1,10})(?=\D|$)/i);
        if (match) return parseInt(match[1], 10);

        match = limpio.match(/ID\s*[:\-NÑ]?\s*(\d{1,10})(?=\D|$)/i);
        if (match) return parseInt(match[1], 10);

        match = limpio.match(/(?:^|\D)(\d{1,10})(?=\D|$)/);
        return match ? parseInt(match[1], 10) : 0;
    }

    function setEstadoBusqueda(texto, estado) {
        estadoBusqueda.textContent = texto;
        estadoBusqueda.classList.remove("is-loading", "is-error");
        if (estado === "loading") estadoBusqueda.classList.add("is-loading");
        if (estado === "error") estadoBusqueda.classList.add("is-error");
    }

    function syncModoUI() {
        const checkedModo = document.querySelector('input[name="modo"]:checked');
        const modo = checkedModo ? checkedModo.value : "asistencia";

        modoOptions.forEach((option) => {
            const input = option.querySelector('input[name="modo"]');
            option.classList.toggle("is-active", !!(input && input.checked));
        });

        if (modo === "agregar") {
            modoHint.textContent = "Modo actual: inscribir al participante en la sesion. Si ya existe, se validara disponibilidad.";
            scanInput.placeholder = "Escribe ID o escanea el QR para inscribirlo...";
            btnProcesarScan.textContent = "Inscribir";
        } else {
            modoHint.textContent = "Modo actual: registrar asistencia para participantes previamente inscritos.";
            scanInput.placeholder = "Escribe ID o escanea el QR del gafete...";
            btnProcesarScan.textContent = "Procesar";
        }
    }

    function refresh() {
        const termino = busInput.value.trim();
        setEstadoBusqueda(termino ? "Buscando" : "Actualizando", "loading");

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "Consultar_Clase.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;

            if (xhr.status === 200) {
                resDiv.innerHTML = xhr.responseText;
                setEstadoBusqueda(termino ? "Filtro activo" : "Listo", "");
            } else {
                setEstadoBusqueda("Error de carga", "error");
                resDiv.innerHTML = '<div class="result-placeholder"><strong>No se pudo cargar la informacion.</strong><span>Intenta de nuevo en unos segundos.</span></div>';
            }
        };
        xhr.send("busqueda=" + encodeURIComponent(termino) + "&id=" + encodeURIComponent(idClase));
    }

    busInput.addEventListener("input", refresh);
    modoInputs.forEach((input) => input.addEventListener("change", syncModoUI));
    syncModoUI();
    refresh();

    // Acciones de Tabla
    window.btnMarcarAsistencia = (id) => enviarAccion("marcar_asistencia.php", id);
    window.btnInscribirYAsistir = (id) => enviarAccion("Inscribir_Participante_Ajax.php", id);

    function enviarAccion(url, idPart) {
        setEstadoBusqueda("Procesando", "loading");
        const xhr = new XMLHttpRequest();
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;

            let msg = xhr.responseText, ok = xhr.status == 200;
            try { const j = JSON.parse(msg); msg=j.msg; ok=j.ok; } catch(e){}

            if (msg) {
                toast(msg, ok);
            }

            if(ok) refresh();
            else setEstadoBusqueda("Revision requerida", "error");
        };
        xhr.send("id_participante=" + encodeURIComponent(idPart) + "&id_clase=" + encodeURIComponent(idClase));
    }

    function procesarScan(e) {
        e.preventDefault();
        const raw = scanInput.value.trim();
        if(!raw) return;
        const idParticipante = extraerIdParticipante(raw);
        if(!idParticipante) {
            toast("No se pudo extraer un ID valido del QR", false);
            scanInput.focus();
            return;
        }
        const modo = document.querySelector('input[name="modo"]:checked').value;
        enviarAccion((modo === 'agregar' ? "Agregar_Participante_Manual.php" : "marcar_asistencia.php"), idParticipante);
        scanInput.value = '';
        scanInput.focus();
    }

    scanForm.addEventListener("submit", procesarScan);
    btnProcesarScan.addEventListener("click", procesarScan);

    // ZXing QR
    if (window.matchMedia("(max-width: 991px)").matches) {
        const qrContent = document.getElementById('qrWrap');
        qrContent.style.display = 'flex';
        const codeReader = new ZXingBrowser.BrowserMultiFormatReader();
        let selectedId;
        const video = document.getElementById('qrVideo');
        const btnStartQR = document.getElementById('btnStartQR');
        const btnStopQR = document.getElementById('btnStopQR');

        qrMsg.textContent = 'Camara lista para escaneo movil.';

        ZXingBrowser.BrowserCodeReader.listVideoInputDevices().then(devices => {
            const select = document.getElementById('cameraSelect');
            if (!devices.length) {
                qrMsg.textContent = 'No se detectaron camaras disponibles.';
                return;
            }
            selectedId = devices[0].deviceId;
            devices.forEach(d => {
                const opt = document.createElement('option');
                opt.text = d.label || 'Camara disponible';
                opt.value = d.deviceId;
                select.appendChild(opt);
            });
            select.onchange = () => selectedId = select.value;

            btnStartQR.onclick = () => {
                qrMsg.textContent = 'Apunta el QR al recuadro.';
                codeReader.decodeFromVideoDevice(selectedId, video, (res, err) => {
                    if (res) {
                        qrMsg.textContent = 'QR detectado. Procesando...';
                        scanInput.value = res.text;
                        scanForm.dispatchEvent(new Event('submit'));
                    }
                });
                btnStartQR.disabled = true;
                btnStopQR.disabled = false;
            };
            btnStopQR.onclick = () => location.reload();
        }).catch(() => {
            qrMsg.textContent = 'No fue posible listar las camaras.';
        });
    }

    function toast(m, ok) {
        const t = document.createElement('div');
        t.className = 'ga-toast' + (ok ? '' : ' error');
        t.textContent = m;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 2500);
    }

    // Modal
    const modal = document.getElementById("modalRegistro");
    window.abrirModalRegistro = () => { modal.style.display = "block"; };
    window.cerrarModalRegistro = () => { modal.style.display = "none"; };
    
    document.getElementById("formRegistroRapido").onsubmit = function(e) {
        e.preventDefault();
        const d = new URLSearchParams(new FormData(this));
        d.append("id_clase", idClase);
        fetch("Registro_Rapido_Ajax.php", { method:'POST', body:d })
        .then(async r => {
            const text = await r.text();
            try {
                return JSON.parse(text);
            } catch (_) {
                return { ok: false, msg: text || "Respuesta invalida del servidor" };
            }
        }).then(res => {
            toast(res.msg, res.ok);
            if(res.ok) {
                cerrarModalRegistro();
                refresh();
                this.reset();
                scanInput.focus();
            }
        });
    };
});
</script>
<script src="animacion.js"></script>
</body>
</html>
