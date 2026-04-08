<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de tienda
checkRole('tienda');

$userId = $_SESSION['user_id'];

// Obtener información de la tienda (alias para id de aliado)
$stmt = $pdo->prepare("
    SELECT u.*, a.*, a.id AS aliado_id 
    FROM usuarios u 
    LEFT JOIN aliados a ON u.id = a.usuario_id 
    WHERE u.id = ? AND (a.tipo = 'tienda' OR a.tipo IS NULL)
");
$stmt->execute([$userId]);
$tiendaInfo = $stmt->fetch();

// Si no tiene perfil de aliado, CREARLO automáticamente
if (!$tiendaInfo || empty($tiendaInfo['nombre_local'])) {
    $defaultName = 'Nueva';
    if ($tiendaInfo && !empty($tiendaInfo['nombre'])) $defaultName = $tiendaInfo['nombre'];
    // Insertar registro por defecto
    $stmt = $pdo->prepare("
        INSERT INTO aliados (usuario_id, tipo, nombre_local, descripcion, activo) 
        VALUES (?, 'tienda', ?, 'Mi Tienda de Mascotas', 1)
    ");
    $stmt->execute([$userId, 'Tienda ' . $defaultName]);
    
    // Recargar datos
    header("Location: " . BASE_URL . "/tienda/tienda-dashboard.php");
    exit;
}

// A partir de aquí: inicializar métricas con valores reales o ceros seguros
$aliadoId = $tiendaInfo['aliado_id'] ?? null;

// Productos (muestra últimos 6)
$products = [];
$productsCount = 0;
if ($aliadoId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos_tienda WHERE tienda_id = ?");
    $stmt->execute([$aliadoId]);
    $productsCount = (int) $stmt->fetchColumn();

    $pstmt = $pdo->prepare("SELECT * FROM productos_tienda WHERE tienda_id = ? ORDER BY id DESC LIMIT 6");
    $pstmt->execute([$aliadoId]);
    $products = $pstmt->fetchAll();
} else {
    $products = [];
    $productsCount = 0;
}

// Clientes hoy (ventas distincts)
$clientsToday = 0;
$ventasMes = 0.0;
$totalVentas = 0;
if ($aliadoId) {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT usuario_id) FROM ventas_tienda WHERE tienda_id = ? AND DATE(fecha) = CURRENT_DATE()");
    $stmt->execute([$aliadoId]);
    $clientsToday = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ventas_tienda WHERE tienda_id = ?");
    $stmt->execute([$aliadoId]);
    $totalVentas = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT IFNULL(SUM(total),0) FROM ventas_tienda WHERE tienda_id = ? AND MONTH(fecha)=MONTH(CURRENT_DATE()) AND YEAR(fecha)=YEAR(CURRENT_DATE())");
    $stmt->execute([$aliadoId]);
    $ventasMes = (float) $stmt->fetchColumn();
}

// Rating (si no existe tabla de reseñas usar 0)
$rating = 0;

// Horario: cargar JSON desde aliados.horario o usar valores por defecto (cerrado)
$horario = [];
if (!empty($tiendaInfo['horario'])) {
    $decoded = json_decode($tiendaInfo['horario'], true);
    if (is_array($decoded)) $horario = $decoded;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Tienda - RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/themes.css">
    <style>
        .tienda-header {
            background: var(--p-gradient);
            color: white;
            padding: 40px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        
        .tienda-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 150px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #667eea;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-price {
            color: #00b09b;
            font-weight: 600;
            font-size: 18px;
        }
        
        .razas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .raza-tag {
            background: #f1f5f9;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            text-align: center;
        }
        
        .tienda-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .tienda-stat {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.3);
            text-align: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .tienda-stat:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        /* Estilos Canje Premium Store */
        .redeem-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            margin-bottom: 35px;
            border: 1px solid var(--p-border);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .redeem-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.12);
        }
        .redeem-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: var(--p-gradient);
        }
        .redeem-form {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .redeem-input {
            flex: 1;
            padding: 15px 25px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #1e293b;
            background: #f8fafc;
            transition: all 0.3s ease;
        }
        .redeem-input:focus {
            outline: none;
            border-color: #ff7e5f;
            background: white;
            box-shadow: 0 0 0 4px rgba(255, 126, 95, 0.1);
        }
        .redeem-input::placeholder {
            color: #94a3b8;
            letter-spacing: normal;
            font-weight: 400;
        }
        .btn-tienda-validate {
            background: var(--p-primary);
            color: white;
            border: none;
            padding: 0 30px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .btn-tienda-validate:hover {
            background: var(--p-primary-dark);
            transform: translateY(-2px);
        }
        #redeem-result {
            margin-top: 25px;
            padding: 24px;
            border-radius: 20px;
            display: none;
            animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .result-success { 
            background: #f0fdf4; 
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .result-error { 
            background: #fef2f2; 
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .btn-tienda-confirm {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .btn-tienda-confirm:hover {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3);
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">

    <!-- Sidebar para tiendas -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon" style="background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);">
                <i class="fas fa-store"></i>
            </div>
            <div class="logo-text">RUGAL STORE</div>
        </div>
        
        <div class="sidebar-section">
            <div class="section-title">MI TIENDA</div>
            <a href="tienda-dashboard.php" class="menu-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="tienda-perfil.php" class="menu-item">
                <i class="fas fa-store"></i>
                <span>Info del Local</span>
            </a>
            <a href="tienda-productos.php" class="menu-item">
                <i class="fas fa-shopping-bag"></i>
                <span>Productos</span>
            </a>
           
        </div>
        
        <div class="sidebar-section">
            <div class="section-title">INVENTARIO</div>
            <a href="tienda-inventario.php" class="menu-item">
                <i class="fas fa-boxes"></i>
                <span>Inventario</span>
            </a>
            <a href="tienda-precios.php" class="menu-item">
                <i class="fas fa-tags"></i>
                <span>Precios</span>
            </a>
        </div>
        
        <div class="sidebar-section">
            <div class="section-title">MI CUENTA</div>
            <a href="perfil.php" class="menu-item">
                <i class="fas fa-user-circle"></i>
                <span>Mi Perfil</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Panel Tienda</h1>
                <div class="breadcrumb">
                    <span>Tienda</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Dashboard</span>
                </div>
            </div>
            
            <div class="header-right">
                <button class="btn-add" onclick="window.location.href='tienda-perfil.php'">
                    <i class="fas fa-edit"></i>
                    <span>Completar Perfil</span>
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Sección de Validación de Canjes -->
            <div class="redeem-card animate-up">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                    <div style="width: 40px; height: 40px; background: #ff7e5f; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div>
                        <h3 style="margin:0;">Validar Canje de Recompensa</h3>
                        <p style="margin:0; font-size: 13px; color: #64748b;">Ingresa el código que te muestra el usuario en su app.</p>
                    </div>
                </div>

                <div class="redeem-form">
                    <input type="text" id="redeem-code" class="redeem-input" placeholder="RUGAL-XXXX-XXXX" maxlength="20">
                    <button class="btn-tienda-validate" onclick="validarCanje()">
                        <i class="fas fa-search"></i> Validar
                    </button>
                </div>

                <div id="redeem-result">
                    <!-- Contenido dinámico -->
                </div>
            </div>
            <div class="tienda-header">
                <div class="vet-info">
                    <div class="vet-logo">
                        <?php if (!empty($tiendaInfo['foto_local'])): ?>
                            <img src="<?php echo BASE_URL . '/tienda/' . htmlspecialchars($tiendaInfo['foto_local']); ?>" alt="Foto de <?php echo htmlspecialchars($tiendaInfo['nombre_local']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                        <?php else: ?>
                            <i class="fas fa-store"></i>
                        <?php endif; ?>
                    </div>
                    <div class="vet-name">
                        <h1><?php echo htmlspecialchars($tiendaInfo['nombre_local']); ?></h1>
                        <p><?php echo htmlspecialchars($tiendaInfo['descripcion'] ?? 'Tienda especializada en mascotas'); ?></p>
                    </div>
                </div>
                
                <div class="tienda-stats">
                    <div class="tienda-stat">
                        <div class="stat-value"><?php echo $productsCount; ?></div>
                        <div class="stat-label">Productos</div>
                    </div>
                    <div class="tienda-stat">
                        <div class="stat-value"><?php echo $clientsToday; ?></div>
                        <div class="stat-label">Clientes Hoy</div>
                    </div>
                    <div class="tienda-stat">
                        <div class="stat-value"><?php echo '$' . number_format($ventasMes, 0, ',', '.'); ?></div>
                        <div class="stat-label">Ventas Mes</div>
                    </div>
                    <div class="tienda-stat">
                        <div class="stat-value"><?php echo $rating ? number_format($rating,1) : '0'; ?></div>
                        <div class="stat-label">Rating</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>Productos Destacados</h3>
                            <button class="btn-primary" onclick="window.location.href='tienda-productos.php'">
                                <i class="fas fa-plus"></i> Agregar Producto
                            </button>
                        </div>
                        <div class="tienda-products">
                            <?php if (empty($products)): ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <div class="product-info">
                                        <h4>No hay productos aún</h4>
                                        <p class="product-price">-</p>
                                        <p>Agrega tu primer producto para que aparezca aquí.</p>
                                        <div style="margin-top:12px;">
                                            <button class="btn-primary" onclick="window.location.href='tienda-productos.php'">
                                                <i class="fas fa-plus"></i> Agregar Producto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <div class="product-card">
                                        <div class="product-image">
                                            <?php if (!empty($p['imagen'])): ?>
                                                <img src="<?php echo BASE_URL . '/tienda/' . htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                            <?php else: ?>
                                                <i class="fas fa-box"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-info">
                                            <h4><?php echo htmlspecialchars($p['nombre']); ?></h4>
                                            <p class="product-price"><?php echo '$' . number_format((float)$p['precio'],0,',','.'); ?></p>
                                            <p><?php echo htmlspecialchars($p['descripcion'] ?? ''); ?></p>
                                            <?php if (isset($p['stock'])): ?>
                                                <div style="margin-top:8px; font-size:13px; color:#475569;">Stock: <?php echo (int)$p['stock']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Horarios de Atención -->
            <div class="row" style="margin-top: 30px;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>Horarios de Atención</h3>
                            <button class="btn-primary" onclick="window.location.href='tienda-horarios.php'">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        </div>
                        <div style="padding: 20px;">
                            <?php
                            $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                            
                            if (empty($horario)):
                            ?>
                                <div style="text-align:center; padding:30px; color:#64748b;">
                                    <i class="fas fa-clock" style="font-size:40px; margin-bottom:15px;"></i>
                                    <p>No has configurado tus horarios aún. <a href="tienda-horarios.php">Configúralos ahora</a></p>
                                </div>
                            <?php else: ?>
                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
                                    <?php foreach ($dias as $idx => $dia): 
                                        $h = $horario[$dia] ?? ['abierto' => 0, 'apertura' => '08:00', 'cierre' => '18:00'];
                                        $abierto = (int)($h['abierto'] ?? 0);
                                    ?>
                                        <div style="background:#f8fafc; border-radius:12px; padding:15px; border-left:4px solid <?php echo $abierto ? '#10b981' : '#ef4444'; ?>;">
                                            <div style="font-weight:600; margin-bottom:8px;"><?php echo $dia; ?></div>
                                            <div style="font-size:14px; color:#64748b;">
                                                <?php echo $abierto ? 
                                                    htmlspecialchars($h['apertura']) . ' - ' . htmlspecialchars($h['cierre']) : 
                                                    'Cerrado'; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function validarCanje() {
            const codigo = document.getElementById('redeem-code').value.trim();
            if (!codigo) {
                alert('Por favor ingrese un código');
                return;
            }

            const resultDiv = document.getElementById('redeem-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Verificando...</div>';
            resultDiv.className = '';

            const formData = new FormData();
            formData.append('action', 'validar');
            formData.append('codigo', codigo);

            fetch('../ajax-validar-canje.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    resultDiv.className = 'result-success';
                    resultDiv.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin:0; color: #166534; font-size: 18px;">¡Código Válido!</h4>
                                <p style="margin: 5px 0 0 0; color: #15803d; font-size: 15px;">
                                    <i class="fas fa-gift"></i> <strong>Premio:</strong> ${data.canje.titulo}<br>
                                    ${data.canje.producto_vinc_nombre ? `<i class="fas fa-box"></i> <strong>Producto:</strong> ${data.canje.producto_vinc_nombre}<br>` : ''}
                                    <i class="fas fa-user"></i> <strong>Usuario:</strong> ${data.canje.usuario_nombre}
                                </p>
                            </div>
                            <button class="btn-tienda-confirm" onclick="confirmarCanje('${codigo}')">
                                <i class="fas fa-check-circle"></i> Confirmar Entrega
                            </button>
                        </div>
                    `;
                } else {
                    resultDiv.className = 'result-error';
                    resultDiv.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
                            <div>
                                <h4 style="margin:0; color: #991b1b; font-size: 18px;">Error de Validación</h4>
                                <p style="margin: 5px 0 0 0; color: #b91c1c; font-weight: 500;">${data.message}</p>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(err => {
                resultDiv.className = 'result-error';
                resultDiv.innerHTML = '<p>Error de conexión al servidor. Verifica que el archivo ajax-validar-canje.php existe en la raíz del proyecto.</p>';
            });
        }

        function confirmarCanje(codigo) {
            if (!confirm('¿Deseas confirmar que la recompensa ya fue entregada? Esta acción no se puede deshacer.')) return;

            const resultDiv = document.getElementById('redeem-result');
            resultDiv.innerHTML = '<div style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Procesando...</div>';

            const formData = new FormData();
            formData.append('action', 'confirmar');
            formData.append('codigo', codigo);

            fetch('../ajax-validar-canje.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    resultDiv.className = 'result-success';
                    resultDiv.innerHTML = `
                        <div style="text-align:center;">
                            <i class="fas fa-check-circle" style="font-size: 40px; color: #10b981; margin-bottom: 15px;"></i>
                            <h4 style="margin:0; color: #065f46;">¡Canje Realizado Exitosamente!</h4>
                            <p style="margin: 10px 0 0 0; color: #047857;">La recompensa ha sido marcada como entregada.</p>
                            <button class="btn-tienda" style="margin-top: 15px;" onclick="location.reload()">Aceptar</button>
                        </div>
                    `;
                    document.getElementById('redeem-code').value = '';
                } else {
                    alert(data.message);
                    validarCanje(); // Volver a mostrar error
                }
            });
        }
    </script>
</body>
</html>