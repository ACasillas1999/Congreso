<?php
// Conectar a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";



// Obtener el ID del registro de la agenda desde la URL
$id_agenda = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Recuperar eventos en curso
$sql = "SELECT DISTINCT A.Actividad
FROM actividades A
JOIN Agenda E 
ON E.ID_Evento = A.ID_Evento
;" ;
$result = $conn->query($sql);

// Inicializar variables para los campos de la agenda
$salon = $actividad = $fecha = $horario = '';

// Si se recibió un ID válido, recuperar la información de la agenda
if ($id_agenda > 0) {
    $sql_agenda = "SELECT * FROM agenda WHERE id = $id_agenda";
    $result_agenda = $conn->query($sql_agenda);

    if ($result_agenda->num_rows > 0) {
        $row_agenda = $result_agenda->fetch_assoc();
        $salon = $row_agenda['Salon'];
        $actividad = $row_agenda['Actividad'];
        $fecha = $row_agenda['Fecha'];
        $horario = $row_agenda['Horario'];
    }
}

// Cerrar conexión
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles3.css">
    <title>Actualizar Agenda</title>
</head>
<body>
    
<header class="header">
    <div class="logo">Actualizar Agenda</div>
    <nav class="navbar">
        <ul>
        <li class="nav-item"><a href='Clase.php?id=<?php echo $id_agenda; ?>' class="nav-link">Volver</a></li>

        </ul>
    </nav>
</header>

<form action="Funcion_Actualizar_Agenda.php" method="POST">
    <input type="hidden" name="id_agenda" value="<?php echo $id_agenda; ?>">

    <label for="salon">Salón:</label>
    <input type="text" id="salon" name="salon" value="<?php echo htmlspecialchars($salon); ?>" readonly><br><br>

    <label for="actividad">Actividad:</label>
   <!-- <input type="text" id="actividad" name="actividad" value="<?php echo htmlspecialchars($actividad); ?>" required><br><br>-->
   <select id="actividad" name="actividad" required>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<option value='" . $row["Actividad"] . "'>" . $row["Actividad"] . "</option>";
            }
        } else {
            echo "<option value=''>No hay eventos disponibles</option>";
        }
        ?>
    </select><br><br>

    <label for="fecha">Fecha:</label>
    <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>" readonly><br><br>

    <label for="horario">Horario:</label>
    <input type="text" id="horario" name="horario" value="<?php echo htmlspecialchars($horario); ?>" readonly><br><br>

    <input type="submit" value="Actualizar Agenda">
</form>

</body>
</html>
