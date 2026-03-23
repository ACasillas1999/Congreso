<?php
// 1. Manejo de la sesión y seguridad
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

if (($_SESSION["Rol"] ?? '') !== 'Admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

// 2. Manejo de la búsqueda AJAX (DEBE ESTAR ARRIBA PARA EVITAR RENDERIZADO DEL SIDEBAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['busqueda'])) {
    require_once __DIR__ . "/Conexiones/Conexion.php";
    $b = "%" . $_POST['busqueda'] . "%";
    $sql = "SELECT E.*, (SELECT COUNT(*) FROM participante P WHERE P.ID_Evento = E.ID) AS total_part 
            FROM evento E 
            WHERE E.name_evento LIKE ? OR E.ubicacion LIKE ? 
            ORDER BY E.fecha_inicio DESC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $b, $b);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<table class='mi-tabla'>";
            echo "<tr><th>ID</th><th>Evento</th><th>Ubicación</th><th>Estado</th><th>Inicio</th><th>Participantes</th><th>Acción</th></tr>";
            while ($row = $result->fetch_assoc()) {
                $est = strtoupper((string)($row['estado'] ?? ''));
                $badg = ($est=='CANCELADO'?'badge-cancelado':($est=='EN CURSO'?'badge-encurso':'badge-finalizado'));
                echo "<tr>
                    <td>{$row['ID']}</td>
                    <td><b>" . htmlspecialchars($row['name_evento']) . "</b></td>
                    <td>" . htmlspecialchars($row['ubicacion']) . "</td>
                    <td><span class='badge $badg'>{$row['estado']}</span></td>
                    <td>" . htmlspecialchars($row['fecha_inicio']) . "</td>
                    <td>{$row['total_part']} / {$row['capacidad']}</td>
                    <td><a href='Evento_inicio.php?id={$row['ID']}'>Ver Detalles</a></td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='mensaje-vacio'>No se encontraron eventos coincidentes.</div>";
        }
        $stmt->close();
    }
    $conn->close();
    exit; // Detener ejecución aquí para peticiones AJAX
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <?php include "header_css.php"; ?>
    <style>
        #resultado {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .search-container {
            margin-bottom: 28px;
            position: relative;
            max-width: 100%;
        }
        .search-input {
            width: 100%;
            padding: 14px 20px 14px 48px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--theme-border);
            color: #fff;
            border-radius: var(--theme-radius);
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            background: rgba(255,255,255,0.1);
            border-color: var(--theme-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(56, 217, 255, 0.15);
        }
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--theme-text-soft);
            pointer-events: none;
        }
    </style>
</head>
<body class="fade-in">
    <?php include "sidebar.php"; ?>
    <div class="container">
        <h2 class="titulo">Panel de Eventos</h2>
        
        <div class="search-container">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="busqueda" class="search-input" placeholder="Buscar por nombre o ubicación...">
        </div>

        <div id="resultado">
            <!-- Los resultados se cargan vía AJAX -->
        </div>
    </div>

    <script>
        function bindRowNavigation() {
            if (window.matchMedia('(min-width: 992px)').matches) return;
            document.querySelectorAll('.mi-tabla tr').forEach(tr => {
                if (tr.querySelector('th')) return;
                let linkEl = tr.querySelector('a[href*="Evento_inicio.php"]');
                let href = linkEl ? linkEl.getAttribute('href') : null;
                if (href) {
                    tr.style.cursor = 'pointer';
                    tr.onclick = function(e) {
                        if (!['A','BUTTON','INPUT'].includes(e.target.tagName)) {
                            window.location.href = href;
                        }
                    };
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            const input = document.getElementById("busqueda");
            const resDiv = document.getElementById("resultado");

            input.addEventListener("input", function() {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        resDiv.innerHTML = xhr.responseText;
                        bindRowNavigation();
                    }
                };
                xhr.send("busqueda=" + encodeURIComponent(input.value));
            });

            // Carga inicial
            input.dispatchEvent(new Event('input'));
        });
    </script>
    <script src="animacion.js"></script>
</body>
</html>
