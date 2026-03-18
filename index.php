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
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos</title>
    <!-- Enlace al archivo de estilos -->
    <link rel="stylesheet" type="text/css" href="styles.css">
    <?php include "header_css.php"; ?>
    <!-- Ícono de la página -->

    <style>

#resultado{
  width:100%;
  overflow-x:auto;               /* habilita scroll horizontal si es necesario */
  -webkit-overflow-scrolling:touch; /* scroll suave en iOS */
}

/* La tabla usa todo el ancho disponible.
   Usa min-width para que las columnas no colapsen en pantallas estrechas. */
.mi-tabla{
  width:100%;
  border-collapse:collapse;
  background:rgba(0,0,0,.18);
  border-radius:12px;
  overflow:hidden; /* para radios en thead */
  min-width: 1000px; /* ajusta según tus columnas (900–1200 px) */
}

/* Estilos base de celdas */
.mi-tabla th,.mi-tabla td{
  padding:10px 12px;
  border:1px solid rgba(255,255,255,.08);
  text-align:left;
  vertical-align:middle;
  white-space:nowrap; /* evita saltos raros de texto */
}
.mi-tabla th{
  background:rgba(63,140,255,.2);
  font-weight:700;
}

/* Zebra + hover */
.mi-tabla tr:nth-child(even){ background:rgba(255,255,255,.03) }
.mi-tabla tr:hover{ background:rgba(63,140,255,.08) }

/* En móvil puedes reducir padding para que quepa más */
@media (max-width: 991px){
  .mi-tabla th,.mi-tabla td{ padding:8px 10px; }
}

/* Opcional: hacer pegajoso el encabezado cuando hay scroll horizontal/vertical */
.mi-tabla thead th{
  position:sticky; top:0; z-index:1;
}

@media (max-width: 991px){
  .mi-tabla tr[data-href] { cursor: pointer; }
}


    </style>

</head>

