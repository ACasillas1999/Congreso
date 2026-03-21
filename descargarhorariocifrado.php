<?php
require __DIR__ . "/config.php";

$token  = $_GET['token'] ?? '';
$format = (($_GET['format'] ?? 'png') === 'pdf') ? 'pdf' : 'png';

$id = openssl_decrypt(urldecode($token), METODO_CIFRADO, CLAVE_SECRETA, 0, VECTOR);
$id = (int)$id;
if ($id <= 0) {
  http_response_code(400);
  exit('Token invalido');
}

$url = buildAppUrl("DescargarHorario.php?id={$id}&format={$format}");

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 12,
]);
if (IS_LOCAL) {
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
}
$body = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http !== 200 || $body === false) {
  http_response_code(404);
  exit('No se pudo generar el horario');
}

if ($format === 'png') {
  header('Content-Type: image/png');
  header('Content-Disposition: attachment; filename="Horario_'.$id.'.png"');
} else {
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="Horario_'.$id.'.pdf"');
}
header('Cache-Control: no-store');
echo $body;
exit;
