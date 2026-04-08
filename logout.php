<?php
require_once 'db.php';
// db.php ya habrá iniciado la sesión correctamente

// Si el usuario tenía sesión, borrar su token en la BD
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // Ignorar error silenciosamente
    }
}

// Borrar la cookie de 'Recordarme' correctamente
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    unset($_COOKIE['remember_token']);
}

// Limpiar y destruir la sesión
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: login.php');
exit;
?>