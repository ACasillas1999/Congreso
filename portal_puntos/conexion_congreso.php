<?php
$dbServer = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'gpoascen_congresos';

$conn = new mysqli($dbServer, $dbUsername, $dbPassword, $dbName);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}
?>
