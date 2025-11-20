<?php
// Iniciar la sesión y verificar el inicio de sesión del usuario
/*
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}
*/

// Conectar a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID del participante desde la solicitud
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validar el ID
if ($id <= 0) {
    die("ID inválido.");
}

// Consultar la base de datos para obtener la ruta del gafete
$sql = "SELECT Ruta_Gafete FROM participante WHERE ID = $id";
$result = $conn->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conn->error);
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $filePath = $row["Ruta_Gafete"];

    // Verificar si el archivo existe
    if (file_exists($filePath)) {
        // Configurar encabezados para la descarga del archivo
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        // Leer el archivo y enviarlo al navegador
        readfile($filePath);
        exit;
    } else {
        die("El archivo no existe.");
    }
} else {
    die("No se encontró el participante.");
}

$conn->close();
?>
