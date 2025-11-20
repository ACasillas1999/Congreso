<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

if (isset($_POST['evento_id'])) {
    $eventoID = $conn->real_escape_string($_POST['evento_id']);

    $sql = "SELECT COUNT(*) as total FROM participante WHERE ID_Evento = '$eventoID'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo $row['total'];
    } else {
        echo '0';
    }
}

$conn->close();
?>
