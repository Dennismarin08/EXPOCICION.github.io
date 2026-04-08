<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de tienda
checkRole('tienda');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND tipo = 'tienda'");
$stmt->execute([$userId]);
$tienda = $stmt->fetch();
if (!$tienda) header("Location: tienda-dashboard.php");
$tiendaId = $tienda['id'];

// Obtener productos para el selector
$stmt = $pdo->prepare("SELECT * FROM productos_tienda WHERE tienda_id = ? AND activo = 1 AND stock > 0");
$stmt->execute([$tiendaId]);
$productos = $stmt->fetchAll();

// Procesar Venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_sale') {
    try {
        $pdo->beginTransaction();
        
        $cartData = json_decode($_POST['cart_data'], true);
        $total = $_POST['total_amount'];
        $clientPhone = $_POST['client_phone'] ?? null;
        
        // Buscar usuario por teléfono si existe (opcional)
        $clienteId = null;
        if ($clientPhone) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefono = ?");
            $stmt->execute([$clientPhone]);
            $clienteId = $stmt->fetchColumn();
        }
        
        // Crear Venta
        $stmt = $pdo->prepare("INSERT INTO ventas_tienda (tienda_id, usuario_id, total, metodo_pago) VALUES (?, ?, ?, 'efectivo')");
        $stmt->execute([$tiendaId, $clienteId, $total]);
        $ventaId = $pdo->lastInsertId();
        
        // Crear Detalles y Actualizar Stock
        foreach ($cartData as $item) {
            $stmt = $pdo->prepare("INSERT INTO detalle_ventas_tienda (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ventaId, $item['id'], $item['qty'], $item['price'], $item['qty'] * $item['price']]);
            
            // Restar stock
            $stmt = $pdo->prepare("UPDATE productos_tienda SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['qty'], $item['id']]);
        }
        
        $pdo->commit();
        header("Location: tienda-ventas.php?success=1&id=" . $ventaId);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al procesar venta: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Venta (POS) - RUGAL Tienda</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pos-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            height: calc(100vh - 120px);
        }
        
        @media(max-width: 900px) { .pos-container { grid-template-columns: 1fr; } }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .product-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: transform 0.2s;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 120px;
        }
        
        .product-item:hover { transform: translateY(-3px); border-color: #ff7e5f; }
        
        .cart-panel {
            background: white;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-qty {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid #cbd5e1;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .total-display {
            font-size: 24px;
            font-weight: 800;
            text-align: right;
            margin-bottom: 20px;
            color: #1e293b;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }
        
        .search-bar {
            width: 100%;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-tienda.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Punto de Venta</h1>
        </header>

        <div class="content-wrapper is-full-height">
            <?php if (isset($_GET['success'])): ?>
                <div style="background:#d1fae5; color:#065f46; padding:15px; border-radius:10px; margin-bottom:20px;">
                    <i class="fas fa-check-circle"></i> Venta realizada con éxito #<?php echo $_GET['id']; ?>
                </div>
            <?php endif; ?>

            <div class="pos-container">
                <!-- Left: Product List -->
                <div style="display:flex; flex-direction:column; overflow:hidden;">
                    <input type="text" id="search" class="search-bar" placeholder="🔍 Buscar productos..." onkeyup="filterProducts()">
                    
                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($productos as $p): ?>
                            <div class="product-item" onclick='addToCart(<?php echo json_encode($p); ?>)'>
                                <div style="font-weight:700; color:#1e293b;"><?php echo htmlspecialchars($p['nombre']); ?></div>
                                <div>
                                    <div style="font-size:12px; color:#64748b;"><?php echo $p['stock']; ?> disp.</div>
                                    <div style="color:#ff7e5f; font-weight:800; font-size:18px;">$<?php echo number_format($p['precio'], 0); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Right: Cart -->
                <div class="cart-panel">
                    <h3 style="margin-bottom:15px;">Resumen de Venta</h3>
                    <div class="cart-items" id="cartItems">
                        <!-- Items injected by JS -->
                        <div style="text-align:center; color:#cbd5e1; margin-top:50px;">
                            Carrito vacío
                        </div>
                    </div>
                    
                    <div class="total-display">
                        Total: $<span id="cartTotal">0</span>
                    </div>
                    
                    <input type="text" id="clientPhone" placeholder="📱 Teléfono Cliente (Opcional)" style="padding:10px; border:1px solid #e2e8f0; border-radius:8px; width:100%; margin-bottom:10px;">
                    
                    <button class="btn-checkout" onclick="processSale()">
                        <i class="fas fa-cash-register"></i> Cobrar
                    </button>
                    
                    <form id="saleForm" method="POST" style="display:none;">
                        <input type="hidden" name="action" value="process_sale">
                        <input type="hidden" name="cart_data" id="formCartData">
                        <input type="hidden" name="total_amount" id="formTotal">
                        <input type="hidden" name="client_phone" id="formPhone">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        const products = <?php echo json_encode($productos); ?>;

        function filterProducts() {
            const term = document.getElementById('search').value.toLowerCase();
            const items = document.querySelectorAll('.product-item');
            
            items.forEach(item => {
                const name = item.querySelector('div').innerText.toLowerCase();
                item.style.display = name.includes(term) ? 'flex' : 'none';
            });
        }

        function addToCart(product) {
            const existing = cart.find(i => i.id === product.id);
            if (existing) {
                if(existing.qty < product.stock) {
                    existing.qty++;
                } else {
                    alert('Stock insuficiente');
                }
            } else {
                cart.push({
                    id: product.id,
                    name: product.nombre,
                    price: parseFloat(product.precio),
                    qty: 1,
                    maxStock: product.stock
                });
            }
            renderCart();
        }

        function updateQty(id, change) {
            const item = cart.find(i => i.id === id);
            if (!item) return;
            
            const newQty = item.qty + change;
            if (newQty <= 0) {
                cart = cart.filter(i => i.id !== id);
            } else if (newQty > item.maxStock) {
                alert('Stock máximo alcanzado');
            } else {
                item.qty = newQty;
            }
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            container.innerHTML = '';
            let total = 0;
            
            if (cart.length === 0) {
                container.innerHTML = '<div style="text-align:center; color:#cbd5e1; margin-top:50px;">Carrito vacío</div>';
            }
            
            cart.forEach(item => {
                const subtotal = item.qty * item.price;
                total += subtotal;
                
                const div = document.createElement('div');
                div.className = 'cart-item';
                div.innerHTML = `
                    <div>
                        <div style="font-weight:600;">${item.name}</div>
                        <div style="font-size:12px; color:#64748b;">$${item.price} x ${item.qty}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:bold;">$${subtotal}</div>
                        <div class="qty-controls" style="justify-content:flex-end; margin-top:5px;">
                            <button class="btn-qty" onclick="updateQty(${item.id}, -1)">-</button>
                            <span>${item.qty}</span>
                            <button class="btn-qty" onclick="updateQty(${item.id}, 1)">+</button>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
            
            document.getElementById('cartTotal').innerText = total.toLocaleString();
        }

        function processSale() {
            if (cart.length === 0) {
                alert('El carrito está vacío');
                return;
            }
            
            if(!confirm('¿Confirmar venta?')) return;
            
            document.getElementById('formCartData').value = JSON.stringify(cart);
            document.getElementById('formTotal').value = cart.reduce((acc, item) => acc + (item.qty * item.price), 0);
            document.getElementById('formPhone').value = document.getElementById('clientPhone').value;
            
            document.getElementById('saleForm').submit();
        }
    </script>
</body>
</html>
