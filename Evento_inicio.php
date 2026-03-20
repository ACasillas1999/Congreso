<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
require_once __DIR__ . "/Conexiones/Conexion.php";

$nombre_evento = "";
if ($id > 0) {
    if ($stmt = $conn->prepare("SELECT name_evento FROM evento WHERE ID = ?")) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($nombre_evento);
        $stmt->fetch();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Evento</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <?php include "header_css.php"; ?>
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    <style>
        .acordeon-section {
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--theme-border);
            border-radius: var(--theme-radius);
            overflow: hidden;
        }
        .acordeon-header {
            background: var(--theme-primary-dark);
            color: #fff;
            padding: 10px 18px; /* Reducido de 15px 22px */
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .acordeon-content {
            display: none;
            padding: 24px;
            background: rgba(0, 0, 0, 0.12);
        }
        .acordeon-active .acordeon-content {
            display: block;
        }
        .agenda-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .agenda-item {
            background: linear-gradient(135deg, var(--theme-surface-soft), rgba(255,255,255,0.03));
            color: var(--theme-text);
            padding: 22px;
            border-radius: var(--theme-radius);
            border: 1px solid var(--theme-border);
            position: relative;
            transition: all 0.3s ease;
        }
        .agenda-item:hover {
            transform: translateY(-4px);
            background: rgba(255,255,255,0.08);
            border-color: var(--theme-primary);
        }
        .agenda-item h3 { 
            margin: 0 0 10px; 
            font-size: 16px; 
            color: var(--theme-title); 
            text-shadow: 0 0 8px rgba(124, 236, 255, 0.4); 
            font-weight: 700;
        }
        .agenda-item p { 
            margin: 5px 0; 
            font-size: 14px; 
            color: var(--theme-text);
            opacity: 1; 
        }
        .agenda-item button {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: rgba(56, 217, 255, 0.1);
            color: var(--theme-title);
            border: 1px solid var(--theme-title);
            border-radius: var(--theme-radius);
            cursor: pointer;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }
        .agenda-item button:hover {
            background: var(--theme-title);
            color: #000;
            box-shadow: 0 0 15px var(--theme-title);
        }
        .mensaje-vacio {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: var(--theme-radius);
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--theme-text-soft);
        }
    </style>
