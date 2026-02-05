<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Arreglar AUTO_INCREMENT en Producción</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #45a049; }
        button.danger { background: #f44336; }
        button.danger:hover { background: #da190b; }
    </style>
</head>
<body>
    <h1>🔧 Arreglar Campo ID AUTO_INCREMENT</h1>
    
    <?php
    require_once __DIR__ . "/Conexiones/Conexion.php";
    require_once __DIR__ . "/config.php";
    
    echo "<div class='info'>";
    echo "<strong>Entorno detectado:</strong> " . (IS_PRODUCTION ? "🌐 PRODUCCIÓN" : "🏠 DESARROLLO LOCAL");
    echo "</div>";
    
    // Verificar estructura actual
    echo "<h2>1. Verificando estructura actual de la tabla</h2>";
    
    $result = $conn->query("SHOW CREATE TABLE participante");
    if ($result) {
        $row = $result->fetch_array();
        $createTable = $row[1];
        
        echo "<pre>" . htmlspecialchars($createTable) . "</pre>";
        
        // Verificar si tiene AUTO_INCREMENT
        if (strpos($createTable, 'AUTO_INCREMENT') !== false) {
            echo "<p class='success'>✅ El campo ID ya tiene AUTO_INCREMENT configurado</p>";
            echo "<p>Si aún ves el error, puede ser un problema de permisos o de caché. Intenta:</p>";
            echo "<ul>";
            echo "<li>Reiniciar el servidor MySQL</li>";
            echo "<li>Verificar que el usuario de la BD tenga permisos de INSERT</li>";
            echo "</ul>";
        } else {
            echo "<p class='error'>❌ El campo ID NO tiene AUTO_INCREMENT</p>";
            
            if (!isset($_GET['fix'])) {
                echo "<div class='info'>";
                echo "<p><strong>⚠️ Acción requerida:</strong></p>";
                echo "<p>Necesitas agregar AUTO_INCREMENT al campo ID. Esto se puede hacer de dos formas:</p>";
                echo "<h3>Opción 1: Desde este script (Recomendado)</h3>";
                echo "<form method='get'>";
                echo "<button type='submit' name='fix' value='1'>🔧 Arreglar AUTO_INCREMENT Ahora</button>";
                echo "</form>";
                echo "<h3>Opción 2: Manualmente desde phpMyAdmin</h3>";
                echo "<p>Ejecuta este SQL en phpMyAdmin:</p>";
                echo "<pre>ALTER TABLE participante MODIFY COLUMN ID INT NOT NULL AUTO_INCREMENT;</pre>";
                echo "</div>";
            } else {
                echo "<h2>2. Aplicando corrección...</h2>";
                
                // Intentar arreglar
                $sql = "ALTER TABLE participante MODIFY COLUMN ID INT NOT NULL AUTO_INCREMENT";
                
                if ($conn->query($sql) === TRUE) {
                    echo "<p class='success'>✅ AUTO_INCREMENT agregado exitosamente!</p>";
                    echo "<p>Verifica la estructura actualizada:</p>";
                    
                    $result2 = $conn->query("SHOW CREATE TABLE participante");
                    if ($result2) {
                        $row2 = $result2->fetch_array();
                        echo "<pre>" . htmlspecialchars($row2[1]) . "</pre>";
                    }
                    
                    echo "<p class='success'><strong>✅ Problema resuelto. Ahora puedes agregar participantes sin errores.</strong></p>";
                    
                } else {
                    echo "<p class='error'>❌ Error al aplicar la corrección: " . $conn->error . "</p>";
                    echo "<div class='info'>";
                    echo "<p><strong>Solución alternativa:</strong></p>";
                    echo "<p>Ejecuta este comando manualmente en phpMyAdmin o desde la consola MySQL:</p>";
                    echo "<pre>ALTER TABLE participante CHANGE ID ID INT NOT NULL AUTO_INCREMENT;</pre>";
                    echo "</div>";
                }
            }
        }
    } else {
        echo "<p class='error'>❌ Error al consultar la tabla: " . $conn->error . "</p>";
    }
    
    $conn->close();
    ?>
    
    <hr>
    <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">🔄 Recargar</a></p>
</body>
</html>
