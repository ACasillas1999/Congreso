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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <link rel="stylesheet" href="styles3.css">
    <?php include "header_css.php"; ?>
    <title>Nueva Ubicacion</title>
</head>
<body>
    
<header class="header">
    <div class="logo">Agregar Ubicacion</div>
    <nav class="navbar">
        <ul>
            <li class="nav-item"><a href='index.php' class="nav-link">Volver</a></li>
        </ul>
    </nav>
</header>
 
<p></p>
    
<form action="FuncionNuevoRegistroUbicacion.php" method="POST">
    
    <label for="Nombre">Nombre de la Ubicacion:</label>
    <input type="text" id="Nombre" name="Nombre" required><br><br>

    <label for="Direccion">Direccion:</label>
    <input type="text" id="Direccion" name="Direccion" required><br><br>

    <label for="Salones">Cantidad de Salones:</label>
    <input type="number" id="Salones" name="Salones" required min="1" oninput="calcularCapacidadTotal()"><br><br>

    <label for="Capacidad_por_salon">Capacidad por salón:</label>
    <input type="number" id="Capacidad_por_salon" name="Capacidad_por_salon" required min="1" oninput="calcularCapacidadTotal()"><br><br>

    <label for="Capacidad_total">Capacidad Total:</label>
    <input type="text" id="Capacidad_total" name="Capacidad_total" readonly><br><br>

    <label for="Capacidad_total_actual">Capacidad Total Actual:</label>
    <input type="text" id="Capacidad_total_actual" name="Capacidad_actual" readonly><br><br>

    <input type="submit" value="Agregar Ubicacion">
</form>

<script>
function calcularCapacidadTotal() {
    const salones = document.getElementById('Salones').value;
    const capacidadPorSalon = document.getElementById('Capacidad_por_salon').value;
    const capacidadTotal = salones * capacidadPorSalon;

    document.getElementById('Capacidad_total').value = capacidadTotal;
    // Por simplicidad, la capacidad total actual será igual a la capacidad total
    document.getElementById('Capacidad_total_actual').value = capacidadTotal;
}
</script>

</body>
</html>
