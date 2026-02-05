<?php
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

// Obtener el último participante registrado
$sql = "SELECT ID, Nombre, Ruta_Gafete, Ruta_Horario FROM participante ORDER BY ID DESC LIMIT 3";
$result = $conn->query($sql);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Últimos Participantes</title>";
echo "<style>
body{font-family:Arial;padding:20px;} 
table{border-collapse:collapse;width:100%;margin:20px 0;}
th,td{border:1px solid #ddd;padding:12px;text-align:left;}
th{background:#4CAF50;color:white;}
.success{color:green;} .error{color:red;} .warning{color:orange;}
code{background:#f4f4f4;padding:2px 6px;border-radius:3px;}
</style></head><body>";

echo "<h1>🔍 Últimos 3 Participantes Registrados</h1>";
echo "<table>";
echo "<tr><th>ID</th><th>Nombre</th><th>Ruta Gafete</th><th>¿Existe?</th><th>Ruta Horario</th><th>¿Existe?</th><th>Acciones</th></tr>";

while ($row = $result->fetch_assoc()) {
    $id = $row['ID'];
    $nombre = $row['Nombre'];
    $rutaGafete = $row['Ruta_Gafete'];
    $rutaHorario = $row['Ruta_Horario'];
    
    $gafeteExiste = $rutaGafete && file_exists($rutaGafete);
    $horarioExiste = $rutaHorario && file_exists($rutaHorario);
    
    echo "<tr>";
    echo "<td><strong>$id</strong></td>";
    echo "<td>$nombre</td>";
    echo "<td><code>" . ($rutaGafete ?: '(vacío)') . "</code></td>";
    echo "<td>" . ($gafeteExiste ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
    echo "<td><code>" . ($rutaHorario ?: '(vacío)') . "</code></td>";
    echo "<td>" . ($horarioExiste ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
    echo "<td>";
    echo "<a href='debug_gafete.php?id=$id'>Gafete</a> | ";
    echo "<a href='debug_horario.php?id=$id'>Horario</a>";
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Verificar archivos físicos
echo "<hr><h2>📁 Archivos Físicos en Disco</h2>";
echo "<h3>Gafetes en: <code>" . GAFETES_OUTPUT . "</code></h3>";
$gafetes = glob(GAFETES_OUTPUT . '/Gafete_personalizado_*.jpg');
rsort($gafetes);
echo "<ul>";
foreach (array_slice($gafetes, 0, 5) as $file) {
    $basename = basename($file);
    $size = filesize($file);
    echo "<li><code>$basename</code> - " . number_format($size) . " bytes</li>";
}
echo "</ul>";

echo "<h3>Horarios en: <code>" . HORARIOS_OUTPUT . "</code></h3>";
$horarios = glob(HORARIOS_OUTPUT . '/Horario_*.png');
rsort($horarios);
if (empty($horarios)) {
    echo "<p class='warning'>⚠️ No hay horarios generados</p>";
} else {
    echo "<ul>";
    foreach (array_slice($horarios, 0, 5) as $file) {
        $basename = basename($file);
        $size = filesize($file);
        echo "<li><code>$basename</code> - " . number_format($size) . " bytes</li>";
    }
    echo "</ul>";
}

$conn->close();
echo "</body></html>";
?>
