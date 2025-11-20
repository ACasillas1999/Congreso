<?php
// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener los datos del formulario
$nombre = isset($_POST['Nombre']) ? $_POST['Nombre'] : '';
$direccion = isset($_POST['Direccion']) ? $_POST['Direccion'] : '';
$salones = isset($_POST['Salones']) ? intval($_POST['Salones']) : 0;
$capacidadPorSalon = isset($_POST['Capacidad_por_salon']) ? intval($_POST['Capacidad_por_salon']) : 0;
$capacidadTotal = isset($_POST['Capacidad_total']) ? intval($_POST['Capacidad_total']) : 0;
//$capacidadTotalActual = isset($_POST['Capacidad_actual']) ? intval($_POST['Capacidad_actual']) : 0;

// Consulta para insertar la nueva ubicación
$sql = "INSERT INTO ubicaciones (Nombre, Direccion, Salones, Capacidad_por_salon, Capacidad_total) 
        VALUES (?, ?, ?, ?, ?)";

// Preparar la consulta
if ($stmt = $conn->prepare($sql)) {
    // Vincular los parámetros
    $stmt->bind_param("ssiii", $nombre, $direccion, $salones, $capacidadPorSalon, $capacidadTotal);

    // Ejecutar la consulta
    if ($stmt->execute()) {

        echo '<script>window.location.href = "index.php";</script>';

       // echo "Ubicación agregada exitosamente.";
    } else {
        echo "Error al agregar la ubicación: " . $stmt->error;

        
    }

    // Cerrar la consulta preparada
    $stmt->close();
} else {
    echo "Error al preparar la consulta: " . $conn->error;
}

// Cerrar la conexión
$conn->close();
?>
