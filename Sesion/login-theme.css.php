<?php
header("Content-Type: text/css; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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

try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn instanceof mysqli && !$conn->connect_error) {
        $conn->set_charset("utf8mb4");
        $table_exists = $conn->query("SHOW TABLES LIKE 'configuracion_css'");
        if ($table_exists && $table_exists->num_rows > 0) {
            $result = $conn->query("SELECT nombre_variable, valor_css FROM configuracion_css");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $css_vars[$row['nombre_variable']] = $row['valor_css'];
                }
            }
        }
        $conn->close();
    }
} catch (Throwable $e) {
    // Si algo falla, usar defaults silenciosamente.
}
?>
:root {
<?php foreach ($css_vars as $var => $val): ?>
  <?php echo $var; ?>: <?php echo $val; ?>;
<?php endforeach; ?>
}
