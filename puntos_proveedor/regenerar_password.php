<?php
include "../Conexiones/Conexion.php";

ob_start();
include __DIR__ . "/../header_css.php";
$providerThemeCss = ob_get_clean();

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Regenerar Contraseña</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: radial-gradient(circle at center, var(--theme-primary, #f5f7fb), var(--theme-primary-dark, #dfe6f1));
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: var(--theme-text, #111);
        }

        .card {
            background: var(--theme-surface-strong, white);
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            width: 100%;
            box-shadow: var(--theme-shadow, 0 10px 25px rgba(0, 0, 0, 0.15));
            text-align: center;
        }

        .card h2 {
            color: var(--theme-title, #2575fc);
        }

        .card p {
            font-size: 16px;
            margin: 10px 0;
            color: var(--theme-text-soft, #333);
        }

        .btn {
            display: inline-block;
            margin: 10px 5px;
            padding: 10px 15px;
            background: var(--theme-primary, #2575fc);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--theme-primary-dark, #1a5dd8);
        }
    </style>
    {$providerThemeCss}
</head>
<body>";

if (isset($_GET['usuario'])) {
    $usuario = $conn->real_escape_string($_GET['usuario']);
    $nueva_password = substr(md5(uniqid()), 0, 8);
    $hash = password_hash($nueva_password, PASSWORD_DEFAULT);

    $sql = "UPDATE usuarios SET password = '$hash', password_visible = '$nueva_password' WHERE username = '$usuario'";

    if ($conn->query($sql)) {
        echo "<div class='card'>";
        echo "<h2>🔁 Contraseña actualizada</h2>";
        echo "<p><strong>Usuario:</strong> $usuario</p>";
        echo "<p><strong>Contraseña nueva:</strong> <span id='pw'>$nueva_password</span></p>";
        echo "<button class='btn' onclick='copiar()'>📋 Copiar</button>";
        echo "<button class='btn' onclick='window.print()'>🖨️ Imprimir</button>";
        echo "<a class='btn' href='../index.php'>⬅️ Volver al inicio</a>";
        echo "</div>";
    } else {
        echo "<p>Error al actualizar: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Usuario no especificado.</p>";
}

echo "
<script>
function copiar() {
    const texto = 'Usuario: $usuario\\nContraseña: ' + document.getElementById('pw').innerText;
    navigator.clipboard.writeText(texto).then(() => {
        alert('✅ Contraseña copiada al portapapeles');
    });
}
</script>
</body>
</html>";
?>
