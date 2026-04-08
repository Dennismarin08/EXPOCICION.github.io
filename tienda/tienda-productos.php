<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de tienda
checkRole('tienda');

$userId = $_SESSION['user_id'];

// Obtener información de la tienda
$stmt = $pdo->prepare("
    SELECT a.id as tienda_id, a.* 
    FROM aliados a
    WHERE a.usuario_id = ? AND a.tipo = 'tienda'
");
$stmt->execute([$userId]);
$tiendaInfo = $stmt->fetch();

if (!$tiendaInfo) {
    header("Location: tienda-dashboard.php");
    exit;
}

$tiendaId = $tiendaInfo['tienda_id'];

// Procesar acciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear') {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $categoria = $_POST['categoria'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $peso = $_POST['peso'] ?? null;
        
        // Manejo de Foto
        $fotoUrl = null;
        if (isset($_FILES['foto_producto']) && $_FILES['foto_producto']['error'] === 0) {
            $uploadDir = __DIR__ . '/uploads/productos/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['foto_producto']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_') . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['foto_producto']['tmp_name'], $targetPath)) {
                $fotoUrl = 'uploads/productos/' . $filename;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO productos_tienda (tienda_id, nombre, descripcion, categoria, precio, stock, destacado, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tiendaId, $nombre, $descripcion, $categoria, $precio, $stock, $destacado, $fotoUrl]);
        
        header("Location: tienda-productos.php?success=created");
        exit;
    } elseif ($action === 'editar') {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $categoria = $_POST['categoria'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $activo = $_POST['activo'];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $peso = $_POST['peso'] ?? null;
        
        // Manejo de Foto
        $fotoUrl = null;
        if (isset($_FILES['foto_producto']) && $_FILES['foto_producto']['error'] === 0) {
            $uploadDir = __DIR__ . '/uploads/productos/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['foto_producto']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_') . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['foto_producto']['tmp_name'], $targetPath)) {
                $fotoUrl = 'uploads/productos/' . $filename;
            }
        }
        
        if ($fotoUrl) {
            $stmt = $pdo->prepare("UPDATE productos_tienda SET nombre = ?, descripcion = ?, categoria = ?, precio = ?, stock = ?, activo = ?, destacado = ?, imagen = ? WHERE id = ? AND tienda_id = ?");
            $stmt->execute([$nombre, $descripcion, $categoria, $precio, $stock, $activo, $destacado, $fotoUrl, $id, $tiendaId]);
        } else {
            $stmt = $pdo->prepare("UPDATE productos_tienda SET nombre = ?, descripcion = ?, categoria = ?, precio = ?, stock = ?, activo = ?, destacado = ? WHERE id = ? AND tienda_id = ?");
            $stmt->execute([$nombre, $descripcion, $categoria, $precio, $stock, $activo, $destacado, $id, $tiendaId]);
        }
        
        header("Location: tienda-productos.php?success=updated");
        exit;
    } elseif ($action === 'eliminar') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM productos_tienda WHERE id = ? AND tienda_id = ?");
        $stmt->execute([$id, $tiendaId]);
        
        header("Location: tienda-productos.php?success=deleted");
        exit;
    }
}

// Obtener productos
$stmt = $pdo->prepare("SELECT * FROM productos_tienda WHERE tienda_id = ? ORDER BY nombre");
$stmt->execute([$tiendaId]);
$productos = $stmt->fetchAll();

