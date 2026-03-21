<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo "Sesion no valida.";
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

function obtenerRutaQrDisponible(?string $qrPath): string
{
    $qrPath = trim((string)$qrPath);
    if ($qrPath === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $qrPath)) {
        return htmlspecialchars($qrPath, ENT_QUOTES, 'UTF-8');
    }

    $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $qrPath), DIRECTORY_SEPARATOR);
    $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . $relativePath;

    if (!is_file($absolutePath)) {
        return '';
    }

    return htmlspecialchars(str_replace('\\', '/', $qrPath), ENT_QUOTES, 'UTF-8');
}

$busqueda = isset($_POST['busqueda']) ? trim((string)$_POST['busqueda']) : '';
$rol = $_SESSION["Rol"] ?? '';
$soloActivos = ($rol === 'Vendedor');

$sql = "SELECT
            P.ID,
            E.name_evento,
            P.Sucursal,
            P.Vendedor,
            P.Nombre,
            P.RFC,
            P.Puesto,
            P.Proveedor,
            P.Telefono,
            P.QR_Code
        FROM participante P
        JOIN evento E ON P.ID_Evento = E.ID
        WHERE (
            P.Nombre LIKE ?
            OR P.Sucursal LIKE ?
            OR P.Vendedor LIKE ?
            OR P.Proveedor LIKE ?
            OR E.name_evento LIKE ?
        )";

if ($soloActivos) {
    // "EN CURSO" es el estado activo actual; "ACTIVO" se incluye por compatibilidad.
    $sql .= " AND UPPER(TRIM(E.estado)) IN ('EN CURSO', 'ACTIVO')";
}

$sql .= " ORDER BY P.ID DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Error en la consulta: " . $conn->error;
    $conn->close();
    exit;
}

$like = "%{$busqueda}%";
$stmt->bind_param("sssss", $like, $like, $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    echo "Error en la consulta: " . $conn->error;
} else {
    if ($result->num_rows > 0) {
        echo "<table class='mi-tabla'>";
        echo "<tr>
            <th>ID</th>
            <th>Evento</th>
            <th>Sucursal</th>
            <th>Vendedor</th>
            <th>Nombre</th>
            <th>RFC</th>
            <th>Puesto</th>
            <th>Proveedor</th>
            <th>Telefono</th>
            <th>QR</th>
            <th colspan='2'>Acciones</th>
        </tr>";

        while ($row = $result->fetch_assoc()) {
            $id = (int)$row["ID"];
            $evento = htmlspecialchars((string)$row["name_evento"], ENT_QUOTES, 'UTF-8');
            $sucursal = htmlspecialchars((string)$row["Sucursal"], ENT_QUOTES, 'UTF-8');
            $vendedor = htmlspecialchars((string)$row["Vendedor"], ENT_QUOTES, 'UTF-8');
            $nombre = htmlspecialchars((string)$row["Nombre"], ENT_QUOTES, 'UTF-8');
            $rfc = htmlspecialchars((string)$row["RFC"], ENT_QUOTES, 'UTF-8');
            $puesto = htmlspecialchars((string)$row["Puesto"], ENT_QUOTES, 'UTF-8');
            $proveedor = htmlspecialchars((string)$row["Proveedor"], ENT_QUOTES, 'UTF-8');
            $telefono = htmlspecialchars((string)$row["Telefono"], ENT_QUOTES, 'UTF-8');
            $qrImagePath = obtenerRutaQrDisponible($row["QR_Code"] ?? '');
            $qrHtml = $qrImagePath !== ''
                ? "<img src='{$qrImagePath}' alt='QR' style='width: 60px; height: auto;' loading='lazy'>"
                : "<span style='color:#94a3b8;font-size:12px;'>Sin QR</span>";

            echo "<tr>";
            echo "<td><b>{$id}</b></td>";
            echo "<td>{$evento}</td>";
            echo "<td>{$sucursal}</td>";
            echo "<td>{$vendedor}</td>";
            echo "<td style='max-width: 150px; word-wrap: break-word;'>{$nombre}</td>";
            echo "<td>{$rfc}</td>";
            echo "<td>{$puesto}</td>";
            echo "<td>{$proveedor}</td>";
            echo "<td>{$telefono}</td>";
            echo "<td>{$qrHtml}</td>";
            echo "<td><a href='DescargarGafete.php?id={$id}' class='btn-tabla' title='Gafete'>Gafete</a></td>";
            echo "<td><a href='DescargarHorario.php?id={$id}' class='btn-tabla' title='Horario' style='background: rgba(234, 179, 8, 0.15); color: #eab308; border-color: rgba(234, 179, 8, 0.3);'>Horario</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No se encontraron resultados.";
    }
}

$stmt->close();
$conn->close();
?>
