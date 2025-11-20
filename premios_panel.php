<?php
require_once __DIR__ . "/Conexiones/Conexion.php";
$id_evento = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_evento <= 0) {
    die("Evento no válido.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<link rel="icon" href="/Congreso/educacion.png" type="image/x-icon" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta charset="UTF-8">
    <title>Panel de Premios</title>
    <!--<link rel="stylesheet" href="styles.css">-->

    <style>

  /* ========== THEME ========== */
:root{
  --bg:#0b1229;
  --bg-2:#0f1b3f;
  --ink:#f3f6ff;
  --muted:#b7c2d9;
  --line:#243157;
  --card:#121e46;
  --card-2:#0f1a3b;
  --primary:#2ea8ff;
  --primary-2:#1e79ff;
  --accent:#ff9554;
  --shadow:0 14px 40px rgba(0,0,0,.45);
  --r-lg:18px; --r-md:14px;
}

/* Reset mínimo */
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0; color:var(--ink);
  font-family: "Inter","Segoe UI",system-ui,Arial,sans-serif;
  background:
    radial-gradient(900px 600px at 10% -10%, #1a3b8f33, transparent 60%),
    radial-gradient(800px 500px at 110% 10%, #1e79ff24, transparent 60%),
    linear-gradient(180deg, var(--bg), var(--bg-2) 70%);
  padding: env(safe-area-inset-top) 14px env(safe-area-inset-bottom);
}

/* ===== Appbar ===== */
.appbar{
  position:sticky; top:0; z-index:20;
  display:grid; grid-template-columns:48px 1fr 48px; align-items:center;
  padding:10px 2px 10px;
  backdrop-filter: blur(10px);
  background: linear-gradient(180deg, rgba(7,12,36,.85), rgba(7,12,36,.35));
}
.btn.back{
  width:44px; height:44px; display:grid; place-items:center;
  border-radius:12px; text-decoration:none; color:var(--ink);
  background: rgba(255,255,255,.08);
  border:1px solid #1f2b55;
}
.appbar__title{ text-align:center; line-height:1.05; }
.appbar .title{ font-weight:800; font-size:clamp(16px,4.8vw,20px); }
.appbar .subtitle{ color:var(--muted); font-size:12px; opacity:.95; }

/* ===== Layout ===== */
.wrap{ width:min(100%, 920px); margin:12px auto 24px; display:grid; gap:14px; }

/* ===== Hero ===== */
.hero{
  position:relative; overflow:hidden;
  border-radius:var(--r-lg);
  padding:16px;
  background: linear-gradient(135deg, #1e79ff20, #101a3f 60%);
  border:1px solid var(--line);
  box-shadow: var(--shadow);
}
.hero:before{
  content:""; position:absolute; inset:auto -15% -45% -15%;
  height:180px; filter: blur(26px);
  background: radial-gradient(60% 60% at 50% 40%, #2ea8ff33, transparent 70%);
  pointer-events:none;
}
.hero h1{ margin:0 0 6px; font-size:clamp(18px,5.2vw,22px); }
.hero p{ margin:0; color:var(--muted); font-size:14px; }

/* ===== Cards ===== */
.cards{
  display:grid; gap:12px;
  grid-template-columns: 1fr;             /* móvil: 1 col */
}
.card{
  text-decoration:none; color:var(--ink);
  display:grid; grid-template-columns:auto 1fr; align-items:center; gap:12px;
  min-height:64px; padding:14px 16px;
  border-radius:var(--r-lg);
  background: linear-gradient(180deg, var(--card), var(--card-2));
  border:1px solid var(--line);
  box-shadow: 0 10px 28px rgba(0,0,0,.4), inset 0 -1px 0 rgba(255,255,255,.06);
  transition: transform .12s ease, box-shadow .2s ease, background .2s ease;
}
.card__icon{ font-size:22px; }
.card__title{ font-weight:800; font-size:16px; line-height:1.2; }
.card__desc{ grid-column:2; color:var(--muted); font-size:13px; margin-top:2px; }

.card:hover{ transform: translateY(-2px); box-shadow: 0 16px 36px rgba(0,0,0,.55); }
.card:active{ transform: translateY(0) scale(.985); }

.card--orange{ border-color:#ff955450; background:linear-gradient(180deg,#1b1435,#221a42); }
.card--blue  { border-color:#2ea8ff55; background:linear-gradient(180deg,#122455,#0f1b3f); }
.card--gray  { border-color:#9ea6bb44; background:linear-gradient(180deg,#141a30,#11182e); }

/* Focus a11y */
.card:focus-visible, .btn.back:focus-visible{
  outline:3px solid var(--primary); outline-offset:2px;
}

/* ===== Responsivo ===== */
/* Tablet: dos columnas, tipografía algo mayor */
@media (min-width: 600px){
  .cards{ grid-template-columns: 1fr 1fr; gap:14px; }
  .card{ min-height:72px; padding:16px 18px; }
  .card__title{ font-size:17px; }
}

/* Desktop medio: tres columnas y hero más aire */
@media (min-width: 900px){
  .cards{ grid-template-columns: 1fr 1fr 1fr; }
  .hero{ padding:18px 20px; }
}

/* Reduce motion */
@media (prefers-reduced-motion: reduce){ *{ transition:none !important; } }

    </style>
</head>
<body>
  <header class="appbar">
    <div class="appbar__left">
      <a class="btn back" href="Evento_inicio.php?id=<?= $id_evento ?>">←</a>
    </div>
    <div class="appbar__title">
      <span class="emoji">🎁</span>
      <div class="title">Panel de Premios</div>
      <div class="subtitle">Evento #<?= $id_evento ?></div>
    </div>
    <div class="appbar__right"></div>
  </header>

  <main class="wrap">
    <section class="hero">
      <div class="hero__glow"></div>
      <h1>Gestiona premios del evento</h1>
      <p>Administra, canjea y vuelve al inicio con una interfaz lista para táctil.</p>
    </section>

    <nav class="cards">
      <a href="administrar_premios.php?id=<?= $id_evento ?>" class="card card--orange">
        <div class="card__icon">➕</div>
        <div class="card__title">Administrar Premios</div>
        <div class="card__desc">Crea, edita y controla disponibilidad.</div>
      </a>

      <a href="canjear.php?id=<?= $id_evento ?>" class="card card--blue">
        <div class="card__icon">🎟</div>
        <div class="card__title">Canjear Premios</div>
        <div class="card__desc">Escanea y realiza canjes al instante.</div>
      </a>

      <a href="Evento_inicio.php?id=<?= $id_evento ?>" class="card card--gray">
        <div class="card__icon">⬅️</div>
        <div class="card__title">Volver al Evento</div>
        <div class="card__desc">Regresar al panel principal.</div>
      </a>
    </nav>
  </main>
</body>

</html>
