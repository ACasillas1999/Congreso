<?php
include "../Conexiones/Conexion.php";
$id = $_POST['id'];
$evento = $_POST['evento'];
$nombre = $conn->real_escape_string($_POST['NombreProveedor']);
$puntos = (int)$_POST['Puntos'];

$sql = "UPDATE proveedor_evento SET NombreProveedor = '$nombre', Puntos = $puntos WHERE ID = $id";
if ($conn->query($sql) === TRUE) {
header("Location: ../Evento_inicio.php?id=$evento");
} else {
    echo "Error al actualizar: " . $conn->error;
}
