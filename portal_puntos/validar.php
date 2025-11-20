<?php
require_once __DIR__ . "/conexion_congreso.php";
require_once __DIR__ . "/conexion_facturas.php";
require_once __DIR__ . "/config_turnstile.php";

function mostrarError($mensaje) {
    echo '
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Error</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <style>
        body {
          margin: 0;
          padding: 20px;
          font-family: "Segoe UI", sans-serif;
          background: radial-gradient(circle at center, #202040, #121212);
          color: #fff;
          display: flex;
          justify-content: center;
          align-items: center;
          min-height: 100vh;
          text-align: center;
        }
        .card {
          background: #2a1b1b;
          border: 2px solid #ff2e2e;
          box-shadow: 0 0 25px rgba(255, 46, 46, 0.5);
          padding: 30px 20px;
          border-radius: 15px;
          max-width: 460px;
          width: 100%;
          animation: fadeIn 0.6s ease-out;
        }
        h2 {
          color: #ff2e2e;
          margin-bottom: 20px;
          font-size: 24px;
          text-shadow: 0 0 10px #ff2e2e;
        }
        p {
          font-size: 16px;
        }
        a {
          display: inline-block;
          margin-top: 25px;
          padding: 12px 20px;
          background: linear-gradient(90deg, #ff7b00, #ffaa00);
          color: #000;
          font-weight: bold;
          text-decoration: none;
          border-radius: 8px;
          box-shadow: 0 0 15px #ff990088;
          transition: background 0.3s ease, transform 0.2s;
        }
        a:hover {
          background: linear-gradient(90deg, #ffaa00, #ffbb33);
          transform: scale(1.03);
        }
        @keyframes fadeIn {
          from { opacity: 0; transform: scale(0.95); }
          to { opacity: 1; transform: scale(1); }
        }
      </style>
    </head>
    <body>
      <div class="card">
        <h2>❌ Error</h2>
        <p>' . htmlspecialchars($mensaje) . '</p>
        <a href="index.php">← Volver</a>
      </div>
    </body>
    </html>';
    exit;
}

// 🔒 Honeypot: si el bot llenó el campo oculto, cortamos.
if (!empty($_POST['website_url'] ?? '')) {
    mostrarError("❌ Verificación anti-bots fallida.");
}

// 🔒 Verificar token de Cloudflare Turnstile
function verificar_turnstile(): bool {
    if (empty($_POST['cf-turnstile-response'])) return false;

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret'   => TURNSTILE_SECRET_KEY,
            'response' => $_POST['cf-turnstile-response'],
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null, // opcional
        ]),
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    if (!$resp) return false;
    $json = json_decode($resp, true);
    return !empty($json['success']);
}

// Si no pasa el captcha, no seguimos con la lógica de canje
if (!verificar_turnstile()) {
    mostrarError("❌ Verificación anti-bots fallida. Intenta de nuevo.");
}



$telefono = $_POST['telefono'] ?? '';
$tienda = $_POST['tienda'] ?? '';
$nofactura = $_POST['nofactura'] ?? '';
$evento_id = $_POST['evento'] ?? 0; // Asegúrate de enviarlo si aplica

if (!$telefono || !$tienda || !$nofactura) {
    mostrarError("Faltan datos del formulario.");
}

// 1. Validar teléfono y obtener RFC
$sql_tel = "SELECT RFC FROM participante WHERE Telefono = ?";
$stmt1 = $conn->prepare($sql_tel);
$stmt1->bind_param("s", $telefono);
$stmt1->execute();
$res1 = $stmt1->get_result();
if ($res1->num_rows === 0) {
    mostrarError("Tu número no está registrado.");
}
$rfc = $res1->fetch_assoc()['RFC'];

// 2. Validar factura
$sql_factura = "SELECT * FROM facturascnx WHERE Tienda = ? AND NoFactura = ? AND (EstatusCanjeada = 0 OR EstatusCanjeada IS NULL) LIMIT 1";
$stmt2 = $conn_facturas->prepare($sql_factura);
$stmt2->bind_param("ss", $tienda, $nofactura);
$stmt2->execute();
$res2 = $stmt2->get_result();
$factura = $res2->fetch_assoc();

if (!$factura) {
    // Verificamos si existe pero ya fue canjeada
    $sql_check_canjeada = "SELECT * FROM facturascnx 
                           WHERE Tienda = ? AND NoFactura = ? 
                           AND EstatusCanjeada = 1 LIMIT 1";
    $stmt_check = $conn_facturas->prepare($sql_check_canjeada);
    $stmt_check->bind_param("ss", $tienda, $nofactura);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows > 0) {
        mostrarError("Esta factura ya fue canjeada.");
    } else {
        mostrarError("Factura no encontrada, verifica el numero de factura.");
    }
}


$monto_actual = floatval($factura['Total']);

// 3. Obtener acumulado previo
$sql_prev = "SELECT SUM(Total) AS total_acumulado FROM facturascnx WHERE RFC = ? AND EstatusCanjeada = 1";
$stmt3 = $conn_facturas->prepare($sql_prev);
$stmt3->bind_param("s", $rfc);
$stmt3->execute();
$res3 = $stmt3->get_result();
$acumulado_previo = floatval($res3->fetch_assoc()['total_acumulado']);

