<?php

session_name("Congreso");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /congreso/Sesion/login.html");
    exit;
}

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Verificar si se ha enviado información de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST["id"];
    
    // Consulta a la base de datos filtrando solo las columnas necesarias
    $sql = "SELECT ID, name_evento, duracion, estado, fecha_inicio FROM Evento";
    
    $result = $conn->query($sql);

    // Verificar si la consulta fue exitosa
    if ($result === false) {
        echo "Error en la consulta: " . $conn->error;
    } else {
        if ($result->num_rows > 0) {
            // Mostrar los datos encontrados en forma de tabla
            echo "<table class='mi-tabla' border='1'>";
            echo "<tr><th>ID</th><th>ID_Evento</th><th>Sucursal</th><th>Vendedor</th><th>Nombre</th><th>Proveedor</th><th>Acción</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                // Definir el color de fondo según el estado
                $estado = $row["estado"];
                $color = "#FFFFFF"; // Color por defecto (blanco)

                switch (strtoupper($estado)) {
                    case "CANCELADO":
                        $color = "#FFCCCC"; // ROJO pastel
                        break;
                    case "EN CURSO":
                        $color = "#FFFFCC"; // AMARILLO pastel
                        break;
                    case "FINALIZADO":
                        $color = "#ccffe6"; // VERDE pastel
                        break;
                }

                echo "<tr>";
                echo "<td>" . $row["ID"] . "</td>";
                echo "<td>" . $row["name_evento"] . "</td>";
                echo "<td>" . $row["duracion"] . "</td>";
                echo "<td style='background-color: $color;'>" . $row["estado"] . "</td>";
                echo "<td>" . $row["fecha_inicio"] . "</td>";
                echo "<td><a href='Evento_inicio.php?id=" . $row["ID"] . "'>Ver Detalles</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No se encontraron resultados.";
        }
    }
}

$conn->close();
?>
