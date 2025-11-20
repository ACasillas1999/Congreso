<?php
// Iniciar la sesión de forma segura
/*
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}
*/

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";



$busqueda = isset($_POST['busqueda']) ? $_POST['busqueda'] : '';
$sql = "SELECT 
            P.ID, 
            E.name_evento, 
            P.Sucursal, 
            P.Vendedor, 
            P.Nombre, 
            P.Proveedor,
            P.QR_Code  
        FROM 
            participante P
        JOIN 
            evento E
        ON 
            P.ID_Evento = E.ID
        WHERE 
           ( P.Nombre LIKE '%$busqueda%' 
            OR P.Sucursal LIKE '%$busqueda%' 
            OR P.Vendedor LIKE '%$busqueda%' 
            OR P.Proveedor LIKE '%$busqueda%')

        ";

$result = $conn->query($sql);

if ($result === false) {
    echo "Error en la consulta: " . $conn->error;
} else {
    if ($result->num_rows > 0) {
        echo "<table class='mi-tabla' border='1'>";
        echo "<tr><th>ID</th><th>ID_Evento</th><th>Sucursal</th><th>Vendedor</th><th>Nombre</th><th>Proveedor</th><th>QR Code</th><th>Acción</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["ID"] . "</td>";
            echo "<td>" . $row["name_evento"] . "</td>";
            echo "<td>" . $row["Sucursal"] . "</td>";
            echo "<td>" . $row["Vendedor"] . "</td>";
            echo "<td>" . $row["Nombre"] . "</td>";
            echo "<td>" . $row["Proveedor"] . "</td>";

            // Asume que $row["QR_Code"] contiene la ruta relativa al código QR
            $qrImagePath = $row["QR_Code"] ? $row["QR_Code"] : 'path_to_default_image.png';
            echo "<td><img src='" . htmlspecialchars($qrImagePath) . "' alt='QR Code' width='100'></td>";
            echo "<td><a href='Detalles_Participantes.php?id=" . $row["ID"] . "'>Ver Detalles</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No se encontraron resultados.";
    }
}

$conn->close();
?>
