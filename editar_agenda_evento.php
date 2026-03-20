<?php
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_secure', true);
session_name("CON");
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {
    header("location: /Congreso/Sesion/login.html");
    exit;
}

require_once __DIR__ . "/Conexiones/Conexion.php";

$evento_id = intval($_GET['id'] ?? 0);
if (!$evento_id) {
    die("Evento invalido");
}

$id_evento = $evento_id;
$agenda_editar_id = intval($_GET['editar'] ?? 0);
$agenda_edit = null;

$evento_nombre = '';
$ev = $conn->prepare("SELECT name_evento FROM evento WHERE ID = ?");
$ev->bind_param("i", $evento_id);
$ev->execute();
$ev_res = $ev->get_result();
$ev_row = $ev_res ? $ev_res->fetch_assoc() : null;
$evento_nombre = $ev_row['name_evento'] ?? '';
$ev->close();

$act = $conn->prepare("SELECT Actividad FROM actividades WHERE ID_Evento = ? ORDER BY Actividad");
$act->bind_param("i", $evento_id);
$act->execute();
$actividades = $act->get_result()->fetch_all(MYSQLI_ASSOC);
$act->close();

$fd = $conn->prepare("SELECT DISTINCT Fecha FROM agenda WHERE ID_Evento = ? ORDER BY Fecha");
$fd->bind_param("i", $evento_id);
$fd->execute();
$fechas_res = $fd->get_result();
$fechas_evento = [];
while ($f = $fechas_res->fetch_assoc()) {
    $fechas_evento[] = $f['Fecha'];
}
$fd->close();

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

