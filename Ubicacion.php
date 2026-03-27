<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
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
    <link rel="stylesheet" type="text/css" href="styles.css">
    <?php include "header_css.php"; ?>
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
</head>
<body class="fade-in">

    <?php include "sidebar.php"; ?>

    <div class="container">
        <h2 class="titulo">Ubicaciones</h2>
        <div style="margin-bottom: 20px; text-align: right;">
            <a href="NuevoRegistroUbicacion.php" style="padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">+ Nueva Ubicación</a>
        </div>
        <div id="resultado">
            <?php
            require_once __DIR__ . "/Conexiones/Conexion.php";
            $sql = "SELECT ID, Nombre, Direccion, Salones, Capacidad_por_salon, Capacidad_total FROM ubicaciones";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
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
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='note'>No se encontraron resultados.</p>";
            }
            $conn->close();
            ?>
        </div>
    </div>

    <script src="animacion.js"></script>
</body>
</html>
