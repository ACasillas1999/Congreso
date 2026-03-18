<?php
require_once "Conexiones/Conexion.php";
$tables = ['participante', 'clase', 'agenda'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
}
