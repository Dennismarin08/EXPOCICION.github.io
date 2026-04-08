<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de veterinaria
checkRole('veterinaria');

$userId = $_SESSION['user_id'];

// Obtener info veterinaria
$stmt = $pdo->prepare("SELECT id, nombre_local FROM aliados WHERE usuario_id = ? AND tipo = 'veterinaria'");
$stmt->execute([$userId]);
$vet = $stmt->fetch();

if (!$vet) {
    header("Location: vet-dashboard.php");
    exit;
}

$vetId = $vet['id'];

// Manejar actualización de horarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $horario = json_encode($_POST['horario'], JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("UPDATE aliados SET horario = ? WHERE id = ?");
    
    if ($stmt->execute([$horario, $vetId])) {
        $msg = "Horarios actualizados correctamente.";
        $msgType = "success";
    } else {
        $msg = "Error al actualizar horarios.";
        $msgType = "error";
    }
}

// Obtener horario actual
$stmt = $pdo->prepare("SELECT horario FROM aliados WHERE id = ?");
$stmt->execute([$vetId]);
$resultado = $stmt->fetchColumn();
$currentHorario = $resultado ? json_decode($resultado, true) : [];

// Días de la semana
$dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

// Estructura por defecto si es null
if (!$currentHorario) {
    $currentHorario = [];
    foreach ($dias as $dia) {
        $currentHorario[$dia] = ['apertura' => '08:00', 'cierre' => '18:00', 'abierto' => 1];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horarios de Atención - RUGAL Veterinaria</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .schedule-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .day-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .day-label {
            width: 120px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .time-inputs {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .time-input {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #475569;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider { background-color: #00b09b; }
        input:checked + .slider:before { transform: translateX(24px); }
        
        .status-text {
            width: 80px;
            text-align: right;
            font-size: 14px;
            font-weight: 600;
        }
        
        .open { color: #00b09b; }
        .closed { color: #ef4444; }
        
        .btn-save {
            background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 30px;
            transition: transform 0.2s;
        }
        
        .btn-save:hover { transform: translateY(-2px); }

        /* --- Hamburger Menu --- */
        .hamburger-menu {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            width: 45px;
            height: 45px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            z-index: 10002;
            cursor: pointer;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .hamburger-menu span {
            display: block;
            width: 22px;
            height: 2px;
            background: #334155;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: none;
        }
        .sidebar-overlay.active { display: block; }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .day-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .time-inputs {
                width: 100%;
                justify-content: space-between;
            }
            .time-inputs > input {
                flex: 1;
            }
            .day-row > div:last-child {
                width: 100%;
                justify-content: space-between;
            }
        }
        @media (max-width: 992px) {
            .hamburger-menu { display: flex; }
            .sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                height: 100vh;
                z-index: 10001;
                transition: left 0.3s ease;
            }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>
    
    <!-- Hamburger Menu -->
    <button class="hamburger-menu" onclick="toggleSidebar()">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Configuración de Horarios</h1>
                <div class="breadcrumb">
                    <span>Veterinaria</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Horarios</span>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <?php if (isset($msg)): ?>
                <div style="padding: 15px; border-radius: 10px; margin-bottom: 20px; background: <?php echo $msgType == 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $msgType == 'success' ? '#065f46' : '#991b1b'; ?>;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="schedule-card">
                <form method="POST">
                    <?php foreach ($dias as $dia): ?>
                        <?php 
                            $isOpen = isset($currentHorario[$dia]['abierto']) ? $currentHorario[$dia]['abierto'] : 0;
                            $apertura = isset($currentHorario[$dia]['apertura']) ? $currentHorario[$dia]['apertura'] : '08:00';
                            $cierre = isset($currentHorario[$dia]['cierre']) ? $currentHorario[$dia]['cierre'] : '18:00';
                        ?>
                        <div class="day-row">
                            <div class="day-label">
                                <i class="far fa-calendar"></i> <?php echo $dia; ?>
                            </div>
                            
                            <div class="time-inputs" id="times-<?php echo $dia; ?>" style="opacity: <?php echo $isOpen ? '1' : '0.5'; ?>;">
                                <input type="time" name="horario[<?php echo $dia; ?>][apertura]" value="<?php echo $apertura; ?>" class="time-input">
                                <span>a</span>
                                <input type="time" name="horario[<?php echo $dia; ?>][cierre]" value="<?php echo $cierre; ?>" class="time-input">
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="status-text <?php echo $isOpen ? 'open' : 'closed'; ?>" id="status-<?php echo $dia; ?>">
                                    <?php echo $isOpen ? 'Abierto' : 'Cerrado'; ?>
                                </span>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="horario[<?php echo $dia; ?>][abierto]" value="1" 
                                           <?php echo $isOpen ? 'checked' : ''; ?> 
                                           onchange="toggleDay('<?php echo $dia; ?>', this)">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Horarios
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.sidebar-overlay').classList.toggle('active');
            document.querySelector('.hamburger-menu').classList.toggle('active');
        }

        function toggleDay(dia, checkbox) {
            const timesDiv = document.getElementById('times-' + dia);
            const statusSpan = document.getElementById('status-' + dia);
            
            if (checkbox.checked) {
                timesDiv.style.opacity = '1';
                statusSpan.textContent = 'Abierto';
                statusSpan.className = 'status-text open';
            } else {
                timesDiv.style.opacity = '0.5';
                statusSpan.textContent = 'Cerrado';
                statusSpan.className = 'status-text closed';
            }
        }
    </script>
</body>
</html>
