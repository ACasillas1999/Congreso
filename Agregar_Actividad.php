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

// Recuperar ubicaciones
$sql = "SELECT ID, name_evento FROM evento where estado = 'EN CURSO'";
$result = $conn->query($sql);

// Cerrar conexión
$conn->close();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <link rel="stylesheet" href="styles3.css">
    <title>Nueva Actividad</title>
</head>
<!--<body class = "fade-in">-->

<body class = "fade-in">
    
<header class="header">
    <div class="logo">Agregar Actividad</div>
    <nav class="navbar">
        <ul>

       <!--<li class="corner-left-bottom"><a href="javascript:history.back()">Volver</a></li>-->
            <li class="nav-item"><a href='Evento_inicio.php?id=<?php  echo $id; ?>' class="nav-link">Volver</a></li>
        </ul>
    </nav>
</header>
<p></p>

<form action="Funcion_Agregar_Actividad.php" method="POST">
<label for="Evento">Evento:</label>
    <select id="Evento" name="Evento" required>
      
      <?php
        // Mostrar las ubicaciones en el combo box
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<option value='" . $row["ID"] . "'>" . $row["name_evento"] . "</option>";
            }
        } else {
            echo "<option value=''>No hay ubicaciones disponibles</option>";
        }
        ?>
    </select><br><br>
    
    <label for="Actividad">Nombre de la Actividad:</label>
    <input type="text" id="Actividad" name="Actividad" required><br><br>

    <label for="Descripcion">Descripcion:</label>
    <input type="text" id="Descripcion" name="Descripcion" required><br><br>


    <label for="capacidad">Capacidad:</label>
    <input type="number" id="capacidad" name="capacidad" required><br><br>

    <label for="Puntos_Default">Puntos por asistencia (default):</label>
<input type="number" id="Puntos_Default" name="Puntos_Default" min="0" step="1" value="0" required><br><br>
<label>
  <input type="checkbox" name="Exclusiva" value="1">
  Actividad exclusiva (solo Gerente/Admin)
</label>


   <!-- <label for="direccion">Dirección:</label>
    <input type="text" id="direccion" name="direccion" required><br><br>-->

    

    <input type="submit" value="Agregar Actividad">
</form>

</body>
</html>
