<?php
// ALTER TABLE servicios_veterinaria ADD COLUMN icono VARCHAR(50) DEFAULT 'fa-paw';
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

// Obtener servicios
$stmt = $pdo->prepare("SELECT * FROM servicios_veterinaria WHERE veterinaria_id = ? ORDER BY nombre");
$stmt->execute([$vetInfo['aliado_id']]);
$servicios = $stmt->fetchAll();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear') {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $precio = $_POST['precio'] ?? 0;
        $duracion = $_POST['duracion_minutos'] ?? 30;
        $icono = $_POST['icono'] ?? 'fa-paw';
        
        $stmt = $pdo->prepare("INSERT INTO servicios_veterinaria (veterinaria_id, nombre, descripcion, precio, duracion_minutos, icono) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vetInfo['aliado_id'], $nombre, $descripcion, $precio, $duracion, $icono]);
        
        header("Location: vet-servicios.php?success=1");
        exit;
    } elseif ($action === 'editar') {
        $id = $_POST['id'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $precio = $_POST['precio'] ?? 0;
        $duracion = $_POST['duracion_minutos'] ?? 30;
        $activo = $_POST['activo'] ?? 1;
        $icono = $_POST['icono'] ?? 'fa-paw';
        
        $stmt = $pdo->prepare("UPDATE servicios_veterinaria SET nombre = ?, descripcion = ?, precio = ?, duracion_minutos = ?, activo = ?, icono = ? WHERE id = ? AND veterinaria_id = ?");
        $stmt->execute([$nombre, $descripcion, $precio, $duracion, $activo, $icono, $id, $vetInfo['aliado_id']]);
        
        header("Location: vet-servicios.php?updated=1");
        exit;
    } elseif ($action === 'eliminar') {
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM servicios_veterinaria WHERE id = ? AND veterinaria_id = ?");
        $stmt->execute([$id, $vetInfo['aliado_id']]);
        
        header("Location: vet-servicios.php?deleted=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Servicios - RUGAL Veterinaria</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border-left: 4px solid #00b09b;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .service-card.inactive {
            opacity: 0.6;
            border-left-color: #ccc;
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .service-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .service-price {
            font-size: 24px;
            font-weight: 800;
            color: #00b09b;
        }
        
        .service-description {
            color: #64748b;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .service-meta {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: #64748b;
            margin-bottom: 15px;
        }
        
        .service-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-edit, .btn-delete {
            flex: 1;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #667eea;
            color: white;
        }
        
        .btn-edit:hover {
            background: #5568d3;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #00b09b;
        }
        
        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        
        .icon-picker {
            display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px;
        }
        .icon-option-btn {
            width: 50px; height: 50px; border-radius: 12px;
            border: 2px solid #e2e8f0; display: flex; align-items: center;
            justify-content: center; cursor: pointer; font-size: 20px;
            color: #64748b; transition: all 0.2s; background: white;
        }
        .icon-option-btn:hover { border-color: #00b09b; color: #00b09b; background: #f0fdf4; }
        .icon-option-btn.active { border-color: #00b09b; background: #00b09b; color: white; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .header-right {
                width: 100%;
            }
            .header-right .btn-add {
                width: 100%;
                justify-content: center;
            }
            .service-actions {
                flex-direction: column;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .modal-content {
                width: 95%;
                padding: 20px;
            }
            .modal-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Mis Servicios</h1>
                <div class="breadcrumb">
                    <span>Veterinaria</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Servicios</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-add" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Servicio</span>
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Servicio creado exitosamente</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Servicio actualizado exitosamente</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Servicio eliminado exitosamente</span>
                </div>
            <?php endif; ?>
            
            <div class="services-grid">
                <?php foreach ($servicios as $servicio): ?>
                    <div class="service-card <?php echo $servicio['activo'] ? '' : 'inactive'; ?>">
                        <div style="text-align:center; margin-bottom:15px; color:#00b09b;">
                            <i class="fas <?php echo htmlspecialchars($servicio['icono'] ?? 'fa-paw'); ?>" style="font-size:36px;"></i>
                        </div>
                        <div class="service-header" style="flex-direction:column; align-items:center; text-align:center;">
                            <div>
                                <div class="service-title"><?php echo htmlspecialchars($servicio['nombre']); ?></div>
                                <span class="status-badge <?php echo $servicio['activo'] ? 'status-active' : 'status-inactive'; ?>" style="margin-bottom:10px; display:inline-block;">
                                    <?php echo $servicio['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </div>
                            <div class="service-price">$<?php echo number_format($servicio['precio'], 0); ?></div>
                        </div>
                        
                        <div class="service-description">
                            <?php echo htmlspecialchars($servicio['descripcion'] ?? 'Sin descripción'); ?>
                        </div>
                        
                        <div class="service-meta">
                            <span><i class="far fa-clock"></i> <?php echo $servicio['duracion_minutos']; ?> min</span>
                        </div>
                        
                        <div class="service-actions">
                            <button class="btn-edit" onclick='editService(<?php echo json_encode($servicio); ?>)'>
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn-delete" onclick="deleteService(<?php echo $servicio['id']; ?>, '<?php echo htmlspecialchars($servicio['nombre']); ?>')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($servicios)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #64748b;">
                        <i class="fas fa-stethoscope" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                        <h3>No tienes servicios registrados</h3>
                        <p>Comienza agregando tus primeros servicios veterinarios</p>
                        <button class="btn-add" onclick="openCreateModal()" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Crear Primer Servicio
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Crear/Editar -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Nuevo Servicio</h2>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="serviceForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="crear">
                <input type="hidden" name="id" id="serviceId">
                
                <div class="form-group">
                    <label>Icono del Servicio</label>
                    <div class="icon-picker" id="iconPicker">
                        <div class="icon-option-btn active" data-icon="fa-stethoscope" title="Consulta">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <div class="icon-option-btn" data-icon="fa-syringe" title="Vacuna">
                            <i class="fas fa-syringe"></i>
                        </div>
                        <div class="icon-option-btn" data-icon="fa-shower" title="Baño/Grooming">
                            <i class="fas fa-shower"></i>
                        </div>
                        <div class="icon-option-btn" data-icon="fa-tooth" title="Dental">
                            <i class="fas fa-tooth"></i>
                        </div>
                        <div class="icon-option-btn" data-icon="fa-cut" title="Cirugía">
                            <i class="fas fa-cut"></i>
                        </div>
                        <div class="icon-option-btn" data-icon="fa-x-ray" title="Radiografía">
                            <i class="fas fa-x-ray"></i>
                        </div>
                        <div class="icon-option-btn" data-icon="fa-pills" title="Medicamentos">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="icon-option-btn" data-icon="fa-paw" title="General">
                            <i class="fas fa-paw"></i>
                        </div>
                    </div>
                    <input type="hidden" name="icono" id="serviceIcon" value="fa-stethoscope">
                </div>
                
                <div class="form-group">
                    <label>Nombre del Servicio *</label>
                    <input type="text" name="nombre" id="serviceName" class="form-input" required placeholder="Ej: Consulta General">
                </div>
                
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" id="serviceDescription" class="form-input" placeholder="Describe el servicio..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Precio (COP) *</label>
                        <input type="number" name="precio" id="servicePrice" class="form-input" required placeholder="50000">
                        <div id="priceFormatted" style="font-weight:700; color:#00b09b; margin-top:5px; font-size:14px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Duración (min) *</label>
                        <input type="number" name="duracion_minutos" id="serviceDuration" class="form-input" required placeholder="30">
                    </div>
                </div>
                
                <div class="form-group" id="statusGroup" style="display: none;">
                    <label>Estado</label>
                    <select name="activo" id="serviceStatus" class="form-input">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Guardar Servicio
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function updateIconSelection(iconClass) {
            document.querySelectorAll('.icon-option-btn').forEach(b => b.classList.remove('active'));
            let btn = document.querySelector(`.icon-option-btn[data-icon="${iconClass}"]`);
            if (btn) {
                btn.classList.add('active');
            } else {
                let defaultBtn = document.querySelector('.icon-option-btn[data-icon="fa-paw"]');
                if(defaultBtn) defaultBtn.classList.add('active');
            }
            document.getElementById('serviceIcon').value = iconClass || 'fa-paw';
        }

        document.querySelectorAll('.icon-option-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateIconSelection(this.getAttribute('data-icon'));
            });
        });

        document.getElementById('servicePrice').addEventListener('input', function() {
            let val = parseInt(this.value) || 0;
            document.getElementById('priceFormatted').innerText = '= $' + val.toLocaleString('es-CO') + ' COP';
        });

        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Servicio';
            document.getElementById('formAction').value = 'crear';
            document.getElementById('serviceForm').reset();
            document.getElementById('statusGroup').style.display = 'none';
            document.getElementById('priceFormatted').innerText = '';
            updateIconSelection('fa-stethoscope');
            document.getElementById('serviceModal').classList.add('active');
        }
        
        function editService(service) {
            document.getElementById('modalTitle').textContent = 'Editar Servicio';
            document.getElementById('formAction').value = 'editar';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('serviceName').value = service.nombre;
            document.getElementById('serviceDescription').value = service.descripcion || '';
            document.getElementById('servicePrice').value = service.precio;
            document.getElementById('priceFormatted').innerText = '= $' + parseInt(service.precio).toLocaleString('es-CO') + ' COP';
            document.getElementById('serviceDuration').value = service.duracion_minutos;
            document.getElementById('serviceStatus').value = service.activo;
            updateIconSelection(service.icono);
            document.getElementById('statusGroup').style.display = 'block';
            document.getElementById('serviceModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('serviceModal').classList.remove('active');
        }
        
        function deleteService(id, nombre) {
            if (confirm(`¿Estás seguro de eliminar el servicio "${nombre}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="eliminar">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('serviceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
