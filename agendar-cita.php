<?php
require_once 'db.php';
require_once 'includes/check-auth.php';
$userId = $_SESSION['user_id'];

$preselectedVetId = $_GET['vet_id'] ?? null;

// Obtener mascotas del usuario
$stmt = $pdo->prepare("SELECT id, nombre, tipo FROM mascotas WHERE user_id = ?");
$stmt->execute([$userId]);
$mascotas = $stmt->fetchAll();

// Obtener veterinarias activas
$stmt = $pdo->query("SELECT id, nombre_local, direccion FROM aliados WHERE tipo = 'veterinaria' AND activo = 1 AND acepta_citas = 1");
$vets = $stmt->fetchAll();

// Si hay veterinaria preseleccionada, obtener sus servicios
$servicios = [];
if ($preselectedVetId) {
    $stmt = $pdo->prepare("SELECT id, nombre, precio, duracion_minutos FROM servicios_veterinaria WHERE veterinaria_id = ? AND activo = 1");
    $stmt->execute([$preselectedVetId]);
    $servicios = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - RUGAL</title>
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .booking-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b; }
        .form-input { 
            width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px;
            transition: all 0.3s;
        }
        .form-input:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-submit:hover { transform: translateY(-2px); }
        
        .service-option {
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            cursor: pointer;
        }
        .service-option:hover { background: #f8fafc; }
        .service-option.selected { border-color: #667eea; background: #eff6ff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Agendar Nueva Cita</h1>
            <a href="citas.php" style="color:#64748b;"><i class="fas fa-times"></i> Cancelar</a>
        </header>
        
        <div class="content-wrapper">
            <div class="booking-card">
                <form action="procesar-cita.php" method="POST" id="bookingForm">
                    <!-- Paso 1: Veterinaria -->
                    <div class="form-group">
                        <label class="form-label">Veterinaria</label>
                        <select name="veterinaria_id" id="vetSelect" class="form-input" required onchange="cargarServicios(this.value)">
                            <option value="">Selecciona una clínica...</option>
                            <?php foreach ($vets as $vet): ?>
                                <option value="<?php echo $vet['id']; ?>" <?php echo $preselectedVetId == $vet['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vet['nombre_local']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Paso 2: Mascota -->
                    <div class="form-group">
                        <label class="form-label">¿Para quién es la cita?</label>
                        <select name="mascota_id" class="form-input" required>
                            <?php foreach ($mascotas as $pet): ?>
                                <option value="<?php echo $pet['id']; ?>">
                                    <?php echo htmlspecialchars($pet['nombre']); ?> (<?php echo ucfirst($pet['tipo']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Paso 3: Servicio -->
                    <div class="form-group">
                        <label class="form-label">Motivo / Servicio</label>
                        <select name="servicio_id" id="serviceSelect" class="form-input">
                            <option value="">Consulta General (Default)</option>
                            <?php foreach ($servicios as $serv): ?>
                                <option value="<?php echo $serv['id']; ?>">
                                    <?php echo htmlspecialchars($serv['nombre']); ?> - $<?php echo number_format($serv['precio'], 0); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="margin-top:5px; font-size:12px; color:#64748b;">* El precio final puede variar según el diagnóstico.</div>
                    </div>
                    
                     <!-- Custom Reason if no service selected -->
                    <div class="form-group">
                        <label class="form-label">Detalles Adicionales</label>
                        <textarea name="motivo" class="form-input" rows="3" placeholder="Describe brevemente los síntomas..."></textarea>
                    </div>
                    
                    <!-- Paso 4: Fecha y Hora -->
                    <div class="form-row" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha" id="dateInput" class="form-input" required min="<?php echo date('Y-m-d'); ?>" onchange="checkAvailability()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Hora</label>
                            <div id="timeSlotsContainer" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 12px; max-height: 300px; overflow-y: auto; padding: 10px; border: 2px solid #e2e8f0; border-radius: 10px; background: #f8fafc;">
                                <!-- Slots will be generated here -->
                                <p style="grid-column: 1/-1; color: #64748b; font-size: 14px; text-align: center; margin: 20px 0;">Selecciona una fecha para ver horarios disponibles.</p>
                            </div>
                            <input type="hidden" name="hora" id="selectedTime" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Confirmar Cita</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Generate default slots
    const startHour = 8;
    const endHour = 18;
    
    function generateSlots(bookedTimes = []) {
        const container = document.getElementById('timeSlotsContainer');
        container.innerHTML = '';
        
        if (bookedTimes.length === 0) {
            // Check if we are just initializing or if there are really no bookings
            const vetId = document.getElementById('vetSelect').value;
            const date = document.getElementById('dateInput').value;
            if (!vetId || !date) {
                container.innerHTML = '<p style="grid-column: 1/-1; color: #64748b; font-size: 14px;">Selecciona una veterinaria y fecha para ver horarios.</p>';
                return;
            }
        }

        for (let h = startHour; h <= endHour; h++) {
            ['00', '30'].forEach(min => {
                const time = `${h.toString().padStart(2, '0')}:${min}`;
                const isBooked = bookedTimes.includes(time);
                
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `time-slot ${isBooked ? 'booked' : 'available'}`;
                btn.style.minWidth = '80px';
                btn.style.padding = '12px 8px';
                btn.style.borderRadius = '8px';
                btn.style.fontSize = '14px';
                btn.style.fontWeight = '600';
                btn.style.border = '2px solid transparent';
                btn.style.transition = 'all 0.2s';
                btn.style.cursor = isBooked ? 'not-allowed' : 'pointer';

                if (isBooked) {
                    btn.style.background = '#fee2e2';
                    btn.style.color = '#991b1b';
                    btn.style.borderColor = '#fecaca';
                    btn.disabled = true;
                    btn.title = 'Horario no disponible';
                } else {
                    btn.style.background = '#f0fdf4';
                    btn.style.color = '#166534';
                    btn.style.borderColor = '#dcfce7';
                    btn.onclick = () => selectTime(time, btn);
                }

                btn.textContent = time;
                container.appendChild(btn);
            });
        }
    }
    
    function selectTime(time, btnElement) {
        document.getElementById('selectedTime').value = time;
        // Reset and Highlight
        document.querySelectorAll('#timeSlotsContainer button.available').forEach(b => {
            b.style.background = '#f0fdf4';
            b.style.color = '#166534';
            b.style.borderColor = '#dcfce7';
            b.style.transform = 'scale(1)';
        });
        
        btnElement.style.background = '#166534';
        btnElement.style.color = 'white';
        btnElement.style.borderColor = '#166534';
        btnElement.style.transform = 'scale(1.05)';
    }
    
    function checkAvailability() {
        const vetId = document.getElementById('vetSelect').value;
        const date = document.getElementById('dateInput').value;
        
        if (!vetId || !date) return;
        
        const container = document.getElementById('timeSlotsContainer');
        container.innerHTML = '<p style="grid-column: 1/-1; color: #64748b; font-size: 14px;"><i class="fas fa-spinner fa-spin"></i> Cargando disponibilidad...</p>';
        
        fetch(`ajax-get-booked-slots.php?vet_id=${vetId}&date=${date}`)
            .then(response => response.json())
            .then(bookedTimes => {
                generateSlots(bookedTimes);
            })
            .catch(err => {
                console.error('Error:', err);
                container.innerHTML = '<p style="grid-column: 1/-1; color: #ef4444; font-size: 14px;">Error al cargar horarios. Intenta de nuevo.</p>';
            });
    }

    function cargarServicios(vetId) {
         // Reload page to fetch services (simplest for now)
         const date = document.getElementById('dateInput').value;
         let url = 'agendar-cita.php?vet_id=' + vetId;
         // Preserve date if selected? No, simpler to just reload.
         window.location.href = url;
    }
    
    // Initial load
    generateSlots();
    </script>
</body>
</html>
