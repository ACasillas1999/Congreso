<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}

// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID del participante desde la URL
$id_participante = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_participante > 0) {
    // Consulta a la base de datos para obtener los datos del participante
    $sql = "SELECT Nombre, QR_Code FROM participante WHERE ID = $id_participante";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nombre = $row['Nombre'];
        $qrImagePath = $row['QR_Code'];

        // Verificar que la ruta al archivo QR sea correcta
        if (file_exists($qrImagePath)) {
            // Iniciar el proceso de generación del PDF
            require('fpdf/fpdf.php');
            $pdf = new FPDF();
            $pdf->AddPage();

            // Configuración del título del gafete
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, "Gafete del Participante", 0, 1, 'C');
            $pdf->Ln(10); // Espacio en blanco

            // Agregar el nombre del participante
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, "Nombre: $nombre", 0, 1, 'C');
            $pdf->Ln(20); // Espacio en blanco

            // Agregar el QR Code en el centro del PDF
            $pdf->Image($qrImagePath, 80, 80, 50, 50); // Centrar en la página
            $pdf->Ln(50); // Espacio en blanco

            // Salida del PDF al navegador
            $pdf->Output('I', 'Gafete_' . $nombre . '.pdf');
        } else {
            echo "La imagen del código QR no se encuentra en la ruta especificada.";
        }
    } else {
        echo "No se encontraron datos para el participante con ID: $id_participante";
    }
} else {
    echo "ID de participante inválido.";
}

$conn->close();
?>
