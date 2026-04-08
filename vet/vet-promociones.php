<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de veterinaria
checkRole('veterinaria');

$userId = $_SESSION['user_id'];

// Obtener info veterinaria
$stmt = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND tipo = 'veterinaria'");
$stmt->execute([$userId]);
$vet = $stmt->fetch();

if (!$vet) {
    header("Location: vet-dashboard.php");
    exit;
}

$vetId = $vet['id'];

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear') {
        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $descuento = $_POST['descuento_porcentaje'] ?? 0;
        $codigo = $_POST['codigo_cupon'] ?? '';
        $inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fin = $_POST['fecha_fin'] ?? date('Y-m-d', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("INSERT INTO promociones (aliado_id, titulo, descripcion, descuento_porcentaje, codigo_cupon, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vetId, $titulo, $descripcion, $descuento, $codigo, $inicio, $fin]);
        
        header("Location: vet-promociones.php?success=1");
        exit;
    } elseif ($action === 'editar') {
        $id = $_POST['id'] ?? 0;
        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $descuento = $_POST['descuento_porcentaje'] ?? 0;
        $codigo = $_POST['codigo_cupon'] ?? '';
        $inicio = $_POST['fecha_inicio'] ?? '';
        $fin = $_POST['fecha_fin'] ?? '';
        $activo = $_POST['activo'] ?? 1;
        
        $stmt = $pdo->prepare("UPDATE promociones SET titulo = ?, descripcion = ?, descuento_porcentaje = ?, codigo_cupon = ?, fecha_inicio = ?, fecha_fin = ?, activo = ? WHERE id = ? AND aliado_id = ?");
        $stmt->execute([$titulo, $descripcion, $descuento, $codigo, $inicio, $fin, $activo, $id, $vetId]);
        
        header("Location: vet-promociones.php?updated=1");
        exit;
    } elseif ($action === 'eliminar') {
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM promociones WHERE id = ? AND aliado_id = ?");
        $stmt->execute([$id, $vetId]);
        
        header("Location: vet-promociones.php?deleted=1");
        exit;
    }
}

