<?php
session_name("CON");
session_start();
require_once __DIR__ . "/../Conexiones/Conexion.php";

// Verificar si se han enviado los datos de inicio de sesión
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener el nombre de usuario y la contraseña del formulario
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Preparar y ejecutar la consulta SQL utilizando una consulta preparada
    $sql = "SELECT id, password, Rol FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Verificar si se encontró un usuario con ese nombre de usuario
    if ($stmt->num_rows == 1) {
        // Vincular variables de resultado
        $stmt->bind_result($id, $stored_password, $Rol);
        $stmt->fetch();

        // Verificar si la contraseña coincide
        if (password_verify($password, $stored_password)) {
            // Las credenciales son correctas, iniciar sesión
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $username; //Almacena usuario de la sesión
            $_SESSION["Rol"] = $Rol;

            // Redirigir según el rol del usuario
              if ($Rol == 'Admin') {
                header("location: ../index.php");
            } elseif ($Rol == 'Vendedor') {
                header("location: ../Participantes.php");
            } elseif ($Rol == 'Gerente') {
                header("location: ../Participantes.php");
            } elseif ($Rol == 'Evento') {
                header("location: https://congresos.grupoascencio.com.mx/Congreso/Clase.php?id=568");
            } elseif ($Rol == 'proveedor') {
                header("location: ../proveedor/index.php"); 
            } else {
                header("location: ../index.php");
            }
            exit;
        } else {
            header("location: Error.html");
            exit;
        }
    } else {
        header("location: Error.html");
        exit;
    }
    // Cerrar la consulta preparada
    $stmt->close();
}
?>
