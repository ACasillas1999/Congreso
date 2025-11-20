<?php
// Uso: generar_Horario.php?id=99&format=png

session_name("CON");
session_start();

require_once __DIR__ . "/Conexiones/Conexion.php";
$conn->set_charset('utf8mb4');
$conn->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// ------------ Config ------------
$ID_PARTICIPANTE = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$FORMAT = isset($_GET['format']) ? strtolower($_GET['format']) : 'png'; // png|pdf

// Machotes (vertical y, si tienes, horizontal). Si no hay horizontal, se rotará el vertical.
$TEMPLATE_PORTRAIT = __DIR__ . "/Machote/Machote_Horario.png";
$TEMPLATE_LAND     = __DIR__ . "/Machote/Machote_Horario_L.png"; // opcional

$FONT = __DIR__ . "/assets/Roboto_Condensed-Black.ttf";

// Tamaños (más grandes)
$CFG = [
  'title' => ['nombre'=>32, 'evento'=>20, 'ubic'=>18],
  'col'   => ['header'=>18, 'cell'=>18, 'row_h_min'=>36, 'pad_y'=>18, 'line_space'=>6],
];

// ---------------------------------
if ($ID_PARTICIPANTE <= 0) { http_response_code(400); exit("Falta ?id="); }

// Consulta base
$sql = "SELECT a.Salon, a.Fecha, a.Horario, a.Actividad, e.name_evento, e.ubicacion, p.Nombre
        FROM clase c
        JOIN agenda a ON c.ID_Agenda = a.ID
        JOIN evento e ON a.ID_Evento = e.ID
        JOIN participante p ON c.ID_Participante = p.ID
        WHERE c.ID_Participante = ?
        ORDER BY a.Fecha ASC, a.Horario ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ID_PARTICIPANTE);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$nombre = $evento = $ubicacion = "";
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
    $nombre    = $r['Nombre'];
    $evento    = $r['name_evento'];
    $ubicacion = $r['ubicacion'];
}
$stmt->close();

if (empty($rows)) { http_response_code(404); exit("Sin horario para este participante."); }

// ======== Agrupar por día (columna por día) ========
$byDay = [];
foreach ($rows as $r) {
    $d = date('Y-m-d', strtotime($r['Fecha']));
    $byDay[$d][] = $r;
}
$days = array_keys($byDay);
$colsN = max(1, count($days));

// -------------- Herramientas comunes --------------
function ttf_width($size,$font,$text){
    $b = imagettfbbox($size,0,$font,$text);
    return $b[2]-$b[0];
}
function wrap_text($text,$size,$font,$maxW){
    $out=[]; foreach (preg_split('/\R/u', trim((string)$text)) as $p){
        $ws=preg_split('/\s+/u',trim($p)); $line='';
        foreach($ws as $w){
            $t=$line===''?$w:"$line $w";
            if (ttf_width($size,$font,$t) <= $maxW) $line=$t;
            else { if($line!=='') $out[]=$line; $line=$w; }
        }
        if($line!=='') $out[]=$line;
    } return $out;
}

