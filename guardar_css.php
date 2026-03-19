<?php
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$action = $_POST['action'] ?? '';
$allowed_animations = [
    'liquid-ether',
    'aurora-flow',
    'particle-network',
    'neon-grid',
    'leather-upholstery',
    'glass-bubbles',
    'radar-rings',
    'diagonal-shimmer',
    'mosaic-pulse',
    'none'
];
$defaults = [
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

if ($action === 'restore') {
    $stmt = $conn->prepare("INSERT INTO configuracion_css (nombre_variable, valor_css) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor_css = VALUES(valor_css)");
    foreach ($defaults as $var => $val) {
        $stmt->bind_param("ss", $var, $val);
        $stmt->execute();
    }
} elseif ($action === 'save') {
    $vars = [
        '--bg-gradient-start',
        '--bg-gradient-end',
        '--azul-oscuro',
        '--azul-medio',
        '--titulo-neon',
        '--naranja',
        '--login-animation'
    ];

    $stmt = $conn->prepare("INSERT INTO configuracion_css (nombre_variable, valor_css) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor_css = VALUES(valor_css)");
    foreach ($vars as $var) {
        if (isset($_POST[$var])) {
            $val = trim($_POST[$var]);
            if ($var === '--login-animation' && !in_array($val, $allowed_animations, true)) {
                $val = $defaults['--login-animation'];
            }
            $stmt->bind_param("ss", $var, $val);
            $stmt->execute();
        }
    }
}

header("location: personalizar.php?status=success");
exit;
?>
