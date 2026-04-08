<?php
/**
 * RUGAL - Funciones para Plan de Salud Mensual
 * Versión simplificada y funcional
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/razas_data.php';

/**
 * Términos médicos básicos
 */
function obtenerTerminosMedicos() {
    return [
        'claudicacion' => [
            'termino' => 'Cojera',
            'explicacion' => 'Dificultad al caminar o evitar apoyar una pata',
            'icono' => 'fa-walking',
            'categoria' => 'locomotor'
        ],
        'disfagia' => [
            'termino' => 'Dificultad para tragar',
            'explicacion' => 'Tos al comer o regurgitación',
            'icono' => 'fa-utensils',
            'categoria' => 'digestivo'
        ],
        'poliuria' => [
            'termino' => 'Orina/Bebe mucho',
            'explicacion' => 'Orina o bebe agua en exceso',
            'icono' => 'fa-tint',
            'categoria' => 'urinario'
        ],
        'prurito' => [
            'termino' => 'Comezón',
            'explicacion' => 'Rascado excesivo por alergias o parásitos',
            'icono' => 'fa-hand-sparkles',
            'categoria' => 'dermatologico'
        ],
        'alopecia' => [
            'termino' => 'Caída de pelo',
            'explicacion' => 'Pérdida anormal de pelo',
            'icono' => 'fa-cloud',
            'categoria' => 'dermatologico'
        ],
        'otitis' => [
            'termino' => 'Infección de oído',
            'explicacion' => 'Dolor, mal olor o secreciones en oídos',
            'icono' => 'fa-ear-listen',
            'categoria' => 'auditivo'
        ],
        'epifora' => [
            'termino' => 'Lagrimeo excesivo',
            'explicacion' => 'Secreción ocular abundante',
            'icono' => 'fa-eye',
            'categoria' => 'ocular'
        ],
        'estomatitis' => [
            'termino' => 'Inflamación bucal',
            'explicacion' => 'Mal aliento, encías rojas o sangrado',
            'icono' => 'fa-tooth',
            'categoria' => 'dental'
        ],
        'dermatitis' => [
            'termino' => 'Inflamación de piel',
            'explicacion' => 'Enrojecimiento, costras o heridas en piel',
            'icono' => 'fa-hand-dots',
            'categoria' => 'dermatologico'
        ],
        'vomito_regurgitacion' => [
            'termino' => 'Vómito/Regurgitación',
            'explicacion' => 'Expulsión de comida o líquidos',
            'icono' => 'fa-stomach',
            'categoria' => 'digestivo'
        ],
        'diarrea_estrenimiento' => [
            'termino' => 'Diarrea/Estreñimiento',
            'explicacion' => 'Cambios en hábitos intestinales',
            'icono' => 'fa-poop',
            'categoria' => 'digestivo'
        ],
        'tos_disnea' => [
            'termino' => 'Tos/Dificultad respiratoria',
            'explicacion' => 'Tos frecuente o respiración dificultosa',
            'icono' => 'fa-lungs',
            'categoria' => 'respiratorio'
        ]
    ];
}

/**
 * Escala BCS simplificada
 */
function obtenerEscalaBCS() {
    return [
        1 => 'Muy delgado',
        2 => 'Delgado', 
        3 => 'Ligeramente delgado',
        4 => 'Ideal (bajo)',
        5 => 'Ideal',
        6 => 'Ideal (alto)',
        7 => 'Ligeramente sobrepeso',
        8 => 'Sobrepeso',
        9 => 'Obeso'
    ];
}

/**
 * Niveles de apetito
 */
function obtenerNivelesApetito() {
    return [
        'muy_bajo' => 'Muy bajo - No quiere comer',
        'bajo' => 'Bajo - Come poco',
        'normal' => 'Normal - Come bien',
        'alto' => 'Alto - Come mucho',
        'muy_alto' => 'Muy alto - Come en exceso'
    ];
}

/**
 * Niveles de actividad
 */
function obtenerNivelesActividad() {
    return [
        'muy_baja' => 'Muy baja - Duerme casi todo el día',
        'baja' => 'Baja - Poco interés en jugar',
        'normal' => 'Normal - Actividad equilibrada',
        'alta' => 'Alta - Muy juguetón/activo',
        'muy_alta' => 'Muy alta - Hiperactivo'
    ];
}

/**
 * Common Health Conditions
 */
function obtenerCondicionesComunes() {
    return [
        'alergias' => 'Alergias (Piel/Alimentos)',
        'articulaciones' => 'Problemas Articulares/Artritis',
        'cardiaco' => 'Problemas Cardíacos',
        'renal' => 'Problemas Renales',
        'digestivo' => 'Estómago Sensible/Digestivo',
        'diabetes' => 'Diabetes',
        'sobrepeso' => 'Sobrepeso/Obesidad',
        'dental' => 'Problemas Dentales',
        'ninguna' => 'Ninguna condición conocida'
    ];
}

/**
 * Obtener veterinarias recomendadas
 */
