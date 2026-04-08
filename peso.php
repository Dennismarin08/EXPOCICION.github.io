<?php
require_once 'db.php';
require_once 'puntos-functions.php';

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);
$nivelInfo = obtenerInfoNivel($user['nivel'] ?? 'bronce');

global $pdo;
$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ? ORDER BY id ASC LIMIT 1");
$stmt->execute([$userId]);
$mascota = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Peso - RUGAL</title>
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
                <h1 class="page-title">Control de Peso ⚖️</h1>
                <div class="breadcrumb">
                    <span>Dashboard</span> <i class="fas fa-chevron-right"></i> <span>Salud</span> <i class="fas fa-chevron-right"></i> <span>Peso</span>
                </div>
            </div>
            <div class="header-right">
                 <div class="nivel-badge">
                    <?php echo $nivelInfo['icono']; ?> Nivel <?php echo $nivelInfo['nombre']; ?>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="row">
                <div class="col-8">
                    <!-- Gráfico -->
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Evolución de Peso</h3>
                        </div>
                        <div style="padding: 20px; height: 300px;">
                            <canvas id="weightChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Historial Tabla -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Historial Detallado</h3>
                        </div>
                        <div style="padding: 20px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--light-border); text-align: left;">
                                        <th style="padding: 10px;">Fecha</th>
                                        <th style="padding: 10px;">Peso (kg)</th>
                                        <th style="padding: 10px;">Cambio</th>
                                        <th style="padding: 10px;">Notas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT * FROM peso_historial WHERE mascota_id = ? ORDER BY fecha DESC");
                                    $stmt->execute([$mascota['id'] ?? 0]);
                                    $registros = $stmt->fetchAll();
                                    
                                    $prevPeso = 0;
                                    // Reverse for calculation? No, just display from DB
                                    // Better logical display
                                    
                                    foreach ($registros as $i => $reg): 
                                        // Calculate change vs next record (which is previous in time)
                                        $cambio = 0;
                                        if (isset($registros[$i+1])) {
                                            $cambio = $reg['peso'] - $registros[$i+1]['peso'];
                                        }
                                    ?>
                                    <tr style="border-bottom: 1px solid var(--light-border);">
                                        <td style="padding: 12px;"><?php echo date('d/m/Y', strtotime($reg['fecha'])); ?></td>
                                        <td style="padding: 12px; font-weight: bold;"><?php echo $reg['peso']; ?> kg</td>
                                        <td style="padding: 12px;">
                                            <?php if ($cambio > 0): ?>
                                                <span style="color: var(--danger-color);"><i class="fas fa-arrow-up"></i> +<?php echo number_format($cambio, 1); ?></span>
                                            <?php elseif ($cambio < 0): ?>
                                                <span style="color: var(--success-color);"><i class="fas fa-arrow-down"></i> <?php echo number_format($cambio, 1); ?></span>
                                            <?php else: ?>
                                                <span style="color: var(--text-light);">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; color: var(--text-light);"><?php echo htmlspecialchars((string)($reg['notas'] ?? '')); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <!-- Formulario -->
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header">
                            <h3>Registrar Peso</h3>
                        </div>
                        <div style="padding: 25px;">
                            <form id="formPeso">
                                <input type="hidden" name="mascota_id" value="<?php echo $mascota['id']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Peso (kg)</label>
                                    <div style="position: relative;">
                                        <input type="number" name="peso" step="0.01" class="form-control" placeholder="0.00" required>
                                        <span style="position: absolute; right: 10px; top: 10px; color: var(--text-light);">kg</span>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Notas</label>
                                    <textarea name="notas" class="form-control" rows="2" placeholder="Opcional"></textarea>
                                </div>
                                
                                <button type="submit" class="btn-submit" style="width: 100%;">
                                    <i class="fas fa-save"></i> Guardar Peso
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Resumen -->
                    <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                        <div style="padding: 25px;">
                            <h3>Peso Actual</h3>
                            <div style="font-size: 36px; font-weight: bold;">
                                <?php echo $mascota['peso'] ?? 0; ?> <small style="font-size: 16px;">kg</small>
                            </div>
                            <div style="margin-top: 10px; opacity: 0.9;">
                                Peso ideal aprox: <?php echo $mascota['peso_promedio'] ?? 'N/A'; ?> kg
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-dark); }
        .form-control { width: 100%; padding: 10px; border: 1px solid var(--light-border); border-radius: 8px; font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary-color); }
        .btn-submit { background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-submit:hover { opacity: 0.9; }
    </style>
    
    <script>
    // Gráfico
    const ctx = document.getElementById('weightChart').getContext('2d');
    
    <?php
    // Prepare data for chart (reverse chronologically for chart)
    $chartData = array_reverse($registros);
    $labels = array_map(function($r) { return date('d/m', strtotime($r['fecha'])); }, $chartData);
    $data = array_map(function($r) { return $r['peso']; }, $chartData);
    ?>
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Peso (kg)',
                data: <?php echo json_encode($data); ?>,
                borderColor: '#2563eb',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(37, 99, 235, 0.1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });

    // Formulario
    document.getElementById('formPeso').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        // Convert FormData to URLSearchParams for standard POST
        const params = new URLSearchParams();
        for(const pair of formData) {
            params.append(pair[0], pair[1]);
        }
        
        fetch('ajax-save-peso.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Peso registrado correctamente');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
    });
    </script>
    </div>
</body>
</html>
