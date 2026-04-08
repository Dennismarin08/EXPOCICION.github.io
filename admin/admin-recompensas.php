<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../puntos-functions.php';

// Verificar admin
checkRole('admin');

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'crear' || $action === 'editar') {
            $titulo = $_POST['titulo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $puntos = $_POST['puntos'] ?? 0;
            $tipo = $_POST['tipo'] ?? 'producto';
            $tipo_acceso = $_POST['tipo_acceso'] ?? 'free';
            $stock = $_POST['stock'] ?? -1;
            $fecha_limite = !empty($_POST['fecha_limite']) ? $_POST['fecha_limite'] : null;
            $ubicacion_canje = $_POST['ubicacion_canje'] ?? '';
            
            // Lógica de Alcance
            $alcance_tipo = $_POST['alcance_tipo'] ?? 'global';
            $alcance_valor = null;
            
            if ($alcance_tipo === 'tipo_aliado') {
                $alcance_valor = $_POST['tipo_aliado_valor'] ?? '';
            } elseif ($alcance_tipo === 'especificos') {
                if (isset($_POST['aliados_ids']) && is_array($_POST['aliados_ids'])) {
                    $alcance_valor = implode(',', $_POST['aliados_ids']);
                }
            }
            
            // Vincular a Producto
            $producto_id = !empty($_POST['producto_id']) ? $_POST['producto_id'] : null;
            $producto_tabla = !empty($_POST['producto_tabla']) ? $_POST['producto_tabla'] : null;

            // Beneficios y Precios
            $precio_original = !empty($_POST['precio_original']) ? $_POST['precio_original'] : null;
            $precio_oferta = !empty($_POST['precio_oferta']) ? $_POST['precio_oferta'] : null;
            $porcentaje_descuento = !empty($_POST['porcentaje_descuento']) ? $_POST['porcentaje_descuento'] : null;
            $es_gratis = isset($_POST['es_gratis']) ? 1 : 0;

            
            if ($action === 'crear') {
                $stmt = $pdo->prepare("
                    INSERT INTO recompensas (titulo, descripcion, puntos_requeridos, tipo, tipo_acceso, stock, fecha_limite, ubicacion_canje, alcance_tipo, alcance_valor, producto_id, producto_tabla, precio_original, precio_oferta, porcentaje_descuento, es_gratis, activa)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$titulo, $descripcion, $puntos, $tipo, $tipo_acceso, $stock, $fecha_limite, $ubicacion_canje, $alcance_tipo, $alcance_valor, $producto_id, $producto_tabla, $precio_original, $precio_oferta, $porcentaje_descuento, $es_gratis]);
                $mensaje = '✅ Recompensa creada correctamente';
            } else {
                $id = $_POST['id'] ?? 0;
                $stmt = $pdo->prepare("
                    UPDATE recompensas 
                    SET titulo=?, descripcion=?, puntos_requeridos=?, tipo=?, tipo_acceso=?, stock=?, fecha_limite=?, ubicacion_canje=?, alcance_tipo=?, alcance_valor=?, producto_id=?, producto_tabla=?, precio_original=?, precio_oferta=?, porcentaje_descuento=?, es_gratis=?
                    WHERE id=?
                ");
                $stmt->execute([$titulo, $descripcion, $puntos, $tipo, $tipo_acceso, $stock, $fecha_limite, $ubicacion_canje, $alcance_tipo, $alcance_valor, $producto_id, $producto_tabla, $precio_original, $precio_oferta, $porcentaje_descuento, $es_gratis, $id]);
                $mensaje = '✅ Recompensa actualizada correctamente';
            }
            $tipo_mensaje = 'success';
            
        } elseif ($action === 'eliminar') {
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("UPDATE recompensas SET activa = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $mensaje = '🗑️ Recompensa eliminada';
            $tipo_mensaje = 'success';
        }
        
    } catch (Exception $e) {
        $mensaje = '❌ Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener recompensas
$stmt = $pdo->query("SELECT * FROM recompensas WHERE activa = 1 ORDER BY id DESC");
$recompensas = $stmt->fetchAll();

// Obtener lista de aliados
$stmt = $pdo->query("SELECT id, nombre_local, tipo FROM aliados WHERE activo = 1 ORDER BY tipo, nombre_local");
$aliados = $stmt->fetchAll();

// Obtener estadísticas
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN tipo = 'producto' THEN 1 ELSE 0 END) as productos,
    SUM(CASE WHEN tipo = 'servicio' THEN 1 ELSE 0 END) as servicios,
    SUM(CASE WHEN tipo = 'descuento' OR tipo = '' THEN 1 ELSE 0 END) as descuentos
    FROM recompensas WHERE activa = 1");
$stats = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Recompensas - Admin RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/color-fixes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            --gradient-info: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --card-bg: rgba(30, 41, 59, 0.8);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .main-content {
            padding: 30px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Header Premium */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title-section h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 50%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .page-title-section .breadcrumb {
            color: #94a3b8;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .breadcrumb i { font-size: 0.5rem; opacity: 0.5; }

        /* Stats Cards Modernas */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card-mini {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card-mini::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .stat-card-mini:hover {
            transform: translateY(-6px) scale(1.02);
            border-color: rgba(139, 92, 246, 0.4);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
        }

        .stat-card-mini:hover::before { opacity: 1; }

        .stat-icon-mini {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            position: relative;
        }

        .stat-icon-mini::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            background: inherit;
            filter: blur(10px);
            opacity: 0.4;
            z-index: -1;
        }

        .stat-icon-mini.total { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
        .stat-icon-mini.producto { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); }
        .stat-icon-mini.servicio { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); }
        .stat-icon-mini.descuento { background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%); }

        .stat-content { flex: 1; }

        .stat-value-mini {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label-mini {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* Layout Principal */
        .admin-container {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
            align-items: start;
        }

        @media (max-width: 1200px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
        }

        /* Form Card Premium */
        .form-card {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .form-header {
            background: var(--gradient-primary);
            padding: 25px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
            animation: shimmer 4s infinite linear;
        }

        .form-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }

        @keyframes shimmer {
            0% { transform: translateX(-10%) rotate(0deg); }
            100% { transform: translateX(10%) rotate(360deg); }
        }

        .form-header h3 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
            font-weight: 700;
        }

        .form-header .btn-reset {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            position: relative;
            z-index: 1;
        }

        .form-header .btn-reset:hover {
            background: rgba(255,255,255,0.25);
            transform: scale(1.05);
        }

        .form-body {
            padding: 30px;
            max-height: calc(100vh - 250px);
            overflow-y: auto;
        }

        .form-body::-webkit-scrollbar {
            width: 6px;
        }

        .form-body::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 3px;
        }

        .form-body::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.5);
            border-radius: 3px;
        }

        /* Secciones del formulario */
        .form-section {
            margin-bottom: 28px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }

        .form-section:nth-child(1) { animation-delay: 0.1s; }
        .form-section:nth-child(2) { animation-delay: 0.2s; }
        .form-section:nth-child(3) { animation-delay: 0.3s; }
        .form-section:nth-child(4) { animation-delay: 0.4s; }
        .form-section:nth-child(5) { animation-delay: 0.5s; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .form-section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #a78bfa;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title i {
            font-size: 1rem;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(167, 139, 250, 0.15);
            border-radius: 8px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-grid.single {
            grid-template-columns: 1fr;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group { margin-bottom: 0; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #e2e8f0;
            font-size: 0.85rem;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(15, 23, 42, 0.6);
            color: white;
            font-family: inherit;
        }

        .form-control:focus {
            border-color: #8b5cf6;
            background: rgba(15, 23, 42, 0.9);
            outline: none;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15), 0 4px 12px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #64748b;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 18px;
            padding-right: 40px;
        }

        select.form-control option {
            background: #1e293b;
            color: white;
            padding: 12px;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Checkbox Premium */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .checkbox-wrapper:hover {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.18) 0%, rgba(16, 185, 129, 0.08) 100%);
            border-color: rgba(16, 185, 129, 0.5);
            transform: translateX(4px);
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 24px;
            height: 24px;
            accent-color: #10b981;
            cursor: pointer;
            border-radius: 6px;
        }

        .checkbox-wrapper label {
            margin: 0;
            font-weight: 600;
            color: #34d399;
            cursor: pointer;
            font-size: 0.95rem;
        }

        /* Sección de Alcance */
        .alcance-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.08) 100%);
            border: 1px dashed rgba(139, 92, 246, 0.3);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.3s;
        }

        .alcance-section:hover {
            border-color: rgba(139, 92, 246, 0.5);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(139, 92, 246, 0.12) 100%);
        }

        .alcance-section .form-section-title {
            color: #a78bfa;
        }

        .alcance-options {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .alcance-option {
            flex: 1;
            min-width: 120px;
            padding: 12px 16px;
            background: rgba(255,255,255,0.05);
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .alcance-option:hover {
            background: rgba(139, 92, 246, 0.15);
            color: #e2e8f0;
        }

        .alcance-option.active {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        }

        .alcance-details {
            margin-top: 16px;
            padding: 16px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 12px;
            display: none;
        }

        .alcance-details.active {
            display: block;
            animation: fadeInUp 0.3s ease;
        }

        /* Grid de Productos Premium */
        .productos-container {
            margin-top: 20px;
        }

        .productos-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        .tab-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.08);
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            background: rgba(139, 92, 246, 0.2);
            color: white;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        }

        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            max-height: 320px;
            overflow-y: auto;
            padding: 16px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .productos-grid::-webkit-scrollbar {
            width: 5px;
        }

        .productos-grid::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.4);
            border-radius: 3px;
        }

        .producto-card {
            background: rgba(30, 41, 59, 0.9);
            border: 2px solid transparent;
            border-radius: 14px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .producto-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .producto-card:hover {
            border-color: rgba(139, 92, 246, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.2);
        }

        .producto-card:hover::before { opacity: 1; }

        .producto-card.selected {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.15);
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.3);
            transform: scale(1.02);
        }

        .producto-card .producto-img {
            width: 100%;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 8px;
            background: #1e293b;
        }

        .producto-card .producto-icon {
            width: 100%;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 100%);
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 1.8rem;
            color: #64748b;
        }

        .producto-card .producto-nombre {
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .producto-card .producto-precio {
            font-size: 0.8rem;
            color: #10b981;
            font-weight: 800;
        }

        .producto-card .producto-aliado {
            font-size: 0.65rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 4px;
        }

        .producto-card .badge-tipo {
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 0.6rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-tipo.servicio {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #1e1b4b;
        }

        .badge-tipo.producto {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        /* Aliados Grid */
        .aliados-search {
            position: relative;
            margin-bottom: 14px;
        }

        .aliados-search input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.6);
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .aliados-search input:focus {
            border-color: #8b5cf6;
            outline: none;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        }

        .aliados-search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .aliados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 8px;
            max-height: 180px;
            overflow-y: auto;
            padding: 10px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 12px;
        }

        .aliado-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .aliado-checkbox:hover {
            background: rgba(139, 92, 246, 0.12);
        }

        .aliado-checkbox input {
            accent-color: #8b5cf6;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .aliado-checkbox .aliado-info {
            flex: 1;
            min-width: 0;
        }

        .aliado-checkbox .aliado-nombre {
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .aliado-checkbox .aliado-tipo {
            font-size: 0.65rem;
            color: #64748b;
            text-transform: capitalize;
            font-weight: 600;
        }

        /* Preview Card Flotante */
        .preview-section {
            position: sticky;
            top: 30px;
        }

        .preview-card {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .preview-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .preview-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 50%);
            animation: pulse 3s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .preview-image-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255,255,255,0.2);
        }

        .preview-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-image-container i {
            font-size: 3rem;
            color: rgba(255,255,255,0.5);
        }

        .preview-puntos {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #1e1b4b;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.85rem;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }

        .preview-body {
            padding: 24px;
        }

        .preview-tipo-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .preview-tipo-badge.producto {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .preview-tipo-badge.servicio {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .preview-tipo-badge.descuento {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.2) 0%, rgba(236, 72, 153, 0.1) 100%);
            color: #f472b6;
            border: 1px solid rgba(236, 72, 153, 0.3);
        }

        .preview-titulo {
            font-size: 1.25rem;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .preview-descripcion {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .preview-precios {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .preview-precio-original {
            font-size: 1rem;
            color: #64748b;
            text-decoration: line-through;
        }

        .preview-precio-oferta {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
        }

        .preview-descuento {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .preview-gratis {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.9rem;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .preview-stock {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: #f59e0b;
            margin-bottom: 16px;
        }

        .preview-aliados {
            padding: 14px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .preview-aliados-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #6366f1;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .preview-aliados-list {
            font-size: 0.8rem;
            color: #e2e8f0;
        }

        /* Lista de Recompensas */
        .recompensas-list {
            margin-top: 30px;
        }

        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .list-header h2 {
            font-size: 1.5rem;
            color: white;
            font-weight: 700;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.8);
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            border-color: #8b5cf6;
            outline: none;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-tab:hover {
            background: rgba(139, 92, 246, 0.15);
            color: white;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }

        .recompensas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .recompensa-item {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .recompensa-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .recompensa-item:hover {
            transform: translateY(-4px);
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .recompensa-item:hover::before {
            transform: scaleX(1);
        }

        .recompensa-item .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .recompensa-item .item-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .item-badge.producto {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .item-badge.servicio {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .item-badge.descuento {
            background: rgba(236, 72, 153, 0.2);
            color: #f472b6;
        }

        .item-badge.premium {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        .recompensa-item .item-puntos {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #1e1b4b;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .recompensa-item .item-titulo {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }

        .recompensa-item .item-desc {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .recompensa-item .item-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 16px;
        }

        .item-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .recompensa-item .item-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-edit {
            background: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .btn-edit:hover {
            background: #6366f1;
            color: white;
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            background: #ef4444;
            color: white;
        }

        /* Mensajes */
        .mensaje {
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .mensaje.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.1) 100%);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .mensaje.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        /* Botón Guardar */
        .btn-guardar {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 24px;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }

        .btn-guardar:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(139, 92, 246, 0.5);
        }

        .btn-guardar:active {
            transform: translateY(0);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Header -->
        <div class="page-header">
            <div class="page-title-section">
                <h1><i class="fas fa-gift"></i> Gestión de Recompensas</h1>
                <div class="breadcrumb">
                    <span>Admin</span>
                    <i class="fas fa-circle"></i>
                    <span>Recompensas</span>
                </div>
            </div>
        </div>

        <!-- Mensaje -->
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <i class="fas <?php echo $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card-mini">
                <div class="stat-icon-mini total"><i class="fas fa-gift"></i></div>
                <div class="stat-content">
                    <div class="stat-value-mini"><?php echo $stats['total']; ?></div>
                    <div class="stat-label-mini">Total Recompensas</div>
                </div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon-mini producto"><i class="fas fa-box"></i></div>
                <div class="stat-content">
                    <div class="stat-value-mini"><?php echo $stats['productos']; ?></div>
                    <div class="stat-label-mini">Productos</div>
                </div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon-mini servicio"><i class="fas fa-hand-holding-heart"></i></div>
                <div class="stat-content">
                    <div class="stat-value-mini"><?php echo $stats['servicios']; ?></div>
                    <div class="stat-label-mini">Servicios</div>
                </div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon-mini descuento"><i class="fas fa-tags"></i></div>
                <div class="stat-content">
                    <div class="stat-value-mini"><?php echo $stats['descuentos']; ?></div>
                    <div class="stat-label-mini">Descuentos</div>
                </div>
            </div>
        </div>

        <div class="admin-container">
            <!-- Formulario -->
            <div class="form-card">
                <div class="form-header">
                    <h3 id="form-title"><i class="fas fa-plus-circle"></i> Nueva Recompensa</h3>
                    <button class="btn-reset" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Limpiar
                    </button>
                </div>
                <div class="form-body">
                    <form id="rewardForm" method="POST">
                        <input type="hidden" name="action" id="form-action" value="crear">
                        <input type="hidden" name="id" id="form-id" value="">
                        <input type="hidden" name="producto_id" id="f-producto-id" value="">
                        <input type="hidden" name="producto_tabla" id="f-producto-tabla" value="">

                        <!-- Información Básica -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-info-circle"></i>
                                Información Básica
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Título de la Recompensa</label>
                                    <input type="text" name="titulo" id="f-titulo" class="form-control" placeholder="Ej: Shampoo Medicado Gratis" required>
                                </div>
                                <div class="form-group">
                                    <label>Puntos requeridos</label>
                                    <input type="number" name="puntos" id="f-puntos" class="form-control" placeholder="100" min="0" required>
                                </div>
                            </div>
                            <div class="form-group" style="margin-top: 16px;">
                                <label>Descripción</label>
                                <textarea name="descripcion" id="f-desc" class="form-control" placeholder="Describe los beneficios de esta recompensa..."></textarea>
                            </div>
                        </div>

                        <!-- Tipo y Acceso -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-layer-group"></i>
                                Clasificación
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Tipo de Recompensa</label>
                                    <select name="tipo" id="f-tipo" class="form-control" onchange="updatePreview()">
                                        <option value="producto">🎁 Producto</option>
                                        <option value="servicio">🏥 Servicio</option>
                                        <option value="descuento">🏷️ Descuento</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tipo de Acceso</label>
                                    <select name="tipo_acceso" id="f-tipo-acceso" class="form-control" onchange="updatePreview()">
                                        <option value="free">⭐ Gratis / Free</option>
                                        <option value="premium">👑 Premium</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Beneficios -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-percent"></i>
                                Beneficios y Descuentos
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Precio Original ($)</label>
                                    <input type="number" name="precio_original" id="f-precio-original" class="form-control" placeholder="30000" min="0" step="0.01" oninput="updatePreview()">
                                </div>
                                <div class="form-group">
                                    <label>Precio Oferta ($)</label>
                                    <input type="number" name="precio_oferta" id="f-precio-oferta" class="form-control" placeholder="15000" min="0" step="0.01" oninput="updatePreview()">
                                </div>
                            </div>
                            <div class="form-grid" style="margin-top: 16px;">
                                <div class="form-group">
                                    <label>Porcentaje de Descuento (%)</label>
                                    <input type="number" name="porcentaje_descuento" id="f-porcentaje" class="form-control" placeholder="50" min="0" max="100" oninput="updatePreview()">
                                </div>
                                <div class="form-group" style="display: flex; align-items: center;">
                                    <div class="checkbox-wrapper" style="width: 100%;">
                                        <input type="checkbox" name="es_gratis" id="f-es-gratis" onchange="updatePreview()">
                                        <label for="f-es-gratis">🎉 Es Completamente Gratis</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alcance -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-globe"></i>
                                Alcance (¿Quiénes pueden usarlo?)
                            </div>
                            
                            <div class="alcance-section">
                                <div class="alcance-options">
                                    <div class="alcance-option active" data-value="global" onclick="setAlcance('global')">
                                        <i class="fas fa-globe"></i> Global
                                    </div>
                                    <div class="alcance-option" data-value="tipo_aliado" onclick="setAlcance('tipo_aliado')">
                                        <i class="fas fa-filter"></i> Por Tipo
                                    </div>
                                    <div class="alcance-option" data-value="especificos" onclick="setAlcance('especificos')">
                                        <i class="fas fa-users"></i> Específicos
                                    </div>
                                </div>
                                
                                <input type="hidden" name="alcance_tipo" id="f-alcance-tipo" value="global">
                                
                                <div class="alcance-details" id="alcance-tipo_aliado">
                                    <div class="form-group">
                                        <label>Seleccionar Tipo de Aliado</label>
                                        <select name="tipo_aliado_valor" id="f-tipo-valor" class="form-control" onchange="loadProducts()">
                                            <option value="veterinaria">🏥 Veterinarias</option>
                                            <option value="tienda">🏪 Tiendas</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="alcance-details" id="alcance-especificos">
                                    <div class="aliados-search">
                                        <i class="fas fa-search"></i>
                                        <input type="text" id="aliados-search-input" placeholder="Buscar aliados..." onkeyup="filterAliados()">
                                    </div>
                                    <div class="aliados-grid" id="aliados-grid">
                                        <?php foreach ($aliados as $aliado): ?>
                                            <label class="aliado-checkbox">
                                                <input type="checkbox" name="aliados_ids[]" value="<?php echo $aliado['id']; ?>" class="check-aliado" id="check-<?php echo $aliado['id']; ?>" onchange="loadProducts()">
                                                <div class="aliado-info">
                                                    <div class="aliado-nombre"><?php echo htmlspecialchars($aliado['nombre_local']); ?></div>
                                                    <div class="aliado-tipo"><?php echo $aliado['tipo']; ?></div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Productos/Servicios -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-box-open"></i>
                                Vincular Producto o Servicio
                            </div>
                            
                            <div class="productos-tabs">
                                <button type="button" class="tab-btn active" data-type="producto" onclick="setProductType('producto')">
                                    <i class="fas fa-box"></i> Productos
                                </button>
                                <button type="button" class="tab-btn" data-type="servicio" onclick="setProductType('servicio')">
                                    <i class="fas fa-concierge-bell"></i> Servicios
                                </button>
                            </div>
                            
                            <input type="hidden" id="f-item-type" value="producto">
                            
                            <div class="productos-grid" id="productos-grid">
                                <div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 40px 20px;">
                                    <i class="fas fa-arrow-up" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                                    Selecciona un alcance arriba para ver los productos disponibles
                                </div>
                            </div>
                        </div>

                        <!-- Stock y Fechas -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-calendar-alt"></i>
                                Disponibilidad
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Stock disponible (-1 = ilimitado)</label>
                                    <input type="number" name="stock" id="f-stock" class="form-control" value="-1" min="-1">
                                </div>
                                <div class="form-group">
                                    <label>Fecha límite (opcional)</label>
                                    <input type="datetime-local" name="fecha_limite" id="f-fecha-limite" class="form-control">
                                </div>
                            </div>
                            <div class="form-group" style="margin-top: 16px;">
                                <label>Ubicación de canje</label>
                                <input type="text" name="ubicacion_canje" id="f-ubicacion" class="form-control" placeholder="Dirección donde se puede canjear">
                            </div>
                        </div>

                        <button type="submit" class="btn-guardar">
                            <i class="fas fa-save"></i>
                            <span id="btn-guardar-text">Crear Recompensa</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Preview Flotante -->
            <div class="preview-section">
                <div class="preview-card">
                    <div class="preview-header">
                        <div class="preview-puntos" id="preview-puntos">0 PTS</div>
                        <div class="preview-image-container">
                            <img id="preview-image" src="" style="display: none;">
                            <i class="fas fa-gift" id="preview-icon"></i>
                        </div>
                    </div>
                    <div class="preview-body">
                        <div class="preview-tipo-badge producto" id="preview-tipo-badge">
                            <i class="fas fa-box"></i> Producto
                        </div>
                        <h3 class="preview-titulo" id="preview-titulo">Nueva Recompensa</h3>
                        <p class="preview-descripcion" id="preview-descripcion">La descripción aparecerá aquí...</p>
                        
                        <div class="preview-precios" id="preview-precios">
                            <span class="preview-precio-oferta" id="preview-precio-oferta">$0</span>
                        </div>
                        
                        <div class="preview-stock" id="preview-stock" style="display: none;">
                            <i class="fas fa-box"></i>
                            <span id="preview-stock-text">Stock: -1</span>
                        </div>
                        
                        <div class="preview-aliados">
                            <div class="preview-aliados-label"><i class="fas fa-store"></i> Aliados participantes</div>
                            <div class="preview-aliados-list" id="preview-aliados">Todos los aliados</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Recompensas -->
        <div class="recompensas-list">
            <div class="list-header">
                <h2><i class="fas fa-list"></i> Recompensas Existentes</h2>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search-recompensas" placeholder="Buscar recompensas..." onkeyup="filterRecompensas()">
                </div>
            </div>
            
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all" onclick="setFilter('all')">Todas</button>
                <button class="filter-tab" data-filter="producto" onclick="setFilter('producto')">Productos</button>
                <button class="filter-tab" data-filter="servicio" onclick="setFilter('servicio')">Servicios</button>
                <button class="filter-tab" data-filter="descuento" onclick="setFilter('descuento')">Descuentos</button>
            </div>
            
            <div class="recompensas-grid" id="recompensas-grid">
                <?php if (empty($recompensas)): ?>
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <i class="fas fa-gift"></i>
                        <h3>No hay recompensas todavía</h3>
                        <p>Crea la primera recompensa usando el formulario</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recompensas as $r): ?>
                        <div class="recompensa-item" data-tipo="<?php echo $r['tipo']; ?>">
                            <div class="item-header">
                                <span class="item-badge <?php echo $r['tipo'] ?: 'descuento'; ?>">
                                    <?php 
                                        $tipoIcon = 'fa-gift';
                                        if($r['tipo'] == 'producto') $tipoIcon = 'fa-box';
                                        if($r['tipo'] == 'servicio') $tipoIcon = 'fa-hand-holding-heart';
                                        if($r['tipo'] == 'descuento' || empty($r['tipo'])) $tipoIcon = 'fa-tags';
                                    ?>
                                    <i class="fas <?php echo $tipoIcon; ?>"></i>
                                    <?php echo ucfirst($r['tipo'] ?: 'descuento'); ?>
                                </span>
                                <span class="item-puntos"><?php echo $r['puntos_requeridos']; ?> PTS</span>
                            </div>
                            <h3 class="item-titulo"><?php echo htmlspecialchars($r['titulo']); ?></h3>
                            <p class="item-desc"><?php echo htmlspecialchars($r['descripcion']); ?></p>
                            <div class="item-meta">
                                <?php if($r['stock'] > -1): ?>
                                    <span><i class="fas fa-box"></i> Stock: <?php echo $r['stock']; ?></span>
                                <?php endif; ?>
                                <?php if(!empty($r['fecha_limite'])): ?>
                                    <span><i class="fas fa-clock"></i> <?php echo date('d/m/Y', strtotime($r['fecha_limite'])); ?></span>
                                <?php endif; ?>
                                <?php if($r['es_gratis']): ?>
                                    <span><i class="fas fa-check-circle" style="color: #10b981;"></i> Gratis</span>
                                <?php endif; ?>
                            </div>
                            <div class="item-actions">
                                <button class="btn-action btn-edit" onclick='editarRecompensa(<?php echo json_encode($r, JSON_HEX_APOS); ?>)'>
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <form method="POST" style="flex: 1;" onsubmit="return confirm('¿Estás seguro de eliminar esta recompensa?');">
                                    <input type="hidden" name="action" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let productosData = [];
        let currentFilter = 'all';
        let editProductId = null;

        // Alcance
        function setAlcance(value) {
            document.getElementById('f-alcance-tipo').value = value;
            
            // Update visual
            document.querySelectorAll('.alcance-option').forEach(opt => {
                opt.classList.toggle('active', opt.dataset.value === value);
            });
            
            // Show/hide details
            document.querySelectorAll('.alcance-details').forEach(d => d.classList.remove('active'));
            const detail = document.getElementById('alcance-' + value);
            if (detail) detail.classList.add('active');
            
            loadProducts();
            updatePreview();
        }

        function toggleAlcance() {
            const value = document.getElementById('f-alcance-tipo').value;
            setAlcance(value);
        }

        // Tipo de producto
        function setProductType(type) {
            document.getElementById('f-item-type').value = type;
            
            document.querySelectorAll('.productos-tabs .tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.type === type);
            });
            
            loadProducts();
            updatePreview();
        }

        // Cargar productos
        function loadProducts() {
            const alcance = document.getElementById('f-alcance-tipo').value;
            const itemType = document.getElementById('f-item-type').value;
            const tipoAliadoSelect = document.getElementById('f-tipo-valor').value;
            const grid = document.getElementById('productos-grid');
            const productTabla = document.getElementById('f-producto-tabla');
            
            // Loading state
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><div class="loading-spinner"></div><p style="margin-top: 15px; color: #94a3b8;">Cargando productos...</p></div>';
            
            // Determinar tipo de producto y IDs según el alcance
            if (alcance === 'global') {
                // GLOBAL = Todos los aliados (veterinarias + tiendas)
                // Cargamos ambos tipos
                fetchBothTypes('', itemType, grid, productTabla);
                return;
                
            } else if (alcance === 'tipo_aliado') {
                // POR TIPO = Todas las veterinarias O todas las tiendas
                const queryType = tipoAliadoSelect || 'veterinaria';
                const queryIds = '';
                
                // Determinar la tabla correcta
                if (itemType === 'servicio' && queryType === 'veterinaria') {
                    productTabla.value = 'servicios_veterinaria';
                } else if (itemType === 'producto') {
                    productTabla.value = (queryType === 'veterinaria') ? 'productos_veterinaria' : 'productos_tienda';
                }
                
                fetch(`admin-ajax-get-products.php?tipo=${queryType}&ids=${queryIds}&item_type=${itemType}`)
                .then(r => r.json())
                .then(data => {
                    productosData = data;
                    renderProductosGrid(data, itemType);
                })
                .catch(err => {
                    console.error('Error cargando productos:', err);
                    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #f87171; padding: 30px;"><i class="fas fa-exclamation-triangle"></i> Error al cargar productos</div>';
                });
                
            } else if (alcance === 'especificos') {
                // ESPECÍFICOS = Solo los aliados seleccionados
                // Ahora carga productos de TODOS los tipos de aliados seleccionados
                loadProductsForSpecificAliados(grid, productTabla, itemType);
                return;
                
            } else {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 30px;"><i class="fas fa-arrow-up"></i> Selecciona un alcance arriba para ver los productos disponibles</div>';
                productTabla.value = '';
                return;
            }
        }

        // Función nueva para cargar productos cuando hay aliados específicos seleccionados
        function loadProductsForSpecificAliados(grid, productTabla, itemType) {
            const checks = document.querySelectorAll('.check-aliado:checked');
            
            if (checks.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 30px;"><i class="fas fa-hand-pointer"></i> Selecciona aliados específicos arriba</div>';
                return;
            }
            
            // Obtener los IDs de aliados seleccionados
            const selectedIds = Array.from(checks).map(c => c.value);
            
            // Obtener los tipos de aliados seleccionados
            const tiposAliados = new Set();
            checks.forEach(check => {
                const label = check.nextElementSibling.querySelector('.aliado-tipo').innerText.toLowerCase();
                tiposAliados.add(label);
            });
            
            const promises = [];
            
            // Cargar productos de tiendas si hay tiendas seleccionadas
            if (tiposAliados.has('tienda')) {
                const idsTienda = selectedIds.filter(id => {
                    const check = document.getElementById('check-' + id);
                    return check && check.nextElementSibling.querySelector('.aliado-tipo').innerText.toLowerCase() === 'tienda';
                });
                if (idsTienda.length > 0 || itemType === 'producto') {
                    promises.push(
                        fetch(`admin-ajax-get-products.php?tipo=tienda&ids=${idsTienda.join(',')}&item_type=producto`)
                        .then(r => r.json())
                        .catch(() => [])
                    );
                } else {
                    promises.push(Promise.resolve([]));
                }
            } else {
                promises.push(Promise.resolve([]));
            }
            
            // Cargar productos de veterinarias si hay veterinarias seleccionadas
            if (tiposAliados.has('veterinaria')) {
                const idsVet = selectedIds.filter(id => {
                    const check = document.getElementById('check-' + id);
                    return check && check.nextElementSibling.querySelector('.aliado-tipo').innerText.toLowerCase() === 'veterinaria';
                });
                
                // Cargar productos de veterinaria
                promises.push(
                    fetch(`admin-ajax-get-products.php?tipo=veterinaria&ids=${idsVet.join(',')}&item_type=producto`)
                    .then(r => r.json())
                    .catch(() => [])
                );
                
                // Cargar servicios de veterinaria
                promises.push(
                    fetch(`admin-ajax-get-products.php?tipo=veterinaria&ids=${idsVet.join(',')}&item_type=servicio`)
                    .then(r => r.json())
                    .catch(() => [])
                );
            } else {
                promises.push(Promise.resolve([]));
                promises.push(Promise.resolve([]));
            }
            
            Promise.all(promises).then(results => {
                // Combinar todos los resultados
                let allData = [];
                
                // Agregar productos de tiendas (results[0])
                if (results[0] && results[0].length > 0) {
                    allData = allData.concat(results[0].map(p => ({...p, source: 'tienda'})));
                }
                
                // Agregar productos de veterinarias (results[1])
                if (results[1] && results[1].length > 0) {
                    allData = allData.concat(results[1].map(p => ({...p, source: 'veterinaria_producto'})));
                }
                
                // Agregar servicios de veterinarias (results[2])
                if (results[2] && results[2].length > 0) {
                    allData = allData.concat(results[2].map(p => ({...p, source: 'veterinaria_servicio'})));
                }
                
                productosData = allData;
                
                // Actualizar la tabla según el tipo seleccionado
                if (itemType === 'servicio') {
                    productTabla.value = 'servicios_veterinaria';
                } else {
                    productTabla.value = 'productos_tienda';
                }
                
                if (allData.length === 0) {
                    grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 40px 20px;">
                        <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                        No hay ${itemType === 'servicio' ? 'servicios' : 'productos'} disponibles para los aliados seleccionados
                    </div>`;
                } else {
                    renderProductosGrid(allData, itemType);
                }
            }).catch(err => {
                console.error('Error cargando productos:', err);
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #f87171; padding: 30px;"><i class="fas fa-exclamation-triangle"></i> Error al cargar productos</div>';
            });
        }

        // Función para cargar ambos tipos (veterinarias y tiendas) en modo global
        function fetchBothTypes(queryIds, itemType, grid, productTabla) {
            const promises = [];
            
            // Cargar productos de tiendas
            promises.push(
                fetch(`admin-ajax-get-products.php?tipo=tienda&ids=${queryIds}&item_type=producto`)
                .then(r => r.json())
                .catch(() => [])
            );
            
            // Cargar productos de veterinarias
            promises.push(
                fetch(`admin-ajax-get-products.php?tipo=veterinaria&ids=${queryIds}&item_type=producto`)
                .then(r => r.json())
                .catch(() => [])
            );
            
            // Cargar servicios de veterinarias
            promises.push(
                fetch(`admin-ajax-get-products.php?tipo=veterinaria&ids=${queryIds}&item_type=servicio`)
                .then(r => r.json())
                .catch(() => [])
            );
            
            Promise.all(promises).then(results => {
                // Combinar todos los resultados
                let allData = [];
                
                // Agregar productos de tiendas
                if (results[0] && results[0].length > 0) {
                    allData = allData.concat(results[0].map(p => ({...p, source: 'tienda'})));
                }
                
                // Agregar productos de veterinarias
                if (results[1] && results[1].length > 0) {
                    allData = allData.concat(results[1].map(p => ({...p, source: 'veterinaria_producto'})));
                }
                
                // Agregar servicios de veterinarias
                if (results[2] && results[2].length > 0) {
                    allData = allData.concat(results[2].map(p => ({...p, source: 'veterinaria_servicio'})));
                }
                
                productosData = allData;
                
                // Actualizar la tabla según el tipo seleccionado
                const currentItemType = document.getElementById('f-item-type').value;
                if (currentItemType === 'servicio') {
                    productTabla.value = 'servicios_veterinaria';
                } else {
                    productTabla.value = 'productos_tienda'; // Por defecto para global
                }
                
                renderProductosGrid(allData, currentItemType);
            });
        }

        function renderProductosGrid(data, itemType) {
            const grid = document.getElementById('productos-grid');
            
            if (!data || data.length === 0) {
                grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 40px 20px;">
                    <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                    No hay ${itemType === 'servicio' ? 'servicios' : 'productos'} disponibles
                </div>`;
                return;
            }

            let html = '';
            data.forEach(p => {
                const imagenSrc = p.imagen_url ? p.imagen_url : (p.imagen ? '../'+p.imagen : '');
                const icono = itemType === 'servicio' ? 'fa-concierge-bell' : 'fa-box';
                
                html += `
                    <div class="producto-card" onclick="seleccionarProducto(${p.id}, '${itemType}')" id="prod-card-${p.id}">
                        ${itemType === 'servicio' ? '<span class="badge-tipo servicio">Servicio</span>' : '<span class="badge-tipo producto">Producto</span>'}
                        ${imagenSrc ? 
                            `<img class="producto-img" src="${imagenSrc}" onerror="this.parentElement.innerHTML=this.parentElement.innerHTML.replace(this.outerHTML, '<div class=\\'producto-icon\\'><i class=\\'fas ${icono}\\'></i></div>')">` :
                            `<div class="producto-icon"><i class="fas ${icono}"></i></div>`
                        }
                        <div class="producto-nombre">${p.nombre}</div>
                        <div class="producto-precio">$${parseInt(p.precio || 0).toLocaleString()}</div>
                        <div class="producto-aliado">${p.aliado_nombre || ''}</div>
                    </div>
                `;
            });
            
            grid.innerHTML = html;
        }

        function seleccionarProducto(id, itemType) {
            const producto = productosData.find(p => p.id == id);
            if (!producto) return;

            // Highlight visual
            document.querySelectorAll('.producto-card').forEach(c => c.classList.remove('selected'));
            const card = document.getElementById('prod-card-' + id);
            if (card) {
                card.classList.add('selected');
                // Pulse animation
                card.style.transform = 'scale(1.05)';
                setTimeout(() => card.style.transform = '', 200);
            }

            // Actualizar campos ocultos
            document.getElementById('f-producto-id').value = id;
            
            // Auto-completar título si está vacío
            if (!document.getElementById('f-titulo').value) {
                document.getElementById('f-titulo').value = producto.nombre;
            }

            // Auto-completar precios
            document.getElementById('f-precio-original').value = producto.precio;
            
            // Si es gratis, oferta es 0
            const esGratis = document.getElementById('f-es-gratis').checked;
            if (!esGratis && !document.getElementById('f-precio-oferta').value) {
                 document.getElementById('f-precio-oferta').value = producto.precio;
            }
            
            // Auto-completar descripción si está vacía
            if (!document.getElementById('f-desc').value && producto.descripcion) {
                document.getElementById('f-desc').value = producto.descripcion;
            }

            // Mostrar preview imagen
            const previewImg = document.getElementById('preview-image');
            const previewIcon = document.getElementById('preview-icon');
            
            if (producto.imagen || producto.imagen_url) {
                const src = producto.imagen_url || ('../' + producto.imagen);
                previewImg.src = src;
                previewImg.style.display = 'block';
                previewIcon.style.display = 'none';
            } else {
                previewImg.style.display = 'none';
                previewIcon.style.display = 'block';
            }
            
            updatePreview();
        }

        function limpiarProducto() {
            document.getElementById('f-producto-id').value = '';
            document.getElementById('f-producto-tabla').value = '';
            
            // Reset preview image
            document.getElementById('preview-image').style.display = 'none';
            document.getElementById('preview-icon').style.display = 'block';
            
            // Quitar selección visual
            document.querySelectorAll('.producto-card').forEach(c => c.classList.remove('selected'));
        }

        // Listener para checkbox "Es Gratis"
        document.getElementById('f-es-gratis').addEventListener('change', function() {
            if(this.checked) {
                document.getElementById('f-precio-oferta').value = 0;
                document.getElementById('f-porcentaje').value = 100;
            }
            updatePreview();
        });

        // Preview
        function updatePreview() {
            const titulo = document.getElementById('f-titulo').value || 'Nueva Recompensa';
            const descripcion = document.getElementById('f-desc').value || 'La descripción aparecerá aquí...';
            const puntos = document.getElementById('f-puntos').value || 0;
            const tipo = document.getElementById('f-tipo').value;
            const precioOriginal = parseFloat(document.getElementById('f-precio-original').value) || 0;
            const precioOferta = parseFloat(document.getElementById('f-precio-oferta').value) || 0;
            const porcentaje = document.getElementById('f-porcentaje').value || 0;
            const esGratis = document.getElementById('f-es-gratis').checked;
            const stock = document.getElementById('f-stock').value;
            const alcance = document.getElementById('f-alcance-tipo').value;
            
            // Update titulo and desc
            document.getElementById('preview-titulo').innerText = titulo;
            document.getElementById('preview-descripcion').innerText = descripcion;
            document.getElementById('preview-puntos').innerText = puntos + ' PTS';
            
            // Update tipo badge
            const badge = document.getElementById('preview-tipo-badge');
            const tipoIcons = {
                'producto': 'fa-box',
                'servicio': 'fa-hand-holding-heart',
                'descuento': 'fa-tags'
            };
            badge.className = 'preview-tipo-badge ' + tipo;
            badge.innerHTML = `<i class="fas ${tipoIcons[tipo] || 'fa-gift'}"></i> ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
            
            // Update precios
            const preciosDiv = document.getElementById('preview-precios');
            if (esGratis) {
                preciosDiv.innerHTML = '<div class="preview-gratis">🎉 ¡COMPLETAMENTE GRÁTIS!</div>';
            } else if (precioOriginal > 0) {
                let html = '';
                if (precioOferta > 0 && precioOferta < precioOriginal) {
                    html += '<span class="preview-precio-original">$' + precioOriginal.toLocaleString() + '</span>';
                    html += '<span class="preview-precio-oferta">$' + precioOferta.toLocaleString() + '</span>';
                    if (porcentaje > 0) {
                        html += '<span class="preview-descuento">-' + porcentaje + '%</span>';
                    }
                } else {
                    html += '<span class="preview-precio-oferta">$' + precioOriginal.toLocaleString() + '</span>';
                }
                preciosDiv.innerHTML = html;
            } else {
                preciosDiv.innerHTML = '<span class="preview-precio-oferta">$0</span>';
            }
            
            // Update stock
            const stockDiv = document.getElementById('preview-stock');
            if (stock > -1) {
                stockDiv.style.display = 'flex';
                document.getElementById('preview-stock-text').innerText = 'Stock: ' + stock;
            } else {
                stockDiv.style.display = 'none';
            }
            
            // Update aliados
            const aliadosDiv = document.getElementById('preview-aliados');
            if (alcance === 'global') {
                aliadosDiv.innerText = 'Todos los aliados';
            } else if (alcance === 'tipo_aliado') {
                const tipoValor = document.getElementById('f-tipo-valor').value;
                aliadosDiv.innerText = tipoValor === 'veterinaria' ? 'Solo Veterinarias' : 'Solo Tiendas';
            } else if (alcance === 'especificos') {
                const checks = document.querySelectorAll('.check-aliado:checked');
                if (checks.length > 0) {
                    const names = Array.from(checks).map(function(c) { return c.nextElementSibling.querySelector('.aliado-nombre').innerText; });
                    aliadosDiv.innerText = names.join(', ');
                } else {
                    aliadosDiv.innerText = 'Selecciona aliados';
                }
            }
        }

        // Filter recompensas
        function filterRecompensas() {
            var search = document.getElementById('search-recompensas').value.toLowerCase();
            var items = document.querySelectorAll('.recompensa-item');
            
            items.forEach(function(item) {
                var titulo = item.querySelector('.item-titulo').innerText.toLowerCase();
                var desc = item.querySelector('.item-desc').innerText.toLowerCase();
                var match = titulo.includes(search) || desc.includes(search);
                item.style.display = match ? 'block' : 'none';
            });
        }

        // Set filter
        function setFilter(filter) {
            currentFilter = filter;
            
            // Update tab
            document.querySelectorAll('.filter-tab').forEach(function(tab) {
                tab.classList.toggle('active', tab.dataset.filter === filter);
            });
            
            // Filter items
            var items = document.querySelectorAll('.recompensa-item');
            items.forEach(function(item) {
                var tipo = item.dataset.tipo;
                var match = filter === 'all' || tipo === filter;
                item.style.display = match ? 'block' : 'none';
            });
        }

        // Filter aliados
        function filterAliados() {
            var search = document.getElementById('aliados-search-input').value.toLowerCase();
            var items = document.querySelectorAll('.aliado-checkbox');
            
            items.forEach(function(item) {
                var nombre = item.querySelector('.aliado-nombre').innerText.toLowerCase();
                item.style.display = nombre.includes(search) ? 'flex' : 'none';
            });
        }

        // Editar recompensa
        function editarRecompensa(r) {
            document.getElementById('form-title').innerHTML = '<i class="fas fa-edit"></i> Editar: ' + r.titulo;
            document.getElementById('form-action').value = 'editar';
            document.getElementById('form-id').value = r.id;
            
            document.getElementById('f-titulo').value = r.titulo;
            document.getElementById('f-puntos').value = r.puntos_requeridos;
            document.getElementById('f-tipo').value = r.tipo || 'producto';
            document.getElementById('f-stock').value = r.stock;
            document.getElementById('f-fecha-limite').value = r.fecha_limite ? r.fecha_limite.replace(' ', 'T') : '';
            document.getElementById('f-ubicacion').value = r.ubicacion_canje || '';
            document.getElementById('f-desc').value = r.descripcion;
            document.getElementById('f-tipo-acceso').value = r.tipo_acceso || 'free';

            // Poblar campos de beneficio
            document.getElementById('f-precio-original').value = r.precio_original || '';
            document.getElementById('f-precio-oferta').value = r.precio_oferta || '';
            document.getElementById('f-porcentaje').value = r.porcentaje_descuento || '';
            document.getElementById('f-es-gratis').checked = (r.es_gratis == 1);
            
            // Set Scope
            var alcance = r.alcance_tipo || 'global';
            document.getElementById('f-alcance-tipo').value = alcance;
            
            // Set Product ID for later selection
            window.editProductId = r.producto_id;
            document.getElementById('f-producto-tabla').value = r.producto_tabla || '';

            // Reset fields
            document.getElementById('f-tipo-valor').value = 'veterinaria';
            document.querySelectorAll('.check-aliado').forEach(function(c) { c.checked = false; });
            
            if (alcance === 'tipo_aliado') {
                document.getElementById('f-tipo-valor').value = r.alcance_valor;
            } else if (alcance === 'especificos' && r.alcance_valor) {
                var ids = r.alcance_valor.split(',');
                ids.forEach(function(id) {
                    var chk = document.getElementById('check-' + id);
                    if(chk) chk.checked = true;
                });
            }
            
            // Update alcance visual
            setAlcance(alcance);
            
            // Scroll to form
            document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth' });
            
            // Update button text
            document.getElementById('btn-guardar-text').innerText = 'Actualizar Recompensa';
            
            updatePreview();
        }

        // Reset form
        function resetForm() {
            document.getElementById('rewardForm').reset();
            document.getElementById('form-title').innerHTML = '<i class="fas fa-plus-circle"></i> Nueva Recompensa';
            document.getElementById('form-action').value = 'crear';
            document.getElementById('form-id').value = '';
            window.editProductId = null;
            
            // Limpiar beneficios manualmente
            document.getElementById('f-precio-original').value = '';
            document.getElementById('f-precio-oferta').value = '';
            document.getElementById('f-porcentaje').value = '';
            document.getElementById('f-es-gratis').checked = false;
            
            // Limpiar preview y grid
            limpiarProducto();
            document.getElementById('productos-grid').innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 40px 20px;"><i class="fas fa-arrow-up" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>Selecciona un alcance arriba para ver los productos disponibles</div>';

            toggleAlcance();
            updatePreview();
            
            // Reset button text
            document.getElementById('btn-guardar-text').innerText = 'Crear Recompensa';
        }

        // Init
        document.addEventListener('DOMContentLoaded', function() {
            toggleAlcance();
            updatePreview();
        });
    </script>
</body>
</html>
