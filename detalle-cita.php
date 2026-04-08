<?php
require_once 'db.php';
require_once 'puntos-functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);
$nivelInfo = obtenerInfoNivel($user['nivel'] ?? 'bronce');

$citaId = $_GET['id'] ?? null;

if (!$citaId) {
    header('Location: citas.php');
    exit;
}

// Obtener detalles de la cita
$sql = "
    SELECT c.*, 
           m.nombre as mascota_nombre, m.foto_perfil as mascota_foto, m.raza as mascota_raza,
           v.nombre_local as veterinaria_nombre, v.nombre_local, v.direccion as veterinaria_direccion, 
           u_vet.telefono as veterinaria_telefono, u_vet.email as veterinaria_email
    FROM citas c
    JOIN mascotas m ON c.mascota_id = m.id
    JOIN aliados v ON c.veterinaria_id = v.id
    JOIN usuarios u_vet ON v.usuario_id = u_vet.id
    WHERE c.id = ? AND c.user_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$citaId, $userId]);
$cita = $stmt->fetch();

if (!$cita) {
    header('Location: citas.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Cita - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .details-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }

        .status-pendiente { background: #fef3c7; color: #92400e; }
        .status-confirmada { background: #d1fae5; color: #065f46; }
        .status-completada { background: #dbeafe; color: #1e40af; }
        .status-cancelada { background: #fee2e2; color: #991b1b; }

        .info-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-block h3 {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            align-items: center;
            gap: 10px;
        }

        .info-row i {
            color: #667eea;
            width: 20px;
            text-align: center;
        }

        .mascota-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
        }

        .mascota-img {
            width: 50px;
            object-fit: contain;
            background: var(--bg-card);
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Detalles de Cita</h1>
                <div class="breadcrumb">
                    <span>Citas</span> <i class="fas fa-chevron-right"></i> <span>#<?php echo $cita['id']; ?></span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-outline" onclick="window.history.back()">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="details-card">
                <div class="status-header">
                    <div>
                        <h2 style="margin: 0;">Cita #<?php echo $cita['id']; ?></h2>
                        <span style="color: #64748b; font-size: 14px;">Generada el <?php echo date('d M Y', strtotime($cita['created_at'])); ?></span>
                    </div>
                    <span class="status-badge status-<?php echo $cita['estado']; ?>">
                        <?php echo ucfirst($cita['estado']); ?>
                    </span>
                </div>

                <div class="info-section">
                    <!-- Columna 1: Info Cita -->
                    <div class="info-block">
                        <h3><i class="fas fa-calendar-alt"></i> Información de Cita</h3>
                        <div class="info-row">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Fecha y Hora:</strong><br>
                                <?php echo date('d F Y, h:i A', strtotime($cita['fecha_hora'])); ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-stethoscope"></i>
                            <div>
                                <strong>Tipo:</strong><br>
                                <?php echo ucfirst($cita['tipo_cita']); ?>
                            </div>
                        </div>
                        <?php if ($cita['motivo']): ?>
                        <div class="info-row" style="align-items: flex-start;">
                            <i class="fas fa-comment-medical" style="margin-top: 4px;"></i>
                            <div>
                                <strong>Motivo:</strong><br>
                                <?php echo htmlspecialchars($cita['motivo']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Columna 2: Info Veterinaria -->
                    <div class="info-block">
                        <h3><i class="fas fa-hospital"></i> Veterinaria</h3>
                        <div class="mascota-preview" style="background: white; border: 1px solid #e2e8f0;">
                            <div style="width: 50px; height: 50px; background: #e0e7ff; color: #4338ca; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-clinic-medical"></i>
                            </div>
                            <div>
                                <strong style="font-size: 16px;"><?php echo htmlspecialchars($cita['nombre_local'] ?? 'Veterinaria'); ?></strong>
                                <div style="font-size: 13px; color: #64748b;"><?php echo htmlspecialchars($cita['veterinaria_direccion'] ?? 'Dirección no disponible'); ?></div>
                            </div>
                        </div>
                        <div style="margin-top: 15px;">
                            <div class="info-row">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($cita['veterinaria_telefono'] ?? 'No disponible'); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($cita['veterinaria_email'] ?? 'No disponible'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <!-- Columna 3: Paciente -->
                    <div class="info-block">
                        <h3><i class="fas fa-paw"></i> Paciente</h3>
                        <div class="mascota-preview">
                            <?php if ($cita['mascota_foto']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($cita['mascota_foto']); ?>" class="mascota-img">
                            <?php else: ?>
                                <div class="mascota-img" style="background: #ccc; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-paw"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong style="font-size: 16px; display: block;"><?php echo htmlspecialchars($cita['mascota_nombre']); ?></strong>
                                <span style="font-size: 13px; color: #64748b;"><?php echo htmlspecialchars($cita['mascota_raza']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Columna 4: Costos -->
                    <?php if ($cita['precio_total']): ?>
                    <div class="info-block">
                        <h3><i class="fas fa-file-invoice-dollar"></i> Costos</h3>
                        <div style="background: #f0fdf4; padding: 15px; border-radius: 10px; border: 1px solid #bbf7d0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>Precio Total:</span>
                                <strong>$<?php echo number_format($cita['precio_total'], 2); ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>Anticipo Requerido:</span>
                                <span>$<?php echo number_format($cita['anticipo_requerido'], 2); ?></span>
                            </div>
                            <hr style="border-top: 1px dashed #86efac; margin: 10px 0;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>Pagado:</span>
                                <strong style="color: #15803d;">$<?php echo number_format($cita['anticipo_pagado'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($cita['estado'] === 'pendiente'): ?>
                <div style="margin-top: 30px; text-align: center; display: flex; gap: 10px; justify-content: center;">
                    <button class="btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <button class="btn-outline" onclick="cancelarCita(<?php echo $cita['id']; ?>)" style="color: #ef4444; border-color: #ef4444;">
                        <i class="fas fa-times"></i> Cancelar Cita
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function cancelarCita(id) {
            if(confirm('¿Seguro que deseas cancelar esta cita?')) {
                fetch('ajax-cancelar-cita.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({cita_id: id})
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        alert('Cita cancelada');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
