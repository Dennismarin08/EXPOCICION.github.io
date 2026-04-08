<?php
require_once __DIR__ . '/../db.php';

checkRole('admin');

// Obtener todos los temas de la IA local
$sql = "SELECT * FROM temas ORDER BY id DESC";
$stmt = $pdo->query($sql);
$temas = [];
while($row = $stmt->fetch()) {
    // Obtener palabras clave asociadas
    $tId = $row['id'];
    $pk = $pdo->prepare("SELECT texto FROM preguntas WHERE tema_id = ?");
    $pk->execute([$tId]);
    $keywords = $pk->fetchAll(PDO::FETCH_COLUMN);
    $row['keywords'] = implode(', ', $keywords);
    $temas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Entrenamiento IA Local | RUGAL Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .ia-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .knowledge-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 15px; }
        .badge-rule { font-size: 11px; background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 8px; font-weight: 700; border: 1px solid #e2e8f0; }
        .form-row-ia { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #edf2f7; }
        .input-ia { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; }
        .btn-ia-save { background: #2563eb; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-admin.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Entrenamiento de IA</h1>
                <div class="breadcrumb"><span>Configuración</span> <i class="fas fa-chevron-right"></i> <span>Motor de Conocimiento</span></div>
            </div>
            <div class="header-right">
                <button class="btn-add" onclick="abrirModalIA()"><i class="fas fa-plus"></i> Nuevo Conocimiento</button>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="ia-grid">
                <?php foreach($temas as $t): ?>
                <div class="knowledge-card">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <h3 style="margin:0; color:#1e293b;"><?php echo htmlspecialchars($t['tema']); ?></h3>
                            <p style="font-size:12px; color:#64748b; margin-top:4px;">
                                <i class="fas fa-key"></i> Keywords: <strong><?php echo htmlspecialchars($t['keywords']); ?></strong>
                            </p>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button class="btn-sm" style="background:#f1f5f9; color:#475569;" onclick='editarIA(<?php echo json_encode($t); ?>)'><i class="fas fa-edit"></i></button>
                            <button class="btn-sm" style="background:#fee2e2; color:#ef4444;" onclick="borrarIA(<?php echo $t['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    
                    <div style="font-size:14px; color:#475569; line-height:1.6; background:#f8fafc; padding:12px; border-radius:8px;">
                        <?php echo nl2br(htmlspecialchars($t['respuesta'])); ?>
                    </div>

                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span class="badge-rule">Edad: <?php echo $t['edad_min']; ?>-<?php echo $t['edad_max']; ?> años</span>
                        <span class="badge-rule">Peso: <?php echo $t['peso_min']; ?>-<?php echo $t['peso_max']; ?> kg</span>
                        <?php if($t['raza_especifica']): ?><span class="badge-rule">Raza: <?php echo $t['raza_especifica']; ?></span><?php endif; ?>
                        <?php if($t['categoria_enfermedad']): ?><span class="badge-rule" style="background:#eff6ff; color:#2563eb; border-color:#bfdbfe;">Categoría: <?php echo $t['categoria_enfermedad']; ?></span><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Edición/Creación -->
    <div id="modalIA" class="modal-overlay" onclick="if(event.target===this)cerrarModalIA()">
        <div class="modal-content animate-up" style="max-width:700px;">
            <div class="modal-header">
                <h3 id="modalTitle">Gestionar Conocimiento IA</h3>
                <button class="modal-close" onclick="cerrarModalIA()">&times;</button>
            </div>
            <form id="formIA" style="padding:20px; display:flex; flex-direction:column; gap:15px;">
                <input type="hidden" name="id" id="iaId">
                
                <div class="form-group">
                    <label class="form-label">Título del Tema / Condición</label>
                    <input type="text" name="tema" id="iaTema" class="input-ia" required placeholder="Ej: Vómito en cachorros">
                </div>

                <div class="form-group">
                    <label class="form-label">Palabras Clave (Separadas por coma)</label>
                    <input type="text" name="keywords" id="iaKeywords" class="input-ia" required placeholder="vomito, vomitar, ha vomitado">
                </div>

                <div class="form-group">
                    <label class="form-label">Respuesta / Consejo Veterinario</label>
                    <textarea name="respuesta" id="iaRespuesta" class="input-ia" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Configuración de Reglas Inteligentes</label>
                    <div class="form-row-ia">
                        <div>
                            <label style="font-size:11px; font-weight:700;">Edad Mín (Años)</label>
                            <input type="number" name="edad_min" id="iaEdadMin" class="input-ia" value="0">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700;">Edad Máx (Años)</label>
                            <input type="number" name="edad_max" id="iaEdadMax" class="input-ia" value="99">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700;">Peso Mín (kg)</label>
                            <input type="number" step="0.1" name="peso_min" id="iaPesoMin" class="input-ia" value="0">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700;">Peso Máx (kg)</label>
                            <input type="number" step="0.1" name="peso_max" id="iaPesoMax" class="input-ia" value="999">
                        </div>
                    </div>
                </div>

                <div class="form-row-ia" style="background:none; border:none; padding:0;">
                    <div class="form-group">
                        <label class="form-label">Raza Específica (Opcional)</label>
                        <input type="text" name="raza_especifica" id="iaRaza" class="input-ia" placeholder="Ej: Bulldog, Pastor Alemán">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoría Clínica</label>
                        <select name="categoria_enfermedad" id="iaCat" class="input-ia">
                            <option value="General">General</option>
                            <option value="Digestivo">Digestivo</option>
                            <option value="Dermatológico">Dermatológico</option>
                            <option value="Urgencia">Urgencia</option>
                            <option value="Preventivo">Preventivo</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-ia-save">Guardar Conocimiento</button>
            </form>
        </div>
    </div>

    <script>
    function abrirModalIA() {
        document.getElementById('formIA').reset();
        document.getElementById('iaId').value = '';
        document.getElementById('modalIA').classList.add('active');
    }
    function cerrarModalIA() { document.getElementById('modalIA').classList.remove('active'); }
    
    function editarIA(t) {
        abrirModalIA();
        document.getElementById('iaId').value = t.id;
        document.getElementById('iaTema').value = t.tema;
        document.getElementById('iaKeywords').value = t.keywords;
        document.getElementById('iaRespuesta').value = t.respuesta;
        document.getElementById('iaEdadMin').value = t.edad_min;
        document.getElementById('iaEdadMax').value = t.edad_max;
        document.getElementById('iaPesoMin').value = t.peso_min;
        document.getElementById('iaPesoMax').value = t.peso_max;
        document.getElementById('iaRaza').value = t.raza_especifica;
        document.getElementById('iaCat').value = t.categoria_enfermedad;
    }

    document.getElementById('formIA').onsubmit = function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', document.getElementById('iaId').value ? 'edit' : 'create');
        
        fetch('ajax-manage-ia.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if(data.success) location.reload();
            else alert(data.message);
        });
    }

    function borrarIA(id) {
        if(!confirm('¿Seguro que deseas eliminar este conocimiento?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch('ajax-manage-ia.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if(data.success) location.reload();
        });
    }
    </script>
</body>
</html>