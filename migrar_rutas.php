<?php
/**
 * Script de Migración: Actualizar Rutas de Gafetes y Horarios
 * 
 * Este script actualiza las rutas de los gafetes y horarios de los participantes
 * existentes para que funcionen con el nuevo sistema de configuración.
 */

require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migración de Rutas</title>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style></head><body>";
echo "<h1>🔄 Migración de Rutas - Gafetes y Horarios</h1>";

// Verificar si se debe ejecutar
if (!isset($_GET['ejecutar'])) {
    echo "<div class='info'>";
    echo "<h2>⚠️ Antes de ejecutar:</h2>";
    echo "<p>Este script actualizará las rutas de TODOS los participantes en la base de datos.</p>";
    echo "<p><strong>Entorno detectado:</strong> " . (IS_LOCAL ? "🏠 DESARROLLO LOCAL" : "🌐 PRODUCCIÓN") . "</p>";
    echo "<p><strong>Ruta de gafetes:</strong> <code>" . GAFETES_OUTPUT . "</code></p>";
    echo "<p><strong>Ruta de horarios:</strong> <code>" . HORARIOS_OUTPUT . "</code></p>";
    echo "</div>";
    echo "<p><a href='?ejecutar=1&confirmar=1' style='background:#4CAF50;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:20px;'>▶️ Ejecutar Migración</a></p>";
    echo "</body></html>";
    exit;
}

if (!isset($_GET['confirmar'])) {
    echo "<p class='error'>Debes confirmar la ejecución.</p>";
    exit;
}

echo "<h2>Iniciando migración...</h2>";

// Obtener todos los participantes
$sql = "SELECT ID, Ruta_Gafete, Ruta_Horario FROM participante WHERE Ruta_Gafete IS NOT NULL OR Ruta_Horario IS NOT NULL";
$result = $conn->query($sql);

if (!$result) {
    echo "<p class='error'>Error en la consulta: " . $conn->error . "</p>";
    exit;
}

$totalParticipantes = $result->num_rows;
$gafetesActualizados = 0;
$horariosActualizados = 0;
$errores = 0;

echo "<p class='info'>Total de participantes a procesar: <strong>$totalParticipantes</strong></p>";
echo "<hr>";

while ($row = $result->fetch_assoc()) {
    $id = $row['ID'];
    $rutaGafeteActual = $row['Ruta_Gafete'];
    $rutaHorarioActual = $row['Ruta_Horario'];
    
    $actualizarGafete = false;
    $actualizarHorario = false;
    $nuevaRutaGafete = null;
    $nuevaRutaHorario = null;
    
    // Verificar y actualizar ruta de gafete
    if ($rutaGafeteActual) {
        $nombreArchivo = basename($rutaGafeteActual);
        $nuevaRutaGafete = GAFETES_OUTPUT . '/' . $nombreArchivo;
        
        // Solo actualizar si la ruta es diferente
        if ($rutaGafeteActual !== $nuevaRutaGafete) {
            $actualizarGafete = true;
        }
    }
    
    // Verificar y actualizar ruta de horario
    if ($rutaHorarioActual) {
        $nombreArchivo = basename($rutaHorarioActual);
        $nuevaRutaHorario = HORARIOS_OUTPUT . '/' . $nombreArchivo;
        
        // Solo actualizar si la ruta es diferente
        if ($rutaHorarioActual !== $nuevaRutaHorario) {
            $actualizarHorario = true;
        }
    }
    
    // Ejecutar actualización si es necesario
    if ($actualizarGafete || $actualizarHorario) {
        $updates = [];
        $params = [];
        $types = "";
        
        if ($actualizarGafete) {
            $updates[] = "Ruta_Gafete = ?";
            $params[] = $nuevaRutaGafete;
            $types .= "s";
        }
        
        if ($actualizarHorario) {
            $updates[] = "Ruta_Horario = ?";
            $params[] = $nuevaRutaHorario;
            $types .= "s";
        }
        
        $params[] = $id;
        $types .= "i";
        
        $updateSql = "UPDATE participante SET " . implode(", ", $updates) . " WHERE ID = ?";
        $stmt = $conn->prepare($updateSql);
        
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                if ($actualizarGafete) $gafetesActualizados++;
                if ($actualizarHorario) $horariosActualizados++;
                echo "<p class='success'>✅ Participante ID $id actualizado</p>";
            } else {
                $errores++;
                echo "<p class='error'>❌ Error al actualizar participante ID $id: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $errores++;
            echo "<p class='error'>❌ Error al preparar consulta para participante ID $id</p>";
        }
    }
}

echo "<hr>";
echo "<h2>📊 Resumen de Migración</h2>";
echo "<ul>";
echo "<li><strong>Total procesados:</strong> $totalParticipantes</li>";
echo "<li class='success'><strong>Gafetes actualizados:</strong> $gafetesActualizados</li>";
echo "<li class='success'><strong>Horarios actualizados:</strong> $horariosActualizados</li>";
echo "<li" . ($errores > 0 ? " class='error'" : "") . "><strong>Errores:</strong> $errores</li>";
echo "</ul>";

if ($errores === 0) {
    echo "<p class='success'><strong>✅ Migración completada exitosamente!</strong></p>";
} else {
    echo "<p class='error'><strong>⚠️ Migración completada con errores. Revisa los mensajes arriba.</strong></p>";
}

$conn->close();
echo "</body></html>";
?>
