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

$id_actividad = isset($_POST['id']) ? intval($_POST['id']) : 0;
$id_evento    = isset($_POST['id_evento']) ? intval($_POST['id_evento']) : 0;
$actividad     = trim($_POST['actividad'] ?? '');
$descripcion   = trim($_POST['descripcion'] ?? '');
$capacidad     = intval($_POST['capacidad'] ?? 0);
$puntos        = intval($_POST['puntos'] ?? 0);
$redirect_to   = $_POST['redirect_to'] ?? ("Actividades.php?id=" . $id_actividad);

if ($id_actividad > 0 && !empty($actividad)) {
    // 1. Obtener nombre antiguo
    $stmt_old = $conn->prepare("SELECT Actividad FROM actividades WHERE ID = ?");
    $stmt_old->bind_param("i", $id_actividad);
    $stmt_old->execute();
    $res_old = $stmt_old->get_result();
    $row_old = $res_old->fetch_assoc();
    $old_activity = $row_old['Actividad'] ?? '';
    $stmt_old->close();

    if ($old_activity) {
        // 2. Actualizar actividades
        $stmt_upd = $conn->prepare("UPDATE actividades SET Actividad=?, Descripcion=?, capacidad=?, Puntos_Default=? WHERE ID=?");
        $stmt_upd->bind_param("ssiii", $actividad, $descripcion, $capacidad, $puntos, $id_actividad);
        
        if ($stmt_upd->execute()) {
            // 3. Actualizar agenda si aplica
            $stmt_ag = $conn->prepare("UPDATE agenda SET Actividad = ? WHERE Actividad = ? AND ID_Evento = ?");
            $stmt_ag->bind_param("ssi", $actividad, $old_activity, $id_evento);
            $stmt_ag->execute();
            $stmt_ag->close();

            header("Location: " . $redirect_to);
            exit();
        } else {
            echo "Error al actualizar: " . $conn->error;
        }
        $stmt_upd->close();
    } else {
        echo "Actividad no encontrada.";
    }
} else {
    echo "Faltan datos requeridos.";
}

$conn->close();
?>
