<?php
// Establecer la conexión a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php";

// Función para obtener el ID de la agenda para un evento específico
function obtenerIdAgenda($conn, $idEvento) {
    $sql = "SELECT ID FROM Agenda WHERE ID_Evento = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idEvento);
    $stmt->execute();
    $stmt->bind_result($idAgenda);
    $stmt->fetch();
    $stmt->close();
    
    return $idAgenda;
}

// Función para extraer datos del código QR
function extraerDatosQR($qrCode) {
    $datos = [];
    $partes = explode('Ñ', $qrCode);

    foreach ($partes as $parte) {
        $claveValor = explode(': ', $parte);
        if (count($claveValor) == 2) {
            $datos[trim($claveValor[0])] = trim($claveValor[1]);
        }
    }
    
    return $datos;
}

// Obtener la lista de códigos QR y los ID de los participantes
$sqlObtenerQRs = "SELECT ID, QR_Code FROM participante WHERE QR_Code IS NOT NULL";
$resultadoQRs = $conn->query($sqlObtenerQRs);

if ($resultadoQRs->num_rows > 0) {
    while ($fila = $resultadoQRs->fetch_assoc()) {
        $idParticipante = $fila['ID'];
        $qrCode = $fila['QR_Code'];

        // Extraer datos del código QR
        $datosQR = extraerDatosQR($qrCode);

        if (isset($datosQR['ID']) && isset($datosQR['Evento'])) {
            $idEvento = intval($datosQR['Evento']);
            $idAgenda = obtenerIdAgenda($conn, $idEvento);

            if ($idAgenda) {
                // Insertar en la tabla Clase
                $sqlInsertarClase = "INSERT INTO Clase (ID_Agenda, ID_Participante) VALUES (?, ?)";
                $stmtInsertarClase = $conn->prepare($sqlInsertarClase);
                $stmtInsertarClase->bind_param("ii", $idAgenda, $idParticipante);

                if ($stmtInsertarClase->execute()) {
                    echo "Participante con ID $idParticipante agregado exitosamente a la clase con ID_Agenda $idAgenda.<br>";
                } else {
                    echo "Error al agregar el participante con ID $idParticipante: " . $conn->error . "<br>";
                }

                $stmtInsertarClase->close();
            } else {
                echo "No se encontró agenda para el evento con ID $idEvento para el participante con ID $idParticipante.<br>";
            }
        } else {
            echo "Código QR inválido para el participante con ID $idParticipante.<br>";
        }
    }
} else {
    echo "No se encontraron códigos QR para procesar.";
}

$conn->close();
?>