function obtenerVeterinariasRecomendadas($problemasDetectados = []) {
    global $pdo;

    try {
        $stmt = $pdo->query("
            SELECT a.id, a.nombre_local, a.direccion, u.telefono, a.descripcion, a.servicios
            FROM aliados a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.tipo = 'veterinaria' AND a.activo = 1 AND a.acepta_citas = 1
            ORDER BY a.nombre_local
        ");
        $veterinarias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si hay problemas específicos, priorizar veterinarias con servicios relevantes
        if (count($problemasDetectados) > 0) {
            foreach ($veterinarias as &$vet) {
                // Ensure json_decode always returns an array, never null
                $decoded = json_decode($vet['servicios'] ?? '', true);
                $vet['servicios_array'] = is_array($decoded) ? $decoded : [];
                $vet['relevancia'] = 0;

                // Calcular relevancia basada en servicios
                foreach ($problemasDetectados as $problema) {
                    if (in_array($problema, $vet['servicios_array'], true)) {
                        $vet['relevancia'] += 2;
                    }
                }
            }

            // Ordenar por relevancia
            usort($veterinarias, function($a, $b) {
                return $b['relevancia'] <=> $a['relevancia'];
            });
        }

        return $veterinarias;
    } catch (Exception $e) {
        error_log('Error obteniendo veterinarias: ' . $e->getMessage());
        return [];
    }
}


/**
 * Obtener duración de ejercicio recomendada por raza
 */
function obtenerDuracionEjercicioPorRaza($raza) {
    $raza = strtolower(trim($raza));
    $duraciones = [
        'border collie' => '60-90 min',
        'pastor aleman' => '60 min',
        'labrador' => '45-60 min',
        'golden retriever' => '45-60 min',
        'husky' => '60-90 min',
        'pug' => '20-30 min (baja intensidad)',
        'bulldog' => '20-30 min (baja intensidad)',
        'chihuahua' => '20-30 min',
        'yorkshire' => '30 min',
        'poodle' => '30-45 min',
        'beagle' => '45-60 min',
        'boxer' => '45-60 min',
        'dalmata' => '60 min',
        'rottweiler' => '45 min',
        'shih tzu' => '20-30 min',
        'terrier' => '30-45 min',
        'mix' => '30-45 min'
    ];
    
    foreach ($duraciones as $key => $val) {
        if (strpos($raza, $key) !== false) {
            return $val;
        }
    }
    
    return '30-45 min'; // Default
}

/**
 * Generar recomendaciones mensuales detalladas y personalizadas
 */
function generarRecomendacionesMensuales($datos, $mascota) {
    // --- ANÁLISIS DE PESO AUTOMÁTICO ---
    $datosRaza = obtenerDatosRaza($mascota['raza']);
    $pesoActual = floatval($mascota['peso'] ?? 0);
    $pesoMin = floatval($datosRaza['peso_min'] ?? 0);
    $pesoMax = floatval($datosRaza['peso_max'] ?? 0);
    $estadoPeso = 'optimo';

    if ($pesoActual > 0 && $pesoMax > 0 && $pesoActual > $pesoMax) {
        $estadoPeso = 'sobrepeso';
    } elseif ($pesoActual > 0 && $pesoMin > 0 && $pesoActual < $pesoMin) {
        $estadoPeso = 'bajo_peso';
    }

    $recomendaciones = [
        'diaria' => [
            'alimentacion' => [],
            'ejercicio' => [],
            'higiene' => [],
            'salud' => []
        ],
        'semanal' => [
            'alimentacion' => [],
            'ejercicio' => [],
            'higiene' => [],
            'salud' => []
        ],
        'mensual' => [
            'alimentacion' => [],
            'ejercicio' => [],
            'higiene' => [],
            'salud' => [],
            'veterinario' => []
        ],
        'veterinarias_recomendadas' => []
    ];

    // Datos procesados
    $bcs = intval($datos['bcs'] ?? 5);
    $apetito = $datos['apetito'] ?? 'normal';
    $actividad = $datos['actividad'] ?? 'normal'; // nombre campo actualizado
    $tipoAlimento = $datos['tipo_alimento'] ?? '';
    $etapaVida = $datos['etapa_vida'] ?? '';
    $tipoActividad = $datos['tipo_actividad'] ?? '';
    $horaEjercicio = $datos['hora_ejercicio'] ?? '';
    $condiciones = $datos['condiciones'] ?? [];
    $sintomas = $datos['sintomas'] ?? []; // Array detallado
    
    $especie = strtolower($mascota['especie'] ?? 'perro');
    $edadAnios = intval($mascota['edad_anios'] ?? 0);    

    // Determinar objetivo de peso: El análisis automático de peso vs raza tiene prioridad
    $objetivoPeso = 'mantener';
    $objetivos = [];
    
    if ($estadoPeso === 'sobrepeso') {
        $objetivoPeso = 'perder';
        $objetivos[] = 'Reducir peso corporal para alcanzar un rango saludable (' . $pesoMax . ' kg)';
        $objetivos[] = 'Aumentar la actividad física de forma controlada';
    } elseif ($estadoPeso === 'bajo_peso') {
        $objetivoPeso = 'ganar';
        $objetivos[] = 'Aumentar peso de forma gradual y saludable';
    } else {
        // Si el peso está en rango, se usa el BCS del usuario como ajuste fino
        if ($bcs <= 3) {
            $objetivoPeso = 'ganar';
            $objetivos[] = 'Mejorar la condición corporal para un estado más robusto.';
        } elseif ($bcs >= 7) {
            $objetivoPeso = 'perder';
            $objetivos[] = 'Alcanzar una condición corporal ideal mediante control calórico y ejercicio.';
        } else {
            $objetivos[] = 'Mantener el peso y condición física actuales.';
        }
    }

    // Verificar términos médicos
    $terminos = $datos['terminos_medicos'] ?? [];
    $problemasDetectados = [];

    foreach ($terminos as $termino => $valor) {
        if ($valor !== 'no' && $valor !== '') {
            $problemasDetectados[] = $termino;
        }
    }
    
    if (!empty($problemasDetectados) || !empty($condiciones)) {
        $objetivos[] = 'Controlar y monitorear condiciones de salud detectadas';
    }
    
    if ($edadAnios < 1) {
        $objetivos[] = 'Asegurar crecimiento y desarrollo óptimo';
    } elseif ($edadAnios > 7) {
        $objetivos[] = 'Mantener calidad de vida y movilidad en etapa senior';
    }

    $recomendaciones['objetivos'] = $objetivos;

    // === ALIMENTACIÓN ===
    // Por etapa de vida
    if ($etapaVida === 'cachorro') {
        $recomendaciones['diaria']['alimentacion'][] = 'Alimentar 3-4 veces al día (crecimiento)';
        $recomendaciones['semanal']['alimentacion'][] = 'Ajustar ración según ganancia de peso semanal';
    } elseif ($etapaVida === 'senior') {
        $recomendaciones['diaria']['alimentacion'][] = 'Ofrecer dieta de fácil digestión';
        $recomendaciones['diaria']['salud'][] = 'Observar consumo de agua (importante en seniors)';
    }

    // Por tipo de alimento
    if ($tipoAlimento === 'croquetas') {
        $recomendaciones['diaria']['alimentacion'][] = 'Asegurar hidratación extra (comida seca)';
    } elseif ($tipoAlimento === 'natural' || $tipoAlimento === 'humedo') {
        $recomendaciones['diaria']['higiene'][] = 'Higiene bucal post-comida (mayor riesgo sarro)';
        $recomendaciones['diaria']['alimentacion'][] = 'Retirar comida no consumida en 30 min (evitar bacterias)';
    }

    // Por Peso y Condición (Lógica mejorada)
    if ($estadoPeso === 'sobrepeso') {
        $recomendaciones['diaria']['alimentacion'][] = 'Reducir ración diaria un 15-20% (consultar veterinario)';
        $recomendaciones['diaria']['alimentacion'][] = 'Eliminar por completo premios calóricos y sobras de comida humana';
        $recomendaciones['diaria']['ejercicio'][] = 'Aumentar duración de paseos en 15 minutos, a ritmo constante';
        $recomendaciones['semanal']['salud'][] = 'Control de peso semanal obligatorio y registrarlo';
    } elseif ($estadoPeso === 'bajo_peso') {
        $recomendaciones['diaria']['alimentacion'][] = 'Aumentar ración diaria un 10-15% (consultar veterinario)';
        $recomendaciones['semanal']['salud'][] = 'Pesaje semanal para monitorear ganancia de peso';
    } else {
        if ($bcs >= 7) { // Sobrepeso leve detectado por BCS
            $recomendaciones['diaria']['alimentacion'][] = 'Medir ración exacta con báscula (no usar tazas medidoras)';
            $recomendaciones['diaria']['alimentacion'][] = 'Reemplazar premios comerciales por opciones saludables (ej: zanahoria)';
            $recomendaciones['semanal']['salud'][] = 'Control de peso semanal';
        } elseif ($bcs <= 3) { // Bajo peso leve detectado por BCS
            $recomendaciones['diaria']['alimentacion'][] = 'Considerar un alimento más denso en calorías (consultar vet)';
            $recomendaciones['semanal']['salud'][] = 'Pesaje semanal para asegurar que no siga bajando';
        } else {
            $recomendaciones['diaria']['alimentacion'][] = 'Mantener ración actual y horarios fijos';
        }
    }

    // === EJERCICIO ===
    // Duración base por raza
    $raza = $mascota['raza'] ?? '';
    $duracionBase = obtenerDuracionEjercicioPorRaza($raza);
    $recomendaciones['diaria']['ejercicio'][] = "Meta de actividad diaria: $duracionBase";
    
    // RUTINA DIARIA ESPECÍFICA - Caminar por la mañana o noche
    $edadAnios = intval($mascota['edad_anios'] ?? 0);
    if ($edadAnios < 1) {
        // Cachorros: sesiones cortas
        $recomendaciones['diaria']['ejercicio'][] = "Caminar 15-20 minutos por la mañana (7-9 AM) o noche (7-9 PM)";
        $recomendaciones['diaria']['ejercicio'][] = "Evitar ejercicio intenso (huesos en desarrollo)";
    } elseif ($edadAnios >= 7) {
        // Seniors: ejercicio moderado
        $recomendaciones['diaria']['ejercicio'][] = "Caminar 20-30 minutos por la mañana (7-9 AM) o noche (7-9 PM)";
        $recomendaciones['diaria']['ejercicio'][] = "Ritmo tranquilo, permitir descansos frecuentes";
    } else {
        // Adultos: ejercicio normal
        if (strpos(strtolower($raza), 'husky') !== false || 
            strpos(strtolower($raza), 'border') !== false ||
            strpos(strtolower($raza), 'pastor') !== false) {
            // Razas activas
            $recomendaciones['diaria']['ejercicio'][] = "Caminar 40-60 minutos por la mañana (6-8 AM) o noche (7-9 PM)";
        } else {
            // Razas normales/pequeñas
            $recomendaciones['diaria']['ejercicio'][] = "Caminar 30-40 minutos por la mañana (7-9 AM) o noche (7-9 PM)";
        }
    }
    
    // Advertencia sobre horarios
    $recomendaciones['diaria']['ejercicio'][] = "⚠️ EVITAR ejercicio entre 12 PM - 4 PM (riesgo de golpe de calor)";

    // Por tipo de actividad
    if ($tipoActividad === 'caminata') {
        $recomendaciones['diaria']['ejercicio'][] = 'Variar ruta para estimulación mental';
    } elseif ($tipoActividad === 'juego') {
        $recomendaciones['diaria']['ejercicio'][] = 'Evitar saltos bruscos repetitivos';
    } elseif ($tipoActividad === 'entrenamiento') {
        $recomendaciones['diaria']['ejercicio'][] = 'Sesiones cortas (15 min) con refuerzo positivo';
    }

    // Por hora (Golpe de calor)
    if ($horaEjercicio === 'tarde') {
        $recomendaciones['diaria']['ejercicio'][] = '⚠️ RIESGO CALOR: Mover ejercicio a mañana/noche o reducir intensidad';
        $recomendaciones['diaria']['salud'][] = 'Verificar almohadillas por quemaduras';
    }

    // Intensidad según actividad reportada vs BCS
    if ($bcs >= 7) {
        $recomendaciones['diaria']['ejercicio'][] = 'Ejercicio de bajo impacto (caminata, natación)';
        $recomendaciones['diaria']['ejercicio'][] = 'Aumentar duración gradualmente, no intensidad';
    }

    // === SALUD Y CONDICIONES ===
    // Condiciones preexistentes
    if (in_array('articulaciones', $condiciones)) {
        $recomendaciones['diaria']['salud'][] = 'Suplementación articular (si recetada)';
        $recomendaciones['diaria']['ejercicio'][] = 'Evitar escaleras y saltos';
        $recomendaciones['diaria']['higiene'][] = 'Mantener uñas cortas (ayuda tracción)';
    }
    if (in_array('cardiaco', $condiciones)) {
        $recomendaciones['diaria']['salud'][] = 'Contar frecuencia respiratoria en reposo';
        $recomendaciones['diaria']['ejercicio'][] = 'Evitar ejercicio intenso o en calor';
    }
    if (in_array('renal', $condiciones)) {
        $recomendaciones['diaria']['alimentacion'][] = 'Asegurar ingesta de agua (fuentes/caldos)';
        $recomendaciones['diaria']['alimentacion'][] = 'Dieta renal estricta (sin extras proteicos)';
    }
    if (in_array('dental', $condiciones)) {
        $recomendaciones['diaria']['higiene'][] = 'Limpieza dental/Enjuague diario';
    }
    if (in_array('alergias', $condiciones)) {
        $recomendaciones['diaria']['higiene'][] = 'Limpieza de patas al volver de paseo';
        $recomendaciones['diaria']['alimentacion'][] = 'Dieta hipoalergénica estricta';
    }

    // === SÍNTOMAS DETECTADOS ===
    foreach ($sintomas as $nombre => $info) {
        if ($info['severidad'] !== 'no') {
            $terminos = obtenerTerminosMedicos();
            $sintomaLabel = $terminos[$nombre]['termino'] ?? $nombre;
            $recomendaciones['diaria']['salud'][] = "Monitorear evolución de: $sintomaLabel";
            
            // === RECOMENDACIONES ESPECÍFICAS POR SÍNTOMA ===
            switch ($nombre) {
                case 'claudicacion': // Cojera
                    $recomendaciones['diaria']['ejercicio'][] = "🚫 EVITAR: Correr, saltar o subir escaleras";
                    $recomendaciones['diaria']['ejercicio'][] = "🐕 Caminatas suaves en terreno plano (10-15 min)";
                    $recomendaciones['diaria']['salud'][] = "🔍 Revisar almohadillas y patas diariamente";
                    $recomendaciones['diaria']['salud'][] = "🧊 Aplicar frío si hay hinchazón";
                    $recomendaciones['semanal']['salud'][] = "📞 Consultar al veterinario si no mejora en 3-5 días";
                    break;
                    
                case 'prurito': // Comezón
                    $recomendaciones['diaria']['higiene'][] = "🧼 Bañar con shampoo hipoalergénico 1-2 veces por semana";
                    $recomendaciones['diaria']['higiene'][] = "🧹 Limpiar orejas para prevenir infecciones";
                    $recomendaciones['diaria']['higiene'][] = "🚿 Enjuagar patas después de paseo";
                    $recomendaciones['diaria']['alimentacion'][] = "🥗 Considerar dieta hipoalergénica";
                    $recomendaciones['diaria']['salud'][] = "💊 Revisar si tiene pulgas (tratar si es necesario)";
                    $recomendaciones['diaria']['salud'][] = "✋ Evitar que se rasque excessively (usar collar isabelino si es necesario)";
                    $recomendaciones['semanal']['salud'][] = "🌿 Aplicar cremas o sprays recomendados por el vet";
                    break;
                    
                case 'disfagia': // Dificultad para tragar
                    $recomendaciones['diaria']['alimentacion'][] = "🍲 Humedecer el alimento para facilitar tragado";
                    $recomendaciones['diaria']['alimentacion'][] = "🥣 Ofrecer comida enlatada/suave en lugar de croquetas secas";
                    $recomendaciones['diaria']['alimentacion'][] = "💧 Asegurar hydration correcta";
                    $recomendaciones['diaria']['salud'][] = "🔍 Revisar boca y garganta semanalmente";
                    $recomendaciones['mensual']['veterinario'][] = "🦷 Revisión dental para descartar problemas dentales";
                    break;
                    
                case 'poliuria': // Orina/Bebe mucho
                    $recomendaciones['diaria']['salud'][] = "💧 Siempre tener agua fresca disponible";
                    $recomendaciones['diaria']['salud'][] = "🚶‍♀️ Sacar a pasear más frecuentemente para orinar";
                    $recomendaciones['diaria']['salud'][] = "🔍 Observar color de la orina (oscuro = problema)";
                    $recomendaciones['semanal']['salud'][] = "🧪 Podría necesitar análisis de sangre/orina";
                    break;
                    
                case 'alopecia': // Caída de pelo
                    $recomendaciones['diaria']['higiene'][] = "🪮 Cepillar diariamente para estimular circulación";
                    $recomendaciones['diaria']['higiene'][] = "🛁 Bañar mensualmente con shampoo específico";
                    $recomendaciones['diaria']['alimentacion'][] = "💊 Suplementos de omega 3 y biotina";
                    $recomendaciones['diaria']['salud'][] = "🔍 Buscar signos de parásitos o alergias";
                    break;
                    
                case 'otitis': // Infección de oído
                    $recomendaciones['diaria']['higiene'][] = "👂 Limpiar orejas semanalmente con solución apropiada";
                    $recomendaciones['diaria']['higiene'][] = "🚿 Secar orejas después del baño";
                    $recomendaciones['diaria']['salud'][] = "🚫 NO usar hisopos de algodón dentro del oído";
                    $recomendaciones['diaria']['salud'][] = "🔍 Revisar si hay mal olor o secreción";
                    $recomendaciones['semanal']['salud'][] = "💧 Aplicar gotas telinga según indicación veterinaria";
                    break;
                    
                case 'epifora': // Lagrimeo excesivo
                    $recomendaciones['diaria']['higiene'][] = "👁️ Limpiar area de los ojos diariamente";
                    $recomendaciones['diaria']['higiene'][] = "🧼 Usar agua tibia o solución salina";
                    $recomendaciones['diaria']['salud'][] = "🔍 Observar color de la descarga (marrón/verde = infección)";
                    $recomendaciones['semanal']['veterinario'][] = "👨‍⚕️ Revisión ocular si no mejora";
                    break;
                    
                case 'estomatitis': // Inflamación bucal
                    $recomendaciones['diaria']['higiene'][] = "🦷 Cepillar dientes diariamente o usar enjuague dental";
                    $recomendaciones['diaria']['alimentacion'][] = "🥩 Alimentar con comida blanda si tiene dolor";
                    $recomendaciones['diaria']['salud'][] = "🔍 Revisar encías semanalmente";
                    $recomendaciones['semanal']['veterinario'][] = "🦷 Limpieza dental profesional recomendada";
                    break;
                    
                case 'dermatitis': // Inflamación de piel
                    $recomendaciones['diaria']['higiene'][] = "🧼 Bañar con shampoo medicinal según indicação";
                    $recomendaciones['diaria']['higiene'][] = "🚫 Evitar el sol directo en areas afectadas";
                    $recomendaciones['diaria']['higiene'][] = "👕 Usar ropa suave de algodón";
                    $recomendaciones['diaria']['salud'][] = "💊 Aplicar cremas tópico según prescripción";
                    $recomendaciones['diaria']['salud'][] = "🚫 Evitar que se lama/rasque las heridas";
                    break;
                    
                case 'vomito_regurgitacion': // Vómito
                    $recomendaciones['diaria']['alimentacion'][] = "🍽️ Dejar en ayunas 12 horas (adultos)";
                    $recomendaciones['diaria']['alimentacion'][] = "🥣 Reintroducir comida gradualmente (arroz pollo)";
                    $recomendaciones['diaria']['alimentacion'][] = "🚫 Evitar comida grasosa o humana";
                    $recomendaciones['diaria']['salud'][] = "💧 Ofrecer agua en pequeñas cantidades";
                    $recomendaciones['diaria']['salud'][] = "🤢 Monitorear frecuencia de vómito";
                    $recomendaciones['diaria']['salud'][] = "🚨 Si hay sangre, llevar al vet inmediatamente";
                    break;
                    
                case 'diarrea_estrenimiento': // Diarrea/Estreñimiento
                    if ($info['severidad'] === 'severo' || $info['severidad'] === 'moderado') {
                        $recomendaciones['diaria']['alimentacion'][] = "🥣 Dieta blanda: arroz, pollo hervido, calabaza";
                        $recomendaciones['diaria']['alimentacion'][] = "🚫 Evitar lacteos y comida grasosa";
                    } else {
                        $recomendaciones['diaria']['alimentacion'][] = "💧 Aumentar intake de agua";
                        $recomendaciones['diaria']['alimentacion'][] = "🥬 Agregar fibra a la dieta (calabaza, vegetales)";
                    }
                    $recomendaciones['diaria']['salud'][] = "🔍 Observar color y consistencia de las heces";
                    $recomendaciones['diaria']['salud'][] = "💊 Probiotics pueden ayudar";
                    $recomendaciones['diaria']['salud'][] = "🚨 Si dura más de 3 días o hay sangre, al vet";
                    break;
                    
                case 'tos_disnea': // Tos/Dificultad respiratoria
                    $recomendaciones['diaria']['salud'][] = "🌬️ Mantener ambiente libre de humo y polvo";
                    $recomendaciones['diaria']['salud'][] = "🚫 Evitar ejercicio intenso";
                    $recomendaciones['diaria']['salud'][] = "💧 Asegurar buena hidratación";
                    $recomendaciones['diaria']['salud'][] = "🔍 Observar si tiene dificultad para respirar";
                    $recomendaciones['diaria']['salud'][] = "🚨 Dificultad respiratoria = EMERGENCIA, ir al vet";
                    $recomendaciones['semanal']['veterinario'][] = "🫁 Radiografía de tórax puede ser necesaria";
                    break;
            }
            
            if ($info['veterinario'] === 'no') {
                $recomendaciones['mensual']['veterinario'][] = "⚠️ AGENDAR CITA: Revisión de $sintomaLabel ($info[duracion])";
            }
        }
    }

    // === HIGIENE GENERAL (Fallback) ===
    if (empty($recomendaciones['diaria']['higiene'])) {
        $recomendaciones['diaria']['higiene'][] = 'Revisión rápida de ojos y oídos';
        $recomendaciones['semanal']['higiene'][] = 'Cepillado de pelaje';
    }

    // === VETERINARIO GENERAL ===
    if ($edadAnios >= 7) {
         $recomendaciones['mensual']['veterinario'][] = 'Chequeo geriátrico semestral';
    } else {
         $recomendaciones['mensual']['veterinario'][] = 'Preventivos mensuales (pulgas/garrapatas)';
    }

    // Obtener veterinarias (solo si hay alertas o síntomas sin ver)
    $necesitaVet = !empty($sintomas) || !empty($condiciones) || $bcs <= 2 || $bcs >= 8;
    // Extraer claves de síntomas
    $clavesSintomas = array_keys($sintomas);
    $recomendaciones['veterinarias_recomendadas'] = $necesitaVet ? obtenerVeterinariasRecomendadas($clavesSintomas) : [];

    return $recomendaciones;
}

/**
 * Calcular nivel de alerta simplificado
 */
/**
 * Calcular nivel de alerta mejorado
 */
function calcularNivelAlerta($datos, $mascota) {
    // Por defecto VERDE
    $alerta = [
        'nivel' => 'verde',
        'titulo' => 'Estado Saludable',
        'mensaje' => 'No se detectaron problemas urgentes. Mantén los cuidados preventivos.',
        'accion' => 'Continuar con el plan de salud mensual.'
    ];

    // --- ANÁLISIS DE PESO AUTOMÁTICO ---
    $datosRaza = obtenerDatosRaza($mascota['raza']);
    $pesoActual = floatval($mascota['peso'] ?? 0);
    $pesoMin = floatval($datosRaza['peso_min'] ?? 0);
    $pesoMax = floatval($datosRaza['peso_max'] ?? 0);
    $porcentajeSobrepeso = 0;
    $porcentajeBajoPeso = 0;

    if ($pesoActual > 0 && $pesoMax > 0 && $pesoActual > $pesoMax) {
        $porcentajeSobrepeso = (($pesoActual - $pesoMax) / $pesoMax) * 100;
    } elseif ($pesoActual > 0 && $pesoMin > 0 && $pesoActual < $pesoMin) {
        $porcentajeBajoPeso = (($pesoMin - $pesoActual) / $pesoMin) * 100;
    }
    
    $bcs = intval($datos['bcs'] ?? 5);
    $apetito = $datos['apetito'] ?? 'normal';
    $horaEjercicio = $datos['hora_ejercicio'] ?? '';
    $condiciones = $datos['condiciones'] ?? [];
    $sintomas = $datos['sintomas'] ?? [];
    
    // Contadores
    $severos = 0;
    $moderados = 0;
    
    foreach ($sintomas as $s) {
        if (($s['severidad'] ?? '') === 'severo') $severos++;
        if (($s['severidad'] ?? '') === 'moderado') $moderados++;
    }

    // Criterios ROJO (Urgencia)
    $esRojo = false;
    if ($severos > 0) $esRojo = true; // Cualquier síntoma severo
    if ($bcs <= 1 || $bcs >= 9) $esRojo = true; // Extremo peso
    if ($apetito === 'muy_bajo') $esRojo = true; // Anorexia posible
    if ($porcentajeSobrepeso > 40) $esRojo = true; // Obesidad severa
    if ($porcentajeBajoPeso > 30) $esRojo = true; // Desnutrición severa

    // Criterios AMARILLO (Precaución)
    $esAmarillo = false;
    if ($moderados > 0) $esAmarillo = true;
    if (!empty($condiciones)) $esAmarillo = true; // Condiciones crónicas requieren monitoreo
    if ($bcs == 2 || $bcs == 3 || $bcs == 7 || $bcs == 8) $esAmarillo = true;
    if ($horaEjercicio === 'tarde') $esAmarillo = true; // Riesgo ambiental
    if ($porcentajeSobrepeso > 20) $esAmarillo = true; // Sobrepeso
    if ($porcentajeBajoPeso > 15) $esAmarillo = true; // Bajo peso

    // Asignar nivel
    if ($esRojo) {
        $alerta = [
            'nivel' => 'rojo',
            'titulo' => 'Atención Veterinaria Requerida',
            'mensaje' => 'Se han detectado indicadores de salud que requieren atención profesional inmediata.',
            'accion' => 'Agenda una consulta veterinaria en las próximas 24 horas.'
        ];
        if ($porcentajeSobrepeso > 40) {
            $alerta['titulo'] = 'Obesidad Severa Detectada';
            $alerta['mensaje'] = 'El sobrepeso de ' . round($porcentajeSobrepeso) . '% es severo y pone en riesgo la salud de tu mascota. Es crucial una evaluación veterinaria para un plan de pérdida de peso seguro.';
            $alerta['accion'] = 'Pide cita con aliados o ve a una vet cercana.';
        }
        if ($porcentajeBajoPeso > 30) {
            $alerta['titulo'] = 'Desnutrición Severa Detectada';
            $alerta['mensaje'] = 'El peso de tu mascota está ' . round($porcentajeBajoPeso) . '% por debajo del mínimo saludable. Es crucial una evaluación veterinaria inmediata.';
            $alerta['accion'] = 'Pide cita con aliados o ve a una vet cercana.';
        }
    } elseif ($esAmarillo) {
        $alerta = [
            'nivel' => 'amarillo',
            'titulo' => 'Precaución y Monitoreo',
            'mensaje' => 'Hay condiciones o síntomas que requieren observación cercana.',
            'accion' => 'Sigue las recomendaciones específicas y consulta si empeora.'
        ];
        if ($porcentajeSobrepeso > 20) {
            $alerta['titulo'] = 'Sobrepeso Detectado';
            $alerta['mensaje'] = 'Se detectó un sobrepeso del ' . round($porcentajeSobrepeso) . '%. Es importante iniciar un plan de control de peso para prevenir problemas de salud a futuro.';
            $alerta['accion'] = 'Sigue las recomendaciones del plan y monitorea el peso semanalmente.';
        }
        if ($porcentajeBajoPeso > 15) {
            $alerta['titulo'] = 'Bajo Peso Detectado';
            $alerta['mensaje'] = 'Se detectó que tu mascota está ' . round($porcentajeBajoPeso) . '% por debajo del peso ideal. Se recomienda ajustar la alimentación.';
            $alerta['accion'] = 'Sigue las recomendaciones de alimentación y monitorea el peso.';
        }
    } else {
        // Nivel VERDE (pero revisamos desviaciones leves para recomendar Premium)
        if ($porcentajeSobrepeso > 0) {
            $alerta['titulo'] = 'Ligero Sobrepeso';
            $alerta['mensaje'] = 'Tu mascota está un ' . round($porcentajeSobrepeso) . '% por encima del promedio ideal. Es buen momento para ajustar la dieta y ejercicio con el Plan Premium.';
        } elseif ($porcentajeBajoPeso > 0) {
            $alerta['titulo'] = 'Peso Bajo el Promedio';
            $alerta['mensaje'] = 'Tu mascota está un ' . round($porcentajeBajoPeso) . '% por debajo del promedio. Un plan de nutrición ayudaría a fortalecerla.';
        }
    }
    
    return $alerta;
}

/**
 * Guardar plan mensual simplificado
 */
function guardarPlanMensual($pdo, $mascotaId, $userId, $datos, $recomendaciones, $alerta) {
    try {
        $mes = date('n');
        $anio = date('Y');
        
        // Verificar si ya existe un plan para este mes
        $stmt = $pdo->prepare("
            SELECT id FROM planes_salud_mensual 
            WHERE mascota_id = ? AND mes = ? AND anio = ?
        ");
        $stmt->execute([$mascotaId, $mes, $anio]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existente) {
            // Calcular health score basado en el nivel de alerta
            $healthInfo = calcularPuntajeSalud($alerta['nivel'] ?? 'verde', $existente['id']);
            $healthScore = $healthInfo['score'];
            
            // Actualizar plan existente
            $stmt = $pdo->prepare("
                UPDATE planes_salud_mensual
                SET datos_json = ?, recomendaciones_json = ?, alertas_json = ?, 
                    nivel_alerta = ?, health_score = ?
                WHERE id = ?
            ");
            $stmt->execute([
                json_encode($datos),
                json_encode($recomendaciones),
                json_encode($alerta),
                $alerta['nivel'] ?? 'verde',
                $healthScore,
                $existente['id']
            ]);
            $planId = $existente['id'];

            // Eliminar tareas antiguas
            $stmt = $pdo->prepare("DELETE FROM plan_salud_mensual_tareas WHERE plan_id = ?");
            $stmt->execute([$planId]);
        } else {
            // Calcular health score inicial basado en el nivel de alerta
            $healthInfo = calcularPuntajeSalud($alerta['nivel'] ?? 'verde');
            $healthScore = $healthInfo['score'];
            
            // Crear nuevo plan
            $stmt = $pdo->prepare("
                INSERT INTO planes_salud_mensual
                (mascota_id, user_id, mes, anio, datos_json, recomendaciones_json, alertas_json, 
                 nivel_alerta, health_score, last_health_update, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $mascotaId,
                $userId,
                $mes,
                $anio,
                json_encode($datos),
                json_encode($recomendaciones),
                json_encode($alerta),
                $alerta['nivel'] ?? 'verde',
                $healthScore
            ]);
            $planId = $pdo->lastInsertId();
        }
        
        // Crear tareas básicas - pasar la fecha de creación del plan
        $tareasCreadas = 0;
        $fechaInicio = date('Y-m-d'); // Usar la fecha actual como inicio del plan
        $tareas = generarTareasBasicas($recomendaciones, $mascotaId, $fechaInicio);
        
        $stmt = $pdo->prepare("
            INSERT INTO plan_salud_mensual_tareas 
            (plan_id, titulo, descripcion, categoria, fecha, hora, completada, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        
        foreach ($tareas as $tarea) {
            $stmt->execute([
                $planId,
                $tarea['titulo'],
                $tarea['descripcion'],
                $tarea['categoria'],
                $tarea['fecha'],
                $tarea['hora']
            ]);
            $tareasCreadas++;
        }
        
        // Registrar actividad del usuario (generó un plan)
        registrarActividadUsuario($userId, 'plan_generated');
 
        return [
            'success' => true,
            'plan_id' => $planId,
            'tareas_creadas' => $tareasCreadas,
            'health_score' => $healthScore ?? null
        ];
        
    } catch (Exception $e) {
        error_log('Error guardando plan: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generar tareas detalladas del plan basadas en recomendaciones por frecuencia
 * @param array $recomendaciones - Las recomendaciones generadas
 * @param int $mascotaId - ID de la mascota
 * @param string $fechaInicio - Fecha de inicio del plan (formato Y-m-d)
 */
function generarTareasBasicas($recomendaciones, $mascotaId, $fechaInicio = null) {
    $tareas = [];
    
    // Usar la fecha de hoy si no se proporciona
    $inicio = $fechaInicio ? date('Y-m-d', strtotime($fechaInicio)) : date('Y-m-d');
    $mes = date('n', strtotime($inicio));
    $anio = date('Y', strtotime($inicio));

    // === TAREAS DIARIAS ===
    // Crear una tarea diaria para cada uno de los 30 días del plan
    if (isset($recomendaciones['diaria'])) {
        // Alimentación diaria - crear para cada día desde la fecha de inicio
        if (!empty($recomendaciones['diaria']['alimentacion'])) {
            for ($i = 0; $i < 30; $i++) {
                $fecha = date('Y-m-d', strtotime("+$i days", strtotime($inicio)));
                // Usar la primera recomendación de alimentación
                $rec = $recomendaciones['diaria']['alimentacion'][0];
                $tareas[] = [
                    'titulo' => 'Alimentación diaria',
                    'descripcion' => $rec,
                    'categoria' => 'alimentacion',
                    'fecha' => $fecha,
                    'hora' => '08:00:00'
                ];
            }
        }

        // Ejercicio diario - crear para cada día
        if (!empty($recomendaciones['diaria']['ejercicio'])) {
            for ($i = 0; $i < 30; $i++) {
                $fecha = date('Y-m-d', strtotime("+$i days", strtotime($inicio)));
                $rec = $recomendaciones['diaria']['ejercicio'][0];
                $tareas[] = [
                    'titulo' => 'Ejercicio diario',
                    'descripcion' => $rec,
                    'categoria' => 'ejercicio',
                    'fecha' => $fecha,
                    'hora' => '17:00:00'
                ];
            }
        }

        // Higiene diaria - crear para cada día
        if (!empty($recomendaciones['diaria']['higiene'])) {
            for ($i = 0; $i < 30; $i++) {
                $fecha = date('Y-m-d', strtotime("+$i days", strtotime($inicio)));
                $rec = $recomendaciones['diaria']['higiene'][0];
                $tareas[] = [
                    'titulo' => 'Higiene diaria',
                    'descripcion' => $rec,
                    'categoria' => 'higiene',
                    'fecha' => $fecha,
                    'hora' => '20:00:00'
                ];
            }
        }

        // Salud diaria - crear para cada día
        if (!empty($recomendaciones['diaria']['salud'])) {
            for ($i = 0; $i < 30; $i++) {
                $fecha = date('Y-m-d', strtotime("+$i days", strtotime($inicio)));
                $rec = $recomendaciones['diaria']['salud'][0];
                $tareas[] = [
                    'titulo' => 'Monitoreo de salud',
                    'descripcion' => $rec,
                    'categoria' => 'salud',
                    'fecha' => $fecha,
                    'hora' => '21:00:00'
                ];
            }
        }
    }

    // === TAREAS SEMANALES ===
    // Crear 4 tareas semanales (una por semana durante 30 días)
    if (isset($recomendaciones['semanal'])) {
        // Alimentación semanal - crear 4 tareas (una por semana)
        if (!empty($recomendaciones['semanal']['alimentacion'])) {
            for ($semana = 0; $semana < 4; $semana++) {
                $fecha = date('Y-m-d', strtotime("+{$semana} weeks", strtotime($inicio)));
                foreach ($recomendaciones['semanal']['alimentacion'] as $rec) {
                    $tareas[] = [
                        'titulo' => 'Revisión semanal alimentación',
                        'descripcion' => $rec,
                        'categoria' => 'alimentacion',
                        'fecha' => $fecha,
                        'hora' => '09:00:00'
                    ];
                }
            }
        }

        // Ejercicio semanal
        if (!empty($recomendaciones['semanal']['ejercicio'])) {
            for ($semana = 0; $semana < 4; $semana++) {
                $fecha = date('Y-m-d', strtotime("+{$semana} weeks", strtotime($inicio)));
                foreach ($recomendaciones['semanal']['ejercicio'] as $rec) {
                    $tareas[] = [
                        'titulo' => 'Ejercicio semanal',
                        'descripcion' => $rec,
                        'categoria' => 'ejercicio',
                        'fecha' => $fecha,
                        'hora' => '16:00:00'
                    ];
                }
            }
        }

        // Higiene semanal
        if (!empty($recomendaciones['semanal']['higiene'])) {
            for ($semana = 0; $semana < 4; $semana++) {
                $fecha = date('Y-m-d', strtotime("+{$semana} weeks", strtotime($inicio)));
                foreach ($recomendaciones['semanal']['higiene'] as $rec) {
                    $tareas[] = [
                        'titulo' => 'Higiene semanal',
                        'descripcion' => $rec,
                        'categoria' => 'higiene',
                        'fecha' => $fecha,
                        'hora' => '10:00:00'
                    ];
                }
            }
        }

        // Salud semanal
        if (!empty($recomendaciones['semanal']['salud'])) {
            for ($semana = 0; $semana < 4; $semana++) {
                $fecha = date('Y-m-d', strtotime("+{$semana} weeks", strtotime($inicio)));
                foreach ($recomendaciones['semanal']['salud'] as $rec) {
                    $tareas[] = [
                        'titulo' => 'Control semanal de salud',
                        'descripcion' => $rec,
                        'categoria' => 'salud',
                        'fecha' => $fecha,
                        'hora' => '11:00:00'
                    ];
                }
            }
        }
    }

    // === TAREAS MENSUALES ===
    // Crear tareas mensuales (una vez al mes)
    if (isset($recomendaciones['mensual'])) {
        // Alimentación mensual - día 15 del mes
        if (!empty($recomendaciones['mensual']['alimentacion'])) {
            $fecha = date('Y-m-15', strtotime($inicio));
            // Si la fecha ya pasó, ponerla para el próximo mes
            if (strtotime($fecha) < strtotime($inicio)) {
                $fecha = date('Y-m-15', strtotime("+1 month", strtotime($inicio)));
            }
            foreach ($recomendaciones['mensual']['alimentacion'] as $rec) {
                $tareas[] = [
                    'titulo' => 'Revisión mensual alimentación',
                    'descripcion' => $rec,
                    'categoria' => 'alimentacion',
                    'fecha' => $fecha,
                    'hora' => '09:00:00'
                ];
            }
        }

        // Ejercicio mensual - día 20
        if (!empty($recomendaciones['mensual']['ejercicio'])) {
            $fecha = date('Y-m-20', strtotime($inicio));
            if (strtotime($fecha) < strtotime($inicio)) {
                $fecha = date('Y-m-20', strtotime("+1 month", strtotime($inicio)));
            }
            foreach ($recomendaciones['mensual']['ejercicio'] as $rec) {
                $tareas[] = [
                    'titulo' => 'Evaluación mensual ejercicio',
                    'descripcion' => $rec,
                    'categoria' => 'ejercicio',
                    'fecha' => $fecha,
                    'hora' => '17:00:00'
                ];
            }
        }

        // Higiene mensual - día 25
        if (!empty($recomendaciones['mensual']['higiene'])) {
            $fecha = date('Y-m-25', strtotime($inicio));
            if (strtotime($fecha) < strtotime($inicio)) {
                $fecha = date('Y-m-25', strtotime("+1 month", strtotime($inicio)));
            }
            foreach ($recomendaciones['mensual']['higiene'] as $rec) {
                $tareas[] = [
                    'titulo' => 'Higiene mensual',
                    'descripcion' => $rec,
                    'categoria' => 'higiene',
                    'fecha' => $fecha,
                    'hora' => '10:00:00'
                ];
            }
        }

        // Salud mensual - día 28
        if (!empty($recomendaciones['mensual']['salud'])) {
            $fecha = date('Y-m-28', strtotime($inicio));
            if (strtotime($fecha) < strtotime($inicio)) {
                $fecha = date('Y-m-28', strtotime("+1 month", strtotime($inicio)));
            }
            foreach ($recomendaciones['mensual']['salud'] as $rec) {
                $tareas[] = [
                    'titulo' => 'Evaluación mensual de salud',
                    'descripcion' => $rec,
                    'categoria' => 'salud',
                    'fecha' => $fecha,
                    'hora' => '11:00:00'
                ];
            }
        }

        // Veterinario mensual
        if (!empty($recomendaciones['mensual']['veterinario'])) {
            foreach ($recomendaciones['mensual']['veterinario'] as $rec) {
                // Poner la cita veterinaria el día 10 del mes siguiente
                $fecha = date('Y-m-10', strtotime("+1 month", strtotime($inicio)));
                
                if (strpos($rec, 'CONSULTA') !== false || strpos($rec, 'URGENTE') !== false) {
                    $tareas[] = [
                        'titulo' => 'Consulta veterinaria urgente',
                        'descripcion' => $rec,
                        'categoria' => 'veterinario',
                        'fecha' => $fecha,
                        'hora' => '10:00:00'
                    ];
                } elseif (strpos($rec, 'Vacunación') !== false || strpos($rec, 'Chequeo') !== false) {
                    $tareas[] = [
                        'titulo' => 'Cita veterinaria programada',
                        'descripcion' => $rec,
                        'categoria' => 'veterinario',
                        'fecha' => $fecha,
                        'hora' => '10:00:00'
                    ];
                } else {
                    $tareas[] = [
                        'titulo' => 'Seguimiento veterinario',
                        'descripcion' => $rec,
                        'categoria' => 'veterinario',
                        'fecha' => $fecha,
                        'hora' => '10:00:00'
                    ];
                }
            }
        }
    }

    return $tareas;
}

/**
 * Obtener plan de salud mensual activo
 */
function obtenerPlanSaludMensual($mascotaId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM planes_salud_mensual 
            WHERE mascota_id = ? 
            ORDER BY anio DESC, mes DESC 
            LIMIT 1
        ");
        $stmt->execute([$mascotaId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            return null;
        }
        
        $plan['datos'] = json_decode($plan['datos_json'], true);
        $plan['recomendaciones'] = json_decode($plan['recomendaciones_json'], true);
        $plan['alertas'] = json_decode($plan['alertas_json'], true);
        
        $stmtTareas = $pdo->prepare("
            SELECT * FROM plan_salud_mensual_tareas 
            WHERE plan_id = ? 
            ORDER BY fecha, hora
        ");
        $stmtTareas->execute([$plan['id']]);
        $plan['tareas'] = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);
        
        return $plan;
    } catch (Exception $e) {
        error_log('Error obteniendo plan: ' . $e->getMessage());
        return null;
    }
}

/**
 * Obtener tareas del plan para el calendario
 */
function obtenerTareasPlanCalendario($mascotaId, $mes = null, $anio = null) {
    global $pdo;
    
    try {
        $mes = $mes ?: date('n');
        $anio = $anio ?: date('Y');
        
        $stmt = $pdo->prepare("
            SELECT t.*, p.mascota_id, m.nombre as mascota_nombre
            FROM plan_salud_mensual_tareas t
            JOIN planes_salud_mensual p ON t.plan_id = p.id
            JOIN mascotas m ON p.mascota_id = m.id
            WHERE p.mascota_id = ? 
            AND MONTH(t.fecha) = ? 
            AND YEAR(t.fecha) = ?
            ORDER BY t.fecha, t.hora
        ");
        $stmt->execute([$mascotaId, $mes, $anio]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error obteniendo tareas: ' . $e->getMessage());
        return [];
    }
}

/**
 * Generar rutina personalizada aplanada para visualización
 */
function generarRutinaPersonalizada($recomendaciones) {
    if (isset($recomendaciones['recomendaciones'])) {
        // En caso de que se pase el array completo del plan
        $recomendaciones = $recomendaciones['recomendaciones'];
    }

    $rutina = [
        'diaria' => [],
        'semanal' => [],
        'mensual' => []
    ];

    // Diaria
    if (isset($recomendaciones['diaria'])) {
        foreach ($recomendaciones['diaria'] as $categoria => $items) {
            foreach ($items as $item) {
                $rutina['diaria'][] = $item;
            }
        }
    }

    // Semanal
    if (isset($recomendaciones['semanal'])) {
        foreach ($recomendaciones['semanal'] as $categoria => $items) {
            foreach ($items as $item) {
                $rutina['semanal'][] = $item;
            }
        }
    }

    // Mensual
    if (isset($recomendaciones['mensual'])) {
        foreach ($recomendaciones['mensual'] as $categoria => $items) {
            foreach ($items as $item) {
                $rutina['mensual'][] = $item;
            }
        }
    }

    return $rutina;
}

/**
 * Calcula un puntaje de salud (Health Score) persistente basado en datos reales.
 * El score se guarda en la base de datos y mejora con el tiempo basado en actividad del usuario.
 */
function calcularPuntajeSalud($nivel, $planId = null) {
    global $pdo;
    
    // Normalizar nivel (puede venir como 'verde', 'amarillo', 'rojo' o nulo)
    $nivel = strtolower($nivel ?? 'verde');
    
    // Si tenemos un planId, intentar obtener el score guardado
    if ($planId) {
        try {
            $stmt = $pdo->prepare("SELECT health_score FROM planes_salud_mensual WHERE id = ?");
            $stmt->execute([$planId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['health_score'] > 0) {
                // Retornar el score persistente
                $score = intval($result['health_score']);
                return [
                    'score' => $score,
                    'level' => $score >= 80 ? 3 : ($score >= 50 ? 2 : 1),
                    'max_xp' => 100,
                    'current_xp' => $score
                ];
            }
        } catch (Exception $e) {
            error_log('Error obteniendo health score: ' . $e->getMessage());
        }
    }
    
    // Si no hay score guardado, calcular uno inicial basado en el nivel de alerta
    $score = 85; // Default
    $level = 3;
    
    switch ($nivel) {
        case 'rojo':
            $score = rand(30, 40); // Rango bajo para problemas serios
            $level = 1;
            break;
        case 'amarillo':
            $score = rand(60, 70); // Rango medio para precaución
            $level = 2;
            break;
        case 'verde':
            $score = rand(85, 95); // Rango alto para salud buena
            $level = 3;
            break;
        default:
            $score = 85;
            $level = 3;
    }
    
    return [
        'score' => $score,
        'level' => $level,
        'max_xp' => 100,
        'current_xp' => $score 
    ];
}

/**
 * Verificar si el usuario cumple con el requisito de actividad semanal
 * Debe conectarse al menos 5 de 7 días Y no tener gap de 3+ días consecutivos
 */
function verificarActividadSemanal($userId) {
    global $pdo;
    
    try {
        // Obtener días únicos de actividad en los últimos 7 días
        $stmt = $pdo->prepare("
            SELECT DISTINCT activity_date 
            FROM user_activity_log 
            WHERE user_id = ? 
            AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY activity_date ASC
        ");
        $stmt->execute([$userId]);
        $activeDays = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $diasActivos = count($activeDays);
        
        // Requisito 1: Al menos 5 de 7 días
        if ($diasActivos < 5) {
            return [
                'califica' => false,
                'razon' => "Solo $diasActivos de 7 días activos (necesita 5)",
                'dias_activos' => $diasActivos
            ];
        }
        
        // Requisito 2: No tener gap de 3+ días consecutivos
        if (count($activeDays) > 1) {
            for ($i = 1; $i < count($activeDays); $i++) {
                $fecha1 = new DateTime($activeDays[$i - 1]);
                $fecha2 = new DateTime($activeDays[$i]);
                $diff = $fecha1->diff($fecha2)->days;
                
                if ($diff >= 3) {
                    return [
                        'califica' => false,
                        'razon' => "Gap de $diff días consecutivos sin actividad",
                        'dias_activos' => $diasActivos
                    ];
                }
            }
        }
        
        // Cumple ambos requisitos
        return [
            'califica' => true,
            'razon' => "$diasActivos días activos, sin gaps largos",
            'dias_activos' => $diasActivos
        ];
        
    } catch (Exception $e) {
        error_log('Error verificando actividad semanal: ' . $e->getMessage());
        return [
            'califica' => false,
            'razon' => 'Error al verificar actividad',
            'dias_activos' => 0
        ];
    }
}

/**
 * Registrar actividad del usuario para el día actual
 */
function registrarActividadUsuario($userId, $activityType = 'login') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_log (user_id, activity_date, activity_type)
            VALUES (?, CURDATE(), ?)
            ON DUPLICATE KEY UPDATE activity_type = VALUES(activity_type)
        ");
        $stmt->execute([$userId, $activityType]);
        return true;
    } catch (Exception $e) {
        error_log('Error registrando actividad: ' . $e->getMessage());
        return false;
    }
}

/**
 * Actualizar health score basado en actividad semanal
 * +3% si cumple 5/7 días sin gap de 3+ días
 */
function actualizarHealthScoreSemanal($planId, $userId) {
    global $pdo;
    
    try {
        // Obtener plan actual
        $stmt = $pdo->prepare("
            SELECT health_score, last_health_update 
            FROM planes_salud_mensual 
            WHERE id = ?
        ");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            return ['success' => false, 'message' => 'Plan no encontrado'];
        }
        
        $currentScore = intval($plan['health_score'] ?? 85);
        $lastUpdate = $plan['last_health_update'];
        
        // Verificar si ya pasó una semana desde la última actualización
        if ($lastUpdate) {
            $lastUpdateDate = new DateTime($lastUpdate);
            $now = new DateTime();
            $daysSinceUpdate = $lastUpdateDate->diff($now)->days;
            
            if ($daysSinceUpdate < 7) {
                return [
                    'success' => false,
                    'message' => 'Aún no ha pasado una semana',
                    'days_remaining' => 7 - $daysSinceUpdate
                ];
            }
        }
        
        // Verificar actividad semanal
        $actividad = verificarActividadSemanal($userId);
        
        if (!$actividad['califica']) {
            return [
                'success' => false,
                'message' => $actividad['razon'],
                'score_unchanged' => $currentScore
            ];
        }
        
        // Incrementar 3% (máximo 100)
        $incremento = 3;
        $newScore = min(100, $currentScore + $incremento);
        
        // Actualizar en base de datos
        $stmt = $pdo->prepare("
            UPDATE planes_salud_mensual 
            SET health_score = ?, last_health_update = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newScore, $planId]);
        
        return [
            'success' => true,
            'message' => '¡Health Score mejorado!',
            'old_score' => $currentScore,
            'new_score' => $newScore,
            'incremento' => $incremento,
            'dias_activos' => $actividad['dias_activos']
        ];
        
    } catch (Exception $e) {
        error_log('Error actualizando health score: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error al actualizar'];
    }
}
