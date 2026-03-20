<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

// Valores por defecto
$css_vars = [
    '--azul-oscuro' => '#054a6b',
    '--azul-medio' => '#1ca9dc',
    '--azul-suave' => '#dff8ff',
    '--gris-suave' => '#f5f6fa',
    '--naranja' => '#38d9ff',
    '--verde' => '#0ea5c6',
    '--bg-gradient-start' => '#95ecff',
    '--bg-gradient-end' => '#054a6b',
    '--container-bg' => 'rgba(8, 27, 50, 0.7)',
    '--titulo-neon' => '#7cecff',
    '--login-animation' => 'liquid-ether'
];

// Intentar cargar desde la base de datos
try {
    // Si la conexion global esta cerrada o no existe, intentar crear una nueva
    $temp_conn = null;
    $close_temp_conn = false;

    if (isset($conn) && $conn instanceof mysqli) {
        try {
            if ($conn->ping()) {
                $temp_conn = $conn;
            }
        } catch (Throwable $ignored) {
            $temp_conn = null;
        }
    }

    if (!$temp_conn) {
        $temp_conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        $temp_conn->set_charset("utf8mb4");
        $close_temp_conn = true;
    }

    if ($temp_conn && !$temp_conn->connect_error) {
        $css_res = $temp_conn->query("SELECT nombre_variable, valor_css FROM configuracion_css");
        if ($css_res && $css_res->num_rows > 0) {
            while ($css_row = $css_res->fetch_assoc()) {
                $css_vars[$css_row['nombre_variable']] = $css_row['valor_css'];
            }
        }
        if ($close_temp_conn) {
            $temp_conn->close();
        }
    }
} catch (Throwable $e) {
    // Si hay error, fallar silenciosamente y usar defaults
}
?>

<style id="custom-css-vars">
:root {
    <?php foreach ($css_vars as $var => $val): ?>
    <?php echo $var; ?>: <?php echo $val; ?>;
    <?php endforeach; ?>
    --theme-bg-start: var(--bg-gradient-start);
    --theme-bg-end: var(--bg-gradient-end);
    --theme-primary: var(--azul-medio);
    --theme-primary-dark: var(--azul-oscuro);
    --theme-accent: var(--naranja);
    --theme-title: var(--titulo-neon);
    --theme-surface: var(--container-bg);
    --theme-surface-strong: rgba(8, 27, 50, 0.88);
    --theme-surface-soft: rgba(255, 255, 255, 0.06);
    --theme-border: rgba(255, 255, 255, 0.12);
    --theme-text: #ffffff;
    --theme-text-soft: rgba(255, 255, 255, 0.78);
    --theme-shadow: 0 14px 34px rgba(0, 0, 0, 0.28);
    --theme-radius: 4px; /* Estética más cuadrada y profesional */
    --theme-radius-full: 4px; 

    /* Layout Variables */
    --sidebar-width: 280px; 
    --sidebar-glass: var(--theme-primary-dark); /* Base de personalización */
    --sidebar-hover: rgba(255, 255, 255, 0.1);
    --theme-chip: rgba(255, 255, 255, 0.08);
}


* { box-sizing: border-box; }
/* Base compartida */
body {
    background: linear-gradient(180deg, var(--theme-bg-start) 0%, var(--theme-bg-end) 100%);
    background-attachment: fixed;
    background-size: cover;
    color: var(--theme-text);
    margin: 0 !important;
    padding: 0 !important; /* Reset para evitar solapamientos en mobile */
    min-height: 100vh;
    font-size: 14px; /* Más compacto */
}

@media (min-width: 768px) {
    body {
        padding-left: var(--sidebar-width) !important;
    }
}


.sidebar {
    background: linear-gradient(145deg, var(--theme-primary-dark), var(--theme-primary));
}

/* Container se define más abajo con las tablas */

