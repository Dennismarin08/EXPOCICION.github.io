<?php
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$admin = getUsuario($userId);

// Fetch existing content
$stmt = $pdo->query("SELECT * FROM contenido_educativo ORDER BY created_at DESC");
$contents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Educación - Admin RUGAL</title>
    <link rel="stylesheet" href="../dashboard.css">
    <link rel="stylesheet" href="../css/themes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .btn-new { background: var(--p-gradient); color: white; padding: 12px 25px; border-radius: 12px; font-weight: bold; border: none; cursor: pointer; margin-bottom: 25px; }
        .edu-table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; box-shadow: var(--shadow-md); }
        .edu-table th { background: #f8fafc; padding: 15px; text-align: left; font-size: 13px; color: #64748b; border-bottom: 1px solid #e2e8f0; }
        .edu-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .badge { padding: 5px 10px; border-radius: 50px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-video { background: #e0f2fe; color: #0369a1; }
        .badge-foto { background: #fef3c7; color: #92400e; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 20px; width: 500px; max-width: 90%; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; color: #64748b; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body class="theme-usuario">
    <?php include '../includes/sidebar-admin.php'; ?>
    
    <div class="main-content" style="margin-left: 260px;">
        <header class="header">
            <h1 class="page-title">Administrar Educación</h1>
            <div class="breadcrumb">Panel Admin <i class="fas fa-chevron-right"></i> Papas Primerizos</div>
        </header>

        <div class="admin-container">
            <button class="btn-new" onclick="openModal()"><i class="fas fa-plus"></i> Nuevo Contenido</button>

            <table class="edu-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Imagen/Video</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contents as $c): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($c['titulo']); ?></strong></td>
                        <td><?php echo ucfirst($c['categoria']); ?></td>
                        <td><span class="badge badge-<?php echo $c['tipo']; ?>"><?php echo $c['tipo']; ?></span></td>
                        <td><a href="../<?php echo $c['media_url']; ?>" target="_blank" style="color: var(--p-primary);"><i class="fas fa-external-link-alt"></i> Ver</a></td>
                        <td>
                            <button onclick="deleteContent(<?php echo $c['id']; ?>)" style="color: #ef4444; background: none; border: none; cursor: pointer;"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <div id="eduModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 20px;">Nuevo Contenido Educativo</h2>
            <form id="eduForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="titulo" class="form-control" required placeholder="Ej: Cómo bañar a tu perro">
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Categoría</label>
                    <select name="categoria" class="form-control">
                        <option value="educacion">Educación</option>
                        <option value="alimentacion">Alimentación</option>
                        <option value="juegos">Juegos</option>
                        <option value="limpieza">Limpieza</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>¿Qué se necesita? (Lista)</label>
                    <textarea name="lista_necesidades" class="form-control" rows="3" placeholder="Ej: Jabón neutro, Toalla, Cepillo..."></textarea>
                </div>
                <div class="form-group">
                    <label>Paso a Paso (Detalles)</label>
                    <textarea name="paso_a_paso" class="form-control" rows="5" placeholder="Escribe aquí las instrucciones detalladas..."></textarea>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" class="form-control">
                        <option value="foto">Foto</option>
                        <option value="video">Video</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Archivo (Subir)</label>
                    <input type="file" name="media" class="form-control" accept="image/*,video/*" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" onclick="closeModal()" style="padding: 10px 20px; border-radius: 8px;">Cancelar</button>
                    <button type="submit" class="btn-new" style="margin-bottom: 0;">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('eduModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('eduModal').style.display = 'none'; }

        document.getElementById('eduForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create');

            try {
                const response = await fetch('../ajax-manage-education.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            } catch (err) { 
                console.error('Error:', err);
                alert('Error de conexión al guardar. Revisa la consola para más detalles.'); 
            }
        });

        async function deleteContent(id) {
            if (!confirm('¿Seguro que deseas eliminar este contenido?')) return;
            try {
                const response = await fetch('../ajax-manage-education.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                });
                const res = await response.json();
                if (res.success) location.reload();
                else alert(res.message);
            } catch (err) { 
                console.error('Error:', err);
                alert('Error de conexión al eliminar.'); 
            }
        }
    </script>
</body>
</html>
