<?php

session_name("CON");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes</title>
    <link rel="stylesheet" type="text/css" href="styles.css?v=3">
    <?php include "header_css.php"; ?>
</head>

<body class="fade-in">

    <?php include "sidebar.php"; ?>


    <div class="container">
        <h2 class="titulo">Participantes</h2>

        <!-- Campo de búsqueda -->
        <div style="margin-bottom: 20px; display: flex; justify-content: center;">
            <input type="text" id="busqueda" placeholder="Buscar por nombre, sucursal, vendedor, etc." 
                   style="width: 100%; max-width: 500px; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: white;">
        </div>

        <div class="tabla-scroll">
            <div id="resultado">
                <!-- Aquí se mostrarán los resultados de la búsqueda -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var busquedaInput = document.getElementById("busqueda");
            if (busquedaInput) {
                busquedaInput.addEventListener("input", function () {
                    var busqueda = busquedaInput.value;
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "Consulta.php", true);
                    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState == 4 && xhr.status == 200) {
                            document.getElementById("resultado").innerHTML = xhr.responseText;
                        }
                    };
                    xhr.send("busqueda=" + encodeURIComponent(busqueda) + "&id=" + encodeURIComponent("<?php echo $id; ?>"));
                });
                busquedaInput.dispatchEvent(new Event('input'));
            }
        });
    </script>

</body>

</html>


