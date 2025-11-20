<?php
// Iniciar la sesión de forma segura
ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}

// Conexión a la base de datos
require_once __DIR__ . "/../Conexiones/Conexion.php";

// Obtener el ID del evento y la plantilla seleccionada del formulario
$id_evento = isset($_POST['Evento']) ? intval($_POST['Evento']) : 0;
$template = isset($_POST['Template']) ? $_POST['Template'] : '';

if ($id_evento > 0 && !empty($template)) {
    // Consulta SQL para obtener el teléfono y name_evento de los participantes
    $sql = "SELECT 
                P.ID, 
                E.name_evento, 
                P.Sucursal, 
                P.Vendedor, 
                P.Nombre, 
                P.Proveedor,
                P.Telefono,
                P.QR_Code  
            FROM 
                participante P
            JOIN 
                evento E
            ON 
                P.ID_Evento = E.ID
            WHERE 
                P.ID_Evento = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // TOKEN QUE NOS DA FACEBOOK
        $token = 'EAAGacaATjwEBOZBgqhohcVk1ZBGEAbiTl7i86qESvSPjdllaomwzIG7LmOOvyTFpzyIlXX6dtTYTVTLLuw6SjaLoh2rec07I8qu1nGNYSVZAmQTGNa3QCQjujTqfd7QuLLwFNQllnX2z1V7JvToDhEi5KVqUWXHSqgSETvGyU7S2SN2fpXW0NpQaRI48pwZAgGS7A1BQMjLl5ZBjy';
        // URL A DONDE SE MANDARA EL MENSAJE
        $url = 'https://graph.facebook.com/v20.0/335894526282507/messages';

        $errors = [];
        while ($row = $result->fetch_assoc()) {
            $telefono = '52' . $row['Telefono'];
            $nombre_evento = $row['name_evento'];

            // CONFIGURACION DEL MENSAJE
            $mensaje = ''
        . '{'
        . '"messaging_product": "whatsapp", '
        . '"to": "'.$telefono.'", '
    
    
      //  . '"type": "text", '
      // . '"text": '
    
        . '"type": "template", '
        . '"template": '
        . '{'
   
        . '     "name": "'.$template.'",'
        . '     "language":{ "code": "en_US" } '
        . '} '
    
    
        . '}';

            // DECLARAMOS LAS CABECERAS
            $header = array("Authorization: Bearer " . $token, "Content-Type: application/json",);// INICIAMOS EL CURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $mensaje);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            // OBTENEMOS LA RESPUESTA DEL ENVIO DE INFORMACION
            $response = json_decode(curl_exec($curl), true);
            // OBTENEMOS EL CODIGO DE LA RESPUESTA
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            // CERRAMOS EL CURL
            curl_close($curl);

            echo '<script>window.location.href = "/Congreso/Participantes.php?id=' . $id_evento . '";</script>';

            if ($status_code !== 200) {
                $errors[] = "Error al enviar mensaje a $telefono: " . json_encode($response);
            }
        }

        if (empty($errors)) {
            echo json_encode(['status' => 'success', 'message' => 'Mensajes enviados correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Algunos mensajes no se pudieron enviar.', 'errors' => $errors]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => "No se encontraron participantes para el evento."]);
    }

    // Cerrar la consulta
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => "ID del evento o plantilla no válidos."]);
}

// Cerrar la conexión
$conn->close();
?>
