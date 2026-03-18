<?php
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$action = $_POST['action'] ?? '';

if ($action === 'restore') {
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
        '--titulo-neon' => '#7cecff'
    ];
    
    $stmt = $conn->prepare("UPDATE configuracion_css SET valor_css = ? WHERE nombre_variable = ?");
    foreach ($defaults as $var => $val) {
        $stmt->bind_param("ss", $val, $var);
        $stmt->execute();
    }
} elseif ($action === 'save') {
    $vars = [
        '--bg-gradient-start',
        '--bg-gradient-end',
        '--azul-oscuro',
        '--azul-medio',
        '--titulo-neon',
        '--naranja'
    ];
    
    $stmt = $conn->prepare("UPDATE configuracion_css SET valor_css = ? WHERE nombre_variable = ?");
    foreach ($vars as $var) {
        if (isset($_POST[$var])) {
            $val = $_POST[$var];
            $stmt->bind_param("ss", $val, $var);
            $stmt->execute();
        }
    }
}

header("location: personalizar.php?status=success");
exit;
?>