/* Global Modal Fix */
.modal {
    display: none;
    position: fixed;
    z-index: 99999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(8px);
}
.modal-content {
    background: var(--theme-surface-strong);
    margin: 10% auto;
    padding: 30px;
    border: 1px solid var(--theme-border);
    border-radius: var(--theme-radius);
    max-width: 500px;
    position: relative;
    box-shadow: var(--theme-shadow);
}
.close { position: absolute; right: 20px; top: 15px; color: #fff; font-size: 28px; cursor: pointer; }

.titulo,
.chart-title {
    color: var(--theme-title);
    text-shadow: 0 0 5px var(--theme-title), 0 0 10px var(--theme-title);
    margin-bottom: 20px;
}

.titulo2 {
    color: var(--theme-text-soft);
    text-align: center;
    font-size: 14px;
    margin-bottom: 25px;
    font-weight: 500;
}

/* Modern Table Styling (Glassmorphism) */
.mi-tabla, .tabla-agenda {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px; /* Espacio entre filas para efecto de tarjetas */
    margin-top: 20px;
    background: transparent !important; /* El contenedor ya tiene fondo */
    border: none !important;
}

.mi-tabla th, .tabla-agenda th {
    background: var(--theme-surface-soft) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: var(--theme-title) !important;
    text-transform: uppercase;
    font-size: 10px; /* Reducido de 11px */
    font-weight: 700;
    letter-spacing: 1.2px;
    padding: 8px 10px; /* Reducido para ahorrar espacio */
    text-align: center;
    border: none !important;
    border-bottom: 2px solid var(--theme-border) !important;
}

.mi-tabla th:first-child, .tabla-agenda th:first-child { border-radius: var(--theme-radius) 0 0 var(--theme-radius); }
.mi-tabla th:last-child, .tabla-agenda th:last-child { border-radius: 0 var(--theme-radius) var(--theme-radius) 0; }

.mi-tabla tr, .tabla-agenda tr {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mi-tabla td, .tabla-agenda td {
    padding: 8px 10px; /* Reducido para ahorrar espacio */
    background: var(--theme-surface-soft);
    border: none !important;
    color: var(--theme-text);
    font-size: 13px; /* Reducido para ahorrar espacio */
    text-align: center;
    border-top: 1px solid rgba(255,255,255,0.03) !important;
    border-bottom: 1px solid rgba(0,0,0,0.1) !important;
}

.mi-tabla tr td:first-child, .tabla-agenda tr td:first-child { 
    border-radius: var(--theme-radius) 0 0 var(--theme-radius);
    border-left: 1px solid var(--theme-border) !important;
}
.mi-tabla tr td:last-child, .tabla-agenda tr td:last-child { 
    border-radius: 0 var(--theme-radius) var(--theme-radius) 0;
    border-right: 1px solid var(--theme-border) !important;
}

.mi-tabla tr:hover td, .tabla-agenda tr:hover td {
    background: rgba(255, 255, 255, 0.12) !important;
    transform: scale(1.005);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    color: #fff;
}

.mi-tabla td a {
    color: var(--theme-title);
    text-decoration: none;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: var(--theme-radius);
    background: rgba(56, 217, 255, 0.1);
    transition: all 0.2s ease;
    white-space: nowrap;
}

.mi-tabla td a:hover {
    background: var(--theme-title);
    color: #000 !important;
    text-decoration: none !important;
}

/* Botones compactos para tablas */
.btn-tabla {
    display: inline-block !important;
    padding: 4px 8px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: 4px;
    background: rgba(56, 217, 255, 0.1);
    color: var(--theme-title) !important;
    border: 1px solid rgba(56, 217, 255, 0.2);
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-tabla:hover {
    background: var(--theme-title) !important;
    color: #000 !important;
    box-shadow: 0 0 10px var(--theme-title);
}

/* Contenedor con scroll para tablas anchas */
.container {
    background-color: var(--theme-surface);
    overflow-x: auto;
    width: 98% !important;
    max-width: none !important;
    margin: 10px 1% 20px !important; /* Margen fijo, no auto */
    padding: 24px;
    border-radius: var(--theme-radius);
    border: 1px solid var(--theme-border);
    box-shadow: var(--theme-shadow);
}

/* Modern Badges/Chips */
.badge {
    padding: 6px 12px;
    border-radius: var(--theme-radius);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.badge-finalizado { background: rgba(0, 255, 157, 0.2); color: #00ff9d; border: 1px solid rgba(0, 255, 157, 0.3); }
.badge-encurso { background: rgba(255, 213, 0, 0.2); color: #ffd500; border: 1px solid rgba(255, 213, 0, 0.3); }
.badge-cancelado { background: rgba(255, 82, 82, 0.2); color: #ff5252; border: 1px solid rgba(255, 82, 82, 0.3); }


.button,
input[type="submit"],
.boton-consultar,
#btnContinuar,
#btnGuardar {
    background-color: var(--theme-primary);
}

.button:hover,
input[type="submit"]:hover,
.boton-consultar:hover,
#btnContinuar:hover,
#btnGuardar:hover {
    background-color: var(--theme-primary-dark);
}

.header {
    background: linear-gradient(145deg, var(--theme-primary-dark), var(--theme-primary));
}

.logo,
h1,
h2,
h3,
.title {
    color: var(--theme-title);
}

form,
.form-panel,
.panel,
.card {
    background-color: var(--theme-surface-strong);
    border-color: var(--theme-border);
}

label,
.muted,
.subtitle,
.note {
    color: var(--theme-text-soft);
}

input[type="text"],
input[type="date"],
input[type="time"],
input[type="number"],
input[type="tel"],
input[type="password"],
input[type="email"],
textarea,
select {
    background-color: var(--theme-surface-strong) !important;
    color: var(--theme-text) !important;
    border: 1px solid var(--theme-border);
}

select option {
    background-color: var(--theme-primary-dark) !important;
    color: #fff !important;
}

input[type="text"]:focus,
input[type="date"]:focus,
input[type="time"]:focus,
input[type="number"]:focus,
input[type="tel"]:focus,
input[type="password"]:focus,
input[type="email"]:focus,
textarea:focus,
select:focus {
    box-shadow: 0 0 0 2px rgba(56, 217, 255, 0.25);
    border-color: var(--theme-title);
}

.theme-accent,
a.theme-accent {
    color: var(--theme-accent);
}

.theme-card {
    background: var(--theme-surface);
    border: 1px solid var(--theme-border);
    box-shadow: var(--theme-shadow);
}



/* Glassmorphism Sidebar Modernization */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: linear-gradient(180deg, var(--theme-primary-dark), var(--theme-primary)) !important;
    backdrop-filter: blur(12px) saturate(160%);
    -webkit-backdrop-filter: blur(12px) saturate(160%);
    border-right: 1px solid var(--theme-border);
    padding: 0 12px 24px;
    z-index: 1050;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--theme-primary) transparent;
    transform: translateX(-100%); /* Oculto por defecto en móvil */
}

@media (min-width: 768px) {
    .sidebar {
        transform: translateX(0); /* Siempre visible en PC */
    }
}

.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: var(--theme-primary);
    border-radius: 10px;
}


.sidebar .sidebar-logo {
    padding: 45px 12px 25px; /* Más espacio al top para evitar cortes */
    margin-bottom: 5px; /* Menos espacio abajo para acercar el menú */
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    background: rgba(0,0,0,0.1); /* Sutil distinción del logo */
}

.sidebar .sidebar-logo img {
    max-width: 95%; /* Asegurar que no se corte */
    height: auto;
    display: block;
    margin: 0 auto;
    filter: drop-shadow(0 0 8px var(--theme-title));
}

.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    flex-grow: 1;
}

.sidebar li {
    margin-bottom: 8px;
}

.sidebar li a, .sidebar-link-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    color: var(--theme-text-soft);
    text-decoration: none;
    border-radius: var(--theme-radius);
    font-weight: 500;
    transition: all 0.2s ease;
    background: transparent;
    border: none;
    width: 100%;
    cursor: pointer;
    font-family: inherit;
    font-size: 14.5px; /* Reducido de 16px */
}

