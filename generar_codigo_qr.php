<?php
// Incluir el autoload de Composer
require 'vendor/autoload.php'; 

// Importar las clases necesarias de Endroid QR Code
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Datos para el código QR
$datos = "https://www.ejemplo.com"; // Reemplaza esto con los datos que quieras codificar

// Crear el objeto QR Code
$qrCode = new QrCode($datos);

// Establecer el tamaño y el nivel de corrección (opcional)
$qrCode->setSize(300);
$qrCode->setErrorCorrectionLevel('high');

// Crear el archivo PNG
$writer = new PngWriter();
$path = 'codigo_qr.png'; // Ruta donde se guardará el archivo QR
$writer->writeFile($qrCode, $path); // Guarda el archivo en el servidor

// Mostrar el código QR en una imagen
header('Content-Type: image/png');
echo $writer->writeString($qrCode);
?>
