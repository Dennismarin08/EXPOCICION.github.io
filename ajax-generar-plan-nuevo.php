<?php
/**
 * RUGAL - AJAX Generar Plan Nuevo
 * Endpoint limpio para generar planes de salud
 */

require_once 'db.php';
require_once 'includes/PlanSaludGenerator.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

// Obtener datos
$mascotaId = $_POST['mascota_id'] ?? null;
$preview = isset($_POST['preview']) && $_POST['preview'] == '1';

if (!$mascotaId) {
    echo json_encode(['success' => false, 'message' => 'ID de mascota requerido']);
    exit;
}

// Preparar overrides desde POST
$overrides = [
    'edad' => $_POST['edad'] ?? null,
    'peso' => $_POST['peso'] ?? null,
    'raza' => $_POST['raza'] ?? null,
    'nivel_actividad' => $_POST['actividad'] ?? null,
    'tipo_alimento' => $_POST['tipo_alimento'] ?? null,
    'porcion_aprox' => $_POST['porcion_aprox'] ?? null,
    'marca_comercial' => $_POST['marca_comercial'] ?? null,
    'condiciones' => $_POST['condiciones'] ?? '',
    'sintomas' => $_POST['sintomas'] ?? '',
    'enfermedades' => $_POST['enfermedades'] ?? ''
];

// Validaciones server-side
if (empty($overrides['raza'])) {
    echo json_encode(['success' => false, 'message' => 'Raza es requerida']);
    exit;
}

if (empty($overrides['edad']) || $overrides['edad'] < 0) {
    echo json_encode(['success' => false, 'message' => 'Edad inválida']);
    exit;
}

if (empty($overrides['peso']) || $overrides['peso'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Peso inválido']);
    exit;
}

if (empty($overrides['nivel_actividad'])) {
    echo json_encode(['success' => false, 'message' => 'Nivel de actividad requerido']);
    exit;
}

try {
    // Generar plan
    $generator = new PlanSaludGenerator($pdo);
    $resultado = $generator->generar($mascotaId, $overrides, !$preview);
    
    if ($resultado['success']) {
        // Si guardamos, actualizar perfil de mascota
        if (!$preview) {
            $fields = [];
            $params = [];
            
            if (!empty($overrides['raza'])) {
                $fields[] = 'raza = ?';
                $params[] = $overrides['raza'];
            }
            if (!empty($overrides['tipo_alimento'])) {
                $fields[] = 'tipo_alimento = ?';
                $params[] = $overrides['tipo_alimento'];
            }
            if (!empty($overrides['marca_comercial'])) {
                $fields[] = 'marca_comercial = ?';
                $params[] = $overrides['marca_comercial'];
            }
            
            if (count($fields) > 0) {
                $params[] = $mascotaId;
                $sql = "UPDATE mascotas SET " . implode(', ', $fields) . " WHERE id = ?";
                try {
                    $pdo->prepare($sql)->execute($params);
                } catch (Exception $e) {
                    // Silenciar error de columna inexistente
                }
            }
            
            // No se resetea el estado_salud aquí; se mantiene el valor real de la mascota.
        }
        
        echo json_encode([
            'success' => true,
            'plan_id' => $resultado['plan_id'] ?? null,
            'preview' => $resultado['preview'] ?? null,
            'mascota' => $resultado['mascota'] ?? null
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado['error'] ?? 'Error desconocido'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en ajax-generar-plan-nuevo: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