<body class="fade-in">

    <!-- Barra lateral -->
    <div class="sidebar">
        <ul>
            <img src='\Congreso\img\Logo.png' alt='Estadísticas' class='icono-AddChofer'
                style='max-width: 90%; height: auto;'>

            <li><a href="NuevoRegistroInicio.php">Agregar Evento</a></li>
            <li><a href="Ubicacion.php">Ubicaciones</a></li>
            <li><a href="puntos_proveedor/agregar_proveedor_evento.php">Agregar Proveedores</a></li>
            <li><a href="personalizar.php" style="color: #ff9800;">✨ Personalizar</a></li>

            <li class="reg-button">
                <form action="/Congreso/Registrar/" method="post">
                    <input type="submit" value="Nuevo Usuario">
                </form>
            </li>

            <li class="logout-button">
                <form action="logout.php" method="post">
                    <input type="submit" value="Cerrar sesión">
                </form>
            </li>
        </ul>
    </div>

    <div class="content">
        <!-- Contenido principal de tu página -->
    </div>

    <!-- Contenedor principal -->
    <div class="container">
        <h2 class="titulo">Eventos</h2>

        <!--<p>Bienvenido, <?php echo $_SESSION["username"]; ?>!</p>-->

        <!-- Contenedor para mostrar resultados de la consulta -->
        <div id="resultado">
            <?php
            // Establecer la conexión a la base de datos
            require_once __DIR__ . "/Conexiones/Conexion.php";

            // Consulta inicial sin filtro de búsqueda
            $busqueda = isset($_POST['busqueda']) ? $_POST['busqueda'] : '';

            $sql = "SELECT 
                    E.ID, 
                    E.name_evento, 
                    E.ubicacion, 
                    E.duracion, 
                    E.estado, 
                    E.fecha_inicio, 
                    E.capacidad, 
                    (
                        SELECT COUNT(*) 
                        FROM participante P 
                        WHERE P.ID_Evento = E.ID
                    ) AS Total_Participantes
                FROM 
                    evento E
                JOIN 
                    ubicaciones U 
                ON 
                    E.ubicacion = U.Nombre;";

            $result = $conn->query($sql);

            // Verificar si la consulta fue exitosa
            if ($result === false) {
                echo "Error en la consulta: " . $conn->error;
            } else {
                if ($result->num_rows > 0) {
                    // Mostrar los datos encontrados en forma de tabla
                    echo "<table class='mi-tabla' border='1'>";
                    //  echo "<tr><th>ID</th><th>Nombre del Evento</th><th>Ubicación</th><th>Duración</th><th>Estado</th><th>Fecha de Inicio</th><th>Capacidad Actual</th><th>Total Participantes</th><th>Acción</th></tr>";
                    echo "<tr><th>ID</th><th>Nombre del Evento</th><th>Ubicación</th><th>Duración</th><th>Estado</th><th>Fecha de Inicio</th><th>Total Participantes Actuales</th><th>Acción</th></tr>";

                    while ($row = $result->fetch_assoc()) {
                        // Definir el color de fondo según el estado
                        $estado = $row["estado"];
                        $color = "#FFFFFF"; // Color por defecto (blanco)
            
                        switch (strtoupper($estado)) {
                            case "CANCELADO":
                                $color = "#FFCCCC"; // ROJO pastel
                                break;
                            case "EN CURSO":
                                $color = "#a1a132ff"; // AMARILLO pastel
                                break;
                            case "FINALIZADO":
                                $color = "#ccffe6"; // VERDE pastel
                                break;
                        }

                        echo "<tr>";
                        echo "<td>" . $row["ID"] . "</td>";
                        echo "<td>" . $row["name_evento"] . "</td>";
                        echo "<td>" . $row["ubicacion"] . "</td>";
                        echo "<td>" . $row["duracion"] . "</td>";
                        echo "<td style='background-color: $color;'>" . $row["estado"] . "</td>";
                        echo "<td>" . $row["fecha_inicio"] . "</td>";
                        //  echo "<td>" . $row["capacidad_actual"] . "</td>";
                        echo "<td>" . $row["Total_Participantes"] . "</td>";
                        echo "<td><a href='Evento_inicio.php?id=" . $row["ID"] . "'>Ver Evento</a></td>";
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
        document.addEventListener("DOMContentLoaded", function () {
            // Función para manejar el evento de búsqueda en tiempo real
            document.getElementById("busqueda").addEventListener("input", function (event) {
                // Obtener la búsqueda ingresada desde el campo de texto
                var busqueda = document.getElementById("busqueda").value;

                // Realizar una solicitud AJAX para cargar los datos
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
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

    <script>
  // --- NUEVO: vincula navegación por fila en móvil ---
  function bindRowNavigation() {
    if (window.matchMedia('(min-width: 992px)').matches) return; // solo móvil

    const rows = document.querySelectorAll('.mi-tabla tr');
    rows.forEach(tr => {
      // omite la fila de encabezados
      if (tr.querySelector('th')) return;

      // busca el link "Ver Evento" en la fila
      let linkEl = tr.querySelector('a[href*="Evento_inicio.php"]');
      let href = linkEl ? linkEl.getAttribute('href') : null;

      // fallback: arma URL con el ID (primera celda)
      if (!href) {
        const idCell = tr.querySelector('td:first-child');
        if (idCell) {
          const id = (idCell.textContent || '').trim();
          if (id) href = 'Evento_inicio.php?id=' + encodeURIComponent(id);
        }
      }

      if (href) {
        tr.dataset.href = href;

        // evita duplicar listeners al refrescar
        tr.onclick = function (e) {
          const tag = e.target.tagName.toLowerCase();
          // si tocó un control interactivo, respeta su acción
          if (['a','button','input','label','select','textarea'].includes(tag)) return;
          window.location.href = this.dataset.href;
        };
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    // Vincula al cargar
    bindRowNavigation();

    // --- Tu búsqueda en tiempo real ---
    const input = document.getElementById("busqueda");
    if (input) {
      input.addEventListener("input", function () {
        var busqueda = input.value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
          if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById("resultado").innerHTML = xhr.responseText;
            // Re-vincula después de actualizar la tabla
            bindRowNavigation();
          }
        };
        xhr.send("busqueda=" + encodeURIComponent(busqueda));
      });

      // Disparo inicial
      input.dispatchEvent(new Event('input'));
    }
  });
</script>

    <script src="animacion.js"></script>
</body>

</html>