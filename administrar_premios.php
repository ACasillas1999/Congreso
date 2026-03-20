<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_evento <= 0) {
    die("Evento no válido.");
}

// Agregar premio nuevo
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nombre']) && isset($_POST['puntos'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $puntos = intval($_POST['puntos']);

    $sql_insert = "INSERT INTO premios_evento (ID_Evento, NombrePremio, PuntosNecesarios) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("isi", $id_evento, $nombre, $puntos);
    $stmt->execute();
    $stmt->close();
    header("Location: administrar_premios.php?id=$id_evento");
    exit;
}

// Eliminar premio
if (isset($_GET['eliminar'])) {
    $id_premio = intval($_GET['eliminar']);
    $conn->query("DELETE FROM premios_evento WHERE ID = $id_premio AND ID_Evento = $id_evento");
    header("Location: administrar_premios.php?id=$id_evento");
    exit;
}

// Obtener premios actuales
$sql = "SELECT * FROM premios_evento WHERE ID_Evento = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_evento);
$stmt->execute();
$result = $stmt->get_result();
$premios = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Premios</title>
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon" />

    <link rel="stylesheet" href="styles.css">
    <?php include "header_css.php"; ?>
      <style>
        /* Fondo general */
        body {
            margin: 0;
            padding: 30px 15px;
            font-family: "Segoe UI", Arial, sans-serif;
            background: radial-gradient(circle at center, #1e2a78 0%, #000c2c 80%);
            color: #fff;
            min-height: 100vh;
            text-align: center;
        }

        h1, h2 {
            text-shadow: 0 0 6px rgba(255,255,255,0.3);
        }

        h1 {
            font-size: clamp(22px, 5vw, 34px);
            margin-bottom: 20px;
        }

        h2 {
            font-size: clamp(18px, 4vw, 26px);
            margin-top: 40px;
        }

        /* Formulario */
        form {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            max-width: 420px;
            margin: auto;
            box-shadow: 0 6px 16px rgba(0,0,0,0.4);
            display: flex;
            flex-direction: column;
            gap: 12px;
            text-align: left;
        }

        form label {
            font-weight: 600;
            margin-bottom: 4px;
        }

        form input {
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            width: 100%;
        }

        form button {
            margin-top: 10px;
            padding: 12px;
            background: linear-gradient(135deg, #ff8c00, #ff5722);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.3s ease;
        }
.mi-tabla td {
    color: #fff;
}

.mi-tabla a {
    color: #ff5252;
    font-weight: bold;
    text-decoration: none;
    transition: color 0.2s;
}

.mi-tabla a:hover {
    color: #ff1744;
}

/* Responsive: scroll horizontal en pantallas pequeñas */
@media (max-width: 480px) {
    table.mi-tabla {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    .mi-tabla th, .mi-tabla td {
        font-size: 14px;
        padding: 10px;
    }
}

/* Botón "Volver al evento" (el <p> que viene justo después de la tabla) */
.mi-tabla + p{
  margin-top: 28px;
  text-align: center;
}

.mi-tabla + p a{
  display: inline-flex;
  align-items: center;
  gap: 8px;                     /* separa el ícono ⬅️ del texto */
  padding: 12px 18px;
  border-radius: 10px;
  background: linear-gradient(135deg, #21a1f3, #1976d2);
  color: #fff;
  font-weight: 700;
  font-size: clamp(14px, 3.8vw, 16px);
  text-decoration: none;
  box-shadow: 0 6px 16px rgba(0,0,0,.35), inset 0 -2px 0 rgba(255,255,255,.12);
  transition: transform .15s ease, filter .2s ease, background .3s ease;
}

.mi-tabla + p a:hover{
  background: linear-gradient(135deg, #289cf6, #1e88e5);
  transform: translateY(-1px) scale(1.02);
}

.mi-tabla + p a:active{
  transform: translateY(0) scale(.98);
}

.mi-tabla + p a:focus-visible{
  outline: 2px solid rgba(33,150,243,.55);
  outline-offset: 3px;
  border-radius: 12px;
}

body{
  background: radial-gradient(circle at center, var(--theme-primary, #1e2a78) 0%, var(--theme-primary-dark, #000c2c) 80%);
  color: var(--theme-text, #fff);
}

h1,
h2{
  color: var(--theme-title, #7cecff);
}

form,
table.mi-tabla{
  background: var(--theme-surface-soft, rgba(255,255,255,0.05));
  border: 1px solid var(--theme-border, rgba(255,255,255,0.15));
  box-shadow: var(--theme-shadow, 0 6px 16px rgba(0,0,0,0.35));
}

form input{
  background: var(--theme-surface, #1f2a4d);
  color: var(--theme-text, #fff);
  border: 1px solid var(--theme-border, rgba(255,255,255,0.12));
}

form button{
  background: linear-gradient(135deg, var(--naranja, #ff8c00), var(--theme-accent, #21a1f3));
}

form button:hover{
  background: linear-gradient(135deg, var(--theme-accent, #21a1f3), var(--theme-primary-dark, #054a6b));
}

.mi-tabla th{
  background: var(--theme-primary-dark, #054a6b);
}

.mi-tabla td{
  color: var(--theme-text, #fff);
}

.mi-tabla a{
  color: var(--naranja, #ff8c00);
}

.mi-tabla a:hover{
  color: var(--theme-title, #7cecff);
}

.mi-tabla + p a{
  background: linear-gradient(135deg, var(--theme-accent, #21a1f3), var(--theme-primary-dark, #1976d2));
}

.mi-tabla + p a:hover{
  background: linear-gradient(135deg, var(--theme-primary, #289cf6), var(--theme-primary-dark, #1e88e5));
}


    </style>
</head>
<body>
    <h1>🎁 Premios para el Evento #<?= $id_evento ?></h1>

    <form method="POST">
        <label>Nombre del Premio:</label>
        <input type="text" name="nombre" required>

        <label>Puntos Necesarios:</label>
        <input type="number" name="puntos" required min="1">

        <button type="submit">Agregar Premio</button>
    </form>

    <h2>Premios Actuales</h2>
    <table class="mi-tabla">
        <tr>
            <th>Nombre</th>
            <th>Puntos</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($premios as $premio): ?>
            <tr>
                <td><?= htmlspecialchars($premio['NombrePremio']) ?></td>
                <td><?= $premio['PuntosNecesarios'] ?></td>
                <td>
                    <a href="?id=<?= $id_evento ?>&eliminar=<?= $premio['ID'] ?>" onclick="return confirm('¿Eliminar este premio?')">❌ Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="Evento_inicio.php?id=<?= $id_evento ?>">⬅️ Volver al evento</a></p>
</body>
</html>
