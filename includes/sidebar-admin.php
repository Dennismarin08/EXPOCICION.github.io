<?php
/**
 * RUGAL - Admin Sidebar Component
 * Centralized navigation for Administrator pages
 */

function isActive($pageName) {
    return (strpos($_SERVER['PHP_SELF'], $pageName) !== false) ? 'active' : '';
}
?>

<div class="sidebar">
    <div class="logo">
        <div class="logo-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="logo-text">RUGAL ADMIN</div>
        <div class="logo-subtitle">Gestión Integral</div>
    </div>

    <div class="sidebar-section">
        <div class="section-title">
            <i class="fas fa-cogs" style="margin-right:8px;"></i>
            ADMINISTRACIÓN
        </div>
        <a href="admin-dashboard.php" class="menu-item <?php echo isActive('admin-dashboard.php'); ?>">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="admin-usuarios.php" class="menu-item <?php echo isActive('admin-usuarios.php'); ?>">
            <i class="fas fa-users"></i>
            <span>Usuarios</span>
            <?php if (isset($stats['total_usuarios'])): ?>
                <span class="badge"><?php echo $stats['total_usuarios']; ?></span>
            <?php endif; ?>
        </a>
        <a href="admin-aliados.php" class="menu-item <?php echo isActive('admin-aliados.php'); ?>">
            <i class="fas fa-handshake"></i>
            <span>Aliados</span>
        </a>
        <a href="admin-educacion.php" class="menu-item <?php echo isActive('admin-educacion.php'); ?>">
            <i class="fas fa-graduation-cap"></i>
            <span>Educación</span>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-title">
            <i class="fas fa-gamepad" style="margin-right:8px;"></i>
            GAMIFICACIÓN
        </div>
        <a href="admin-tareas-gestion.php" class="menu-item <?php echo isActive('admin-tareas-gestion.php'); ?>">
            <i class="fas fa-tasks"></i>
            <span>Gestión Tareas</span>
        </a>
        <a href="admin-tareas-validacion.php" class="menu-item <?php echo isActive('admin-tareas-validacion.php'); ?>">
            <i class="fas fa-check-double"></i>
            <span>Validar Tareas</span>
        </a>
        <a href="admin-recompensas.php" class="menu-item <?php echo isActive('admin-recompensas.php'); ?>">
            <i class="fas fa-gift"></i>
            <span>Recompensas</span>
        </a>
        <a href="admin-membresias.php" class="menu-item <?php echo isActive('admin-membresias.php'); ?>">
            <i class="fas fa-crown"></i>
            <span>Membresías</span>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-title">
            <i class="fas fa-server" style="margin-right:8px;"></i>
            SISTEMA
        </div>
        <a href="admin-config.php" class="menu-item <?php echo isActive('admin-config.php'); ?>">
            <i class="fas fa-cog"></i>
            <span>Configuración</span>
        </a>
        <a href="<?php echo (defined('BASE_URL') ? BASE_URL : ''); ?>/logout.php" class="menu-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>
