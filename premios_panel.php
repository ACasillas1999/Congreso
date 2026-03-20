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
<?php include "header_css.php"; ?>
<style>
:root{
  --bg: var(--theme-primary-dark, #054a6b);
  --bg-2: var(--theme-primary, #1ca9dc);
  --ink: var(--theme-text, #ffffff);
  --muted: var(--theme-text-soft, rgba(255,255,255,.78));
  --line: rgba(255,255,255,.12);
  --card: rgba(8,27,50,.88);
  --card-2: rgba(8,27,50,.74);
  --primary: var(--theme-primary, #1ca9dc);
  --primary-2: var(--theme-primary-dark, #054a6b);
  --accent: var(--theme-title, #7cecff);
  --shadow:0 14px 40px rgba(0,0,0,.45);
  --r-lg:18px; --r-md:14px;
}
*{box-sizing:border-box}
html,body{height:100%}
body{margin:0;color:var(--ink);font-family:"Inter","Segoe UI",system-ui,Arial,sans-serif;background:radial-gradient(900px 600px at 10% -10%, rgba(56,217,255,.22), transparent 60%),radial-gradient(800px 500px at 110% 10%, rgba(28,169,220,.20), transparent 60%),linear-gradient(180deg, var(--bg), var(--bg-2) 70%);padding:env(safe-area-inset-top) 14px env(safe-area-inset-bottom)}
.appbar{position:sticky;top:0;z-index:20;display:grid;grid-template-columns:48px 1fr 48px;align-items:center;padding:10px 2px 10px;backdrop-filter:blur(10px);background:linear-gradient(180deg, rgba(7,12,36,.85), rgba(7,12,36,.35))}
.btn.back{width:44px;height:44px;display:grid;place-items:center;border-radius:12px;text-decoration:none;color:var(--ink);background:rgba(255,255,255,.08);border:1px solid var(--line)}
.appbar__title{text-align:center;line-height:1.05}.appbar .title{font-weight:800;font-size:clamp(16px,4.8vw,20px)}.appbar .subtitle{color:var(--muted);font-size:12px;opacity:.95}
.wrap{width:min(100%, 920px);margin:12px auto 24px;display:grid;gap:14px}
.hero{position:relative;overflow:hidden;border-radius:var(--r-lg);padding:16px;background:linear-gradient(135deg, rgba(56,217,255,.18), rgba(8,27,50,.88) 60%);border:1px solid var(--line);box-shadow:var(--shadow)}
.hero:before{content:"";position:absolute;inset:auto -15% -45% -15%;height:180px;filter:blur(26px);background:radial-gradient(60% 60% at 50% 40%, rgba(56,217,255,.35), transparent 70%);pointer-events:none}
.hero h1{margin:0 0 6px;font-size:clamp(18px,5.2vw,22px);color:var(--accent)}
.hero p{margin:0;color:var(--muted);font-size:14px}
.cards{display:grid;gap:12px;grid-template-columns:1fr}
.card{text-decoration:none;color:var(--ink);display:grid;grid-template-columns:auto 1fr;align-items:center;gap:12px;min-height:64px;padding:14px 16px;border-radius:var(--r-lg);background:linear-gradient(180deg, var(--card), var(--card-2));border:1px solid var(--line);box-shadow:0 10px 28px rgba(0,0,0,.4), inset 0 -1px 0 rgba(255,255,255,.06);transition:transform .12s ease, box-shadow .2s ease, background .2s ease}
.card__icon{font-size:22px}.card__title{font-weight:800;font-size:16px;line-height:1.2}.card__desc{grid-column:2;color:var(--muted);font-size:13px;margin-top:2px}
.card:hover{transform:translateY(-2px);box-shadow:0 16px 36px rgba(0,0,0,.55)}.card:active{transform:translateY(0) scale(.985)}
.card--orange{border-color:rgba(56,217,255,.35);background:linear-gradient(180deg, rgba(56,217,255,.12), rgba(8,27,50,.88))}
.card--blue{border-color:rgba(28,169,220,.45);background:linear-gradient(180deg, rgba(28,169,220,.14), rgba(8,27,50,.88))}
.card--gray{border-color:rgba(255,255,255,.18);background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(8,27,50,.88))}
.card:focus-visible,.btn.back:focus-visible{outline:3px solid var(--accent);outline-offset:2px}
@media (min-width: 600px){.cards{grid-template-columns:1fr 1fr;gap:14px}.card{min-height:72px;padding:16px 18px}.card__title{font-size:17px}}
@media (min-width: 900px){.cards{grid-template-columns:1fr 1fr 1fr}.hero{padding:18px 20px}}
@media (prefers-reduced-motion: reduce){*{transition:none !important}}
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


