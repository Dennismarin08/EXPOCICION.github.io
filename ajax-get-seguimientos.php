<?php
require_once 'db.php';
require_once 'includes/seguimiento_functions.php';

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'No autorizado']); exit; }

$mascota_id = $_GET['mascota_id'] ?? ($_POST['mascota_id'] ?? null);
$desde = $_GET['desde'] ?? ($_POST['desde'] ?? null);
$hasta = $_GET['hasta'] ?? ($_POST['hasta'] ?? null);

if (!$mascota_id) { echo json_encode(['success'=>false,'error'=>'Mascota requerida']); exit; }

try {
    $rows = obtenerSeguimientos($pdo, $mascota_id, $desde, $hasta);
    // incluir alertas asociadas
    foreach ($rows as &$r) {
        $stmt = $pdo->prepare("SELECT * FROM alertas WHERE seguimiento_id = ?");
        $stmt->execute([$r['id']]);
        $r['alertas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode(['success'=>true,'seguimientos'=>$rows]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
