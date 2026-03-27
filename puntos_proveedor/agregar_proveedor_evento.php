<?php include "../Conexiones/Conexion.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Proveedor al Evento</title>
    <?php include "../header_css.php"; ?>
    <style>
         /* ========== FONDO GENERAL ========== */
    body {
      font-family: 'Segoe UI', sans-serif;
      background: radial-gradient(circle at center, var(--theme-primary-dark, #0d1c3b), var(--theme-primary, #1e2a78));
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      color: var(--theme-text, #e0e0e0);
    }

    /* ========== CONTENEDOR DEL FORMULARIO ========== */
    .form-container {
      background-color: var(--theme-surface-strong, rgba(30, 30, 47, 0.96));
      padding: 30px;
      border-radius: 15px;
      box-shadow:
        0 -40px 40px -20px rgba(199, 111, 57, 0.25),
        0 40px 40px -20px rgba(30, 144, 255, 0.25);
      backdrop-filter: blur(6px);
      width: 90%;
      max-width: 400px;
      box-sizing: border-box;
      animation: fadeIn 1s ease-out;
    }

    /* ========== TÍTULO ========== */
    .form-container h2 {
      text-align: center;
      margin-bottom: 25px;
      font-size: 28px;
      color: var(--naranja, #c76f39);
      text-shadow:
        0 0 6px var(--naranja, #c76f39),
        0 0 12px var(--naranja, #c76f39);
    }

    /* ========== LABELS ========== */
    label {
      display: block;
      margin-top: 15px;
      margin-bottom: 5px;
      color: var(--theme-text-soft, #dddddd);
      font-weight: 500;
    }

    /* ========== INPUTS Y SELECT ========== */
    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      box-sizing: border-box;
      padding: 10px;
      margin-bottom: 10px;
      border: none;
      border-radius: 8px;
      background-color: var(--theme-surface, #2a2a3f);
      color: var(--theme-text, #fff);

    input[type="text"]:focus,
    input[type="number"]:focus,
    select:focus {
      outline: none;
      box-shadow: 0 0 8px var(--naranja, #c76f39);
    }

    /* ========== BOTONES ========== */
    button,
    .btn-volver {
      width: 100%;
      margin-top: 20px;
      padding: 12px;
      background-color: var(--naranja, #c76f39);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      box-shadow:
        0 0 10px #c76f39,
        0 0 20px #c76f39;
      transition: background-color 0.3s ease, transform 0.2s ease;
      text-align: center;
      text-decoration: none;
      display: inline-block;
    }

    button:hover,
    .btn-volver:hover {
      background-color: var(--theme-primary-dark, #a85522);
      transform: scale(1.03);
    }

    /* ========== ANIMACIONES ========== */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Agregar Proveedor</h2>
        <form action="guardar_proveedor_evento.php" method="POST">
            <label>Evento:</label>
            <select name="ID_Evento" required>
                <?php
                $result = $conn->query("SELECT ID, name_evento FROM evento");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['ID']}'>{$row['name_evento']}</option>";
                }
                ?>
            </select>

            <label>Nombre del Proveedor:</label>
            <input type="text" name="NombreProveedor" required>

            <label>Puntos que otorgará:</label>
            <input type="number" name="Puntos" min="0" required>

            <button type="submit">Agregar Proveedor</button>
        </form>

  <button type="button" onclick="location.href='../index.php'">⬅️ Volver</button>
    </div>
</body>
</html>
