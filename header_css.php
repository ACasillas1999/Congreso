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
    '--titulo-neon' => '#7cecff'
];

// Intentar cargar desde la base de datos
try {
    // Si la conexión global está cerrada o no existe, intentar crear una nueva
    $temp_conn = $conn;
    if (!$temp_conn || $temp_conn->connect_error || !@$temp_conn->ping()) {
        $temp_conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        $temp_conn->set_charset("utf8mb4");
    }

    if ($temp_conn && !$temp_conn->connect_error) {
        $result = $temp_conn->query("SELECT nombre_variable, valor_css FROM configuracion_css");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $css_vars[$row['nombre_variable']] = $row['valor_css'];
            }
        }
        // Si nosotros creamos la conexión temporal, la cerramos
        if ($temp_conn !== $conn) {
            $temp_conn->close();
        }
    }
} catch (Exception $e) {
    // Si hay error, fallar silenciosamente y usar defaults
}
?>

<style id="custom-css-vars">
:root {
    <?php foreach ($css_vars as $var => $val): ?>
    <?php echo $var; ?>: <?php echo $val; ?>;
    <?php endforeach; ?>
}

/* Aplicar variables a elementos específicos para asegurar compatibilidad */
body {
    background: linear-gradient(180deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%) !important;
}

.sidebar {
    background: linear-gradient(145deg, var(--azul-oscuro), var(--azul-medio)) !important;
}

.container {
    background-color: var(--container-bg) !important;
}

.titulo, .chart-title {
    color: var(--titulo-neon) !important;
    text-shadow: 0 0 5px var(--titulo-neon), 0 0 10px var(--titulo-neon) !important;
}

.mi-tabla th {
    background: linear-gradient(180deg, var(--azul-medio) 0%, var(--azul-oscuro) 100%) !important;
}

.button, input[type="submit"], .boton-consultar, #btnContinuar, #btnGuardar {
    background-color: var(--azul-medio) !important;
}

.button:hover, input[type="submit"]:hover, .boton-consultar:hover, #btnContinuar:hover, #btnGuardar:hover {
    background-color: var(--azul-oscuro) !important;
}
</style>
