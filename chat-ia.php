<?php
/**
 * RUGAL - Chat IA Dedicado
 * Asistente de salud con IA para consultas sobre comportamiento y síntomas
 */

require_once 'db.php';
require_once 'puntos-functions.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);

// Obtener mascota principal
$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ? ORDER BY id LIMIT 1");
$stmt->execute([$userId]);
$pet = $stmt->fetch();

// Obtener historial de IA
$iaHistory = [];
if ($pet) {
    $stmt = $pdo->prepare("SELECT * FROM historial_medico WHERE mascota_id = ? AND (tipo = 'IA' OR tipo = 'comportamiento_ia') ORDER BY fecha DESC, id DESC LIMIT 20");
    $stmt->execute([$pet['id']]);
    $iaHistory = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente IA - RUGAL</title>
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --p-primary: #33a1ff;
            --p-accent: #5fc1ff;
            --p-gradient: linear-gradient(135deg, #33a1ff 0%, #1d91f0 100%);
            --p-glass: rgba(30, 41, 59, 0.7);
            --p-border: rgba(255, 255, 255, 0.1);
            --bg-dark: #0f172a;
        }

        body {
            background-color: var(--bg-dark) !important;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(51, 161, 255, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(51, 161, 255, 0.05) 0%, transparent 40%);
            background-attachment: fixed;
            color: #f1f5f9 !important;
        }

        .chat-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* Standardized Premium Header (Centered) */
        .header {
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 40px 20px;
            gap: 15px;
            background: transparent;
        }

        .header-left {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .page-title {
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 2.5rem !important;
            margin-bottom: 8px !important;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: transform 0.3s ease;
            letter-spacing: -1px;
        }

        .breadcrumb, .breadcrumb span, .breadcrumb i {
            color: rgba(255, 255, 255, 0.5) !important;
            justify-content: center;
            font-size: 14px;
        }

        .chat-header {
            background: var(--p-gradient);
            color: white;
            padding: 40px;
            border-radius: 24px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.3);
            position: relative;
            overflow: hidden;
        }

        .chat-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(255,255,255,0.2) 0%, transparent 80%);
            pointer-events: none;
        }
        
        .chat-box {
            background: var(--p-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--p-border);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        .chat-history {
            max-height: 550px;
            overflow-y: auto;
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding-right: 15px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }
        
        .chat-history::-webkit-scrollbar { width: 6px; }
        .chat-history::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        
        .chat-message {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .chat-message:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .chat-bubble {
            max-width: 90%;
            padding: 16px 20px;
            border-radius: 18px;
            font-size: 15px;
            line-height: 1.6;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .chat-bubble.user {
            align-self: flex-end;
            background: var(--p-gradient);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
        }
        
        .chat-bubble.bot {
            align-self: flex-start;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            border-bottom-left-radius: 4px;
        }
        
        .chat-timestamp {
            font-size: 12px;
            color: #64748b;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .chat-input-container {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            background: rgba(255, 255, 255, 0.03);
            padding: 8px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .chat-input {
            flex: 1;
            padding: 16px 20px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(15, 23, 42, 0.8);
            color: white;
            font-size: 15px;
            transition: all 0.3s;
        }

        .chat-input:focus {
            outline: none;
            border-color: var(--p-accent);
            background: rgba(15, 23, 42, 1);
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.2);
        }
        
        .chat-send-btn {
            padding: 0 28px;
            background: var(--p-gradient);
            border: none;
            border-radius: 14px;
            color: white;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }
        
        .chat-send-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }
        
        .chat-send-btn:disabled {
            opacity: 0.5;
            transform: none;
        }
        
        .delete-btn {
            position: absolute;
            right: 15px;
            top: 15px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 10;
        }
        
        .delete-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: scale(1.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0.5;
        }

        .info-disclaimer {
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.2);
            padding: 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .card-premium-action {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);
        }

        .btn-premium-white {
            background: white;
            color: #059669;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .btn-premium-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">🤖 Asistente de Salud IA</h1>
                <div class="breadcrumb">
                    <span>Salud</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Asistente IA</span>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="chat-container">
                <div class="chat-header">
                    <i class="fas fa-robot" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h2 style="margin: 0 0 10px 0;">Consulta de Salud con IA</h2>
                    <p style="margin: 0; opacity: 0.9; font-size: 14px;">
                        Describe cualquier síntoma o comportamiento de <?php echo $pet ? htmlspecialchars($pet['nombre']) : 'tu mascota'; ?> 
                        para recibir consejos orientativos
                    </p>
                </div>
                
                <?php if (!$pet): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-paw"></i>
                        <h3>No tienes mascotas registradas</h3>
                        <p>Agrega una mascota para usar el asistente de salud IA</p>
                        <button class="btn-add" onclick="window.location.href='agregar-mascota.php'" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Agregar Mascota
                        </button>
                    </div>
                </div>
                <?php else: ?>
                
                <div class="chat-box">
                    <h3 style="margin: 0 0 20px 0; color: var(--texto-principal, #FFFFFF);">
                        <i class="fas fa-comments"></i> Conversación
                    </h3>
                    
                    <div class="chat-history" id="chatHistory">
                        <?php if (empty($iaHistory)): ?>
                        <div class="empty-state" style="padding: 40px 20px;">
                            <i class="fas fa-comment-dots"></i>
                            <p>No hay conversaciones aún. ¡Haz tu primera consulta!</p>
                        </div>
                        <?php else: ?>
                            <?php foreach (array_reverse($iaHistory) as $entry): ?>
                            <div class="chat-message" id="message-<?php echo $entry['id']; ?>">
                                <button class="delete-btn" onclick="eliminarMensaje(<?php echo $entry['id']; ?>)" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                                
                                <div class="chat-bubble user">
                                    <?php 
                                    $text = $entry['diagnostico'];
                                    $text = str_replace(['El cliente preguntó: "', '"'], '', $text);
                                    if (preg_match('/el usuario diagnostico (.*) del perro la fecha .*/i', $text, $matches)) {
                                        $text = $matches[1];
                                    }
                                    echo htmlspecialchars($text); 
                                    ?>
                                </div>
                                
                                <div class="chat-bubble bot">
                                    <?php echo nl2br(htmlspecialchars(str_replace('Respuesta de la IA: ', '', $entry['tratamiento']))); ?>
                                    <div class="chat-timestamp">
                                        <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($entry['created_at'] ?? $entry['fecha'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div style="border-top: 1px solid var(--p-border); padding-top: 25px;">
                        <div class="info-disclaimer">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Nota:</strong> Esta información es orientativa y no reemplaza la consulta veterinaria. 
                            Las conversaciones se guardan automáticamente en el historial médico.
                        </div>
                        
                        <div class="chat-input-container">
                            <input 
                                type="text" 
                                id="chatInput" 
                                class="chat-input" 
                                placeholder="Ej: No quiere comer o está cojeando..."
                                onkeydown="if(event.key==='Enter') enviarMensaje()"
                            >
                            <button class="chat-send-btn" id="sendBtn" onclick="enviarMensaje()">
                                <i class="fas fa-paper-plane"></i> Enviar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-premium-action">
                    <div style="text-align: center;">
                        <i class="fas fa-notes-medical" style="font-size: 40px; margin-bottom: 15px; color: white;"></i>
                        <h4 style="margin: 0 0 10px 0; font-size: 20px; font-weight: 800; color: white;">Historial Médico Completo</h4>
                        <p style="margin: 0 0 20px 0; opacity: 0.9; font-size: 15px; color: white;">
                            Todas tus consultas con la IA se guardan en el historial médico de <?php echo htmlspecialchars($pet['nombre']); ?>
                        </p>
                        <a href="historial.php" class="btn-premium-white">
                            <i class="fas fa-arrow-right"></i> Ver Historial Completo
                        </a>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function eliminarMensaje(id) {
            if (!confirm('¿Estás seguro de eliminar esta conversación?')) return;
            
            fetch('ajax-delete-ia-history.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = document.getElementById('message-' + id);
                    if (el) {
                        el.style.transition = 'opacity 0.3s';
                        el.style.opacity = '0';
                        setTimeout(() => el.remove(), 300);
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
        
        function enviarMensaje() {
            const input = document.getElementById('chatInput');
            const sendBtn = document.getElementById('sendBtn');
            const chatHistory = document.getElementById('chatHistory');
            const behavior = input.value.trim();
            
            if (!behavior) return;
            
            // Deshabilitar input
            input.disabled = true;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            
            // Agregar mensaje del usuario inmediatamente
            const userBubble = document.createElement('div');
            userBubble.className = 'chat-message';
            userBubble.innerHTML = `
                <div class="chat-bubble user">${behavior}</div>
            `;
            chatHistory.appendChild(userBubble);
            chatHistory.scrollTop = chatHistory.scrollHeight;
            
            // Limpiar input
            input.value = '';
            
            // Enviar a la IA
            fetch('ajax-analyze-behavior.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `behavior=${encodeURIComponent(behavior)}&mascota_id=<?php echo $pet['id']; ?>&pet_name=<?php echo htmlspecialchars($pet['nombre']); ?>`
            })
            .then(res => res.json())
            .then(data => {
                input.disabled = false;
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
                
                if (data.success) {
                    // Agregar respuesta de la IA
                    const botBubble = document.createElement('div');
                    const now = new Date();
                    const timestamp = now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
                    
                    let responseText = data.response.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    
                    botBubble.className = 'chat-bubble bot';
                    botBubble.innerHTML = `
                        ${responseText}
                        <div class="chat-timestamp">
                            <i class="fas fa-clock"></i> ${timestamp}
                        </div>
                    `;
                    userBubble.appendChild(botBubble);
                    chatHistory.scrollTop = chatHistory.scrollHeight;
                    
                    // Recargar después de 2 segundos para obtener el ID del mensaje
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert('Error: ' + data.error);
                    userBubble.remove();
                }
            })
            .catch(error => {
                input.disabled = false;
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
                alert('Error al enviar el mensaje: ' + error.message);
                userBubble.remove();
            });
        }
        
        // Auto-scroll al final al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const chatHistory = document.getElementById('chatHistory');
            if (chatHistory) {
                chatHistory.scrollTop = chatHistory.scrollHeight;
            }
        });
    </script>
</body>
</html>
