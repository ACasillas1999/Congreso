<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener datos del formulario
$id_actividad = isset($_POST['id']) ? intval($_POST['id']) : 0;
$id_evento = isset($_POST['id_evento']) ? intval($_POST['id_evento']) : 0;
$actividad = isset($_POST['actividad']) ? $conn->real_escape_string($_POST['actividad']) : '';
$descripcion = isset($_POST['descripcion']) ? $conn->real_escape_string($_POST['descripcion']) : '';
$capacidad = isset($_POST['capacidad']) ? $conn->real_escape_string($_POST['capacidad']) : '';

// Verificar que los campos requeridos no estén vacíos
if ($id_actividad > 0 && !empty($actividad) && !empty($descripcion)) {
    // Primero, obtener el nombre antiguo de la actividad
    $sql_get_old_activity = "SELECT Actividad FROM actividades WHERE ID = $id_actividad";
    $result = $conn->query($sql_get_old_activity);

    if ($result && $row = $result->fetch_assoc()) {
        $old_activity = $row['Actividad'];

        // Actualizar la actividad en la tabla 'actividades'
        $sql_update_actividad = "UPDATE actividades
         SET Actividad = '$actividad', Descripcion = '$descripcion', capacidad = '$capacidad'
          WHERE ID = $id_actividad";

        if ($conn->query($sql_update_actividad) === TRUE) {
            // Actualizar todas las apariciones en la tabla 'agenda'
            $sql_update_agenda = "UPDATE agenda SET Actividad = '$actividad' WHERE Actividad = '$old_activity' AND ID_Evento = $id_evento";

            if ($conn->query($sql_update_agenda) === TRUE) {
                   header("Location: Actividades.php?id=$id_actividad");
                echo "Actividad actualizada exitosamente en ambas tablas.";
                // Redireccionar a otra página o mostrar un mensaje de éxito
             
            //  return redirect('Actividades.php?id=' . $id_actividad);
                exit();
            } else {
                echo "Error actualizando la actividad en la tabla 'agenda': " . $conn->error;
            }
        } else {
            echo "Error actualizando la actividad en la tabla 'actividades': " . $conn->error;
        }
    } else {
        echo "No se encontró la actividad antigua.";
    }
} else {
    echo "Por favor, completa todos los campos requeridos.";
}

// Cerrar conexión
$conn->close();
?>
