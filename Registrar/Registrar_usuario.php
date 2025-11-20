<?php
// Registrar_usuario.php
session_name("CON");
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../Conexiones/Conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');
$confirm  = (string)($_POST['confirm_password'] ?? '');
$rol      = trim($_POST['Rol'] ?? '');

// Validaciones básicas
if ($username === '' || $password === '' || $confirm === '' || $rol === '') {
  exit('Faltan datos.');
}
if ($password !== $confirm) {
  exit('Las contraseñas no coinciden.');
}

// ✅ Whitelist de roles (incluye Gerente)
$roles_permitidos = ['Admin','Gerente','Evento','Vendedor','Proveedor'];
if (!in_array($rol, $roles_permitidos, true)) {
  exit('Rol no permitido.');
}

// ¿usuario ya existe?
$chk = $conn->prepare("SELECT 1 FROM usuarios WHERE username = ? LIMIT 1");
$chk->bind_param("s", $username);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
  exit('El usuario ya existe.');
}

// Hash de contraseña
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Inserción (dejamos password_visible en NULL)
$sql  = "INSERT INTO usuarios (username, password, Rol, password_visible) VALUES (?, ?, ?, NULL)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $hashed_password, $rol);

if ($stmt->execute()) {
  header("Location: Registro_exitoso.html");
  exit;
} else {
  exit("Error al registrar el usuario: " . $conn->error);
}
