<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Arreglar AUTO_INCREMENT con Foreign Keys</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        button { background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #45a049; }
        .step { background: #f9f9f9; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
    </style>
</head>
<body>
    <h1>🔧 Arreglar AUTO_INCREMENT (con Foreign Keys)</h1>
    
    <?php
    require_once __DIR__ . "/Conexiones/Conexion.php";
    require_once __DIR__ . "/config.php";
    
    echo "<div class='info'>";
    echo "<strong>Entorno:</strong> " . (IS_PRODUCTION ? "🌐 PRODUCCIÓN" : "🏠 DESARROLLO");
    echo "</div>";
    
    if (!isset($_GET['ejecutar'])) {
        echo "<h2>⚠️ Proceso a Ejecutar</h2>";
        echo "<p>Se ejecutarán los siguientes pasos para agregar AUTO_INCREMENT al campo ID:</p>";
        echo "<ol>";
        echo "<li>Eliminar temporalmente la foreign key <code>clase_ibfk_2</code> de la tabla <code>clase</code></li>";
        echo "<li>Modificar el campo <code>ID</code> en <code>participante</code> para agregar AUTO_INCREMENT</li>";
        echo "<li>Recrear la foreign key <code>clase_ibfk_2</code></li>";
        echo "</ol>";
        
        echo "<div class='warning'>";
        echo "<p><strong>⚠️ IMPORTANTE:</strong></p>";
        echo "<ul>";
        echo "<li>Este proceso es seguro y NO borrará datos</li>";
        echo "<li>Solo modifica la estructura de las tablas</li>";
        echo "<li>Toma unos segundos</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<form method='get'>";
        echo "<button type='submit' name='ejecutar' value='1'>▶️ Ejecutar Proceso</button>";
        echo "</form>";
        
    } else {
        echo "<h2>Ejecutando proceso...</h2>";
        
        $errores = [];
        $pasos = [];
        
        // Paso 1: Eliminar foreign key
        echo "<div class='step'>";
        echo "<h3>Paso 1: Eliminando foreign key temporal</h3>";
        $sql1 = "ALTER TABLE clase DROP FOREIGN KEY clase_ibfk_2";
        if ($conn->query($sql1) === TRUE) {
            echo "<p class='success'>✅ Foreign key eliminada</p>";
            $pasos[] = "FK eliminada";
        } else {
            $error = $conn->error;
            echo "<p class='error'>❌ Error: $error</p>";
            // Si el error es que no existe, continuar igual
            if (strpos($error, "check that it exists") !== false) {
                echo "<p class='warning'>⚠️ La foreign key no existe, continuando...</p>";
                $pasos[] = "FK no existía";
            } else {
                $errores[] = "Paso 1: " . $error;
            }
        }
        echo "</div>";
        
        // Paso 2: Agregar AUTO_INCREMENT
        if (empty($errores)) {
            echo "<div class='step'>";
            echo "<h3>Paso 2: Agregando AUTO_INCREMENT al campo ID</h3>";
            $sql2 = "ALTER TABLE participante MODIFY COLUMN ID INT NOT NULL AUTO_INCREMENT";
            if ($conn->query($sql2) === TRUE) {
                echo "<p class='success'>✅ AUTO_INCREMENT agregado exitosamente</p>";
                $pasos[] = "AUTO_INCREMENT agregado";
            } else {
                echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
                $errores[] = "Paso 2: " . $conn->error;
            }
            echo "</div>";
        }
        
        // Paso 3: Recrear foreign key
        if (empty($errores)) {
            echo "<div class='step'>";
            echo "<h3>Paso 3: Recreando foreign key</h3>";
            $sql3 = "ALTER TABLE clase ADD CONSTRAINT clase_ibfk_2 
                     FOREIGN KEY (ID_Participante) REFERENCES participante(ID) ON DELETE CASCADE";
            if ($conn->query($sql3) === TRUE) {
                echo "<p class='success'>✅ Foreign key recreada</p>";
                $pasos[] = "FK recreada";
            } else {
                echo "<p class='error'>❌ Error: " . $conn->error . "</p>";
                $errores[] = "Paso 3: " . $conn->error;
            }
            echo "</div>";
        }
        
        // Resumen
        echo "<hr>";
        if (empty($errores)) {
            echo "<h2 class='success'>✅ Proceso completado exitosamente</h2>";
            echo "<p>La tabla <code>participante</code> ahora tiene AUTO_INCREMENT en el campo ID.</p>";
            
            // Verificar
            echo "<h3>Verificación:</h3>";
            $result = $conn->query("SHOW CREATE TABLE participante");
            if ($result) {
                $row = $result->fetch_array();
                if (strpos($row[1], 'AUTO_INCREMENT') !== false) {
                    echo "<p class='success'>✅ Confirmado: AUTO_INCREMENT está activo</p>";
                }
                echo "<details>";
                echo "<summary>Ver estructura completa</summary>";
                echo "<pre>" . htmlspecialchars($row[1]) . "</pre>";
                echo "</details>";
            }
            
            echo "<div class='info'>";
            echo "<p><strong>🎉 ¡Listo!</strong> Ahora puedes agregar participantes sin errores.</p>";
            echo "<p><a href='/Congreso/Agregar_Participante.php'>➡️ Ir a Agregar Participante</a></p>";
            echo "</div>";
            
        } else {
            echo "<h2 class='error'>❌ Hubo errores en el proceso</h2>";
            echo "<ul>";
            foreach ($errores as $err) {
                echo "<li class='error'>$err</li>";
            }
            echo "</ul>";
            
            echo "<div class='info'>";
            echo "<h3>Solución Manual:</h3>";
            echo "<p>Ejecuta estos comandos en phpMyAdmin en este orden:</p>";
            echo "<pre>";
            echo "-- 1. Eliminar foreign key\n";
            echo "ALTER TABLE clase DROP FOREIGN KEY clase_ibfk_2;\n\n";
            echo "-- 2. Agregar AUTO_INCREMENT\n";
            echo "ALTER TABLE participante MODIFY COLUMN ID INT NOT NULL AUTO_INCREMENT;\n\n";
            echo "-- 3. Recrear foreign key\n";
            echo "ALTER TABLE clase ADD CONSTRAINT clase_ibfk_2 \n";
            echo "FOREIGN KEY (ID_Participante) REFERENCES participante(ID) ON DELETE CASCADE;";
            echo "</pre>";
            echo "</div>";
        }
    }
    
    $conn->close();
    ?>
    
    <hr>
    <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">🔄 Volver</a></p>
</body>
</html>
