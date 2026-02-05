<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verificar Extensión GD</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Verificación de Extensión GD</h1>
    
    <?php
    require_once __DIR__ . "/config.php";
    
    echo "<div class='info'>";
    echo "<strong>Entorno:</strong> " . (IS_PRODUCTION ? "🌐 PRODUCCIÓN" : "🏠 DESARROLLO LOCAL");
    echo "</div>";
    
    echo "<h2>Estado de la Extensión GD</h2>";
    
    if (extension_loaded('gd')) {
        echo "<p class='success'>✅ La extensión GD está HABILITADA</p>";
        
        echo "<h3>Información de GD:</h3>";
        $gdInfo = gd_info();
        echo "<table>";
        echo "<tr><th>Característica</th><th>Estado</th></tr>";
        foreach ($gdInfo as $key => $value) {
            $displayValue = is_bool($value) ? ($value ? '✅ Sí' : '❌ No') : $value;
            echo "<tr><td>$key</td><td>$displayValue</td></tr>";
        }
        echo "</table>";
        
        echo "<h3>Funciones Disponibles:</h3>";
        $funciones = [
            'imagecreate' => 'Crear imagen',
            'imagecreatefromjpeg' => 'Cargar JPEG',
            'imagecreatefrompng' => 'Cargar PNG',
            'imagejpeg' => 'Guardar JPEG',
            'imagepng' => 'Guardar PNG',
            'imagecolorallocate' => 'Asignar colores',
            'imagettftext' => 'Escribir texto con fuentes TrueType'
        ];
        
        echo "<table>";
        echo "<tr><th>Función</th><th>Descripción</th><th>Estado</th></tr>";
        foreach ($funciones as $func => $desc) {
            $disponible = function_exists($func);
            echo "<tr>";
            echo "<td><code>$func()</code></td>";
            echo "<td>$desc</td>";
            echo "<td>" . ($disponible ? "<span class='success'>✅ Disponible</span>" : "<span class='error'>❌ No disponible</span>") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='success'>";
        echo "<h2>✅ Todo está correcto</h2>";
        echo "<p>La extensión GD está habilitada y todas las funciones necesarias están disponibles.</p>";
        echo "</div>";
        
    } else {
        echo "<p class='error'>❌ La extensión GD NO está habilitada</p>";
        
        echo "<div class='warning'>";
        echo "<h2>⚠️ Acción Requerida</h2>";
        
        if (IS_PRODUCTION) {
            echo "<h3>Para Servidor de Producción (Linux/cPanel):</h3>";
            echo "<p>Tienes varias opciones para habilitar GD:</p>";
            
            echo "<h4>Opción 1: Desde cPanel (Recomendado)</h4>";
            echo "<ol>";
            echo "<li>Accede a tu cPanel</li>";
            echo "<li>Busca <strong>\"Select PHP Version\"</strong> o <strong>\"MultiPHP Manager\"</strong></li>";
            echo "<li>Selecciona tu versión de PHP</li>";
            echo "<li>En <strong>\"Extensions\"</strong>, marca la casilla <code>gd</code></li>";
            echo "<li>Guarda los cambios</li>";
            echo "</ol>";
            
            echo "<h4>Opción 2: Contactar al Proveedor de Hosting</h4>";
            echo "<p>Si no tienes acceso a cPanel, contacta a tu proveedor de hosting y solicita que habiliten la extensión GD para PHP.</p>";
            
            echo "<h4>Opción 3: Vía SSH (si tienes acceso root)</h4>";
            echo "<pre>";
            echo "# Para Ubuntu/Debian:\n";
            echo "sudo apt-get install php-gd\n";
            echo "sudo systemctl restart apache2\n\n";
            echo "# Para CentOS/RHEL:\n";
            echo "sudo yum install php-gd\n";
            echo "sudo systemctl restart httpd\n";
            echo "</pre>";
            
        } else {
            echo "<h3>Para Desarrollo Local (Windows/XAMPP):</h3>";
            echo "<ol>";
            echo "<li>Abre el archivo <code>C:\\xampp\\php\\php.ini</code></li>";
            echo "<li>Busca la línea: <code>;extension=gd</code></li>";
            echo "<li>Elimina el punto y coma (;) para que quede: <code>extension=gd</code></li>";
            echo "<li>Guarda el archivo</li>";
            echo "<li>Reinicia Apache desde el Panel de Control de XAMPP</li>";
            echo "</ol>";
        }
        
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<p><strong>Después de habilitar GD:</strong></p>";
        echo "<ol>";
        echo "<li>Recarga esta página para verificar que GD esté habilitado</li>";
        echo "<li>Intenta agregar un participante nuevamente</li>";
        echo "</ol>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h2>Información del Sistema</h2>";
    echo "<table>";
    echo "<tr><th>Parámetro</th><th>Valor</th></tr>";
    echo "<tr><td>Versión de PHP</td><td>" . phpversion() . "</td></tr>";
    echo "<tr><td>Sistema Operativo</td><td>" . PHP_OS . "</td></tr>";
    echo "<tr><td>Servidor Web</td><td>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>";
    echo "<tr><td>Archivo php.ini</td><td>" . php_ini_loaded_file() . "</td></tr>";
    echo "</table>";
    ?>
    
    <hr>
    <p><a href="<?php echo $_SERVER['PHP_SELF']; ?>">🔄 Recargar</a> | <a href="phpinfo.php" target="_blank">📋 Ver phpinfo() completo</a></p>
</body>
</html>
