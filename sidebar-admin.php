<?php
// Helper para clase activa
if (!function_exists('isAdminActive')) {
    function isAdminActive($pageName) {
        return (strpos($_SERVER['PHP_SELF'], $pageName) !== false) ? 'active' : '';
    }
}
?>
<div class="sidebar">
    <div class="logo">
        <div class="logo-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="logo-text">RUGAL ADMIN</div>
        <div class="logo-subtitle">Panel de Control</div>
    </div>
    
    <div class="sidebar-section">
        <div class="section-title">GENERAL</div>
        <a href="admin-dashboard.php" class="menu-item <?php echo isAdminActive('admin-dashboard.php'); ?>">
            <i class="fas fa-chart-line"></i> <span>Dashboard</span>
        </a>
        <a href="admin-config.php" class="menu-item <?php echo isAdminActive('admin-config.php'); ?>">
            <i class="fas fa-cogs"></i> <span>Configuración</span>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-title">USUARIOS Y ALIADOS</div>
        <a href="admin-usuarios.php" class="menu-item <?php echo isAdminActive('admin-usuarios.php'); ?>">
            <i class="fas fa-users"></i> <span>Usuarios</span>
        </a>
        <a href="admin-aliados.php" class="menu-item <?php echo isAdminActive('admin-aliados.php'); ?>">
            <i class="fas fa-store"></i> <span>Aliados</span>
        </a>
        <a href="admin-membresias.php" class="menu-item <?php echo isAdminActive('admin-membresias.php'); ?>">
            <i class="fas fa-crown"></i> <span>Membresías</span>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-title">GAMIFICACIÓN</div>
        <a href="admin-tareas-gestion.php" class="menu-item <?php echo isAdminActive('admin-tareas-gestion.php'); ?>">
            <i class="fas fa-tasks"></i> <span>Tareas</span>
        </a>
        <a href="admin-tareas-validacion.php" class="menu-item <?php echo isAdminActive('admin-tareas-validacion.php'); ?>">
            <i class="fas fa-check-double"></i> <span>Validación</span>
        </a>
        <a href="admin-recompensas.php" class="menu-item <?php echo isAdminActive('admin-recompensas.php'); ?>">
            <i class="fas fa-gift"></i> <span>Recompensas</span>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-title">CONTENIDO</div>
        <a href="admin-educacion.php" class="menu-item <?php echo isAdminActive('admin-educacion.php'); ?>">
            <i class="fas fa-graduation-cap"></i> <span>Educación</span>
        </a>
        <a href="admin-reporte.php" class="menu-item <?php echo isAdminActive('admin-reporte.php'); ?>">
            <i class="fas fa-file-alt"></i> <span>Reportes</span>
        </a>
    </div>

    <div class="sidebar-section">
        <a href="../logout.php" class="menu-item logout">
            <i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span>
        </a>
    </div>
</div>