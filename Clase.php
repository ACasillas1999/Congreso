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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clase</title>
    <link rel="stylesheet" type="text/css" href="styles_clase.css">
    <?php include "header_css.php"; ?>
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    <script src="https://unpkg.com/@zxing/browser@latest"></script>
    <?php
    // Obtener ID_Evento para el sidebar
    $id_evento = 0;
    if ($id > 0) {
        $stmt_e = $conn->prepare("SELECT ID_Evento FROM agenda WHERE ID = ?");
        $stmt_e->bind_param("i", $id);
        $stmt_e->execute();
        $stmt_e->bind_result($id_evento);
        $stmt_e->fetch();
        $stmt_e->close();
    }
    ?>
    <style>
        .container {
            margin-top: 10px !important;
        }
        
        /* Panel de Control Estilizado */
        .control-panel {
            margin: 15px 0;
            background: rgba(255,255,255,0.03);
            padding: 18px; /* Reducido de 24px */
            border-radius: var(--theme-radius);
            border: 1px solid var(--theme-border);
        }
        .control-panel h3 {
            color: var(--theme-title) !important;
            margin-top: 0;
            font-size: 20px;
            margin-bottom: 18px;
            text-shadow: 0 0 8px rgba(255, 215, 0, 0.2);
        }
        .control-panel label {
            color: var(--theme-text);
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .control-mode-options {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        .qr-wrap {
            display: none;
            flex-direction: column;
            align-items: center;
        }
        .qr-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            width: 100%;
            justify-content: center;
        }
        .camera-select {
            padding: 10px;
            border-radius: var(--theme-radius);
            border: 1px solid var(--theme-border);
            background: rgba(0,0,0,0.3);
            color: #fff;
            flex: 1;
            max-width: 300px;
            font-size: 14px;
        }
        .scan-form {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .scan-input {
            flex: 1;
            padding: 14px;
            border-radius: var(--theme-radius);
            border: 1px solid var(--theme-border);
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-size: 16px;
        }
        .scan-button {
            padding: 0 30px;
        }
        #qrMsg:empty {
            display: none;
        }

        .input-search {
            width: 100%;
            padding: 14px 20px 14px 45px !important;
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid var(--theme-border) !important;
            color: #fff !important;
            border-radius: var(--theme-radius) !important;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .input-search:focus { background: rgba(255,255,255,0.1) !important; border-color: var(--theme-primary) !important; }

        .qr-video-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            border-radius: var(--theme-radius);
            overflow: hidden;
            border: 1px solid var(--theme-border);
            margin: 15px 0;
            background: #000;
        }
        
        #resultado { margin-top: 25px; }

        .ga-toast {
            position: fixed;
            right: 20px; bottom: 20px;
            padding: 12px 20px;
            border-radius: var(--theme-radius);
            color: #fff;
            background: var(--theme-surface-strong);
            border: 1px solid var(--theme-border);
            z-index: 100000;
            backdrop-filter: blur(10px);
            box-shadow: var(--theme-shadow);
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        @media (max-width: 768px) {
            .control-panel {
                display: flex;
                flex-direction: column;
                padding: 14px 12px;
                gap: 12px;
            }
            .control-panel h3 {
                font-size: 18px;
                margin-bottom: 0;
            }
            .control-mode-options {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 0;
                order: 1;
            }
            .control-panel label {
                width: auto;
                min-height: 46px;
                padding: 10px 12px;
                border: 1px solid var(--theme-border);
                border-radius: var(--theme-radius);
                background: rgba(255,255,255,0.04);
                justify-content: flex-start;
            }
            .qr-wrap {
                width: 100%;
                gap: 10px;
                align-items: stretch;
                order: 3;
            }
            .qr-toolbar {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                align-items: center;
                margin-bottom: 0;
            }
            .camera-select {
                grid-column: 1 / -1;
                flex: none;
                width: 100%;
                max-width: none;
                min-height: 44px;
                height: 44px;
            }
            .qr-toolbar .button {
                width: 100%;
                min-height: 44px;
                padding: 10px 14px;
            }
            .qr-video-container {
                width: 100%;
                max-width: none;
                margin: 0;
                aspect-ratio: 1 / 1;
                max-height: 240px;
            }
            .qr-video-container video {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .scan-form {
                display: none;
                grid-template-columns: minmax(0, 1fr) 120px;
                gap: 8px;
                align-items: stretch;
                margin-top: 0;
                order: 2;
            }
            #scanForm {
                display: none !important;
                grid-template-columns: minmax(0, 1fr) 120px;
                gap: 8px !important;
                margin-top: 0 !important;
            }
            .scan-input,
            .scan-button {
                width: 100%;
            }
            #idParticipante,
            #btnProcesarScan {
                width: 100%;
            }
            .scan-button {
                min-height: 44px;
                padding: 10px 16px;
            }
            #btnProcesarScan {
                min-height: 44px;
                padding: 10px 16px !important;
            }
        }

        @media (max-width: 480px) {
            .control-mode-options {
                grid-template-columns: 1fr;
            }
            .scan-form,
            #scanForm {
                display: none !important;
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body class="fade-in">
<?php include "sidebar.php"; ?>

<div class="container">
    <h2 class="titulo">Gestión de Participantes</h2>
    <div class="search-container" style="position:relative;">
        <svg style="position:absolute; left:15px; top:15px; color:var(--theme-text-soft);" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" id="busqueda" placeholder="Buscar por nombre, teléfono o proveedor..." class="input-search">
    </div>

    <div class="control-panel">
        <h3>Modo de Registro</h3>
        <div class="control-mode-options">
            <label><input type="radio" name="modo" value="asistencia" checked> Tomar asistencia</label>
            <label><input type="radio" name="modo" value="agregar"> Inscribir en clase</label>
        </div>

        <div id="qrWrap" class="qr-wrap">
            <div class="qr-toolbar">
                <select id="cameraSelect" class="camera-select"></select>
                <button id="btnStartQR" type="button" class="button">Encender Cámara</button>
                <button id="btnStopQR" type="button" class="button" style="background:#555" disabled>Apagar</button>
            </div>
            <div class="qr-video-container">
                <video id="qrVideo" playsinline style="width:100%; height:auto;"></video>
                <div style="position:absolute; inset:0; pointer-events:none; border:2px solid var(--theme-primary); margin:15%; opacity:0.6; border-radius:10px; box-shadow: 0 0 0 1000px rgba(0,0,0,0.4);"></div>
            </div>
            <div id="qrMsg" style="margin-top:10px; color:var(--theme-primary); font-weight:600;"></div>
        </div>

        <form id="scanForm" data-no-fade="1" action="#" method="post" onsubmit="return false;" style="display:flex; gap:12px; margin-top:20px;">
            <input type="text" id="idParticipante" placeholder="Escribe ID o escanea código..." required style="flex:1; padding:14px; border-radius:var(--theme-radius); border:1px solid var(--theme-border); background:rgba(255,255,255,0.05); color:#fff; font-size:16px;">
            <input type="hidden" id="idClase" value="<?php echo $id; ?>">
            <button id="btnProcesarScan" class="button" type="button" style="padding:0 30px;">Procesar</button>
        </form>
    </div>

    <div id="resultado">
        <!-- Contenido dinámico desde Consultar_Clase.php -->
    </div>
</div>

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

    function refresh() {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "Consultar_Clase.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                resDiv.innerHTML = xhr.responseText;
            }
        };
        xhr.send("busqueda=" + encodeURIComponent(busInput.value) + "&id=" + encodeURIComponent(idClase));
    }

    busInput.addEventListener("input", refresh);
    refresh();

    // Acciones de Tabla
    window.btnMarcarAsistencia = (id) => enviarAccion("marcar_asistencia.php", id);
    window.btnInscribirYAsistir = (id) => enviarAccion("Inscribir_Participante_Ajax.php", id);

    function enviarAccion(url, idPart) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                let msg = xhr.responseText, ok = xhr.status == 200;
                try { const j = JSON.parse(msg); msg=j.msg; ok=j.ok; } catch(e){}
                toast(msg, ok);
                if(ok) refresh();
            }
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

        ZXingBrowser.BrowserCodeReader.listVideoInputDevices().then(devices => {
            const select = document.getElementById('cameraSelect');
            selectedId = devices[0].deviceId;
            devices.forEach(d => {
                const opt = document.createElement('option');
                opt.text = d.label; opt.value = d.deviceId;
                select.appendChild(opt);
            });
            select.onchange = () => selectedId = select.value;

            document.getElementById('btnStartQR').onclick = () => {
                codeReader.decodeFromVideoDevice(selectedId, video, (res, err) => {
                    if (res) {
                        scanInput.value = res.text;
                        scanForm.dispatchEvent(new Event('submit'));
                    }
                });
                document.getElementById('btnStartQR').disabled = true;
                document.getElementById('btnStopQR').disabled = false;
            };
            document.getElementById('btnStopQR').onclick = () => location.reload();
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