.sidebar li a:hover, .sidebar-link-btn:hover {
    background: var(--sidebar-hover);
    color: var(--theme-text);
    transform: translateX(4px);
}


.sidebar li a.active {
    background: var(--theme-primary);
    color: #fff;
    box-shadow: 0 4px 15px rgba(56, 217, 255, 0.3);
}

/* Accordion Styles */
.sidebar-sections {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-bottom: 20px;
}

.sidebar-section {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    padding-bottom: 8px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    cursor: pointer;
    color: var(--theme-text-soft);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    transition: all 0.2s ease;
    user-select: none;
    opacity: 0.7;
}

.section-header:hover {
    color: var(--theme-text);
    opacity: 1;
}

.section-header .chevron {
    transition: transform 0.3s ease;
}

.section-header.active .chevron {
    transform: rotate(180deg);
}

.section-content {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out, opacity 0.2s;
    opacity: 0;
}

.section-content.open {
    max-height: 1000px; /* Suficiente para todos los links */
    opacity: 1;
    transition: max-height 0.5s ease-in, opacity 0.3s;
}

.sidebar-form-item {
    list-style: none;
}

.sidebar .sidebar-footer {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid var(--theme-border);
}

.sidebar .logout-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: rgba(255, 82, 82, 0.1);
    color: #ff5252 !important;
    border-radius: var(--theme-radius);
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}

.sidebar .logout-btn:hover {
    background: #ff5252;
    color: #fff !important;
}

/* Compensación para el contenido */
/* Layout adjustments */
.content, .wrap {
    width: 98% !important;
    max-width: none !important;
    margin: 10px auto !important;
}
@media (max-width: 767px) {
    .container, .content, .wrap {
        width: 95% !important;
        margin: 10px auto !important;
        padding: 15px;
    }
}

/* Responsividad básica */
/* Mobile Toggle Button */
.sidebar-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1100;
    background: var(--theme-primary);
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    cursor: pointer;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    box-shadow: var(--theme-shadow);
}

.sidebar-toggle span {
    display: block;
    width: 24px;
    height: 2px;
    background: #fff;
    transition: all 0.3s ease;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 999;
}

@media (max-width: 767px) {
    .sidebar-toggle {
        display: flex;
    }
    
    .sidebar.open {
        transform: translateX(0);
        box-shadow: 20px 0 50px rgba(0,0,0,0.5);
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    /* Animación del hamburguesa */
    .sidebar-toggle.active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .sidebar-toggle.active span:nth-child(2) { opacity: 0; }
    .sidebar-toggle.active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
}
</style>
