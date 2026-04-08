<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de veterinaria
checkRole('veterinaria');

$userId = $_SESSION['user_id'];

// Obtener información del veterinario y su aliado
$stmt = $pdo->prepare("SELECT u.*, a.nombre_local, a.descripcion, a.direccion, a.servicios, a.precio_consulta,
    a.activo, a.pendiente_verificacion, a.fotos_verificacion
    FROM usuarios u
    LEFT JOIN aliados a ON a.usuario_id = u.id AND a.tipo = 'veterinaria'
    WHERE u.id = ?");
$stmt->execute([$userId]);
$vetInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// ID del aliado (veterinaria)
$stmtAliado = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND tipo = 'veterinaria'");
$stmtAliado->execute([$userId]);
$aliadoRow = $stmtAliado->fetch(PDO::FETCH_ASSOC);
$vetId = $aliadoRow['id'] ?? $userId;

$today = date('Y-m-d');

// Citas de hoy
$stmtCitasHoy = $pdo->prepare("SELECT COUNT(*) as total FROM citas WHERE veterinaria_id = ? AND DATE(fecha_hora) = ? AND estado IN ('confirmada','completada')");
$stmtCitasHoy->execute([$vetId, $today]);
$citasHoy = $stmtCitasHoy->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total pacientes únicos
$stmtPacientes = $pdo->prepare("SELECT COUNT(DISTINCT m.id) as total FROM mascotas m INNER JOIN citas c ON m.id = c.mascota_id WHERE c.veterinaria_id = ? AND c.estado IN ('confirmada','completada')");
$stmtPacientes->execute([$vetId]);
$totalPacientes = $stmtPacientes->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Ingresos del mes
$stmtIngresos = $pdo->prepare("SELECT SUM(precio_total) as total FROM citas WHERE veterinaria_id = ? AND MONTH(fecha_hora) = MONTH(CURRENT_DATE()) AND YEAR(fecha_hora) = YEAR(CURRENT_DATE()) AND estado NOT IN ('cancelada','pendiente')");
$stmtIngresos->execute([$vetId]);
$ingresosMes = $stmtIngresos->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Próximas citas confirmadas
$stmtProximas = $pdo->prepare("SELECT COUNT(*) as total FROM citas WHERE veterinaria_id = ? AND estado = 'confirmada' AND fecha_hora >= NOW()");
$stmtProximas->execute([$vetId]);
$totalProximas = $stmtProximas->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Citas pendientes de confirmar
$stmtPendientes = $pdo->prepare("SELECT COUNT(*) as total FROM citas WHERE veterinaria_id = ? AND estado = 'pendiente'");
$stmtPendientes->execute([$vetId]);
$citasPendientes = $stmtPendientes->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Lista citas próximas (las 6 más cercanas)
$stmtLista = $pdo->prepare("
    SELECT c.*, m.nombre as mascota_nombre, m.raza, m.especie, m.foto_perfil as mascota_foto,
           u.nombre as propietario_nombre, u.telefono as propietario_tel,
           s.nombre as servicio_nombre
    FROM citas c
    INNER JOIN mascotas m ON c.mascota_id = m.id
    INNER JOIN usuarios u ON m.user_id = u.id
    LEFT JOIN servicios_veterinaria s ON c.servicio_id = s.id
    WHERE c.veterinaria_id = ? AND c.estado = 'confirmada' AND c.fecha_hora >= NOW()
    ORDER BY c.fecha_hora ASC
    LIMIT 6
");
$stmtLista->execute([$vetId]);
$citasProximas = $stmtLista->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Últimas 5 completadas (historial reciente)
$stmtCompletadas = $pdo->prepare("
    SELECT c.*, m.nombre as mascota_nombre, m.raza, u.nombre as propietario_nombre
    FROM citas c
    INNER JOIN mascotas m ON c.mascota_id = m.id
    INNER JOIN usuarios u ON m.user_id = u.id
    WHERE c.veterinaria_id = ? AND c.estado = 'completada'
    ORDER BY c.fecha_hora DESC
    LIMIT 5
");
$stmtCompletadas->execute([$vetId]);
$citasCompletadas = $stmtCompletadas->fetchAll(PDO::FETCH_ASSOC) ?? [];

// Nombre a mostrar
$nombreVet = $vetInfo['nombre_local'] ?? $vetInfo['nombre'] ?? 'Veterinaria';
// verificado = activo=1 y pendiente_verificacion=0
$verificado = !empty($vetInfo['activo']) && empty($vetInfo['pendiente_verificacion']);
// Foto: primera foto de verificación si existe, o null

$fotoVet = !empty($vetInfo['foto_local']) ? $vetInfo['foto_local'] : null;
if (!$fotoVet && !empty($vetInfo['fotos_verificacion'])) {
    $fotos = json_decode($vetInfo['fotos_verificacion'], true);
    if (is_array($fotos) && !empty($fotos[0])) $fotoVet = $fotos[0];
}
$urlFotoVet = buildImgUrl($fotoVet);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – <?php echo htmlspecialchars($nombreVet); ?> | RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/themes.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/color-fixes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Variables ── */
        :root {
            --vet-primary: #7c3aed;
            --vet-primary-dark: #5b21b6;
            --vet-accent: #10b981;
            --vet-amber: #f59e0b;
            --vet-danger: #ef4444;
            --vet-surface: #ffffff;
            --vet-bg: #f1f5f9;
            --vet-border: #e2e8f0;
            --vet-text: #0f172a;
            --vet-muted: #64748b;
            --vet-gradient: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #6d28d9 100%);
            --vet-gradient-green: linear-gradient(135deg, #059669, #10b981);
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md: 0 4px 16px rgba(0,0,0,.07), 0 2px 6px rgba(0,0,0,.04);
            --shadow-lg: 0 10px 40px rgba(0,0,0,.10), 0 4px 12px rgba(0,0,0,.05);
        }

        /* ── Hero Header ── */
        .vet-hero {
            background: var(--vet-gradient);
            border-radius: 24px;
            padding: 36px 32px 28px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .vet-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
            pointer-events: none;
        }
        .vet-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: 30%;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
            pointer-events: none;
        }
        .hero-top {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }
        .hero-avatar {
            width: 72px; height: 72px;
            border-radius: 18px;
            background: rgba(255,255,255,.2);
            border: 2px solid rgba(255,255,255,.35);
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; color: white;
            overflow: hidden; flex-shrink: 0;
        }
        .hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .hero-info { flex: 1; }
        .hero-info h1 {
            color: white; font-size: 22px; font-weight: 700;
            margin: 0 0 4px; display: flex; align-items: center; gap: 8px;
        }
        .badge-verificado {
            background: rgba(255,255,255,.22);
            border: 1px solid rgba(255,255,255,.35);
            color: white; font-size: 11px; font-weight: 600;
            padding: 2px 10px; border-radius: 20px;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .hero-info p { color: rgba(255,255,255,.72); font-size: 14px; margin: 0; }
        .hero-actions {
            display: flex; gap: 10px; flex-shrink: 0;
        }
        .btn-hero {
            padding: 9px 18px; border-radius: 12px; font-weight: 600; font-size: 13px;
            display: inline-flex; align-items: center; gap: 7px; cursor: pointer;
            text-decoration: none; transition: all .2s; border: none;
        }
        .btn-hero-outline {
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.3);
            color: white;
        }
        .btn-hero-outline:hover { background: rgba(255,255,255,.25); }
        .btn-hero-solid {
            background: white; color: var(--vet-primary); font-weight: 700;
        }
        .btn-hero-solid:hover { background: #f0e6ff; }

        /* ── Stats Grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
        }
        .stat-card {
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 16px;
            padding: 18px 16px;
            text-align: center;
            backdrop-filter: blur(12px);
            transition: all .25s;
            cursor: default;
        }
        .stat-card:hover {
            background: rgba(255,255,255,.22);
            transform: translateY(-3px);
        }
        .stat-icon {
            font-size: 18px; color: rgba(0, 0, 0, 0.8);
            margin-bottom: 6px;
        }
        .stat-num {
            font-size: 26px; font-weight: 800; color: white;
            line-height: 1;
        }
        .stat-num.green { color: #6ee7b7; }
        .stat-num.amber { color: #fcd34d; }
        .stat-label {
            font-size: 11px; color: rgba(0, 0, 0, 0.65);
            margin-top: 5px; font-weight: 500; text-transform: uppercase; letter-spacing: .5px;
        }

        /* ── Section Cards ── */
        .card {
            background: var(--vet-surface);
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--vet-border);
            overflow: hidden;
        }
        .card-header {
            padding: 20px 24px 16px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--vet-border);
        }
        .card-title {
            font-size: 16px; font-weight: 700; color: var(--vet-text);
            display: flex; align-items: center; gap: 10px;
        }
        .card-title i { color: var(--vet-primary); font-size: 15px; }
        .card-body { padding: 20px 24px; }
        .card-link {
            font-size: 13px; color: var(--vet-primary); font-weight: 600;
            text-decoration: none; display: flex; align-items: center; gap: 4px;
        }
        .card-link:hover { color: var(--vet-primary-dark); }

        /* ── Canje box ── */
        .canje-box {
            background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
            border: 1.5px solid #a7f3d0;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex; align-items: center; gap: 20px;
        }
        .canje-icon {
            width: 52px; height: 52px; border-radius: 14px;
            background: var(--vet-gradient-green);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: white; flex-shrink: 0;
        }
        .canje-content { flex: 1; }
        .canje-content h4 { margin: 0 0 2px; font-size: 15px; color: #064e3b; }
        .canje-content p { margin: 0 0 12px; font-size: 13px; color: #047857; }
        .canje-form { display: flex; gap: 10px; }
        .canje-input {
            flex: 1; padding: 11px 16px;
            border: 1.5px solid #6ee7b7; border-radius: 12px;
            font-size: 14px; font-weight: 600; letter-spacing: 1px;
            background: white; color: var(--vet-text);
            transition: border-color .2s;
        }
        .canje-input:focus { outline: none; border-color: var(--vet-accent); box-shadow: 0 0 0 3px rgba(16,185,129,.12); }
        .btn-canje {
            padding: 11px 20px; border-radius: 12px; font-weight: 700; font-size: 13px;
            background: var(--vet-gradient-green); color: white; border: none; cursor: pointer;
            display: flex; align-items: center; gap: 7px; transition: all .2s;
            box-shadow: 0 3px 10px rgba(16,185,129,.3);
        }
        .btn-canje:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(16,185,129,.4); }
        #canje-result { margin-top: 14px; border-radius: 14px; display: none; padding: 16px 20px; animation: fadeUp .35s ease; }
        .canje-ok { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
        .canje-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

        /* ── Appointment list ── */
        .apt-list { display: flex; flex-direction: column; gap: 12px; }
        .apt-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; border-radius: 14px;
            border: 1px solid var(--vet-border);
            background: #fafbfc;
            transition: all .2s;
        }
        .apt-item:hover { border-color: #c4b5fd; background: #faf5ff; transform: translateX(3px); }
        .apt-avatar {
            width: 44px; height: 44px; border-radius: 12px;
            background: var(--vet-gradient);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: white; flex-shrink: 0; overflow: hidden;
        }
        .apt-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .apt-info { flex: 1; min-width: 0; }
        .apt-name { font-weight: 700; color: var(--vet-text); font-size: 14px; }
        .apt-sub { font-size: 12px; color: var(--vet-muted); margin-top: 2px; display: flex; gap: 12px; flex-wrap: wrap; }
        .apt-sub span { display: flex; align-items: center; gap: 4px; }
        .apt-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .btn-sm {
            padding: 7px 13px; border-radius: 10px; font-size: 12px; font-weight: 600;
            border: none; cursor: pointer; display: flex; align-items: center; gap: 5px;
            transition: all .2s; text-decoration: none;
        }
        .btn-purple { background: #ede9fe; color: var(--vet-primary); }
        .btn-purple:hover { background: #ddd6fe; }
        .btn-amber { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .btn-amber:hover { background: #fef3c7; }
        .btn-ia {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white;
        }
        .btn-ia:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(124,58,237,.35); }

        /* ── Time badge ── */
        .time-badge {
            font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 8px;
            white-space: nowrap;
        }
        .time-today { background: #ede9fe; color: #5b21b6; }
        .time-soon  { background: #fffbeb; color: #92400e; }
        .time-future{ background: #f1f5f9; color: #475569; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 40px 20px; color: var(--vet-muted);
        }
        .empty-state i { font-size: 36px; opacity: .35; margin-bottom: 12px; display: block; }
        .empty-state p { font-size: 14px; margin: 0; }

        /* ── Modal IA ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,.55);
            backdrop-filter: blur(4px);
            z-index: 9999;
            justify-content: center; align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white; border-radius: 24px;
            width: 90%; max-width: 640px; max-height: 90vh;
            overflow: hidden; display: flex; flex-direction: column;
            box-shadow: 0 25px 80px rgba(0,0,0,.25);
            animation: modalIn .3s cubic-bezier(.34,1.56,.64,1);
        }
        .modal-head {
            padding: 24px 28px 18px;
            border-bottom: 1px solid var(--vet-border);
            display: flex; align-items: flex-start; gap: 14px;
        }
        .modal-head-icon {
            width: 48px; height: 48px; border-radius: 14px;
            background: linear-gradient(135deg,#7c3aed,#a855f7);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: white; flex-shrink: 0;
        }
        .modal-head-text h2 { margin: 0 0 3px; font-size: 18px; color: var(--vet-text); }
        .modal-head-text p { margin: 0; font-size: 13px; color: var(--vet-muted); }
        .modal-close {
            margin-left: auto; background: none; border: none;
            font-size: 20px; cursor: pointer; color: var(--vet-muted);
            padding: 4px 8px; border-radius: 8px; transition: background .2s;
        }
        .modal-close:hover { background: #f1f5f9; }
        .modal-body {
            padding: 22px 28px;
            overflow-y: auto; flex: 1;
        }
        .gemini-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #4285f4, #ea4335, #fbbc04, #34a853);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            font-size: 12px; font-weight: 700;
        }
        .ia-section {
            margin-bottom: 18px; padding: 16px;
            border-radius: 14px; border: 1px solid var(--vet-border);
        }
        .ia-section h4 {
            margin: 0 0 10px; font-size: 14px;
            display: flex; align-items: center; gap: 7px;
        }
        .ia-section ul { margin: 0; padding-left: 18px; font-size: 13px; line-height: 1.75; color: #334155; }
        .ia-section.purple { border-color: #c4b5fd; background: #faf5ff; }
        .ia-section.purple h4 { color: #5b21b6; }
        .ia-section.amber  { border-color: #fde68a; background: #fffbeb; }
        .ia-section.amber h4  { color: #92400e; }
        .ia-section.blue   { border-color: #bfdbfe; background: #eff6ff; }
        .ia-section.blue h4   { color: #1e40af; }
        .ia-section.red    { border-color: #fecaca; background: #fef2f2; }
        .ia-section.red h4    { color: #991b1b; }
        .ia-section.green  { border-color: #a7f3d0; background: #ecfdf5; }
        .ia-section.green h4  { color: #065f46; }
        .ia-loading {
            text-align: center; padding: 40px 20px; color: var(--vet-muted);
        }
        .ia-spinner {
            width: 40px; height: 40px; border-radius: 50%;
            border: 3px solid #e2e8f0; border-top-color: var(--vet-primary);
            animation: spin .8s linear infinite; margin: 0 auto 14px;
        }
        .ia-disclaimer {
            font-size: 11px; color: var(--vet-muted); text-align: center;
            margin-top: 14px; font-style: italic;
        }

        /* ── Historial reciente ── */
        .hist-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 0; border-bottom: 1px solid var(--vet-border);
        }
        .hist-item:last-child { border-bottom: none; }
        .hist-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--vet-accent); flex-shrink: 0;
        }
        .hist-info { flex: 1; font-size: 13px; }
        .hist-name { font-weight: 600; color: var(--vet-text); }
        .hist-date { color: var(--vet-muted); font-size: 12px; }

        /* ── Responsive ── */
        @media(max-width:900px) {
            .stats-grid { grid-template-columns: repeat(3,1fr); }
            .hero-actions { flex-direction: column; }
        }
        @media(max-width:640px) {
            .stats-grid { grid-template-columns: repeat(2,1fr); }
            .vet-hero { padding: 24px 20px 20px; }
            .hero-top { flex-wrap: wrap; }
            .canje-box { flex-direction: column; }
            .canje-form { flex-direction: column; }
            .apt-item { flex-wrap: wrap; }
            .apt-actions { width: 100%; }
            .btn-sm { flex: 1; justify-content: center; }
            .modal-head { flex-wrap: wrap; }
        }

        /* ── Animations ── */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(10px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes modalIn {
            from { opacity:0; transform:scale(.92) translateY(20px); }
            to   { opacity:1; transform:scale(1) translateY(0); }
        }
        @keyframes spin { to { transform:rotate(360deg); } }
        .animate-up { animation: fadeUp .4s ease both; }
        .delay-1 { animation-delay:.05s; }
        .delay-2 { animation-delay:.1s; }
        .delay-3 { animation-delay:.15s; }
    </style>
</head>
<body class="<?php echo $themeClass ?? ''; ?>">
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Panel Veterinaria</h1>
                <div class="breadcrumb">
                    <span>Veterinaria</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Dashboard</span>
                </div>
            </div>
            <div class="header-right">
                <button class="btn-add" onclick="window.location.href='vet-citas.php'">
                    <i class="fas fa-calendar-check"></i>
                    <span>Ver Citas</span>
                    <?php if($citasPendientes > 0): ?>
                        <span style="background:#ef4444;color:white;font-size:11px;padding:2px 7px;border-radius:20px;"><?php echo $citasPendientes; ?></span>
                    <?php endif; ?>
                </button>
            </div>
        </header>

        <div class="content-wrapper">

            <!-- ── Hero ── -->
            <div class="vet-hero animate-up">
                <div class="hero-top">
                    <div class="hero-avatar">
                        <?php if($fotoVet && $fotoVet != '[]'): ?>
                            <img src="<?php echo $urlFotoVet; ?>" alt="Logo">
                        <?php else: ?>
                            <i class="fas fa-hospital-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="hero-info">
                        <h1>
                            <?php echo htmlspecialchars($nombreVet); ?>
                            <?php if($verificado): ?>
                                <span class="badge-verificado"><i class="fas fa-check-circle"></i> Verificado</span>
                            <?php endif; ?>
                        </h1>
                        <p><i class="fas fa-envelope" style="margin-right:5px;opacity:.7;"></i><?php echo htmlspecialchars($vetInfo['email'] ?? ''); ?><?php if(!empty($vetInfo['direccion'])): ?> &nbsp;·&nbsp; <i class="fas fa-map-marker-alt" style="opacity:.7;"></i> <?php echo htmlspecialchars($vetInfo['direccion']); ?><?php endif; ?></p>
                    </div>
                    <div class="hero-actions">
                        <a href="vet-perfil.php" class="btn-hero btn-hero-outline"><i class="fas fa-edit"></i> Editar perfil</a>
                        <a href="vet-citas.php" class="btn-hero btn-hero-solid"><i class="fas fa-calendar"></i> Gestionar citas</a>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-sun"></i></div>
                        <div class="stat-num"><?php echo intval($citasHoy); ?></div>
                        <div class="stat-label">Citas hoy</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="stat-num amber"><?php echo intval($totalProximas); ?></div>
                        <div class="stat-label">Próximas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-paw"></i></div>
                        <div class="stat-num"><?php echo intval($totalPacientes); ?></div>
                        <div class="stat-label">Pacientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-num amber"><?php echo intval($citasPendientes); ?></div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="stat-num green">$<?php echo number_format($ingresosMes, 0, ',', '.'); ?></div>
                        <div class="stat-label">Ingresos mes</div>
                    </div>
                </div>
            </div>

            <!-- ── Grid principal ── -->
            <div style="display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start;">

                <!-- Columna izquierda -->
                <div style="display:flex; flex-direction:column; gap:24px;">

                    <!-- Citas próximas -->
                    <div class="card animate-up delay-1">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-calendar-check"></i> Citas próximas confirmadas</div>
                            <a href="vet-citas.php" class="card-link">Ver todas <i class="fas fa-chevron-right" style="font-size:11px;"></i></a>
                        </div>
                        <div class="card-body">
                            <?php if(empty($citasProximas)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p><strong>No hay citas confirmadas próximas.</strong><br>Cuando los usuarios agenden contigo aparecerán aquí.</p>
                                </div>
                            <?php else: ?>
                                <div class="apt-list">
                                    <?php foreach($citasProximas as $cita):
                                        $fechaCita = strtotime($cita['fecha_hora']);
                                        $esHoy = date('Y-m-d', $fechaCita) === $today;
                                        $esMañana = date('Y-m-d', $fechaCita) === date('Y-m-d', strtotime('+1 day'));
                                        $horaLabel = date('d/m H:i', $fechaCita);
                                        if($esHoy) { $badge = 'time-today'; $labelText = 'Hoy '.date('H:i',$fechaCita); }
                                        elseif($esMañana) { $badge = 'time-soon'; $labelText = 'Mañana '.date('H:i',$fechaCita); }
                                        else { $badge = 'time-future'; $labelText = date('d/m/Y H:i',$fechaCita); }
                                        $inicialMascota = strtoupper(substr($cita['mascota_nombre'],0,1));
                                    ?>
                                    <div class="apt-item">
                                        <div class="apt-avatar">
                                            <?php if(!empty($cita['mascota_foto'])): ?>
                                                <img src="<?php echo BASE_URL.'/'.htmlspecialchars($cita['mascota_foto']); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo $inicialMascota; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="apt-info">
                                            <div class="apt-name">
                                                <?php echo htmlspecialchars($cita['mascota_nombre']); ?>
                                                <span style="font-weight:400;font-size:12px;color:var(--vet-muted);">(<?php echo htmlspecialchars($cita['raza']); ?>)</span>
                                            </div>
                                            <div class="apt-sub">
                                                <span><i class="fas fa-user"></i><?php echo htmlspecialchars($cita['propietario_nombre']); ?></span>
                                                <?php if(!empty($cita['servicio_nombre'])): ?>
                                                    <span><i class="fas fa-stethoscope"></i><?php echo htmlspecialchars($cita['servicio_nombre']); ?></span>
                                                <?php endif; ?>
                                                <?php if(!empty($cita['propietario_tel'])): ?>
                                                    <span><i class="fas fa-phone"></i><?php echo htmlspecialchars($cita['propietario_tel']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="time-badge <?php echo $badge; ?>"><?php echo $labelText; ?></span>
                                        <div class="apt-actions">
                                            <button class="btn-sm btn-ia" onclick="abrirIA(<?php echo intval($cita['mascota_id']); ?>, '<?php echo addslashes(htmlspecialchars($cita['mascota_nombre'])); ?>')">
                                                <i class="fas fa-robot"></i> IA
                                            </button>
                                            <a href="vet-paciente.php?id=<?php echo intval($cita['mascota_id']); ?>" class="btn-sm btn-purple">
                                                <i class="fas fa-file-medical"></i> Ficha
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Historial reciente -->
                    <?php if(!empty($citasCompletadas)): ?>
                    <div class="card animate-up delay-2">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-history"></i> Atenciones recientes</div>
                            <a href="vet-citas.php?estado=completada" class="card-link">Ver historial <i class="fas fa-chevron-right" style="font-size:11px;"></i></a>
                        </div>
                        <div class="card-body">
                            <?php foreach($citasCompletadas as $c): ?>
                                <div class="hist-item">
                                    <div class="hist-dot"></div>
                                    <div class="hist-info">
                                        <div class="hist-name"><?php echo htmlspecialchars($c['mascota_nombre']); ?> <span style="font-weight:400;color:var(--vet-muted);">— <?php echo htmlspecialchars($c['propietario_nombre']); ?></span></div>
                                        <div class="hist-date"><i class="fas fa-check-circle" style="color:var(--vet-accent);font-size:11px;"></i> Completada el <?php echo date('d/m/Y', strtotime($c['fecha_hora'])); ?></div>
                                    </div>
                                    <a href="vet-paciente.php?id=<?php echo intval($c['mascota_id']); ?>" class="btn-sm btn-purple" style="flex-shrink:0;">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Columna derecha -->
                <div style="display:flex; flex-direction:column; gap:24px;">

                    <!-- Canje de recompensas -->
                    <div class="card animate-up delay-1">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-ticket-alt" style="color:var(--vet-accent);"></i> Validar canje</div>
                        </div>
                        <div class="card-body" style="padding-bottom:18px;">
                            <p style="font-size:13px;color:var(--vet-muted);margin:0 0 14px;">Ingresa el código que muestra el usuario en su app RUGAL.</p>
                            <div style="display:flex;gap:8px;">
                                <input type="text" id="canje-code" class="canje-input" placeholder="RUGAL-XXXX-XXXX" maxlength="20" style="flex:1;padding:10px 14px;border:1.5px solid var(--vet-border);border-radius:11px;font-size:13px;font-weight:600;letter-spacing:1px;">
                                <button class="btn-canje" onclick="validarCanje()" style="padding:10px 16px;border-radius:11px;font-size:13px;">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="canje-result"></div>
                        </div>
                    </div>

                    <!-- Accesos rápidos -->
                    <div class="card animate-up delay-2">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-bolt"></i> Accesos rápidos</div>
                        </div>
                        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding-bottom:18px;">
                            <?php
                            $accesos = [
                                ['vet-citas.php',       'fas fa-calendar-check', 'Mis citas',     '#7c3aed'],
                                ['vet-clientes.php',    'fas fa-paw',            'Pacientes',     '#059669'],
                                ['vet-servicios.php',   'fas fa-stethoscope',    'Servicios',     '#2563eb'],
                                ['vet-horarios.php',    'fas fa-clock',          'Horarios',      '#d97706'],
                                ['vet-productos.php',   'fas fa-pills',          'Productos',     '#dc2626'],
                                ['vet-estadisticas.php','fas fa-chart-bar',      'Estadísticas',  '#0891b2'],
                            ];
                            foreach($accesos as [$href,$icon,$label,$color]):
                            ?>
                            <a href="<?php echo $href; ?>" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid var(--vet-border);text-decoration:none;transition:all .2s;background:#fafbfc;" onmouseover="this.style.borderColor='<?php echo $color; ?>40';this.style.background='<?php echo $color; ?>08'" onmouseout="this.style.borderColor='var(--vet-border)';this.style.background='#fafbfc'">
                                <div style="width:34px;height:34px;border-radius:9px;background:<?php echo $color; ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="<?php echo $icon; ?>" style="color:<?php echo $color; ?>;font-size:14px;"></i>
                                </div>
                                <span style="font-size:13px;font-weight:600;color:var(--vet-text);"><?php echo $label; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Tip IA -->
                    <div class="card animate-up delay-3" style="background:linear-gradient(135deg,#faf5ff,#f5f3ff);border-color:#ddd6fe;">
                        <div class="card-body" style="padding:20px;">
                            <div style="display:flex;align-items:flex-start;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#a855f7);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-robot" style="color:white;font-size:16px;"></i>
                                </div>
                                <div>
                                    <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:4px;">Asistente IA clínico</div>
                                    <div style="font-size:12px;color:#6d28d9;line-height:1.6;">Haz clic en <strong>IA</strong> en cualquier cita para obtener un resumen predictivo del paciente antes de la consulta — síntomas, anomalías recientes y recomendaciones generadas con Gemini.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /content-wrapper -->
    </div><!-- /main-content -->

    <!-- ── Modal IA Clínico ── -->
    <div id="modalIA" class="modal-overlay" onclick="if(event.target===this)cerrarIA()">
        <div class="modal-box">
            <div class="modal-head">
                <div class="modal-head-icon"><i class="fas fa-robot"></i></div>
                <div class="modal-head-text">
                    <h2>Historial Clínico Inteligente</h2>
                    <p id="modalSubtitle">Análisis generado con <span class="gemini-badge">✦ Gemini AI</span></p>
                </div>
                <button class="modal-close" onclick="cerrarIA()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="ia-loading">
                    <div class="ia-spinner"></div>
                    <p>Analizando historial médico del paciente...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    /* ── Canje ── */
    function validarCanje() {
        const codigo = document.getElementById('canje-code').value.trim();
        if (!codigo) { alert('Por favor ingresa un código'); return; }
        const res = document.getElementById('canje-result');
        res.style.display = 'block';
        res.className = '';
        res.innerHTML = '<div style="text-align:center;padding:10px;color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Verificando...</div>';

        const fd = new FormData();
        fd.append('action','validar'); fd.append('codigo', codigo);

        fetch('../ajax-validar-canje.php', { method:'POST', body:fd })
        .then(r=>r.json()).then(data=>{
            if(data.success){
                res.className='canje-ok';
                res.innerHTML=`<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                    <div>
                        <div style="font-weight:700;font-size:15px;">✅ Código válido</div>
                        <div style="margin-top:4px;font-size:13px;">
                            <strong>Premio:</strong> ${data.canje.titulo}<br>
                            ${data.canje.producto_vinc_nombre ? `<strong>Producto:</strong> ${data.canje.producto_vinc_nombre}<br>` : ''}
                            <strong>Usuario:</strong> ${data.canje.usuario_nombre}
                        </div>
                    </div>
                    <button onclick="confirmarCanje('${codigo}')" style="background:#059669;color:white;border:none;padding:10px 16px;border-radius:10px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;"><i class="fas fa-check"></i> Confirmar</button>
                </div>`;
            } else {
                res.className='canje-error';
                res.innerHTML=`<strong>❌ Error:</strong> ${data.message}`;
            }
        }).catch(()=>{ res.className='canje-error'; res.innerHTML='Error de conexión'; });
    }

    function confirmarCanje(codigo) {
        if(!confirm('¿Confirmar que la recompensa fue entregada? No se puede deshacer.')) return;
        const res = document.getElementById('canje-result');
        res.innerHTML='<div style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Procesando...</div>';
        const fd = new FormData();
        fd.append('action','confirmar'); fd.append('codigo', codigo);
        fetch('../ajax-validar-canje.php', {method:'POST',body:fd})
        .then(r=>r.json()).then(data=>{
            if(data.success){
                res.className='canje-ok';
                res.innerHTML='<div style="text-align:center;"><i class="fas fa-check-circle" style="font-size:28px;color:#10b981;display:block;margin-bottom:8px;"></i><strong>¡Canje confirmado exitosamente!</strong></div>';
                document.getElementById('canje-code').value='';
                setTimeout(()=>{ res.style.display='none'; },3000);
            } else { alert(data.message); validarCanje(); }
        });
    }

    /* ── Modal IA ── */
    function abrirIA(mascotaId, nombre) {
        document.getElementById('modalIA').classList.add('active');
        document.getElementById('modalSubtitle').innerHTML = `<strong>${nombre}</strong> — Análisis con <span class="gemini-badge">✦ Gemini AI</span>`;
        document.getElementById('modalBody').innerHTML = `
            <div class="ia-loading">
                <div class="ia-spinner"></div>
                <p>Analizando historial médico de <strong>${nombre}</strong>...</p>
                <p style="font-size:12px;margin-top:4px;opacity:.7;">Cruzando plan de salud, bitácora diaria y citas recientes</p>
            </div>`;

        const fd = new FormData();
        fd.append('mascota_id', mascotaId);

        fetch('../vet/ajax-historial-inteligente.php', { method:'POST', body:fd })
        .then(r=>r.json()).then(data=>{
            if(data.success){
                document.getElementById('modalBody').innerHTML = data.html;
            } else {
                document.getElementById('modalBody').innerHTML = `
                    <div class="ia-section red">
                        <h4><i class="fas fa-exclamation-circle"></i> Error</h4>
                        <p style="margin:0;font-size:13px;">${data.message}</p>
                    </div>`;
            }
        }).catch(()=>{
            document.getElementById('modalBody').innerHTML='<div class="ia-section red"><h4>Error de conectividad</h4><p style="margin:0;font-size:13px;">No se pudo conectar con el servidor.</p></div>';
        });
    }

    function cerrarIA() {
        document.getElementById('modalIA').classList.remove('active');
    }

    document.addEventListener('keydown', e => { if(e.key==='Escape') cerrarIA(); });
    </script>
</body>
</html>