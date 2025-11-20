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
// Incluye el archivo de conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Verifica si el ID de la actividad está presente en la URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Prepara la consulta para obtener los detalles de la actividad
    $actividad_sql = "SELECT ID, Actividad, Descripcion, ID_Evento, capacidad, Puntos_Default FROM actividades WHERE ID = ?";

    if ($stmt_actividad = $conn->prepare($actividad_sql)) {
        $stmt_actividad->bind_param("i", $id);
        $stmt_actividad->execute();
        $actividad_result = $stmt_actividad->get_result();

        if ($actividad_result && $actividad_result->num_rows > 0) {
            // Obtiene los datos de la actividad
            $actividad_row = $actividad_result->fetch_assoc();
            $id_evento = $actividad_row['ID_Evento'];
            ?>

            <!DOCTYPE html>
            <html lang="es">

            <head>
                <meta charset="UTF-8">
                <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Detalles de la Actividad</title>
                <link rel="stylesheet" type="text/css" href="styles.css">
                <script>
                    function confirmarAgendar() {
                        return confirm("¿Estás seguro de que deseas agendar esta actividad?");
                    }

                    function agendarActividad(fecha, hora, salon, idActividad) {
                        if (confirm('¿Estás seguro de que deseas agendar esta actividad para la fecha ' + fecha + ', hora ' + hora + ', en el salón ' + salon + '?')) {
                            // Configurar los valores del formulario oculto
                            document.getElementById('fecha').value = fecha;
                            document.getElementById('hora').value = hora;
                            document.getElementById('salon').value = salon;
                            document.getElementById('id_actividad').value = idActividad;

                            // Enviar la solicitud AJAX
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', 'agendar_actividad.php', true);
                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            xhr.onload = function () {
                                if (xhr.status >= 200 && xhr.status < 400) {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        alert('Actividad agendada con éxito.');
                                        // Aquí puedes actualizar la agenda en la página si es necesario
                                    } else {
                                        alert('Error al agendar la actividad: ' + response.error);
                                    }
                                } else {
                                    alert('Error en la solicitud.');
                                }
                            };
                            xhr.send(new URLSearchParams(new FormData(document.getElementById('agendar-form'))).toString());
                        }
                    }

                    document.addEventListener('DOMContentLoaded', function () {
                        // Aquí puedes agregar la lógica adicional si es necesario
                    });
                </script>


                <style>
                    .agenda-tabla {
  margin-top: 40px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 16px;
  box-shadow: 0 0 20px rgba(0,255,255,0.07);
}

.agenda-dia {
  font-size: 22px;
  font-weight: 600;
  margin-bottom: 20px;
  color: #90caf9;
  text-shadow: 0 0 6px rgba(144, 202, 249, 0.4);
  border-bottom: 1px solid rgba(255,255,255,0.1);
  padding-bottom: 10px;
}

.tabla-agenda {
  width: 100%;
  border-collapse: collapse;
  color: #fff;
}

.tabla-agenda th, .tabla-agenda td {
  border: 1px solid rgba(255,255,255,0.1);
  padding: 12px;
  text-align: center;
}

.tabla-agenda th {
  background-color: #1e2a78;
  font-weight: 600;
  text-transform: uppercase;
}

.tabla-agenda td {
  background-color: rgba(255,255,255,0.02);
  vertical-align: middle;
}

.tabla-agenda tr:hover td {
  background-color: rgba(255,255,255,0.05);
}

                </style>
            </head>

            <body class="fade-in">

                <div class="sidebar">
                    <ul>
                        <!--   <li><a href="/Congreso/Registrar">Agregar</a></li>-->
                        <li class="corner-left-bottom"><a
                                href="Evento_inicio.php?id=<?php echo htmlspecialchars($id_evento); ?>">Volver</a></li>
                    </ul>
                </div>

                <div class="container">
                    <h2 class="titulo">Detalles de la Actividad</h2>
                    <table class="mi-tabla" border="1">
                        <tr>
                            <th>ID</th>
                            <td><?php echo htmlspecialchars($actividad_row['ID']); ?></td>
                        </tr>
                        <tr>
                            <th>Actividad</th>
                            <td><?php echo htmlspecialchars($actividad_row['Actividad']); ?></td>
                        </tr>
                        <tr>
                            <th>Descripción</th>
                            <td><?php echo htmlspecialchars($actividad_row['Descripcion']); ?></td>
                           

                        </tr>
                        
                        <tr>
                            
                             <th>Puntos</th>
                            <td><?php echo htmlspecialchars($actividad_row['Puntos_Default']); ?></td>
                            </tr>
                        <tr>
                            <th> Cap. Participantes</th>
                            <td><?php echo htmlspecialchars($actividad_row['capacidad']); ?></td>

                        </tr>
                    </table>
                    <p></p>
                    <button class="button"
                        onclick="window.location.href='Actualizar_Actividad.php?id=<?php echo $id; ?>'">Actualizar
                        Actividad</button>

                    <?php
                    // Consulta SQL para verificar si la actividad ya está agendada
                    $agenda_sql = "SELECT Fecha, Horario, Salon FROM agenda WHERE ID_Evento = ? AND Actividad = ? ORDER BY Fecha";
                    if ($stmt_agenda = $conn->prepare($agenda_sql)) {
                        $stmt_agenda->bind_param("is", $id_evento, $actividad_row['Actividad']);
                        $stmt_agenda->execute();
                        $agenda_result = $stmt_agenda->get_result();

                        if ($agenda_result && $agenda_result->num_rows > 0) {
                            echo "<h2 class='titulo'>Detalles de la Actividad en la Agenda</h2>";
                            echo "<table class='mi-tabla' border='1'>";
                            echo "<tr><th>Horario</th><th>Salón</th><th>Fecha</th></tr>";

                            while ($agenda_row = $agenda_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($agenda_row['Horario']) . "</td>";
                                echo "<td>" . htmlspecialchars($agenda_row['Salon']) . "</td>";
                                echo "<td>" . htmlspecialchars($agenda_row['Fecha']) . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<p>Esta actividad aún no ha sido agendada.</p>";
                        }
                        $stmt_agenda->close(); // Cierra el stmt para agenda
                    } else {
                        echo "Error al preparar la consulta de agenda: " . htmlspecialchars($conn->error);
                    }

                    // Consulta SQL para obtener las fechas, horas, actividades y salones del evento
                    $agenda_sql = "SELECT Fecha, Horario, Actividad, Salon FROM agenda WHERE ID_Evento = ? ORDER BY Horario";
                    if ($stmt_agenda = $conn->prepare($agenda_sql)) {
                        $stmt_agenda->bind_param("i", $id_evento);
                        $stmt_agenda->execute();
                        $agenda_result = $stmt_agenda->get_result();

                        if ($agenda_result && $agenda_result->num_rows > 0) {
                            $agenda_array = array();
                            while ($agenda_row = $agenda_result->fetch_assoc()) {
                                $fecha = $agenda_row["Fecha"];
                                $hora = $agenda_row["Horario"];
                                $salon = $agenda_row["Salon"];
                                $actividad = $agenda_row["Actividad"];

                                if (!isset($agenda_array[$fecha])) {
                                    $agenda_array[$fecha] = array();
                                }
                                $agenda_array[$fecha][] = array("hora" => $hora, "actividad" => $actividad, "salon" => $salon);
                            }

                            echo "<h2 class='titulo'><div class='chart-title'>Fechas y Actividades del Evento</div></h2>";
                            echo "<div class='agenda-grid'>";

                            echo "<div class='agenda-grid'>";
                           foreach ($agenda_array as $fecha => $actividades) {
    echo "<div class='agenda-tabla'>";
    echo "<h3 class='agenda-dia'>🗓️ Fecha: " . htmlspecialchars($fecha) . "</h3>";

    // Ordenar actividades por hora y organizar por salón
    $horarios = [];

    foreach ($actividades as $actividad) {
        $hora = $actividad['hora'];
        $salon = $actividad['salon'];
        $nombreActividad = $actividad['actividad'];

        $horarios[$hora][$salon] = $nombreActividad;
    }

    echo "<table class='tabla-agenda'>";
    echo "<thead><tr><th>Hora</th><th>Salón 1</th><th>Salón 2</th></tr></thead><tbody>";

    foreach ($horarios as $hora => $salones) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($hora) . "</td>";

        for ($i = 1; $i <= 2; $i++) {
            $salon_nombre = "Salon $i";
            $actividad_texto = $salones[$salon_nombre] ?? "Vacio";

            $color = $actividad_texto !== 'Vacio' ? 'badge-verde' : 'badge-rojo';

            echo "<td>";
            echo "<div class='badge $color'>" . htmlspecialchars($actividad_texto) . "</div>";
            echo "<button class='boton-agendar' onclick=\"agendarActividad('" . htmlspecialchars($fecha) . "', '" . htmlspecialchars($hora) . "', '" . htmlspecialchars($salon_nombre) . "', '" . htmlspecialchars($id) . "')\">Agendar</button>";
            echo "</td>";
        }

        echo "</tr>";
    }

    echo "</tbody></table>";

    echo "<button class='boton-consultar mt-3' onclick=\"location.href='ver_horario_dia.php?id=" . htmlspecialchars($id_evento) . "&fecha=" . urlencode($fecha) . "'\">📅 Ver Horario de este Día</button>";
    echo "</div>"; // cierre de agenda-tabla
}

                            echo "</div>"; // agenda-grid
        

                        } else {
                            echo "No se encontraron actividades para este evento.";
                        }
                    } else {
                        echo "Error al preparar la consulta de agenda: " . htmlspecialchars($conn->error);
                    }
                    ?>

                   <form id="agendar-form" style="display: none;">
  <input type="hidden" id="fecha" name="fecha">
  <input type="hidden" id="hora" name="hora">
  <input type="hidden" id="salon" name="salon">
  <input type="hidden" id="id_actividad" name="id_actividad">
  <input type="hidden" id="id_evento" name="id_evento" value="<?= htmlspecialchars($id_evento) ?>">
</form>


                </div>
            </body>

            </html>

            <?php
        } else {
            echo "No se encontraron detalles para esta actividad.";
        }
        $stmt_actividad->close(); // Cierra el stmt para actividad
    } else {
        echo "Error al preparar la consulta de detalles de la actividad: " . htmlspecialchars($conn->error);
    }

} else {
    echo "ID de actividad no válido.";
}

// Cierra la conexión a la base de datos
$conn->close();
?>