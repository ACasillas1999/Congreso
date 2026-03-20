<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Recuperar ubicaciones
$sql = "SELECT Nombre FROM ubicaciones";
$result = $conn->query($sql);

// Cerrar conexión
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles3.css">
    <?php include "header_css.php"; ?>
    <title>Nuevo Evento</title>
    <script>
        function calcularDuracion() {
            var fechaInicio = document.getElementById('fecha_inicio').value;
            var fechaFin = document.getElementById('fecha_fin').value;
            
            if (fechaInicio && fechaFin) {
                var inicio = new Date(fechaInicio);
                var fin = new Date(fechaFin);
                var duracion = Math.ceil((fin - inicio) / (1000 * 60 * 60 * 24)) + 1; // +1 para incluir el día de inicio
                document.getElementById('duracion').value = duracion + ' dias';
            } else {
                document.getElementById('duracion').value = '';
            }
        }
    </script>
</head>
<body>
    <?php include "sidebar.php"; ?>

<header class="header">
    <div class="logo">Agregar Evento</div>
    <nav class="navbar">
        <ul>
            <li class="nav-item"><a href='index.php' class="nav-link">Volver</a></li>
        </ul>
    </nav>
</header>

<form action="NuevoRegistro.php" method="POST">
    
    <label for="name_evento">Nombre del Evento:</label>
    <input type="text" id="name_evento" name="name_evento" required><br><br>

    <label for="fecha_inicio">Fecha Inicio:</label>
    <input type="date" id="fecha_inicio" name="fecha_inicio" required onchange="calcularDuracion()"><br><br>

    <label for="fecha_fin">Fecha Final:</label>
    <input type="date" id="fecha_fin" name="fecha_fin" required onchange="calcularDuracion()"><br><br>

    <label for="duracion">Duración:</label>
    <input type="text" id="duracion" name="duracion" readonly><br><br>

    <label for="estado">Estado:</label>
    <input type="text" id="estado" name="estado" value="EN CURSO" readonly><br><br>

   <!-- <label for="direccion">Dirección:</label>
    <input type="text" id="direccion" name="direccion" required><br><br>-->

    <label for="ubicacion">Ubicación:</label>
    <select id="ubicacion" name="ubicacion" required>
        <?php
        // Mostrar las ubicaciones en el combo box
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<option value='" . $row["Nombre"] . "'>" . $row["Nombre"] . "</option>";
            }
        } else {
            echo "<option value=''>No hay ubicaciones disponibles</option>";
        }
        ?>
    </select><br><br>

    <label for="capacidad">Capacidad del evento:</label>
    <input type="number" id="capacidad" name="capacidad"  required><br><br>

<label for="tipo_puntos">Tipo de sistema de puntos:</label>
<select name="tipo_puntos" id="tipo_puntos" required>
    <option value="ninguno">Sin sistema de puntos</option>
    <option value="individual">Individual</option>
    <option value="grupal">Grupal (compartido por RFC)</option>
</select><br><br>




    <input type="submit" value="Agregar Evento">
</form>

</body>
</html>
