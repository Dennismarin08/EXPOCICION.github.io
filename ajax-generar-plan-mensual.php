<?php
/**
 * RUGAL - AJAX Handler para Generar Plan de Salud Mensual
 * Procesa el formulario y genera recomendaciones personalizadas con detección inteligente
 */

// TEMPORARY: Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar buffer de output para capturar cualquier error inesperado
ob_start();

require_once 'includes/check-auth.php';
require_once 'includes/plan_salud_mensual_functions.php';
require_once 'puntos-functions.php';
require_once 'premium-functions.php'; // <-- ¡AQUÍ ESTÁ LA MAGIA!
require_once 'db.php';

// Limpiar cualquier output acumulado antes de enviar headers
$unexpected_output = ob_get_clean();
if (!empty($unexpected_output)) {
    error_log('Unexpected output before headers: ' . $unexpected_output);
}

header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se recibió el ID de la mascota
if (!isset($_POST['mascota_id']) || empty($_POST['mascota_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de mascota requerido']);
    exit;
}

$mascotaId = intval($_POST['mascota_id']);
$userId = $_SESSION['user_id'];

// Verificar que la mascota pertenece al usuario
$stmt = $pdo->prepare("SELECT id, nombre, especie, raza, edad_anios, edad_meses, peso FROM mascotas WHERE id = ? AND user_id = ? AND estado = 'activo'");
$stmt->execute([$mascotaId, $userId]);
$mascota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mascota) {
    echo json_encode(['success' => false, 'message' => 'Mascota no encontrada o no autorizada']);
    exit;
}

try {
    // Recopilar datos del formulario
    // Recopilar datos del formulario
    $datos = [
        'condicion_corporal' => intval($_POST['condicion_corporal'] ?? 5),
        'bcs' => intval($_POST['condicion_corporal'] ?? 5), // Mantener retrocompatibilidad
        'apetito' => $_POST['apetito'] ?? 'normal',
        'tipo_alimento' => $_POST['tipo_alimento'] ?? '',
        'marca_alimento' => $_POST['marca_alimento'] ?? '',
        'etapa_vida' => $_POST['etapa_vida'] ?? '',
        'actividad' => $_POST['actividad'] ?? 'normal',
        'tipo_actividad' => $_POST['tipo_actividad'] ?? '',
        'tiempo_ejercicio' => $_POST['tiempo_ejercicio'] ?? '',
        'hora_ejercicio' => $_POST['hora_ejercicio'] ?? '',
        'condiciones' => $_POST['condiciones'] ?? [],
        'otra_condicion' => $_POST['otra_condicion'] ?? '',
        'sintomas' => [] // Estructura detallada
    ];

    // Procesar síntomas detallados
    $terminosPosibles = array_keys(obtenerTerminosMedicos());
    foreach ($terminosPosibles as $termino) {
        $valor = $_POST['sintoma_' . $termino] ?? 'no';
        $datos['terminos_medicos'][$termino] = $valor; // Compatibilidad
        
        if ($valor !== 'no') {
            $datos['sintomas'][$termino] = [
                'severidad' => $valor,
                'duracion' => $_POST['duracion_' . $termino] ?? '',
                'veterinario' => $_POST['veterinario_' . $termino] ?? 'no'
            ];
        }
    }

    // Generar recomendaciones mensuales personalizadas
    $recomendaciones = generarRecomendacionesMensuales($datos, $mascota);

    // Calcular nivel de alerta
    $alerta = calcularNivelAlerta($datos, $mascota);

    // Lógica para usuarios FREE: Solo diagnóstico y alerta
    if (!esPremium($userId)) {
        $response = ['success' => true];
        $titulo = $alerta['titulo'] ?? 'Estado de Salud';
        
        if ($alerta['nivel'] === 'rojo' || $alerta['nivel'] === 'amarillo') {
            $response['free_alert'] = [
                'type' => 'warning',
                'title' => "⚠️ " . $titulo,
                'message' => "Hemos detectado indicadores importantes: {$titulo}.\n\nTe recomendamos contactar a un veterinario. Puedes pedir cita con los aliados registrados o llevarlo a una veterinaria cercana y comentar del caso.\n\nSi deseas un plan detallado con dieta y ejercicios para ayudar a tu mascota, actualiza a Premium."
            ];
        } else {
            // Caso Verde / Leve: Usamos el mensaje específico si existe (ej: Ligero Sobrepeso)
            $esDefault = ($titulo === 'Estado Saludable');
            $mensajeBase = $esDefault ? "Tu mascota parece estar en buen estado." : $alerta['mensaje'];
            
            $response['free_alert'] = [
                'type' => 'info',
                'title' => $esDefault ? "✅ Estado Saludable" : "ℹ️ " . $titulo,
                'message' => $mensajeBase . "\n\nPara obtener la dieta exacta, ejercicios y recomendaciones para mantenerla saludable, actualiza a Premium."
            ];
        }
        echo json_encode($response);
        exit;
    }

    // Actualizar estado de salud en la tabla mascotas (Solo Premium que guardan plan)
    $nuevoEstado = ($alerta['nivel'] === 'rojo') ? 'grave' : (($alerta['nivel'] === 'amarillo') ? 'regular' : 'excelente');
    $pdo->prepare("UPDATE mascotas SET estado_salud = ? WHERE id = ?")->execute([$nuevoEstado, $mascotaId]);

    // Preparar respuesta con veterinarias recomendadas
    $response = [
        'success' => true,
        'alerta' => $alerta,
        'recomendaciones' => $recomendaciones,
        'mascota' => $mascota
    ];

    // Si es preview, solo generar recomendaciones sin guardar
    if (isset($_POST['preview']) && $_POST['preview'] === '1') {
        $response['preview'] = true;
        echo json_encode($response);
        exit;
    }

    // Guardar en base de datos
    $resultado = guardarPlanMensual($pdo, $mascotaId, $userId, $datos, $recomendaciones, $alerta);

    if (!$resultado['success']) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar el plan: ' . $resultado['error']
        ]);
        exit;
    }

    $response['message'] = 'Plan de salud mensual generado exitosamente';
    $response['plan_id'] = $resultado['plan_id'];
    $response['tareas_creadas'] = $resultado['tareas_creadas'];
    $response['redirect_url'] = 'plan-salud-mensual.php?mascota_id=' . $mascotaId;

    echo json_encode($response);


} catch (Exception $e) {
    error_log('Error en ajax-generar-plan-mensual.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage(),
        'debug' => $e->getMessage()
    ]);
    exit;
}

// Asegurar que no haya output adicional después
exit;
?>
