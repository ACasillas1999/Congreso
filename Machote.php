<?php
// Ruta de la imagen machote
$templatePath = '/home1/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/Gafetes_visitantes.jpg';

// Cargar la imagen
$image = imagecreatefromjpeg($templatePath);

// Definir el color del texto
$colorNegro = imagecolorallocate($image, 0, 0, 0);
$colorNaranja = imagecolorallocate($image, 255, 102, 0);
$colorBlanco = imagecolorallocate($image, 255, 255, 255); // Definir el color blanco

// Definir la ruta de la fuente (archivo TTF)
$fontPath = '/home1/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/Font/ArialNarrow7-9YJ9n.ttf'; // Asegúrate de que esta ruta es correcta

// Texto a escribir (ejemplo)
$nombre = 'Alex Casillas';

// Escribir el nombre
imagettftext($image, 150, 0, 220, 1540, $colorNegro, $fontPath, $nombre);

// Escribir el mes y año
$fecha = '';
imagettftext($image, 18, 0, 320, 750, $colorBlanco, $fontPath, $fecha);

// Cargar la imagen del código QR
$qrPath = '/home1/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/qr_image.png'; // Asegúrate de que esta ruta es correcta
$qrImage = imagecreatefrompng($qrPath);

// Obtener las dimensiones del QR y redimensionarlo si es necesario
list($qrWidth, $qrHeight) = getimagesize($qrPath);
$qrNewWidth =900; // ancho deseado
$qrNewHeight = 900; // alto deseado
$qrResized = imagecreatetruecolor($qrNewWidth, $qrNewHeight);
imagecopyresampled($qrResized, $qrImage, 0, 0, 0, 0, $qrNewWidth, $qrNewHeight, $qrWidth, $qrHeight);

// Superponer el QR en la imagen original
$qrX = 1755; // posición X donde se colocará el QR
$qrY = 280; // posición Y donde se colocará el QR
imagecopy($image, $qrResized, $qrX, $qrY, 0, 0, $qrNewWidth, $qrNewHeight);

// Guardar la nueva imagen
$outputPath = '/home1/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/Generados/Gafete_personalizado.jpg';
imagejpeg($image, $outputPath, 100);

// Limpiar memoria
imagedestroy($image);
imagedestroy($qrImage);
imagedestroy($qrResized);

echo "Gafete generado exitosamente en: " . $outputPath;
?>
