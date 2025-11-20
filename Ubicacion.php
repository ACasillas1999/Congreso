<?php
// Iniciar la sesión de forma segura

ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubicaciones</title>
    <!-- Enlace al archivo de estilos -->
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <!-- Ícono de la página -->
    <link rel="icon" href="congreso/educacion.png" type="image/x-icon">
</head>
<body class="fade-in">

<!-- Barra lateral -->
<div class="sidebar">
    <ul>
       <!-- <li><a href="NuevoRegistroInicio.php">Agregar Evento</a></li>-->
        <li><a href="NuevoRegistroUbicacion.php">Agregar Ubicacion</a></li>
     
        <li><a href="index.php">Volver</a></li>
        
       
    </ul>
</div>

<div class="content">
    <!-- Contenido principal de tu página -->
</div>

<!-- Contenedor principal -->
<div class="container">
    <h2 class="titulo">Ubicaciones</h2>


    <p></p>

    <!-- Contenedor para mostrar resultados de la consulta -->
    <div id="resultado">
        <?php
        // Establecer la conexión a la base de datos
        require_once __DIR__ . "/Conexiones/Conexion.php";

        // Consulta inicial sin filtro de búsqueda
        $busqueda = isset($_POST['busqueda']) ? $_POST['busqueda'] : '';
        $sql = "SELECT ID, Nombre, Direccion, Salones, Capacidad_por_salon, Capacidad_total
                FROM ubicaciones";

        $result = $conn->query($sql);

        // Verificar si la consulta fue exitosa
        if ($result === false) {
            echo "Error en la consulta: " . $conn->error;
        } else {
            if ($result->num_rows > 0) {
                echo "<table class='mi-tabla' border='1'>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Direccion</th><th>Salones</th><th>Capacidad por Salon</th> <th>Capacidad Total</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["ID"] . "</td>";
                    echo "<td>" . $row["Nombre"] . "</td>";
                    echo "<td>" . $row["Direccion"] . "</td>";
                    echo "<td>" . $row["Salones"] . "</td>";
                  
                    echo "<td>" . $row["Capacidad_por_salon"] . "</td>";
                    echo "<td>" . $row["Capacidad_total"] . "</td>";
                    

                    /////////////////Actualiza este para porder editar la tabla de ubicaciones
                   /* echo "<td><a href='Evento_inicio.php?id=" . $row["ID"] . "'>Actualizar</a></td>";*/
                    echo "</tr>";
                }
                echo "</table>";
                
            } else {
                echo "No se encontraron resultados.";
            }
        
        }
        $conn->close();
        ?>
    </div>

</div>

<!-- Script de JavaScript -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Función para manejar el evento de búsqueda en tiempo real
    document.getElementById("busqueda").addEventListener("input", function(event) {
        // Obtener la búsqueda ingresada desde el campo de texto
        var busqueda = document.getElementById("busqueda").value;
        
        // Realizar una solicitud AJAX para cargar los datos
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.getElementById("resultado").innerHTML = xhr.responseText; // Actualizar el contenido de la tabla
            }
        };
        xhr.send("busqueda=" + encodeURIComponent(busqueda)); // Enviar la búsqueda al servidor
    });

    // Disparar la búsqueda inicial sin texto para cargar todos los resultados
    document.getElementById("busqueda").dispatchEvent(new Event('input'));
});
</script>
<script src="animacion.js"></script>
</body>
</html>
