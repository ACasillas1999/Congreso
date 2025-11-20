<?php
session_start();
require_once __DIR__ . "/../Conexiones/Conexion.php";

if (!isset($_SESSION['Rol']) || $_SESSION['Rol'] !== 'proveedor') {
    die("Acceso denegado");
}

$usuario = $_SESSION['username'];

$stmt = $conn->prepare("
    SELECT p.Nombre, p.Telefono, ev.name_evento, pp.puntos, pp.fecha 
    FROM puntos_proveedor pp
    INNER JOIN participante p ON p.ID = pp.id_participante
    INNER JOIN evento ev ON ev.ID = pp.id_evento
    WHERE pp.usuario = ?
    ORDER BY pp.fecha DESC
");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$res = $stmt->get_result();
?>

<h2>Historial de puntos asignados por <?= htmlspecialchars($usuario) ?></h2>
<table border="1" cellpadding="6">
    <tr>
        <th>Participante</th>
        <th>Teléfono</th>
        <th>Evento</th>
        <th>Puntos</th>
        <th>Fecha</th>
    </tr>
    <?php while ($row = $res->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['Nombre']) ?></td>
            <td><?= htmlspecialchars($row['Telefono']) ?></td>
            <td><?= htmlspecialchars($row['name_evento']) ?></td>
            <td><?= $row['puntos'] ?></td>
            <td><?= $row['fecha'] ?></td>
        </tr>
    <?php endwhile; ?>
</table>
