<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";
$conn->set_charset("utf8mb4");

  function validarFecha($fecha) {
    return (!empty($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) ? "'$fecha'" : "NULL";
}

// Obtener los datos del formulario
$name_evento = $_POST['name_evento'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin']; // Añadido el campo fecha_fin
$duracion = $_POST['duracion'];
$estado = $_POST['estado'];
$ubicacion = $_POST['ubicacion']; // Añadido el campo ubicacion
$capacidad = $_POST['capacidad'];

// Insertar el evento en la tabla "evento"
$sql = "INSERT INTO evento (ID, name_evento, fecha_inicio, fecha_fin, duracion, estado, ubicacion, capacidad) 
        VALUES (NULL, '$name_evento', '$fecha_inicio', '$fecha_fin', '$duracion', '$estado', '$ubicacion', '$capacidad')";

if ($conn->query($sql) === TRUE) {
    // Obtener el ID del evento recién insertado
    $id_evento = $conn->insert_id;

    // Consulta para obtener la cantidad de salones
    $stmt = $conn->prepare("SELECT U.Salones FROM ubicaciones U 
                            JOIN evento E ON U.Nombre = E.ubicacion 
                            WHERE U.Nombre = ?");
    $stmt->bind_param("s", $ubicacion);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $salones = $row['Salones']; // Asumiendo que 'Salones' contiene la cantidad de salones, puedes ajustarlo según tu esquema
    } else {
        echo "No se encontraron salones para la ubicación especificada.";
        exit();
    }

    // Convertir las fechas a objetos DateTime
    $fecha_inicio_obj = new DateTime($fecha_inicio);
    $fecha_fin_obj = new DateTime($fecha_fin);

    // Iterar a través de cada día del evento
    //ESTO ES PARA QUE LO GENERE AUTOMATICAMENTE EL HORARIO 
    //$fecha_actual = clone $fecha_inicio_obj;
    /*while ($fecha_actual <= $fecha_fin_obj) {
        // Generar registros en la tabla Agenda para cada día y cada salón
        for ($salon = 1; $salon <= $salones; $salon++) {
            for ($hora = 9; $hora <= 19; $hora++) {
                // Formatear el horario (ejemplo: 9:00-10:00)
                $horario = sprintf('%02d:00-%02d:00', $hora, $hora + 1);
                
                // Insertar el registro en la tabla Agenda
                $sql_agenda = "INSERT INTO agenda (ID, ID_Evento, Salon, Fecha, Horario, Actividad) 
                                VALUES (NULL, '$id_evento', 'Salon $salon', '" . $fecha_actual->format('Y-m-d') . "', '$horario', 'Vacio')";
                if ($conn->query($sql_agenda) === TRUE) {
                    // Registro insertado correctamente
                } else {
                    echo "Error al agregar el registro a Agenda: " . $conn->error;
                }
            }
        }

        // Avanzar al siguiente día
        $fecha_actual->modify('+1 day');
    }*/ //TERMINA AQUI AUTOMATICO HORARIO

    echo '<script>alert("Evento agregado correctamente.");';
    echo 'window.location.href = "index.php";</script>';
} else {
    echo "Error al agregar el evento: " . $conn->error;
}

// Cerrar la conexión
$conn->close();
?>
