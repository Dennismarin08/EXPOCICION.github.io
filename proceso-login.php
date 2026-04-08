<?php
require_once 'db.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

try {
    // Verificar si es una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Validar campos
    if (!isset($_POST['email']) || empty(trim($_POST['email']))) {
        throw new Exception('El email es requerido');
    }
    
    if (!isset($_POST['password']) || empty(trim($_POST['password']))) {
        throw new Exception('La contraseña es requerida');
    }
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Buscar usuario con su rol
    $stmt = $pdo->prepare("
        SELECT u.*, m.id as mascota_id 
        FROM usuarios u 
        LEFT JOIN mascotas m ON u.id = m.user_id 
        WHERE u.email = ?
        GROUP BY u.id
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Verificar si el usuario existe
    if (!$user) {
        throw new Exception('Credenciales incorrectas');
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Credenciales incorrectas');
    }
    
    // Actualizar último login
    try {
        $stmtUp = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
        $stmtUp->execute([$user['id']]);
    } catch (Exception $e) { /* ignore */ }

    // Iniciar sesión con todos los datos
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['nombre'];
    $_SESSION['user_rol'] = $user['rol']; // ← NUEVO: Rol en sesión
    $_SESSION['premium'] = $user['premium'] ?? 0;
    $_SESSION['pet_id'] = $user['mascota_id'] ?? null;
    
    // Si marcó "Recordarme"
    if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
        $token = bin2hex(random_bytes(32));
        $updateStmt = $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id = ?");
        $updateStmt->execute([$token, $user['id']]);
        
        // Cookie por 30 días con parámetros seguros y consistentes
        $cookieLifetime = time() + (86400 * 30);
        setcookie('remember_token', $token, [
            'expires'  => $cookieLifetime,
            'path'     => '/',
            'secure'   => false, // Poner true en producción HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    
    // Determinar redirección según rol (rutas con carpetas)
    $base = defined('BASE_URL') ? BASE_URL : '';
    switch ($user['rol']) {
        case 'admin':
            $redirect = $base . '/admin/admin-dashboard.php';
            break;
        case 'veterinaria':
            $redirect = $base . '/vet/vet-dashboard.php';
            break;
        case 'tienda':
            $redirect = $base . '/tienda/tienda-dashboard.php';
            break;
        default:
            $redirect = $base . '/dashboard.php';
            break;
    }
    
    $response['success'] = true;
    $response['message'] = '¡Inicio de sesión exitoso!';
    $response['redirect'] = $redirect;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>