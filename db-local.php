<?php
// db-local.php - CONFIGURACIÓN PARA WAMPSERVER (LOCAL)
// Para usar: Renombrar este archivo a 'db.php' mientras trabajas en tu PC.

require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Bogota');

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Configuración LOCAL (WampServer)
            $host = 'localhost';
            $dbname = 'rugal_db';
            $username = 'root'; 
            $password = ''; 
            
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
        } catch (PDOException $e) {
            die("Error de conexión local: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}

function getDBConnection() {
    return Database::getInstance();
}

// Crear directorio de uploads si no existe
if (!file_exists('uploads/')) {
    mkdir('uploads/', 0777, true);
    file_put_contents('uploads/.htaccess', 
        "Order deny,allow\nDeny from all\n<Files ~ \"\.(jpg|jpeg|png|gif)$\">\nAllow from all\n</Files>");
}

// Función para subir foto
function uploadPhoto($file) {
    if (!isset($file) || $file['error'] == UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] != UPLOAD_ERR_OK) throw new Exception('Error al subir archivo: ' . $file['error']);
    if ($file['size'] > 5 * 1024 * 1024) throw new Exception('La imagen es muy grande (máximo 5MB)');
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes)) throw new Exception('Tipo de archivo no permitido');
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'pet_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $filePath = 'uploads/' . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) return $fileName;
    throw new Exception('Error al guardar la imagen');
}

$pdo = getDBConnection();

// Funciones de utilidad
function getUsuario($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getUserRole($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT rol, premium FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function isAdmin($userId) { $u = getUserRole($userId); return $u && $u['rol'] === 'admin'; }
function isVeterinaria($userId) { $u = getUserRole($userId); return $u && $u['rol'] === 'veterinaria'; }
function isTienda($userId) { $u = getUserRole($userId); return $u && $u['rol'] === 'tienda'; }
function isUsuario($userId) { $u = getUserRole($userId); return $u && $u['rol'] === 'usuario'; }
function isPremium($userId) { $u = getUserRole($userId); return $u && $u['premium'] == 1; }

function checkRole($requiredRole) {
    if (!isset($_SESSION['user_id'])) {
        $isSubdir = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/vet/') !== false || strpos($_SERVER['PHP_SELF'], '/tienda/') !== false);
        header('Location: ' . ($isSubdir ? '../login.html' : 'login.html'));
        exit;
    }
    $user = getUserRole($_SESSION['user_id']);
    if (!$user || $user['rol'] !== $requiredRole) {
        $isSubdir = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/vet/') !== false || strpos($_SERVER['PHP_SELF'], '/tienda/') !== false);
        header('Location: ' . ($isSubdir ? '../dashboard.php' : 'dashboard.php'));
        exit;
    }
}

function getThemeClass() {
    if (!isset($_SESSION['user_id'])) return 'theme-usuario';
    global $pdo;
    $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) return 'theme-usuario';
    switch ($user['rol']) {
        case 'veterinaria': return 'theme-vet';
        case 'tienda': return 'theme-tienda';
        case 'admin': return 'theme-admin'; 
        default: return 'theme-usuario';
    }
}

$themeClass = getThemeClass();

function redirectByRole() {
    if (!isset($_SESSION['user_id'])) return;
    $user = getUserRole($_SESSION['user_id']);
    if (!$user) return;
    if ($user['rol'] === 'admin' && basename($_SERVER['PHP_SELF']) === 'dashboard.php') {
        header('Location: admin/admin-dashboard.php');
        exit;
    }
}

redirectByRole();
?>
