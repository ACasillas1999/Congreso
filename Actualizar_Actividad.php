<?php

// Iniciar la sesión de forma segura

ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {
 
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}
// Conectar a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Obtener el ID de la actividad desde la URL
$id_actividad = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Inicializar variables para los campos de la actividad
$id_evento = $actividad = $descripcion = '';

// Si se recibió un ID válido, recuperar la información de la actividad
if ($id_actividad > 0) {
    $sql_actividad = "SELECT * FROM actividades WHERE ID = $id_actividad";
    $result_actividad = $conn->query($sql_actividad);

    if ($result_actividad->num_rows > 0) {
        $row_actividad = $result_actividad->fetch_assoc();
        $id_evento = $row_actividad['ID_Evento'];
        $actividad = $row_actividad['Actividad'];
        $descripcion = $row_actividad['Descripcion'];
        $capacidad = $row_actividad['capacidad'];
    }
}

// Cerrar conexión
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles3.css">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <title>Actualizar Actividad</title>
</head>
<body>
    
<header class="header">
    <div class="logo">Actualizar Actividad</div>
    <nav class="navbar">
        <ul>
        <li class="nav-item"><a href='Actividades.php?id=<?php echo $id_actividad; ?>' class="nav-link">Volver</a></li>
        </ul>
    </nav>
</header>

<form action="Funcion_Actualizar_Actividad.php" method="POST">
    <input type="hidden" name="id" value="<?php echo $id_actividad; ?>">

    <label for="id_evento">ID del Evento:</label>
    <input type="text" id="id_evento" name="id_evento" value="<?php echo htmlspecialchars($id_evento); ?>" readonly><br><br>

    <label for="actividad">Actividad:</label>
    <input type="text" id="actividad" name="actividad" value="<?php echo htmlspecialchars($actividad); ?>" required><br><br>

    <label for="descripcion">Descripción:</label>
    <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($descripcion); ?></textarea><br><br>

    <label for="capacidad">Capacidad:</label>
    <input type="number" id="capacidad" name="capacidad" value="<?php echo htmlspecialchars($capacidad); ?>" required><br><br>


    <input type="submit" value="Actualizar Actividad">
</form>

</body>
</html>