// 4. Calcular puntos
$acumulado_nuevo = $acumulado_previo + $monto_actual;
$bloques_antes = floor($acumulado_previo / 50000);
$bloques_despues = floor($acumulado_nuevo / 50000);
$bloques_nuevos = $bloques_despues - $bloques_antes;
$nuevos_puntos = $bloques_nuevos * 30;

// 5. Obtener puntos actuales
$sql_puntos = "SELECT Puntos FROM puntos_rfc WHERE RFC = ?";
$stmt4 = $conn->prepare($sql_puntos);
$stmt4->bind_param("s", $rfc);
$stmt4->execute();
$res4 = $stmt4->get_result();

$puntos_actuales = 0;

if ($res4 && $res4->num_rows > 0) {
    $row_puntos = $res4->fetch_assoc();
    $puntos_actuales = intval($row_puntos['Puntos']);
} else {
    // Insertar cartera vacía si no existe
    $sql_insert_cartera = "INSERT INTO puntos_rfc (RFC, ID_Evento, Puntos) VALUES (?, ?, 0)";
    $stmt_cartera = $conn->prepare($sql_insert_cartera);
    $stmt_cartera->bind_param("si", $rfc, $evento_id);
    $stmt_cartera->execute();
}

// 6. Aplicar tope
$tope = 100;
$puntos_finales = min($puntos_actuales + $nuevos_puntos, $tope);
$puntos_a_sumar = $puntos_finales - $puntos_actuales;

// 7. Marcar factura como canjeada
$sql_canjear = "UPDATE facturascnx SET EstatusCanjeada = 1 WHERE Tienda = ? AND NoFactura = ?";
$stmt5 = $conn_facturas->prepare($sql_canjear);
$stmt5->bind_param("ss", $tienda, $nofactura);

$stmt5->execute();

// 8. Insertar/actualizar puntos_rfc
if ($puntos_a_sumar > 0) {
    $sql_up = "INSERT INTO puntos_rfc (RFC, ID_Evento, Puntos) 
               VALUES (?, ?, ?) 
               ON DUPLICATE KEY UPDATE Puntos = Puntos + ?";
    $stmt6 = $conn->prepare($sql_up);
    $stmt6->bind_param("siii", $rfc, $evento_id, $puntos_a_sumar, $puntos_a_sumar);
    $stmt6->execute();

    // 9. Actualizar en participante
    $sql_up_part = "UPDATE participante SET Puntos = ? WHERE RFC  = ? AND Telefono = ?";
    
    $stmt7 = $conn->prepare($sql_up_part);
    $stmt7->bind_param("iss", $puntos_finales, $rfc, $telefono);
    $stmt7->execute();
}

// 10. Mostrar resultado
echo '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Factura Validada</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      padding: 20px;
      font-family: "Segoe UI", sans-serif;
      background: radial-gradient(circle at center, #202040, #121212);
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      text-align: center;
    }
    .card {
      background: #1e1e2f;
      border: 2px solid #ff7b00;
      box-shadow: 0 0 25px rgba(255, 123, 0, 0.4);
      padding: 30px 20px;
      border-radius: 15px;
      max-width: 460px;
      width: 100%;
      animation: slideIn 0.8s ease-out;
    }
    h2 {
      color: #ff7b00;
      margin-bottom: 20px;
      font-size: 24px;
      text-shadow: 0 0 10px #ff7b00;
    }
    p {
      margin: 10px 0;
      font-size: 16px;
    }
    strong {
      color: #ff9900;
    }
    a {
      display: inline-block;
      margin-top: 25px;
      padding: 12px 20px;
      background: linear-gradient(90deg, #ff7b00, #ffaa00);
      color: #000;
      font-weight: bold;
      text-decoration: none;
      border-radius: 8px;
      box-shadow: 0 0 15px #ff990088;
      transition: background 0.3s ease, transform 0.2s;
    }
    a:hover {
      background: linear-gradient(90deg, #ffaa00, #ffbb33);
      transform: scale(1.03);
    }
    @keyframes slideIn {
      0% { transform: translateY(-20px); opacity: 0; }
      100% { transform: translateY(0); opacity: 1; }
    }
    @media (max-width: 480px) {
      .card { padding: 25px 15px; }
      h2 { font-size: 20px; }
      p { font-size: 15px; }
    }
  </style>
</head>
<body>
  <div class="card">
    <h2>✅ ¡Factura validada!</h2>
    <p><strong>RFC:</strong> ' . htmlspecialchars($rfc) . '</p>
    <p><strong>Monto de factura:</strong> $' . number_format($monto_actual, 2) . '</p>
    <p><strong>Monto acumulado:</strong> $' . number_format($acumulado_nuevo, 2) . '</p>
    <p><strong>Puntos anteriores:</strong> ' . $puntos_actuales . '</p>
    <p><strong>Puntos otorgados:</strong> ' . $puntos_a_sumar . '</p>
    <p><strong>Total en billetera:</strong> ' . $puntos_finales . ' / 100</p>
    <a href="index.php">← Volver</a>
  </div>
</body>
</html>';
