<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 //Incluir la biblioteca QR Code
require_once 'phpqrcode/qrlib.php';

require_once __DIR__ . "/Conexiones/Conexion.php";
require_once __DIR__ . "/config.php";

// Verificar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $evento = $conn->real_escape_string($_POST['Evento']);
    $sucursal = $conn->real_escape_string($_POST['sucursal']);
    $Vendedor = $conn->real_escape_string($_POST['Vendedor']);
    $Nombre = $conn->real_escape_string($_POST['Nombre']);
    $Proveedor = $conn->real_escape_string($_POST['Proveedor']);
    $Telefono = $conn->real_escape_string($_POST['Telefono']);
$RFC = $conn->real_escape_string($_POST['rfc']);
$Puesto = $conn->real_escape_string($_POST['puesto']);

    // Verificar si el número de teléfono ya existe en la base de datos
     $sql_check = "SELECT ID FROM participante WHERE Telefono = '$Telefono' AND ID_Evento = '$evento'";
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows > 0) {
        // Si el número de teléfono ya existe, mostrar una alerta y recargar la página
        echo "<script>
            alert('Error: El número de teléfono ya está registrado para este evento.');
            window.location.href = 'Agregar_Participante.php?id=$evento';
          </script>";
    } else {
        // Crear la consulta SQL para insertar los datos
     $sql = "INSERT INTO participante (ID_Evento, Sucursal, Vendedor, Nombre, RFC, Puesto, Proveedor, Telefono) 
        VALUES ('$evento', '$sucursal', '$Vendedor', '$Nombre', '$RFC', '$Puesto', '$Proveedor', '$Telefono')";

        if ($conn->query($sql) === TRUE) {
            // Obtener el ID del último participante insertado
          $last_id = $conn->insert_id;

// 🔹 BLOQUE PARA GUARDAR ACTIVIDADES (con solapes + exclusivas por rol)
$actividades = $_POST['actividades'] ?? []; // IDs de agenda seleccionados (checkboxes)
if (!empty($actividades)) {
    $acts_ids = array_map('intval', $actividades);
    $ids_csv  = implode(',', $acts_ids);

    // Slots seleccionados (para validar solapes)
    $sqlSlots = "
        SELECT ID, Fecha, Horario
        FROM agenda
        WHERE ID IN ($ids_csv) AND ID_Evento = $evento AND Actividad <> 'Vacio'
    ";
    $rsSlots = $conn->query($sqlSlots);
    $slots = [];
    while ($r = $rsSlots->fetch_assoc()) {
        if (preg_match('/^(\d{2}:\d{2})(?::\d{2})?-(\d{2}:\d{2})(?::\d{2})?$/', $r['Horario'], $m)) {
            $slots[] = [
                'id'    => (int)$r['ID'],
                'fecha' => $r['Fecha'],
                'ini'   => (int)substr($m[1],0,2) * 60 + (int)substr($m[1],3,2),
                'fin'   => (int)substr($m[2],0,2) * 60 + (int)substr($m[2],3,2),
            ];
        } else if (preg_match('/^(\d{2}):(\d{2})/', $r['Horario'], $mx)) {
            $ini = (int)$mx[1]*60 + (int)$mx[2];
            $slots[] = ['id'=>(int)$r['ID'],'fecha'=>$r['Fecha'],'ini'=>$ini,'fin'=>$ini+60];
        }
    }

    // Valida solapes
    for ($i=0; $i<count($slots); $i++) {
        for ($j=$i+1; $j<count($slots); $j++) {
            if ($slots[$i]['fecha'] === $slots[$j]['fecha']) {
                $solapa = ($slots[$i]['ini'] < $slots[$j]['fin']) && ($slots[$j]['ini'] < $slots[$i]['fin']);
                if ($solapa) {
                    echo "<script>alert('Las actividades seleccionadas se solapan. Corrige tu selección.');history.back();</script>";
                    exit;
                }
            }
        }
    }

    // ✅ Bloqueo por rol para EXCLUSIVAS (Vendedor no puede)
    $rol = $_SESSION['Rol'] ?? '';
    $chkEx = $conn->prepare("
        SELECT COALESCE(a.Exclusiva,0) AS ex
        FROM agenda ag
        LEFT JOIN actividades a
          ON a.ID_Evento = ag.ID_Evento AND a.Actividad = ag.Actividad
        WHERE ag.ID = ?
        LIMIT 1
    ");

    // Inserción de inscripciones
    $ins = $conn->prepare("INSERT INTO clase (ID_Agenda, ID_Participante, Tipo_Inscripcion) VALUES (?, ?, 0)");
    foreach ($acts_ids as $id_agenda) {
        // Checar exclusividad
        $chkEx->bind_param("i", $id_agenda);
        $chkEx->execute();
        $ex = (int)($chkEx->get_result()->fetch_column() ?? 0);

        if ($rol === 'Vendedor' && $ex === 1) {
            echo "<script>alert('Esta actividad es exclusiva. No tienes permisos para agendar.');history.back();</script>";
            exit;
        }

        // Evita duplicado y guarda
        $dup = $conn->prepare("SELECT 1 FROM clase WHERE ID_Agenda=? AND ID_Participante=?");
        $dup->bind_param("ii", $id_agenda, $last_id);
        $dup->execute();
        if ($dup->get_result()->num_rows == 0) {
            $ins->bind_param("ii", $id_agenda, $last_id);
            $ins->execute();
        }
    }
}
// 🔹 FIN BLOQUE

// 🔹 FIN BLOQUE PARA GUARDAR ACTIVIDADES

// 🔹 GENERAR HORARIO (solo si hubo actividades seleccionadas)
if (!empty($actividades)) {
    $host = $_SERVER['HTTP_HOST'] ?? 'congresos.grupoascencio.com.mx';
    $genUrl = "https://{$host}/Congreso/Generar_Horario.php?id={$last_id}&format=png&silent=1";

    if (function_exists('curl_init')) {
        $ch = curl_init($genUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $respHor = curl_exec($ch);
        curl_close($ch);
        // Opcional: guardar log si quieres depurar
        // @file_put_contents(__DIR__."/logs/horarios.log", date('c')." ID:$last_id RESP:$respHor\n", FILE_APPEND);
    } else {
        @file_get_contents($genUrl);
    }
}



$token = openssl_encrypt($last_id, METODO_CIFRADO, CLAVE_SECRETA, 0, VECTOR);
$token = urlencode($token);

$url_gafete_publico = "https://congresos.grupoascencio.com.mx/congreso/DescargarGafeteCifrado.php?token=$token";



$url_gafete = "https://congresos.grupoascencio.com.mx/congreso/DescargarGafete.php?id=$last_id";



            // Crear la información para el código QR
            $qrData = "ID: $last_id\nEvento: $evento\nNombre: $Nombre\nSucursal: $sucursal";

            // Especificar la ruta donde se guardará el código QR
            $qrDirectory = 'qrcodes/';
            if (!is_dir($qrDirectory)) {
                mkdir($qrDirectory, 0777, true); // Crear el directorio si no existe
            }

            $qrFilename = $qrDirectory . 'participante_' . $last_id . '.png';
            QRcode::png($qrData, $qrFilename, QR_ECLEVEL_L, 4);

            // Crear la información para el gafete
            
            //$templatePath = '/home/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/Gafetes_visitantes.jpg';
            //$fontPath = '/home/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/Font/nexa-book.ttf';
            //$outputDirectory = '/home1/gpoascen/congresos.grupoascencio.com.mx/Congreso/Machote/Generados/';



            $templatePath = TEMPLATE_GAFETE;
            $fontPath = FONT_NEXA;
            $outputDirectory = GAFETES_OUTPUT;


            if (!is_dir($outputDirectory)) {
                mkdir($outputDirectory, 0777, true); // Crear el directorio si no existe
            }
            $outputPath = $outputDirectory . '/Gafete_personalizado_' . urlencode($last_id) . '.jpg';

            // Crear el gafete
            $image = imagecreatefromjpeg($templatePath);
            $colorNegro = imagecolorallocate($image, 0, 0, 0);
          //   $colorNegro = imagecolorallocate($image, 255, 255, 255);
            $colorBlanco = imagecolorallocate($image, 255, 255, 255);

            // Definir el área de la mitad izquierda del gafete
            $areaWidth = 1000; // Ancho del área en la mitad izquierda
            $areaX = 202; // Posición X de inicio del área (ajusta según tu necesidad)

            // Obtener las dimensiones del texto para centrarlo
            $fontSize = 60; // Tamaño de la fuente
            $textBox = imagettfbbox($fontSize, 0, $fontPath, $Nombre);
            $textWidth = abs($textBox[4] - $textBox[0]);

            // Calcular la posición X para centrar el texto en la mitad izquierda
            if ($textWidth > $areaWidth) {
                // Si el texto es más ancho que el área, ajustarlo al área
                $fontSize = ($areaWidth / $textWidth) * $fontSize; 
                $textBox = imagettfbbox($fontSize, 0, $fontPath, $Nombre);
                $textWidth = abs($textBox[4] - $textBox[0]);
            }

            $x = $areaX + ($areaWidth - $textWidth) / 2;
           // $y = 1050; // Posición Y fija
            $y = 1500; // Posición Y fija
            
            // Escribir el nombre centrado en la mitad izquierda del gafete
            imagettftext($image, $fontSize, 0, $x, $y, $colorNegro, $fontPath, $Nombre);

            // Escribir el mes y año (esto se puede ajustar según tu necesidad)
            $fecha = ''; // Cambia este valor según tus necesidades
            imagettftext($image, 18, 0, 320, 750, $colorBlanco, $fontPath, $fecha);

            // Cargar la imagen del código QR y redimensionarlo
            $qrImage = imagecreatefrompng($qrFilename);
            list($qrWidth, $qrHeight) = getimagesize($qrFilename);
            $qrNewWidth = 900; // Ancho deseado
            $qrNewHeight = 900; // Alto deseado
            $qrResized = imagecreatetruecolor($qrNewWidth, $qrNewHeight);
            imagecopyresampled($qrResized, $qrImage, 0, 0, 0, 0, $qrNewWidth, $qrNewHeight, $qrWidth, $qrHeight);

            // Superponer el QR en la imagen original
            $qrX = 1755; // Posición X
            $qrY = 280; // Posición Y
            imagecopy($image, $qrResized, $qrX, $qrY, 0, 0, $qrNewWidth, $qrNewHeight);

            // Guardar el gafete
            imagejpeg($image, $outputPath, 100);

            // Limpiar memoria
            imagedestroy($image);
            imagedestroy($qrImage);
            imagedestroy($qrResized);

            // Actualizar la base de datos para guardar la ruta del gafete
            $sqlUpdate = "UPDATE participante SET QR_Code = '$qrFilename', Ruta_Gafete = '$outputPath' WHERE ID = $last_id";


// ENVIAR WHATSAPP CON CLOUD API
$telefono_con_codigo = "52" . $Telefono; // Código de país México
$nombre = $Nombre;

// DATOS DE META (REEMPLAZA ESTOS)
$access_token = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
$phone_number_id = '335894526282507';
$template_name = 'registro_vallarta_2026'; // Tu plantilla exacta

// Mensaje con botón personalizado (token como parámetro del botón)
$data = [
  "messaging_product" => "whatsapp",
  "to" => $telefono_con_codigo,
  "type" => "template",
  "template" => [
    "name" => $template_name,           // test_registro_participante
    "language" => ["code" => "en_US"],
    "components" => [
      [
        "type" => "body",
        "parameters" => [
          ["type" => "text", "text" => $nombre]
        ]
      ],
      // Botón 0: Descargar Gafete (usa {{1}} en la plantilla)
      [
        "type" => "button",
        "sub_type" => "url",
        "index" => "0",
        "parameters" => [
          ["type" => "text", "text" => $token]
        ]
      ],
      // Botón 1: Descargar Horario (usa {{1}} en la plantilla)
      [
        "type" => "button",
        "sub_type" => "url",
        "index" => "1",
        "parameters" => [
          ["type" => "text", "text" => $token]
        ]
      ]
    ]
  ]
];


$headers = [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v19.0/$phone_number_id/messages");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);




// Opcional: guardar log del envío
file_put_contents("log_whatsapp.txt", $response);



            if ($conn->query($sqlUpdate) === TRUE) {
                // Redirigir con un mensaje de éxito usando JavaScript
                echo '<script type="text/javascript">
                    alert("¡Registro Completado!");
                    window.location.href = "Participantes.php?id=' . $evento . '";
                </script>';
                exit(); // Asegúrate de detener el script después de la redirección
            }
            else {
                echo "Error al actualizar la base de datos con la ruta del gafete: " . $conn->error;
            }
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Cerrar conexión
$conn->close();
?>