// Categorías predefinidas
$categorias = ['Alimentos', 'Juguetes', 'Accesorios', 'Higiene', 'Ropa', 'Camas', 'Otros'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Productos - RUGAL Tienda</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .products-table th, .products-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .products-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #64748b;
        }
        
        .product-img-preview {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-stock-high { background: #d1fae5; color: #065f46; }
        .badge-stock-low { background: #fee2e2; color: #991b1b; }
        .badge-category { background: #e0e7ff; color: #4338ca; }
        
        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-edit { background: #eff6ff; color: #3b82f6; }
        .btn-edit:hover { background: #dbeafe; }
        
        .btn-delete { background: #fef2f2; color: #ef4444; }
        .btn-delete:hover { background: #fee2e2; }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
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
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-full { grid-column: 1 / -1; }
        
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
            background: #ff7e5f;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .photo-preview {
            width: 100%;
            height: 150px;
            border-radius: 12px;
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .photo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .photo-preview i {
            font-size: 40px;
            color: #cbd5e1;
        }

    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-tienda.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Inventario de Productos</h1>
                <div class="breadcrumb">
                    <span>Tienda</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Productos</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-add" onclick="openCreateModal()" style="background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%); color:white; padding:12px 24px; border:none; border-radius:12px; cursor:pointer; font-weight:bold; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-plus"></i>
                    <span>Nuevo Producto</span>
                </button>
            </div>
        </header>

        <div class="content-wrapper">
             <?php if (empty($productos)): ?>
                <div style="text-align: center; padding: 60px; color: #94a3b8;">
                    <i class="fas fa-box-open" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3>Tu inventario está vacío</h3>
                    <p>Comienza agregando los productos que vendes en tu tienda.</p>
                </div>
            <?php else: ?>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:15px;">
                                        <div class="product-img-preview">
                                            <?php if(!empty($producto['imagen'])): ?>
                                                <img src="<?php echo BASE_URL . '/tienda/' . htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:8px;">
                                            <?php else: ?>
                                                <i class="fas fa-image"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:bold; color:#1e293b;"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                            <?php if($producto['destacado']): ?>
                                                <span style="font-size:10px; color:#f59e0b;"><i class="fas fa-star"></i> Destacado</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge badge-category"><?php echo htmlspecialchars($producto['categoria']); ?></span></td>
                                <td style="font-weight:bold; color:#ff7e5f;">$<?php echo number_format($producto['precio'], 0); ?></td>
                                <td>
                                    <span class="badge <?php echo $producto['stock'] < 10 ? 'badge-stock-low' : 'badge-stock-high'; ?>">
                                        <?php echo $producto['stock']; ?> unids.
                                    </span>
                                </td>
                                <td>
                                    <?php if($producto['activo']): ?>
                                        <span style="color:#10b981;"><i class="fas fa-check-circle"></i> Activo</span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;"><i class="fas fa-times-circle"></i> Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <button class="btn-action btn-edit" onclick='editProduct(<?php echo json_encode($producto); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteProduct(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 id="modalTitle">Nuevo Producto</h2>
                <button onclick="closeModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            
            <form method="POST" id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="crear">
                <input type="hidden" name="id" id="productId">
                
                <!-- Preview de Foto -->
                <div id="photoPreviewContainer" class="photo-preview">
                    <i class="fas fa-image"></i>
                </div>
                
                <!-- Input de Foto -->
                <div class="form-group form-full">
                    <label>Foto del Producto</label>
                    <input type="file" name="foto_producto" id="fotoProd" class="form-input" accept="image/*" onchange="previewPhoto()">
                    <small style="color:#64748b; margin-top:5px; display:block;">PNG, JPG o GIF (Max 2MB)</small>
                </div>
                
                <div class="form-group">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="nombre" id="prodName" class="form-input" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Categoría *</label>
                        <select name="categoria" id="prodCat" class="form-input" required onchange="togglePesoSelect()">
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="pesoGroup" style="display:none;">
                        <label>Peso/Kilaje *</label>
                        <select name="peso" id="prodPeso" class="form-input">
                            <option value="">-- Seleccionar peso --</option>
                            <option value="1 kg">1 kg</option>
                            <option value="2 kg">2 kg</option>
                            <option value="4 kg">4 kg</option>
                            <option value="8 kg">8 kg</option>
                            <option value="12 kg">12 kg</option>
                            <option value="15 kg">15 kg</option>
                            <option value="18 kg">18 kg</option>
                            <option value="20 kg">20 kg</option>
                            <option value="22.7 kg (50 lb)">22.7 kg (50 lb)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Precio *</label>
                        <input type="number" name="precio" id="prodPrice" class="form-input" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Stock Inicial *</label>
                        <input type="number" name="stock" id="prodStock" class="form-input" required>
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; top:15px; position:relative;">
                        <input type="checkbox" name="destacado" id="prodDestacado" style="width:20px; height:20px; margin-right:10px;">
                        <label for="prodDestacado">Producto Destacado</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" id="prodDesc" class="form-input" rows="3"></textarea>
                </div>

                <div class="form-group" id="activeGroup" style="display:none;">
                    <label>Estado</label>
                    <select name="activo" id="prodActive" class="form-input">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Guardar Producto</button>
            </form>
        </div>
    </div>

    <script>
        const pesoOptions = ['1 kg', '2 kg', '4 kg', '8 kg', '12 kg', '15 kg', '18 kg', '20 kg', '22.7 kg (50 lb)'];
        
        function previewPhoto() {
            const file = document.getElementById('fotoProd').files[0];
            const preview = document.getElementById('photoPreviewContainer');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                };
                reader.readAsDataURL(file);
            }
        }
        
        function togglePesoSelect() {
            const categoria = document.getElementById('prodCat').value;
            const pesoGroup = document.getElementById('pesoGroup');
            if (categoria === 'Alimentos') {
                pesoGroup.style.display = 'block';
            } else {
                pesoGroup.style.display = 'none';
                document.getElementById('prodPeso').value = '';
            }
        }
        
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Producto';
            document.getElementById('formAction').value = 'crear';
            document.getElementById('productForm').reset();
            document.getElementById('activeGroup').style.display = 'none';
            document.getElementById('photoPreviewContainer').innerHTML = '<i class="fas fa-image"></i>';
            document.getElementById('pesoGroup').style.display = 'none';
            document.getElementById('productModal').classList.add('active');
        }

        function editProduct(prod) {
            document.getElementById('modalTitle').textContent = 'Editar Producto';
            document.getElementById('formAction').value = 'editar';
            document.getElementById('productId').value = prod.id;
            document.getElementById('prodName').value = prod.nombre;
            document.getElementById('prodCat').value = prod.categoria;
            document.getElementById('prodPrice').value = prod.precio;
            document.getElementById('prodStock').value = prod.stock;
            document.getElementById('prodDesc').value = prod.descripcion;
            document.getElementById('prodDestacado').checked = prod.destacado == 1;
            document.getElementById('prodActive').value = prod.activo;
            document.getElementById('prodPeso').value = prod.peso || '';
            document.getElementById('activeGroup').style.display = 'block';
            
            // Mostrar/ocultar select de peso
            if (prod.categoria === 'Alimentos') {
                document.getElementById('pesoGroup').style.display = 'block';
            } else {
                document.getElementById('pesoGroup').style.display = 'none';
            }
            
            // Preview de foto si existe
            if (prod.imagen) {
                document.getElementById('photoPreviewContainer').innerHTML = '<img src="<?php echo BASE_URL; ?>/tienda/' + prod.imagen + '" alt="Product">';
            } else {
                document.getElementById('photoPreviewContainer').innerHTML = '<i class="fas fa-image"></i>';
            }
            
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
        
        window.onclick = function(e) {
            if (e.target == document.getElementById('productModal')) closeModal();
        }
    </script>
</body>
</html>
