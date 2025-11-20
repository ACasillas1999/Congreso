<?php
/* estadisticas_vendedores.php */
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "Conexiones/Conexion.php";

$evento_id = isset($_GET['evento']) ? intval($_GET['evento']) : 0;
if ($evento_id <= 0) { http_response_code(400); exit("Evento inválido."); }

// Nombre del evento
$nombre_evento = "Evento desconocido";
$stmt = $conn->prepare("SELECT name_evento FROM evento WHERE ID = ?");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$rs = $stmt->get_result();
if ($row = $rs->fetch_assoc()) { $nombre_evento = $row['name_evento']; }
$stmt->close();

// Sucursales para el selector
$sucursales = [];
$stmt = $conn->prepare("
  SELECT DISTINCT UPPER(COALESCE(NULLIF(p.Sucursal,''),'(SIN SUCURSAL)')) AS Sucursal
  FROM participante p
  WHERE p.ID_Evento = ?
  ORDER BY Sucursal
");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$rs = $stmt->get_result();
while ($r = $rs->fetch_assoc()) { $sucursales[] = $r['Sucursal']; }
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Estadísticas por Vendedor</title>
  <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{background:linear-gradient(to right,#12172a,#1d1f3a);color:#f0f0f0;font-family:'Poppins',sans-serif;margin:0}
    .container{max-width:1100px;margin:40px auto;padding:24px;background:rgba(30,33,58,.95);border-radius:16px;box-shadow:0 0 30px rgba(0,0,0,.35)}
    .titulo{margin:0 0 14px;font-size:26px;font-weight:700;color:#ffa726;text-align:center;text-shadow:0 0 8px rgba(0,0,0,.6)}
    .sub{opacity:.9;margin:4px 0 18px;text-align:center}
    .toolbar{display:flex;gap:10px;align-items:center;justify-content:center;flex-wrap:wrap;margin:12px 0 18px}
    select,button{border-radius:10px;border:1px solid rgba(255,255,255,.12);background:#222845;color:#fff;padding:10px 12px}
    .btn-volver{background:linear-gradient(90deg,#00c6ff,#0072ff);border:none}
    .btn-volver:hover{filter:brightness(1.05)}
    .resume{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin:12px 0 8px}
    .chip{background:#26315a;border:1px solid rgba(255,255,255,.12);padding:8px 12px;border-radius:999px}
    .chip strong{color:#ffd180}
    .grid-tops{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:14px}
    @media(max-width:900px){.grid-tops{grid-template-columns:1fr 1fr}}
    @media(max-width:640px){.grid-tops{grid-template-columns:1fr}}
    .card{background:#23284a;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:12px}
    .card h4{margin:0 0 6px}
    .muted{color:#cfd6ff;opacity:.9;font-size:.9rem}
    canvas{background:#1e1e2f;border-radius:12px;padding:12px;margin-top:16px;box-shadow:0 0 15px rgba(0,0,0,.25)}
    table{width:100%;border-collapse:collapse;margin-top:18px}
    th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.1)}
    thead th{background:#0d47a1}
    tbody tr:hover{background:#33335b;cursor:pointer}
    .highlight{background:#004d40!important;color:#a7ffeb}
    .empty{padding:10px;text-align:center;color:#cfd6ff;opacity:.85}
    /* Modal */
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);display:none;align-items:center;justify-content:center;z-index:9999;padding:16px}
    .modal{background:#1d2139;color:#eef1ff;width:min(900px,96vw);max-height:90vh;overflow:hidden;border-radius:14px;box-shadow:0 20px 70px rgba(0,0,0,.5);display:flex;flex-direction:column}
    .modal-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.08);background:linear-gradient(180deg,rgba(255,167,38,.15),rgba(255,167,38,.02))}
    .modal-title{font-weight:700}
    .modal-close{background:transparent;color:#fff;border:none;font-size:22px;cursor:pointer}
    .modal-body{padding:12px 16px 16px;overflow:auto}
    .tabs{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0 10px}
    .tab-btn{padding:8px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.15);background:#2a2f52;color:#fff;cursor:pointer;font-weight:600}
    .tab-btn.active{background:#4c58a5}
    .tab-panel{display:none}
    .tab-panel.active{display:block}
    .badge{display:inline-block;min-width:18px;padding:2px 8px;border-radius:999px;background:#ffa726;color:#111;font-weight:800;font-size:.85rem;text-align:center;margin-left:6px}
    .pill{display:inline-block;padding:2px 10px;border-radius:999px;background:#00c78c;color:#06221a;font-weight:700;font-size:.85rem;margin-left:6px}
    .grid {display:grid;grid-template-columns:1fr 1fr;gap:10px}
    @media(max-width:720px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <h2 class="titulo">📊 Estadísticas por Vendedor</h2>
  <div class="sub">Evento: <strong><?= htmlspecialchars($nombre_evento) ?></strong></div>

  <div class="toolbar">
    <a class="btn-volver" href="Evento_inicio.php?id=<?= urlencode($evento_id) ?>">← Volver al evento</a>
    <select id="selSucursal" title="Filtrar por sucursal">
      <option value="TODAS">Todas las sucursales</option>
      <?php foreach($sucursales as $s): ?>
        <option value="<?= htmlspecialchars($s, ENT_QUOTES) ?>"><?= htmlspecialchars($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="resume" id="resumenTops">
    <!-- se rellena por JS -->
  </div>

  <canvas id="graficaVendedores" height="140"></canvas>

  <table>
    <thead>
      <tr>
        <th>Vendedor</th>
        <th>Registrados</th>
        <th>Con ≥1 asistencia</th>
        <th>Tasa</th>
      </tr>
    </thead>
    <tbody id="tbodyVendedores">
      <tr><td colspan="4" class="empty">Cargando…</td></tr>
    </tbody>
  </table>

  <div id="topsPorSucursal" style="margin-top:18px;display:none;">
    <h3 style="margin:12px 0 8px;">🏬 Top por sucursal (solo en “Todas”)</h3>
    <div id="gridTops" class="grid-tops"></div>
  </div>
</div>

<!-- ===== Modal Detalle por vendedor (se reutiliza del flujo anterior) ===== -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Detalle de <span id="modalVendedor"></span></div>
      <button class="modal-close" id="modalClose" aria-label="Cerrar">✕</button>
    </div>
    <div class="modal-body">
      <div class="muted">Evento: <strong><?= htmlspecialchars($nombre_evento) ?></strong> • <span id="resumenCounts"></span></div>
      <div class="tabs" style="margin-top:8px;">
        <button class="tab-btn active" data-tab="tabTodos">Todos</button>
        <button class="tab-btn" data-tab="tabCon">Con asistencia</button>
        <button class="tab-btn" data-tab="tabSin">Sin asistencia</button>
      </div>
      <div id="tabTodos" class="tab-panel active"><div id="panelTodos" class="grid"></div></div>
      <div id="tabCon" class="tab-panel"><div id="panelCon" class="grid"></div></div>
      <div id="tabSin" class="tab-panel"><div id="panelSin" class="grid"></div></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const evento = <?= json_encode($evento_id) ?>;
const selSucursal = document.getElementById('selSucursal');
const tbody = document.getElementById('tbodyVendedores');
const resumenTops = document.getElementById('resumenTops');
const topsBox = document.getElementById('topsPorSucursal');
const gridTops = document.getElementById('gridTops');

let chart;
function ensureChart(){ 
  const ctx = document.getElementById('graficaVendedores').getContext('2d');
  if(chart) chart.destroy();
  chart = new Chart(ctx, {
    type: 'bar',
    data: { labels: [], datasets: [
      { label: 'Registrados', data: [], backgroundColor: 'rgba(235,172,54,.75)', borderColor: 'rgba(235,172,54,1)', borderWidth:1, borderRadius:6 },
      { label: 'Con ≥1 asistencia', data: [], backgroundColor: 'rgba(0,199,140,.75)', borderColor: 'rgba(0,199,140,1)', borderWidth:1, borderRadius:6 }
    ]},
    options: { responsive:true, scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } } }
  });
}

async function loadStats(){
  const sucursal = selSucursal.value || 'TODAS';
  tbody.innerHTML = '<tr><td colspan="4" class="empty">Cargando…</td></tr>';
  resumenTops.innerHTML = '';
  topsBox.style.display = 'none';
  gridTops.innerHTML = '';

  const url = `stats_vendedores.php?evento=${encodeURIComponent(evento)}&sucursal=${encodeURIComponent(sucursal)}`;
  const resp = await fetch(url);
  if(!resp.ok){ tbody.innerHTML = '<tr><td colspan="4" class="empty">Error al cargar.</td></tr>'; return; }
  const data = await resp.json();

  // Resumen top general y top de la sucursal
  const chips = [];
  if (data.top_general) {
    chips.push(`<span class="chip">🥇 Top general: <strong>${escapeHtml(data.top_general.vendedor)}</strong> (${data.top_general.total})</span>`);
  }
  if (data.top_sucursal) {
    chips.push(`<span class="chip">🏬 Top en sucursal ${escapeHtml(data.top_sucursal.sucursal)}: <strong>${escapeHtml(data.top_sucursal.vendedor)}</strong> (${data.top_sucursal.total})</span>`);
  } else if (sucursal !== 'TODAS') {
    chips.push(`<span class="chip">🏬 Sucursal ${escapeHtml(sucursal)} sin datos.</span>`);
  }
  resumenTops.innerHTML = chips.join("");

  // Tabla
  if(!data.vendedores || data.vendedores.length === 0){
    tbody.innerHTML = '<tr><td colspan="4" class="empty">Sin datos.</td></tr>';
  } else {
    tbody.innerHTML = data.vendedores.map(v => `
      <tr data-vendedor="${escapeAttr(v.Vendedor)}" class="${data.top_general && v.Vendedor===data.top_general.vendedor ? 'highlight':''}">
        <td>${escapeHtml(v.Vendedor)}</td>
        <td>${v.total_clientes}</td>
        <td>${v.con_asistencia}</td>
        <td>${v.tasa}%</td>
      </tr>
    `).join('');
  }

  // Gráfica
  ensureChart();
  chart.data.labels = (data.vendedores||[]).map(v => v.Vendedor);
  chart.data.datasets[0].data = (data.vendedores||[]).map(v => v.total_clientes);
  chart.data.datasets[1].data = (data.vendedores||[]).map(v => v.con_asistencia);
  chart.update();

  // Top por sucursal (solo en TODAS)
  if (sucursal === 'TODAS' && data.tops_por_sucursal && data.tops_por_sucursal.length){
    topsBox.style.display = 'block';
    gridTops.innerHTML = data.tops_por_sucursal.map(t => `
      <div class="card">
        <h4>${escapeHtml(t.sucursal)}</h4>
        <div class="muted">Top: <strong>${escapeHtml(t.vendedor)}</strong> (${t.total})</div>
      </div>
    `).join('');
  }
}

selSucursal.addEventListener('change', loadStats);
document.addEventListener('DOMContentLoaded', loadStats);

// ===== Modal de detalle por vendedor (igual que versión anterior) =====
const overlay = document.getElementById('modalOverlay');
const modalClose = document.getElementById('modalClose');
const modalVend = document.getElementById('modalVendedor');
const resumenCounts = document.getElementById('resumenCounts');
const panels = {
  Todos: document.getElementById('panelTodos'),
  Con: document.getElementById('panelCon'),
  Sin: document.getElementById('panelSin'),
};
function openModal(){ overlay.style.display = 'flex'; }
function closeModal(){ overlay.style.display = 'none'; clearPanels(); }
modalClose.addEventListener('click', closeModal);
overlay.addEventListener('click', e=>{ if(e.target===overlay) closeModal(); });
function clearPanels(){ for (const k in panels){ panels[k].innerHTML=''; } resumenCounts.textContent=''; }
function setActiveTab(tabId){
  document.querySelectorAll('.tab-btn').forEach(b=> b.classList.toggle('active', b.dataset.tab===tabId));
  document.querySelectorAll('.tab-panel').forEach(p=> p.classList.toggle('active', p.id===tabId));
}
document.querySelectorAll('.tab-btn').forEach(btn=> btn.addEventListener('click', ()=> setActiveTab(btn.dataset.tab)));

tbody.addEventListener('click', async (e)=>{
  const tr = e.target.closest('tr[data-vendedor]'); if(!tr) return;
  const vendedor = tr.getAttribute('data-vendedor');
  modalVend.textContent = vendedor;
  clearPanels();
  panels.Todos.innerHTML = '<div class="muted">Cargando detalle…</div>';
  openModal();

  const suc = selSucursal.value || 'TODAS';
  const resp = await fetch(`detalles_vendedor.php?evento=${encodeURIComponent(evento)}&vendedor=${encodeURIComponent(vendedor)}&sucursal=${encodeURIComponent(suc)}`);
  if(!resp.ok){ panels.Todos.innerHTML='<div class="muted">Error al cargar detalle.</div>'; return; }
  const data = await resp.json();

  const all = data.participantes || [];
  const con = all.filter(x => Number(x.asistencias) > 0);
  const sin = all.filter(x => Number(x.asistencias) === 0);

  resumenCounts.innerHTML = `Registrados: <strong>${data.total||all.length}</strong>
    <span class="pill">Con asistencia: ${data.con_asistencia||con.length}</span>
    <span class="pill" style="background:#e57373;color:#2b0b0b;">Sin: ${data.sin_asistencia||sin.length}</span>`;

  const cardHTML = (p) => `
    <div class="card">
      <h4>${escapeHtml(p.Nombre || '')}</h4>
      <div class="muted">Tel: ${escapeHtml(p.Telefono || '-')} • RFC: ${escapeHtml(p.RFC || '-')} • Suc: ${escapeHtml(p.Sucursal || '-')}</div>
      <div style="margin-top:6px;">Asistencias: <span class="badge">${Number(p.asistencias)||0}</span></div>
    </div>`;
  panels.Todos.innerHTML = all.map(cardHTML).join('') || '<div class="muted">Sin registros.</div>';
  panels.Con.innerHTML   = con.map(cardHTML).join('')   || '<div class="muted">Nadie con asistencia.</div>';
  panels.Sin.innerHTML   = sin.map(cardHTML).join('')   || '<div class="muted">Todos asistieron.</div>';
});

function escapeHtml(s){ return (s??'').replace(/[&<>\"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
function escapeAttr(s){ return escapeHtml(s); }
</script>
</body>
</html>