</head>
<body class="fade-in">
    <?php include "sidebar.php"; ?>
    <div class="container">
        <h2 class="titulo">
            <?php echo $nombre_evento ? "Evento: " . htmlspecialchars($nombre_evento) : "Evento no encontrado"; ?>
        </h2>

        <?php
        if ($id > 0) {
            // 1. Información del Evento
            $stmt = $conn->prepare("SELECT ID, name_evento, ubicacion, duracion, estado, fecha_inicio FROM evento WHERE ID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $ubicacion_nombre = '';
            if ($res && $res->num_rows > 0) {
                echo '<div class="acordeon-section">';
                echo '<div class="acordeon-header"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Información del Evento</div>';
                echo '<div class="acordeon-content"><table class="mi-tabla">';
                echo "<tr><th>Nombre</th><th>Ubicación</th><th>Duración</th><th>Estado</th><th>Inicio</th><th>Acción</th></tr>";
                while ($row = $res->fetch_assoc()) {
                    $est = strtoupper((string)($row['estado'] ?? ''));
                    $badg = ($est=='CANCELADO'?'badge-cancelado':($est=='EN CURSO'?'badge-encurso':'badge-finalizado'));
                    $ubicacion_nombre = $row['ubicacion'];
                    echo "<tr><td>{$row['name_evento']}</td><td>{$row['ubicacion']}</td><td>{$row['duracion']}</td><td><span class='badge $badg'>{$row['estado']}</span></td><td>{$row['fecha_inicio']}</td><td><a href='Actualizar_Evento.php?id={$row['ID']}'>Editar</a></td></tr>";
                }
                echo "</table></div></div>";
            }
            $stmt->close();

            // 2. Ubicación
            if (!empty($ubicacion_nombre)) {
                $stmt = $conn->prepare("SELECT U.Nombre, U.Direccion, U.Salones, U.Capacidad_por_salon, E.capacidad, (SELECT COUNT(*) FROM participante P WHERE P.ID_Evento = E.ID) AS total_part FROM ubicaciones U JOIN evento E ON U.Nombre = E.ubicacion WHERE U.Nombre = ? AND E.ID = ?");
                $stmt->bind_param("si", $ubicacion_nombre, $id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    echo '<div class="acordeon-section">';
                    echo '<div class="acordeon-header"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> Ubicación del Evento</div>';
                    echo '<div class="acordeon-content"><table class="mi-tabla">';
                    echo "<tr><th>Nombre</th><th>Dirección</th><th>Salones</th><th>Cap/Salón</th><th>Asistentes</th><th>Capacidad</th></tr>";
                    while ($ur = $res->fetch_assoc()) {
                        echo "<tr><td>{$ur['Nombre']}</td><td>{$ur['Direccion']}</td><td>{$ur['Salones']}</td><td>{$ur['Capacidad_por_salon']}</td><td>{$ur['total_part']}</td><td>{$ur['capacidad']}</td></tr>";
                    }
                    echo "</table></div></div>";
                }
                $stmt->close();
            }

            // 3. Actividades
            $stmt = $conn->prepare("SELECT ID, Actividad, Descripcion, Puntos_Default FROM actividades WHERE ID_Evento = ? ORDER BY Actividad");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                echo '<div class="acordeon-section">';
                echo '<div class="acordeon-header"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> Actividades del Evento</div>';
                echo '<div class="acordeon-content"><table class="mi-tabla">';
                echo "<tr><th>ID</th><th>Actividad</th><th>Descripción</th><th>Puntos</th><th>Acción</th></tr>";
                while ($ar = $res->fetch_assoc()) {
                    echo "<tr><td>{$ar['ID']}</td><td>{$ar['Actividad']}</td><td>{$ar['Descripcion']}</td><td>{$ar['Puntos_Default']}</td><td><a href='Actividades.php?id={$ar['ID']}'>Ver</a></td></tr>";
                }
                echo "</table></div></div>";
            } else {
                echo "<div class='mensaje-vacio'><svg width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'></circle><line x1='12' y1='8' x2='12' y2='12'></line><line x1='12' y1='16' x2='12.01' y2='16'></line></svg> No se encontraron actividades.</div>";
            }
            $stmt->close();

            // 4. Agenda (Agenda Grid)
            $stmt = $conn->prepare("SELECT Fecha, Actividad FROM agenda WHERE ID_Evento = ? ORDER BY Fecha");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                echo '<div class="acordeon-section">';
                echo '<div class="acordeon-header"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> Agenda del Evento</div>';
                echo '<div class="acordeon-content"><div class="agenda-grid">';
                $ag = [];
                while ($r = $res->fetch_assoc()) {
                    if (!isset($ag[$r['Fecha']])) $ag[$r['Fecha']] = [];
                    if (!empty($r['Actividad'])) $ag[$r['Fecha']][] = $r['Actividad'];
                }
                foreach ($ag as $f => $as) {
                    echo "<div class='agenda-item'><h3>Fecha: $f</h3>";
                    if (empty($as)) echo "<p>Sin actividad programada</p>";
                    else foreach ($as as $a) echo "<p>• $a</p>";
                    echo "<button onclick=\"location.href='ver_horario_dia.php?id=$id&fecha=".urlencode($f)."'\">Ver Horario</button></div>";
                }
                echo "</div></div></div>";
            }
            $stmt->close();

            // 5. Proveedores
            $stmt = $conn->prepare("SELECT p.ID, p.NombreProveedor, p.Puntos, u.password_visible FROM proveedor_evento p LEFT JOIN usuarios u ON u.username = p.NombreProveedor WHERE p.ID_Evento = ? AND p.Activo = 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                echo '<div class="acordeon-section">';
                echo '<div class="acordeon-header"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> Proveedores Asignados</div>';
                echo '<div class="acordeon-content"><table class="mi-tabla"><tr><th>Proveedor</th><th>Puntos</th><th>Clave</th><th>Acciones</th></tr>';
                while ($p = $res->fetch_assoc()) {
                    $pass = htmlspecialchars($p['password_visible'] ?? '***');
                    echo "<tr><td>{$p['NombreProveedor']}</td><td>{$p['Puntos']}</td><td>$pass</td><td><a href='puntos_proveedor/editar_proveedor_evento.php?id={$p['ID']}&evento=$id'>Edit</a> | <a href='puntos_proveedor/regenerar_password.php?usuario=".urlencode($p['NombreProveedor'])."'>Reset</a></td></tr>";
                }
                echo "</table></div></div>";
            }
            $stmt->close();

            // 6. Estadísticas
            $stmt = $conn->prepare("SELECT pp.usuario, COUNT(*) as sc, SUM(pp.puntos) as tp, MAX(pp.fecha) as last FROM puntos_proveedor pp JOIN proveedor_evento pe ON pp.usuario = pe.NombreProveedor WHERE pe.ID_Evento = ? GROUP BY pp.usuario ORDER BY tp DESC");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                echo '<div class="acordeon-section">';
                echo '<div class="acordeon-header"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:12px;"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg> Estadísticas de Escaneo</div>';
                echo '<div class="acordeon-content"><table class="mi-tabla"><tr><th>Usuario</th><th>Escaneos</th><th>Puntos</th><th>Último</th></tr>';
                while ($s = $res->fetch_assoc()) {
                    echo "<tr><td>{$s['usuario']}</td><td>{$s['sc']}</td><td>{$s['tp']}</td><td>{$s['last']}</td></tr>";
                }
                echo "</table></div></div>";
            }
            $stmt->close();
        }
        $conn->close();
        ?>
    </div>

    <script src="animacion.js"></script>
    <script>
        document.querySelectorAll(".acordeon-header").forEach(header => {
            header.addEventListener("click", () => {
                header.parentElement.classList.toggle("acordeon-active");
            });
        });
    </script>
</body>
</html>
