<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de veterinaria
checkRole('veterinaria');

$userId = $_SESSION['user_id'];

// Obtener info veterinaria
$stmt = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND tipo = 'veterinaria'");
$stmt->execute([$userId]);
$vet = $stmt->fetch();

if (!$vet) {
    header("Location: vet-dashboard.php");
    exit;
}

$vetId = $vet['id'];

// Obtener pacientes (mascotas) únicas que han agendado citas
$stmt = $pdo->prepare("
    SELECT DISTINCT m.id as mascota_id, m.nombre, m.raza, m.foto_perfil as mascota_foto, m.peso, m.edad_anios, m.edad_meses,
    u.id as dueno_id, u.nombre as dueno_nombre, u.email as dueno_email, u.telefono as dueno_telefono,
    (SELECT COUNT(*) FROM citas c2 WHERE c2.mascota_id = m.id AND c2.veterinaria_id = ?) as total_citas,
    (SELECT MAX(fecha_hora) FROM citas c3 WHERE c3.mascota_id = m.id AND c3.veterinaria_id = ?) as ultima_cita
    FROM mascotas m
    JOIN usuarios u ON m.user_id = u.id
    JOIN citas c ON m.id = c.mascota_id
    WHERE c.veterinaria_id = ? AND c.estado IN ('confirmada', 'completada')
    ORDER BY ultima_cita DESC
");
$stmt->execute([$vetId, $vetId, $vetId]);
$pacientes = $stmt->fetchAll();

// Si no hay pacientes reales, mostrar mensaje vacío
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Clientes - RUGAL Veterinaria</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 350px));
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }
        
        @media (min-width: 769px) {
            .clients-grid {
                justify-content: start;
            }
        }
        
        .client-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s;
        }
        
        @media (max-width: 480px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .client-card {
                flex-direction: column;
                text-align: center;
                padding: 20px 15px;
            }
        }
        
        .client-card:hover { transform: translateY(-5px); }
        
        .client-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #64748b;
            object-fit: cover;
        }
        
        .client-info h3 {
            margin: 0 0 5px 0;
            color: #1e293b;
        }
        
        .client-details {
            font-size: 13px;
            color: #64748b;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .client-stats {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
            font-size: 13px;
            font-weight: 600;
            color: #00b09b;
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Mis Pacientes</h1>
                <div class="breadcrumb">
                    <span>Veterinaria</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Pacientes Regulados</span>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="clients-grid">
                <?php if (empty($pacientes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-paw" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3>Aún no tienes pacientes registrados</h3>
                        <p>Las mascotas que asistan a citas contigo aparecerán aquí.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pacientes as $paciente): ?>
                        <div class="client-card">
                            <?php if ($paciente['mascota_foto']): ?>
                                <?php 
                                    $foto = $paciente['mascota_foto'];
                                    if (strpos($foto, 'http') !== 0 && strpos($foto, 'uploads/') !== 0) {
                                        $foto = '../uploads/' . ltrim($foto, '/');
                                    } elseif (strpos($foto, 'uploads/') === 0) {
                                        $foto = '../' . $foto;
                                    }
                                ?>
                                <img src="<?php echo htmlspecialchars($foto); ?>" class="client-avatar" alt="Foto Paciente">
                            <?php else: ?>
                                <div class="client-avatar" style="background: #7c3aed; color: white;">
                                    <i class="fas fa-paw"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="client-info">
                                <h3><?php echo htmlspecialchars($paciente['nombre']); ?> <span style="font-size:12px; font-weight:normal; color:#64748b;">(<?php echo htmlspecialchars($paciente['raza']); ?>)</span></h3>
                                <div class="client-details">
                                    <span><i class="fas fa-user"></i> Dueño: <?php echo htmlspecialchars($paciente['dueno_nombre']); ?></span>
                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($paciente['dueno_telefono'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="client-stats" style="display: flex; justify-content: space-between; align-items: center;">
                                    <span><i class="fas fa-calendar-check"></i> <?php echo $paciente['total_citas']; ?> Citas</span>
                                    <a href="vet-paciente.php?id=<?php echo $paciente['mascota_id']; ?>&tab=ficha" style="background:#3b82f6; color:white; padding:4px 10px; border-radius:6px; text-decoration:none; font-weight:600;"><i class="fas fa-eye"></i> Ver Ficha</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
