<?php
require_once __DIR__ . "/Conexiones/Conexion.php";
$id_evento = isset($_GET['evento']) ? intval($_GET['evento']) : 0;

$sql = "SELECT pp.usuario, COUNT(*) as total_puntos
        FROM puntos_proveedor pp
        WHERE pp.id_evento = ?
        GROUP BY pp.usuario
        ORDER BY total_puntos DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_evento);
$stmt->execute();
$result = $stmt->get_result();

// Datos para la gráfica
$labels = [];
$data = [];

echo "<div class='container py-4'>";
echo "<h2 class='text-center mb-4'>Estadísticas de Proveedores</h2>";

echo "<canvas id='graficaPuntos' height='100'></canvas><hr class='my-4'>";

// Acordeón de proveedores
echo "<div class='accordion' id='accordionProveedores'>";
$index = 0;
while ($row = $result->fetch_assoc()) {
    $usuario = $row['usuario'];
    $total = $row['total_puntos'];

    $labels[] = $usuario;
    $data[] = $total;

    // Participantes escaneados por proveedor
    $stmt_det = $conn->prepare("
        SELECT pa.Nombre, pp.puntos, pp.fecha 
        FROM puntos_proveedor pp
        JOIN participante pa ON pa.ID = pp.id_participante
        WHERE pp.usuario = ? AND pp.id_evento = ?
        ORDER BY pp.fecha DESC
    ");
    $stmt_det->bind_param("si", $usuario, $id_evento);
    $stmt_det->execute();
    $detalles = $stmt_det->get_result();

    echo "
    <div class='accordion-item'>
        <h2 class='accordion-header' id='heading$index'>
            <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse$index' aria-expanded='false' aria-controls='collapse$index'>
                <strong>$usuario</strong> - Total puntos: <span class='text-success ms-2'>$total</span>
            </button>
        </h2>
        <div id='collapse$index' class='accordion-collapse collapse' aria-labelledby='heading$index' data-bs-parent='#accordionProveedores'>
            <div class='accordion-body'>
                <div class='table-responsive'>
                    <table class='table table-sm table-bordered align-middle'>
                        <thead class='table-light'>
                            <tr>
                                <th>Participante</th>
                                <th>Puntos</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>";
                            while ($det = $detalles->fetch_assoc()) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($det['Nombre']) . "</td>
                                    <td>" . htmlspecialchars($det['puntos']) . "</td>
                                    <td>" . date("d/m/Y H:i", strtotime($det['fecha'])) . "</td>
                                </tr>";
                            }
    echo "          </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>";
    $index++;
}
echo "</div>";
echo "</div>";

// Preparar datos para gráfica
$js_labels = json_encode($labels);
$js_data = json_encode($data);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estad�sticas Proveedores</title>
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include "header_css.php"; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
    body {
        background: linear-gradient(180deg, var(--theme-bg-start, #95ecff), var(--theme-bg-end, #054a6b));
        color: var(--theme-text, #fff);
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
    }
    h2 {
        color: var(--theme-title, #7cecff);
        text-align: center;
        font-weight: 600;
        margin-bottom: 30px;
        text-shadow: 1px 1px 5px #000;
    }
    .accordion-button {
        background-color: rgba(8, 27, 50, 0.88);
        color: var(--theme-text, #fff);
        font-weight: 600;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .accordion-button:hover { background-color: rgba(255,255,255,.08); }
    .accordion-button:not(.collapsed) {
        background-color: var(--theme-primary-dark, #054a6b);
        color: #fff;
        box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
    }
    .accordion-item {
        background-color: rgba(8, 27, 50, 0.88);
        border: 1px solid rgba(255,255,255,.08);
        margin-bottom: 10px;
        border-radius: 8px;
    }
    .accordion-body { background-color: rgba(255,255,255,.04); }
    .table {
        background-color: rgba(255,255,255,.04) !important;
        color: #fff;
    }
    .table thead { background-color: var(--theme-primary-dark, #054a6b); color: #fff; }
    .table tbody tr:hover { background-color: rgba(255,255,255,.08); }
    .btn-volver {
        display: inline-block;
        background: linear-gradient(90deg, var(--theme-primary, #1ca9dc), var(--theme-primary-dark, #054a6b));
        color: white;
        padding: 12px 25px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        transition: background 0.3s ease, transform 0.2s;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.25);
    }
    .btn-volver:hover {
        background: linear-gradient(90deg, var(--theme-primary-dark, #054a6b), var(--theme-primary, #1ca9dc));
        transform: scale(1.05);
    }
    canvas {
        background: rgba(8,27,50,.62);
        border-radius: 12px;
        padding: 20px;
        margin-top: 30px;
        box-shadow: 0 0 20px rgba(0,0,0,0.18);
    }
</style>
</head>
<body>

<div class="container my-3">
<a href="Evento_inicio.php?id=<?= htmlspecialchars($_GET['evento'] ?? 0) ?>" class="btn-volver">
        ← Volver al evento
    </a>
</div>

<script>
const themeStyles = getComputedStyle(document.documentElement);
const themeBar = themeStyles.getPropertyValue('--theme-title').trim() || '#7cecff';
const themeBorder = themeStyles.getPropertyValue('--theme-primary').trim() || '#1ca9dc';
const ctx = document.getElementById('graficaPuntos').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $js_labels ?>,
        datasets: [{
            label: 'Puntos otorgados',
            data: <?= $js_data ?>,
                backgroundColor: themeBar,
            borderColor: themeBorder,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Cantidad de puntos'
                }
            }
        }
    }
});
</script>


