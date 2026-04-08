<?php
// Función para marcar el item activo en el menú
if (!function_exists('isVetActive')) {
    function isVetActive($pageName) {
        return (strpos($_SERVER['PHP_SELF'], $pageName) !== false) ? 'active' : '';
    }
}
?>
<div class="sidebar">
    <div class="logo">
        <div class="logo-icon">
            <i class="fas fa-hand-holding-medical"></i>
        </div>
        <div class="logo-text">RUGAL VET</div>
        <div class="logo-subtitle">Panel Profesional</div>
    </div>
    
    <div class="sidebar-section">
        <div class="section-title">PRINCIPAL</div>
        <a href="vet-dashboard.php" class="menu-item <?php echo isVetActive('vet-dashboard.php'); ?>">
            <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
        </a>
        <a href="vet-citas.php" class="menu-item <?php echo isVetActive('vet-citas.php'); ?>">
            <i class="fas fa-calendar-check"></i> <span>Mis Citas</span>
        </a>
        <a href="vet-clientes.php" class="menu-item <?php echo isVetActive('vet-clientes.php'); ?>">
            <i class="fas fa-paw"></i> <span>Pacientes</span>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-title">ADMINISTRACIÓN</div>
        <a href="vet-servicios.php" class="menu-item <?php echo isVetActive('vet-servicios.php'); ?>">
            <i class="fas fa-stethoscope"></i> <span>Servicios</span>
        </a>
        <a href="vet-productos.php" class="menu-item <?php echo isVetActive('vet-productos.php'); ?>">
            <i class="fas fa-pills"></i> <span>Productos</span>
        </a>
        <a href="vet-promociones.php" class="menu-item <?php echo isVetActive('vet-promociones.php'); ?>">
            <i class="fas fa-tag"></i> <span>Promociones</span>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-title">CONFIGURACIÓN</div>
        <a href="vet-horarios.php" class="menu-item <?php echo isVetActive('vet-horarios.php'); ?>">
            <i class="fas fa-clock"></i> <span>Horarios</span>
        </a>
        <a href="vet-perfil.php" class="menu-item <?php echo isVetActive('vet-perfil.php'); ?>">
            <i class="fas fa-user-md"></i> <span>Mi Perfil</span>
        </a>
    </div>

    <div class="sidebar-section">
        <a href="../logout.php" class="menu-item logout">
            <i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span>
        </a>
    </div>
</div>
<div class="sidebar-overlay" onclick="document.querySelector('.sidebar').classList.remove('active'); this.classList.remove('active');"></div>