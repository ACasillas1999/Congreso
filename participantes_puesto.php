<?php
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] !== "Admin") {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once "Conexiones/Conexion.php";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
        <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <meta charset="UTF-8">
    <title>Participantes por Puesto</title>
    <?php include "header_css.php"; ?>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
  background: radial-gradient(circle at center, var(--theme-primary-dark, #0d1c3b), var(--theme-primary, #1e2a78));
            color: var(--theme-text, white);
            margin: 0;
            padding: 20px;
        }

        .titulo {
            text-align: center;
            font-size: 32px;
            color: var(--theme-title, #ff7f00);
            text-shadow: 0 0 10px var(--theme-title, #ff7f00);
            margin-bottom: 30px;
        }

        .btn-volver {
            display: inline-block;
            padding: 10px 20px;
            background: var(--naranja, #ff7f00);
            color: var(--theme-text, white);
            text-decoration: none;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(255,127,0,0.5);
            font-weight: bold;
            margin-bottom: 20px;
        }

        .buscador {
            text-align: center;
            margin-bottom: 30px;
        }

        #buscarPuesto {
            padding: 12px;
            font-size: 16px;
            width: 80%;
            max-width: 400px;
            border-radius: 5px;
            border: none;
            outline: none;
            background: var(--theme-surface, #eee);
            color: #222;
        }

        .grupo {
            background: var(--theme-surface-strong, #1a1a2e);
            margin: 0 auto 20px auto;
            max-width: 1000px;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255,127,0,0.3);
        }

        .grupo h3 {
            color: var(--theme-title, #ff7f00);
            margin: 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
        }

        .grupo h3:hover {
            text-decoration: underline;
        }

        .participantes {
            display: none;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.4);
        }

        th, td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        th {
            background-color: var(--theme-primary-dark, #1f1f3b);
            color: var(--theme-title, #ff7f00);
            font-weight: bold;
        }

        td {
            background-color: var(--theme-surface, #2a2a40);
        }

        .no-datos {
            color: var(--theme-text-soft, #ccc);
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>

<h2 class="titulo">👥 Participantes por Puesto - Evento <?= $id ?></h2>

<a href="Participantes.php?id=<?= $id ?>" class="btn-volver">← Volver</a>

<div class="buscador">
    <input type="text" id="buscarPuesto" placeholder="Buscar puesto...">
</div>

<?php
// Obtener todos los puestos distintos en el evento
$stmt = $conn->prepare("SELECT Puesto FROM participante WHERE ID_Evento = ? AND Puesto IS NOT NULL AND Puesto != '' GROUP BY Puesto ORDER BY Puesto");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

$puestos = [];

while ($row = $res->fetch_assoc()) {
    $puestos[] = $row['Puesto'];
}

foreach ($puestos as $puesto) {
    // Obtener cantidad de participantes por puesto
    $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM participante WHERE ID_Evento = ? AND Puesto = ?");
    $stmt_count->bind_param("is", $id, $puesto);
    $stmt_count->execute();
    $res_count = $stmt_count->get_result();
    $total = $res_count->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();

    echo "<div class='grupo' data-puesto='" . strtolower($puesto) . "'>";
    echo "<h3 onclick='toggleGrupo(\"$puesto\")'>Puesto: <span>$puesto</span> <span>($total)</span> <span>▼</span></h3>";
    echo "<div class='participantes' id='grupo_$puesto'>";


    $stmt_part = $conn->prepare("SELECT Nombre, Telefono, RFC, Sucursal FROM participante WHERE ID_Evento = ? AND Puesto = ?");
    $stmt_part->bind_param("is", $id, $puesto);
    $stmt_part->execute();
    $res_part = $stmt_part->get_result();

    echo "<table><tr><th>Nombre</th><th>Teléfono</th><th>RFC</th><th>Sucursal</th></tr>";
    while ($p = $res_part->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($p['Nombre']) . "</td>
                <td>" . htmlspecialchars($p['Telefono']) . "</td>
                <td>" . htmlspecialchars($p['RFC']) . "</td>
                <td>" . htmlspecialchars($p['Sucursal']) . "</td>
              </tr>";
    }

    echo "</table></div></div>";
    $stmt_part->close();
}
?>

<script>
function toggleGrupo(puesto) {
    const panel = document.getElementById("grupo_" + puesto);
    if (panel) {
        panel.style.display = (panel.style.display === "block") ? "none" : "block";
    }
}

document.getElementById("buscarPuesto").addEventListener("input", function () {
    const valor = this.value.toLowerCase();
    document.querySelectorAll(".grupo").forEach(grupo => {
        const puesto = grupo.getAttribute("data-puesto");
        grupo.style.display = puesto.includes(valor) ? "block" : "none";
    });
});
</script>

</body>
</html>
