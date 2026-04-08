<?php
/**
 * RUGAL - Store Sidebar Component
 * Centralized navigation for Store pages
 */

if (!function_exists('isActive')) {
    function isActive($pageName) {
        return (strpos($_SERVER['PHP_SELF'], $pageName) !== false) ? 'active' : '';
    }
}
?>

<div class="sidebar">
    <div class="logo">
        <div class="logo-icon" style="background: var(--p-gradient);">
            <i class="fas fa-store"></i>
        </div>
        <div class="logo-text">RUGAL STORE</div>
        <div class="logo-subtitle">Panel Tienda</div>
    </div>
    
    <div class="sidebar-section">
        <div class="section-title">MI TIENDA</div>
        <a href="tienda-dashboard.php" class="menu-item <?php echo isActive('tienda-dashboard.php'); ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="tienda-productos.php" class="menu-item <?php echo isActive('tienda-productos.php'); ?>">
            <i class="fas fa-box"></i>
            <span>Productos</span>
        </a>
        <a href="tienda-inventario.php" class="menu-item <?php echo isActive('tienda-inventario.php'); ?>">
            <i class="fas fa-warehouse"></i>
            <span>Inventario</span>
        </a>
        <a href="tienda-horarios.php" class="menu-item <?php echo isActive('tienda-horarios.php'); ?>">
            <i class="fas fa-clock"></i>
            <span>Horarios</span>
        </a>
    </div>
    
    <div class="sidebar-section">
        <div class="section-title">MI CUENTA</div>
        <a href="tienda-perfil.php" class="menu-item <?php echo isActive('tienda-perfil.php'); ?>">
            <i class="fas fa-user-circle"></i>
            <span>Mi Perfil</span>
        </a>
        <a href="<?php echo (defined('BASE_URL') ? BASE_URL : ''); ?>/logout.php" class="menu-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>