// Obtener promociones
$stmt = $pdo->prepare("SELECT * FROM promociones WHERE aliado_id = ? ORDER BY created_at DESC");
$stmt->execute([$vetId]);
$promociones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Promociones - RUGAL Veterinaria</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .promos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 400px));
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }
        
        @media (min-width: 769px) {
            .promos-grid {
                justify-content: start;
            }
        }
        
        .promo-card {
            background: white;
            border-radius: 15px;
            padding: 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .promo-header {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            padding: 20px;
            color: white;
            position: relative;
        }
        
        .promo-discount {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: #6366f1;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .promo-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            padding-right: 60px;
        }
        
        .promo-code {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            display: inline-block;
        }
        
        .promo-body {
            padding: 20px;
            flex: 1;
        }
        
        .promo-desc {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .promo-dates {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }
        
        .promo-actions {
            padding: 15px 20px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            display: flex;
            gap: 10px;
        }
        
        .btn-edit, .btn-delete {
            flex: 1;
            padding: 8px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-edit { background: #e0e7ff; color: #4338ca; }
        .btn-edit:hover { background: #c7d2fe; }
        
        .btn-delete { background: #fee2e2; color: #991b1b; }
        .btn-delete:hover { background: #fecaca; }
        
        /* Modal Styles */
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
        
        .modal.active { display: flex; }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group { margin-bottom: 15px; }
        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            margin-top: 5px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
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
            .promos-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 100%));
                justify-content: center;
            }
        }
        @media (max-width: 480px) {
            .modal-content {
                width: 95%;
                padding: 20px;
            }
            .promo-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Mis Promociones</h1>
                <div class="breadcrumb">
                    <span>Veterinaria</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Promociones</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-add" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Nueva Promo</span>
                </button>
            </div>
        </header>

        <div class="content-wrapper">
            <?php if (isset($_GET['success'])): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> Promoción creada exitosamente
                </div>
            <?php endif; ?>

            <div class="promos-grid">
                <?php foreach ($promociones as $promo): ?>
                    <div class="promo-card" style="opacity: <?php echo $promo['activo'] ? 1 : 0.6; ?>">
                        <div class="promo-header">
                            <div class="promo-title"><?php echo htmlspecialchars($promo['titulo']); ?></div>
                            <?php if ($promo['codigo_cupon']): ?>
                                <div class="promo-code"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($promo['codigo_cupon']); ?></div>
                            <?php endif; ?>
                            <div class="promo-discount">
                                -<?php echo $promo['descuento_porcentaje']; ?>%
                            </div>
                        </div>
                        
                        <div class="promo-body">
                            <div class="promo-desc">
                                <?php echo htmlspecialchars($promo['descripcion']); ?>
                            </div>
                            <div class="promo-dates">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('d/m/Y', strtotime($promo['fecha_inicio'])); ?> - 
                                <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?>
                            </div>
                        </div>
                        
                        <div class="promo-actions">
                            <button class="btn-edit" onclick='editPromo(<?php echo json_encode($promo); ?>)'>
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn-delete" onclick="deletePromo(<?php echo $promo['id']; ?>, '<?php echo htmlspecialchars($promo['titulo']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($promociones)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #94a3b8;">
                        <i class="fas fa-tags" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                        <h3>No tienes promociones activas</h3>
                        <p>Crea cupones y ofertas para atraer más clientes</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="promoModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 id="modalTitle">Nueva Promoción</h2>
                <button onclick="closeModal()" style="border:none; bg:none; cursor:pointer; font-size:24px;">&times;</button>
            </div>
            
            <form method="POST" id="promoForm">
                <input type="hidden" name="action" id="formAction" value="crear">
                <input type="hidden" name="id" id="promoId">
                
                <div class="form-group">
                    <label>Título *</label>
                    <input type="text" name="titulo" id="promoTitle" class="form-input" required placeholder="Ej: 2x1 en Baño">
                </div>
                
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" id="promoDesc" class="form-input" rows="3"></textarea>
                </div>
                
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
                    <div class="form-group">
                        <label>Descuento (%) *</label>
                        <input type="number" name="descuento_porcentaje" id="promoDiscount" class="form-input" required placeholder="20">
                    </div>
                    <div class="form-group">
                        <label>Código Cupón</label>
                        <input type="text" name="codigo_cupon" id="promoCode" class="form-input" placeholder="VERANO2026">
                    </div>
                </div>
                
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
                    <div class="form-group">
                        <label>Fecha Inicio *</label>
                        <input type="date" name="fecha_inicio" id="promoStart" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha Fin *</label>
                        <input type="date" name="fecha_fin" id="promoEnd" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-group" id="statusGroup" style="display:none;">
                    <label>Estado</label>
                    <select name="activo" id="promoStatus" class="form-input">
                        <option value="1">Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Guardar Promoción</button>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nueva Promoción';
            document.getElementById('formAction').value = 'crear';
            document.getElementById('promoForm').reset();
            document.getElementById('statusGroup').style.display = 'none';
            document.getElementById('promoModal').classList.add('active');
        }
        
        function editPromo(promo) {
            document.getElementById('modalTitle').textContent = 'Editar Promoción';
            document.getElementById('formAction').value = 'editar';
            document.getElementById('promoId').value = promo.id;
            document.getElementById('promoTitle').value = promo.titulo;
            document.getElementById('promoDesc').value = promo.descripcion;
            document.getElementById('promoDiscount').value = promo.descuento_porcentaje;
            document.getElementById('promoCode').value = promo.codigo_cupon;
            document.getElementById('promoStart').value = promo.fecha_inicio;
            document.getElementById('promoEnd').value = promo.fecha_fin;
            document.getElementById('promoStatus').value = promo.activo;
            document.getElementById('statusGroup').style.display = 'block';
            document.getElementById('promoModal').classList.add('active');
        }
        
        function deletePromo(id, title) {
            if(confirm('¿Eliminar ' + title + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="eliminar"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('promoModal').classList.remove('active');
        }
        
        window.onclick = function(e) {
            if (e.target == document.getElementById('promoModal')) closeModal();
        }
    </script>
</body>
</html>
