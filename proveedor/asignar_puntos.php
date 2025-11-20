<?php
session_name("CON");
session_start();
require_once __DIR__ . "/../Conexiones/Conexion.php";

/* 🔎 Siempre responde texto plano (lo muestra innerText en tu front) */
header('Content-Type: text/plain; charset=UTF-8');

/* 🧯 Muestra errores y lanza excepciones de mysqli (evita “se quedó en procesando…”) */
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || strtolower($_SESSION["Rol"]) !== "proveedor") {
    http_response_code(403);
    exit("❌ Acceso denegado");
}

$usuario = $_SESSION['username'] ?? '';

$qr_texto = $_POST['codigo'] ?? '';
if ($qr_texto === '') exit("❌ Código QR vacío.");

/* === Parseo original conservado === */
$partes = explode("Ñ", $qr_texto);
if (isset($partes[0])) {
    $raw_id = trim(str_replace("ID", "", $partes[0]));
    // ⚠️ Conservando tu truncado a 4 dígitos
    $codigo = substr(preg_replace('/\D/', '', $raw_id), 0, 4);
    if ($codigo === '') exit("❌ ID inválido extraído del QR.");
    $codigo = (int)$codigo;
} else {
    exit("❌ Código QR no contiene formato esperado.");
}

echo "Procesando ID: $codigo\n";

/* 1) Participante y evento */
if (!$codigo || !is_numeric($codigo)) {
    exit("❌ ID no es válido numéricamente: " . htmlspecialchars((string)$codigo));
}

$stmt = $conn->prepare("SELECT ID, Nombre, RFC, ID_Evento FROM participante WHERE ID = ?");
$stmt->bind_param("i", $codigo);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) exit("❌ Participante no encontrado con ID: $codigo");

$participante   = $res->fetch_assoc();
$id_participante= (int)$participante['ID'];
$nombre         = $participante['Nombre'];
$rfc            = trim($participante['RFC'] ?? '');
$id_evento      = (int)$participante['ID_Evento'];

if ($id_evento <= 0)  exit("❌ Participante sin evento válido.");
if ($rfc === '')      exit("❌ El participante no tiene RFC registrado.");

/* 2) Puntos del proveedor en este evento */
$pstmt = $conn->prepare("SELECT Puntos FROM proveedor_evento WHERE NombreProveedor = ? AND ID_Evento = ? AND Activo = 1");
$pstmt->bind_param("si", $usuario, $id_evento);
$pstmt->execute();
$puntos_res = $pstmt->get_result();

if ($puntos_res->num_rows === 0) exit("⚠️ No tienes puntos configurados para este evento.");

$row_puntos = $puntos_res->fetch_assoc();
$puntos = (int)$row_puntos['Puntos'];
if ($puntos <= 0) exit("⚠️ Configuración de puntos inválida para este evento.");

// 3 + 4. Cooldown y suma ATÓMICOS (transacción + FOR UPDATE)
$conn->begin_transaction();
try {
    // Bloquea y calcula el diff EN SQL (evita problemas de zona horaria)
    $lock = $conn->prepare("
        SELECT puntos, fecha,
               IFNULL(TIMESTAMPDIFF(SECOND, fecha, NOW()), 999999) AS diff_sec
        FROM puntos_proveedor
        WHERE id_participante=? AND usuario=? AND id_evento=?
        FOR UPDATE
    ");
    $lock->bind_param("isi", $id_participante, $usuario, $id_evento);
    $lock->execute();
    $row = $lock->get_result()->fetch_assoc();

    if ($row) {
        $diff = (int)$row['diff_sec'];  // segundos desde el último escaneo (según MySQL)
        if ($diff < 120) {
            $restan = 120 - $diff;
            $mins   = floor($restan/60);
            $secs   = $restan % 60;
            $conn->rollback();
            exit("⏳ Debes esperar 2 minutos para volver a dar puntos a este participante. Faltan {$mins}m {$secs}s.");
        }

        // Pasó el cooldown: suma y refresca fecha
        $upd = $conn->prepare("
            UPDATE puntos_proveedor
            SET puntos = puntos + ?, fecha = NOW()
            WHERE id_participante=? AND usuario=? AND id_evento=?
        ");
        $upd->bind_param("iisi", $puntos, $id_participante, $usuario, $id_evento);
        $upd->execute();
    } else {
        // Primera vez: inserta con fecha NOW()
        $ins = $conn->prepare("
            INSERT INTO puntos_proveedor (id_participante, id_evento, usuario, puntos, fecha)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $ins->bind_param("iisi", $id_participante, $id_evento, $usuario, $puntos);
        $ins->execute();
    }

    // Wallet grupal RFC+Evento (solo si pasó el cooldown)
    $ins0 = $conn->prepare("
        INSERT INTO puntos_rfc (RFC, ID_Evento, Puntos)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE Puntos = Puntos + VALUES(Puntos)
    ");
    $ins0->bind_param("sii", $rfc, $id_evento, $puntos);
    $ins0->execute();

    // (Opcional) saldo actualizado
    $sel = $conn->prepare("SELECT Puntos FROM puntos_rfc WHERE RFC=? AND ID_Evento=? LIMIT 1");
    $sel->bind_param("si", $rfc, $id_evento);
    $sel->execute();
    $saldo = (int)($sel->get_result()->fetch_assoc()['Puntos'] ?? 0);

    $conn->commit();

} catch (Throwable $e) {
    $conn->rollback();
    exit("❌ Error al registrar puntos: ".$e->getMessage());
}
echo "✅ $puntos puntos asignados a $nombre (ID $id_participante)\n";
echo "RFC: $rfc | Evento: #$id_evento\n";
echo "💼 Saldo grupal actualizado: $saldo pts.";
exit;


