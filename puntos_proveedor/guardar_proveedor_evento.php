<?php
include "../Conexiones/Conexion.php";

ob_start();
include __DIR__ . "/../header_css.php";
$providerThemeCss = ob_get_clean();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Resultado</title>
    {$providerThemeCss}
    <style>
        body {
            background: radial-gradient(circle at center, var(--theme-primary, #f0f4f8), var(--theme-primary-dark, #d8e1ee));
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: var(--theme-text, #111);
        }

        .card {
            background: var(--theme-surface-strong, white);
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 100%;
            box-shadow: var(--theme-shadow, 0 10px 25px rgba(0,0,0,0.1));
        }

        .card h2 {
            color: var(--theme-title, #2575fc);
            margin-bottom: 20px;
        }

        .card p {
            font-size: 16px;
            color: var(--theme-text-soft, #333);
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: var(--theme-primary, #2575fc);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
        }

        .btn:hover {
            background: var(--theme-primary-dark, #1a5dd8);
        }
    </style>
</head>
<body>
<div class='card'>
";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_evento = $conn->real_escape_string($_POST['ID_Evento']);
    $nombre_proveedor = $conn->real_escape_string($_POST['NombreProveedor']);
    $puntos = intval($_POST['Puntos']);

    $sql_insert = "INSERT INTO proveedor_evento (ID_Evento, NombreProveedor, Puntos) 
                   VALUES ('$id_evento', '$nombre_proveedor', '$puntos')";

    if ($conn->query($sql_insert)) {
        $usuario = $conn->real_escape_string($nombre_proveedor);
        $password_clara = substr(md5(uniqid()), 0, 8);
        $password_hash = password_hash($password_clara, PASSWORD_DEFAULT);

        $check_user = $conn->query("SELECT * FROM usuarios WHERE username = '$usuario'");
        if ($check_user->num_rows == 0) {
            $sql_user = "INSERT INTO usuarios (username, password, password_visible, Rol) 
             VALUES ('$usuario', '$password_hash', '$password_clara', 'proveedor')";

            if ($conn->query($sql_user)) {
                echo "<h2>✅ Proveedor y usuario creados correctamente.</h2>";
                echo "<p><strong>Usuario:</strong> $usuario</p>";
                echo "<p><strong>Contraseña temporal:</strong> $password_clara</p>";
                echo "<button class='btn' onclick=\"copiarCredenciales()\">📋 Copiar credenciales</button>";
            } else {
                echo "<h2>❌ Error al crear el usuario</h2><p>" . $conn->error . "</p>";
            }
        } else {
            echo "<h2>⚠️ Usuario ya existente</h2>";
            echo "<p>Se agregó el proveedor pero el usuario <strong>$usuario</strong> ya existía.</p>";
        }
    } else {
        echo "<h2>❌ Error al guardar el proveedor</h2><p>" . $conn->error . "</p>";
    }
}

echo "
    <br><a href='agregar_proveedor_evento.php' class='btn'>⬅️ Volver</a>
</div>

<script>
function copiarCredenciales() {
    const texto = 'Usuario: $usuario\\nContraseña: $password_clara';
    navigator.clipboard.writeText(texto).then(() => {
        alert('✅ Credenciales copiadas al portapapeles');
    });
}
</script>

</body>
</html>";
?>
