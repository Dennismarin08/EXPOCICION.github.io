<?php
require_once 'db.php';
require_once 'includes/check-auth.php';

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);

// Filters
$categoria = $_GET['cat'] ?? 'todos';
$tipo = $_GET['tipo'] ?? 'todos';

$sql = "SELECT * FROM contenido_educativo WHERE 1=1";
$params = [];

if ($categoria !== 'todos') {
    $sql .= " AND categoria = ?";
    $params[] = $categoria;
}

if ($tipo !== 'todos') {
    $sql .= " AND tipo = ?";
    $params[] = $tipo;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contenidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papas Primerizos - RUGAL</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edu-container { max-width: 1200px; margin: 0 auto; padding: 30px; }
        
        .edu-hero {
            background: var(--p-gradient);
            border-radius: 24px;
            padding: 40px;
            color: white;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
        }
        
        .edu-hero-content { position: relative; z-index: 2; max-width: 600px; }
        .edu-hero h1 { font-size: 36px; font-weight: 800; margin-bottom: 15px; }
        .edu-hero p { font-size: 18px; opacity: 0.9; line-height: 1.6; }
        .edu-hero i.bg-icon {
            position: absolute; right: -20px; bottom: -20px;
            font-size: 200px; opacity: 0.1; transform: rotate(-15deg);
        }

        .filter-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; flex-wrap: wrap; gap: 20px;
        }

        .tabs { display: flex; gap: 10px; }
        .tab-btn {
            padding: 10px 20px; border-radius: 12px; border: 1px solid var(--p-border);
            background: white; color: var(--p-text-muted); font-weight: 600;
            text-decoration: none; transition: all 0.3s;
        }
        .tab-btn.active {
            background: var(--p-primary); color: white; border-color: var(--p-primary);
        }

        .type-pills { display: flex; gap: 10px; }
        .pill {
            padding: 6px 15px; border-radius: 50px; font-size: 13px; font-weight: 700;
            background: #e2e8f0; color: #475569; text-decoration: none; transition: all 0.2s;
        }
        .pill.active { background: var(--p-accent); color: white; }

        .edu-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px;
        }

        .edu-card {
            background: white; border-radius: 20px; overflow: hidden;
            box-shadow: var(--shadow-md); border: 1px solid var(--p-border);
            transition: transform 0.3s;
        }
        .edu-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }

        .edu-media { height: 200px; width: 100%; position: relative; }
        .edu-media img, .edu-media video { width: 100%; height: 100%; object-fit: cover; }
        .edu-media .play-overlay {
            position: absolute; top:0; left:0; width:100%; height:100%;
            display:flex; align-items:center; justify-content:center;
            background: rgba(0,0,0,0.3); color: white; font-size: 40px; pointer-events: none;
        }

        .edu-content { padding: 20px; }
        .edu-cat {
            font-size: 11px; font-weight: 800; text-transform: uppercase;
            color: var(--p-primary); margin-bottom: 8px; display: block;
        }
        .edu-title { font-size: 18px; font-weight: 700; color: var(--p-text-main); margin-bottom: 10px; }
        .edu-desc { font-size: 14px; color: var(--p-text-muted); line-height: 1.5; margin-bottom: 20px; }

        .btn-read {
            display: flex; align-items: center; gap: 8px; color: var(--p-primary);
            font-weight: 700; text-decoration: none; font-size: 14px;
        }
        .btn-read:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .edu-container { padding: 15px; }
            .edu-hero {
                flex-direction: column;
                padding: 28px 20px;
                text-align: center;
            }
            .edu-hero-content { max-width: 100%; }
            .edu-hero h1 { font-size: 28px; }
            .edu-hero p { font-size: 16px; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .tabs { flex-wrap: wrap; justify-content: center; }
            .type-pills { flex-wrap: wrap; justify-content: center; }
            .edu-grid { grid-template-columns: 1fr; gap: 20px; }
        }
        @media (max-width: 480px) {
            .edu-hero h1 { font-size: 22px; }
            .tab-btn { padding: 8px 14px; font-size: 13px; }
        }

        /* Modal Detail */
        .modal-detail { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center; padding: 20px; }
        .modal-detail-content { background: white; border-radius: 24px; max-width: 900px; width: 100%; max-height: 90vh; overflow-y: auto; display: flex; flex-direction: column; }
        .modal-body { padding: 30px; display: flex; gap: 30px; }
        .modal-media { flex: 1; border-radius: 15px; overflow: hidden; background: #000; }
        .modal-info { flex: 1; }
        .modal-close { position: absolute; top: 20px; right: 20px; color: white; font-size: 30px; cursor: pointer; }
        .paso-item { background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 10px; border-left: 4px solid var(--p-primary); }

        @media (max-width: 800px) {
            .modal-body { flex-direction: column; }
        }

        /* Visibility Fixes */
        .page-title { color: var(--p-text-main) !important; }
        .breadcrumb { color: var(--p-text-muted) !important; }
        .edu-hero { text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .edu-hero p { color: #ffffff !important; opacity: 1 !important; }
        .edu-title { color: var(--p-text-main) !important; }
    </style>
</head>
<?php 
// Convertir rol a clase de tema
$roleTheme = 'theme-usuario';
if ($user['rol'] === 'veterinaria') $roleTheme = 'theme-vet';
elseif ($user['rol'] === 'tienda') $roleTheme = 'theme-tienda';
?>
<body class="<?php echo $roleTheme; ?>">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Papas Primerizos</h1>
            <div class="breadcrumb">
                <span>Comunidad</span> <i class="fas fa-chevron-right"></i> <span>Educación</span>
            </div>
        </header>

        <div class="edu-container">
            <div class="edu-hero">
                <div class="edu-hero-content">
                    <h1>¡Bienvenido a la aventura! 🐾</h1>
                    <p>Todo lo que necesitas saber para darle la mejor vida a tu nuevo mejor amigo. Guías, tutoriales y consejos de expertos.</p>
                </div>
                <i class="fas fa-graduation-cap bg-icon"></i>
            </div>

            <div class="filter-bar">
                <div class="tabs">
                    <a href="?cat=todos&tipo=<?php echo $tipo; ?>" class="tab-btn <?php echo $categoria == 'todos' ? 'active' : ''; ?>">Todo</a>
                    <a href="?cat=educacion&tipo=<?php echo $tipo; ?>" class="tab-btn <?php echo $categoria == 'educacion' ? 'active' : ''; ?>">Educación</a>
                    <a href="?cat=alimentacion&tipo=<?php echo $tipo; ?>" class="tab-btn <?php echo $categoria == 'alimentacion' ? 'active' : ''; ?>">Alimentación</a>
                    <a href="?cat=juegos&tipo=<?php echo $tipo; ?>" class="tab-btn <?php echo $categoria == 'juegos' ? 'active' : ''; ?>">Juegos</a>
                    <a href="?cat=limpieza&tipo=<?php echo $tipo; ?>" class="tab-btn <?php echo $categoria == 'limpieza' ? 'active' : ''; ?>">Limpieza</a>
                </div>

                <div class="type-pills">
                    <a href="?tipo=todos&cat=<?php echo $categoria; ?>" class="pill <?php echo $tipo == 'todos' ? 'active' : ''; ?>">Todos</a>
                    <a href="?tipo=foto&cat=<?php echo $categoria; ?>" class="pill <?php echo $tipo == 'foto' ? 'active' : ''; ?>"><i class="fas fa-image"></i> Fotos</a>
                    <a href="?tipo=video&cat=<?php echo $categoria; ?>" class="pill <?php echo $tipo == 'video' ? 'active' : ''; ?>"><i class="fas fa-video"></i> Videos</a>
                </div>
            </div>

            <div class="edu-grid">
                <?php foreach ($contenidos as $item): ?>
                    <div class="edu-card">
                        <div class="edu-media">
                            <?php if ($item['tipo'] == 'video'): ?>
                                <video src="<?php echo $item['media_url']; ?>"></video>
                                <div class="play-overlay"><i class="fas fa-play-circle"></i></div>
                            <?php else: ?>
                                <img src="<?php echo $item['media_url']; ?>" alt="<?php echo htmlspecialchars($item['titulo']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="edu-content">
                            <span class="edu-cat"><?php echo ucfirst($item['categoria']); ?></span>
                            <h3 class="edu-title"><?php echo htmlspecialchars($item['titulo']); ?></h3>
                            <p class="edu-desc"><?php echo htmlspecialchars($item['descripcion']); ?></p>
                            <a href="javascript:void(0)" class="btn-read" onclick='showDetail(<?php echo json_encode($item); ?>)'>Ver contenido completo <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($contenidos)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: var(--p-text-muted);">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                        <p>No se encontró contenido para estos filtros.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Detalle -->
    <div id="modalDetail" class="modal-detail" onclick="if(event.target == this) closeDetail()">
        <span class="modal-close" onclick="closeDetail()">&times;</span>
        <div class="modal-detail-content">
            <div class="modal-body">
                <div class="modal-media" id="detailMedia"></div>
                <div class="modal-info">
                    <span class="edu-cat" id="detailCat"></span>
                    <h2 class="edu-title" id="detailTitle" style="font-size: 28px;"></h2>
                    <p class="edu-desc" id="detailDesc" style="font-size: 16px; margin-bottom: 25px;"></p>
                    
                    <div id="detailNeeds" style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 10px; color: var(--p-primary);"><i class="fas fa-shopping-basket"></i> ¿Qué se necesita?</h4>
                        <div id="needsList" style="background: #f1f5f9; padding: 15px; border-radius: 12px; font-size: 14px; line-height: 1.6;"></div>
                    </div>

                    <div id="detailSteps">
                        <h4 style="margin-bottom: 15px;"><i class="fas fa-list-ol"></i> Paso a Paso</h4>
                        <div id="stepsList"></div>
                    </div>

                    <hr style="margin: 30px 0; border: none; border-top: 1px solid #e2e8f0;">

                    <div class="comments-section">
                        <h4><i class="fas fa-comments"></i> Comentarios y Reseñas</h4>
                        <div id="eduCommentsList" style="margin-top: 20px; max-height: 300px; overflow-y: auto;"></div>
                        
                        <form id="commentForm" style="margin-top: 20px;">
                            <input type="hidden" id="contentId" name="content_id">
                            <div style="display: flex; gap: 10px;">
                                <textarea name="comentario" class="form-control" placeholder="Escribe tu reseña..." required style="flex: 1;"></textarea>
                                <button type="submit" class="btn-read" style="border: none; border-radius: 8px; cursor: pointer; padding: 0 20px;">Enviar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showDetail(item) {
            document.getElementById('detailTitle').textContent = item.titulo;
            document.getElementById('detailDesc').textContent = item.descripcion;
            document.getElementById('detailCat').textContent = item.categoria.toUpperCase();
            document.getElementById('contentId').value = item.id;
            
            const mediaCont = document.getElementById('detailMedia');
            if (item.tipo === 'video') {
                mediaCont.innerHTML = `<video src="${item.media_url}" controls style="width:100%; height:100%; object-fit:contain;" autoplay></video>`;
            } else {
                mediaCont.innerHTML = `<img src="${item.media_url}" style="width:100%; height:100%; object-fit:contain;">`;
            }

            // Needs List
            const needsList = document.getElementById('needsList');
            if (item.lista_necesidades) {
                needsList.textContent = item.lista_necesidades;
                document.getElementById('detailNeeds').style.display = 'block';
            } else {
                document.getElementById('detailNeeds').style.display = 'none';
            }

            const stepsList = document.getElementById('stepsList');
            stepsList.innerHTML = '';
            if (item.paso_a_paso) {
                const steps = item.paso_a_paso.split('\n');
                steps.forEach(step => {
                    if (step.trim()) {
                        const div = document.createElement('div');
                        div.className = 'paso-item';
                        div.textContent = step;
                        stepsList.appendChild(div);
                    }
                });
                document.getElementById('detailSteps').style.display = 'block';
            } else {
                document.getElementById('detailSteps').style.display = 'none';
            }

            loadComments(item.id);

            document.getElementById('modalDetail').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        async function loadComments(contentId) {
            const list = document.getElementById('eduCommentsList');
            list.innerHTML = '<p style="font-size: 12px; color: #64748b;">Cargando comentarios...</p>';
            try {
                const response = await fetch('ajax-comentarios-educacion.php?id=' + contentId);
                const comments = await response.json();
                list.innerHTML = '';
                if (comments.length === 0) {
                    list.innerHTML = '<p style="font-size: 12px; color: #64748b; font-style: italic;">Sé el primero en comentar.</p>';
                    return;
                }
                comments.forEach(c => {
                    const div = document.createElement('div');
                    div.style.cssText = 'padding: 10px 0; border-bottom: 1px solid #f1f5f9;';
                    div.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <strong style="font-size: 13px;">${c.autor_nombre}</strong>
                            <span style="font-size: 11px; color: #94a3b8;">${new Date(c.created_at).toLocaleDateString()}</span>
                        </div>
                        <p style="font-size: 13px; color: #475569; margin: 0;">${c.comentario}</p>
                    `;
                    list.appendChild(div);
                });
            } catch (err) { 
                console.error('Error al cargar comentarios:', err);
                list.innerHTML = 'Error al cargar comentarios'; 
            }
        }

        document.getElementById('commentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('ajax-comentarios-educacion.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    this.reset();
                    loadComments(document.getElementById('contentId').value);
                } else {
                    alert('Error: ' + res.message);
                }
            } catch (err) { 
                console.error('Error al enviar comentario:', err);
                alert('Error de conexión al enviar el comentario.'); 
            }
        });

        function closeDetail() {
            document.getElementById('modalDetail').style.display = 'none';
            document.getElementById('detailMedia').innerHTML = '';
            document.body.style.overflow = '';
        }
    </script>
</body>
</html>
