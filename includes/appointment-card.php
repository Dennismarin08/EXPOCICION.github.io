<?php
// Unified appointment card: uses $cita if provided (from vet listings), otherwise $appointment
$data = [];
if (isset($cita) && is_array($cita)) $data = $cita;
elseif (isset($appointment) && is_array($appointment)) $data = $appointment;
// Normalize keys
$id = $data['id'] ?? null;
$mascota_nombre = $data['mascota_nombre'] ?? ($data['nombre'] ?? 'Mascota');
$cliente_nombre = $data['cliente_nombre'] ?? ($data['dueno_nombre'] ?? '-');
$fecha_hora = $data['fecha_hora'] ?? ($data['fecha'] ?? date('Y-m-d H:i'));
$estado = $data['estado'] ?? 'pendiente';
$tipo = $data['mascota_tipo'] ?? ($data['tipo'] ?? 'perro');
$raza = $data['mascota_raza'] ?? ($data['raza'] ?? 'Sin raza');
$servicio = $data['servicio_nombre'] ?? ($data['servicio'] ?? null);
$telefono = $data['cliente_telefono'] ?? ($data['telefono'] ?? '');
$motivo = $data['motivo'] ?? '';
?>
<div class="appointment-card">
    <div class="appointment-icon">
        <i class="fas fa-<?php echo (stripos($tipo,'gat')!==false) ? 'cat' : 'dog'; ?>"></i>
    </div>
    
    <div class="appointment-info">
        <div class="appointment-title">
            <?php echo htmlspecialchars($cliente_nombre); ?> - <?php echo htmlspecialchars($mascota_nombre); ?>
            <span class="status-badge status-<?php echo htmlspecialchars($estado); ?>">
                <?php echo ucfirst(htmlspecialchars($estado)); ?>
            </span>
        </div>
        <div class="appointment-details">
            <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($fecha_hora)); ?></span>
            <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($fecha_hora)); ?></span>
            <span><i class="fas fa-paw"></i> <?php echo htmlspecialchars($raza); ?></span>
            <?php if ($servicio): ?>
                <span><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($servicio); ?></span>
            <?php endif; ?>
            <?php if ($telefono): ?>
                <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($telefono); ?></span>
            <?php endif; ?>
        </div>
        <?php if ($motivo): ?>
            <div style="margin-top: 10px; color: #64748b; font-size: 14px;">
                <strong>Motivo:</strong> <?php echo htmlspecialchars($motivo); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="appointment-actions">
        <?php if ($estado === 'pendiente'): ?>
            <button class="btn-action btn-confirm" onclick="confirmAppointment(<?php echo intval($id); ?>)">
                <i class="fas fa-check"></i> Confirmar
            </button>
            <button class="btn-action btn-cancel" onclick="cancelAppointment(<?php echo intval($id); ?>)">
                <i class="fas fa-times"></i> Cancelar
            </button>
        <?php elseif ($estado === 'confirmada'): ?>
            <button class="btn-action btn-complete" onclick="completeAppointment(<?php echo intval($id); ?>)">
                <i class="fas fa-check-circle"></i> Completar
            </button>
            <button class="btn-action btn-cancel" onclick="cancelAppointment(<?php echo intval($id); ?>)">
                <i class="fas fa-times"></i> Cancelar
            </button>
        <?php endif; ?>
        <a href="vet-paciente.php?id=<?php echo intval($data['mascota_id'] ?? $data['mascota'] ?? 0); ?>" class="btn-action" style="background:#f3f4f6;border:1px solid #e5e7eb;">Ficha Paciente</a>
    </div>
</div>
