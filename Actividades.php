<?php
session_name("CON");
session_start();

// Verificar si el usuario no está logueado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$id_actividad = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_evento = isset($_GET['evento']) ? intval($_GET['evento']) : 0;
$is_list_view = false;
$actividad_row = null;
$actividades = [];
$nombre_evento = '';

if ($id_evento > 0) {
    $is_list_view = true;
    $sidebar_event_id = $id_evento;

    if ($stmt_evento = $conn->prepare("SELECT name_evento FROM evento WHERE ID = ?")) {
        $stmt_evento->bind_param("i", $id_evento);
        $stmt_evento->execute();
        $res_evento = $stmt_evento->get_result();
        $evento_row = $res_evento ? $res_evento->fetch_assoc() : null;
        $nombre_evento = $evento_row['name_evento'] ?? '';
        $stmt_evento->close();
    }

    $stmt = $conn->prepare("SELECT ID, Actividad, Descripcion, Puntos_Default, capacidad FROM actividades WHERE ID_Evento = ? ORDER BY Actividad");
    if (!$stmt) {
        echo "No fue posible consultar las actividades del evento.";
        exit;
    }
    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $result = $stmt->get_result();
    $actividades = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} elseif ($id_actividad > 0) {
    $stmt = $conn->prepare("SELECT * FROM actividades WHERE ID = ?");
    if (!$stmt) {
        echo "No fue posible consultar la actividad.";
        exit;
    }
    $stmt->bind_param("i", $id_actividad);
    $stmt->execute();
    $result = $stmt->get_result();
    $actividad_row = $result->fetch_assoc();
    $stmt->close();

    if (!$actividad_row) {
        echo "Actividad no encontrada.";
        exit;
    }

    $id_evento = (int)($actividad_row['ID_Evento'] ?? 0);
    $id = $id_actividad; // Para compatibilidad con botones de actualizar
    $sidebar_event_id = $id_evento;
} else {
    echo "ID de actividad o evento no proporcionado.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_list_view ? 'Actividades del Evento' : 'Detalles de la Actividad'; ?></title>
    <link rel="stylesheet" type="text/css" href="styles.css?v=3">
    <?php include "header_css.php"; ?>
    <style>
        body, button, input, select, textarea {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }
    </style>
</head>
<body class="fade-in">

    <?php include "sidebar.php"; ?>

    <div class="container">
        <?php if ($is_list_view): ?>
            <h2 class="titulo">
                Actividades del Evento<?php echo $nombre_evento !== '' ? ': ' . htmlspecialchars($nombre_evento) : ''; ?>
            </h2>
            <button class="button" type="button" onclick="abrirModalActividad()">
                Agregar Actividad
            </button>
            <p></p>

            <?php if (!empty($actividades)): ?>
                <table class="mi-tabla" border="1">
                    <tr>
                        <th>ID</th>
                        <th>Actividad</th>
                        <th>Descripción</th>
                        <th>Puntos</th>
                        <th>Capacidad</th>
                        <th>Acciones</th>
                    </tr>
                    <?php foreach ($actividades as $actividad): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($actividad['ID']); ?></td>
                            <td><?php echo htmlspecialchars($actividad['Actividad']); ?></td>
                            <td><?php echo htmlspecialchars($actividad['Descripcion'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($actividad['Puntos_Default'] ?? '0'); ?></td>
                            <td><?php echo htmlspecialchars($actividad['capacidad'] ?? '0'); ?></td>
                            <td>
                                <a href="Actividades.php?id=<?php echo (int)$actividad['ID']; ?>" class="btn-tabla" style="background:rgba(56,217,255,0.1); color:var(--theme-title); border:1px solid rgba(56,217,255,0.2);">Ver</a>
                                <a href="javascript:void(0)" class="btn-tabla" onclick="abrirModalEditar(<?php echo (int)$actividad['ID']; ?>, '<?php echo addslashes($actividad['Actividad']); ?>', '<?php echo addslashes($actividad['Descripcion'] ?? ''); ?>', <?php echo (int)$actividad['capacidad']; ?>, <?php echo (int)$actividad['Puntos_Default']; ?>)">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p class="note">No hay actividades registradas para este evento.</p>
            <?php endif; ?>
        <?php else: ?>
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
                    <td><?php echo htmlspecialchars($actividad_row['Descripcion'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>Puntos</th>
                    <td><?php echo htmlspecialchars($actividad_row['Puntos_Default'] ?? '0'); ?></td>
                </tr>
                <tr>
                    <th>Cap. Participantes</th>
                    <td><?php echo htmlspecialchars($actividad_row['capacidad'] ?? '0'); ?></td>
                </tr>
            </table>

            <p></p>
            <button class="button" onclick="window.location.href='Actualizar_Actividad.php?id=<?php echo $id; ?>'">
                Actualizar Actividad
            </button>
            <button class="button" onclick="window.location.href='Actividades.php?evento=<?php echo $id_evento; ?>'">
                Ver todas las actividades
            </button>

            <?php
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
                    echo "<p class='note'>Esta actividad aún no ha sido asignada a la agenda.</p>";
                }
                $stmt_agenda->close();
            }
            ?>
        <?php endif; ?>
    </div>

    <?php if ($is_list_view): ?>
        <div id="modalActividad" class="modal">
            <div class="modal-content" style="max-width: 640px; margin: 5% auto;">
                <span class="close" onclick="cerrarModalActividad()">&times;</span>
                <h3 style="color:var(--theme-title); margin-bottom:20px; border-bottom:1px solid var(--theme-border); padding-bottom:10px;">
                    Agregar Actividad
                </h3>
                <form action="Funcion_Agregar_Actividad.php" method="POST">
                    <input type="hidden" name="Evento" value="<?php echo $id_evento; ?>">
                    <input type="hidden" name="redirect_to" value="Actividades.php?evento=<?php echo $id_evento; ?>">

                    <div style="display:grid; gap:14px;">
                        <div>
                            <label for="ActividadModal" style="display:block; margin-bottom:6px;">Nombre de la Actividad:</label>
                            <input type="text" id="ActividadModal" name="Actividad" required style="width:100%; padding:12px;">
                        </div>

                        <div>
                            <label for="DescripcionModal" style="display:block; margin-bottom:6px;">Descripcion:</label>
                            <input type="text" id="DescripcionModal" name="Descripcion" required style="width:100%; padding:12px;">
                        </div>

                        <div>
                            <label for="CapacidadModal" style="display:block; margin-bottom:6px;">Capacidad:</label>
                            <input type="number" id="CapacidadModal" name="capacidad" required min="1" style="width:100%; padding:12px;">
                        </div>

                        <div>
                            <label for="PuntosModal" style="display:block; margin-bottom:6px;">Puntos por asistencia:</label>
                            <input type="number" id="PuntosModal" name="Puntos_Default" min="0" step="1" value="0" required style="width:100%; padding:12px;">
                        </div>

                        <label style="display:flex; align-items:center; gap:8px; color:var(--theme-text);">
                            <input type="checkbox" name="Exclusiva" value="1">
                            Actividad exclusiva (solo Gerente/Admin)
                        </label>
                    </div>

                    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:22px;">
                        <button type="button" class="button" onclick="cerrarModalActividad()" style="background:#4b5b70;">Cancelar</button>
                        <input type="submit" value="Guardar Actividad">
                    </div>
                </form>
            </div>
        </div>
        
        <div id="modalEditarActividad" class="modal">
            <div class="modal-content" style="max-width: 640px; margin: 5% auto;">
                <span class="close" onclick="cerrarModalEditar()">&times;</span>
                <h3 style="color:var(--theme-title); margin-bottom:20px; border-bottom:1px solid var(--theme-border); padding-bottom:10px;">
                    Editar Actividad
                </h3>
                <form action="Funcion_Actualizar_Actividad.php" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="id_evento" value="<?php echo $id_evento; ?>">
                    <input type="hidden" name="redirect_to" value="Actividades.php?evento=<?php echo $id_evento; ?>">

                    <div style="display:grid; gap:14px;">
                        <div>
                            <label for="edit_actividad" style="display:block; margin-bottom:6px;">Nombre de la Actividad:</label>
                            <input type="text" id="edit_actividad" name="actividad" required style="width:100%; padding:12px;">
                        </div>

                        <div>
                            <label for="edit_descripcion" style="display:block; margin-bottom:6px;">Descripcion:</label>
                            <textarea id="edit_descripcion" name="descripcion" required style="width:100%; padding:12px; min-height:80px; background:rgba(0,0,0,0.2); color:white; border:1px solid var(--theme-border); border-radius:8px;"></textarea>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <div>
                                <label for="edit_capacidad" style="display:block; margin-bottom:6px;">Capacidad:</label>
                                <input type="number" id="edit_capacidad" name="capacidad" required min="1" style="width:100%; padding:12px;">
                            </div>

                            <div>
                                <label for="edit_puntos" style="display:block; margin-bottom:6px;">Puntos:</label>
                                <input type="number" id="edit_puntos" name="puntos" min="0" step="1" required style="width:100%; padding:12px;">
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:22px;">
                        <button type="button" class="button" onclick="cerrarModalEditar()" style="background:#4b5b70;">Cancelar</button>
                        <input type="submit" value="Guardar Cambios">
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
    const modalActividad = document.getElementById('modalActividad');
    const modalEditarActividad = document.getElementById('modalEditarActividad');

    function abrirModalActividad() {
        if(modalActividad) modalActividad.style.display = 'block';
    }
    function cerrarModalActividad() {
        if(modalActividad) modalActividad.style.display = 'none';
    }
    
    function abrirModalEditar(id, nombre, descripcion, capacidad, puntos) {
        if(!modalEditarActividad) return;
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_actividad').value = nombre;
        document.getElementById('edit_descripcion').value = descripcion;
        document.getElementById('edit_capacidad').value = capacidad;
        document.getElementById('edit_puntos').value = puntos;
        modalEditarActividad.style.display = 'block';
    }
    function cerrarModalEditar() {
        if(modalEditarActividad) modalEditarActividad.style.display = 'none';
    }

    window.addEventListener('click', function(event) {
        if (event.target === modalActividad) cerrarModalActividad();
        if (event.target === modalEditarActividad) cerrarModalEditar();
    });
    </script>
</body>
</html>
