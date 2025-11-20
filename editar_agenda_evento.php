<?php
require_once __DIR__ . "/Conexiones/Conexion.php";

$evento_id = intval($_GET['id'] ?? 0);
if (!$evento_id) die("Evento inválido");

// Actividades
$act = $conn->prepare("SELECT Actividad FROM actividades WHERE ID_Evento = ? ORDER BY Actividad");
$act->bind_param("i", $evento_id);
$act->execute();
$actividades = $act->get_result()->fetch_all(MYSQLI_ASSOC);
$act->close();

// Fechas del evento (solo éstas en el select)
$fd = $conn->prepare("SELECT DISTINCT Fecha FROM agenda WHERE ID_Evento = ? ORDER BY Fecha");
$fd->bind_param("i", $evento_id);
$fd->execute();
$fechas_res = $fd->get_result();
$fechas_evento = [];
while ($f = $fechas_res->fetch_assoc()) { $fechas_evento[] = $f['Fecha']; }
$fd->close();

// Agenda actual
$q = $conn->prepare("
  SELECT ID, Actividad, Fecha, Horario, Salon
  FROM agenda
  WHERE ID_Evento = ? AND Actividad <> 'Vacio'
  ORDER BY Fecha, SUBSTRING(Horario,1,5), Salon
");
$q->bind_param("i", $evento_id);
$q->execute();
$agenda = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Editar agenda · Evento #<?= $evento_id ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
  :root{
    --bg1:#0b1630;        /* azul profundo */
    --bg2:#0f2246;        /* azul medio */
    --panel:#1b2236;      /* panel */
    --ink:#eef2ff;        /* texto */
    --muted:#9fb0d1;
    --brand:#ff8a2b;      /* naranja principal */
    --brand-2:#ffb463;    /* naranja claro */
    --edge:#ff8a2b;       /* borde exterior */
    --line:#263250;       /* líneas tabla */
    --hover:#232c44;
    --ok:#22c55e;
    --bad:#ef4444;
  }

  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0; color:var(--ink); font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
    background:
      radial-gradient(900px 500px at 50% 10%, rgba(255,138,43,.12), transparent 60%),
      radial-gradient(1200px 600px at 70% 30%, rgba(87,147,255,.10), transparent 65%),
      linear-gradient(180deg, var(--bg2), var(--bg1));
  }

  /* Marco exterior naranja */
  .frame{
    position:fixed; inset:16px;
    border:4px solid var(--edge);
    border-radius:18px;
    pointer-events:none;
    box-shadow: 0 0 0 6px rgba(255,138,43,.08), 0 0 40px rgba(255,138,43,.25) inset;
  }

  .wrap{
    max-width:1100px; margin:48px auto 72px; padding:0 16px;
  }

  .title{
    text-align:center; margin:12px 0 24px;
    font-weight:900; letter-spacing:2px; font-size:48px;
    color:var(--brand);
    text-shadow: 0 3px 0 rgba(0,0,0,.25), 0 0 24px rgba(255,138,43,.35);
  }

  .card{
    margin:0 auto; max-width:980px;
    background: linear-gradient(180deg, rgba(0,0,0,.18), rgba(0,0,0,.10)), var(--panel);
    border-radius:18px; border:1px solid rgba(255,255,255,.06);
    box-shadow: 0 30px 80px rgba(0,0,0,.35), 0 0 40px rgba(24,50,99,.35);
    padding:22px;
  }

  .header-row{
    display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px;
  }
  .back{
    display:inline-flex; align-items:center; gap:8px;
    color:var(--brand); text-decoration:none; font-weight:700;
    padding:8px 12px; border-radius:10px; border:1px solid rgba(255,138,43,.35);
    background:rgba(255,138,43,.08);
  }
  .back:hover{filter:brightness(1.05)}

  .subtitle{
    font-size:18px; font-weight:800; letter-spacing:.4px;
  }

  .form-grid{
    display:grid; gap:12px; align-items:end;
    grid-template-columns: 2fr 1fr 1fr 1fr auto;
  }
  label{display:block; font-size:12px; color:var(--muted); margin: 0 0 6px 2px;}

  input, select{
    width:100%; padding:12px 12px; border-radius:12px;
    border:1px solid rgba(255,255,255,.06); outline:none;
    color:var(--ink); background:#121a30;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.02);
  }
  input::placeholder{color:#7d8caf}
  input:focus, select:focus{box-shadow: 0 0 0 2px rgba(255,138,43,.35); border-color: rgba(255,138,43,.5);}

  .btn{
    padding:12px 16px; border-radius:12px; border:none; cursor:pointer; font-weight:800;
    color:#1a1206; background:linear-gradient(180deg, var(--brand), var(--brand-2));
    box-shadow: 0 10px 22px rgba(255,138,43,.35);
    transition: transform .05s ease, filter .2s ease;
  }
  .btn:hover{filter:brightness(1.05)}
  .btn:active{transform:translateY(1px)}

  .table{
    width:100%; border-collapse:collapse; margin-top:18px; overflow:hidden;
    border-radius:14px; border:1px solid rgba(255,255,255,.06);
  }
  .table thead th{
    font-size:12px; letter-spacing:.6px; text-transform:uppercase; text-align:left;
    padding:12px; background:#141f38; color:#b2c6ff;
    border-bottom:1px solid var(--line);
  }
  .table tbody td{
    padding:12px; border-bottom:1px solid var(--line);
  }
  .table tbody tr:hover{background:var(--hover)}
  .tag{
    display:inline-block; padding:4px 10px; border-radius:999px;
    background:rgba(255,138,43,.12); color:#ffd7b2; border:1px solid rgba(255,138,43,.35); font-size:12px;
  }
  .muted{color:var(--muted)}
  .empty{
    margin-top:8px; padding:16px; text-align:center; color:var(--muted);
    border:1px dashed var(--line); border-radius:12px; background:#111a32;
  }

  /* Responsive */
  @media (max-width: 960px){ .form-grid{grid-template-columns: 1fr 1fr;} }
  @media (max-width: 520px){
    .title{font-size:36px}
    .header-row{flex-direction:column; align-items:flex-start}
  }
</style>
</head>
<body>
  <div class="frame"></div>

  <div class="wrap">

    <div class="card">
      <div class="header-row">
        <a class="back" href="Evento_inicio.php?id=<?= $evento_id ?>">← Volver</a>
        <div class="subtitle">Congresos · Agenda manual — Evento #<?= $evento_id ?></div>
      </div>

      <form class="form-grid" method="post" action="guardar_horario.php" autocomplete="off">
        <input type="hidden" name="id_evento" value="<?= $evento_id ?>">

        <!-- Actividad -->
        <div>
          <label>Actividad</label>
          <select name="actividad" required>
            <option value="">— Elegir —</option>
            <?php foreach($actividades as $a): ?>
              <option><?= htmlspecialchars($a['Actividad']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Fecha: solo días del evento -->
     <div>
          <label>Fecha</label>
          <input type="date" name="fecha" required>
        </div>

        <!-- Horario -->
        <div>
          <label>Horario (HH:MM-HH:MM)</label>
          <input name="horario" placeholder="09:00-10:00" pattern="^\d{2}:\d{2}-\d{2}:\d{2}$" required>
        </div>

        <!-- Salón -->
          <div>
          <label>Salón</label>
          <?php
          // Obtener salones asociados al evento (únicos)
          $salones_query = $conn->prepare("SELECT DISTINCT Salon FROM agenda WHERE ID_Evento = ? AND Salon <> '' ORDER BY Salon");
          $salones_query->bind_param("i", $evento_id);
          $salones_query->execute();
          $salones_res = $salones_query->get_result();
          $salones = $salones_res->fetch_all(MYSQLI_ASSOC);
          $salones_query->close();
          ?>
        
            <input type="text" name="salon" required placeholder="Nombre del salón">
         
        </div>

        <button class="btn">Agregar</button>
      </form>
    </div>

    <div class="card" style="margin-top:18px;">
      <?php if (empty($agenda)): ?>
        <div class="empty">No hay horarios todavía. Agrega el primero arriba.</div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Horario</th>
              <th>Salón</th>
              <th>Actividad</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($agenda as $r): ?>
              <tr>
                <td><span class="tag"><?= htmlspecialchars($r['Fecha']) ?></span></td>
                <td><?= htmlspecialchars($r['Horario']) ?></td>
                <td class="muted"><?= htmlspecialchars($r['Salon']) ?></td>
                <td><?= htmlspecialchars($r['Actividad']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
