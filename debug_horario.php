<?php
// Script de depuración para verificar horarios
require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 2628;

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Horario</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} code{background:#f4f4f4;padding:2px 6px;border-radius:3px;}</style></head><body>";
echo "<h1>🔍 Depuración de Horario - Participante #$id</h1>";

// Consultar participante
$sql = "SELECT ID, Nombre, Ruta_Horario FROM participante WHERE ID = $id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<h2>Información del Participante</h2>";
    echo "<p><strong>ID:</strong> {$row['ID']}</p>";
    echo "<p><strong>Nombre:</strong> {$row['Nombre']}</p>";
    echo "<p><strong>Ruta guardada en BD:</strong> <code>" . ($row['Ruta_Horario'] ?: '(vacío)') . "</code></p>";
    
    if ($row['Ruta_Horario']) {
        echo "<p><strong>¿Archivo existe en ruta BD?</strong> " . (file_exists($row['Ruta_Horario']) ? "✅ SÍ" : "❌ NO") . "</p>";
    }
    
    echo "<hr>";
    echo "<h2>Verificación de Archivos</h2>";
    
    // Buscar archivos de horario para este participante
    $pattern = HORARIOS_OUTPUT . '/Horario_' . $id . '_*.png';
    $files = glob($pattern);
    
    echo "<p><strong>Buscando en:</strong> <code>" . HORARIOS_OUTPUT . "</code></p>";
    echo "<p><strong>Patrón:</strong> <code>Horario_{$id}_*.png</code></p>";
    
    if (empty($files)) {
        echo "<p class='error'>❌ No se encontraron archivos de horario para este participante</p>";
        echo "<hr>";
        echo "<h3>💡 Solución: Generar el Horario</h3>";
        echo "<p>El horario no ha sido generado aún. Puedes generarlo ahora:</p>";
        
        if (isset($_GET['generar'])) {
            echo "<p class='info'>⏳ Generando horario...</p>";
            $genUrl = "http://" . $_SERVER['HTTP_HOST'] . "/Congreso/Generar_Horario.php?id={$id}&format=png&silent=1";
            
            if (function_exists('curl_init')) {
                $ch = curl_init($genUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $json = json_decode($response, true);
                    if ($json && isset($json['ok']) && $json['ok']) {
                        echo "<p class='success'>✅ Horario generado exitosamente!</p>";
                        echo "<p><strong>Archivo:</strong> <code>{$json['file']}</code></p>";
                        echo "<p><a href='DescargarHorario.php?id=$id'>📥 Descargar Horario</a></p>";
                    } else {
                        echo "<p class='error'>❌ Error al generar: " . htmlspecialchars($response) . "</p>";
                    }
                } else {
                    echo "<p class='error'>❌ Error HTTP $httpCode: " . htmlspecialchars($response) . "</p>";
                }
            } else {
                echo "<p class='error'>❌ cURL no está disponible</p>";
            }
        } else {
            echo "<p><a href='?id=$id&generar=1' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔧 Generar Horario Ahora</a></p>";
        }
        
    } else {
        echo "<p class='success'>✅ Se encontraron " . count($files) . " archivo(s) de horario:</p>";
        echo "<ul>";
        foreach ($files as $file) {
            $basename = basename($file);
            $size = filesize($file);
            $date = date('Y-m-d H:i:s', filemtime($file));
            echo "<li><code>$basename</code> - " . number_format($size) . " bytes - $date</li>";
        }
        echo "</ul>";
        
        // Usar el archivo más reciente
        $latestFile = end($files);
        echo "<hr>";
        echo "<h3>Actualizar Base de Datos</h3>";
        
        if ($row['Ruta_Horario'] !== $latestFile) {
            if (isset($_GET['fix'])) {
                $updateSql = "UPDATE participante SET Ruta_Horario = ? WHERE ID = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("si", $latestFile, $id);
                if ($stmt->execute()) {
                    echo "<p class='success'><strong>✅ Ruta actualizada en la base de datos!</strong></p>";
                    echo "<p><a href='DescargarHorario.php?id=$id'>📥 Descargar Horario</a></p>";
                } else {
                    echo "<p class='error'>Error al actualizar: " . $conn->error . "</p>";
                }
            } else {
                echo "<p class='info'>La ruta en la BD es diferente al archivo más reciente.</p>";
                echo "<p><a href='?id=$id&fix=1' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔧 Actualizar Ruta en BD</a></p>";
            }
        } else {
            echo "<p class='success'>✅ La ruta en la BD es correcta</p>";
            echo "<p><a href='DescargarHorario.php?id=$id'>📥 Descargar Horario</a></p>";
        }
    }
    
} else {
    echo "<p class='error'>❌ No se encontró el participante con ID: $id</p>";
}

echo "<hr>";
echo "<p><a href='debug_gafete.php?id=$id'>🔍 Ver Debug de Gafete</a> | <a href='migrar_rutas.php'>🔄 Migrar Todas las Rutas</a></p>";

$conn->close();
echo "</body></html>";
?>
