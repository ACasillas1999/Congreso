<?php

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


require_once __DIR__ . "/Conexiones/Conexion.php";

// Recuperar eventos en curso y su capacidad
$sql = "SELECT ID, name_evento, capacidad FROM evento WHERE estado = 'EN CURSO'";
$result = $conn->query($sql);


/*/ Recuperar eventos en curso
$sql = "SELECT ID, name_evento FROM evento WHERE estado = 'EN CURSO'";
$result = $conn->query($sql);*/

// Cerrar conexión
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles3.css">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <title>Envio WP</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

</head>
<body class = "fade-in">
<div class="background-image"></div>
<div class="overlay"></div>
<header class="header">
    <div class="logo">Envio Masivo WP</div>
    <nav class="navbar">
        <ul>
            <li class="corner-left-bottom"><a href="javascript:history.back()">Volver</a></li>
        </ul>
    </nav>
</header>
<p></p>

<form action="Mensajes_WP/Mensaje_Masivo_WP.php" method="POST">
    <label for="Evento">Evento:</label>
    <select id="Evento" name="Evento" required onchange="updateParticipantes()">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<option value='" . $row["ID"] . "' data-capacidad='" . $row["capacidad"] . "'>" . $row["name_evento"] . "</option>";
            }
        } else {
            echo "<option value=''>No hay eventos disponibles</option>";
        }
        ?>
    </select><br><br>

    
    <p id="participantes-info"></p>
    <p></p>
    
    <label for="Template">Plantilla:</label><br>
    <select id="Template" name="Template" required>
        <option value="">Selecciona una plantilla</option>
        <option value="hello_world">Test</option>
       <!-- <option value="conexion_faltan_3_dias">Conexion-Faltan 3 dias</option>
        <option value="faltan_2_dias">Conexion-Faltan 2 dias</option>
        <option value="falta_un_dia">Conexion-Falta 1 dia</option>
        <option value="conexio_gracias_por_registrarte">Conexion-Gracias por registrarte</option>
        <option value="conexion_encuesta">Conexion Encuesta</option>
        <option value="clausura">clausura</option>-->
         <option value="certificacion_sobretensiones">certificacion_sobretensiones</option>
         <option value="falta_1_dia_vallarta_day">Ya es Mañana-Vallarta Day</option>
         <option value="encuesta_vallarta_day">encuesta_vallarta_day</option>
         <option value="schneider_electric_y_grupo_ascencio_faltan_solo_4_dias">schneider y Grupo Ascencio - Faltan 4 dias</option>
         <option value="schneider_electric_y_grupo_ascencio_faltan_2_dias">
            schneider y Grupo Ascencio - Faltan 2 dias</option>
            <option value="schneider_electric_y_grupo_ascencio__nos_vemos_maana">
            schneider y Grupo Ascencio - YA ES MAÑANA WOO</option>
            <option value="schneider_electric_y_grupo_ascencio_encuesta">schneider_electric_y_grupo_ascencio_encuesta</option>
            <option value="3_dias_cnx_2025">3_dias_cnx_2025</option>
            <option value="2_dias_cnx_2025">2_dias_cnx_2025</option>
            <option value="maana_dias_cnx_2025">maana_dias_cnx_2025</option>
            <option value="hoy_dias_cnx_2025">hoy_dias_cnx_2025</option>
            <option value="puntos_cnx_2025">puntos_cnx_2025</option>

         </select><br><br>

    <input type="submit" value="Enviar">
</form>


<script>
function updateParticipantes() {
    var eventoID = $('#Evento').val();
    var capacidad = $('#Evento option:selected').data('capacidad');
    
    $.ajax({
        url: 'get_participantes.php',
        type: 'POST',
        data: { evento_id: eventoID },
        success: function(data) {
            var participantes = parseInt(data);
            var disponibles = capacidad - participantes;
            $('#participantes-info').text('Participantes registrados: ' + participantes + ' / ' + capacidad );
            
            // Verificar si se ha alcanzado la capacidad
            if (disponibles <= 0) {
                alert('Este evento ha alcanzado su capacidad máxima.');
                $('input[type="submit"]').prop('disabled', true);
            } else {
                $('input[type="submit"]').prop('disabled', false);
            }
        }
    });
}
</script>

</body>
</html>

