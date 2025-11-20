<?php
require_once __DIR__ . "/conexion_congreso.php";

$telefono = $_POST['telefono'] ?? '';

if (!$telefono) {
    die("⚠️ Número no proporcionado.");
}

$sql = "SELECT Nombre, RFC FROM participante WHERE Telefono = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $telefono);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("❌ Número no registrado en el sistema.");
}

$row = $res->fetch_assoc();
$nombre = $row['Nombre'];
$rfc = $row['RFC'];

$puntos = 0;
$sql2 = "SELECT Puntos FROM participante WHERE Telefono = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("s", $telefono);
$stmt2->execute();
$res2 = $stmt2->get_result();
if ($res2->num_rows > 0) {
    $puntos = intval($res2->fetch_assoc()['Puntos']);
}

$porcentaje = min(100, round(($puntos / 100) * 100));
$nivel = $puntos == 0 ? "Sin puntos aún" : ($puntos < 100 ? "En progreso" : "¡Recompensa lista!");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Progreso de Facturas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      background: radial-gradient(circle at center, #202040, #121212);
      font-family: 'Segoe UI', sans-serif;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }

    .panel {
      background: #1e1e2f;
      border: 2px solid #ff7b00;
      border-radius: 16px;
      padding: 40px 30px;
      width: 90%;
      max-width: 460px;
      box-shadow: 0 0 25px rgba(255, 123, 0, 0.4);
      text-align: center;
      animation: slideIn 0.8s ease-out;
    }

    @keyframes slideIn {
      0% { transform: translateY(-20px); opacity: 0; }
      100% { transform: translateY(0); opacity: 1; }
    }

    .panel h2 {
      color: #ff7b00;
      font-size: 26px;
      margin-bottom: 20px;
      text-shadow: 0 0 10px #ff7b00;
    }

    .panel p {
      font-size: 16px;
      margin: 8px 0;
    }

    .progress-container {
      background: #292929;
      border-radius: 50px;
      height: 22px;
      margin: 20px 0;
      box-shadow: inset 0 0 8px #000;
      overflow: hidden;
    }

    .progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #ff7b00, #ffaa00);
      width: <?= $porcentaje ?>%;
      transition: width 0.5s ease;
      border-radius: 50px;
    }

    .badge {
      background-color: #ff7b00;
      color: #000;
      font-weight: bold;
      padding: 6px 14px;
      border-radius: 30px;
      box-shadow: 0 0 10px #ff7b00aa;
      display: inline-block;
      margin-top: 10px;
    }

    .btn-volver {
      margin-top: 25px;
      display: inline-block;
      padding: 10px 18px;
      background: linear-gradient(90deg, #ff7b00, #ffaa00);
      color: #000;
      font-weight: bold;
      border-radius: 8px;
      text-decoration: none;
      box-shadow: 0 0 12px #ff9900aa;
      transition: background 0.3s ease, transform 0.2s;
    }

    .btn-volver:hover {
      background: linear-gradient(90deg, #ffaa00, #ffbb33);
      transform: scale(1.03);
    }

    @media (max-width: 480px) {
      .panel {
        padding: 30px 20px;
      }

      .panel h2 {
        font-size: 22px;
      }

      .panel p {
        font-size: 15px;
      }
    }

.logo-container {
  position: absolute;
  top: 30px;
  text-align: center;
  width: 100%;
  z-index: 1;
    margin-bottom: 20px;

}

.logo-container img {
      width: 32rem;

  max-width: 60%;
  filter: drop-shadow(0 0 15px #ff7b00aa);
  animation: pulseGlow 3s infinite ease-in-out;
}

@keyframes pulseGlow {
  0%, 100% {
    filter: drop-shadow(0 0 15px #ff7b00aa);
  }
  50% {
    filter: drop-shadow(0 0 5px #ff7b0055);
  }
}

  </style>
</head>
<body>
<div class="logo-container">
  <img src="../img/logo_colores.png" alt="Logo Grupo Ascencio">
</div>

  <div class="panel">
    <h2>🎯 Progreso por Facturas</h2>
    <p><strong>Nombre:</strong> <?= htmlspecialchars($nombre) ?></p>
    <p><strong>RFC:</strong> <?= htmlspecialchars($rfc) ?></p>
    <p><strong>Puntos acumulados:</strong> <?= $puntos ?> / 100</p>

    <div class="progress-container">
      <div class="progress-bar"></div>
    </div>

    <span class="badge"><?= $nivel ?></span>

    <br><a href="index.php" class="btn-volver">← Volver</a>
  </div>

</body>
</html>