// ===================================================
// ====================   PNG   ======================
// ===================================================
if ($FORMAT === 'png') {
    $PORTRAIT = realpath($TEMPLATE_PORTRAIT);
    if (!$PORTRAIT || !is_file($PORTRAIT)) { http_response_code(500); exit("❌ Machote vertical no existe"); }

    // Intento 1: retrato; si no cabe, usar paisaje
    $try_orients = ['portrait','land'];

    foreach ($try_orients as $ORIENT) {
        // Cargar fondo base
        if ($ORIENT==='land' && is_file($TEMPLATE_LAND)) {
            $bgPath = $TEMPLATE_LAND;
            $bg = @imagecreatefromstring(file_get_contents($bgPath));
        } else {
            $bgPath = $PORTRAIT;
            $bg = @imagecreatefromstring(file_get_contents($bgPath));
            if ($ORIENT==='land' && !is_file($TEMPLATE_LAND)) {
                // girar el vertical
                $rot = imagerotate($bg, 90, 0);
                imagedestroy($bg);
                $bg = $rot;
            }
        }
        if (!$bg instanceof GdImage) { http_response_code(500); exit("❌ No se pudo cargar el machote"); }

        $W = imagesx($bg);  // base
        $H = imagesy($bg);

        // Hi-DPI
        $SCALE = 3;
        $W2=$W*$SCALE; $H2=$H*$SCALE;
        $im = imagecreatetruecolor($W2,$H2);
        imagealphablending($im,true); imagesavealpha($im,true);
        imagecopyresampled($im,$bg,0,0,0,0,$W2,$H2,$W,$H);
        imagedestroy($bg);

        // Colores
        $white   = imagecolorallocate($im,255,255,255);
        $lineCol = $white;
        $stroke  = imagecolorallocatealpha($im,0,0,0,80);

        // Helpers
        $sx = fn($v)=> (int)round($v * $SCALE);
        $fs = fn($pt)=> max(10, (int)round($pt * $SCALE));

        $strokeText = function($size,$x,$y,$text) use($im,$FONT,$white,$stroke){
            foreach([[-1,0],[1,0],[0,-1],[0,1],[-1,-1],[1,-1],[-1,1],[1,1]] as [$dx,$dy]){
                imagettftext($im,$size,0,$x+$dx,$y+$dy,$stroke,$FONT,$text);
            }
            imagettftext($im,$size,0,$x,$y,$white,$FONT,$text);
        };

        // Márgenes según orientación
        if ($ORIENT==='portrait') {
            $M = ['l'=> $sx(60), 't'=> $sx(120), 'r'=> $sx(60), 'b'=> $sx(40)];
        } else {
            $M = ['l'=> $sx(80), 't'=> $sx(80),  'r'=> $sx(80), 'b'=> $sx(60)];
        }

        // Títulos (más grandes)
        $strokeText($fs($CFG['title']['nombre']), $M['l'], $M['t'], $nombre);
        $strokeText($fs($CFG['title']['evento']), $M['l'], $M['t'] + $sx(34), $evento);
        $strokeText($fs($CFG['title']['ubic']),   $M['l'], $M['t'] + $sx(60), $ubicacion);

        // Área de tabla
        $y0 = $M['t'] + $sx(92);
        $x0 = $M['l'];
        $usableW = $W2 - $M['l'] - $M['r'];
        $usableH = $H2 - $y0   - $M['b'];

        // Anchos de columnas por día
        $gap = $sx(18);
        $colW = (int)floor(($usableW - ($gap*($colsN-1))) / $colsN);

        // Tipos de letra celdas
        $fHeader = $fs($CFG['col']['header']);
        $fCell   = $fs($CFG['col']['cell']);
        $rowHmin = $fs($CFG['col']['row_h_min']);
        $padY    = $sx($CFG['col']['pad_y']);
        $lSpace  = $sx($CFG['col']['line_space']);
        $thick   = max(1,(int)round(2*$SCALE));

        // ---------- MEDICIÓN para saber si cabe ----------
        $maxColHeight = 0;
        foreach ($days as $d) {
            $h = 0;
            $h += $fHeader + $sx(16); // alto encabezado del día
            foreach ($byDay[$d] as $r) {
                $lines = wrap_text($r['Actividad'], $fCell, $FONT, $colW - $sx(12));
                $hRow = max($rowHmin, count($lines)*($fCell+$lSpace) - $lSpace) + ($padY*2) + $sx(8); // título + tiempos/salón
                $h += $hRow;
            }
            $maxColHeight = max($maxColHeight, $h);
        }

        if ($maxColHeight > $usableH) {
            // No cabe: intentamos siguiente orientación
            imagedestroy($im);
            if ($ORIENT==='land') {
                // ya es el último intento: reducimos un poco tipografías para salvar
                $CFG['col']['header'] = max(14, $CFG['col']['header']-2);
                $CFG['col']['cell']   = max(14, $CFG['col']['cell']-2);
                // volver a iterar desde land con fuentes reducidas
                $try_orients = ['land']; 
            }
            continue;
        }

        // ---------- Dibujo real ----------
        imagesetthickness($im,$thick);
        $x = $x0;
        foreach ($days as $idx=>$d) {
            // Encabezado de día
            $strokeText($fHeader, $x, $y0, date('Y-m-d', strtotime($d)));

            // Línea bajo encabezado
            imageline($im, $x, $y0 + $sx(10), $x + $colW, $y0 + $sx(10), $lineCol);

            // Filas del día
            $y = $y0 + $sx(24);
            foreach ($byDay[$d] as $r) {
                // Primera línea: Hora  •  Salón
                $top = $y + $padY;
                $primera = date('H:i', strtotime($r['Horario'])) . " • " . $r['Salon'];
                $strokeText($fCell, $x + $sx(6), $top, $primera);

                // Actividad (envuelta)
                $lines = wrap_text($r['Actividad'], $fCell, $FONT, $colW - $sx(12));
                $ly = $top + $sx(20);
                foreach ($lines as $ln) {
                    $strokeText($fCell, $x + $sx(6), $ly, $ln);
                    $ly += ($fCell + $lSpace);
                }
                $hAct = max($rowHmin, ($ly - ($top + $sx(20))) - $lSpace);
                $yEnd = $top + $sx(20) + $hAct + $padY;

                // Línea separadora
                imageline($im, $x, $yEnd + $sx(6), $x + $colW, $yEnd + $sx(6), $lineCol);
                $y = $yEnd + $sx(10);
            }

            // Separador vertical entre columnas
            if ($idx < $colsN-1) {
                imageline($im, $x + $colW + (int)($gap/2), $y0, $x + $colW + (int)($gap/2), $y0 + $usableH, $lineCol);
            }
            $x += $colW + $gap;
        }
        imagesetthickness($im,1);

        // Reducir a tamaño base
        $out = imagecreatetruecolor($W,$H);
        imagealphablending($out,true); imagesavealpha($out,true);
        imagecopyresampled($out,$im,0,0,0,0,$W,$H,$W2,$H2);
        imagedestroy($im);

        if (ob_get_length()) ob_end_clean();
        header("Content-Type: image/png");
        header('Content-Disposition: inline; filename="horario_'.$ID_PARTICIPANTE.'.png"');
        imagepng($out);
        imagedestroy($out);
        exit;
    }

    // Si llegó aquí, algo falló
    http_response_code(500); exit("No se pudo maquetar la imagen.");
}

