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
<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

//echo "ID recibido: " . htmlspecialchars($id);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="icon" type="image/png" href="/congreso/educacion.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Evento</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">


    <style>
        :root {
            --primary: #1976d2;
            --light: #f4f6f9;
            --dark: #1e1e1e;
            --border: #ccc;
            --radius: 12px;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Acordeón general */
        .acordeon-section {
            margin-bottom: 30px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: white;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .acordeon-header {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }

        .acordeon-content {
            display: none;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }

        .acordeon-active .acordeon-content {
            display: block;
                background: #5894edd1;
        }

        /* Fechas y actividades (cards) */
        .agenda-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            margin-top: 20px;
        }

        .agenda-item {
  background: #0077cc;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: var(--shadow);
        }

        .agenda-item h3 {
            margin-bottom: 10px;
            font-size: 17px;
            color: var(--primary);
        }

        .agenda-item p {
            margin-bottom: 6px;
            font-size: 14px;
        }

        .agenda-item button {
            margin-top: 10px;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            background-color: var(--primary);
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .agenda-item button:hover {
            background-color: #135ca1;
        }

        /* Tabla */
        .mi-tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        .mi-tabla th {
            background-color: var(--primary);
            color: white;
            padding: 12px;
            text-align: center;
        }

        .mi-tabla td {
            padding: 10px;
            border: 1px solid var(--border);
            text-align: center;
        }

        .mi-tabla tr:nth-child(even) {
            background-color: #ff2121ff;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mensaje-vacio {
  background-color: #e8f4fd;
  color: #0c4a6e;
  border: 1px solid #b6e0fe;
  padding: 14px 18px;
  border-radius: 10px;
  margin: 20px 0;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.mensaje-vacio i {
  font-size: 18px;
  color: #0077cc;
}

        
:root {
  --primary: #1e2a78;
  --light: #f4f6f9;
  --dark: #1c1c1c;
  --border: #ccc;
  --radius: 12px;
  --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* ====================== TÍTULO NEÓN ====================== */
.titulo-neon-evento {
  font-size: 45px;
  font-weight: bold;
  text-align: center;
  color: var(--primary);
  margin-top: 30px;
  margin-bottom: 20px;
  text-shadow:
    0 0 5px var(--primary),
    0 0 10px var(--primary),
    0 0 20px var(--primary),
    0 0 40px var(--primary);
  animation: parpadeoNeon 1.8s infinite alternate;
}

@keyframes parpadeoNeon {
  0% {
    opacity: 1;
    text-shadow:
      0 0 5px var(--primary),
      0 0 10px var(--primary),
      0 0 20px var(--primary),
      0 0 40px var(--primary);
  }
  100% {
    opacity: 0.6;
    text-shadow:
      0 0 2px var(--primary),
      0 0 5px var(--primary),
      0 0 10px var(--primary),
      0 0 20px var(--primary);
  }
}

/* ====================== TABLAS ====================== */
.mi-tabla {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  font-size: 14px;
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: var(--shadow);
}

.mi-tabla th {
  background-color: var(--primary);
  color: white;
  padding: 12px;
  text-align: center;
  font-weight: bold;
  text-transform: uppercase;
}

.mi-tabla td {
  background-color: #ffffff;   /* fondo claro */
  color: #1e2a78 !important;   /* texto azul fuerte, se verá bien */
  font-weight: 500;
}
.mi-tabla tr:nth-child(even) {
  background-color: #f1f5fa;
}

/* ====================== ACORDEÓN ====================== */
.acordeon-section {
  margin-bottom: 30px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: white;
  box-shadow: var(--shadow);
  overflow: hidden;
}

.acordeon-header {
  background-color: var(--primary);
  color: white;
  padding: 15px 20px;
  font-weight: bold;
  cursor: pointer;
  font-size: 16px;
  transition: background 0.3s;
}

.acordeon-header:hover {
  background-color: #151f5c;
}

.acordeon-content {
  display: none;
  padding: 20px;
  animation: fadeIn 0.3s ease;
}

.acordeon-active .acordeon-content {
  display: block;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ====================== CARDS (Agenda por día) ====================== */
.agenda-item {
  background: linear-gradient(135deg, #1e2a78, #a77542);
  color: white;
  border: none;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
  overflow: hidden;
}

.agenda-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.4);
}

/* Icono decorativo opcional */
.agenda-item::before {
  content: "📅";
  position: absolute;
  top: 16px;
  right: 20px;
  font-size: 24px;
  opacity: 0.2;
}

/* Para los textos internos (por si los tienes) */
.agenda-item h3 {
  margin: 0 0 8px;
  font-size: 20px;
  color: #fff;
}

.agenda-item p {
  margin: 0;
  font-size: 16px;
  color: #f8f8f8;
}


/* ====================== MENSAJES VACÍOS ====================== */
.mensaje-vacio {
  background-color: #e8f4fd;
  color: #0c4a6e;
  border: 1px solid #b6e0fe;
  padding: 14px 18px;
  border-radius: 10px;
  margin: 20px 0;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: var(--shadow);
}

.mensaje-vacio i {
  font-size: 18px;
  color: var(--primary);
}


    </style>

</head>

<body class="fade-in">

    <div class="sidebar">
        <ul>
            <img src='\Congreso\img\Logo.png' alt='Estaditicas ' class='icono-AddChofer'
                style='max-width: 90%; height: auto;'>

            <li><a href="/Congreso/Agregar_Actividad.php?id=<?php echo htmlspecialchars($id); ?>">
                    Agregar Actividad
                </a></li>
            <li><a href="/Congreso/Participantes.php?id=<?php echo htmlspecialchars($id); ?>">
                    Participantes
                </a></li>
            <li><a href="puntos_proveedor/agregar_proveedor_evento.php">Agregar Proveedores</a></li>

            <li><a href="/Congreso/Estadisticas.php?id=<?php echo htmlspecialchars($id); ?>">
                    Estadistica
                </a></li>
<li>
                <a class="btn" href="editar_agenda_evento.php?id=<?php echo htmlspecialchars($id); ?>">Editar agenda</a>
</li>

            <li>
                <a href="/Congreso/estadisticas_vendedores.php?evento=<?= htmlspecialchars($id) ?>">
                    <i class="fas fa-chart-bar"></i>
                    Estadísticas Vendedores
                </a>
            </li>
            <li>
                <a href="/Congreso/estadisticas_proveedores.php?evento=<?= htmlspecialchars($id) ?>">
                    📊 Estadísticas Proveedores
                </a>
            </li>
            <li>
                <a href="/Congreso/premios_panel.php?id=<?= htmlspecialchars($id) ?>">
                    🎁 Panel de Premios
                </a>
            </li>

<li>
    <a href="/Congreso/estadisticas_facturas.php?evento=<?= htmlspecialchars($id) ?>">
        🧾 Estadísticas de Facturas
    </a>
</li>


            <li class="corner-left-bottom"><a href="index.php">
                    Volver

                </a></li>
        </ul>
    </div>

    <div class="content">

    </div>


    <div class="container">
        <h2 class="titulo">
            <?php
            // Mostrar el nombre del evento en el que estoy
            require_once __DIR__ . "/Conexiones/Conexion.php";
            $nombre_evento = "";
            if ($id > 0) {
                $sql_nombre = "SELECT name_evento FROM evento WHERE ID = ?";
                if ($stmt_nombre = $conn->prepare($sql_nombre)) {
                    $stmt_nombre->bind_param("i", $id);
                    $stmt_nombre->execute();
                    $stmt_nombre->bind_result($nombre_evento);
                    $stmt_nombre->fetch();
                    $stmt_nombre->close();
                }
            }
            ?>
            <div class="chart-title">
                <?php echo $nombre_evento ? "Evento: " . htmlspecialchars($nombre_evento) : "Evento no encontrado"; ?>
            </div>
        </h2>
        <p></p>
        <div id="resultado">
        </div>



        <?php
        // Establecer la conexión a la base de datos
        require_once __DIR__ . "/Conexiones/Conexion.php";

        ////-------------------------------------------------------------------------------------------////
        
        // Obtener el ID de la URL
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id > 0) {
            // Consulta SQL con parámetro
            $sql = "SELECT ID, name_evento, ubicacion, duracion, estado, fecha_inicio 
            FROM evento 
            WHERE ID = ?";

            // Preparar la consulta
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    echo '<div class="acordeon-section">';
                    echo '<div class="acordeon-header">📝 Información del Evento</div>';
                    echo '<div class="acordeon-content">';
                    echo "<table class='mi-tabla' border='1'>";
                    echo "<tr><th>Nombre del Evento</th><th>Ubicación</th><th>Duración</th><th>Estado</th><th>Fecha de Inicio</th><th>Acción</th></tr>";

                    $ubicacion = '';

                    while ($row = $result->fetch_assoc()) {
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
                        echo "<td>" . $row["name_evento"] . "</td>";
                        echo "<td>" . $row["ubicacion"] . "</td>";
                        echo "<td>" . $row["duracion"] . "</td>";
                        echo "<td style='background-color: $color;'>" . $row["estado"] . "</td>";
                        echo "<td>" . $row["fecha_inicio"] . "</td>";
                        echo "<td><a href='Actualizar_Evento.php?id=" . $row["ID"] . "'>Actualizar</a></td>";
                        echo "</tr>";
                        echo "</table>";
                        echo '</div>';
                        echo '</div>';
                        $ubicacion = $row["ubicacion"];
                    }
                    echo "</table>";
                } else {
                    echo "No se encontraron resultados.";
                }
                $stmt->close();
            } else {
                echo "Error al preparar la consulta: " . $conn->error;
            }
            ////-------------------------------------------------------------------------------------------////
            // Consulta para obtener el resumen de la ubicación
            if (!empty($ubicacion)) {
                $ubicacion_sql = "SELECT DISTINCT
        U.Nombre,
        U.Direccion,
        U.Salones, 
        U.Capacidad_por_salon,
        U.Capacidad_total,
        E.capacidad,
        (
            SELECT COUNT(*) 
            FROM participante P 
            WHERE P.ID_Evento = E.ID
        ) AS Total_Participantes
  FROM
      ubicaciones U
      JOIN evento E ON U.Nombre = E.ubicacion
  WHERE U.Nombre = ? AND E.ID = $id;";


                if ($stmt = $conn->prepare($ubicacion_sql)) {
                    $stmt->bind_param("s", $ubicacion);
                    $stmt->execute();
                    $ubicacion_result = $stmt->get_result();

                    if ($ubicacion_result && $ubicacion_result->num_rows > 0) {

                        echo '<div class="acordeon-section">';
                        echo '<div class="acordeon-header">📍 Ubicación del Evento</div>';
                        echo '<div class="acordeon-content">';
                        echo "<table class='mi-tabla' border='1'>";
                        echo "<table class='mi-tabla' border='1'>";
                        echo "<tr><th>Nombre</th><th>Dirección</th><th>Salones</th><th>Capacidad por Salón</th><th>Participantes Actuales</th><th>Capacidad del evento</th></tr>";

                        while ($ubicacion_row = $ubicacion_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $ubicacion_row["Nombre"] . "</td>";
                            echo "<td>" . $ubicacion_row["Direccion"] . "</td>";
                            echo "<td>" . $ubicacion_row["Salones"] . "</td>";
                            echo "<td>" . $ubicacion_row["Capacidad_por_salon"] . "</td>";
                            echo "<td>" . $ubicacion_row["Total_Participantes"] . "</td>";
                            echo "<td>" . $ubicacion_row["capacidad"] . "</td>";


                            echo "</tr>";

                            echo "</table>";
                            echo '</div>';
                            echo '</div>';
                        }
                        echo "</table>";
                    } else {
                        echo "No se encontró información para la ubicación especificada.";
                    }
                    $stmt->close();
                } else {
                    echo "Error al preparar la consulta de ubicación: " . $conn->error;
                }
            }

            ////-------------------------------------------------------------------------------------------//// 
            /*/ Consulta SQL para obtener el resumen de las actividades del evento
        $agenda_sql = "SELECT Salon, Fecha, Horario, Actividad 
        FROM agenda 
        WHERE ID_Evento = ? 
        ORDER BY Fecha, Horario";

        if ($stmt = $conn->prepare($agenda_sql)) {
        // Vincular el parámetro
        $stmt->bind_param("i", $id);

        // Ejecutar la consulta
        $stmt->execute();

        // Obtener el resultado
        $agenda_result = $stmt->get_result();

        // Verificar si la consulta fue exitosa
        if ($agenda_result === false) {
        echo "Error en la consulta de agenda: " . $stmt->error;
        } else {
        if ($agenda_result->num_rows > 0) {
        // Mostrar el resumen de las actividades del evento
        echo "<h2 class='titulo'><div class='chart-title'>Agenda del Evento</div></h2>";
        echo "<p></p>";
        echo "<table class='mi-tabla' border='1'>";
        echo "<tr><th>Salón</th><th>Fecha</th><th>Horario</th><th>Actividad</th></tr>";

        while ($agenda_row = $agenda_result->fetch_assoc()) {
         echo "<tr>";
         echo "<td>" . $agenda_row["Salon"] . "</td>";
         echo "<td>" . $agenda_row["Fecha"] . "</td>";
         echo "<td>" . $agenda_row["Horario"] . "</td>";
         echo "<td>" . $agenda_row["Actividad"] . "</td>";
         echo "</tr>";
        }
        echo "</table>";
        } else {
        echo "No se encontraron actividades para este evento.";
        }
        }

        // Cerrar la consulta preparada
        */
            ////-------------------------------------------------------------------------------------------////
        
            ////-------------------------------------------------------------------------------------------////
// Consulta SQL para obtener las actividades del evento
            $actividades_sql = "SELECT ID, Actividad, Descripcion,Puntos_Default 
FROM actividades 
WHERE ID_Evento = ? 
ORDER BY Actividad";

            if ($stmt = $conn->prepare($actividades_sql)) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $actividades_result = $stmt->get_result();

                if ($actividades_result && $actividades_result->num_rows > 0) {


                    echo '<div class="acordeon-section">';
                    echo '<div class="acordeon-header">📚 Actividades del Evento</div>';
                    echo '<div class="acordeon-content">';
                    echo "<table class='mi-tabla' border='1'>";
                    echo "<tr><th>ID</th><th>Actividad</th><th>Descripción</th><th>Puntos</th><th>Accion</th></tr>";

                    while ($actividad_row = $actividades_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($actividad_row["ID"]) . "</td>";
                        echo "<td>" . htmlspecialchars($actividad_row["Actividad"]) . "</td>";
                        echo "<td>" . htmlspecialchars($actividad_row["Descripcion"]) . "</td>";
                        echo "<td>" . htmlspecialchars($actividad_row["Puntos_Default"]) . "</td>";
                        echo "<td><a href='Actividades.php?id=" . htmlspecialchars($actividad_row["ID"]) . "'>Ver Actividad</a></td>";
                        echo "</tr>";


                    }
                    echo "</table>";

                    echo "</table>";
                    echo '</div>';
                    echo '</div>';
                } else {
echo "<div class='mensaje-vacio'><i>📄</i> No se encontraron actividades para este evento.</div>";
                }
                $stmt->close();
            } else {
                echo "Error al preparar la consulta de actividades: " . htmlspecialchars($conn->error);
            }
            /////////---------------------------------------------------------------------------------////////
        
            // Consulta SQL para obtener las fechas y actividades del evento
            $agenda_sql = "SELECT Fecha, Actividad 
               FROM agenda 
               WHERE ID_Evento = ? 
               ORDER BY Fecha";

            if ($stmt = $conn->prepare($agenda_sql)) {
                // Vincular el parámetro
                $stmt->bind_param("i", $id);

                // Ejecutar la consulta
                $stmt->execute();

                // Obtener el resultado
                $agenda_result = $stmt->get_result();

                // Verificar si la consulta fue exitosa
                if ($agenda_result === false) {
                    echo "Error en la consulta de agenda: " . $stmt->error;
                } else {
                    if ($agenda_result->num_rows > 0) {
                        // Array para agrupar actividades por fecha
                        $agenda_array = array();

                        while ($agenda_row = $agenda_result->fetch_assoc()) {
                            $fecha = $agenda_row["Fecha"];
                            $actividad = $agenda_row["Actividad"];

                            // Agrupar actividades por fecha y evitar duplicados
                            if (!isset($agenda_array[$fecha])) {
                                $agenda_array[$fecha] = array();
                            }
                            if (!in_array($actividad, $agenda_array[$fecha])) {
                                $agenda_array[$fecha][] = $actividad;
                            }
                        }

                        // Mostrar el resumen de las actividades del evento en un grid
                        echo '<div class="acordeon-section">';
                        echo '<div class="acordeon-header">📅 Fechas y Actividades del Evento</div>';
                        echo '<div class="acordeon-content">';
                        echo "<div class='agenda-grid'>"; // Contenedor del grid
        
                        // Iterar sobre el array para mostrar las actividades agrupadas por fecha
                        foreach ($agenda_array as $fecha => $actividades) {
                            echo "<div class='agenda-item'>";
                            echo "<h3>Fecha: " . $fecha . "</h3>";
                            foreach ($actividades as $actividad) {
                                // Si la actividad está vacía, muestra solo una entrada vacía
                                if (empty($actividad)) {
                                    echo "<p>Actividad: Sin actividad</p>";
                                    break; // Salir del bucle después de mostrar la primera actividad vacía
                                } else {
                                    echo "<p>Actividad: " . $actividad . "</p>";
                                }
                            }
                            // Botón para ver el horario por día
                            echo "<button onclick=\"location.href='ver_horario_dia.php?id=" . $id . "&fecha=" . urlencode($fecha) . "'\">Ver Horario de este Día</button>";
                            echo "</div>";
                        }

                        echo "</div>"; // Fin del grid
                        echo "</div>"; // agenda-grid
                        echo '</div>';
                        // Botón para ver el horario completo
                        echo "<div class='ver-horario-completo'>";
                        //  echo "<button onclick=\"location.href='ver_horario_completo.php?id=" . $id . "'\">Ver Horario Completo</button>";
                        echo "</div>";
                    } else {
                        echo "No se encontraron actividades para este evento.";
                    }
                }

                $stmt->close();
            } else {
                echo "Error al preparar la consulta de agenda: " . $conn->error;
            }

            echo "<p></p>";

            // Mostrar proveedores asignados a este evento
            $proveedor_sql = "SELECT p.ID, p.NombreProveedor, p.Puntos, u.password_visible 
FROM proveedor_evento p 
LEFT JOIN usuarios u ON u.username = p.NombreProveedor 
WHERE p.ID_Evento = ? AND p.Activo = 1";

            if ($stmt = $conn->prepare($proveedor_sql)) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $proveedor_result = $stmt->get_result();


                if ($proveedor_result && $proveedor_result->num_rows > 0) {
                    echo '<div class="acordeon-section">';
                    echo '<div class="acordeon-header">🤝 Proveedores Asignados</div>';
                    echo '<div class="acordeon-content">';
                    echo "<table class='mi-tabla' border='1'>";
                    echo "<tr><th>Nombre del Proveedor</th><th>Puntos</th><th>Contraseña</th><th>Acciones</th></tr>";

                    while ($row = $proveedor_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row["NombreProveedor"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["Puntos"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["password_visible"] ?? 'No disponible') . "</td>";
                        echo "<td>
    <a href='puntos_proveedor/editar_proveedor_evento.php?id=" . $row["ID"] . "&evento=" . $id . "'>Editar</a> |
    <a href='puntos_proveedor/eliminar_proveedor_evento.php?id=" . $row["ID"] . "&evento=" . $id . "' onclick=\"return confirm('¿Seguro que quieres marcar como eliminado este proveedor?')\">Eliminar</a> |
    <a href='puntos_proveedor/regenerar_password.php?usuario=" . urlencode($row["NombreProveedor"]) . "' onclick=\"return confirm('¿Regenerar contraseña para este proveedor?')\">🔁 Regenerar</a>
</td>";


                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</table>";
                    echo '</div>';
                    echo '</div>';
                } else {
echo "<div class='mensaje-vacio'><i>🤝</i>No hay proveedores registrados para este evento.</div>";
                }

                $stmt->close();
            } else {
                echo "Error al preparar la consulta de proveedores: " . $conn->error;
            }

            ////-------------------------------------------------------------------------------------------////
        
        } else {
            echo "ID no válido.";
        }



        // Ahora mostrar las estadísticas de puntos por proveedor
        
        $sql_stats = "SELECT 
    pp.usuario AS Proveedor, 
    COUNT(*) AS Escaneos, 
    SUM(pp.puntos) AS TotalPuntos,
    MAX(pp.fecha) AS UltimoEscaneo
FROM puntos_proveedor pp
JOIN proveedor_evento pe ON pp.usuario = pe.NombreProveedor
WHERE pe.ID_Evento = ?
GROUP BY pp.usuario
ORDER BY TotalPuntos DESC";

        if ($stmt = $conn->prepare($sql_stats)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stats_result = $stmt->get_result();

            if ($stats_result->num_rows > 0) {
                echo '<div class="acordeon-section">';
                echo '<div class="acordeon-header">📊 Estadísticas de Proveedores</div>';
                echo '<div class="acordeon-content">';
                echo "<table class='mi-tabla' border='1'>";
                echo "<tr><th>Proveedor</th><th>Total Escaneos</th><th>Total de Puntos</th><th>Último Escaneo</th></tr>";

                while ($row = $stats_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["Proveedor"]) . "</td>";
                    echo "<td>" . $row["Escaneos"] . "</td>";
                    echo "<td>" . $row["TotalPuntos"] . "</td>";
                    echo "<td>" . $row["UltimoEscaneo"] . "</td>";
                    echo "</tr>";
                }

                echo "</table>";
                echo "</table>";
                echo '</div>';
                echo '</div>';
            } else {
echo "<div class='mensaje-vacio'><i>🔍</i>No hay registros de escaneos para este evento.</div>";
            }

            $stmt->close();
        } else {
            echo "Error en la consulta de estadísticas: " . $conn->error;
        }
        $conn->close();


        ?>





    </div>

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

    <script src="animacion.js"></script>


    <script>
        document.querySelectorAll(".acordeon-header").forEach(header => {
            header.addEventListener("click", () => {
                const section = header.parentElement;
                section.classList.toggle("acordeon-active");
            });
        });
    </script>

</body>

</html>