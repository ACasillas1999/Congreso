<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Canjea tus Puntos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php require_once __DIR__ . '/config_turnstile.php'; ?>

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 20px;
      font-family: 'Segoe UI', sans-serif;
      background: radial-gradient(circle at center, #202040, #121212);
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      min-height: 100vh;
    }

    .logo-glow {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 20px;
      margin-bottom: 20px;
      padding: 20px;
      border-radius: 20px;
      background: radial-gradient(circle, #ff7b0044 0%, #00000000 70%);
      box-shadow: 0 0 25px rgba(255, 123, 0, 0.6),
                  0 0 50px rgba(255, 123, 0, 0.4),
                  0 0 80px rgba(255, 123, 0, 0.2);
      animation: pulseGlow 3s infinite ease-in-out;
      width: 100%;
  max-width: 400px; /* antes estaba en 320px */
    }

    .logo-glow img {
  width: 320px;
  max-width: 100%;
  height: auto;
  filter: drop-shadow(0 0 35px #ff7b00aa);
  transition: transform 0.3s ease-in-out;
}


.logo-glow img:hover {
  transform: scale(1.05);
}

    @keyframes pulseGlow {
      0%, 100% {
        box-shadow: 0 0 25px rgba(255, 123, 0, 0.6),
                    0 0 50px rgba(255, 123, 0, 0.4),
                    0 0 80px rgba(255, 123, 0, 0.2);
        transform: scale(1);
      }
      50% {
        box-shadow: 0 0 10px rgba(255, 123, 0, 0.3),
                    0 0 30px rgba(255, 123, 0, 0.2),
                    0 0 40px rgba(255, 123, 0, 0.1);
        transform: scale(1.02);
      }
    }

    .container {
      background: #1f1f1f;
      padding: 30px 20px;
      border-radius: 16px;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 0 20px #ff7b0077;
      text-align: center;
      border: 2px solid #ff7b00;
      margin-bottom: 40px;
    }

    h1 {
      color: #ff7b00;
      margin-bottom: 25px;
      text-shadow: 0 0 10px #ff7b00;
      font-size: 22px;
    }

    label {
      display: block;
      text-align: left;
      margin-bottom: 6px;
      margin-top: 15px;
      color: #ddd;
      font-size: 14px;
    }

    input {
      width: 100%;
      padding: 12px;
      margin-bottom: 10px;
      border: none;
      border-radius: 8px;
      background: #292929;
      color: white;
      font-size: 16px;
      outline: none;
    }

    input::placeholder {
      color: #888;
    }

    .btn {
      width: 100%;
      padding: 14px;
      margin-top: 20px;
      border: none;
      border-radius: 8px;
      background: linear-gradient(90deg, #ff7b00, #ff9900);
      color: #000;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      box-shadow: 0 0 15px #ff990088;
      transition: background 0.3s, transform 0.2s;
    }

    .btn:hover {
      background: linear-gradient(90deg, #ff9900, #ffaa00);
      transform: scale(1.02);
    }

    .link {
      display: block;
      margin-top: 25px;
      color: #ff7b00;
      text-decoration: none;
      font-size: 15px;
      transition: color 0.3s;
    }

    .link:hover {
      color: #ffaa00;
    }

    @media (max-width: 480px) {
      body {
        padding: 10px;
      }

      h1 {
        font-size: 18px;
      }

      .container {
        padding: 25px 15px;
      }

      .logo-glow {
        max-width: 90%;
        padding: 15px;
      }
    }


    select {
  width: 100%;
  padding: 12px;
  margin-bottom: 10px;
  border: none;
  border-radius: 8px;
  background: #292929;
  color: white;
  font-size: 16px;
  outline: none;
}

select option {
  background: #292929;
  color: white;
}

    .cf-turnstile { margin: 12px 0; }


  </style>
      <link rel="icon" type="image/png" href="/congreso/educacion.png">

</head>
<body>

  <div class="logo-glow">
    <img src="../img/logo_colores.png" alt="Logo Grupo Ascencio">
  </div>

  <div class="container">
    <h1>Canjea tus Facturas por Puntos</h1>

    <!-- FORM 1: Validar y canjear -->
    <form action="validar.php" method="POST">
      <label for="telefono">📱 Teléfono</label>
      <input type="text" name="telefono" id="telefono" placeholder="Ej. 5522334455" required>

      <label for="tienda">🏬 Sucursal</label>
      <select name="tienda" id="tienda" required>
        <option value="" disabled selected>Selecciona una sucursal</option>
        <option value="DEASA">DEASA</option>
        <option value="DIMEGSA">DIMEGSA</option>
        <option value="TAPATIA">TAPATIA</option>
        <option value="SEGSA">SEGSA</option>
        <option value="FESA">FESA</option>
        <option value="CODI">CODI</option>
        <option value="ILUMINACION">ILUMINACION</option>
        <option value="AIESA">AIESA</option>
        <option value="GABSA">GABSA</option>
        <option value="VALLARTA">VALLARTA</option>
        <option value="QUERETARO">QUERETARO</option>
      </select>

      <label for="nofactura">🧾 No. de Factura</label>
      <input type="text" name="nofactura" id="nofactura" placeholder="Ej. F123456789" required>

      <!-- Captcha Turnstile -->
      <div class="cf-turnstile"
           data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>"
           data-theme="auto"
           data-size="normal"></div>

      <!-- Honeypot (oculto) -->
      <input type="text" name="website_url" style="display:none">

      <button type="submit" class="btn">Validar y Canjear</button>
    </form>

    <!-- FORM 2: Consultar progreso -->
    <form action="panel_progreso.php" method="POST">
      <label for="telefonoConsulta">📱 Consultar puntos por teléfono:</label>
      <input type="text" name="telefono" id="telefonoConsulta" placeholder="Ej. 5522334455" required>

      <!-- Captcha Turnstile -->
      <div class="cf-turnstile"
           data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>"
           data-theme="auto"
           data-size="normal"></div>

      <!-- Honeypot -->
      <input type="text" name="website_url" style="display:none">

      <button type="submit" class="btn">Ver mi progreso</button>
    </form>
  </div>

</body>
</html>
