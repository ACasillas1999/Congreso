<?php
// Base de datos externa donde están las facturas
$host_f = '187.188.43.3';
$port_f = '3307';
$dbname_f = 'cedis';
$user_f = 'gpoascen_conexion';
$pass_f = 'A1V.Rvqp.9(9';

$conn_facturas = new mysqli($host_f, $user_f, $pass_f, $dbname_f, $port_f);
if ($conn_facturas->connect_error) {
    die("Error al conectar a la base de facturas: " . $conn_facturas->connect_error);
}
?>
