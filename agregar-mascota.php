<?php
require_once 'db.php';
require_once 'puntos-functions.php';
require_once 'includes/razas_data.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user = getUsuario($_SESSION['user_id']);
$userId = $_SESSION['user_id'];
$nivelInfo = obtenerInfoNivel($user['nivel'] ?? 'bronce');
$mensaje = '';
$razas = obtenerListaRazas();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $raza = $_POST['raza'] ?? '';
    $edad = $_POST['edad'] ?? 0;
    $peso = $_POST['peso'] ?? 0;
    $sexo = $_POST['sexo'] ?? '';
    $nivel_actividad = $_POST['nivel_actividad'] ?? 'medio';
    
    // Simple validation
    if ($nombre && $tipo && $raza && $edad && $peso) {
        $stmt = $pdo->prepare("INSERT INTO mascotas (user_id, nombre, tipo, raza, edad, peso, sexo, nivel_actividad) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$userId, $nombre, $tipo, $raza, $edad, $peso, $sexo, $nivel_actividad])) {
            header('Location: mascotas.php');
            exit;
        } else {
            $mensaje = 'Error al guardar la mascota.';
        }
    } else {
        $mensaje = 'Por favor completa todos los campos requeridos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Mascota - RUGAL</title>
    <link rel="stylesheet" href="css/dashboard-colors.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-dark); }
        .form-control { width: 100%; padding: 12px; border: 1px solid var(--light-border); border-radius: 12px; background: var(--light-bg); color: var(--text-dark); }
        .form-control:focus { outline: none; border-color: var(--primary-color); }
        .form-row { display: flex; gap: 20px; }
        .form-col { flex: 1; }
        .info-box { background: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary-color); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info-box p { margin: 0; color: var(--text-dark); font-size: 14px; line-height: 1.6; }
    </style>
</head>
<body class="theme-usuario">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Agregar Nueva Mascota 🐶</h1>
                <div class="breadcrumb">
                    <span>Dashboard</span> <i class="fas fa-chevron-right"></i> <span>Agregar Mascota</span>
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
                <div class="col-8" style="margin: 0 auto; float: none;">
                    <div class="card">
                        <div class="card-header">
                            <h3>Datos de la Mascota</h3>
                        </div>
                        <div style="padding: 30px;">
                            <div class="info-box">
                                <p><strong>ℹ️  Nota:</strong> Estos datos servirán para generar un plan de salud personalizado mensual basado en la raza, edad, peso y nivel de actividad de tu mascota.</p>
                            </div>
                            
                            <?php if ($mensaje): ?>
                                <div style="padding: 15px; background: rgba(239, 68, 68, 0.1); color: var(--danger-color); border-radius: 8px; margin-bottom: 20px;">
                                    <?php echo $mensaje; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Nombre de la Mascota *</label>
                                            <input type="text" name="nombre" class="form-control" required placeholder="Ej: Firulais">
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Tipo *</label>
                                            <select name="tipo" class="form-control" required>
                                                <option value="">-- Selecciona --</option>
                                                <option value="perro">Perro</option>
                                                <option value="gato">Gato</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Raza *</label>
                                            <select name="raza" class="form-control" required>
                                                <option value="">-- Selecciona una raza --</option>
                                                <?php foreach ($razas as $r): ?>
                                                    <option value="<?php echo htmlspecialchars($r); ?>"><?php echo ucfirst($r); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small style="color: #64748b; margin-top: 5px; display: block;">Si no encuentras tu raza, selecciona "mestizo"</small>
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Sexo *</label>
                                            <select name="sexo" class="form-control" required>
                                                <option value="">-- Selecciona --</option>
                                                <option value="macho">Macho</option>
                                                <option value="hembra">Hembra</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Edad (años) *</label>
                                            <input type="number" name="edad" class="form-control" step="0.1" min="0" required placeholder="Ej: 2.5">
                                            <small style="color: #64748b; margin-top: 5px; display: block;">Usado para clasificar: Cachorro (0-1), Adulto (1-7), Senior (7+)</small>
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-group">
                                            <label class="form-label">Peso Actual (kg) *</label>
                                            <input type="number" name="peso" class="form-control" step="0.1" min="0" required placeholder="Ej: 25.5">
                                            <small style="color: #64748b; margin-top: 5px; display: block;">Se comparará con el rango saludable de la raza</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Nivel de Actividad Diaria *</label>
                                    <select name="nivel_actividad" class="form-control" required>
                                        <option value="">-- Selecciona --</option>
                                        <option value="bajo">
                                            🟢 BAJO - Paseos cortos, juegos suaves, mucho descanso
                                        </option>
                                        <option value="medio" selected>
                                            🟡 MEDIO - Paseos regulares, juegos moderados, equilibrado
                                        </option>
                                        <option value="alto">
                                            🔴 ALTO - Mucho ejercicio, deportes caninos, actividad intensa
                                        </option>
                                    </select>
                                    <small style="color: #64748b; margin-top: 5px; display: block;">Esto afectará las recomendaciones de ejercicio y alimentación</small>
                                </div>

                                <div style="margin-top: 30px; text-align: right;">
                                    <a href="mascotas.php" class="btn-cancel" style="margin-right: 10px; padding: 12px 24px; text-decoration: none;">Cancelar</a>
                                    <button type="submit" class="btn-submit">Guardar Mascota y Generar Plan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
