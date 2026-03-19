<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

$sql = "CREATE TABLE IF NOT EXISTS configuracion_css (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_variable VARCHAR(50) NOT NULL UNIQUE,
    valor_css VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabla configuracion_css creada correctamente o ya existia.\n";

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

    $stmt = $conn->prepare("INSERT IGNORE INTO configuracion_css (nombre_variable, valor_css) VALUES (?, ?)");
    foreach ($defaults as $var => $val) {
        $stmt->bind_param("ss", $var, $val);
        $stmt->execute();
    }

    echo "Valores por defecto verificados.\n";
} else {
    echo "Error creando la tabla: " . $conn->error . "\n";
}

$conn->close();
?>
