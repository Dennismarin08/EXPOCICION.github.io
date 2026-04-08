<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de veterinaria
checkRole('veterinaria');

$userId = $_SESSION['user_id'];

// Obtener información de la veterinaria
$stmt = $pdo->prepare("
    SELECT a.id as aliado_id, a.*, u.* 
    FROM aliados a
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE u.id = ? AND a.tipo = 'veterinaria'
");
$stmt->execute([$userId]);
$vetInfo = $stmt->fetch();

if (!$vetInfo) {
    header("Location: vet-dashboard.php");
    exit;
}

// Obtener citas
$stmt = $pdo->prepare("
    SELECT c.*, u.nombre as cliente_nombre, u.telefono as cliente_telefono, 
           m.nombre as mascota_nombre, m.tipo as mascota_tipo, m.raza as mascota_raza,
           s.nombre as servicio_nombre
    FROM citas c
    JOIN usuarios u ON c.user_id = u.id
    JOIN mascotas m ON c.mascota_id = m.id
    LEFT JOIN servicios_veterinaria s ON c.servicio_id = s.id
    WHERE c.veterinaria_id = ?
    ORDER BY c.fecha_hora DESC
");
$stmt->execute([$vetInfo['aliado_id']]);
$citas = $stmt->fetchAll();

// Agrupar citas por estado
$citasPendientes = array_filter($citas, fn($c) => $c['estado'] === 'pendiente');
$citasConfirmadas = array_filter($citas, fn($c) => $c['estado'] === 'confirmada');
$citasCompletadas = array_filter($citas, fn($c) => $c['estado'] === 'completada');
$citasCanceladas = array_filter($citas, fn($c) => $c['estado'] === 'cancelada');

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $citaId = $_POST['cita_id'] ?? 0;
    
    if ($action === 'confirmar') {
        $stmt = $pdo->prepare("UPDATE citas SET estado = 'confirmada' WHERE id = ? AND veterinaria_id = ?");
        $stmt->execute([$citaId, $vetInfo['aliado_id']]);
        header("Location: vet-citas.php?confirmed=1");
        exit;
    } elseif ($action === 'completar') {
        // Guardar notas/diagnóstico/tratamiento cuando se completa la cita
        $notas_veterinaria = $_POST['notas_veterinaria'] ?? null;
        $diagnostico = $_POST['diagnostico'] ?? null;
        $tratamiento = $_POST['tratamiento'] ?? null;
        $stmt = $pdo->prepare("UPDATE citas SET estado = 'completada', notas_veterinaria = ?, diagnostico = ?, tratamiento = ? WHERE id = ? AND veterinaria_id = ?");
        $stmt->execute([$notas_veterinaria, $diagnostico, $tratamiento, $citaId, $vetInfo['aliado_id']]);
        header("Location: vet-citas.php?completed=1");
        exit;
    } elseif ($action === 'cancelar') {
        $stmt = $pdo->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ? AND veterinaria_id = ?");
        $stmt->execute([$citaId, $vetInfo['aliado_id']]);
        header("Location: vet-citas.php?cancelled=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - RUGAL Veterinaria</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 5px;
            max-width: 100%;
            width: 100%;
        }

        /* Ocultar barra de scroll en los tabs para mejor diseño en móvil */
        .tabs::-webkit-scrollbar {
            height: 4px;
        }
        .tabs::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #00b09b;
            border-bottom-color: #00b09b;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .appointments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .appointment-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            gap: 20px;
            align-items: center;
            transition: transform 0.3s;
        }
        
        .appointment-card:hover {
            transform: translateX(5px);
        }
        
        .appointment-icon {
            width: 60px;
            height: 60px;
            min-width: 60px;
            border-radius: 15px;
            background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .appointment-info {
            flex: 1;
        }
        
        .appointment-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .appointment-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            color: #64748b;
            font-size: 14px;
        }
        
        .appointment-details span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .appointment-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-confirm {
            background: #10b981;
            color: white;
        }
        
        .btn-confirm:hover {
            background: #059669;
        }
        
        .btn-complete {
            background: #3b82f6;
            color: white;
        }
        
        .btn-complete:hover {
            background: #2563eb;
        }
        
        .btn-cancel {
            background: #ef4444;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #dc2626;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pendiente {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-confirmada {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-completada {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-cancelada {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            /* Tabs responsivos: envolver en lugar de scroll horizontal */
            .tabs {
                flex-wrap: wrap;
                overflow-x: visible;
                white-space: normal;
                border-bottom: none;
                gap: 8px;
                padding-bottom: 0;
            }
            .tab {
                flex: 1 0 calc(50% - 10px);
                text-align: center;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                background: #f8fafc;
            }
            .tab.active {
                background: #00b09b;
                color: white;
                border-color: #00b09b;
            }
            .appointment-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .appointment-actions {
                width: 100%;
                flex-direction: column;
                gap: 8px;
            }
            .btn-action {
                width: 100%;
                text-align: center;
                padding: 12px;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Mis Citas</h1>
                <div class="breadcrumb">
                    <span>Veterinaria</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Citas</span>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($citasPendientes); ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($citasConfirmadas); ?></div>
                    <div class="stat-label">Confirmadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($citasCompletadas); ?></div>
                    <div class="stat-label">Completadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($citas); ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('pendientes')">
                    Pendientes (<?php echo count($citasPendientes); ?>)
                </button>
                <button class="tab" onclick="switchTab('confirmadas')">
                    Confirmadas (<?php echo count($citasConfirmadas); ?>)
                </button>
                <button class="tab" onclick="switchTab('completadas')">
                    Completadas (<?php echo count($citasCompletadas); ?>)
                </button>
                <button class="tab" onclick="switchTab('todas')">
                    Todas (<?php echo count($citas); ?>)
                </button>
            </div>
            
            <!-- Tab Pendientes -->
            <div id="tab-pendientes" class="tab-content active">
                <div class="appointments-list">
                    <?php if (empty($citasPendientes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <h3>No hay citas pendientes</h3>
                            <p>Las nuevas citas aparecerán aquí</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($citasPendientes as $cita): ?>
                            <?php include __DIR__ . '/../includes/appointment-card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab Confirmadas -->
            <div id="tab-confirmadas" class="tab-content">
                <div class="appointments-list">
                    <?php if (empty($citasConfirmadas)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <h3>No hay citas confirmadas</h3>
                        </div>
                    <?php else: ?>
                        <?php foreach ($citasConfirmadas as $cita): ?>
                            <?php include __DIR__ . '/../includes/appointment-card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab Completadas -->
            <div id="tab-completadas" class="tab-content">
                <div class="appointments-list">
                    <?php if (empty($citasCompletadas)): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h3>No hay citas completadas</h3>
                        </div>
                    <?php else: ?>
                        <?php foreach ($citasCompletadas as $cita): ?>
                            <?php include __DIR__ . '/../includes/appointment-card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab Todas -->
            <div id="tab-todas" class="tab-content">
                <div class="appointments-list">
                    <?php if (empty($citas)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar"></i>
                            <h3>No hay citas registradas</h3>
                        </div>
                    <?php else: ?>
                        <?php foreach ($citas as $cita): ?>
                            <?php include __DIR__ . '/../includes/appointment-card.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Remover active de todos los tabs
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Activar el tab seleccionado
            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }
        
        function confirmAppointment(id) {
            if (confirm('¿Confirmar esta cita?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="confirmar">
                    <input type="hidden" name="cita_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function completeAppointment(id) {
            // Abrir modal y colocar el id de la cita
            const modal = document.getElementById('completeModal');
            if (!modal) return alert('Modal no disponible');
            document.getElementById('modal_cita_id').value = id;
            // limpiar campos
            document.getElementById('modal_diagnostico').value = '';
            document.getElementById('modal_tratamiento').value = '';
            document.getElementById('modal_notas').value = '';
            // reset counters
            updateCounter('modal_diagnostico', 'counter_diagnostico', 500);
            updateCounter('modal_tratamiento', 'counter_tratamiento', 500);
            updateCounter('modal_notas', 'counter_notas', 500);
            modal.classList.add('open');
            document.getElementById('modal_diagnostico').focus();
        }
        
        function cancelAppointment(id) {
            if (confirm('¿Cancelar esta cita?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancelar">
                    <input type="hidden" name="cita_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

<!-- Modal para completar cita (mejorado) -->
<style>
    /* Modal styles */
    #completeModal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(2,6,23,0.6); z-index: 9999; opacity: 0; pointer-events: none; transition: opacity 200ms ease; }
    #completeModal.open { opacity: 1; pointer-events: auto; }
    .cm-dialog { width: 720px; max-width: 96%; background: #0f172a; color: #e6eef8; border-radius: 10px; padding: 18px; box-shadow: 0 8px 30px rgba(2,6,23,0.6); border: 1px solid rgba(255,255,255,0.04); transform: translateY(-8px); transition: transform 200ms ease; }
    #completeModal.open .cm-dialog { transform: translateY(0); }
    .cm-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px; }
    .cm-title { font-size:18px; margin:0; }
    .cm-close { background:transparent; border:none; color:#9fb6d9; font-size:18px; cursor:pointer; }
    .cm-body { display:flex; flex-direction:column; gap:10px; }
    .cm-body label { display:block; font-weight:700; font-size:13px; margin-bottom:6px; color:#9fb6d9; }
    .cm-textarea { width:100%; min-height:72px; max-height:220px; resize:vertical; padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02); color:#e6eef8; }
    .cm-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:6px; }
    .cm-btn { padding:8px 14px; border-radius:8px; border:none; cursor:pointer; font-weight:700; }
    .cm-btn.cancel { background:transparent; color:#9fb6d9; border:1px solid rgba(255,255,255,0.03); }
    .cm-btn.submit { background: linear-gradient(90deg,#3b82f6,#60a5fa); color:#04263a; }
    .cm-counter { font-size:12px; color:#7f9fbf; margin-top:4px; text-align:right; }
</style>

<div id="completeModal" aria-hidden="true">
    <div class="cm-dialog" role="dialog" aria-modal="true" aria-labelledby="cmTitle">
        <div class="cm-header">
            <h3 id="cmTitle" class="cm-title">Completar cita — enviar resultado al propietario</h3>
            <button class="cm-close" id="cmClose" aria-label="Cerrar">✕</button>
        </div>
        <form id="completeForm" method="POST">
            <input type="hidden" name="action" value="completar">
            <input type="hidden" name="cita_id" id="modal_cita_id" value="">
            <div class="cm-body">
                <div>
                    <label for="modal_diagnostico">Diagnóstico (resumen)</label>
                    <textarea id="modal_diagnostico" name="diagnostico" class="cm-textarea" maxlength="500"></textarea>
                    <div id="counter_diagnostico" class="cm-counter">0 / 500</div>
                </div>

                <div>
                    <label for="modal_tratamiento">Tratamiento recomendado</label>
                    <textarea id="modal_tratamiento" name="tratamiento" class="cm-textarea" maxlength="500"></textarea>
                    <div id="counter_tratamiento" class="cm-counter">0 / 500</div>
                </div>

                <div>
                    <label for="modal_notas">Notas para el propietario</label>
                    <textarea id="modal_notas" name="notas_veterinaria" class="cm-textarea" maxlength="500"></textarea>
                    <div id="counter_notas" class="cm-counter">0 / 500</div>
                </div>

                <div style="font-size:12px; color:#9fb6d9;">Consejo: sé claro y conciso; la información aparecerá en el dashboard del propietario. Esto no reemplaza una valoración clínica completa.</div>
                <div class="cm-actions">
                    <button type="button" class="cm-btn cancel" id="cmCancel">Cancelar</button>
                    <button type="submit" class="cm-btn submit" id="cmSubmit">Enviar y completar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Helpers
    function qs(id){ return document.getElementById(id); }
    function updateCounter(textId, counterId, max){
        const t = qs(textId); const c = qs(counterId);
        if (!t || !c) return;
        c.innerText = t.value.length + ' / ' + max;
        c.style.color = (t.value.length > max ? '#ffb4b4' : '#7f9fbf');
    }

    // Init counters and events
    ['modal_diagnostico','modal_tratamiento','modal_notas'].forEach(function(id){
        const el = qs(id);
        if (!el) return;
        const max = parseInt(el.getAttribute('maxlength') || '500',10);
        const counter = 'counter_' + id.split('_')[1];
        el.addEventListener('input', function(){ updateCounter(id, counter, max); toggleSubmit(); });
        // initial
        updateCounter(id, counter, max);
    });

    function toggleSubmit(){
        const submit = qs('cmSubmit');
        const d = qs('modal_diagnostico').value.trim();
        const t = qs('modal_tratamiento').value.trim();
        const n = qs('modal_notas').value.trim();
        // require at least one field non-empty
        submit.disabled = (d.length === 0 && t.length === 0 && n.length === 0);
        submit.style.opacity = submit.disabled ? '0.6' : '1';
    }

    // Open/close handlers
    qs('cmClose').addEventListener('click', closeModal);
    qs('cmCancel').addEventListener('click', closeModal);
    document.getElementById('completeModal').addEventListener('click', function(e){
        if (e.target === this) closeModal();
    });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });

    function closeModal(){
        const modal = qs('completeModal');
        if (!modal) return;
        modal.classList.remove('open');
        // small delay to reset
        setTimeout(function(){
            qs('modal_cita_id').value = '';
            qs('modal_diagnostico').value = '';
            qs('modal_tratamiento').value = '';
            qs('modal_notas').value = '';
            updateCounter('modal_diagnostico','counter_diagnostico',500);
            updateCounter('modal_tratamiento','counter_tratamiento',500);
            updateCounter('modal_notas','counter_notas',500);
            toggleSubmit();
        }, 220);
    }

    // Ensure form submission closes modal and allows normal POST
    qs('completeForm').addEventListener('submit', function(){
        // minimal client validation already done
        closeModal();
    });

    // Expose updateCounter for earlier code
    window.updateCounter = updateCounter;
</script>
