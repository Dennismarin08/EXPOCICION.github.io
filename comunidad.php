<?php
require_once 'db.php';
require_once 'puntos-functions.php';
require_once 'includes/comunidad_functions.php';
require_once 'premium-functions.php';

// Auth
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);

// Obtener mascotas del usuario para el tag
$stmt = $pdo->prepare("SELECT id, nombre FROM mascotas WHERE user_id = ?");
$stmt->execute([$userId]);
$userPets = $stmt->fetchAll();

// Obtener Feed (todas las publicaciones)
$stmt = $pdo->query("
    SELECT p.*, u.nombre as autor_nombre, u.foto_perfil as autor_foto, m.nombre as mascota_nombre, m.foto_perfil as mascota_foto
    FROM publicaciones p
    JOIN usuarios u ON p.user_id = u.id
    LEFT JOIN mascotas m ON p.mascota_id = m.id
    ORDER BY p.created_at DESC
    LIMIT 50
");
$feedPosts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidad - RUGAL</title>
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .community-container {
            max-width: 680px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 80px;
        }

        /* Post Creator */
        .post-creator {
            background: var(--p-bg-card);
            border: 1px solid var(--p-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
        }

        .post-input {
            width: 100%;
            background: var(--p-bg-main);
            border: 1px solid var(--p-border);
            border-radius: 12px;
            padding: 15px;
            color: var(--p-text-main);
            font-family: inherit;
            resize: none;
            margin-bottom: 15px;
        }

        .creator-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .media-btn {
            background: var(--p-bg-main);
            color: var(--p-text-muted);
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            border: 1px solid var(--p-border);
        }

        .btn-post {
            background: var(--p-gradient);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }

        /* Feed */
        .post-card {
            background: var(--p-bg-card);
            border: 1px solid var(--p-border);
            border-radius: 20px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .post-header {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--p-primary);
        }

        .post-meta { flex: 1; }
        .post-author { font-weight: 700; font-size: 15px; color: var(--p-text-main); }
        .post-time { font-size: 12px; color: var(--p-text-muted); }

        .pet-tag {
            background: rgba(var(--p-primary-rgb), 0.1);
            color: var(--p-primary);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            border: 1px solid var(--p-border);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .pet-tag:hover { transform: scale(1.05); }

        .pet-tag-img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--p-primary);
        }

        .post-content { padding: 0 20px 20px 20px; font-size: 15px; line-height: 1.6; color: var(--p-text-main); word-break: break-word; }
        .post-media { width: 100%; max-height: 500px; object-fit: cover; }

        .post-actions {
            padding: 12px 20px;
            border-top: 1px solid var(--p-border);
            display: flex;
            gap: 20px;
        }

        .action-btn { color: var(--p-text-muted); cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .action-btn:hover { color: var(--p-primary); }
        .action-btn.liked { color: #ef4444; }

        .post-media-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 15px; }

        @media (max-width: 768px) {
            .community-container { padding: 15px; }
            .post-creator { padding: 18px; }
            .creator-footer { flex-direction: column; gap: 12px; }
            .btn-post { width: 100%; }
            .post-header { padding: 12px 15px; flex-wrap: wrap; }
            .post-content { padding: 0 15px 15px; font-size: 14px; }
            .post-actions { padding: 10px 15px; flex-wrap: wrap; gap: 12px; }
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Hamburger button and overlay logic are managed by sidebar.php -->

    <div class="main-content">
        <!-- Header minimalista para la página en el dashboard -->
        <header class="header">
            <h1 class="page-title">🌟 Comunidad RUGAL</h1>
            <div style="background: var(--p-bg-card); padding: 8px 15px; border-radius: 20px; border: 1px solid var(--p-border); color: var(--p-text-main); font-weight: 600; box-shadow: var(--shadow-sm);">
                🌟 <?php echo number_format($user['puntos']); ?> pts
            </div>
        </header>

        <div class="community-container">
            <!-- Creator -->
            <div class="post-creator">
                <form id="formPost" enctype="multipart/form-data">
                    <textarea name="contenido" class="post-input" placeholder="¿Qué está haciendo tu mascota hoy?..." rows="3" required></textarea>
                    
                    <div class="post-media-actions">
                        <input type="file" name="media" id="mediaInput" hidden accept="image/*,video/*">
                        <label for="mediaInput" class="media-btn"><i class="fas fa-image"></i> Foto/Video</label>
                        
                        <select name="mascota_id" style="background: rgba(0,0,0,0.3); color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 5px 10px; font-size: 13px; max-width: 100%;">
                            <option value="">¿Asociar a una mascota?</option>
                            <?php foreach ($userPets as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="creator-footer">
                        <div id="previewName" style="font-size: 12px; opacity: 0.7;"></div>
                        <button type="submit" class="btn-post" id="btnPost">Publicar</button>
                    </div>
                </form>
            </div>

            <!-- Feed -->
            <div id="mainFeed">
                <?php foreach ($feedPosts as $post): 
                    $isLiked = usuarioDioLike($userId, $post['id']);
                    $comments = obtenerComentarios($post['id']);
                ?>
                    <div class="post-card" id="post-<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <?php 
                            $autorSrc = 'default-user.png';
                            if ($post['autor_foto']) {
                                $autorSrc = $post['autor_foto'];
                            }
                            ?>
                            <img src="uploads/<?php echo $autorSrc; ?>" class="author-avatar">
                            <div class="post-meta">
                                <div class="post-author"><?php echo htmlspecialchars($post['autor_nombre']); ?></div>
                                <div class="post-time"><?php echo date('d M, H:i', strtotime($post['created_at'])); ?></div>
                            </div>
                            
                            <?php if ($post['mascota_id']): ?>
                                <a href="perfil-mascota.php?id=<?php echo $post['mascota_id']; ?>" class="pet-tag">
                                    <img src="uploads/<?php echo $post['mascota_foto'] ?: 'default-pet.png'; ?>" class="pet-tag-img">
                                    <i class="fas fa-paw"></i> <?php echo htmlspecialchars($post['mascota_nombre']); ?>
                                </a>
                            <?php endif; ?>

                            <?php if (isAdmin($userId) || $post['user_id'] == $userId): ?>
                                <button onclick="deletePost(<?php echo $post['id']; ?>)" style="background:none; border:none; color:#ef4444; cursor:pointer; opacity:0.6;" title="Eliminar publicación">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['contenido'])); ?>
                        </div>

                        <?php if ($post['media_url']): ?>
                            <?php 
                            $mUrl = (strpos($post['media_url'], 'http') !== 0) ? 'uploads/'.$post['media_url'] : $post['media_url'];
                            if ($post['media_type'] === 'video'): ?>
                                <video src="<?php echo $mUrl; ?>" controls class="post-media"></video>
                            <?php else: ?>
                                <img src="<?php echo $mUrl; ?>" class="post-media" loading="lazy">
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="post-actions">
                            <div class="action-btn <?php echo $isLiked ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                                <i class="<?php echo $isLiked ? 'fas' : 'far'; ?> fa-heart"></i> 
                                <span class="like-count"><?php echo $post['likes']; ?></span>
                            </div>
                            <div class="action-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                                <i class="far fa-comment"></i> <span id="comment-count-<?php echo $post['id']; ?>"><?php echo count($comments); ?></span> Comentarios
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display:none; padding: 15px 20px; background: rgba(0,0,0,0.1); border-top: 1px solid rgba(255,255,255,0.05);">
                            <div class="comments-list" id="list-<?php echo $post['id']; ?>">
                                <?php foreach ($comments as $comment): ?>
                                    <div style="display:flex; gap:10px; margin-bottom:12px; font-size:13px;">
                                        <img src="uploads/<?php echo $comment['autor_foto'] ?: 'default-user.png'; ?>" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                                        <div style="flex:1;">
                                            <div style="background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:12px;">
                                                <strong><?php echo htmlspecialchars($comment['autor_nombre']); ?></strong>
                                                <p style="margin:2px 0 0 0; opacity:0.9;"><?php echo htmlspecialchars($comment['contenido']); ?></p>
                                            </div>
                                            <span style="font-size:10px; opacity:0.5; margin-left:5px;"><?php echo date('d/m H:i', strtotime($comment['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="display:flex; gap:10px; margin-top:15px;">
                                <input type="text" placeholder="Escribe un comentario..." class="post-input" style="margin-bottom:0; flex:1; padding:8px 15px;" id="input-<?php echo $post['id']; ?>" onkeypress="if(event.key==='Enter') addComment(<?php echo $post['id']; ?>)">
                                <button class="btn-post" style="padding:8px 15px;" onclick="addComment(<?php echo $post['id']; ?>)"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('mediaInput').addEventListener('change', function(e) {
            document.getElementById('previewName').textContent = e.target.files[0] ? 'Fichero: ' + e.target.files[0].name : '';
        });

        document.getElementById('formPost').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnPost');
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            const formData = new FormData(this);
            try {
                const response = await fetch('ajax-create-post.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    location.reload();
                } else {
                    alert(res.message || 'Error al publicar');
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexión');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Publicar';
            }
        });

        async function toggleLike(postId, btn) {
            try {
                const response = await fetch('ajax-toggle-like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId })
                });
                const res = await response.json();
                if (res.success) {
                    const icon = btn.querySelector('i');
                    const count = btn.querySelector('.like-count');
                    count.textContent = res.likes;
                    
                    if (res.action === 'liked') {
                        btn.classList.add('liked');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        btn.classList.remove('liked');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                }
            } catch (error) {
                console.error('Like error:', error);
            }
        }

        function toggleComments(postId) {
            const section = document.getElementById('comments-' + postId);
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        }

        async function addComment(postId) {
            const input = document.getElementById('input-' + postId);
            const content = input.value.trim();
            if (!content) return;

            input.disabled = true;
            try {
                const response = await fetch('ajax-add-comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId, content: content })
                });
                const res = await response.json();
                if (res.success) {
                    input.value = '';
                    const list = document.getElementById('list-' + postId);
                    
                    // Asegurar que la sección de comentarios esté visible
                    const section = document.getElementById('comments-' + postId);
                    section.style.display = 'block';

                    // Actualizar contador visual de comentarios
                    const countSpan = document.getElementById('comment-count-' + postId);
                    if (countSpan) {
                        countSpan.textContent = parseInt(countSpan.textContent) || 0;
                        countSpan.textContent = parseInt(countSpan.textContent) + 1;
                    }

                    // Escapar caracteres para prevenir inyección de HTML
                    const safeContent = content.replace(/</g, "&lt;").replace(/>/g, "&gt;");

                    const html = `
                        <div style="display:flex; gap:10px; margin-bottom:12px; font-size:13px; animation: fadeIn 0.3s;">
                            <img src="uploads/${res.comment.photo || 'default-user.png'}" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                            <div style="flex:1;">
                                <div style="background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:12px;">
                                    <strong>${res.comment.author}</strong>
                                    <p style="margin:2px 0 0 0; opacity:0.9;">${safeContent}</p>
                                </div>
                                <span style="font-size:10px; opacity:0.5; margin-left:5px;">Ahora mismo</span>
                            </div>
                        </div>
                    `;
                    list.insertAdjacentHTML('beforeend', html);
                } else {
                    alert(res.message);
                }
            } catch (error) {
                console.error('Comment error:', error);
            } finally {
                input.disabled = false;
                input.focus();
            }
        }

        async function deletePost(postId) {
            if (!confirm('¿Estás seguro de que deseas eliminar esta publicación?')) return;

            try {
                const response = await fetch('ajax-delete-post.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: postId })
                });
                const res = await response.json();
                if (res.success) {
                    const card = document.getElementById('post-' + postId);
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => card.remove(), 300);
                } else {
                    alert(res.message);
                }
            } catch (error) {
                alert('Error al conectar con el servidor');
            }
        }
    </script>
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</body>
</html>
