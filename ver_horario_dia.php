<?php
// Iniciar la sesión de forma segura

ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON");
session_start();

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["Rol"] === "Vendedor") {
 
    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}



// Conectar a la base de datos
require_once __DIR__ . "/Conexiones/Conexion.php"; // Asegúrate de incluir tu archivo de configuración para la conexión a la base de datos

// Consulta para obtener la agenda del día, incluyendo Horarios, salones, actividades e ID de cada clase
$fecha_actual =isset($_GET['fecha']) ? $_GET['fecha'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$agenda_sql = "SELECT ID, Horario, Salon, Actividad
               FROM agenda
               WHERE ID_Evento = ? AND Fecha = ? AND Actividad <> 'Vacio'
               ORDER BY SUBSTRING(Horario,1,5), Salon";

if ($stmt = $conn->prepare($agenda_sql)) {
    $stmt->bind_param("is", $id, $fecha_actual); // primero evento (int), luego fecha (string)
    $stmt->execute();
    $agenda_result = $stmt->get_result();

    // Crear una matriz asociativa para almacenar las actividades
    $agenda_matriz = array();

    while ($agenda_row = $agenda_result->fetch_assoc()) {
        $Horario = $agenda_row['Horario'];
        $salon = $agenda_row['Salon'];
        $actividad = $agenda_row['Actividad'];
        $id_clase = $agenda_row['ID'];

        // Agrupar actividades por Horario y salón, incluyendo el ID
        $agenda_matriz[$Horario][$salon] = [
            'Actividad' => $actividad,
            'ID' => $id_clase
        ];
    }

    $stmt->close();
}

//$salones = !empty($agenda_matriz) ? array_keys(reset($agenda_matriz)) : [];

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="/congreso/educacion.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Evento</title>
   <!-- <link rel="stylesheet" type="text/css" href="styles.css">-->

    <style>
body {
  background: radial-gradient(circle at center, #0d1c3b, #1e2a78);
  font-family: 'Segoe UI', sans-serif;
  color: #e0e0e0;
  margin: 0;
  padding: 0;
}

.agenda-container {
  margin-left: 260px;
  padding: 30px;
}

.titulo {
  font-size: 32px;
  color: #ffa726;
  text-shadow: 0 0 8px #ffa726;
  margin-bottom: 5px;
  animation: flicker 2s infinite alternate;
}

.titulo2 {
  font-size: 18px;
  color: #90caf9;
  margin-bottom: 25px;
}

.agenda-timeline {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.bloque-horario {
  background-color: rgba(255, 255, 255, 0.02);
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 0 18px rgba(0,255,255,0.06);
  backdrop-filter: blur(5px);
  transition: transform 0.3s ease;
}

.hora-label {
  font-weight: bold;
  font-size: 22px;
  color: #00e5ff;
  margin-bottom: 15px;
  text-shadow: 0 0 6px rgba(0, 229, 255, 0.3);
}

.actividades-linea {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.card-actividad {
  background: rgba(30, 30, 47, 0.8);
  border-left: 5px solid #42a5f5;
  padding: 18px 20px;
  border-radius: 10px;
  width: 280px;
  box-shadow: 0 0 12px rgba(66, 165, 245, 0.3);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card-actividad:hover {
  transform: translateY(-4px);
  box-shadow: 0 0 20px rgba(66, 165, 245, 0.6);
}

.salon-label {
  font-weight: bold;
  margin-bottom: 6px;
  color: #ffcc80;
  text-shadow: 0 0 4px rgba(255, 204, 128, 0.3);
}

.contenido-actividad {
  color: #ffffff;
  font-size: 14px;
  margin-bottom: 12px;
}

.btn-ver {
  background: linear-gradient(90deg, #42a5f5, #478ed1);
  color: white;
  text-decoration: none;
  padding: 8px 14px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: bold;
  transition: all 0.3s ease;
  display: inline-block;
}

.btn-ver:hover {
  background: linear-gradient(90deg, #00c6ff, #0072ff);
  transform: scale(1.05);
  box-shadow: 0 0 10px rgba(0,114,255,0.4);
}

/* Animación del título */
@keyframes flicker {
  0%, 100% {
    opacity: 1;
    text-shadow:
      0 0 5px #ffa726,
      0 0 10px #ffa726,
      0 0 20px #ffa726;
  }
  50% {
    opacity: 0.8;
    text-shadow: none;
  }
}


/* ===== Helpers de visibilidad por breakpoint ===== */
.show-desktop { display: none; }
.show-mobile  { display: flex; }

@media (min-width: 992px){
  .show-desktop { display: block; }
  .show-mobile  { display: none; }
}

/* ===== Topbar (móvil) ===== */
.topbar{
  position: sticky;
  top: 0;
  z-index: 1000;
  height: 56px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(18,18,34,0.85);
  backdrop-filter: blur(6px);
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

.topbar__back{
  appearance: none;
  border: 0;
  outline: 0;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(66,165,245,0.15);
  color: #e0e0e0;
  font-size: 18px;
  cursor: pointer;
}
.topbar__back:hover{ background: rgba(66,165,245,0.25); }

.topbar__title{ flex: 1 1 auto; display: flex; align-items: center; }
.topbar__title .titulo{ margin: 0; font-size: 20px; }

.topbar__actions{
  display: flex;
  gap: 8px;
  align-items: center;
}
.topbar__btn{
  display: inline-block;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  color: #fff;
  background: linear-gradient(90deg, #42a5f5, #478ed1);
  box-shadow: 0 0 10px rgba(0,114,255,0.25);
  border: none;
  cursor: pointer;
}
.topbar__btn:hover{
  transform: translateY(-1px);
  box-shadow: 0 0 14px rgba(0,114,255,0.35);
}
.topbar__btn--warn{ background: linear-gradient(90deg, #f39c12, #d17224); }
.topbar__form{ margin: 0; }

/* Visibilidad por breakpoint */
.show-desktop { display: none; }
.show-mobile  { display: flex; }

@media (min-width: 992px){
  .show-desktop { display: block; }
  .show-mobile  { display: none; }
}

/* Topbar móvil */
.topbar{
  position: sticky;
  top: 0;
  z-index: 1000;
  height: 56px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(18,18,34,0.85);
  backdrop-filter: blur(6px);
  border-bottom: 1px solid rgba(255,255,255,0.08);
}
.topbar__back{
  appearance: none; border: 0; outline: 0;
  width: 40px; height: 40px; border-radius: 10px;
  background: rgba(66,165,245,0.15); color: #e0e0e0; font-size: 18px; cursor: pointer;
}
.topbar__back:hover{ background: rgba(66,165,245,0.25); }
.topbar__title{ flex: 1 1 auto; display: flex; align-items: center; }
.topbar__title .titulo{ margin: 0; font-size: 20px; }
.topbar__actions{ display: flex; gap: 8px; align-items: center; }
.topbar__btn{
  display: inline-block; padding: 8px 12px; border-radius: 8px;
  font-size: 13px; font-weight: 600; text-decoration: none; color: #fff;
  background: linear-gradient(90deg, #42a5f5, #478ed1);
  box-shadow: 0 0 10px rgba(0,114,255,0.25); border: none; cursor: pointer;
}
.topbar__btn:hover{ transform: translateY(-1px); box-shadow: 0 0 14px rgba(0,114,255,0.35); }
.topbar__btn--warn{ background: linear-gradient(90deg, #f39c12, #d17224); }
.topbar__form{ margin: 0; }

/* Sidebar escritorio */
.sidebar{
  position: fixed; top: 0; left: 0; width: 260px; height: 100vh;
  padding: 20px; background: rgba(18,18,34,0.96);
  border-right: 1px solid rgba(255,255,255,0.06);
  box-shadow: var(--shadow-1, 0 8px 24px rgba(0,0,0,.35));
  overflow-y: auto; z-index: 900;
}
.sidebar ul{ list-style: none; margin: 0; padding: 0; }
.sidebar li{ margin-bottom: 10px; }
.sidebar a, .sidebar input[type="submit"]{
  display: block; width: 100%; padding: 10px 12px; border-radius: 10px;
  background: rgba(66,165,245,0.12); color: #e0e0e0;
  text-decoration: none; border: none; text-align: left; cursor: pointer;
}
.sidebar a:hover, .sidebar input[type="submit"]:hover{
  background: rgba(66,165,245,0.22);
}

/* Layout del contenido */
.agenda-container{
  padding: 20px 16px;   /* base móvil */
  margin-left: 0;       /* base móvil */
}

/* espacio para la topbar en móvil */
@media (max-width: 991px){
  .agenda-container{ padding-top: 72px; }
}

/* espacio para el sidebar en escritorio */
@media (min-width: 992px){
  .agenda-container{
    margin-left: 260px; /* igual al ancho del sidebar */
    padding: 30px;
  }
}

/* ===== FIX VISIBILIDAD (colocar AL FINAL del CSS) ===== */
.show-desktop { display: none !important; }   /* sidebar oculto por defecto (móvil) */
.show-mobile  { display: flex !important; }   /* header visible por defecto (móvil) */

@media (min-width: 992px){
  .show-desktop { display: block !important; } /* sidebar visible en escritorio */
  .show-mobile  { display: none !important; }  /* header oculto en escritorio */
}

/* Opcional: asegura layout correcto */
@media (max-width: 991px){
  .agenda-container{ margin-left: 0 !important; padding-top: 72px !important; }
}
@media (min-width: 992px){
  .agenda-container{ margin-left: 260px !important; padding-top: 0 !important; }
}

/* ===== Ajuste del contenedor derecho (ESCRITORIO) ===== */
@media (min-width: 992px){
  /* separa el contenido del sidebar y dale respiración lateral */
  .agenda-container{
    margin-left: 260px;          /* ancho del sidebar */
    padding: 32px 48px 48px;     /* top / lados / bottom */
    min-height: 100vh;
    box-sizing: border-box;
  }

  /* centra y limita el ancho de lo que va dentro para que no se “pegue” a los bordes */
  .agenda-timeline,
  .agenda-container > .titulo,
  .agenda-container > .titulo2{
    max-width: 1120px;           /* ajusta a tu gusto: 960–1280px */
    margin-left: auto;
    margin-right: auto;
  }

  /* un pelín de sangría visual para las tarjetas dentro de cada bloque */
  .bloque-horario{
    padding-left: 24px;
    padding-right: 24px;
  }
}

/* ===== Ajuste del contenedor derecho (MÓVIL) ===== */
@media (max-width: 991px){
  .agenda-container{
    margin-left: 0;
    padding: 20px 16px 32px;     /* top / lados / bottom */
  }
  .agenda-timeline,
  .agenda-container > .titulo,
  .agenda-container > .titulo2{
    max-width: 100%;
    margin-left: 0;
    margin-right: 0;
  }
}


</style>

    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">
</head>
 
<body class = 'fade-in'>

<!-- TOPBAR (solo móvil) -->
<header class="topbar show-mobile">
  <?php if ($_SESSION["Rol"] === "Admin"): ?>
  <button class="topbar__back" type="button" onclick="history.back()" aria-label="Volver">←</button>
     <!-- <a class="topbar__btn" href="Evento_inicio.php?id=<?php echo $id; ?>">Volver</a>-->
    <?php endif; ?>
  <div class="topbar__title"><h2 class="titulo">Horario Día</h2></div>
  <div class="topbar__actions">
    
  
    <?php if ($_SESSION["Rol"] === "Evento"): ?>
      <form action="logout.php" method="post" class="topbar__form">
        <button type="submit" class="topbar__btn topbar__btn--warn">Cerrar sesión</button>
      </form>
    <?php endif; ?>
  </div>
</header>

<!-- SIDEBAR (solo escritorio) -->
<div class="sidebar show-desktop">
  <ul>
    <?php if ($_SESSION["Rol"] === "Admin"): ?>
      <li><a href="Evento_inicio.php?id=<?php echo $id; ?>">Volver</a></li>
    <?php endif; ?>
    <?php if ($_SESSION["Rol"] === "Evento"): ?>
      <li class="logout-button">
        <form action="logout.php" method="post">
          <input type="submit" value="Cerrar sesión">
        </form>
      </li>
    <?php endif; ?>
  </ul>
</div>


<div class="agenda-container">
    <h2 class="titulo">Agenda del Día</h2>
    <h3 class="titulo2">
        <?php echo htmlspecialchars($fecha_actual); ?>
    </h3>
    <div class="agenda-timeline">




 <?php foreach ($agenda_matriz as $Horario => $actividades): ?>
  <div class="bloque-horario">
    <div class="hora-label">🕒 <?= htmlspecialchars($Horario) ?></div>
    <div class="actividades-linea">
      <?php foreach ($actividades as $salon => $info): 
          $actividad = htmlspecialchars($info['Actividad']);
          $id_clase  = $info['ID'];
      ?>










            <div class="card-actividad">
              <div class="salon-label">📍 <?= htmlspecialchars($salon) ?></div>
              <div class="contenido-actividad"><?= $actividad ?></div>
              <a class="btn-ver" href="Clase.php?id=<?= $id_clase ?>">Ver clase</a>
            </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

</div>
<!--<script src="animacion.js"></script>-->
</body>
</html>
