<?php
/**
 * RUGAL - Sidebar Component with Collapsible Menus
 * Centralized navigation for all dashboard pages
 */

// We assume $user and $userId are already defined in the main page
// We also assume $pdo is available

// Helper for active class
function isActive($pageName) {
    if ($pageName === 'dashboard.php' && basename($_SERVER['PHP_SELF']) === 'dashboard.php') return 'active';
    return (strpos($_SERVER['PHP_SELF'], $pageName) !== false) ? 'active' : '';
}

// Check if user is premium
if (!function_exists('esPremium')) {
    require_once __DIR__ . '/../premium-functions.php';
}
$isPremium = esPremium($userId);
?>

<!-- Hamburger Menu Button (Mobile) -->
<button class="hamburger-menu" id="hamburgerBtn" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="logo">

        <div class="logo-icon">
            <img src="<?php echo (defined('BASE_URL') ? BASE_URL : ''); ?>/assets/images/logo.png" alt="RUGAL Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <div class="logo-text">RUGAL</div>
        <div class="logo-subtitle">Cuidado Integral</div>
    </div>
    
    <!-- MI MASCOTA -->
    <div class="sidebar-section">
        <div class="section-title">MI MASCOTA</div>
        <a href="dashboard.php" class="menu-item <?php echo isActive('dashboard.php'); ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="mascotas.php" class="menu-item <?php echo isActive('mascotas.php'); ?>">
            <i class="fas fa-dog"></i>
            <span>Mis Mascotas</span>
        </a>
        <a href="#" class="menu-item coming-soon" onclick="showComingSoon(event, 'Agregar Mascota')">
            <i class="fas fa-plus-circle"></i>
            <span>Agregar Mascota</span>
            <span class="badge badge-v2">v2.0</span>
        </a>
        <a href="qr.php" class="menu-item <?php echo isActive('qr.php'); ?>">
            <i class="fas fa-qrcode"></i>
            <span>QR ID</span>
        </a>
        <a href="calendario.php" class="menu-item <?php echo isActive('calendario.php'); ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Calendario</span>
        </a>
    </div>

    <!-- SALUD (Collapsible) -->
    <div class="sidebar-section">
        <div class="section-title">SALUD</div>
        <div class="menu-section">
            <div class="menu-section-header active" onclick="toggleSubmenu('salud-submenu')">
                <div class="menu-section-title">
                    <i class="fas fa-heartbeat"></i>
                    <span>Salud y Bienestar</span>
                </div>
                <i class="fas fa-chevron-down menu-section-arrow"></i>
            </div>
            <div class="menu-submenu active" id="salud-submenu">
                <a href="citas.php" class="submenu-item <?php echo isActive('citas.php'); ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Mis Citas</span>
                </a>
                <a href="<?php echo $isPremium ? 'plan-salud-mensual.php' : 'upgrade-premium.php'; ?>" class="submenu-item <?php echo isActive('plan-salud-mensual.php'); ?>">
                    <i class="fas fa-file-medical-alt"></i>
                    <span>Plan de Salud Mensual</span>
                    <span class="badge" style="background: #10b981; color: white; font-size: 9px;">Nuevo</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ALIADOS (Collapsible) -->
    <div class="sidebar-section">
        <div class="section-title">ALIADOS RUGAL</div>
        <div class="menu-section">
            <div class="menu-section-header" onclick="toggleSubmenu('aliados-submenu')">
                <div class="menu-section-title">
                    <i class="fas fa-handshake"></i>
                    <span>Red de Aliados</span>
                </div>
                <i class="fas fa-chevron-down menu-section-arrow"></i>
            </div>
            <div class="menu-submenu" id="aliados-submenu">
                <a href="aliados.php" class="submenu-item <?php echo isActive('aliados.php'); ?>">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Todos los Aliados</span>
                </a>
                <a href="aliados.php?tipo=veterinaria" class="submenu-item">
                    <i class="fas fa-hospital"></i>
                    <span>Veterinarias</span>
                </a>
                <a href="aliados.php?tipo=tienda" class="submenu-item">
                    <i class="fas fa-store"></i>
                    <span>Tiendas</span>
                </a>
            </div>
        </div>
    </div>

    <!-- GAMIFICACIÓN (Collapsible) -->
    <div class="sidebar-section">
        <div class="section-title">GAMIFICACIÓN</div>
        <div class="menu-section">
            <div class="menu-section-header" onclick="toggleSubmenu('gamification-submenu')">
                <div class="menu-section-title">
                    <i class="fas fa-trophy"></i>
                    <span>Recompensas</span>
                </div>
                <i class="fas fa-chevron-down menu-section-arrow"></i>
            </div>
            <div class="menu-submenu" id="gamification-submenu">
                <a href="tareas.php" class="submenu-item <?php echo isActive('tareas.php'); ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Tareas Diarias</span>
                </a>
                <a href="ranking.php" class="submenu-item <?php echo isActive('ranking.php'); ?>">
                    <i class="fas fa-medal"></i>
                    <span>Ranking Global</span>
                </a>
                <a href="recompensas.php" class="submenu-item <?php echo isActive('recompensas.php'); ?>">
                    <i class="fas fa-gift"></i>
                    <span>Cofre de Premios</span>
                </a>
                <a href="mis-canjes.php" class="submenu-item <?php echo isActive('mis-canjes.php'); ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Mis Canjes</span>
                </a>
            </div>
        </div>
    </div>

    <!-- COMUNIDAD (Directo) -->
    <div class="sidebar-section">
        <div class="section-title">COMUNIDAD</div>
        <a href="comunidad.php" class="menu-item <?php echo isActive('comunidad.php'); ?>">
            <i class="fas fa-users"></i>
            <span>Comunidad</span>
        </a>
    </div>

    <!-- MI CUENTA -->
    <div class="sidebar-section">
        <div class="section-title">MI CUENTA</div>
        <a href="perfil.php" class="menu-item <?php echo isActive('perfil.php'); ?>">
            <i class="fas fa-user-circle"></i>
            <span>Mi Perfil</span>
        </a>
        <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'): ?>
            <a href="admin/admin-dashboard.php" class="menu-item">
                <i class="fas fa-user-shield"></i>
                <span>Panel Administrador</span>
            </a>
        <?php endif; ?>
        <a href="<?php echo (defined('BASE_URL') ? BASE_URL : ''); ?>/logout.php" class="menu-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>

    <!-- APP CONTROLS (Se muestra vía JS si aplica) -->
    <div class="sidebar-section" id="pwa-install-section" style="border-top: 1px solid rgba(255,255,255,0.1); display: block;">
        <div class="section-title" style="color: #60a5fa;">APLICACIÓN</div>
        <a href="#" class="menu-item" id="pwa-install-btn" style="display:none;">
            <i class="fas fa-download" style="color: #3b82f6;"></i>
            <span>Instalar App</span>
        </a>
        <a href="#" class="menu-item" id="pwa-notif-btn">
            <i class="fas fa-bell" style="color: #f59e0b;"></i>
            <span>Notificaciones</span>
        </a>
    </div>
