<?php

// Iniciar la sesión de forma segura

ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {
 
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID del evento desde la URL
$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Inicializar variables para los campos del evento
$name_evento = $fecha_inicio = $fecha_fin = $duracion = $estado = $ubicacion = '';

// Si se recibió un ID válido, recuperar la información del evento
if ($id_evento > 0) {
    $sql_evento = "SELECT * FROM evento WHERE id = $id_evento";
    $result_evento = $conn->query($sql_evento);

    if ($result_evento->num_rows > 0) {
        $row_evento = $result_evento->fetch_assoc();
        $name_evento = $row_evento['name_evento'];
        $fecha_inicio = $row_evento['fecha_inicio'];
        $fecha_fin = $row_evento['fecha_fin'];
        $duracion = ceil((strtotime($fecha_fin) - strtotime($fecha_inicio)) / (60 * 60 * 24)) + 1;
        $estado = $row_evento['estado'];
        $ubicacion = $row_evento['ubicacion'];
    }
}

// Recuperar ubicaciones
$sql_ubicaciones = "SELECT Nombre FROM ubicaciones";
$result_ubicaciones = $conn->query($sql_ubicaciones);

// Cerrar conexión
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles3.css">
    <?php include "header_css.php"; ?>
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <title>Actualizar Evento</title>
    <script>
        function calcularDuracion() {
            var fechaInicio = document.getElementById('fecha_inicio').value;
            var fechaFin = document.getElementById('fecha_fin').value;
            
            if (fechaInicio && fechaFin) {
                var inicio = new Date(fechaInicio);
                var fin = new Date(fechaFin);
                var duracion = Math.ceil((fin - inicio) / (1000 * 60 * 60 * 24)) + 1; // +1 para incluir el día de inicio
                document.getElementById('duracion').value = duracion + ' días';
            } else {
                document.getElementById('duracion').value = '';
            }
        }
    </script>
</head>
<body>
    
<header class="header">
    <div class="logo">Actualizar Evento</div>
    <nav class="navbar">
        <ul>
            <li class="nav-item"><a href='index.php' class="nav-link">Volver</a></li>
        </ul>
    </nav>
</header>

<form action="ActualizarEvento.php" method="POST">
    <input type="hidden" name="id_evento" value="<?php echo $id_evento; ?>">

    <label for="name_evento">Nombre del Evento:</label>
    <input type="text" id="name_evento" name="name_evento" value="<?php echo $name_evento; ?>" readonly><br><br>

    <label for="fecha_inicio">Fecha Inicio:</label>
    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" readonly onchange="calcularDuracion()"><br><br>

    <label for="fecha_fin">Fecha Final:</label>
    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>" readonly onchange="calcularDuracion()"><br><br>

    <label for="duracion">Duración:</label>
    <input type="text" id="duracion" name="duracion" value="<?php echo $duracion . ' días'; ?>" readonly><br><br>

    <label for="estado">Estado:</label>
    <input type="text" id="estado" name="estado" value="<?php echo $estado; ?>" readonly><br><br>

    <label for="ubicacion">Ubicación:</label>
    <select id="ubicacion" name="ubicacion" readonly>
        <?php
        // Mostrar las ubicaciones en el combo box
        if ($result_ubicaciones->num_rows > 0) {
            while($row_ubicaciones = $result_ubicaciones->fetch_assoc()) {
                $selected = ($ubicacion == $row_ubicaciones['Nombre']) ? 'selected' : '';
                echo "<option value='" . $row_ubicaciones["Nombre"] . "' $selected>" . $row_ubicaciones["Nombre"] . "</option>";
            }
        } else {
            echo "<option value=''>No hay ubicaciones disponibles</option>";
        }
        ?>
    </select><br><br>

 <!--   <input type="submit" value="Actualizar Evento">-->
</form>

</body>
</html>
