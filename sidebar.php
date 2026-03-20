<?php
/**
 * Sidebar Centralizado
 * Gestiona la navegación principal y las restricciones de roles.
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$sidebar_event_id_override = isset($sidebar_event_id) ? (int)$sidebar_event_id : 0;
$id_evento_sidebar = $sidebar_event_id_override > 0
    ? $sidebar_event_id_override
    : (($currentPage === 'Clase.php')
        ? (isset($id_evento) ? (int)$id_evento : 0)
        : (isset($_GET['id']) ? intval($_GET['id']) : (isset($id_evento) ? (int)$id_evento : 0)));
$rol_sidebar = $_SESSION["Rol"] ?? '';

// Función auxiliar para clase activa
function isActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>

<button id="sidebar-toggle" class="sidebar-toggle">
    <span></span>
    <span></span>
    <span></span>
</button>

<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="sidebar" id="main-sidebar">

    <div class="sidebar-logo">
        <a href="/Congreso/index.php">
            <img src="/Congreso/img/Logo.png" alt="Logo Congreso">
        </a>
    </div>

    <?php
    $volver_url = '/Congreso/index.php'; // Por defecto
    if ($id_evento_sidebar > 0) {
        if ($currentPage === 'Evento_inicio.php') {
            $volver_url = '/Congreso/index.php';
        } else {
            $volver_url = "/Congreso/Evento_inicio.php?id=$id_evento_sidebar";
        }
    }
    
    // Si estamos en la página principal, ocultamos el botón de volver
    $hide_volver = ($currentPage === 'index.php');
    ?>

    <?php if (!$hide_volver && $rol_sidebar !== 'Vendedor'): ?>
    <div style="padding: 0 16px; margin-bottom: 16px;">
        <a href="<?= $volver_url ?>" class="sidebar-link-btn" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--theme-border);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Volver
        </a>
    </div>
    <?php endif; ?>

    <div class="sidebar-sections">
        <?php if ($rol_sidebar === 'Vendedor'): ?>
            <!-- VISTA SIMPLIFICADA PARA VENDEDOR -->
            <div class="sidebar-section">
                <ul class="section-content open" style="opacity: 1; max-height: none;">
                    <li>
                        <a href="/Congreso/Agregar_Participante.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('Agregar_Participante.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
                            Agregar participante
                        </a>
                    </li>
                    <li>
                        <a href="/Congreso/Participantes.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('Participantes.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            Ver Participantes
                        </a>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <!-- VISTA COMPLETA PARA ADMIN -->
            <!-- SECCIÓN: ADMINISTRACIÓN -->
            <?php if ($rol_sidebar === 'Admin'): ?>
            <div class="sidebar-section">
                <div class="section-header <?= ($id_evento_sidebar == 0) ? 'active' : '' ?>" onclick="toggleSection('admin-links', this)">
                    <span>ADMINISTRACIÓN</span>
                    <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <ul id="admin-links" class="section-content <?= ($id_evento_sidebar == 0) ? 'open' : '' ?>">
                    <li>
                        <a href="/Congreso/index.php" class="<?= isActive('index.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            Panel General
                        </a>
                    </li>
                    <li>
                        <a href="/Congreso/NuevoRegistroInicio.php" class="<?= isActive('NuevoRegistroInicio.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            Nuevo Evento
                        </a>
                    </li>
                    <li>
                        <a href="/Congreso/Ubicacion.php" class="<?= isActive('Ubicacion.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            Ubicaciones
                        </a>
                    </li>
                    <li>
                        <a href="/Congreso/puntos_proveedor/agregar_proveedor_evento.php" class="<?= isActive('agregar_proveedor_evento.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Añadir Proveedores
                        </a>
                    </li>
                    <li>
                        <a href="/Congreso/personalizar.php" class="<?= isActive('personalizar.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                            Personalizar
                        </a>
                    </li>
                    <li class="sidebar-form-item">
                        <form action="/Congreso/Registrar/" method="post">
                             <button type="submit" class="sidebar-link-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
                                Nuevo Usuario
                             </button>
                        </form>
                    </li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- SECCIÓN: EVENTO ACTUAL -->
            <?php if ($id_evento_sidebar > 0): ?>
            <div class="sidebar-section">
                <div class="section-header active" onclick="toggleSection('event-links', this)">
                    <span>EVENTO ACTUAL</span>
                    <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <ul id="event-links" class="section-content open">
                    <li>
                        <a href="/Congreso/Evento_inicio.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('Evento_inicio.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            Inicio Evento
                        </a>
                    </li>
                    <li>
                        <a href="/Congreso/Agregar_Participante.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('Agregar_Participante.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
                            Añadir Participante
                        </a>
                    </li>
                    <li>
                        <a href="/Congreso/Participantes.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('Participantes.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            Ver Participantes
                        </a>
                    </li>
                    <?php if ($rol_sidebar === 'Admin'): ?>
                        <li>
                            <a href="/Congreso/participantes_rfc.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('participantes_rfc.php', $currentPage) ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Grupos (RFC)
                            </a>
                        </li>
                        <li>
                            <a href="/Congreso/participantes_puesto.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('participantes_puesto.php', $currentPage) ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                Por Puesto
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="/Congreso/Actividades.php?evento=<?= $id_evento_sidebar ?>" class="<?= isActive('Actividades.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Actividades
                        </a>
                    </li>
                    <?php if ($rol_sidebar === 'Admin'): ?>
                    <li>
                        <a href="/Congreso/editar_agenda_evento.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('editar_agenda_evento.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            Editar Agenda
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="/Congreso/Estadisticas.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('Estadisticas.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                            Estadísticas
                        </a>
                    </li>
                    <li>
                        <a href="/Congreso/premios_panel.php?id=<?= $id_evento_sidebar ?>" class="<?= isActive('premios_panel.php', $currentPage) ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
                            Panel de Premios
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <form action="/Congreso/logout.php" method="post">
            <button type="submit" class="logout-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Cerrar Sesión
            </button>
        </form>
    </div>
</div>

<script>
function toggleSection(id, header) {
    const content = document.getElementById(id);
    const isOpen = content.classList.contains('open');
    
    // Cerrar otros (opcional, si quieres que solo uno esté abierto a la vez)
    // document.querySelectorAll('.section-content').forEach(c => c.classList.remove('open'));
    // document.querySelectorAll('.section-header').forEach(h => h.classList.remove('active'));

    if (isOpen) {
        content.classList.remove('open');
        header.classList.remove('active');
    } else {
        content.classList.add('open');
        header.classList.add('active');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('main-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        toggle.classList.toggle('active');
    }
    
    if(toggle) toggle.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);
    
    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                toggleSidebar();
            }
        });
    });
});
</script>
