<?php

ini_set('session.cookie_httponly', true); // Sólo permitir cookies de sesión vía HTTP
ini_set('session.cookie_secure', true); // Solo enviar cookies de sesión a través de conexiones HTTPS
session_name("CON");
session_start();
header('Content-Type: text/html; charset=UTF-8');
$id_evento_preseleccionado = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar si el usuario no está logeado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {

    // Si no está logeado, redirigir al formulario de inicio de sesión
    header("location: /Congreso/Sesion/login.html");
    exit;
}


require_once __DIR__ . "/Conexiones/Conexion.php";

// Recuperar eventos activos (EN CURSO o ACTIVO)
$sql = "SELECT ID, name_evento, capacidad FROM evento WHERE UPPER(TRIM(estado)) IN ('EN CURSO', 'ACTIVO')";
$result = $conn->query($sql);


/*/ Recuperar eventos en curso
$sql = "SELECT ID, name_evento FROM evento WHERE estado = 'EN CURSO'";
$result = $conn->query($sql);*/

// Cerrar conexión más tarde
// $conn->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles3.css?v=2">
    <?php include "header_css.php"; ?>
    <link rel="icon" href="/Congreso/educacion.png" type="image/x-icon">

    <title>Nuevo Participante</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <style>
  .slot{
    display:flex; align-items:center; gap:10px;
    border:1px solid rgba(255,255,255,.12); border-left:6px solid var(--theme-primary, #1ca9dc);
    padding:10px 12px; border-radius:10px; margin:8px 0;
    background:rgba(8,27,50,.82); transition:.2s ease; cursor:pointer;
  }
  .slot:hover{ box-shadow:0 4px 14px rgba(0,0,0,.06); }
  .slot.is-selected{ border-color:var(--theme-title, #7cecff); border-left-color:var(--theme-title, #7cecff); box-shadow:0 0 0 1px rgba(56,217,255,.18); }
  .slot.is-disabled{
    opacity:.45; filter:grayscale(1); background:rgba(255,255,255,.08); cursor:not-allowed;
  }
  .slot.is-disabled *{ pointer-events:none; }
  .slot.exclusiva{ border-left-color:#b91c1c; }
  .badge-exclusiva{
    margin-left:auto; font-size:12px; font-weight:700;
    background:var(--theme-accent, #38d9ff); color:#05273a; padding:4px 8px; border-radius:999px;
    display:inline-flex; align-items:center; gap:6px;
  }
  
  .badge-cupo{
  margin-left:auto; font-size:12px; font-weight:700;
  background:var(--theme-primary, #1ca9dc); color:#fff; padding:4px 8px; border-radius:999px;
  display:inline-flex; align-items:center; gap:6px;
}
.badge-cupo.lleno{ background:#dc2626; }
.slot.lleno{ border-left-color:#dc2626; }

.corner-left-bottom {
  list-style: none; /* quita viñeta */
  margin: 0;
  padding: 0;
}

.corner-left-bottom .btn-volver {
  display: inline-block;
  padding: 10px 20px;
  background: linear-gradient(90deg, var(--theme-primary-dark, #054a6b), var(--theme-primary, #1ca9dc));
  color: #fff;
  font-weight: bold;
  border-radius: 8px;
  text-decoration: none;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  transition: all 0.3s ease;
}

.corner-left-bottom .btn-volver:hover {
  background: linear-gradient(90deg, var(--theme-primary, #1ca9dc), var(--theme-title, #7cecff));
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0,0,0,0.4);
}

.corner-left-bottom .btn-volver:active {
  transform: translateY(0);
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}
#btnContinuar, #btnVolver, #btnGuardar{
  color:#fff;
  border:none;
  border-radius:8px;
  padding:10px 20px;
  font-size:16px;
  cursor:pointer;
  transition: background-color .25s ease, transform .15s ease, opacity .2s ease;
}
#btnContinuar{
  padding:12px 24px;
  background: linear-gradient(90deg, var(--theme-primary-dark, #054a6b), var(--theme-primary, #1ca9dc));
}
#btnVolver{
  background-color:var(--theme-primary-dark, #054a6b);
}
#btnGuardar{
  background-color:var(--verde, #0ea5c6);
}
#btnContinuar:hover, #btnVolver:hover, #btnGuardar:hover{
  background-color:var(--theme-primary-dark, #054a6b);
  transform: translateY(-1px);
}
#btnGuardar:disabled{
  cursor:not-allowed;
  opacity:.7;
}</style>

    <style>
  /* ... existing styles ... */
  
  /* BALANCEAR EL CENTRADO IGNORANDO EL SIDEBAR EN PC */
  @media (min-width: 768px) {
    body {
        /* El cuerpo ya tiene padding-left: 280px del global */
        /* Añadimos padding-right: 280px para que el AREA DE CONTENIDO esté centrada en la pantalla */
        padding-right: var(--sidebar-width, 280px) !important;
    }
  }

  /* Asegurar que el contenedor ocupe todo el ancho centrado */
  .reg-container {
    width: 100% !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    min-height: 100vh;
    padding: 60px 20px;
    margin: 0 !important;
  }
  
  .header, form {
    width: 100%;
    max-width: 650px;
  }
  </style>

</head>

<body class="fade-in">
    <?php include "sidebar.php"; ?>
    <!-- Unique container to avoid global .container conflicts -->
    <div class="reg-container" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start; min-height: 100vh; padding: 40px 20px; width: 100%; margin: 0 !important;">
        <header class="header" style="width: 100%; max-width: 650px; border-radius: 12px; margin: 0 0 24px 0 !important; padding: 24px; background: linear-gradient(145deg, var(--theme-primary-dark), var(--theme-primary));">
            <div class="logo">Agregar Participante</div>
            <nav class="navbar">
                <ul>
                    <li class="corner-left-bottom">
                        <a href="javascript:history.back()" class="btn-volver">← Volver</a>
                    </li>
                </ul>
            </nav>
        </header>

        <form action="Funcion_Agregar_Participante.php" method="POST" style="margin: 0 !important; width: 100%; max-width: 650px; background-color: var(--theme-surface-strong); border-radius: 12px; padding: 32px; box-shadow: var(--theme-shadow); border: 1px solid var(--theme-border);">
            <div id="step1">
                <!-- ... existing content ... -->
                <label for="Evento">Evento:</label>
                <select id="Evento" name="Evento" required onchange="updateParticipantes()">
                    <option value="">Selecciona una opción</option>
                    <?php
                    if (isset($result) && $result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $selected = ((int)$row["ID"] === $id_evento_preseleccionado) ? " selected" : "";
                            echo "<option value='" . $row["ID"] . "' data-capacidad='" . $row["capacidad"] . "'" . $selected . ">" . $row["name_evento"] . "</option>";
                        }
                    } else {
                        echo "<option value=''>No hay eventos disponibles</option>";
                    }
                    ?>
                </select><br><br>

                <p id="participantes-info"></p>

                <label for="sucursal">Sucursal:</label><br>
                <select id="sucursal" name="sucursal" required>
                    <option value="">Selecciona una sucursal</option>
                    <option value="DIMEGSA">DIMEGSA</option>
                    <option value="DEASA">DEASA</option>
                    <option value="AIESA">AIESA</option>
                    <option value="SEGSA">SEGSA</option>
                    <option value="FESA">FESA</option>
                    <option value="TAPATIA">TAPATIA</option>
                    <option value="GABSA">GABSA</option>
                    <option value="ILUMINACION">ILUMINACION</option>
                    <option value="VALLARTA">VALLARTA</option>
                    <option value="QUERETARO">QUERETARO</option>
                    <option value="CODI">CODI</option>
                </select><br><br>

                <label for="Vendedor">Vendedor:</label>
                <input type="text" id="Vendedor" name="Vendedor" required placeholder="Tu Nombre y Apellido"><br><br>

                <label for="Nombre">Nombre del participante:</label>
                <input type="text" id="Nombre" name="Nombre" required><br><br>

                <label for="Proveedor">Razon Social (cliente):</label>
                <input type="text" id="Proveedor" name="Proveedor" required><br><br>

                <label for="rfc">RFC de la razon social (cliente):</label>
                <input type="text" id="rfc" name="rfc" required maxlength="20" placeholder="Recuerda Capturar CORRECTO este dato"><br><br>

                <label for="puesto">Puesto:</label>
                <select id="puesto" name="puesto" required>
                    <option value="">Selecciona un puesto</option>
                    <option value="Ingeniero">Ingeniero</option>
                    <option value="Electricista">Electricista</option>
                    <option value="Ayudante">Ayudante</option>
                    <option value="Supervisor">Supervisor</option>
                    <option value="Compras">Compras</option>
                    <option value="Mantenimiento">Mantenimiento</option>
                    <option value="Jefe de Área">Jefe de Área</option>
                    <option value="Otro">Otro</option>
                </select><br><br>

                <label for="Telefono">Teléfono:</label>
                <input type="tel" id="Telefono" name="Telefono" required pattern="[0-9]{10}" maxlength="10" title="Ingresa un número de 10 dígitos" placeholder="Recuerda Capturar CORRECTO este dato">
                <br><br>
                
                <button type="button" id="btnContinuar">
                    Continuar → Seleccionar actividades
                </button>
            </div>

            <div id="step2" style="display:none; margin-top:24px;">
                <h3>Selecciona actividades (se bloquearán los solapes)</h3>
                <div id="agendaContainer" style="margin:12px 0;"></div>
                <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
                    <button type="button" id="btnVolver">← Volver</button>
                    <button type="submit" id="btnGuardar" disabled style="cursor:not-allowed;opacity:.7;">Guardar participante</button>
                </div>
            </div>
        </form>
    </div>

    <script>
      $(function () {
        if ($('#Evento').val()) {
          updateParticipantes();
        }
      });

       function updateParticipantes() {
  var eventoID = $('#Evento').val();
  var capacidad = $('#Evento option:selected').data('capacidad') || 0;

  $.ajax({
    url: 'get_participantes.php',
    type: 'POST',
    data: { evento_id: eventoID },
    success: function (data) {
      var participantes = parseInt(data || '0', 10);
      var disponibles = capacidad - participantes;

      $('#participantes-info').text(
        'Participantes registrados: ' + participantes + ' / ' + capacidad + '. Quedan ' + disponibles + ' espacios disponibles.'
      );

      const lleno = disponibles <= 0;

      // Bloquea avanzar si está lleno
      $('#btnContinuar').prop('disabled', lleno);

      // Asegura que Guardar esté deshabilitado en step2 hasta que haya clases
      $('#btnGuardar').prop('disabled', true).css({ cursor:'not-allowed', opacity:.7 });

      if (lleno) alert('Este evento ha alcanzado su capacidad máxima.');
    }
  });
}

    </script>

    <!-- ... encabezado y PHP igual ... -->

   

    <script>
        // Paso 1 -> Paso 2
        document.getElementById('btnContinuar').addEventListener('click', function () {
            // Validaciones mínimas
            const req = ['Evento', 'sucursal', 'Vendedor', 'Nombre', 'Proveedor', 'rfc', 'puesto', 'Telefono'];
            for (const id of req) {
                const el = document.getElementById(id);
                if (!el || !el.value.trim()) { el.focus(); return; }
            }

            const eventoID = document.getElementById('Evento').value;
            // Cargar agenda real del evento (sin 'Vacio')
            $('#agendaContainer').load('get_agenda_evento.php?evento=' + encodeURIComponent(eventoID), function () {
                // Activar bloqueo de solapes cuando ya esté cargada
                activarBloqueoSolapes();
                 engancharChecksAgenda();   // <<--- AÑADE ESTA LÍNEA
    actualizarBotonGuardar();
            });

            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
        });

        // Volver
        document.getElementById('btnVolver').addEventListener('click', function () {
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step1').style.display = 'block';
        });

        // Bloqueo de solapes en cliente
          function toMin(h){ const [H,M]=h.split(':').map(Number); return H*60+M; }
  function parseRange(r){ const [a,b]=r.split('-'); return [toMin(a.trim()), toMin(b.trim())]; }

  function activarBloqueoSolapes() {
    const chks = document.querySelectorAll('.chk-slot');

    const setDisabledUI = (chk, disabled) => {
      const card = chk.closest('.slot');
      if (!card) return;
      chk.disabled = disabled;
      card.classList.toggle('is-disabled', disabled);
    };
    const setSelectedUI = (chk, selected) => {
      const card = chk.closest('.slot');
      if (!card) return;
      card.classList.toggle('is-selected', selected);
    };

    function refreshLocks() {
        chks.forEach(c => {
    if (c.dataset.lock === 'capacity') {
      // mantener bloqueado por cupo
      c.disabled = true;
      c.closest('.slot')?.classList.add('lleno','is-disabled');
      return;
    }
    setDisabledUI(c, false);
    setSelectedUI(c, c.checked);
  });
      const chosenByDate = {};
      chks.forEach(c => {
        if (!c.checked) return;
        const f = c.dataset.fecha;
        const [i, fM] = parseRange(c.dataset.horario);
        (chosenByDate[f] ||= []).push([i, fM]);
      });

      chks.forEach(c => {
        if (c.checked) return;
        const f = c.dataset.fecha;
        if (!chosenByDate[f]) return;
        const [i, fM] = parseRange(c.dataset.horario);
        const clash = chosenByDate[f].some(([a,b]) => (i < b && a < fM));
        if (clash) setDisabledUI(c, true);
      });
    }

    chks.forEach(c => c.addEventListener('change', refreshLocks));
    refreshLocks();
  }
    </script>
<script>
  function actualizarBotonGuardar(){
    const seleccionados = document.querySelectorAll('.chk-slot:checked').length;
    const btn = document.getElementById('btnGuardar');
    if (!btn) return;
    btn.disabled = seleccionados === 0;
    btn.style.cursor = seleccionados === 0 ? 'not-allowed' : 'pointer';
    btn.style.opacity = seleccionados === 0 ? '.7' : '1';
  }

  function engancharChecksAgenda(){
    document.querySelectorAll('.chk-slot').forEach(chk=>{
      chk.addEventListener('change', actualizarBotonGuardar);
    });
    actualizarBotonGuardar();
  }

  // Blindaje final del submit
  document.querySelector('form').addEventListener('submit', function(e){
    const tiene = document.querySelectorAll('.chk-slot:checked').length > 0;
    if (!tiene){
      e.preventDefault();
      alert('Debes seleccionar al menos una actividad para guardar el participante.');
    }
  });
</script>

</body>
<?php if(isset($conn)) $conn->close(); ?>
</html>


