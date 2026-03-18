<?php
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

// Auto-setup de la tabla si no existe
$conn->query("CREATE TABLE IF NOT EXISTS configuracion_css (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_variable VARCHAR(50) NOT NULL UNIQUE,
    valor_css VARCHAR(255) NOT NULL
)");

// Verificar si hay registros, si no, insertar defaults
$check = $conn->query("SELECT COUNT(*) as total FROM configuracion_css");
$row = $check->fetch_assoc();
if ($row['total'] == 0) {
    $defaults = [
        '--azul-oscuro' => '#054a6b',
        '--azul-medio' => '#1ca9dc',
        '--azul-suave' => '#dff8ff',
        '--gris-suave' => '#f5f6fa',
        '--naranja' => '#38d9ff',
        '--verde' => '#0ea5c6',
        '--bg-gradient-start' => '#95ecff',
        '--bg-gradient-end' => '#054a6b',
        '--container-bg' => 'rgba(8, 27, 50, 0.7)',
        '--titulo-neon' => '#7cecff'
    ];
    $stmt = $conn->prepare("INSERT INTO configuracion_css (nombre_variable, valor_css) VALUES (?, ?)");
    foreach ($defaults as $var => $val) {
        $stmt->bind_param("ss", $var, $val);
        $stmt->execute();
    }
}

// Cargar valores actuales
$current_vars = [];
$result = $conn->query("SELECT nombre_variable, valor_css FROM configuracion_css");
while ($row = $result->fetch_assoc()) {
    $current_vars[$row['nombre_variable']] = $row['valor_css'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalizar Estilos</title>
    <link rel="stylesheet" href="styles.css">
    <?php include "header_css.php"; ?>
    <style>
        .customizer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .color-group {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .color-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .color-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        input[type="color"] {
            border: none;
            width: 40px;
            height: 40px;
            cursor: pointer;
            background: none;
        }
        input[type="text"] {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            width: 100px;
        }
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn-save {
            background: #28a745 !important;
        }
        .btn-restore {
            background: #dc3545 !important;
        }
    </style>
</head>
<body class="fade-in">
    <div class="sidebar">
        <ul>
            <li><a href="index.php">Volver al Inicio</a></li>
        </ul>
    </div>

    <div class="container">
        <h2 class="titulo">Personalizar Apariencia</h2>
        
        <form action="guardar_css.php" method="POST">
            <div class="customizer-grid">
                <!-- Colores de Fondo -->
                <div class="color-group">
                    <label>Fondo Degradado (Inicio)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="--bg-gradient-start" value="<?php echo $current_vars['--bg-gradient-start']; ?>">
                        <input type="text" value="<?php echo $current_vars['--bg-gradient-start']; ?>" readonly>
                    </div>
                </div>
                <div class="color-group">
                    <label>Fondo Degradado (Fin)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="--bg-gradient-end" value="<?php echo $current_vars['--bg-gradient-end']; ?>">
                        <input type="text" value="<?php echo $current_vars['--bg-gradient-end']; ?>" readonly>
                    </div>
                </div>

                <!-- Colores del Sidebar -->
                <div class="color-group">
                    <label>Sidebar Principal</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="--azul-oscuro" value="<?php echo $current_vars['--azul-oscuro']; ?>">
                        <input type="text" value="<?php echo $current_vars['--azul-oscuro']; ?>" readonly>
                    </div>
                </div>
                <div class="color-group">
                    <label>Sidebar Secundario / Botones</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="--azul-medio" value="<?php echo $current_vars['--azul-medio']; ?>">
                        <input type="text" value="<?php echo $current_vars['--azul-medio']; ?>" readonly>
                    </div>
                </div>

                <!-- Elementos Premium -->
                <div class="color-group">
                    <label>Color de Títulos (Neón)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="--titulo-neon" value="<?php echo $current_vars['--titulo-neon']; ?>">
                        <input type="text" value="<?php echo $current_vars['--titulo-neon']; ?>" readonly>
                    </div>
                </div>
                <div class="color-group">
                    <label>Color de Acentos (Naranja)</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="--naranja" value="<?php echo $current_vars['--naranja']; ?>">
                        <input type="text" value="<?php echo $current_vars['--naranja']; ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="submit" name="action" value="save" class="button btn-save">Guardar Cambios</button>
                <button type="submit" name="action" value="restore" class="button btn-restore" onclick="return confirm('¿Seguro que quieres restaurar los colores originales?')">Restaurar por Defecto</button>
            </div>
        </form>
    </div>

    <script>
        // Actualizar visualmente el texto cuando cambia el color picker
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('input', function() {
                this.nextElementSibling.value = this.value.toUpperCase();
                
                // Actualización en tiempo real (opcional)
                const varName = this.name;
                document.documentElement.style.setProperty(varName, this.value);
            });
        });
    </script>
</body>
</html>
