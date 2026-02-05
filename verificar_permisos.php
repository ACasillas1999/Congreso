<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verificar y Arreglar Permisos</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #4CAF50; color: white; }
        button { background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🔧 Verificar Permisos y Rutas</h1>
    
    <?php
    require_once __DIR__ . "/config.php";
    
    echo "<div class='info'>";
    echo "<strong>Entorno:</strong> " . (IS_PRODUCTION ? "🌐 PRODUCCIÓN" : "🏠 DESARROLLO");
    echo "</div>";
    
    echo "<h2>1. Verificando Rutas de Configuración</h2>";
    echo "<table>";
    echo "<tr><th>Constante</th><th>Valor</th><th>¿Existe?</th><th>¿Escribible?</th></tr>";
    
    $rutas = [
        'BASE_PATH' => BASE_PATH,
        'MACHOTE_PATH' => MACHOTE_PATH,
        'GAFETES_OUTPUT' => GAFETES_OUTPUT,
        'HORARIOS_OUTPUT' => HORARIOS_OUTPUT,
        'QR_OUTPUT' => QR_OUTPUT,
        'TEMPLATE_GAFETE' => TEMPLATE_GAFETE,
        'TEMPLATE_HORARIO_PORTRAIT' => TEMPLATE_HORARIO_PORTRAIT,
        'FONT_NEXA' => FONT_NEXA
    ];
    
    $problemasRutas = [];
    $problemasPermisos = [];
    
    foreach ($rutas as $nombre => $ruta) {
        $existe = file_exists($ruta);
        $escribible = is_writable($ruta);
        $esDirectorio = is_dir($ruta);
        
        echo "<tr>";
        echo "<td><code>$nombre</code></td>";
        echo "<td><code>$ruta</code></td>";
        echo "<td>" . ($existe ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
        
        if ($esDirectorio) {
            echo "<td>" . ($escribible ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
            if (!$escribible && $existe) {
                $problemasPermisos[] = $ruta;
            }
        } else {
            echo "<td>" . ($existe ? ($escribible ? "<span class='success'>✅</span>" : "<span class='warning'>⚠️</span>") : "N/A") . "</td>";
        }
        echo "</tr>";
        
        if (!$existe) {
            $problemasRutas[] = "$nombre: $ruta";
        }
    }
    
    echo "</table>";
    
    // Mostrar problemas
    if (!empty($problemasRutas)) {
        echo "<div class='error'>";
        echo "<h3>❌ Archivos/Directorios No Encontrados:</h3>";
        echo "<ul>";
        foreach ($problemasRutas as $prob) {
            echo "<li>$prob</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    if (!empty($problemasPermisos)) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Directorios Sin Permisos de Escritura:</h3>";
        echo "<ul>";
        foreach ($problemasPermisos as $prob) {
            echo "<li><code>$prob</code></li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Intentar crear directorios
    echo "<hr>";
    echo "<h2>2. Crear Directorios Necesarios</h2>";
    
    $directorios = [
        'QR_OUTPUT' => QR_OUTPUT,
        'GAFETES_OUTPUT' => GAFETES_OUTPUT,
        'HORARIOS_OUTPUT' => HORARIOS_OUTPUT
    ];
    
    foreach ($directorios as $nombre => $dir) {
        echo "<p><strong>$nombre:</strong> <code>$dir</code></p>";
        
        if (file_exists($dir)) {
            if (is_writable($dir)) {
                echo "<p class='success'>✅ Existe y es escribible</p>";
            } else {
                echo "<p class='error'>❌ Existe pero NO es escribible</p>";
                echo "<p>Permisos actuales: " . substr(sprintf('%o', fileperms($dir)), -4) . "</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ No existe, intentando crear...</p>";
            if (@mkdir($dir, 0775, true)) {
                echo "<p class='success'>✅ Directorio creado exitosamente</p>";
            } else {
                echo "<p class='error'>❌ Error al crear directorio</p>";
                echo "<p>Verifica que el directorio padre tenga permisos de escritura</p>";
            }
        }
    }
    
    // Comandos para arreglar permisos
    echo "<hr>";
    echo "<h2>3. Comandos para Arreglar Permisos (SSH)</h2>";
    
    echo "<div class='info'>";
    echo "<p>Si tienes acceso SSH, ejecuta estos comandos:</p>";
    echo "<pre>";
    echo "# Navegar al directorio\n";
    echo "cd /var/www/html/Congreso\n\n";
    echo "# Crear directorios si no existen\n";
    echo "mkdir -p qrcodes\n";
    echo "mkdir -p Machote/Generados\n";
    echo "mkdir -p Machote/Horarios_Generados\n\n";
    echo "# Dar permisos de escritura\n";
    echo "chmod 775 qrcodes\n";
    echo "chmod 775 Machote/Generados\n";
    echo "chmod 775 Machote/Horarios_Generados\n\n";
    echo "# Cambiar propietario (ajusta 'www-data' según tu servidor)\n";
    echo "sudo chown -R www-data:www-data qrcodes\n";
    echo "sudo chown -R www-data:www-data Machote/Generados\n";
    echo "sudo chown -R www-data:www-data Machote/Horarios_Generados\n";
    echo "</pre>";
    echo "</div>";
    
    // Verificar plantillas
    echo "<hr>";
    echo "<h2>4. Verificar Archivos de Plantillas</h2>";
    
    $plantillas = [
        'Gafete' => TEMPLATE_GAFETE,
        'Horario Vertical' => TEMPLATE_HORARIO_PORTRAIT,
        'Horario Horizontal' => TEMPLATE_HORARIO_LANDSCAPE,
        'Fuente Nexa' => FONT_NEXA,
        'Fuente Roboto' => FONT_ROBOTO
    ];
    
    echo "<table>";
    echo "<tr><th>Archivo</th><th>Ruta</th><th>¿Existe?</th><th>Tamaño</th></tr>";
    
    foreach ($plantillas as $nombre => $ruta) {
        $existe = file_exists($ruta);
        $tamano = $existe ? filesize($ruta) : 0;
        
        echo "<tr>";
        echo "<td>$nombre</td>";
        echo "<td><code>$ruta</code></td>";
        echo "<td>" . ($existe ? "<span class='success'>✅</span>" : "<span class='error'>❌</span>") . "</td>";
        echo "<td>" . ($existe ? number_format($tamano) . " bytes" : "N/A") . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Información del sistema
    echo "<hr>";
    echo "<h2>5. Información del Sistema</h2>";
    echo "<table>";
    echo "<tr><th>Parámetro</th><th>Valor</th></tr>";
    echo "<tr><td>Usuario PHP</td><td><code>" . get_current_user() . "</code></td></tr>";
    echo "<tr><td>UID/GID</td><td>" . getmyuid() . " / " . getmygid() . "</td></tr>";
    echo "<tr><td>Directorio actual</td><td><code>" . getcwd() . "</code></td></tr>";
    echo "<tr><td>Directorio script</td><td><code>" . __DIR__ . "</code></td></tr>";
    echo "</table>";
    
    ?>
    
    <hr>
    <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">🔄 Recargar</a></p>
</body>
</html>
