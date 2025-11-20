<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON"); //
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <title>Registro de Usuario</title>
   <!-- <link rel="stylesheet" type="text/css" href="styles.css">-->
    <style>

        /* ======== GENERAL ======== */
body {
  font-family: 'Segoe UI', sans-serif;
  background: radial-gradient(circle at center, #0d1c3b, #1e2a78);
  color: #e0e0e0;
  margin: 0;
  padding: 0;
}

/* ======== HEADER ======== */
.header {
  background: linear-gradient(145deg, #1e2a78, #3f51b5);
  padding: 20px 0;
  text-align: center;
  width: 100%;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.logo {
  font-size: 30px;
  font-weight: bold;
  text-transform: uppercase;
  color: #c76f39;
  text-shadow:
    0 0 6px #c76f39,
    0 0 12px #c76f39;
}

.navbar ul {
  list-style: none;
  padding: 0;
  margin-top: 10px;
}

.nav-item {
  display: inline-block;
  margin: 0 10px;
}

.nav-link {
  color: #fff;
  text-decoration: none;
  font-size: 18px;
  transition: color 0.3s;
}

.nav-link:hover {
  color: #ffcc00;
}

/* ======== CONTENEDOR ======== */
.container {
  max-width: 600px;
  margin: 60px auto;
  padding: 30px;
  background-color: rgba(30, 30, 47, 0.96);
  border-radius: 15px;
  box-shadow:
    0 -40px 40px -20px rgba(199, 111, 57, 0.25),
    0 40px 40px -20px rgba(30, 144, 255, 0.25);
  backdrop-filter: blur(6px);
  animation: fadeIn 1s ease-out;
}

.container h2 {
  text-align: center;
  margin-bottom: 25px;
  font-size: 28px;
  color: #c76f39;
  text-shadow:
    0 0 6px #c76f39,
    0 0 12px #c76f39;
}

/* ======== FORMULARIO ======== */
label {
  display: block;
  margin-top: 15px;
  margin-bottom: 5px;
  color: #dddddd;
  font-weight: 500;
}

input[type="text"],
input[type="password"],
select {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border: none;
  border-radius: 8px;
  background-color: #2a2a3f;
  color: #fff;
  font-size: 16px;
  box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.3);
  transition: 0.3s ease;
}

input:focus,
select:focus {
  outline: none;
  box-shadow: 0 0 8px #c76f39;
}

/* ======== BOTÓN ======== */
input[type="submit"] {
  background-color: #c76f39;
  color: #fff;
  padding: 12px 25px;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  box-shadow:
    0 0 10px #c76f39,
    0 0 20px #c76f39;
  transition: background-color 0.3s ease, transform 0.2s;
}

input[type="submit"]:hover {
  background-color: #a85522;
  transform: scale(1.05);
}

input[type="submit"]:focus {
  outline: none;
  box-shadow:
    0 0 12px #c76f39,
    0 0 24px #c76f39;
}

/* ======== ERRORES ======== */
.error-message {
  margin-top: 15px;
  color: #ff5252;
  font-weight: bold;
  text-align: center;
}

/* ======== ANIMACIONES ======== */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

    </style>
</head>
<body>
   
    <header class="header">
        <div class="logo">Registro de usuario</div>
        <nav class="navbar">
            <ul>
                <li class="nav-item"><a href='../index.php' class="nav-link">Volver</a></li>
              </ul>
        </nav>
    </header>

<div class="container">
    <h2>Registro de Usuario</h2>
    <form action="Registrar_usuario.php" method="post">
        <label for="username">Nombre de Usuario:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="Rol">Rol:</label>
<select id="Rol" name="Rol" required>
  <option value="">Selecciona Rol</option>
  <option value="Admin">Sistemas</option>
  <option value="Gerente">Gerente</option> <!-- NUEVO -->
  <option value="Evento">Evento</option>
  <option value="Vendedor">Vendedor</option>
</select>
<br><br>
        
    
        
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        
       
        
        <label for="confirm_password">Confirmar Contraseña:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        
      
        
        <input type="submit" value="Registrar">
        
        <!-- Aquí se mostrarán los mensajes de error -->
        <div class="error-message">
            <?php if (isset($error_message)) echo $error_message; ?>
        </div>
    </form>
</div>

</body>
</html>
