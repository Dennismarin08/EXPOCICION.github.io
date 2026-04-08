<?php
require_once 'db.php';
require_once 'puntos-functions.php';

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);
$nivelInfo = obtenerInfoNivel($user['nivel'] ?? 'bronce');

// Obtener mascota principal
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ? ORDER BY id ASC LIMIT 1");
$stmt->execute([$userId]);
$mascota = $stmt->fetch() ?: ['id' => 0, 'nombre' => 'Mascota'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Médico - RUGAL</title>
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
                <h1 class="page-title">Historial Médico 📋</h1>
                <div class="breadcrumb">
                    <span>Dashboard</span> <i class="fas fa-chevron-right"></i> <span>Salud</span> <i class="fas fa-chevron-right"></i> <span>Historial</span>
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
                    <!-- Botón para mostrar/ocultar formulario -->
                    <div style="margin-bottom: 20px;">
                        <button onclick="toggleForm()" class="btn-submit" style="background: var(--glass-bg); border: 1px solid var(--glass-border); color: #fff; display: flex; align-items: center; gap: 10px; padding: 12px 20px;">
                            <i class="fas fa-plus-circle"></i> 
                            <span id="btnToggleText">Nuevo Registro Médico</span>
                        </button>
                    </div>

                    <!-- Formulario Nuevo Registro -->
                    <div id="formContainer" class="card" style="margin-bottom: 30px; display: none; overflow: hidden; transition: all 0.3s ease;">
                        <div class="card-header" style="background: rgba(59, 130, 246, 0.1);">
                            <h3><i class="fas fa-file-medical"></i> Registrar Nueva Consulta</h3>
                        </div>
                        <div style="padding: 25px;">
                            <form id="formHistorial">
                                <input type="hidden" name="mascota_id" value="<?php echo $mascota['id']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-calendar-alt"></i> Fecha</label>
                                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-stethoscope"></i> Tipo</label>
                                            <select name="tipo" class="form-control" required>
                                                <option value="Consulta">Consulta General</option>
                                                <option value="Urgencia">Urgencia</option>
                                                <option value="Control">Control / Seguimiento</option>
                                                <option value="Cirugía">Cirugía</option>
                                                <option value="Exámenes">Exámenes</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-comment-medical"></i> Motivo de Consulta</label>
                                    <input type="text" name="motivo" class="form-control" placeholder="Ej: Vómito, Cojera, Chequeo anual..." required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-microscope"></i> Diagnóstico</label>
                                    <textarea name="diagnostico" class="form-control" rows="3" placeholder="Diagnóstico del veterinario..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-pills"></i> Tratamiento / Medicamentos</label>
                                    <textarea name="tratamiento" class="form-control" rows="3" placeholder="Medicamentos recetados, dosis, duración..."></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-user-md"></i> Veterinario</label>
                                            <input type="text" name="veterinario" class="form-control" placeholder="Dr. Juan Pérez">
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label"><i class="fas fa-hospital"></i> Clínica</label>
                                            <input type="text" name="clinica" class="form-control" placeholder="Nombre de la clínica">
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; margin-top: 20px;">
                                    <button type="submit" class="btn-submit" style="width: 100%; padding: 15px;">
                                        <i class="fas fa-save"></i> Guardar Registro Médico
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <!-- Resumen -->
                    <div class="card" style="margin-bottom: 25px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; overflow: hidden; position: relative;">
                        <div style="padding: 25px; position: relative; z-index: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3 style="color: white; margin: 0;">Consultas</h3>
                                <i class="fas fa-notes-medical" style="font-size: 24px; opacity: 0.5;"></i>
                            </div>
                            <div style="font-size: 48px; font-weight: 800; margin-bottom: 5px; line-height: 1;">
                                <?php 
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM historial_medico WHERE mascota_id = ?");
                                $stmt->execute([$mascota['id'] ?? 0]);
                                echo $stmt->fetchColumn(); 
                                ?>
                            </div>
                            <div style="opacity: 0.8; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">Visitas registradas</div>
                        </div>
                        <div style="position: absolute; bottom: -20px; right: -20px; font-size: 120px; color: rgba(255,255,255,0.1); transform: rotate(-15deg);">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                    </div>

                    <!-- Salud General -->
                    <div class="card" style="margin-bottom: 20px; background: var(--glass-bg); backdrop-filter: blur(10px); border: 1px solid var(--glass-border);">
                        <div style="padding: 25px;">
                            <h3 style="margin-bottom: 15px; font-size: 18px;"><i class="fas fa-heartbeat" style="color: #ef4444;"></i> Salud General</h3>
                            <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; text-align: center;">
                                <div style="color: var(--text-dim); font-size: 12px; margin-bottom: 10px; text-transform: uppercase; font-weight: 700;">Estado Estimado</div>
                                <div style="font-size: 20px; font-weight: 700; color: #10b981;">
                                    En proceso... 🐾
                                </div>
                                <p style="font-size: 12px; color: var(--text-dim); margin-top: 10px;">
                                    Estamos analizando sus diagnósticos para darte un resumen pronto.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Línea de Tiempo -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Historial Completo</h3>
                </div>
                <div style="padding: 25px;">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM historial_medico WHERE mascota_id = ? ORDER BY fecha DESC");
                    $stmt->execute([$mascota['id'] ?? 0]);
                    $historial = $stmt->fetchAll();
                    
                    if (empty($historial)): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <p>No hay registros médicos aún.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($historial as $index => $item): 
                                $isIA = ($item['tipo'] === 'IA' || $item['tipo'] === 'comportamiento_ia');
                            ?>
                            <div class="timeline-item <?php echo $isIA ? 'ia-entry' : ''; ?>" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                <div class="timeline-content">
                                    <div class="item-header">
                                        <div style="flex: 1;">
                                            <div class="item-badge <?php echo $isIA ? 'badge-ia' : 'badge-manual'; ?>">
                                                <i class="<?php echo $isIA ? 'fas fa-robot' : 'fas fa-notes-medical'; ?>"></i>
                                                <?php echo $isIA ? 'Asistente IA' : htmlspecialchars($item['tipo']); ?>
                                            </div>
                                            <h4 class="item-title"><?php echo htmlspecialchars($item['motivo']); ?></h4>
                                        </div>
                                        <div class="item-date">
                                            <span class="date-day"><?php echo date('d', strtotime($item['fecha'])); ?></span>
                                            <span class="date-month"><?php echo date('M Y', strtotime($item['fecha'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($item['diagnostico']): ?>
                                    <div class="diagnosis-box">
                                        <div style="font-size: 12px; color: var(--text-dim); margin-bottom: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                            <?php echo $isIA ? 'Interacción del Usuario' : 'Diagnóstico Médico'; ?>
                                        </div>
                                        <div style="color: #e2e8f0;">
                                            <?php 
                                                $diag = $item['diagnostico'];
                                                if (stripos($diag, 'el usuario diagnostico') === 0) {
                                                    echo htmlspecialchars($diag);
                                                } else {
                                                    echo htmlspecialchars(str_replace(['El cliente preguntó: "', '"'], '', $diag));
                                                }
                                            ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($item['tratamiento']): ?>
                                    <div class="treatment-box">
                                        <div style="font-size: 11px; opacity: 0.7; margin-bottom: 5px; font-weight: 700; text-transform: uppercase;">
                                            <?php echo $isIA ? 'Respuesta del Asistente IA' : 'Tratamiento Recomendado'; ?>
                                        </div>
                                        <?php echo htmlspecialchars(str_replace('Respuesta de la IA: ', '', $item['tratamiento'])); ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="item-footer">
                                        <?php if ($item['veterinario']): ?>
                                        <div class="footer-info">
                                            <i class="fas fa-user-md"></i> <?php echo htmlspecialchars($item['veterinario']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($item['clinica']): ?>
                                        <div class="footer-info">
                                            <i class="fas fa-hospital"></i> <?php echo htmlspecialchars($item['clinica']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --accent-primary: #3b82f6;
            --accent-ia: #8b5cf6;
            --text-dim: #94a3b8;
        }

        .timeline {
            position: relative;
            padding: 20px 0;
            margin-left: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: -2px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--accent-primary), var(--accent-ia), transparent);
            border-radius: 2px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 40px;
            padding-left: 40px;
            animation: slideIn 0.5s ease-out forwards;
            opacity: 0;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -11px;
            top: 20px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #0f172a;
            border: 3px solid var(--accent-primary);
            box-shadow: 0 0 15px var(--accent-primary);
            z-index: 2;
        }

        .timeline-item.ia-entry::before {
            border-color: var(--accent-ia);
            box-shadow: 0 0 15px var(--accent-ia);
        }

        .timeline-content {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .timeline-content::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.1), transparent);
            pointer-events: none;
        }

        .timeline-content:hover {
            transform: translateY(-5px) scale(1.01);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            gap: 15px;
        }

        .item-badge {
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-manual {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-ia {
            background: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .item-date {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            min-width: 80px;
        }

        .date-day {
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
            color: #fff;
        }

        .date-month {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-dim);
            text-transform: uppercase;
        }

        .item-title {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 10px 0;
        }

        .diagnosis-box {
            margin: 15px 0;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            border-left: 4px solid var(--accent-primary);
        }

        .ia-entry .diagnosis-box {
            border-left-color: var(--accent-ia);
        }

        .treatment-box {
            padding: 15px;
            background: rgba(16, 185, 129, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.1);
            color: #d1fae5;
            font-size: 14px;
            line-height: 1.6;
        }

        .ia-entry .treatment-box {
            background: rgba(139, 92, 246, 0.05);
            border-color: rgba(139, 92, 246, 0.1);
            color: #ede9fe;
        }

        .item-footer {
            margin-top: 15px;
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: var(--text-dim);
        }

        .footer-info {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Form styling improvements */
        .form-control {
            background: rgba(0, 0, 0, 0.2) !important;
            border: 1px solid var(--glass-border) !important;
            color: #fff !important;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--accent-primary) !important;
            background: rgba(0, 0, 0, 0.3) !important;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.2) !important;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent-primary), #2563eb);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            border: none;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
    </style>
    
    <script>
        function toggleForm() {
            const container = document.getElementById('formContainer');
            const btnText = document.getElementById('btnToggleText');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                btnText.innerText = 'Cancelar Registro';
            } else {
                container.style.display = 'none';
                btnText.innerText = 'Nuevo Registro Médico';
            }
        }

        document.getElementById('formHistorial').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('ajax-save-historial.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registro guardado correctamente');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el registro');
            });
        });
    </script>
    </div>
</body>
</html>
