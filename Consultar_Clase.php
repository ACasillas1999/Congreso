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




// 1. Obtener información de la agenda (Actividad, etc.) y ID_Evento
$sqlAg = "SELECT ID_Evento FROM agenda WHERE ID = ?";
$stmtAg = $conn->prepare($sqlAg);
$stmtAg->bind_param("i", $idAgenda);
$stmtAg->execute();
$idEvento = $stmtAg->get_result()->fetch_assoc()['ID_Evento'] ?? 0;

// Consulta para obtener la información de la agenda (cabecera)
$sqlAgenda = "SELECT * FROM agenda ag JOIN actividades ac on ac.Actividad = ag.Actividad where ag.ID = ?";
$stmtAgenda = $conn->prepare($sqlAgenda);
$stmtAgenda->bind_param("i", $idAgenda);
$stmtAgenda->execute();
$resultAgenda = $stmtAgenda->get_result();

$sqlCapacidad = "SELECT A.capacidad, 
                        (SELECT COUNT(*) FROM clase C WHERE C.ID_Agenda = B.ID) as total_participantes 
                 FROM actividades A 
                 JOIN agenda B ON A.Actividad = B.Actividad 
                 WHERE B.ID = ? AND B.ID_Evento = A.ID_Evento";
                 
$stmtCapacidad = $conn->prepare($sqlCapacidad);
$stmtCapacidad->bind_param("i", $idAgenda);
$stmtCapacidad->execute();
$rowCapacidad = $stmtCapacidad->get_result()->fetch_assoc();

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
    
    if ($rowCapacidad['total_participantes'] < $rowCapacidad['capacidad']) {
        echo "<p>Hay cupos disponibles. <button onclick='abrirModalRegistro()' class='button-sec' style='padding:5px 10px; font-size:12px;'>+ Registro Rápido</button></p>";
    } else {
        echo "<p>No se pueden agregar más participantes. El evento ha alcanzado su capacidad máxima.</p>";
    }
}

// === BUSCADOR O LISTADO PREDETERMINADO ===
$busqueda = isset($_POST['busqueda']) ? trim($_POST['busqueda']) : '';

if ($busqueda !== '') {
    // Buscar en todos los participantes del evento
    $searchTerm = "%$busqueda%";
    $sqlSearch = "SELECT p.*, c.Asistio, c.Asistencia_Fecha, c.ID_Agenda as EnClase
                  FROM participante p
                  LEFT JOIN clase c ON p.ID = c.ID_Participante AND c.ID_Agenda = ?
                  WHERE p.ID_Evento = ? 
                  AND (p.Nombre LIKE ? OR p.Telefono LIKE ? OR p.Proveedor LIKE ? OR p.ID = ?)
                  LIMIT 20";
    
    $stmtSearch = $conn->prepare($sqlSearch);
    $stmtSearch->bind_param("iissss", $idAgenda, $idEvento, $searchTerm, $searchTerm, $searchTerm, $busqueda);
    $stmtSearch->execute();
    $result = $stmtSearch->get_result();

    if ($result->num_rows > 0) {
        echo "<h2>Resultados de Búsqueda</h2>";
        echo "<table class='mi-tabla' border='1'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Proveedor</th><th>Estado</th><th>Acción</th></tr>";
        while ($row = $result->fetch_assoc()) {
            $enClase = !is_null($row['EnClase']);
            $asistio = (int)($row['Asistio'] ?? 0) === 1;
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["ID"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Nombre"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Telefono"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Proveedor"]) . "</td>";
            
            if ($enClase) {
                echo "<td style='color:#4caf50'>" . ($asistio ? "Asistió" : "Inscrito") . "</td>";
                echo "<td>";
                if (!$asistio) {
                    echo "<button onclick='btnMarcarAsistencia(" . $row['ID'] . ")' class='button' style='padding:5px 10px; background:#4caf50'>Asistencia</button>";
                } else {
                    echo "<span style='font-size:12px; color:#aaa'>Registrada: " . $row['Asistencia_Fecha'] . "</span>";
                }
                echo "</td>";
            } else {
                echo "<td style='color:#ff9800'>No inscrito</td>";
                echo "<td><button onclick='btnInscribirYAsistir(" . $row['ID'] . ")' class='button' style='padding:5px 10px; background:#ff9800'>Inscribir y Asistir</button></td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='text-align:center; padding:20px;'>
                <p>No se encontró a nadie con \"$busqueda\".</p>
                <button onclick='abrirModalRegistro()' class='button'>Hacer Registro Rápido</button>
              </div>";
    }
} else {
    // Listado original de la clase
    $sqlParticipantes = "SELECT p.*, c.Asistio, c.Asistencia_Fecha, c.Tipo_Inscripcion
                         FROM clase c
                         JOIN participante p ON c.ID_Participante = p.ID
                         WHERE c.ID_Agenda = ?";
    $stmtParticipantes = $conn->prepare($sqlParticipantes);
    $stmtParticipantes->bind_param("i", $idAgenda);
    $stmtParticipantes->execute();
    $resultParticipantes = $stmtParticipantes->get_result();

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
            echo "<td>" . $tipo . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No se encontraron participantes para esta clase.</p>";
    }
}

$conn->close();
?>
