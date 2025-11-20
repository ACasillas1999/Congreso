<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener datos del formulario
$id_agenda = intval($_POST['id_agenda']);
$salon = $conn->real_escape_string($_POST['salon']);
$actividad = $conn->real_escape_string($_POST['actividad']);
$fecha = $conn->real_escape_string($_POST['fecha']);
$horario = $conn->real_escape_string($_POST['horario']);

// Actualizar los campos en la base de datos
$sql_update = "UPDATE agenda SET Salon='$salon', Actividad='$actividad', Fecha='$fecha', Horario='$horario' WHERE id=$id_agenda";

if ($conn->query($sql_update) === TRUE) {
    //echo "<a href='index.php?id=". $id_agenda ." '>Volver a la agenda</a>";
    header("Location: Clase.php?id=$id_agenda");
    // echo "Registro actualizado correctamente. <a href='index.php'>Volver a la agenda</a>";
} else {
    echo "Error al actualizar el registro: " . $conn->error;
}

// Cerrar conexión
$conn->close();
?>
