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
    <title>Vacunas - RUGAL</title>
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
                <h1 class="page-title">Control de Vacunas 💉</h1>
                <div class="breadcrumb">
                    <span>Dashboard</span> <i class="fas fa-chevron-right"></i> <span>Salud</span> <i class="fas fa-chevron-right"></i> <span>Vacunas</span>
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
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-syringe"></i> Carnet de Vacunación</h3>
                        </div>
                        <div style="padding: 20px;">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM mascotas_salud WHERE mascota_id = ? AND tipo = 'vacuna' ORDER BY fecha_realizado DESC");
                            $stmt->execute([$mascota['id'] ?? 0]);
                            $vacunas = $stmt->fetchAll();
                            
                            if (empty($vacunas)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-syringe"></i>
                                    <p>No hay vacunas registradas.</p>
                                </div>
                            <?php else: ?>
                                <div class="vaccine-list">
                                    <?php foreach ($vacunas as $vacuna): 
                                        $vencimiento = $vacuna['proxima_fecha'] ? strtotime($vacuna['proxima_fecha']) : null;
                                        $estado = 'vigente';
                                        if ($vencimiento) {
                                            $diasRestantes = ($vencimiento - time()) / (60 * 60 * 24);
                                            if ($diasRestantes < 0) $estado = 'vencida';
                                            elseif ($diasRestantes < 30) $estado = 'por-vencer';
                                        }
                                    ?>
                                    <div class="vaccine-item <?php echo $estado; ?>">
                                        <div class="vaccine-icon">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="vaccine-info">
                                            <h4><?php echo htmlspecialchars($vacuna['nombre_evento']); ?></h4>
                                            <div class="vaccine-meta">
                                                <span><i class="far fa-calendar-check"></i> Aplicada: <?php echo date('d/m/Y', strtotime($vacuna['fecha_realizado'])); ?></span>
                                                <?php if ($vacuna['proxima_fecha']): ?>
                                                <span><i class="far fa-calendar-plus"></i> Próxima: <?php echo date('d/m/Y', strtotime($vacuna['proxima_fecha'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="vaccine-status">
                                            <?php if ($estado == 'vencida'): ?>
                                                <span class="badge-status vencida">Vencida</span>
                                            <?php elseif ($estado == 'por-vencer'): ?>
                                                <span class="badge-status warning">Por Vencer</span>
                                            <?php else: ?>
                                                <span class="badge-status vigente">Vigente</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h3>Registrar Vacuna</h3>
                        </div>
                        <div style="padding: 25px;">
                            <form id="formVacuna">
                                <input type="hidden" name="mascota_id" value="<?php echo $mascota['id']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Nombre Vacuna</label>
                                    <select name="nombre" class="form-control" required>
                                        <option value="Puppy">Puppy (Moquillo/Parvo)</option>
                                        <option value="Quíntuple">Quíntuple</option>
                                        <option value="Sextuple">Sextuple</option>
                                        <option value="Rabia">Rabia</option>
                                        <option value="Bordetella">Bordetella (Tos de las perreras)</option>
                                        <option value="Giardia">Giardia</option>
                                        <option value="Triple Felina">Triple Felina</option>
                                        <option value="Leucemia Felina">Leucemia Felina</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Fecha Aplicación</label>
                                    <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Próxima Dosis</label>
                                    <input type="date" name="proxima_fecha" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                                </div>
                                
                                <button type="submit" class="btn-submit" style="width: 100%;">
                                    <i class="fas fa-save"></i> Guardar Vacuna
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <i class="fas fa-info-circle"></i>
                        <p>Mantener las vacunas al día es esencial para prevenir enfermedades graves.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .vaccine-item { display: flex; align-items: center; padding: 15px; border: 1px solid var(--light-border); border-radius: 12px; margin-bottom: 10px; transition: all 0.2s; }
        .vaccine-item:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .vaccine-icon { width: 40px; height: 40px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 18px; }
        .vaccine-info { flex: 1; }
        .vaccine-info h4 { margin: 0 0 5px 0; font-size: 16px; color: var(--text-dark); }
        .vaccine-meta { font-size: 12px; color: var(--text-light); display: flex; gap: 15px; }
        .vaccine-meta i { margin-right: 5px; }
        
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-status.vigente { background: #dcfce7; color: #166534; }
        .badge-status.warning { background: #fef3c7; color: #92400e; }
        .badge-status.vencida { background: #fee2e2; color: #991b1b; }
        
        .info-card { background: #11253fff; border: 1px solid #bfdbfe; color: #1e40af; padding: 15px; border-radius: 12px; display: flex; gap: 10px; margin-top: 20px; }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-dark); }
        .form-control { width: 100%; padding: 10px; border: 1px solid var(--light-border); border-radius: 8px; font-family: inherit; }
        .btn-submit { background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
    </style>
    
    <script>
    document.getElementById('formVacuna').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams();
        for(const pair of formData) params.append(pair[0], pair[1]);
        
        fetch('ajax-save-vacuna.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Vacuna registrada correctamente');
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
