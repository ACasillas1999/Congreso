<?php
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fecha_actual = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Obtener agenda del día
$agenda_matriz = [];
$sql_agenda = "SELECT ID, Actividad, Horario, Salon FROM agenda WHERE ID_Evento = ? AND Fecha = ? ORDER BY Horario";
if ($stmt_agenda = $conn->prepare($sql_agenda)) {
    $stmt_agenda->bind_param("is", $id_evento, $fecha_actual);
    $stmt_agenda->execute();
    $result_agenda = $stmt_agenda->get_result();
    while ($row = $result_agenda->fetch_assoc()) {
        $agenda_matriz[$row['Horario']][$row['Salon']] = [
            'Actividad' => $row['Actividad'],
            'ID' => $row['ID']
        ];
    }
    $stmt_agenda->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario Día</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <?php include "header_css.php"; ?>
    <style>
        .agenda-timeline { margin-top: 20px; }
        .bloque-horario { margin-bottom: 24px; padding: 16px; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .hora-label { font-weight: bold; color: var(--theme-title); margin-bottom: 12px; }
        .actividades-linea { display: flex; flex-wrap: wrap; gap: 12px; }
        .card-actividad { flex: 1; min-width: 200px; padding: 12px; background: rgba(56,217,255,0.05); border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); }
        .salon-label { font-size: 12px; color: var(--theme-text-soft); }
        .btn-ver { margin-top: 8px; display: inline-block; color: var(--theme-title); text-decoration: none; font-size: 13px; }
    </style>
</head>
<body class="fade-in">

    <?php include "sidebar.php"; ?>

    <div class="container">
        <h2 class="titulo">Agenda del Día</h2>
        <h3 class="titulo2"><?php echo htmlspecialchars($fecha_actual); ?></h3>

        <div class="agenda-timeline">
            <?php if (empty($agenda_matriz)): ?>
                <p class="note">No hay actividades programadas para este día.</p>
            <?php else: ?>
                <?php foreach ($agenda_matriz as $Horario => $actividades): ?>
                    <div class="bloque-horario">
                        <div class="hora-label">🕒 <?= htmlspecialchars($Horario) ?></div>
                        <div class="actividades-linea">
                            <?php foreach ($actividades as $salon => $info): ?>
                                <div class="card-actividad">
                                    <div class="salon-label">📍 <?= htmlspecialchars($salon) ?></div>
                                    <div class="contenido-actividad"><?= htmlspecialchars($info['Actividad']) ?></div>
                                    <a class="btn-ver" href="Clase.php?id=<?= $info['ID'] ?>">Ver clase</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
