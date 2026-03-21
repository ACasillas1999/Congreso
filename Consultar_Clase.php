<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

$idAgenda = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($idAgenda <= 0) { echo "ID inválido."; exit; }

// Conteos
$sqlAsis = "SELECT COUNT(*) AS total, SUM(Asistio=1) AS asistieron FROM clase WHERE ID_Agenda = ?";
$stmtAsis = $conn->prepare($sqlAsis);
$stmtAsis->bind_param("i", $idAgenda);
$stmtAsis->execute();
$rowAsis = $stmtAsis->get_result()->fetch_assoc();
$totalInscritos = (int)($rowAsis['total'] ?? 0);
$totalAsistieron = (int)($rowAsis['asistieron'] ?? 0);

// Info Agenda
$sqlAg = "SELECT ID_Evento FROM agenda WHERE ID = ?";
$stmtAg = $conn->prepare($sqlAg);
$stmtAg->bind_param("i", $idAgenda);
$stmtAg->execute();
$idEvento = $stmtAg->get_result()->fetch_assoc()['ID_Evento'] ?? 0;

$sqlAgenda = "SELECT ag.*, ac.Puntos_Default, ac.capacidad FROM agenda ag JOIN actividades ac on ac.Actividad = ag.Actividad WHERE ag.ID = ? AND ac.ID_Evento = ag.ID_Evento";
$stmtAgenda = $conn->prepare($sqlAgenda);
$stmtAgenda->bind_param("i", $idAgenda);
$stmtAgenda->execute();
$resultAgenda = $stmtAgenda->get_result();
$rowAgenda = $resultAgenda->fetch_assoc();

if ($rowAgenda) {
    echo "<h2 class='titulo'>Información de la Agenda</h2>";
    echo "<table class='mi-tabla'>";
    echo "<tr><th>Salón</th><th>Actividad</th><th>Fecha</th><th>Horario</th><th>Puntos</th><th>Capacidad</th><th>Inscritos</th><th>Asistieron</th></tr>";
    echo "<tr>
            <td>" . htmlspecialchars($rowAgenda["Salon"]) . "</td>
            <td>" . htmlspecialchars($rowAgenda["Actividad"]) . "</td>
            <td>" . htmlspecialchars($rowAgenda["Fecha"]) . "</td>
            <td>" . htmlspecialchars($rowAgenda["Horario"]) . "</td>
            <td>" . htmlspecialchars($rowAgenda["Puntos_Default"]) . "</td>
            <td>" . htmlspecialchars($rowAgenda["capacidad"]) . "</td>
            <td>$totalInscritos</td>
            <td>$totalAsistieron</td>
          </tr>";
    echo "</table>";
    
    if ($totalInscritos < $rowAgenda['capacidad']) {
        echo "<p style='margin-top:10px; color:var(--theme-text-soft)'>Hay cupos disponibles. <button onclick='abrirModalRegistro()' class='button' style='padding:5px 15px; font-size:12px; margin-left:10px;'>+ Registro Rápido</button></p>";
    } else {
        echo "<p style='margin-top:10px; color:#ff4444'>Capacidad máxima alcanzada.</p>";
    }
}

// Listado / Búsqueda
$busqueda = isset($_POST['busqueda']) ? trim($_POST['busqueda']) : '';
if ($busqueda !== '') {
    $searchTerm = "%$busqueda%";
    $sqlSearch = "SELECT p.*, c.Asistio, c.Asistencia_Fecha, c.ID_Agenda as EnClase
                  FROM participante p
                  LEFT JOIN clase c ON p.ID = c.ID_Participante AND c.ID_Agenda = ?
                  WHERE p.ID_Evento = ? 
                  AND (p.Nombre LIKE ? OR p.Telefono LIKE ? OR p.Proveedor LIKE ? OR p.ID = ?)
                  LIMIT 30";
    $stmtS = $conn->prepare($sqlSearch);
    $stmtS->bind_param("iissss", $idAgenda, $idEvento, $searchTerm, $searchTerm, $searchTerm, $busqueda);
    $stmtS->execute();
    $res = $stmtS->get_result();

    echo "<h2 class='titulo' style='margin-top:30px;'>Resultados de Búsqueda</h2>";
    if ($res->num_rows > 0) {
        echo "<table class='mi-tabla'><tr><th>ID</th><th>Nombre</th><th>Proveedor</th><th>Estado</th><th>Acción</th></tr>";
        while ($r = $res->fetch_assoc()) {
            $en = !is_null($r['EnClase']); $as = (int)($r['Asistio'] ?? 0) === 1;
            echo "<tr>
                    <td>{$r['ID']}</td>
                    <td><b>".htmlspecialchars($r['Nombre'])."</b></td>
                    <td>".htmlspecialchars($r['Proveedor'])."</td>
                    <td><span class='badge ".($as?'badge-encurso':($en?'badge-finalizado':'badge-cancelado'))."'>".($as?'Asistió':($en?'Inscrito':'No inscrito'))."</span></td>
                    <td>";
            if ($en) {
                if (!$as) echo "<button onclick='btnMarcarAsistencia({$r['ID']})' class='button' style='padding:5px 10px;'>Asistencia</button>";
                else echo "<small style='color:var(--theme-text-soft)'>{$r['Asistencia_Fecha']}</small>";
            } else {
                echo "<button onclick='btnInscribirYAsistir({$r['ID']})' class='button' style='padding:5px 10px; background:var(--theme-primary-dark)'>Inscribir</button>";
            }
            echo "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='text-align:center; padding:30px; background:rgba(255,255,255,0.02); border-radius:var(--theme-radius); border:1px solid var(--theme-border);'>
                <p>No se encontró a nadie. ¿Deseas registrarlo?</p>
                <button onclick='abrirModalRegistro()' class='button'>Hacer Registro Rápido</button>
              </div>";
    }
} else {
    $sqlP = "SELECT p.*, c.Asistio, c.Asistencia_Fecha, c.Tipo_Inscripcion FROM clase c JOIN participante p ON c.ID_Participante = p.ID WHERE c.ID_Agenda = ? ORDER BY c.Asistencia_Fecha DESC, p.Nombre ASC";
    $stmtP = $conn->prepare($sqlP);
    $stmtP->bind_param("i", $idAgenda);
    $stmtP->execute();
    $resP = $stmtP->get_result();

    echo "<h2 class='titulo' style='margin-top:30px;'>Participantes de la Clase</h2>";
    if ($resP->num_rows > 0) {
        echo "<table class='mi-tabla'><tr><th>ID</th><th>Nombre</th><th>Proveedor</th><th>Asistencia</th><th>Tipo</th></tr>";
        while ($rp = $resP->fetch_assoc()) {
            $as = ((int)$rp["Asistio"] === 1);
            $tipo = ((int)$rp["Tipo_Inscripcion"] === 1) ? "Manual" : "Registro";
            echo "<tr>
                    <td>{$rp['ID']}</td>
                    <td><b>".htmlspecialchars($rp['Nombre'])."</b></td>
                    <td>".htmlspecialchars($rp['Proveedor'])."</td>
                    <td>".($as ? "✔ <small>{$rp['Asistencia_Fecha']}</small>" : "—")."</td>
                    <td>$tipo</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='text-align:center; padding:20px; color:var(--theme-text-soft)'>No hay inscritos aún.</p>";
    }
}
$conn->close();
?>
