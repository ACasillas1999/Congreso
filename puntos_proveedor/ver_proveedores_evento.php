<?php
include "../Conexiones/Conexion.php";

$evento = isset($_GET['evento']) ? (int)$_GET['evento'] : 1;

$sql = "SELECT * FROM proveedor_evento WHERE ID_Evento = $evento";
$result = $conn->query($sql);

echo "<h3>Proveedores del evento ID $evento</h3><ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li><strong>{$row['NombreProveedor']}</strong> - {$row['Puntos']} puntos</li>";
}
echo "</ul>";
?>
