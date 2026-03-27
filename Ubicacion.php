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
            <a href="#" onclick="abrirModalUbicacion(event)" style="padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">+ Nueva Ubicación</a>
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

    <!-- Modal Nueva Ubicación -->
    <div id="modalNuevaUbicacion" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.6); z-index:1000; justify-content:center; align-items:center;">
        <div style="background-color:white; padding:25px; border-radius:10px; width:90%; max-width:500px; position:relative; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
            <span onclick="cerrarModalUbicacion()" style="position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; color:#333;">&times;</span>
            <h3 style="margin-top:0; color:#333; border-bottom:1px solid #ddd; padding-bottom:10px;">Agregar Ubicación</h3>
            
            <form action="FuncionNuevoRegistroUbicacion.php" method="POST">
                <div style="margin-bottom: 12px;">
                    <label for="Nombre" style="display:block; margin-bottom:5px; color:#555; font-weight:bold;">Nombre de la Ubicación:</label>
                    <input type="text" id="Nombre" name="Nombre" required style="width:100%; padding:10px; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;">
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label for="Direccion" style="display:block; margin-bottom:5px; color:#555; font-weight:bold;">Dirección:</label>
                    <input type="text" id="Direccion" name="Direccion" required style="width:100%; padding:10px; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;">
                </div>

                <div style="display:flex; gap:10px; margin-bottom: 12px;">
                    <div style="flex:1;">
                        <label for="Salones" style="display:block; margin-bottom:5px; color:#555; font-weight:bold;">Cant. Salones:</label>
                        <input type="number" id="Salones" name="Salones" required min="1" oninput="calcularCapacidadTotal()" style="width:100%; padding:10px; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;">
                    </div>
                    <div style="flex:1;">
                        <label for="Capacidad_por_salon" style="display:block; margin-bottom:5px; color:#555; font-weight:bold;">Cap/Salón:</label>
                        <input type="number" id="Capacidad_por_salon" name="Capacidad_por_salon" required min="1" oninput="calcularCapacidadTotal()" style="width:100%; padding:10px; box-sizing:border-box; border:1px solid #ccc; border-radius:5px;">
                    </div>
                </div>

                <div style="margin-bottom: 12px; display:none;">
                    <label for="Capacidad_total_actual">Capacidad Total Actual:</label>
                    <input type="hidden" id="Capacidad_total_actual" name="Capacidad_actual" readonly>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="Capacidad_total" style="display:block; margin-bottom:5px; color:#555; font-weight:bold;">Capacidad Total:</label>
                    <input type="text" id="Capacidad_total" name="Capacidad_total" readonly style="width:100%; padding:10px; box-sizing:border-box; border:1px solid #ccc; border-radius:5px; background-color:#f5f5f5; color:#333; cursor:not-allowed;">
                </div>

                <button type="submit" style="width:100%; padding:12px; background-color:#4CAF50; color:white; border:none; border-radius:5px; font-size:16px; font-weight:bold; cursor:pointer; transition:background 0.3s;">Guardar Ubicación</button>
            </form>
        </div>
    </div>

    <script>
    function abrirModalUbicacion(e) {
        if(e) e.preventDefault();
        document.getElementById('modalNuevaUbicacion').style.display = 'flex';
    }
    
    function cerrarModalUbicacion() {
        document.getElementById('modalNuevaUbicacion').style.display = 'none';
    }

    function calcularCapacidadTotal() {
        const salones = document.getElementById('Salones').value || 0;
        const capacidadPorSalon = document.getElementById('Capacidad_por_salon').value || 0;
        const capacidadTotal = salones * capacidadPorSalon;

        document.getElementById('Capacidad_total').value = capacidadTotal;
        document.getElementById('Capacidad_total_actual').value = capacidadTotal;
    }
    
    // Cerrar modal al hacer click fuera del contenido
    document.getElementById('modalNuevaUbicacion').addEventListener('click', function(e) {
        if(e.target === this) {
            cerrarModalUbicacion();
        }
    });
    </script>
    <script src="animacion.js"></script>
</body>
</html>
