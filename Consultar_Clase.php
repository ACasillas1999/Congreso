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
        // Vista para Escritorio (Tabla estándar)
        echo "<table class='mi-tabla'><tr><th>ID</th><th>Nombre</th><th>Proveedor</th><th>Estado</th><th>Acción</th></tr>";
        
        // Vista para Móvil (Oculta por defecto con estilo inline y CSS)
        echo "<div class='mobile-card-container' style='display:none;'>";
        
        while ($r = $res->fetch_assoc()) {
            $en = !is_null($r['EnClase']); 
            $as = (int)($r['Asistio'] ?? 0) === 1;
            $nombre = htmlspecialchars($r['Nombre']);
            $proveedor = htmlspecialchars($r['Proveedor']);
            $idPart = $r['ID'];
            
            $statusClass = $as ? 'badge-encurso' : ($en ? 'badge-finalizado' : 'badge-cancelado');
            $statusText = $as ? 'Asistió' : ($en ? 'Inscrito' : 'No inscrito');

            // Fila de Tabla (PC)
            echo "<tr>
                    <td>{$idPart}</td>
                    <td><b>{$nombre}</b></td>
                    <td>{$proveedor}</td>
                    <td><span class='badge {$statusClass}'>{$statusText}</span></td>
                    <td>";
            if ($en) {
                if (!$as) echo "<button onclick='btnMarcarAsistencia({$idPart})' class='button' style='padding:5px 10px;'>Asistencia</button>";
                else echo "<small style='color:var(--txt-dim)'>{$r['Asistencia_Fecha']}</small>";
            } else {
                echo "<button onclick='btnInscribirYAsistir({$idPart})' class='button' style='padding:5px 10px;'>Inscribir</button>";
            }
            echo "</td></tr>";

            // Tarjeta (Móvil)
            echo "<div class='participant-card'>
                    <div class='participant-header'>
                        <div>
                            <span class='participant-name'>{$nombre}</span>
                            <span class='participant-provider'>{$proveedor}</span>
                        </div>
                        <span class='participant-id'>ID: {$idPart}</span>
                    </div>
                    <div class='participant-footer'>
                        <span class='badge " . ($as ? 'badge-encurso' : ($en ? 'badge-finalizado' : 'badge-cancelado')) . "'>{$statusText}</span>
                        <div style='flex:1; text-align:right;'>";
            if ($en) {
                if (!$as) echo "<button onclick='btnMarcarAsistencia({$idPart})' class='btn-mobile-action'>Asistencia</button>";
                else echo "<span style='color:var(--ok); font-weight:600;'>✔ {$r['Asistencia_Fecha']}</span>";
            } else {
                echo "<button onclick='btnInscribirYAsistir({$idPart})' class='btn-mobile-action'>Inscribir</button>";
            }
            echo "      </div>
                    </div>
                  </div>";
        }
        echo "</div>"; // Cierre mobile-card-container
        echo "</table>"; // Cierre mi-tabla PC
    } else {
        echo "<div style='text-align:center; padding:30px; background:rgba(255,255,255,0.02); border-radius:12px; border:1px solid rgba(255,255,255,0.1);'>
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
        echo "<div class='mobile-card-container' style='display:none;'>";
        
        while ($rp = $resP->fetch_assoc()) {
            $as = ((int)$rp["Asistio"] === 1);
            $tipo = ((int)$rp["Tipo_Inscripcion"] === 1) ? "Manual" : "Registro";
            $nombre = htmlspecialchars($rp['Nombre']);
            $proveedor = htmlspecialchars($rp['Proveedor']);
            $idPart = $rp['ID'];

            // Fila Desktop
            echo "<tr>
                    <td>{$idPart}</td>
                    <td><b>{$nombre}</b></td>
                    <td>{$proveedor}</td>
                    <td>".($as ? "✔ <small>{$rp['Asistencia_Fecha']}</small>" : "—")."</td>
                    <td>$tipo</td>
                  </tr>";

            // Tarjeta Móvil
            echo "<div class='participant-card'>
                    <div class='participant-header'>
                        <div>
                            <span class='participant-name'>{$nombre}</span>
                            <span class='participant-provider'>{$proveedor}</span>
                        </div>
                        <span class='participant-id'>ID: {$idPart}</span>
                    </div>
                    <div class='participant-footer'>
                        <span class='badge ".($as?'badge-encurso':'badge-cancelado')."'>".($as?'Asistió':'No asistió')."</span>
                        <span style='color:var(--txt-dim); font-size:12px;'>".($as ? $rp['Asistencia_Fecha'] : $tipo)."</span>
                    </div>
                  </div>";
        }
        echo "</div>";
        echo "</table>";
    } else {
        echo "<p style='text-align:center; padding:20px; color:var(--txt-dim)'>No hay inscritos aún.</p>";
    }
}
$conn->close();
?>
