<?php
/**
 * RUGAL - AJAX de Análisis de Comportamiento IA
 * Conecta con CHAT-IA y registra en Historial Médico
 */
require_once 'db.php';
require_once 'includes/planes_salud_functions.php';

// Nota: CHAT-IA/config.php define $conn. db.php define $pdo.

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$input = strtolower(trim($_POST['behavior'] ?? ''));
$mascotaId = (int)($_POST['mascota_id'] ?? 0);

if ($input === '' || $mascotaId === 0) {
    echo json_encode(['success' => false, 'error' => 'Entrada inválida']);
    exit;
}

// 1. Detección de Urgencias (Hard-coded safety layer)
$urgenciaKeywords = ['sangre', 'sangrando', 'convulsión', 'convulsionando', 'no respira', 'envenenado', 'veneno', 'fractura', 'atropellado', 'inconsciente', 'ahogando'];
$esUrgencia = false;

foreach ($urgenciaKeywords as $key) {
    if (strpos($input, $key) !== false) {
        $esUrgencia = true;
        break;
    }
}

// 1. Buscar coincidencia en la IA (Lógica de CHAT-IA/responder.php)
$iaResponse = "No entendí, disculpas. Si es necesario, en la app te ofrecemos descuentos en las veterinarias que están registradas.";
$foundMatch = false;
$temaIA = "Consulta no entendida";

if ($esUrgencia) {
    $iaResponse = "⚠️ **ATENCIÓN: Esto parece una emergencia.** Por favor, lleva a " . (isset($_POST['pet_name']) ? $_POST['pet_name'] : "tu mascota") . " al veterinario de inmediato. Los síntomas de urgencia requieren atención profesional inmediata.";
    $temaIA = "EMERGENCIA DETECTADA";
    $foundMatch = true;
} else {
    // Coincidencia directa (Ambos sentidos: que el input contenga la pregunta o la pregunta contenga el input)
    $stmt = $conn->prepare("
        SELECT t.tema, t.respuesta 
        FROM preguntas p 
        JOIN temas t ON t.id=p.tema_id
        WHERE ? LIKE CONCAT('%', p.texto, '%') 
           OR p.texto LIKE CONCAT('%', ?, '%')
        LIMIT 1
    ");
    $stmt->bind_param("ss", $input, $input);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res) {
        $iaResponse = $res['respuesta'];
        $temaIA = $res['tema'];
        $foundMatch = true;
    } else {
        // Coincidencia por Palabras Clave (Fallback intermedio)
        $words = explode(' ', $input);
        foreach ($words as $word) {
            if (strlen($word) < 4) continue; // Ignorar palabras cortas
            $stmt = $conn->prepare("
                SELECT t.tema, t.respuesta 
                FROM preguntas p 
                JOIN temas t ON t.id=p.tema_id
                WHERE p.texto LIKE CONCAT('%', ?, '%')
                LIMIT 1
            ");
            $stmt->bind_param("s", $word);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res) {
                $iaResponse = $res['respuesta'];
                $temaIA = $res['tema'];
                $foundMatch = true;
                break;
            }
        }

        if (!$foundMatch) {
            // Similaridad aproximada
            $q = $pdo->query("
              SELECT t.tema, t.respuesta, p.texto 
              FROM preguntas p 
              JOIN temas t ON t.id=p.tema_id
            ");

            $best = ["s" => 0, "r" => null, "t" => null];
            while($r = $q->fetch()){
                similar_text($input, strtolower($r['texto']), $s);
                if($s > $best['s']) $best = ["s" => $s, "r" => $r['respuesta'], "t" => $r['tema']];
            }

            if ($best['s'] > 45) { // Bajamos un poco el umbral para mayor flexibilidad
                $iaResponse = $best['r'];
                $temaIA = $best['t'];
                $foundMatch = true;
            }
        }
    }
}

// 2. Registrar en el Historial Médico (RUGAL DB)
try {
    $motivo = "Consulta al Asistente IA";
    $rawBehavior = $_POST['behavior'] ?? 'Consulta';
    $diagnostico = "el usuario diagnostico " . $rawBehavior . " del perro la fecha " . date('d/m/Y');
    $tratamiento = "Respuesta de la IA: " . $iaResponse;
    
    $datosHistorial = [
        'mascota_id' => $mascotaId,
        'fecha' => date('Y-m-d'),
        'tipo' => 'IA', // Cambiamos a 'IA' para identificarlo mejor
        'motivo' => $motivo,
        'diagnostico' => $diagnostico,
        'tratamiento' => $tratamiento,
        'veterinario' => 'IA RUGAL',
        'clinica' => 'Sistema RUGAL',
        'notas' => "Interacción IA: El usuario puso esto y la IA le respondió."
    ];

    if (guardarHistorialMedico($datosHistorial)) {
        echo json_encode([
            'success' => true,
            'response' => $iaResponse,
            'match' => $foundMatch,
            'message' => 'Comportamiento analizado y sumado al historial médico.'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en el historial médico']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
