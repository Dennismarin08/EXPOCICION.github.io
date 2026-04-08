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

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Configuración de subida
    $baseUploadDir = __DIR__ . '/../uploads/productos_vet/'; // Ruta física absoluta y segura
    $dbUploadDir = 'uploads/productos_vet/';      // Ruta para guardar en BD
    
    if (!file_exists($baseUploadDir)) mkdir($baseUploadDir, 0777, true);
    
    if ($action === 'crear') {
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $precio = $_POST['precio'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        
        $imagenPath = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_') . '.' . $ext;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $baseUploadDir . $filename)) {
                $imagenPath = $dbUploadDir . $filename;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO productos_veterinaria (veterinaria_id, nombre, descripcion, categoria, precio, stock, imagen) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vetInfo['aliado_id'], $nombre, $descripcion, $categoria, $precio, $stock, $imagenPath]);
        
        header("Location: vet-productos.php?success=1");
        exit;
    } elseif ($action === 'editar') {
        $id = $_POST['id'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $precio = $_POST['precio'] ?? 0;
        $stock = $_POST['stock'] ?? 0;
        $activo = $_POST['activo'] ?? 1;
        
        $sql = "UPDATE productos_veterinaria SET nombre = ?, descripcion = ?, categoria = ?, precio = ?, stock = ?, activo = ?";
        $params = [$nombre, $descripcion, $categoria, $precio, $stock, $activo];
        
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_') . '.' . $ext;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $baseUploadDir . $filename)) {
                $sql .= ", imagen = ?";
                $params[] = $dbUploadDir . $filename;
            }
        }
        
        $sql .= " WHERE id = ? AND veterinaria_id = ?";
        $params[] = $id;
        $params[] = $vetInfo['aliado_id'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        header("Location: vet-productos.php?updated=1");
        exit;
    } elseif ($action === 'eliminar') {
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM productos_veterinaria WHERE id = ? AND veterinaria_id = ?");
        $stmt->execute([$id, $vetInfo['aliado_id']]);
        
        header("Location: vet-productos.php?deleted=1");
        exit;
    }
}

// Obtener productos
$stmt = $pdo->prepare("SELECT * FROM productos_veterinaria WHERE veterinaria_id = ? ORDER BY nombre");
$stmt->execute([$vetInfo['aliado_id']]);
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Productos - RUGAL Veterinaria</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover { transform: translateY(-5px); }
        
        .product-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
            background: #f1f5f9;
        }
        
        .no-image {
            width: 100%;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #cbd5e1;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 40px;
        }
        
        .product-category {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .product-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .product-price {
            font-size: 24px;
            font-weight: 800;
            color: #00b09b;
            margin-bottom: 10px;
        }
        
        .product-stock {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stock-level { width: 8px; height: 8px; border-radius: 50%; }
        .stock-high { background-color: #10b981; }
        .stock-medium { background-color: #f59e0b; }
        .stock-low { background-color: #ef4444; }
        
        .product-description {
            color: #64748b;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
            height: 42px;
            overflow: hidden;
            flex-grow: 1;
        }
        
        .product-actions { display: flex; gap: 10px; }
        
        .btn-edit, .btn-delete {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-edit { background: #667eea; color: white; }
        .btn-edit:hover { background: #5568d3; }
        
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 20px; padding: 30px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .form-input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; margin-top: 5px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 500px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        .form-group { margin-bottom: 15px; }
        .btn-submit { width: 100%; padding: 15px; background: #00b09b; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Gestión de Productos</h1>
                <div class="breadcrumb">
                    <span>Veterinaria</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Productos</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-add" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Producto</span>
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
             <?php if (isset($_GET['success'])): ?>
                <div style="background:#d1fae5; color:#065f46; padding:15px; border-radius:10px; margin-bottom:20px;">
                    <i class="fas fa-check-circle"></i> Producto creado exitosamente
                </div>
            <?php endif; ?>
            
            <div class="products-grid">
                <?php foreach ($productos as $producto): ?>
                    <?php 
                        $stockClass = 'stock-high';
                        if ($producto['stock'] < 10) $stockClass = 'stock-low';
                        elseif ($producto['stock'] < 30) $stockClass = 'stock-medium';
                    ?>
                    <div class="product-card">
                        <?php $imgSrc = buildImgUrl($producto['imagen']); ?>
                        <?php if ($producto['imagen']): ?>
                            <img src="<?php echo $imgSrc; ?>" class="product-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="no-image" style="display:none;"><i class="fas fa-image"></i></div>
                        <?php else: ?>
                            <div class="no-image"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                        
                        <span class="product-category"><?php echo htmlspecialchars($producto['categoria']); ?></span>
                        <div class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                        <div class="product-price">$<?php echo number_format($producto['precio'], 0); ?></div>
                        <div class="product-stock">
                            <div class="stock-level <?php echo $stockClass; ?>"></div>
                            Stock: <?php echo $producto['stock']; ?> unidades
                        </div>
                        <div class="product-description">
                            <?php echo htmlspecialchars($producto['descripcion']); ?>
                        </div>
                        <div class="product-actions">
                            <button class="btn-edit" onclick='editProduct(<?php echo json_encode($producto); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="deleteProduct(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($productos)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #64748b;">
                        <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                        <h3>No tienes productos registrados</h3>
                        <p>Agrega los productos que vendes en tu veterinaria</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h2 id="modalTitle">Nuevo Producto</h2>
                <button onclick="closeModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            
            <form method="POST" id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="crear">
                <input type="hidden" name="id" id="productId">
                
                <div class="form-group">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="nombre" id="productName" class="form-input" required>
                </div>

                <!-- Imagen Optional -->
                <div class="form-group">
                    <label>Imagen del Producto (Opcional)</label>
                    <input type="file" name="imagen" id="productImage" class="form-input" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoria" id="productCategory" class="form-input">
                        <option value="Medicamentos">Medicamentos</option>
                        <option value="Alimentos">Alimentos</option>
                        <option value="Higiene">Higiene</option>
                        <option value="Juguetes">Juguetes</option>
                        <option value="Accesorios">Accesorios</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" id="productDesc" class="form-input" rows="3"></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Precio *</label>
                        <input type="number" name="precio" id="productPrice" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Inicial *</label>
                        <input type="number" name="stock" id="productStock" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-group" id="activeGroup" style="display:none;">
                    <label>Estado</label>
                    <select name="activo" id="productActive" class="form-input">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Guardar Producto</button>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Producto';
            document.getElementById('formAction').value = 'crear';
            document.getElementById('productId').value = '';
            document.getElementById('productForm').reset();
            document.getElementById('activeGroup').style.display = 'none';
            document.getElementById('productModal').classList.add('active');
        }
        
        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Editar Producto';
            document.getElementById('formAction').value = 'editar';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.nombre;
            document.getElementById('productCategory').value = product.categoria;
            document.getElementById('productDesc').value = product.descripcion;
            document.getElementById('productPrice').value = product.precio;
            document.getElementById('productStock').value = product.stock;
            document.getElementById('productActive').value = product.activo;
            document.getElementById('activeGroup').style.display = 'block';
            document.getElementById('productModal').classList.add('active');
        }
        
        function deleteProduct(id, name) {
            if(confirm('¿Eliminar ' + name + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="eliminar"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('productModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