// ===================================================
// ====================   PDF   ======================
// ===================================================
require_once __DIR__ . "/fpdf/fpdf.php";

class PDF_BG extends FPDF {
    public $bg;
    function Header() {
        if ($this->bg) $this->Image($this->bg, 0, 0, $this->w, $this->h);
    }
}
function mm($px){ return $px * 0.264583; } // 96dpi aprox.

// Medición simple para decidir orientación
$colsN = count($byDay);
$needLand = ($colsN >= 3); // 3+ días suele requerir horizontal

$pdf = new PDF_BG($needLand ? 'L' : 'P', 'mm', 'A4');
$pdf->bg = $needLand && file_exists($TEMPLATE_LAND) ? $TEMPLATE_LAND : $TEMPLATE_PORTRAIT;
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);

$pdf->SetTextColor(20,20,20);

// Títulos más grandes
$pdf->SetFont('Arial','',16);
$pdf->SetXY(20, 18);
$pdf->SetFontSize( mm($CFG['title']['nombre'])/0.3527 );
$pdf->Cell(0,8, utf8_decode($nombre), 0,1);

$pdf->SetX(20);
$pdf->SetFontSize( mm($CFG['title']['evento'])/0.3527 );
$pdf->Cell(0,8, utf8_decode($evento), 0,1);

$pdf->SetX(20);
$pdf->SetFontSize( mm($CFG['title']['ubic'])/0.3527 );
$pdf->Cell(0,8, utf8_decode($ubicacion), 0,1);

// Área y columnas
$L = 20; $T = 40; $R = 20; $B = 16;
$W = $pdf->GetPageWidth();  $H = $pdf->GetPageHeight();
$usableW = $W - $L - $R;    $usableH = $H - $T - $B;

$gap = 6;
$colW = ($usableW - ($gap*($colsN-1))) / $colsN;

$pdf->SetFont('Arial','B',12);
$x = $L; $yTop = $T;

foreach ($byDay as $d=>$list) {
    $pdf->SetXY($x, $yTop);
    $pdf->Cell($colW, 6, utf8_decode(date('Y-m-d', strtotime($d))), 0, 1, 'L');
    // Línea
    $pdf->Line($x, $pdf->GetY()+1, $x + $colW, $pdf->GetY()+1);

    // Celdas
    $pdf->SetFont('Arial','',11);
    $y = $pdf->GetY() + 3;
    foreach ($list as $r) {
        $pdf->SetXY($x, $y);
        $head = date('H:i', strtotime($r['Horario'])) . " • " . $r['Salon'];
        $pdf->Cell($colW, 5, utf8_decode($head), 0, 1, 'L');

        // Actividad
        $xBefore = $x;
        $yBefore = $pdf->GetY();
        $pdf->SetXY($x, $yBefore);
        $pdf->MultiCell($colW, 5, utf8_decode($r['Actividad']), 0, 'L');
        $yAfter = $pdf->GetY();

        // Separador
        $pdf->Line($x, $yAfter+1, $x + $colW, $yAfter+1);
        $y = $yAfter + 3;
    }

    $x += $colW + $gap;
}

$pdf->Output('I', "horario_{$ID_PARTICIPANTE}.pdf");
?>