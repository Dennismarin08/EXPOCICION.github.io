<?php
require_once 'db.php';
require_once 'puntos-functions.php';

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);
$nivelInfo = obtenerInfoNivel($user['nivel'] ?? 'bronce');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diario de Salud - RUGAL</title>
    <?php include 'pwa-head.php'; ?>
    <?php include 'pwa-head.php'; ?>
    <?php include 'pwa-head.php'; ?>
    <?php include 'pwa-head.php'; ?>
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="css/dashboard-colors.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Diario de Salud 📖</h1>
                <div class="breadcrumb">
                    <span>Dashboard</span> <i class="fas fa-chevron-right"></i> <span>Salud</span> <i class="fas fa-chevron-right"></i> <span>Diario</span>
                </div>
            </div>
            <div class="header-right">
                 <div class="nivel-badge">
                    <?php echo $nivelInfo['icono']; ?> Nivel <?php echo $nivelInfo['nombre']; ?>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
             <div class="card">
                <div class="card-header">
                    <h3>Entradas del Diario</h3>
                    <button class="btn-add"><i class="fas fa-plus"></i> Nueva Entrada</button>
                </div>
                <div class="empty-state">
                    <i class="fas fa-book-medical"></i>
                    <p>No hay entradas en el diario.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
