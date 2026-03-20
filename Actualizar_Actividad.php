<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$id_actividad = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_evento = 0;
$actividad = '';
$descripcion = '';
$capacidad = 0;
$puntos = 0;

if ($id_actividad > 0) {
    if ($stmt = $conn->prepare("SELECT * FROM actividades WHERE ID = ?")) {
        $stmt->bind_param("i", $id_actividad);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $id_evento = $row['ID_Evento'];
            $actividad = $row['Actividad'];
            $descripcion = $row['Descripcion'];
            $capacidad = $row['capacidad'];
            $puntos = $row['Puntos_Default'];
        }
        $stmt->close();
    }
}

if (!$actividad) {
    die("Actividad no encontrada.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    <title>Actualizar Actividad - #<?php echo $id_actividad; ?></title>
    <?php include "header_css.php"; ?>
    <style>
        body, button, input, select, textarea {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }
        @media (min-width: 768px) {
            body { padding-right: var(--sidebar-width, 280px) !important; }
        }
        .reg-container {
            width: 100% !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }
        .center-box {
            width: 100%;
            max-width: 650px;
        }
        .btn-volver:hover {
            background: var(--theme-primary) !important;
            color: white !important;
        }
        .glass-card {
            background: var(--theme-surface-strong);
            border: 1px solid var(--theme-border);
            border-radius: 12px;
            padding: 32px;
            box-shadow: var(--theme-shadow);
        }
        .input-group { margin-bottom: 20px; }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--theme-text-soft);
            font-size: 13px;
            font-weight: 600;
        }
        .input-group input, .input-group textarea {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--theme-border);
            color: white;
            font-size: 15px;
        }
        .input-group input:focus, .input-group textarea:focus {
            outline: none;
            border-color: var(--theme-primary);
            box-shadow: 0 0 10px rgba(56, 217, 255, 0.2);
        }
        .btn-submit {
            width: 100%;
            padding: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: var(--theme-primary);
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            background: var(--theme-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="fade-in">
    <?php include "sidebar.php"; ?>

    <div class="reg-container">
        <div class="center-box">
            <div class="page-header" style="background:var(--theme-surface-strong); border:1px solid var(--theme-border); padding:24px; border-radius:12px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center;">
                <div class="header-left">
                  <h2 class="page-title" style="margin:0; color:var(--theme-title); font-size:24px; text-shadow:0 0 10px var(--theme-title);">Actualizar Actividad</h2>
                  <p class="page-subtitle" style="margin:4px 0 0; color:var(--theme-text-soft); font-size:13px;">Actividad #<?php echo $id_actividad; ?></p>
                </div>
                <div class="header-actions">
                  <a href="Actividades.php?id=<?php echo $id_actividad; ?>" class="btn-volver" style="text-decoration:none; padding:8px 20px; border-radius:30px; background:var(--theme-chip); color:var(--theme-title); border:1px solid var(--theme-border); font-size:13px; font-weight:600; display:inline-block;">← Volver</a>
                </div>
            </div>

            <form action="Funcion_Actualizar_Actividad.php" method="POST" class="glass-card">
                <input type="hidden" name="id" value="<?php echo $id_actividad; ?>">
                <input type="hidden" name="id_evento" value="<?php echo $id_evento; ?>">

                <div class="input-group">
                    <label>Nombre de la Actividad:</label>
                    <input type="text" name="actividad" value="<?php echo htmlspecialchars($actividad); ?>" required>
                </div>

                <div class="input-group">
                    <label>Descripción:</label>
                    <textarea name="descripcion" required style="min-height:100px;"><?php echo htmlspecialchars($descripcion); ?></textarea>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="input-group">
                        <label>Capacidad:</label>
                        <input type="number" name="capacidad" value="<?php echo htmlspecialchars($capacidad); ?>" required min="1">
                    </div>
                     <div class="input-group">
                        <label>Puntos:</label>
                        <input type="number" name="puntos" value="<?php echo htmlspecialchars($puntos); ?>" required min="0">
                    </div>
                </div>
                
                <div style="margin-top:10px;">
                    <button type="submit" class="btn-submit">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
