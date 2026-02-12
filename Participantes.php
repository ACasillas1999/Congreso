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

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var iconoWP = document.querySelector(".icono-WP");
        var imgNormalWP = "/Congreso/img/WSPVB2.png";
        var imgHoverWP = "/Congreso/img/WSPVF2.png";

        // Cambiar la imagen al pasar el mouse para Pendientes
        iconoWP.addEventListener("mouseover", function () {
            iconoWP.src = imgHoverWP;
        });

        // Cambiar de vuelta al mover el mouse fuera para Pendientes
        iconoWP.addEventListener("mouseout", function () {
            iconoWP.src = imgNormalWP;
        });
    });
</script>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participantes</title>
    <link rel="stylesheet" type="text/css" href="styles.css?v=2">

<style>

    #resultado table {
  width: 100%; /* ocupa todo el ancho disponible */
  border-collapse: collapse;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 0 10px rgba(0,0,0,0.2);
  table-layout: auto; /* permite que las columnas se ajusten al contenido */
}

#resultado th, #resultado td {
  color: white;
  padding: 10px;
  text-align: center;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  word-wrap: break-word;
  max-width: 150px; /* limita el ancho máximo de cada celda */
}

#resultado th {
  font-weight: bold;
  position: sticky;
  top: 0;
  z-index: 1;
}


</style>


</head>

<body class="fade-in">

    <div class="sidebar">
        <ul>
            <li><a href="Agregar_Participante.php?id=<?php echo $id; ?>">Agregar participante</a></li>
<?php if ($_SESSION["Rol"] === "Admin"): ?>
    <li><a href="participantes_rfc.php?id=<?php echo $id; ?>">Ver por grupo (RFC)</a></li>
<?php endif; ?>

<?php if ($_SESSION["Rol"] === "Admin"): ?>
    <li><a href="participantes_puesto.php?id=<?= $id ?>">Ver por puesto</a></li>
<?php endif; ?>


            <?php if ($_SESSION["Rol"] === "Admin"): ?>
                <li><a href="Evento_inicio.php?id=<?php echo $id; ?>">Volver</a></li>
            <?php endif; ?>

            <?php if ($_SESSION["Rol"] === "Vendedor" || "Gerente" ): ?>
                <li class="logout-button">
                    <form action="logout.php" method="post">
                        <input type="submit" value="Cerrar sesión">
                    </form>
                </li>
            <?php endif; ?>

            <!--  <li class="corner-left-bottom"><a href="Evento_inicio.php?id=<?php echo $id; ?>">Volver</a></li>-->
        </ul>
    </div>

    


        <div class="container">
            <?php if ($_SESSION["Rol"] === "Admin"): ?>
                <div class="WP-button">
                    <a href="Index_Masivo_WP.php" style="background: none; border: none; padding: 0;">
                        <img src="/Congreso/img/WSPVB2.png" alt="icono-WP" class="icono-WP"
                            style="max-width: 15%; height: auto; margin-left: auto;">
                    </a>
                </div>
            <?php endif; ?>
            <h2 class="titulo">Participantes</h2>

            <!-- Campo de búsqueda -->
            <input type="text" id="busqueda" placeholder="Buscar por nombre, sucursal, vendedor, etc.">

<div class="tabla-scroll">

            <div id="resultado">
                <!-- Aquí se mostrarán los resultados de la búsqueda -->
            </div>
</div>

        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var busquedaInput = document.getElementById("busqueda");

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

                // Para que cargue los resultados por defecto cuando se cargue la página
                busquedaInput.dispatchEvent(new Event('input'));
            });
        </script>

</body>

</html>