</div>

<!-- Link to collapsible CSS -->
<link rel="stylesheet" href="css/sidebar-collapsible.css">

<!-- Styles for v2.0 badges -->
<style>
    .badge-v2 {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 10px;
        padding: 3px 8px;
        border-radius: 10px;
        font-weight: 600;
        margin-left: auto;
    }

    /* Estilo para los iconos de la primera sección */
    .sidebar-section:first-of-type .menu-item i {
        color: #ffffff !important;
        opacity: 0.9;
        font-size: 18px;
        filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.2));
    }

    .sidebar-section:first-of-type .menu-item:hover i {
        color: var(--p-accent);
        transform: scale(1.1);
    }
    
    .menu-item.coming-soon,
    .submenu-item.coming-soon {
        opacity: 0.7;
        cursor: pointer;
    }
    
    .menu-item.coming-soon:hover,
    .submenu-item.coming-soon:hover {
        opacity: 1;
    }
    
    /* Modal para Coming Soon */
    .modal-v2 {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }
    
    .modal-v2-content {
        background: white;
        padding: 40px;
        border-radius: 20px;
        max-width: 500px;
        text-align: center;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .modal-v2-icon {
        font-size: 64px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 20px;
    }
    
    .modal-v2-title {
        font-size: 24px;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 10px;
    }
    
    .modal-v2-text {
        color: #64748b;
        margin-bottom: 25px;
        line-height: 1.6;
    }
    
    .modal-v2-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .modal-v2-btn:hover {
        transform: translateY(-2px);
    }
    .logo-icon img {
        border-radius: 50%; /* Opcional si el logo es circular */
    }
</style>

<!-- Modal Coming Soon -->
<div id="modalV2" class="modal-v2">
    <div class="modal-v2-content">
        <div class="modal-v2-icon">
            <i class="fas fa-rocket"></i>
        </div>
        <h3 class="modal-v2-title" id="modalV2Title">Próximamente</h3>
        <p class="modal-v2-text">
            Esta funcionalidad estará disponible en la <strong>versión 2.0</strong> de RUGAL. 
            Estamos trabajando para traerte la mejor experiencia de gestión de salud para tus mascotas.
        </p>
        <p class="modal-v2-text" style="font-size: 14px;">
            <strong>Por ahora puedes usar:</strong><br>
            📅 Mis Citas - Para agendar consultas veterinarias<br>
            🐾 Mis Mascotas - Para ver y editar el perfil de tus mascotas
        </p>
        <button class="modal-v2-btn" onclick="closeModalV2()">
            <i class="fas fa-check"></i> Entendido
        </button>
    </div>
</div>

<script>
    function showComingSoon(event, featureName) {
        event.preventDefault();
        const modal = document.getElementById('modalV2');
        const title = document.getElementById('modalV2Title');
        title.textContent = featureName + ' - Próximamente en v2.0';
        modal.style.display = 'flex';
    }
    
    function closeModalV2() {
        const modal = document.getElementById('modalV2');
        modal.style.display = 'none';
    }
    
    // Cerrar modal al hacer clic fuera
    document.getElementById('modalV2')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalV2();
        }
    });
    
    // ========================================
    // COLLAPSIBLE SUBMENU FUNCTIONALITY
    // ========================================

    function toggleSubmenu(submenuId) {
        const submenu = document.getElementById(submenuId);
        const header = submenu.previousElementSibling;

        // Toggle active class
        submenu.classList.toggle('active');
        header.classList.toggle('active');

        // Close other submenus (optional - for accordion behavior)
        // Uncomment if you want only one submenu open at a time
        /*
        document.querySelectorAll('.menu-submenu').forEach(menu => {
            if (menu.id !== submenuId && menu.classList.contains('active')) {
                menu.classList.remove('active');
                menu.previousElementSibling.classList.remove('active');
            }
        });
        */
    }

    function navigateToPlanCard() {
        if (window.location.href.includes('dashboard.php')) {
            // Just scroll
            const card = document.getElementById('plan-salud-card');
            if (card) {
                card.scrollIntoView({ behavior: 'smooth' });
            }
        } else {
            // Navigate to dashboard with hash
            window.location.href = 'dashboard.php#plan-salud-card';
        }
        return false;
    }

    // Make function globally available
    window.navigateToPlanCard = navigateToPlanCard;
    
    // Auto-open submenu if current page is in it
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.submenu-item.active').forEach(item => {
            const submenu = item.closest('.menu-submenu');
            if (submenu) {
                submenu.classList.add('active');
                submenu.previousElementSibling.classList.add('active');
            }
        });


    });
    
    // ========================================
    // HAMBURGER MENU FUNCTIONALITY
    // ========================================

    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        hamburgerBtn.classList.toggle('active');
        
        // Prevent body scroll when sidebar is open
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
            sidebarOverlay.style.display = 'block';
        } else {
            document.body.style.overflow = '';
            sidebarOverlay.style.display = 'none';
        }
    }
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        hamburgerBtn.classList.remove('active');
        document.body.style.overflow = '';
        sidebarOverlay.style.display = 'none';
    }
    
    // Event listeners
    hamburgerBtn?.addEventListener('click', toggleSidebar);
    sidebarOverlay?.addEventListener('click', closeSidebar);
    
    // Close sidebar when clicking on a menu item (mobile)
    document.querySelectorAll('.menu-item, .submenu-item').forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
    
    // Close sidebar on window resize if going to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
</script>
