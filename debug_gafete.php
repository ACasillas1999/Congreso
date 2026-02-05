<?php
// Script de depuración para verificar la ruta del gafete
require_once __DIR__ . "/Conexiones/Conexion.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 2628;

$sql = "SELECT ID, Nombre, Ruta_Gafete FROM participante WHERE ID = $id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<h2>Información del Participante ID: {$row['ID']}</h2>";
    echo "<p><strong>Nombre:</strong> {$row['Nombre']}</p>";
    echo "<p><strong>Ruta guardada en BD:</strong> <code>{$row['Ruta_Gafete']}</code></p>";
    echo "<p><strong>¿Archivo existe?</strong> " . (file_exists($row['Ruta_Gafete']) ? "✅ SÍ" : "❌ NO") . "</p>";
    
    // Verificar ruta local
    $localPath = __DIR__ . '/Machote/Generados/Gafete_personalizado_' . $id . '.jpg';
    echo "<p><strong>Ruta local esperada:</strong> <code>$localPath</code></p>";
    echo "<p><strong>¿Archivo existe en ruta local?</strong> " . (file_exists($localPath) ? "✅ SÍ" : "❌ NO") . "</p>";
    
    if (file_exists($localPath)) {
        echo "<hr>";
        echo "<h3>Solución:</h3>";
        echo "<p>El archivo existe pero la ruta en la BD es incorrecta. Actualizar con:</p>";
        echo "<pre>UPDATE participante SET Ruta_Gafete = '$localPath' WHERE ID = $id;</pre>";
        
        // Botón para actualizar
        if (isset($_GET['fix'])) {
            $updateSql = "UPDATE participante SET Ruta_Gafete = ? WHERE ID = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $localPath, $id);
            if ($stmt->execute()) {
                echo "<p style='color: green;'><strong>✅ Ruta actualizada correctamente!</strong></p>";
                echo "<p><a href='DescargarGafete.php?id=$id'>Descargar Gafete</a></p>";
            } else {
                echo "<p style='color: red;'>Error al actualizar: " . $conn->error . "</p>";
            }
        } else {
            echo "<p><a href='?id=$id&fix=1' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Arreglar Ruta Ahora</a></p>";
        }
    }
} else {
    echo "No se encontró el participante con ID: $id";
}

$conn->close();
?>
