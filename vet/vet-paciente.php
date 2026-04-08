<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/plan_salud_mensual_functions.php';

checkRole('veterinaria');

$mascotaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$mascotaId) { header("Location: vet-clientes.php"); exit; }

$userId = $_SESSION['user_id'];
$activeTab = $_GET['tab'] ?? 'ficha';

// 1. Obtener datos de la mascota y el dueño
$stmtM = $pdo->prepare("
    SELECT m.*, u.nombre as dueno_nombre, u.telefono as dueno_tel, u.email as dueno_email
    FROM mascotas m
    JOIN usuarios u ON m.user_id = u.id
    WHERE m.id = ?
");
$stmtM->execute([$mascotaId]);
$mascota = $stmtM->fetch();

if (!$mascota) die("Paciente no encontrado.");

// 1.5 Obtener Historial de Vacunas (desde mascotas_salud)
$stmtV = $pdo->prepare("
    SELECT nombre_evento, fecha_realizado, proxima_fecha 
    FROM mascotas_salud 
    WHERE mascota_id = ? AND tipo = 'vacuna' 
    ORDER BY fecha_realizado DESC
");
$stmtV->execute([$mascotaId]);
$vacunas = $stmtV->fetchAll();

// 2. Plan de Salud Activo e Inteligencia
$plan = obtenerPlanSaludMensual($mascotaId);
$alertas = json_decode($plan['alertas_json'] ?? '[]', true);

// 2. Procesar nueva entrada de historial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nuevo_historial') {
    $stmtH = $pdo->prepare("
        INSERT INTO historial_medico (mascota_id, fecha, tipo, motivo, diagnostico, tratamiento, veterinario, clinica, notas)
        VALUES (?, CURRENT_DATE(), ?, ?, ?, ?, ?, 'RUGAL VET', ?)
    ");
    $stmtH->execute([
        $mascotaId,
        $_POST['tipo'],
        $_POST['motivo'],
        $_POST['diagnostico'],
        $_POST['tratamiento'],
        $_SESSION['user_name'] ?? 'Veterinario', // Usar $_SESSION['user_name']
        $_POST['notas']
    ]);
    header("Location: vet-paciente.php?id=$mascotaId&success=1");
    exit;
}

// 3. Obtener historial médico
$stmtHist = $pdo->prepare("SELECT * FROM historial_medico WHERE mascota_id = ? ORDER BY fecha DESC");
$stmtHist->execute([$mascotaId]);
$historial = $stmtHist->fetchAll();

// 4. Obtener pesos recientes
$stmtPeso = $pdo->prepare("SELECT peso, fecha FROM peso_historial WHERE mascota_id = ? ORDER BY fecha DESC LIMIT 5");
$stmtPeso->execute([$mascotaId]);
$pesos = $stmtPeso->fetchAll();
// 3. Síntomas Recientes (Bitácora de los últimos 14 días)
$stmtS = $pdo->prepare("SELECT fecha, datos, observaciones FROM seguimientos_diarios WHERE mascota_id = ? ORDER BY fecha DESC LIMIT 14");
$stmtS->execute([$mascotaId]);
$bitacora = $stmtS->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha Clínica: <?php echo htmlspecialchars($mascota['nombre']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .health-status { padding: 20px; border-radius: 20px; margin-bottom: 25px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .status-verde { background: #dcfce7; border: 1px solid #22c55e; color: #166534; }
        .status-amarillo { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; }
        .status-rojo { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; }
        
        .clinical-grid { display: grid; grid-template-columns: 1fr 320px; gap: 25px; }
        .history-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; }
        .historial-item { padding: 15px; border-left: 3px solid #7c3aed; background: #f8fafc; margin-bottom: 15px; border-radius: 0 12px 12px 0; }
        .symptom-tag { display: inline-block; padding: 4px 10px; border-radius: 8px; background: #f1f5f9; font-size: 11px; margin: 2px; border: 1px solid #e2e8f0; font-weight: 700; }
        .symptom-alert { background: #fee2e2; color: #ef4444; border-color: #fecaca; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: white; border-radius: 24px; width: 90%; max-width: 600px; padding: 30px; position: relative; }
        .btn-ia { background: linear-gradient(135deg, #7c3aed, #a855f7); color: white; border: none; padding: 12px 24px; border-radius: 12px; cursor: pointer; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 15px; font-family: inherit; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Ficha Clínica Digital</h1>
                <div class="breadcrumb"><span>Pacientes</span> <i class="fas fa-chevron-right"></i> <span><?php echo htmlspecialchars($mascota['nombre']); ?></span></div>
            </div>
            <div class="header-right">
                <button class="btn-ia" onclick="abrirIA(<?php echo $mascotaId; ?>, '<?php echo $mascota['nombre']; ?>')">
                    <i class="fas fa-robot"></i> Análisis con Gemini AI
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Estado de Salud (Semáforo) -->
            <?php if($plan): ?>
                <div class="health-status status-<?php echo $plan['nivel_alerta']; ?>">
                    <i class="fas fa-heartbeat fa-2x"></i>
                    <div>
                        <div style="font-weight: 800; font-size: 18px;"><?php echo $alertas['titulo'] ?? 'Estado General'; ?></div>
                        <div style="font-size: 14px; opacity: 0.9;"><?php echo $alertas['mensaje'] ?? ''; ?></div>
                    </div>
                    <div style="margin-left: auto; text-align: right;">
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700;">Health Score</div>
                        <div style="font-size: 24px; font-weight: 900;"><?php echo $plan['health_score']; ?>%</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="clinical-grid">
                <!-- Columna Izquierda -->
                <div>
                    <!-- 1. Bitácora de Síntomas Recientes -->
                    <div class="history-card">
                        <div class="card-header-alt">
                            <h3><i class="fas fa-notes-medical" style="color:#7c3aed;"></i> Bitácora del Propietario</h3>
                            <span style="font-size: 12px; color: #94a3b8;">Últimos 14 días</span>
                        </div>
                        <?php if(empty($bitacora)): ?>
                            <p style="color:#64748b; font-style: italic;">El propietario no ha registrado bitácoras recientemente.</p>
                        <?php else: ?>
                            <div class="bitacora-list">
                                <?php foreach($bitacora as $dia): 
                                    $data = json_decode($dia['datos'], true);
                                ?>
                                <div style="padding: 12px; border-bottom: 1px solid #f1f5f9;">
                                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                        <strong style="font-size:13px; color:#1e293b;"><?php echo date('d M, Y', strtotime($dia['fecha'])); ?></strong>
                                        <span class="symptom-tag"><?php echo $data['animo'] ?? 'Normal'; ?></span>
                                    </div>
                                    <div style="margin-top:5px;">
                                        <?php if(isset($data['vomitos']) && $data['vomitos']): ?><span class="symptom-tag symptom-alert">Vómitos</span><?php endif; ?>
                                        <?php if(isset($data['diarrea']) && $data['diarrea']): ?><span class="symptom-tag symptom-alert">Diarrea</span><?php endif; ?>
                                        <?php if(isset($data['prurito']) && $data['prurito']): ?><span class="symptom-tag symptom-alert">Picazón</span><?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Historial de Vacunación -->
                    <div class="history-card">
                        <div class="card-header-alt">
                            <h3><i class="fas fa-syringe" style="color:#ec4899;"></i> Control de Vacunas</h3>
                        </div>
                        <?php if(empty($vacunas)): ?>
                            <p style="color:#64748b; font-size: 13px;">No hay registro de vacunas aplicadas.</p>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                                <?php foreach($vacunas as $v): ?>
                                    <div class="data-badge" style="border-left: 4px solid #ec4899;">
                                        <span class="data-label"><?php echo date('d M, Y', strtotime($v['fecha_realizado'])); ?></span>
                                        <span class="data-value"><?php echo htmlspecialchars($v['nombre_evento']); ?></span>
                                        <?php if($v['proxima_fecha']): ?>
                                            <div style="font-size: 10px; color: #ef4444; margin-top: 5px; font-weight: 700;">PROX: <?php echo date('d/m/Y', strtotime($v['proxima_fecha'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 3. Historial Médico (Consultas) -->
                    <div class="history-card">
                        <div class="card-header-alt">
                            <h3><i class="fas fa-file-medical-alt" style="color:#7c3aed;"></i> Evoluciones Clínicas</h3>
                        </div>
                        <?php if(empty($historial)): ?>
                            <p style="color:#64748b; font-style: italic;">Sin registros médicos previos.</p>
                        <?php else: ?>
                            <?php foreach($historial as $h): ?>
                                <div class="historial-item">
                                    <div style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase;"><?php echo date('d M, Y', strtotime($h['fecha'])); ?> • <?php echo htmlspecialchars($h['tipo']); ?></div>
                                    <div style="font-weight: 800; color: #1e293b; font-size: 15px; margin: 4px 0;"><?php echo htmlspecialchars($h['motivo']); ?></div>
                                    <div style="font-size: 13px; color: #475569;"><?php echo nl2br(htmlspecialchars($h['diagnostico'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Columna Derecha: Información Mascota -->
                <div>
                    <div class="history-card" style="text-align:center; padding-top: 40px;">
                        <?php if($mascota['foto_perfil']): ?>
                            <img src="<?php echo buildImgUrl($mascota['foto_perfil']); ?>" style="width:160px; height:160px; border-radius:40px; object-fit: cover; margin-bottom:20px; border: 6px solid #f8fafc; box-shadow: 0 10px 20px rgba(0,0,0,0.1); background: #fff;">
                        <?php else: ?>
                            <div style="width:140px; height:140px; background:#7c3aed; color:white; border-radius:30px; display:flex; align-items:center; justify-content:center; font-size:50px; margin:0 auto 15px;"><i class="fas fa-paw"></i></div>
                        <?php endif; ?>
                        <h2 style="margin:0; color:#1e293b; font-size: 28px;"><?php echo htmlspecialchars($mascota['nombre']); ?></h2>
                        <p style="color:#64748b; font-size:15px; font-weight: 600; margin-bottom:25px;"><?php echo htmlspecialchars($mascota['raza']); ?> • <?php echo $mascota['edad_anios']; ?> años</p>
                        
                        <div style="text-align:left;">
                            <div class="data-badge">
                                <span class="data-label">Propietario</span>
                                <span class="data-value"><?php echo htmlspecialchars($mascota['dueno_nombre']); ?></span>
                                <div style="font-size:12px; color:#7c3aed; margin-top:4px;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($mascota['dueno_tel']); ?></div>
                            </div>
                            <div class="data-badge" style="border-left-color: #10b981;">
                                <span class="data-label">Peso Actual</span>
                                <span class="data-value" style="color:#10b981; font-size: 20px;"><?php echo $mascota['peso']; ?> kg</span>
                            </div>
                        </div>
                    </div>

                    <!-- Plan de Salud -->
                    <div class="history-card" style="background: #1e293b; color: white; border: none;">
                        <h3 style="color: white; margin-bottom: 15px;"><i class="fas fa-clipboard-list"></i> Plan de Salud</h3>
                        <?php if($plan && isset($plan['recomendaciones_json'])): 
                            $recs = json_decode($plan['recomendaciones_json'], true);
                        ?>
                            <div style="font-size:13px; color:#e2e8f0; background:rgba(255,255,255,0.05); padding:15px; border-radius:12px; border-left: 4px solid #3b82f6;">
                                <strong>Nutrición:</strong><br>
                                <?php echo $recs['diaria']['alimentacion'][0] ?? 'Consulte el plan completo'; ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size:12px; color:#94a3b8;">Sin plan generado.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal IA -->
    <div id="modalIA" class="modal-overlay" onclick="if(event.target===this)cerrarIA()">
        <div class="modal-box">
            <h2 style="margin:0 0 20px 0;">Análisis <span style="background: linear-gradient(135deg, #4285f4, #ea4335); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 900;">✦ Gemini AI</span></h2>
            <div id="iaContent" style="min-height:200px;"></div>
            <button onclick="cerrarIA()" style="position:absolute; top:20px; right:20px; border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
    </div>

    <script>
    function abrirIA(id, nombre) {
        document.getElementById('modalIA').classList.add('active');
        document.getElementById('iaContent').innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:30px;color:#7c3aed;margin-bottom:15px;"></i><p>Analizando bitácoras del propietario e historial clínico...</p></div>';
        const fd = new FormData(); fd.append('mascota_id', id);
        fetch('ajax-historial-inteligente.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            document.getElementById('iaContent').innerHTML = data.success ? data.html : `<div style="color:#ef4444;">${data.message}</div>`;
        });
    }
    function cerrarIA() {
        document.getElementById('modalIA').classList.remove('active');
    }
    </script>
</body>
</html>
