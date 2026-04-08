<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../puntos-functions.php';

// Verificar sesión y rol de admin
checkRole('admin');

// Obtener todas las tareas activas
$stmt = $pdo->prepare("SELECT * FROM tareas_comunidad WHERE activa = 1 ORDER BY tipo, puntos DESC");
$stmt->execute();
$tareas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tareas - RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard-extra.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/color-fixes.css">
    <style>
        /* Mejoras de diseño para admin-tareas-gestion */
        .admin-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
            color: white !important;
            padding: 20px 30px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1) !important;
        }

        .btn-create {
            background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%) !important;
            color: white !important;
            border: none !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 176, 155, 0.3) !important;
            transition: all 0.3s ease !important;
        }

        .btn-create:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(0, 176, 155, 0.4) !important;
        }

        .tareas-table {
            width: 100% !important;
            border-collapse: collapse !important;
            background: rgba(255, 255, 255, 0.9) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important;
        }

        .tareas-table th,
        .tareas-table td {
            padding: 15px 20px !important;
            text-align: left !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }

        .tareas-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
            font-weight: 600 !important;
            color: #475569 !important;
            text-transform: uppercase !important;
            font-size: 11px !important;
            letter-spacing: 0.05em !important;
        }

        .tareas-table tr:hover {
            background-color: rgba(59, 130, 246, 0.02) !important;
        }

        .tipo-badge,
        .categoria-badge {
            padding: 6px 12px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            text-transform: capitalize !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
        }

        .tipo-diaria {
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%) !important;
            color: #0369a1 !important;
        }

        .tipo-semanal {
            background: linear-gradient(135deg, #f0fdf4 0%, #bbf7d0 100%) !important;
            color: #15803d !important;
        }

        .tipo-salud {
            background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%) !important;
            color: #b91c1c !important;
        }

        .tipo-especial {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
            color: #b45309 !important;
        }

        .tipo-tiempo_limite {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
            color: #991b1b !important;
        }

        .cat-ejercicio {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        .cat-educacion {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            color: white !important;
        }

        .cat-salud {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        .cat-comunidad {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
            color: white !important;
        }

        .cat-otros {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
            color: white !important;
        }

        .icon-preview {
            width: 30px !important;
            height: 30px !important;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #64748b !important;
        }

        .action-btns {
            display: flex !important;
            gap: 8px !important;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 12px !important;
            border-radius: 8px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
        }

        .btn-edit {
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%) !important;
            color: #0369a1 !important;
        }

        .btn-edit:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(3, 105, 161, 0.2) !important;
        }

        .btn-delete {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
            color: #991b1b !important;
        }

        .btn-delete:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(153, 27, 27, 0.2) !important;
        }

        .form-group {
            margin-bottom: 15px !important;
        }

        .form-group label {
            display: block !important;
            margin-bottom: 5px !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            font-size: 14px !important;
        }

        .form-control {
            width: 100% !important;
            padding: 12px !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            font-family: inherit !important;
            font-size: 14px !important;
            color: #1e293b !important;
            background: #ffffff !important;
            transition: border-color 0.3s ease !important;
        }

        .form-control:focus {
            outline: none !important;
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }

        .form-control::placeholder {
            color: #94a3b8 !important;
        }

        .checkbox-group {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        /* Modal improvements */
        .modal-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(4px) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 1000 !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: all 0.3s ease !important;
        }

        .modal-overlay.active {
            opacity: 1 !important;
            visibility: visible !important;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(12px) !important;
            border-radius: 16px !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3) !important;
            max-width: 600px !important;
            width: 90% !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
            transform: scale(0.9) !important;
            transition: transform 0.3s ease !important;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1) !important;
        }

        .modal-header {
            padding: 20px 30px !important;
            border-bottom: 1px solid #e2e8f0 !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }

        .modal-header h3 {
            margin: 0 !important;
            color: #1e293b !important;
            font-size: 18px !important;
            font-weight: 600 !important;
        }

        .modal-close {
            background: none !important;
            border: none !important;
            font-size: 20px !important;
            cursor: pointer !important;
            color: #64748b !important;
            padding: 5px !important;
            border-radius: 50% !important;
            transition: all 0.2s ease !important;
        }

        .modal-close:hover {
            background: #f1f5f9 !important;
            color: #1e293b !important;
        }

        .modal-body {
            padding: 30px !important;
        }

        .modal-footer {
            padding: 20px 30px !important;
            border-top: 1px solid #e2e8f0 !important;
            display: flex !important;
            justify-content: flex-end !important;
            gap: 10px !important;
        }

        .btn-cancel,
        .btn-submit {
            padding: 10px 20px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .btn-cancel {
            background: #f1f5f9 !important;
            color: #64748b !important;
            border: 1px solid #e2e8f0 !important;
        }

        .btn-cancel:hover {
            background: #e2e8f0 !important;
        }

        .btn-submit {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
            color: white !important;
            border: none !important;
        }

        .btn-submit:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tareas-table {
            animation: fadeInUp 0.5s ease-out !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column !important;
                gap: 15px !important;
                text-align: center !important;
            }

            .tareas-table {
                font-size: 14px !important;
            }

            .tareas-table th,
            .tareas-table td {
                padding: 10px !important;
            }

            .modal-content {
                width: 95% !important;
                margin: 20px !important;
            }

            .modal-body {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-admin.php'; ?>

    <div class="main-content">
        <div class="admin-header">
            <h1 class="page-title" style="color: white; font-size: 24px;">Gestión de Tareas</h1>
            <button class="btn-create" onclick="abrirModal()">
                <i class="fas fa-plus"></i> Nueva Tarea
            </button>
        </div>
        
        <div class="content-wrapper">
            <div class="table-responsive">
                <table class="tareas-table">
                    <thead>
                        <tr>
                            <th>Icono</th>
                            <th>Tarea</th>
                            <th>Categoría</th>
                            <th>Nivel de Acceso</th>
                            <th>Tipo</th>
                            <th>Puntos</th>
                            <th>Guía</th>
                            <th>Validación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tareas as $t): ?>
                            <tr>
                                <td>
                                    <div class="icon-preview">
                                        <i class="<?php echo $t['icono']; ?>"></i>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($t['titulo']); ?></div>
                                    <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($t['descripcion']); ?></div>
                                </td>
                                <td>
                                    <?php $cat = $t['categoria'] ?? 'otros'; ?>
                                    <span class="categoria-badge cat-<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucfirst($cat)); ?></span>
                                </td>
                                <td>
                                    <?php if ($t['tipo_acceso'] === 'premium'): ?>
                                        <span class="tipo-badge" style="background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%); color: #e0e7ff; border: 1px solid #6366f1;">
                                            <i class="fas fa-crown"></i> PREMIUM
                                        </span>
                                    <?php else: ?>
                                        <span class="tipo-badge" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">
                                            FREE
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="tipo-badge tipo-<?php echo $t['tipo']; ?>">
                                        <?php echo ucfirst($t['tipo'] === 'tiempo_limite' ? 'Tiempo Lim.' : $t['tipo']); ?>
                                    </span>
                                    <?php if($t['tipo'] === 'tiempo_limite' && $t['fecha_limite']): ?>
                                        <div style="font-size:10px; color:#ef4444; margin-top:2px;">
                                            <i class="fas fa-clock"></i> <?php echo date('d/m H:i', strtotime($t['fecha_limite'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: bold; color: #667eea;">
                                    +<?php echo $t['puntos']; ?>
                                </td>
                                <td>
                                    <?php if (!empty($t['video_url'])): ?>
                                        <button class="btn-edit" onclick="abrirVideo('<?php echo htmlspecialchars($t['video_url']); ?>')" title="Ver guía">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size:12px; color:#94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['requiere_evidencia']): ?>
                                        <div style="font-size: 12px; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-camera"></i> <?php echo ucfirst($t['tipo_evidencia']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #94a3b8;">Automática</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-edit" onclick='editarTarea(<?php echo json_encode($t); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-delete" onclick="eliminarTarea(<?php echo $t['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Nueva/Editar Tarea -->
    <div class="modal-overlay" id="modalTarea">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Nueva Tarea</h3>
                <button class="modal-close" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="formTarea" enctype="multipart/form-data">
                    <input type="hidden" id="tareaId" name="id">
                    <input type="hidden" name="action" id="formAction" value="crear_tarea">
                    
                    <div class="form-group">
                        <label>Título</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ej: Paseo matutino">
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" id="descripcion" class="form-control" rows="2" placeholder="Breve descripción de la tarea"></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Puntos</label>
                            <input type="number" name="puntos" id="puntos" class="form-control" required value="10">
                        </div>
                        
                        <div class="form-group">
                            <label>Tipo de Tarea</label>
                            <select name="tipo" id="tipo" class="form-control" onchange="toggleFechaLimite()">
                                <option value="diaria">Diaria</option>
                                <option value="semanal">Semanal</option>
                                <option value="salud">Salud</option>
                                <option value="especial">Especial</option>
                                <option value="tiempo_limite">Tiempo Límite ⏱️</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Nivel de Acceso</label>
                            <select name="tipo_acceso" id="tipo_acceso" class="form-control">
                                <option value="free">🔓 FREE (Para todos)</option>
                                <option value="premium">👑 PREMIUM (Solo suscriptores)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="fechaLimiteContainer" style="display: none;">
                        <label>Fecha Límite (Requerido para Tiempo Límite)</label>
                        <input type="datetime-local" name="fecha_limite" id="fecha_limite" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Categoría</label>
                        <select name="categoria" id="categoria" class="form-control">
                            <option value="ejercicio">Ejercicio</option>
                            <option value="educacion">Educación</option>
                            <option value="salud">Salud</option>
                            <option value="comunidad">Comunidad</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Detalles (guía / pasos)</label>
                        <textarea name="detalles" id="detalles" class="form-control" rows="3" placeholder="Explica pasos, duración, recomendaciones..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Icono (FontAwesome)</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="icono" id="icono" class="form-control" placeholder="fas fa-star" value="fas fa-star" onchange="previewIcon()">
                            <div class="icon-preview" id="iconPreview" style="width: 42px; height: 42px; flex-shrink: 0;">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="background: #f8fafc; padding: 15px; border-radius: 8px;">
                        <div class="checkbox-group" style="margin-bottom: 10px;">
                            <input type="checkbox" name="requiere_evidencia" id="requiere_evidencia" onchange="toggleTipoEvidencia()">
                            <label for="requiere_evidencia" style="margin: 0; cursor: pointer;">Requiere Validación (Foto/Video)</label>
                        </div>
                        
                        <div id="tipoEvidenciaContaier" style="display: none; margin-left: 25px;">
                            <label style="font-size: 13px; margin-bottom: 5px;">Tipo de Evidencia:</label>
                            <select name="tipo_evidencia" id="tipo_evidencia" class="form-control" style="font-size: 13px;">
                                <option value="foto">Solo Foto</option>
                                <option value="video">Solo Video</option>
                                <option value="ambos">Foto o Video</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Video guía (opcional)</label>
                        <input type="file" name="video" id="video" accept="video/*" class="form-control" onchange="validarVideo(this)">
                        <small style="color:#64748b;">Sube un video corto que sirva de guía para la tarea. (Máx 20MB)</small>
                        <div id="videoStatus"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                <button class="btn-submit" onclick="guardarTarea()">Guardar Tarea</button>
            </div>
        </div>
    </div>

    <!-- Modal Video Guía -->
    <div class="modal-overlay" id="modalVideo" style="display:none;">
        <div class="modal-content" style="max-width:800px;">
            <div class="modal-header">
                <h3>Guía de la Tarea</h3>
                <button class="modal-close" onclick="cerrarVideo()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" style="padding:0;">
                <video id="videoPlayer" controls style="width:100%; height:auto; background:black;"></video>
            </div>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('formTarea').reset();
            document.getElementById('modalTitle').textContent = 'Nueva Tarea';
            document.getElementById('formAction').value = 'crear_tarea';
            document.getElementById('tareaId').value = '';
            document.getElementById('videoStatus').innerHTML = ''; // Reset status
            toggleTipoEvidencia();
            toggleFechaLimite(); // Check logic
            previewIcon();
            document.getElementById('modalTarea').classList.add('active');
        }
        
        function editarTarea(tarea) {
            document.getElementById('modalTitle').textContent = 'Editar Tarea';
            document.getElementById('formAction').value = 'editar_tarea';
            document.getElementById('tareaId').value = tarea.id;
            
            document.getElementById('titulo').value = tarea.titulo;
            document.getElementById('descripcion').value = tarea.descripcion;
            document.getElementById('categoria').value = tarea.categoria || 'otros';
            document.getElementById('detalles').value = tarea.detalles || '';
            document.getElementById('puntos').value = tarea.puntos;
            document.getElementById('tipo').value = tarea.tipo;
            document.getElementById('tipo_acceso').value = tarea.tipo_acceso || 'free';
            document.getElementById('icono').value = tarea.icono;
            
            // Mostrar estado del video
            const statusDiv = document.getElementById('videoStatus');
            if(tarea.video_url) {
                statusDiv.innerHTML = `<div style="margin-top:5px; padding:5px; background:#f0fdf4; color:#166534; border-radius:5px; font-size:12px;">
                    <i class="fas fa-check-circle"></i> Video guía actual cargado
                </div>`;
            } else {
                statusDiv.innerHTML = `<div style="margin-top:5px; font-size:12px; color:#64748b;">No hay video guía asignado actualmente.</div>`;
            }
            
            // Fecha limite
            if (tarea.fecha_limite) {
                document.getElementById('fecha_limite').value = tarea.fecha_limite.replace(' ', 'T');
            } else {
                document.getElementById('fecha_limite').value = '';
            }
            
            document.getElementById('requiere_evidencia').checked = tarea.requiere_evidencia == 1;
            document.getElementById('tipo_evidencia').value = tarea.tipo_evidencia;
            
            toggleTipoEvidencia();
            toggleFechaLimite();
            previewIcon();
            document.getElementById('modalTarea').classList.add('active');
        }
        
        function cerrarModal() {
            document.getElementById('modalTarea').classList.remove('active');
        }
        
        function previewIcon() {
            const iconClass = document.getElementById('icono').value;
            document.getElementById('iconPreview').innerHTML = `<i class="${iconClass}"></i>`;
        }
        
        function toggleTipoEvidencia() {
            const isChecked = document.getElementById('requiere_evidencia').checked;
            document.getElementById('tipoEvidenciaContaier').style.display = isChecked ? 'block' : 'none';
        }
        
        function guardarTarea() {
            const form = document.getElementById('formTarea');
            const formData = new FormData(form);
            
            fetch('admin-procesar-tareas.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar');
            });
        }
        
        function eliminarTarea(id) {
            if (!confirm('¿Estás seguro de eliminar esta tarea?')) return;
            
            fetch('admin-procesar-tareas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=eliminar_tarea&id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Tarea eliminada');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        function toggleFechaLimite() {
            const tipo = document.getElementById('tipo').value;
            const container = document.getElementById('fechaLimiteContainer');
            if (tipo === 'tiempo_limite') {
                container.style.display = 'block';
                document.getElementById('fecha_limite').required = true;
            } else {
                container.style.display = 'none';
                document.getElementById('fecha_limite').required = false;
            }
        }
        
        function abrirVideo(url) {
            const modal = document.getElementById('modalVideo');
            const player = document.getElementById('videoPlayer');
            player.src = url;
            modal.style.display = 'flex';
            modal.classList.add('active');
        }

        function cerrarVideo() {
            const modal = document.getElementById('modalVideo');
            const player = document.getElementById('videoPlayer');
            player.pause();
            player.src = '';
            modal.style.display = 'none';
            modal.classList.remove('active');
        }
        
        function validarVideo(input) {
            const file = input.files[0];
            if(file) {
                 if(file.size > 5 * 1024 * 1024) {
                     alert("El video es demasiado grande (Máximo 5MB). Por favor comprímelo o sube uno más corto.");
                     input.value = '';
                     return;
                 }
            }
        }
    </script>
</body>
</html>
