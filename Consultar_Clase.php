<?php
// Iniciar la sesión de forma segura
// ini_set('session.cookie_httponly', true);
// ini_set('session.cookie_secure', true);
// session_start();

// Verificar si el usuario no está logeado
// if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
//     header("location: /Congreso/Sesion/login.html");
//     exit;
// }

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID de la agenda desde la solicitud POST
$idAgenda = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($idAgenda <= 0) {
    echo "ID inválido o no proporcionado.";
    exit;
}
// Conteo de inscritos y asistieron (para mostrar en cabecera)
$sqlAsis = "SELECT COUNT(*) AS total, SUM(Asistio=1) AS asistieron
            FROM clase
            WHERE ID_Agenda = ?";
$stmtAsis = $conn->prepare($sqlAsis);
$stmtAsis->bind_param("i", $idAgenda);
$stmtAsis->execute();
$rowAsis = $stmtAsis->get_result()->fetch_assoc();
$totalInscritos  = (int)($rowAsis['total'] ?? 0);
$totalAsistieron = (int)($rowAsis['asistieron'] ?? 0);




// Consulta para obtener la información de la agenda
$sqlAgenda = "SELECT * FROM agenda ag JOIN  actividades ac on ac.Actividad = ag.Actividad where ag.ID = ?";
$stmtAgenda = $conn->prepare($sqlAgenda);
$stmtAgenda->bind_param("i", $idAgenda);
$stmtAgenda->execute();
$resultAgenda = $stmtAgenda->get_result();

// Consulta para obtener la capacidad y el número total de participantes
$sqlCapacidad = "SELECT A.capacidad, 
                        (SELECT COUNT(*) 
                         FROM clase C 
                         WHERE C.ID_Agenda = B.ID) as total_participantes 
                 FROM actividades A 
                 JOIN agenda B ON A.Actividad = B.Actividad 
                 WHERE B.ID = ? AND B.ID_Evento = A.ID_Evento";
                 
$stmtCapacidad = $conn->prepare($sqlCapacidad);
$stmtCapacidad->bind_param("i", $idAgenda);
$stmtCapacidad->execute();
$resultCapacidad = $stmtCapacidad->get_result();

$rowCapacidad = $resultCapacidad->fetch_assoc();

if ($resultAgenda->num_rows > 0 && $rowCapacidad) {
    echo "<h2>Información de la Agenda</h2>";
    echo "<table class='mi-tabla' border='1'>";
echo "<tr><th>Salón</th><th>Actividad</th><th>Fecha</th><th>Horario</th><th>Puntos</th><th>Capacidad</th><th>Inscritos</th><th>Asistieron</th></tr>";
    
    while ($rowAgenda = $resultAgenda->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($rowAgenda["Salon"]) . "</td>";
        echo "<td>" . htmlspecialchars($rowAgenda["Actividad"]) . "</td>";
        echo "<td>" . htmlspecialchars($rowAgenda["Fecha"]) . "</td>";
        echo "<td>" . htmlspecialchars($rowAgenda["Horario"]) . "</td>";
        echo "<td>" . htmlspecialchars($rowAgenda["Puntos_Default"]) . "</td>";
       echo "<td>" . htmlspecialchars($rowCapacidad["capacidad"]) . "</td>";
echo "<td>" . $totalInscritos . "</td>";
echo "<td>" . $totalAsistieron . "</td>";

        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si hay cupos disponibles
    if ($rowCapacidad['total_participantes'] < $rowCapacidad['capacidad']) {
        echo "<p>Hay cupos disponibles. Puedes agregar un nuevo participante.</p>";
    } else {
        echo "<p>No se pueden agregar más participantes. El evento ha alcanzado su capacidad máxima.</p>";
    }
} else {
    echo "No se encontró información de la agenda o no se pudo obtener la capacidad y los participantes.";
}

// Consulta para obtener los participantes de la clase
$sqlParticipantes = "SELECT
    participante.ID,
    participante.ID_Evento,
    participante.Sucursal,
    participante.Vendedor,
    participante.Nombre,
    participante.Proveedor,
    participante.QR_Code,
    participante.Telefono,
    clase.Asistio,
    clase.Asistencia_Fecha,
    clase.Tipo_Inscripcion
FROM
    clase
JOIN
    participante ON clase.ID_Participante = participante.ID
WHERE
    clase.ID_Agenda = ?";


    
$stmtParticipantes = $conn->prepare($sqlParticipantes);
$stmtParticipantes->bind_param("i", $idAgenda);
$stmtParticipantes->execute();
$resultParticipantes = $stmtParticipantes->get_result();

// Mostrar los participantes
if ($resultParticipantes->num_rows > 0) {
    echo "<h2>Participantes de la Clase</h2>";
    echo "<table class='mi-tabla' border='1'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Telefono</th><th>Proveedor</th><th>Asistencia</th><th>Tipo Inscripción</th></tr>";
  while ($rowParticipante = $resultParticipantes->fetch_assoc()) {
    $badge = ((int)$rowParticipante["Asistio"] === 1)
             ? "✔ " . htmlspecialchars($rowParticipante["Asistencia_Fecha"])
             : "—";
    $tipo = ((int)$rowParticipante["Tipo_Inscripcion"] === 1) ? "Inscripción manual" : "Desde registro";

    echo "<tr>";
    echo "<td>" . htmlspecialchars($rowParticipante["ID"]) . "</td>";
    echo "<td>" . htmlspecialchars($rowParticipante["Nombre"]) . "</td>";
    echo "<td>" . htmlspecialchars($rowParticipante["Telefono"]) . "</td>";
    echo "<td>" . htmlspecialchars($rowParticipante["Proveedor"]) . "</td>";
    echo "<td>" . $badge . "</td>";
    echo "<td>" . $tipo . "</td>";  // ← FALTABA ESTA LÍNEA
    echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No se encontraron participantes para esta clase.</p>";
}

// Cerrar conexiones y declaraciones
$stmtAgenda->close();
$stmtCapacidad->close();
$stmtParticipantes->close();
$stmtAsis->close();

$conn->close();
?>
