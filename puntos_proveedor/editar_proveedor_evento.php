<?php
include "../Conexiones/Conexion.php";
$id = $_GET['id'];
$evento = $_GET['evento'];

$sql = "SELECT * FROM proveedor_evento WHERE ID = $id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Proveedor</title>
    <?php include "../header_css.php"; ?>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, var(--theme-primary-dark, #6a11cb), var(--theme-primary, #2575fc));
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--theme-text, #fff);
        }

        .form-container {
            background: var(--theme-surface-strong, white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--theme-shadow, 0 10px 25px rgba(0, 0, 0, 0.2));
            width: 100%;
            max-width: 400px;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: var(--theme-title, #333);
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            color: var(--theme-text-soft, #555);
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--theme-border, #ccc);
            border-radius: 6px;
            font-size: 14px;
            background: var(--theme-surface, #fff);
            color: var(--theme-text, #111);
        }

        button,
        .btn-volver {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            background-color: var(--theme-primary, #2575fc);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        button:hover,
        .btn-volver:hover {
            background-color: var(--theme-primary-dark, #1a5dd8);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Editar Proveedor</h2>
        <form action="guardar_edicion_proveedor.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="evento" value="<?php echo $evento; ?>">

            <label>Nombre del Proveedor:</label>
            <input type="text" name="NombreProveedor" value="<?php echo htmlspecialchars($row['NombreProveedor']); ?>" required>

            <label>Puntos:</label>
            <input type="number" name="Puntos" value="<?php echo $row['Puntos']; ?>" required min="0">

            <button type="submit">Guardar Cambios</button>
        </form>

        <a href="../Evento_inicio.php?id=<?php echo $evento; ?>" class="btn-volver">⬅️ Cancelar y Volver</a>
    </div>
</body>
</html>
