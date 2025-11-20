<?php
// Conectar a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitizar y validar
    $evento         = isset($_POST['Evento']) ? (int)$_POST['Evento'] : 0;
    $actividad      = trim($_POST['Actividad'] ?? '');
    $descripcion    = trim($_POST['Descripcion'] ?? '');
    $capacidad      = isset($_POST['capacidad']) ? (int)$_POST['capacidad'] : 0;
    $puntosDefault  = isset($_POST['Puntos_Default']) ? (int)$_POST['Puntos_Default'] : 0;

    if (!$evento || $actividad === '' || !$capacidad) {
        // Cierra y muestra error simple (puedes mejorar UX con un redirect + msg)
        $conn->close();
        die("Datos incompletos.");
    }
$exclusiva = isset($_POST['Exclusiva']) ? 1 : 0;

// Inserción segura (agrega la columna Exclusiva)
$sql = "INSERT INTO actividades (ID_Evento, Actividad, Descripcion, capacidad, Puntos_Default, Exclusiva)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->close();
    die("Error al preparar la consulta.");
}

// Nota: cambia el tipo de bind a 6 params: i s s i i i
$stmt->bind_param("issiii", $evento, $actividad, $descripcion, $capacidad, $puntosDefault, $exclusiva);

if ($stmt->execute()) {
    header("Location: Evento_inicio.php?id=" . $evento . "&mensaje=exito");
    exit();
} else {
    echo "Error al guardar la actividad: " . $conn->error;
}
$stmt->close();
}

$conn->close();
