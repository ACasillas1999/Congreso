<?php
include "../Conexiones/Conexion.php";
$id = $_GET['id'];
$evento = $_GET['evento'];

$sql = "UPDATE proveedor_evento SET Activo = 0 WHERE ID = $id";
if ($conn->query($sql) === TRUE) {
header("Location: ../Evento_inicio.php?id=$evento");
} else {
    echo "Error al marcar como eliminado: " . $conn->error;
}
