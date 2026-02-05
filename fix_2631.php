<?php
// Script para arreglar el participante 2631
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Arreglar Participante 2631</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;}</style></head><body>";
echo "<h1>🔧 Arreglando Participante 2631</h1>";

$id = 2631;

// Ruta incorrecta (sin barra)
$wrongPath = __DIR__ . '/Machote/GeneradosGafete_personalizado_2631.jpg';
// Ruta correcta
$correctPath = GAFETES_OUTPUT . '/Gafete_personalizado_2631.jpg';

echo "<h2>1. Moviendo archivo de gafete</h2>";
echo "<p><strong>Ruta incorrecta:</strong> <code>$wrongPath</code></p>";
echo "<p><strong>Ruta correcta:</strong> <code>$correctPath</code></p>";

if (file_exists($wrongPath)) {
    if (rename($wrongPath, $correctPath)) {
        echo "<p class='success'>✅ Archivo movido exitosamente</p>";
        
        // Actualizar base de datos
        echo "<h2>2. Actualizando base de datos</h2>";
        $sql = "UPDATE participante SET Ruta_Gafete = ? WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $correctPath, $id);
        
        if ($stmt->execute()) {
            echo "<p class='success'>✅ Base de datos actualizada</p>";
        } else {
            echo "<p class='error'>❌ Error al actualizar BD: " . $conn->error . "</p>";
        }
        $stmt->close();
        
    } else {
        echo "<p class='error'>❌ Error al mover archivo</p>";
    }
} else {
    echo "<p class='error'>❌ Archivo no encontrado en ruta incorrecta</p>";
    if (file_exists($correctPath)) {
        echo "<p class='success'>✅ Pero el archivo ya existe en la ruta correcta</p>";
        
        // Solo actualizar BD
        $sql = "UPDATE participante SET Ruta_Gafete = ? WHERE ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $correctPath, $id);
        if ($stmt->execute()) {
            echo "<p class='success'>✅ Base de datos actualizada</p>";
        }
        $stmt->close();
    }
}

echo "<hr>";
echo "<h2>✅ Proceso completado</h2>";
echo "<p><a href='DescargarGafete.php?id=$id'>📥 Descargar Gafete</a></p>";
echo "<p><a href='debug_horario.php?id=$id'>🔧 Generar Horario</a></p>";

$conn->close();
echo "</body></html>";
?>
