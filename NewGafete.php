<?php
// /Congreso/regenerar_gafete.php (SIN WhatsApp)
declare(strict_types=1);
date_default_timezone_set('America/Mexico_City');

// ===== Sesión / Gate =====
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
session_name('CON'); // usa el mismo nombre que en tu módulo del Congreso
session_start();

$logged = isset($_SESSION['loggedin']) ? ($_SESSION['loggedin'] === true) : false;
$rol    = $_SESSION['Rol'] ?? '';
if (!$logged) { http_response_code(401); exit('No autorizado'); }
if (strtoupper($rol) === 'VENDEDOR') { http_response_code(403); exit('Acceso denegado'); }

// ===== Dependencias =====
require_once __DIR__ . '/phpqrcode/qrlib.php';
require_once __DIR__ . '/Conexiones/Conexion.php';   // Debe exponer $conn (mysqli)
require_once __DIR__ . '/config.php';                // METODO_CIFRADO, CLAVE_SECRETA, VECTOR

// ===== Parámetros =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('Falta parámetro id'); }

$regenHorario = isset($_GET['regen_horario']) ? (int)$_GET['regen_horario'] : 1; // 1=Sí, 0=No

// ===== Participante =====
$sql = "SELECT p.ID, p.ID_Evento AS evento, p.Sucursal, p.Nombre, p.Telefono
        FROM participante p
        WHERE p.ID = ?";
$st = $conn->prepare($sql);
$st->bind_param('i', $id);
$st->execute();
$part = $st->get_result()->fetch_assoc();
if (!$part) { http_response_code(404); exit('Participante no encontrado'); }

$last_id  = (int)$part['ID'];
$evento   = (int)$part['evento'];
$sucursal = (string)$part['Sucursal'];
$Nombre   = (string)$part['Nombre'];

// ===== (Opcional) Regenerar Horario si tiene actividades =====
if ($regenHorario) {
  $chkClase = $conn->prepare("SELECT 1 FROM clase WHERE ID_Participante = ? LIMIT 1");
  $chkClase->bind_param('i', $last_id);
  $chkClase->execute();
  $tieneClase = (bool)$chkClase->get_result()->fetch_column();

  if ($tieneClase) {
    $host   = $_SERVER['HTTP_HOST'] ?? 'congresos.grupoascencio.com.mx';
    $genUrl = "https://{$host}/Congreso/Generar_Horario.php?id={$last_id}&format=png&silent=1";
    if (function_exists('curl_init')) {
      $ch = curl_init($genUrl);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 8);
      curl_exec($ch);
      curl_close($ch);
    } else {
      @file_get_contents($genUrl);
    }
  }
}

// ===== Token cifrado para ligas públicas =====
$token = openssl_encrypt((string)$last_id, METODO_CIFRADO, CLAVE_SECRETA, 0, VECTOR);
$token = urlencode($token);

$url_gafete_publico = "https://congresos.grupoascencio.com.mx/congreso/DescargarGafeteCifrado.php?token={$token}";
$url_gafete_directo = "https://congresos.grupoascencio.com.mx/congreso/DescargarGafete.php?id={$last_id}";

// ===== Generar QR =====
$qrData = "ID: {$last_id}\nEvento: {$evento}\nNombre: {$Nombre}\nSucursal: {$sucursal}";
$qrDir  = __DIR__ . '/qrcodes/';
if (!is_dir($qrDir)) { @mkdir($qrDir, 0775, true); }
$qrFilenameRel = 'qrcodes/participante_' . $last_id . '.png'; // para guardar en DB (relativo)
$qrFilenameAbs = __DIR__ . '/' . $qrFilenameRel;

QRcode::png($qrData, $qrFilenameAbs, QR_ECLEVEL_L, 4);

// ===== Crear Gafete (JPG) =====
$templateFs  = '/home/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/Gafetes_visitantes.jpg';
$templateUrl = 'https://congresos.grupoascencio.com.mx/Congreso/Machote/Gafetes_visitantes.jpg';
$templatePath = is_file($templateFs) ? $templateFs : $templateUrl;

$fontPath = __DIR__ . '/Machote/Font/nexa-book.ttf';

$outputDir  = '/home/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/Generados/';
if (!is_dir($outputDir)) { @mkdir($outputDir, 0775, true); }
$outputPath = $outputDir . 'Gafete_personalizado_' . $last_id . '.jpg';

$image = @imagecreatefromjpeg($templatePath);
if (!$image) { http_response_code(500); exit('No se pudo abrir el machote de gafete'); }

if (!is_file($fontPath)) { imagedestroy($image); http_response_code(500); exit('No se encontró la fuente nexa-book.ttf'); }

$colorTexto = imagecolorallocate($image, 255, 255, 255);

// Centrado del nombre en el área izquierda
$areaWidth = 1000;
$areaX     = 202;
$fontSize  = 60;

$textBox   = imagettfbbox($fontSize, 0, $fontPath, $Nombre);
$textWidth = abs($textBox[4] - $textBox[0]);
if ($textWidth > $areaWidth && $textWidth > 0) {
  $fontSize  = max(12, (int) floor(($areaWidth / $textWidth) * $fontSize));
  $textBox   = imagettfbbox($fontSize, 0, $fontPath, $Nombre);
  $textWidth = abs($textBox[4] - $textBox[0]);
}
$x = (int) round($areaX + ($areaWidth - $textWidth) / 2);
$y = 1050;

imagettftext($image, $fontSize, 0, $x, $y, $colorTexto, $fontPath, $Nombre);

// Insertar QR
$qrImage = imagecreatefrompng($qrFilenameAbs);
list($qrW, $qrH) = getimagesize($qrFilenameAbs);
$qrNewW = 900; $qrNewH = 900;
$qrRes  = imagecreatetruecolor($qrNewW, $qrNewH);
imagealphablending($qrRes, false);
imagesavealpha($qrRes, true);
imagecopyresampled($qrRes, $qrImage, 0, 0, 0, 0, $qrNewW, $qrNewH, $qrW, $qrH);

// Posición del QR según tu diseño
$qrX = 1755; $qrY = 280;
imagecopy($image, $qrRes, $qrX, $qrY, 0, 0, $qrNewW, $qrNewH);

imagejpeg($image, $outputPath, 100);
imagedestroy($image);
imagedestroy($qrImage);
imagedestroy($qrRes);

// ===== Actualiza DB =====
$upd = $conn->prepare("UPDATE participante SET QR_Code=?, Ruta_Gafete=? WHERE ID=?");
$qrDB   = 'qrcodes/participante_' . $last_id . '.png';
$rutaDB = $outputPath;
$upd->bind_param('ssi', $qrDB, $rutaDB, $last_id);
$okUpd = $upd->execute();
if (!$okUpd) {
  http_response_code(500);
  exit('Error al actualizar rutas en participante: ' . $conn->error);
}

// ===== Salida =====
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'ok'             => true,
  'msg'            => 'Gafete y QR regenerados correctamente',
  'id'             => $last_id,
  'qr_rel'         => $qrDB,
  'gafete_path'    => $rutaDB,
  'gafete_publico' => $url_gafete_publico,
  'gafete_directo' => $url_gafete_directo,
  'regen_horario'  => (bool)$regenHorario
]);