if ($agenda_editar_id > 0) {
    $qe = $conn->prepare("
      SELECT ID, Actividad, Fecha, Horario, Salon
      FROM agenda
      WHERE ID = ? AND ID_Evento = ? AND Actividad <> 'Vacio'
      LIMIT 1
    ");
    $qe->bind_param("ii", $agenda_editar_id, $evento_id);
    $qe->execute();
    $edit_res = $qe->get_result();
    $agenda_edit = $edit_res ? $edit_res->fetch_assoc() : null;
    $qe->close();

    if (!$agenda_edit) {
        $agenda_editar_id = 0;
    }
}

$salones_query = $conn->prepare("SELECT DISTINCT Salon FROM agenda WHERE ID_Evento = ? AND Salon <> '' ORDER BY Salon");
$salones_query->bind_param("i", $evento_id);
$salones_query->execute();
$salones_res = $salones_query->get_result();
$salones = $salones_res->fetch_all(MYSQLI_ASSOC);
$salones_query->close();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Agenda manual - Evento #<?= $evento_id ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include "header_css.php"; ?>
<style>
  .page-shell {
    width: 98%;
    margin: 10px auto;
    padding: 20px;
  }

  .hero-panel,
  .section-panel {
    background: var(--theme-surface-strong);
    border: 1px solid var(--theme-border);
    border-radius: var(--theme-radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--theme-shadow);
  }

  .hero-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
  }

  body, button, input, select, textarea {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
  }

  .page-title {
    margin: 0;
    color: var(--theme-title);
    text-shadow: 0 0 10px var(--theme-title);
    font-size: 28px;
  }

  .hero-stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
  }

  .stat {
    background: rgba(255,255,255,0.05);
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid var(--theme-border);
    text-align: center;
    min-width: 100px;
  }

  .stat-label {
    display: block;
    font-size: 10px;
    text-transform: uppercase;
    color: var(--theme-text-soft);
    margin-bottom: 4px;
  }

  .stat-value {
    font-size: 20px;
    font-weight: 800;
    color: var(--theme-title);
  }

  .toolbar-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 15px;
    flex-wrap: wrap;
  }

  .toolbar-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255,193,77,0.12);
    color: #ffd27a;
    border: 1px solid rgba(255,193,77,0.22);
    font-size: 12px;
    font-weight: 700;
  }

  .toolbar-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 8px;
    border: 1px solid var(--theme-border);
    color: var(--theme-text);
    text-decoration: none;
    background: rgba(255,255,255,0.04);
    font-weight: 700;
  }

  .toolbar-link:hover {
    background: rgba(255,255,255,0.08);
  }

  .composer-form {
    display: grid;
    gap: 15px;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    align-items: end;
  }

  .field label {
    display: block;
    font-size: 12px;
    margin-bottom: 5px;
    color: var(--theme-text-soft);
  }

  .field input,
  .field select {
    width: 100%;
    height: 44px;
    padding: 0 12px;
    border-radius: 8px;
  }

  .btn-add {
    height: 44px;
    background: var(--theme-primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-add:hover {
    background: var(--theme-primary-dark);
    transform: translateY(-2px);
  }

  .helper-strip {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .chip {
    font-size: 11px;
    padding: 4px 10px;
    background: rgba(255,255,255,0.08);
    border-radius: 4px;
    color: var(--theme-text-soft);
  }

  .actions-cell {
    white-space: nowrap;
  }

  .btn-inline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 0 12px;
    border-radius: 8px;
    border: 1px solid rgba(56,217,255,0.22);
    background: rgba(56,217,255,0.1);
    color: var(--theme-title);
    text-decoration: none;
    font-weight: 700;
  }

  .btn-inline:hover {
    background: var(--theme-title);
    color: #00131f !important;
  }

  .row-editing td {
    background: rgba(255,193,77,0.08) !important;
  }

  @media (max-width: 768px) {
    .hero-top { flex-direction: column; align-items: flex-start; }
    .hero-stats { width: 100%; justify-content: space-between; }
  }
</style>
</head>
<body class="fade-in">
  <?php include "sidebar.php"; ?>

  <div class="page-shell">
    <section class="hero-panel">
      <div class="hero-top">
        <div>
          <h1 class="page-title">Agenda Manual</h1>
          <p style="color:var(--theme-text-soft); margin-top:5px;">
            <?= htmlspecialchars($evento_nombre) ?> · #<?= $evento_id ?>
          </p>
        </div>

        <div class="hero-stats">
          <div class="stat">
            <span class="stat-label">Actividades</span>
            <span class="stat-value"><?= count($actividades) ?></span>
          </div>
          <div class="stat">
            <span class="stat-label">Horarios</span>
            <span class="stat-value"><?= count($agenda) ?></span>
          </div>
          <div class="stat">
            <span class="stat-label">Salones</span>
            <span class="stat-value"><?= count($salones) ?></span>
          </div>
        </div>
      </div>
    </section>

    <section class="section-panel">
      <div class="toolbar-row">
        <h3 style="color:var(--theme-title); margin:0; font-size:18px;">
          <?= $agenda_edit ? 'Editar horario' : 'Agregar horario' ?>
        </h3>
        <?php if ($agenda_edit): ?>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <span class="toolbar-badge">Editando ID #<?= (int)$agenda_edit['ID'] ?></span>
            <a class="toolbar-link" href="editar_agenda_evento.php?id=<?= $evento_id ?>">Cancelar edición</a>
          </div>
        <?php endif; ?>
      </div>

      <form class="composer-form" method="post" action="guardar_horario.php" autocomplete="off">
        <input type="hidden" name="id_evento" value="<?= $evento_id ?>">
        <?php if ($agenda_edit): ?>
          <input type="hidden" name="id_agenda" value="<?= (int)$agenda_edit['ID'] ?>">
        <?php endif; ?>

        <div class="field">
          <label>Actividad</label>
          <select name="actividad" required>
            <option value="">— Elegir —</option>
            <?php foreach ($actividades as $a): ?>
              <option value="<?= htmlspecialchars($a['Actividad']) ?>" <?= ($agenda_edit && $agenda_edit['Actividad'] === $a['Actividad']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($a['Actividad']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Fecha</label>
          <input type="date" name="fecha" value="<?= htmlspecialchars($agenda_edit['Fecha'] ?? '') ?>" required>
        </div>

        <div class="field">
          <label>Horario (HH:MM-HH:MM)</label>
          <input name="horario" value="<?= htmlspecialchars($agenda_edit['Horario'] ?? '') ?>" placeholder="09:00-10:00" pattern="^\d{2}:\d{2}-\d{2}:\d{2}$" required>
        </div>

        <div class="field">
          <label>Salón</label>
          <input type="text" name="salon" value="<?= htmlspecialchars($agenda_edit['Salon'] ?? '') ?>" required placeholder="Nombre del salón" list="salones-lista">
          <datalist id="salones-lista">
            <?php foreach ($salones as $salon): ?>
              <option value="<?= htmlspecialchars($salon['Salon']) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>

        <button class="btn-add" type="submit"><?= $agenda_edit ? 'Guardar cambios' : 'Agregar' ?></button>
      </form>

      <div class="helper-strip">
        <span class="chip">Formato: 09:00-10:00</span>
        <span class="chip">Fechas: <?= count($fechas_evento) ?></span>
        <?php if ($agenda_edit): ?>
          <span class="chip">Editando sin salir de esta pantalla</span>
        <?php endif; ?>
      </div>
    </section>

    <section class="section-panel" style="padding:0; overflow:hidden;">
      <div style="padding:20px 24px;">
        <h3 style="color:var(--theme-title); margin:0; font-size:18px;">Horarios Registrados</h3>
      </div>
      <?php if (empty($agenda)): ?>
        <div style="padding:40px; text-align:center; color:var(--theme-text-soft);">
          No hay horarios todavía.
        </div>
      <?php else: ?>
        <table class="mi-tabla">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Horario</th>
              <th>Salón</th>
              <th>Actividad</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($agenda as $r): ?>
              <tr class="<?= ($agenda_edit && (int)$agenda_edit['ID'] === (int)$r['ID']) ? 'row-editing' : '' ?>">
                <td><span class="badge" style="background:rgba(56,217,255,0.1); color:var(--theme-title); border:1px solid rgba(56,217,255,0.2);"><?= htmlspecialchars($r['Fecha']) ?></span></td>
                <td style="font-weight:700;"><?= htmlspecialchars($r['Horario']) ?></td>
                <td style="color:var(--theme-text-soft);"><?= htmlspecialchars($r['Salon']) ?></td>
                <td><?= htmlspecialchars($r['Actividad']) ?></td>
                <td class="actions-cell">
                  <a class="btn-inline" href="editar_agenda_evento.php?id=<?= $evento_id ?>&editar=<?= (int)$r['ID'] ?>">Editar</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </div>
</body>
</html>
