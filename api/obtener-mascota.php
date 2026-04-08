<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/razas_data.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { echo json_encode(['success' => false, 'message' => 'ID de mascota no proporcionado']); exit; }

$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE id = ?");
$stmt->execute([$id]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pet) { echo json_encode(['success' => false, 'message' => 'Mascota no encontrada']); exit; }

// Obtener datos adicionales
$datosRaza = obtenerDatosRaza($pet['raza'] ?? '');

// Obtener último peso si no está en el registro principal
$ultimoPeso = null;
if (empty($pet['peso'])) {
    $stmtPeso = $pdo->prepare("SELECT peso FROM peso_historial WHERE mascota_id = ? ORDER BY fecha DESC LIMIT 1");
    $stmtPeso->execute([$id]);
    $ultimoPeso = $stmtPeso->fetchColumn();
}

// Obtener estado de salud actual
$estadoSalud = $pet['estado_salud'] ?? 'excelente';

// Preparar respuesta completa con todos los campos necesarios para el formulario
$resp = [
    'success' => true,
    'mascota' => [
        'id' => $pet['id'],
        'nombre' => $pet['nombre'] ?? '',
        'edad' => $pet['edad'] ?? null,
        'peso' => $pet['peso'] ?? $ultimoPeso ?? null,
        'raza' => $pet['raza'] ?? '',
        'sexo' => $pet['sexo'] ?? '',
        'color' => $pet['color'] ?? '',
        'tipo' => $pet['tipo'] ?? '',
        'nivel_actividad' => $pet['nivel_actividad'] ?? 'medio',
        'tipo_alimento' => $pet['tipo_alimento'] ?? '',
        'marca_comercial' => $pet['marca_comercial'] ?? '',
        'alergias' => $pet['alergias'] ?? '',
        'esterilizado' => $pet['esterilizado'] ?? 0,
        'vive_en' => $pet['vive_en'] ?? '',
        'estado_salud' => $estadoSalud,
        'foto_perfil' => $pet['foto_perfil'] ?? null,
        'fecha_nacimiento' => $pet['fecha_nacimiento'] ?? null,
        'notas' => $pet['notas'] ?? ''
    ],
    'datos_raza' => $datosRaza,
    'metadata' => [
        'edad_calculada' => calcularEdad($pet['fecha_nacimiento'] ?? null, $pet['edad'] ?? null),
        'etapa_vida' => determinarEtapaVida($pet['edad'] ?? 0),
        'peso_promedio_raza' => $datosRaza['peso_promedio'] ?? null,
        'energia_raza' => $datosRaza['energia'] ?? 'media'
    ]
];

echo json_encode($resp);

// Funciones auxiliares
function calcularEdad($fechaNacimiento, $edadRegistrada) {
    if ($edadRegistrada) return floatval($edadRegistrada);

    if ($fechaNacimiento) {
        $nacimiento = new DateTime($fechaNacimiento);
        $ahora = new DateTime();
        $diferencia = $ahora->diff($nacimiento);
        return $diferencia->y + ($diferencia->m / 12) + ($diferencia->d / 365.25);
    }

    return null;
}

function determinarEtapaVida($edad) {
    if (!$edad) return 'desconocida';

    if ($edad < 1) return 'cachorro';
    if ($edad >= 7) return 'senior';
    return 'adulto';
}
