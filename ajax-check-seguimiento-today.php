<?php
require_once 'db.php';
require_once 'includes/seguimiento_functions.php';
require_once 'premium-functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$mascota_id = intval($_GET['mascota_id'] ?? 0);

if (!$mascota_id) {
    echo json_encode(['success' => false, 'error' => 'Mascota no especificada']);
    exit;
}

$isPremium = esPremium($user_id);
$today = date('Y-m-d');

// Verificar si ya tiene un registro hoy
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM seguimientos_diarios WHERE mascota_id = ? AND user_id = ? AND fecha = ?");
$stmt->execute([$mascota_id, $user_id, $today]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$hasToday = $result['cnt'] > 0;

// Para usuarios Free: si ya registraron hoy, no permitir más
// El endpoint responde si ya existe registro hoy
echo json_encode([
    'success' => true,
    'hasToday' => $hasToday,
    'isPremium' => $isPremium
]);
