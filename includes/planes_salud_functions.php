<?php
require_once 'db.php';
require_once 'enfermedades_data.php';

// ==========================================
// GESTIÓN DE DATOS MÉDICOS
// ==========================================

function guardarPeso($mascotaId, $peso, $fecha, $notas = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO peso_historial (mascota_id, peso, fecha, notas) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$mascotaId, $peso, $fecha, $notas]);
}

function guardarVacuna($mascotaId, $nombre, $fecha, $proximaFecha = null, $veterinaria = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO mascotas_salud (mascota_id, tipo, nombre_evento, fecha_realizado, proxima_fecha, veterinaria_id) VALUES (?, 'vacuna', ?, ?, ?, ?)");
    return $stmt->execute([$mascotaId, $nombre, $fecha, $proximaFecha, $veterinaria]);
}

function guardarHistorialMedico($datos) {
    global $pdo;
    $sql = "INSERT INTO historial_medico (mascota_id, fecha, tipo, motivo, diagnostico, tratamiento, veterinario, clinica, notas) 
            VALUES (:mascota_id, :fecha, :tipo, :motivo, :diagnostico, :tratamiento, :veterinario, :clinica, :notas)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($datos);
}

// ==========================================
// GESTIÓN DE PLAN DE SALUD
// ==========================================

function obtenerPlanSalud($mascotaId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM planes_salud WHERE mascota_id = ? AND activo = 1 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$mascotaId]);
    $plan = $stmt->fetch();
    
    if ($plan) {
        $stmt = $pdo->prepare("SELECT * FROM recordatorios_plan WHERE plan_id = ? ORDER BY fecha_programada ASC");
        $stmt->execute([$plan['id']]);
        $plan['recordatorios'] = $stmt->fetchAll();
    }
    
    return $plan;
}

function generarPlanSaludIA($mascotaId, $overrides = [], $save = true) {
    global $pdo;
    require_once __DIR__ . '/razas_data.php';
    
    // 1. Obtener datos completos de la mascota
    $stmt = $pdo->prepare("SELECT * FROM mascotas WHERE id = ?");
    $stmt->execute([$mascotaId]);
    $mascota = $stmt->fetch();
    
    if (!$mascota) return ['success' => false, 'error' => 'Mascota no encontrada'];

    // 2. DETERMINAR ETAPA DE VIDA
    // Aplicar overrides si vienen desde el formulario
    if (!empty($overrides['edad'])) {
        $mascota['edad'] = $overrides['edad'];
    }
    if (!empty($overrides['raza'])) {
        $mascota['raza'] = $overrides['raza'];
    }
    if (!empty($overrides['nivel_actividad'])) {
        $mascota['nivel_actividad'] = $overrides['nivel_actividad'];
    }
    if (!empty($overrides['tipo_alimento'])) {
        $mascota['tipo_alimento'] = $overrides['tipo_alimento'];
    }

    $edad = floatval($mascota['edad'] ?? 0);
    $etapa = 'Adulto';
    $rango_etapa = '1 a 7 años';
    
    if ($edad < 1) {
        $etapa = 'Cachorro';
        $rango_etapa = '0 a 12 meses';
    } elseif ($edad >= 7) {
        $etapa = 'Senior';
        $rango_etapa = '7+ años';
    }

    // 3. OBTENER DATOS DE RAZA Y COMPARAR PESO
    $datosRaza = obtenerDatosRaza($mascota['raza']);

    // Peso: si enviaron override lo usamos, si no, usamos historial o campo mascota
    if (!empty($overrides['peso'])) {
        $pesoActual = floatval($overrides['peso']);
    } else {
        $pesoActual = floatval(obtenerUltimoPeso($mascotaId) ?? $mascota['peso'] ?? 0);
    }
    $pesoPromedio = floatval($datosRaza['peso_promedio'] ?? (($datosRaza['peso_min'] + $datosRaza['peso_max']) / 2));
    $pesoMin = floatval($datosRaza['peso_min']);
    $pesoMax = floatval($datosRaza['peso_max']);
    
    // Calcular estado de peso
    $estadoPeso = 'ÓPTIMO';
    $porcentajeRango = 100;
    
    if ($pesoActual < $pesoMin) {
        $estadoPeso = 'POR DEBAJO DEL RANGO (Delgadez)';
        $porcentajeRango = round(($pesoActual / $pesoPromedio) * 100, 1);
    } elseif ($pesoActual > $pesoMax) {
        $estadoPeso = 'POR ENCIMA DEL RANGO (Sobrepeso)';
        $porcentajeRango = round(($pesoActual / $pesoPromedio) * 100, 1);
    } else {
        $porcentajeRango = round(($pesoActual / $pesoPromedio) * 100, 1);
    }

    // Detectar síntomas o condiciones desde overrides para ajustar clasificación de salud
    $sintomasRaw = $overrides['sintomas'] ?? '';
    $condicionesRaw = $overrides['condiciones'] ?? '';
    $enfermedadesRaw = $overrides['enfermedades'] ?? '';
    $sintomasTxt = strtolower(trim($sintomasRaw));
    $condTxt = strtolower(trim($condicionesRaw));
    $enfermedadesTxt = strtolower(trim($enfermedadesRaw));

    // 6. DETECTAR ENFERMEDADES Y AJUSTAR PLANES (MOVED EARLIER)
    $enfermedades_detectadas = detectarEnfermedades($sintomasRaw, $condicionesRaw);
    // Agregar enfermedades seleccionadas explícitamente en el formulario
    if (!empty($enfermedadesRaw)) {
        $enfermedades_seleccionadas = array_map('trim', explode(',', $enfermedadesRaw));
        $enfermedades_detectadas = array_unique(array_merge($enfermedades_detectadas, $enfermedades_seleccionadas));
    }

    $severeKeywords = ['sangrado','convuls','dificultad para respirar','respira','colapso','paralisi','vómito persistente','vómito con sangre','diarrea con sangre','intoxic','inconsciente'];
    $mildKeywords = ['tos','letargo','pérdida de apetito','vómito','diarrea','cojera','picor','picazón','rascado','secreción ocular','secreción nasal'];

    $estadoSaludOverride = null;
    foreach ($severeKeywords as $kw) {
        if ($kw && strpos($sintomasTxt, $kw) !== false) { $estadoSaludOverride = 'grave'; break; }
    }
    if (!$estadoSaludOverride) {
        foreach ($mildKeywords as $kw) {
            if ($kw && strpos($sintomasTxt, $kw) !== false) { $estadoSaludOverride = 'revision'; break; }
        }
    }
    // Si hay condiciones crónicas conocidas, pasar a 'revision'
    if (!$estadoSaludOverride && $condTxt) {
        $chronicKeys = ['diabetes','artritis','alerg','hipotiroid','epilepsia','cardi'];
        foreach ($chronicKeys as $ck) {
            if (strpos($condTxt, $ck) !== false) { $estadoSaludOverride = 'revision'; break; }
        }
    }

    if ($estadoSaludOverride) {
        $mascota['estado_salud'] = $estadoSaludOverride; // override temporal para generar notas
    }

    // 4. CALCULAR PLAN DE ALIMENTACIÓN
    // Fórmula: 2-3% del peso corporal según etapa y actividad
    $nivelActividad = strtolower($mascota['nivel_actividad'] ?? 'medio');
    
    $porcentajeAlimentacion = 0;
    if ($etapa === 'Cachorro') {
        $porcentajeAlimentacion = 0.035; // Cachorros comen más: 3.5%
        $frecuenciaComida = '3-4 raciones diarias';
        $detalleFrequencia = 'Desayuno, almuerzo, merienda y cena';
    } elseif ($etapa === 'Senior') {
        $porcentajeAlimentacion = 0.022; // Seniors comen menos: 2.2%
        $frecuenciaComida = '2 raciones diarias (porciones controladas)';
        $detalleFrequencia = 'Mañana y tarde, preferiblemente a las mismas horas';
    } else { // Adulto
        if ($nivelActividad === 'alto') {
            $porcentajeAlimentacion = 0.030; // 3%
        } elseif ($nivelActividad === 'bajo') {
            $porcentajeAlimentacion = 0.020; // 2%
        } else {
            $porcentajeAlimentacion = 0.025; // 2.5%
        }
        $frecuenciaComida = '2 raciones diarias';
        $detalleFrequencia = 'Mañana y tarde, con al menos 8 horas entre comidas';
    }

    // Clasificación fisiológica por tamaño/raza
    $razaNombreLower = strtolower($mascota['raza'] ?? '');
    $tamanoRaza = $datosRaza['tamano'] ?? 'mediano';
    $tipoFisiologico = 'mediano';

    // Ajustar clasificación por peso real para mayor precisión
    if ($pesoActual < 10) {
        $tipoFisiologico = 'pequeño';
    } elseif ($pesoActual > 25) {
        $tipoFisiologico = 'grande';
    } else {
        $tipoFisiologico = 'mediano';
    }

    // Override específico para razas conocidas
    if (strpos($razaNombreLower, 'poodle') !== false) {
        if ($pesoActual >= 15) {
            $tipoFisiologico = 'mediano'; // Poodle estándar
        } elseif ($pesoActual >= 7) {
            $tipoFisiologico = 'pequeño'; // Poodle mediano
        } else {
            $tipoFisiologico = 'pequeño'; // Poodle toy
        }
    }

    if (strpos($tamanoRaza, 'pequeno') !== false || strpos($tamanoRaza, 'peque') !== false) $tipoFisiologico = 'pequeño';
    if (strpos($tamanoRaza, 'grande') !== false || strpos($tamanoRaza, 'gigante') !== false) $tipoFisiologico = 'grande';
    // Detectar razas braquicéfalas por nombre común
    $isBraquicefalo = preg_match('/bulldog|pug|braquicef|mops|buldog/i', $razaNombreLower);

    // Ajustes por tipo fisiológico
    $porcentajeAlimentacionPercent = round($porcentajeAlimentacion * 100, 1);
    $ajustePorcentaje = 0;
    $notaAjuste = '';
    // Pequeños suelen necesitar proporción ligeramente mayor, grandes menor proporción por kg
    if ($tipoFisiologico === 'pequeño') {
        $porcentajeAlimentacionPercent = round($porcentajeAlimentacionPercent * 1.08, 1);
    } elseif ($tipoFisiologico === 'grande') {
        $porcentajeAlimentacionPercent = round($porcentajeAlimentacionPercent * 0.92, 1);
    }
    if ($isBraquicefalo) {
        // Evitar ejercicio intenso y priorizar raciones más fraccionadas
        $notaAjuste .= "\n⚠️ Nota: Razas braquicéfalas requieren fraccionar las comidas y evitar ejercicio vigoroso en calor.";
    }
    if ($estadoPeso === 'POR DEBAJO DEL RANGO (Delgadez)') {
        $ajustePorcentaje = 15; // aumentar 15%
        $notaAjuste = "\n⬆️ AJUSTE: Aumentar aproximadamente {$ajustePorcentaje}% sobre el porcentaje recomendado para recuperación gradual de peso.";
    } elseif ($estadoPeso === 'POR ENCIMA DEL RANGO (Sobrepeso)') {
        $ajustePorcentaje = -15; // reducir 15%
        $notaAjuste = "\n⬇️ AJUSTE: Reducir aproximadamente " . abs($ajustePorcentaje) . "% sobre el porcentaje recomendado y aumentar ejercicio para control de peso.";
    }

    $ajusteTexto = $ajustePorcentaje === 0 ? 'Sin ajuste recomendado' : (($ajustePorcentaje > 0) ? "+{$ajustePorcentaje}%" : "{$ajustePorcentaje}%");

    // Mensaje según estado de salud general
    $estadoSalud = strtolower($mascota['estado_salud'] ?? 'excelente');
    $notaSalud = '';
    if ($estadoSalud === 'excelente') {
        $notaSalud = "Mascota en buen estado de salud. Este plan se enfoca en mantener peso ideal, alimentación adecuada y energía balanceada.";
    } elseif ($estadoSalud === 'revision' || $estadoSalud === 'regular') {
        $notaSalud = "Se observan signos leves o cambios en el estado. Se recomienda observación estrecha y visitar a un veterinario aliado si persisten o empeoran los síntomas. Ajustes realizados al plan para mayor precaución.";
    } elseif ($estadoSalud === 'grave') {
        $notaSalud = "Se han detectado signos que requieren atención inmediata. Clasificado como URGENCIA: acudir a un veterinario de forma prioritaria. Este plan ofrece medidas de soporte pero NO sustituye la atención profesional.";
    }

    $nombreMascota = htmlspecialchars($mascota['nombre']);

    // Mensajes personalizados con el nombre y detección de energía
    $msgEstadoPeso = '';
    if ($estadoPeso === 'POR ENCIMA DEL RANGO (Sobrepeso)') {
        $msgEstadoPeso = "Debido a que {$nombreMascota} está por encima del rango de peso saludable para su raza, se recomienda reducir calorías y aumentar actividad gradual.";
    } elseif ($estadoPeso === 'POR DEBAJO DEL RANGO (Delgadez)') {
        $msgEstadoPeso = "Debido a que {$nombreMascota} está por debajo del rango de peso saludable, se recomienda aumentar la ingesta gradualmente y seguimiento veterinario si persiste.";
    }

    // Comparar energía de raza vs mascota
    $energiaRaza = $datosRaza['energia'] ?? 'media';
    $nivelMascota = strtolower($mascota['nivel_actividad'] ?? 'medio');
    $msgEnergia = '';
    if (in_array($energiaRaza, ['muy_alta','alta']) && in_array($nivelMascota, ['bajo','medio']) ) {
        $msgEnergia = "Aunque la raza suele ser energética, {$nombreMascota} muestra un nivel de actividad menor; incorporar estímulos mentales y actividad controlada aumentará bienestar.";
    } elseif (in_array($energiaRaza, ['baja']) && in_array($nivelMascota, ['alto','muy_alto'])) {
        $msgEnergia = "La raza tiende a tener menor energía; si {$nombreMascota} es muy activo, vigila el sobreesfuerzo y adapta rutinas para evitar lesiones.";
    }

    $planAlimentacion = "📍 PLAN DE ALIMENTACIÓN MENSUAL\n" .
        "Mascota: {$nombreMascota}\n" .
        "Etapa: $etapa ($rango_etapa)\n" .
        "Tipo fisiológico: {$tipoFisiologico}" . ($isBraquicefalo ? " (braquicéfalo)" : "") . "\n" .
        "Frecuencia: $frecuenciaComida\n" .
        "Detalle: $detalleFrequencia\n" .
        "Porcentaje recomendado: {$porcentajeAlimentacionPercent}% del peso corporal\n" .
        "Ajuste sugerido: {$ajusteTexto}." .
        $notaAjuste . "\n\n" .
        ($msgEstadoPeso ? ($msgEstadoPeso . "\n") : "") .
        ($msgEnergia ? ($msgEnergia . "\n") : "") .
        "\n💡 RECOMENDACIONES PRINCIPALES:\n" .
        "• Usar alimento de buena calidad, apropiado para la etapa de vida (considerar fórmula senior para mascotas mayores de 7 años)\n" .
        "• Fraccionar las raciones para razas braquicéfalas o según tolerancia\n" .
        "• Mantener agua fresca disponible\n" .
        "• Transición de alimento en 7-10 días si se cambia de dieta\n" .
        "• Monitorear el peso semanalmente y ajustar según progreso\n" .
        "• Si hay picor o problemas de piel, considerar suplementos con omega-3 y ácidos grasos\n" .
        "• Para problemas articulares, incluir glucosamina y condroitina en la dieta\n" .
        "• Si hay síntomas nuevos o empeoramiento, contactar a un veterinario aliado" .
        "\n\nNOTA: " . $notaSalud;

    // Si el usuario especificó una marca comercial en overrides, añadir nota personalizada
    if (!empty($overrides['marca_comercial'])) {
        $marca = trim($overrides['marca_comercial']);
        if (!empty($marca)) {
            $planAlimentacion .= "\n\n🔖 Marca preferida detectada: " . htmlspecialchars($marca) . ".\nRecomendación: seguir las guías de porciones del fabricante y ajustar según el peso y etapa de vida indicados arriba. Si la marca tiene fórmulas para condiciones específicas (por ejemplo control de peso, digestión sensible), considerar esas opciones bajo supervisión veterinaria.";
        }
    }

    // 5. PLAN DE EJERCICIO
    $tiempoEjercicio = "45-60";
    $tipoEjercicio = "Caminatas activas, juegos de búsqueda y entrenamiento básico";
    $detalleEjercicio = "Dividir en 2-3 sesiones de actividad durante el día";
    $intensidad = 'MEDIA';

    if ($nivelActividad === 'bajo') {
        $tiempoEjercicio = "20-30";
        $tipoEjercicio = "Paseos olfativos lentos, juegos suaves, estimulación mental baja";
        $detalleEjercicio = "Paseos cortos, preferiblemente en horarios frescos";
        $intensidad = 'BAJA';
    } elseif ($nivelActividad === 'alto' || in_array($datosRaza['energia'] ?? '', ['muy_alta', 'alta'])) {
        $tiempoEjercicio = "60-90";
        $tipoEjercicio = "Actividad cardiovascular intensa (correr, saltos controlados), juegos dinámicos";
        $detalleEjercicio = "Múltiples sesiones diarias, incluir actividades que desafíen físicamente";
        $intensidad = 'ALTA';
    }

    // Ajustar ejercicio por etapa Y enfermedades detectadas
    if ($etapa === 'Cachorro') {
        $tiempoEjercicio = "20-30";
        $notas_ejercicio = "(No exceder para no dañar articulaciones en desarrollo)";
        $intensidad = 'BAJA';
    } elseif ($etapa === 'Senior') {
        $tiempoEjercicio = "20-40";
        $notas_ejercicio = "(Actividad suave, sin saltos bruscos)";
        $intensidad = 'BAJA';
    } else {
        $notas_ejercicio = "";
    }

    // Ajustar intensidad si hay enfermedades que lo requieran
    $tiene_condicion_articular = in_array('displasia_cadera', $enfermedades_detectadas) ||
                                strpos(strtolower($sintomasRaw), 'cojera') !== false ||
                                strpos(strtolower($sintomasRaw), 'cojea') !== false ||
                                strpos(strtolower($sintomasRaw), 'dolor') !== false;

    $tiene_problemas_cardiacos = in_array('problemas_cardiacos', $enfermedades_detectadas) ||
                                strpos(strtolower($sintomasRaw), 'fatiga') !== false ||
                                strpos(strtolower($sintomasRaw), 'dificultad para respirar') !== false;

    // Si hay condiciones de salud que requieran ejercicio reducido, ajustar intensidad
    if ($tiene_condicion_articular || $tiene_problemas_cardiacos) {
        $intensidad = 'BAJA';
        $tiempoEjercicio = "15-30";
        $tipoEjercicio = "Caminatas cortas y controladas, juegos mentales suaves";
        $detalleEjercicio = "Sesiones cortas, evitar esfuerzos intensos, saltos y escaleras";
        $notas_ejercicio .= " (Ajustado por condición de salud detectada)";
    }

    // Para senior con posibles problemas articulares, siempre reducir intensidad
    if ($etapa === 'Senior' && ($tiene_condicion_articular || strpos(strtolower($sintomasRaw), 'cojera') !== false)) {
        $intensidad = 'BAJA';
        $tiempoEjercicio = "15-25";
        $tipoEjercicio = "Paseos muy suaves y cortos, estimulación mental sin esfuerzo físico";
        $detalleEjercicio = "Priorizar comodidad sobre ejercicio intenso";
    }

    $planEjercicio = "🏃 PLAN DE EJERCICIO MENSUAL\n" .
        "Intensidad: $intensidad\n" .
        "Tiempo diario: $tiempoEjercicio minutos $notas_ejercicio\n" .
        "Tipo: $tipoEjercicio\n" .
        "Distribución: $detalleEjercicio\n" .
        "Recomendación de raza: " . $datosRaza['recomendacion'] . "\n\n" .
        "💡 SUGERENCIAS:\n" .
        "• Establecer rutina fija para ejercicio (mismo horario)\n" .
        "• Alternar tipos de actividad para evitar aburrimiento\n" .
        "• No ejercitar 1 hora antes/después de comer\n" .
        "• Aumentar ejercicio gradualmente si está bajo de peso\n" .
        "• En clima caluroso, ejercitar en horas frescas";

    if ($isBraquicefalo) {
        $planEjercicio .= "\n\n⚠️ Nota para braquicéfalos: Debido a la conformación facial de {$nombreMascota}, su tolerancia al ejercicio es menor. Evitar ejercicio intenso en climas cálidos, fraccionar sesiones y vigilar signos de intolerancia respiratoria.";
    }

    if ($msgEnergia) {
        $planEjercicio .= "\n" . $msgEnergia;
    }

    // Recomendaciones adicionales según rango de peso y etapa
    if ($estadoPeso === 'POR ENCIMA DEL RANGO (Sobrepeso)') {
        $planAlimentacion .= "\n⚠️ RECOMENDACIÓN POR SOBREPESO: Reducir la porción diaria gradualmente y aumentar paseos diarios. Evitar premios calóricos y consultar plan de pérdida de peso con el veterinario.";
        $planEjercicio .= "\n⚠️ RECOMENDACIÓN POR SOBREPESO: Aumentar paseos a ritmo moderado, iniciar sesiones de juego activo de baja intensidad y seguimiento semanal del peso.";
    }

    // Si es cachorro con baja energía, sugerir juguetes y estimulación mental
    if ($etapa === 'Cachorro' && in_array(($datosRaza['energia'] ?? 'media'), ['baja', 'media']) ) {
        $planEjercicio .= "\n🎾 SUGERENCIA: Como cachorro con energía moderada/baja, añadir juguetes interactivos (rompecabezas de comida, juguetes de buscar) y sesiones cortas de estimulación mental para fomentar actividad sin forzar articulaciones.";
    }

    // 6. DETECTAR ENFERMEDADES Y AJUSTAR PLANES
    $enfermedades_detectadas = detectarEnfermedades($sintomasRaw, $condicionesRaw);
    // Agregar enfermedades seleccionadas explícitamente en el formulario
    if (!empty($enfermedadesRaw)) {
        $enfermedades_seleccionadas = array_map('trim', explode(',', $enfermedadesRaw));
        $enfermedades_detectadas = array_unique(array_merge($enfermedades_detectadas, $enfermedades_seleccionadas));
    }
    $recomendaciones_prioritarias = obtenerRecomendacionesPrioritarias($enfermedades_detectadas);

    $urgencias = $recomendaciones_prioritarias['urgencias'];
    $enfermedades_graves = $recomendaciones_prioritarias['graves'];
    $enfermedades_leves = $recomendaciones_prioritarias['leves'];

    // Ajustar planes según enfermedades detectadas
    $ajustes_enfermedad = [];

    // URGENTES - Prioridad máxima
    if (!empty($urgencias)) {
        $ajustes_enfermedad[] = "🚨 URGENCIA VETERINARIA DETECTADA 🚨";
        foreach ($urgencias as $enfermedad) {
            $ajustes_enfermedad[] = "• {$enfermedad['nombre']}: {$enfermedad['sintomas'][0]}";
            $ajustes_enfermedad[] = "  RECOMENDACIONES INMEDIATAS:";
            foreach ($enfermedad['recomendaciones_diarias'] as $rec) {
                $ajustes_enfermedad[] = "  - $rec";
            }
        }
        $ajustes_enfermedad[] = "⚠️ ACUDA INMEDIATAMENTE AL VETERINARIO - NO ESPERE";
    }

    // GRAVES - Ajustes importantes
    if (!empty($enfermedades_graves)) {
        $ajustes_enfermedad[] = "\n🏥 ENFERMEDADES GRAVES DETECTADAS";
        foreach ($enfermedades_graves as $enfermedad) {
            $ajustes_enfermedad[] = "• {$enfermedad['nombre']}: Requiere atención veterinaria";
            $ajustes_enfermedad[] = "  RECOMENDACIONES DIARIAS:";
            foreach ($enfermedad['recomendaciones_diarias'] as $rec) {
                $ajustes_enfermedad[] = "  - $rec";
            }
        }
    }

    // LEVES - Ajustes preventivos
    if (!empty($enfermedades_leves)) {
        $ajustes_enfermedad[] = "\n📋 POSIBLES CONDICIONES LEVES DETECTADAS (REQUIEREN EVALUACIÓN VETERINARIA)";
        foreach ($enfermedades_leves as $enfermedad) {
            $ajustes_enfermedad[] = "• {$enfermedad['nombre']}: Posible condición a evaluar por veterinario";
            $ajustes_enfermedad[] = "  RECOMENDACIONES DIARIAS (MIENTRAS SE EVALÚA):";
            foreach ($enfermedad['recomendaciones_diarias'] as $rec) {
                $ajustes_enfermedad[] = "  - $rec";
            }
        }
    }

    // Aplicar ajustes específicos según enfermedades
    $ajuste_alimentacion_enfermedad = "";
    $ajuste_ejercicio_enfermedad = "";

    foreach ($enfermedades_detectadas as $enf_key) {
        $datos_enf = obtenerDatosEnfermedad($enf_key);
        if (!$datos_enf) continue;

        // Ajustes de alimentación por enfermedad
        if (in_array($enf_key, ['gastroenteritis_leve', 'gastroenteritis_grave', 'parvovirus'])) {
            $ajuste_alimentacion_enfermedad .= "\n• Dieta especial por problemas digestivos: Arroz hervido + pollo desgrasado";
        }
        if (in_array($enf_key, ['insuficiencia_renal'])) {
            $ajuste_alimentacion_enfermedad .= "\n• Alimento renal específico prescrito por veterinario";
        }
        if (in_array($enf_key, ['obesidad'])) {
            $ajuste_alimentacion_enfermedad .= "\n• Control estricto de calorías para pérdida de peso gradual";
        }

        // Ajustes de ejercicio por enfermedad
        if (in_array($enf_key, ['displasia_cadera', 'problemas_cardiacos'])) {
            $ajuste_ejercicio_enfermedad .= "\n• Ejercicio reducido y controlado - evitar saltos y escaleras";
        }
        if (in_array($enf_key, ['golpe_calor', 'problemas_cardiacos'])) {
            $ajuste_ejercicio_enfermedad .= "\n• Evitar ejercicio en horas de calor intenso";
        }
        if (in_array($enf_key, ['torsion_gastrica'])) {
            $ajuste_ejercicio_enfermedad .= "\n• No ejercitar inmediatamente después de comer";
        }
    }

    // Agregar ajustes al plan de alimentación
    if (!empty($ajuste_alimentacion_enfermedad)) {
        $planAlimentacion .= "\n\n⚕️ AJUSTES POR CONDICIONES DE SALUD:" . $ajuste_alimentacion_enfermedad;
    }

    // Agregar ajustes al plan de ejercicio
    if (!empty($ajuste_ejercicio_enfermedad)) {
        $planEjercicio .= "\n\n⚕️ AJUSTES POR CONDICIONES DE SALUD:" . $ajuste_ejercicio_enfermedad;
    }

    // 7. RUTINAS DIARIAS, SEMANALES Y MENSUALES (AJUSTADAS POR ENFERMEDADES)
    $rutinaDiaria = "☀️ RUTINA DIARIA\n";

    // Ajustar alimentación según enfermedades
    $hora_desayuno = "07:00";
    $hora_cena = "18:00";
    $alimentacion_desayuno = "Mantener ~{$porcentajeAlimentacionPercent}% del peso corporal (ajuste: {$ajusteTexto})";
    $alimentacion_cena = "Mantener ~{$porcentajeAlimentacionPercent}% del peso corporal (ajuste: {$ajusteTexto})";

    if (in_array('gastroenteritis_leve', $enfermedades_detectadas) || in_array('gastroenteritis_grave', $enfermedades_detectadas)) {
        $alimentacion_desayuno = "Dieta blanda: Arroz hervido + pollo desgrasado (porciones pequeñas cada 3-4 horas)";
        $alimentacion_cena = "Dieta blanda: Arroz hervido + pollo desgrasado (porciones pequeñas cada 3-4 horas)";
        $rutinaDiaria .= "• 07:00 - DESAYUNO: $alimentacion_desayuno\n";
        $rutinaDiaria .= "• 10:00 - MERIENDA: Dieta blanda\n";
        $rutinaDiaria .= "• 13:00 - ALMUERZO: Dieta blanda\n";
        $rutinaDiaria .= "• 16:00 - MERIENDA: Dieta blanda\n";
        $rutinaDiaria .= "• 18:00 - CENA: $alimentacion_cena\n";
    } elseif (in_array('insuficiencia_renal', $enfermedades_detectadas)) {
        $alimentacion_desayuno = "Alimento renal específico (según prescripción veterinaria)";
        $alimentacion_cena = "Alimento renal específico (según prescripción veterinaria)";
        $rutinaDiaria .= "• $hora_desayuno - DESAYUNO: $alimentacion_desayuno\n";
        $rutinaDiaria .= "• $hora_cena - CENA: $alimentacion_cena\n";
    } else {
        $rutinaDiaria .= "• $hora_desayuno - DESAYUNO: $alimentacion_desayuno\n";
        $rutinaDiaria .= "• $hora_cena - CENA: $alimentacion_cena\n";
    }

    // Ajustar ejercicio según enfermedades
    $ejercicio_manana = round($tiempoEjercicio / 2) . " minutos";
    $ejercicio_tarde = round($tiempoEjercicio / 2) . " minutos";

    if (in_array('displasia_cadera', $enfermedades_detectadas) || in_array('problemas_cardiacos', $enfermedades_detectadas)) {
        $ejercicio_manana = "15-20 minutos (caminata suave, evitar saltos)";
        $ejercicio_tarde = "15-20 minutos (caminata suave, evitar saltos)";
    } elseif (in_array('golpe_calor', $enfermedades_detectadas)) {
        $ejercicio_manana = "20 minutos (solo en horas frescas)";
        $ejercicio_tarde = "10 minutos (evitar calor del día)";
    }

    $rutinaDiaria .= "• 08:30 - PASEO/EJERCICIO: $ejercicio_manana\n";
    $rutinaDiaria .= "• 12:00 - JUEGO/ESTIMULACIÓN: 15 minutos de entrenamiento o juegos mentales\n";
    $rutinaDiaria .= "• 19:30 - PASEO/EJERCICIO: $ejercicio_tarde\n";
    $rutinaDiaria .= "• 20:00 - REVISIÓN RÁPIDA: Verificar orejas, patas, uñas y ojos (higiene)\n";
    $rutinaDiaria .= "• 21:00 - DESCANSO";

    // Agregar recomendaciones específicas de enfermedades a la rutina diaria
    if (!empty($enfermedades_detectadas)) {
        $rutinaDiaria .= "\n\n💊 RECOMENDACIONES ESPECÍFICAS POR CONDICIÓN:";

        foreach ($enfermedades_detectadas as $enf_key) {
            $datos_enf = obtenerDatosEnfermedad($enf_key);
            if ($datos_enf && !empty($datos_enf['recomendaciones_diarias'])) {
                $rutinaDiaria .= "\n• {$datos_enf['nombre']}:";
                foreach (array_slice($datos_enf['recomendaciones_diarias'], 0, 2) as $rec) {
                    $rutinaDiaria .= "\n  - $rec";
                }
            }
        }
    }

    $rutinaSemanal = "📅 RUTINA SEMANAL (Además de lo diario)\n" .
        "• LUNES: Cepillado completo (si es pelo largo: 30 min, si es corto: 10 min)\n" .
        "• MIÉRCOLES: Revisión de oídos y limpieza si es necesario\n" .
        "• VIERNES: Revisión de patas y uñas; corte si es necesario\n" .
        "• DOMINGO: Baño e inspección profunda (piel, pelaje, ojos, dientes)\n" .
        "• CADA 2-3 DÍAS: Cepillado de dientes (o según recomendación veterinaria)\n" .
        "• CHECK SEMANAL: Verificar específicamente orejas, patas, uñas y piel";

    // Agregar recomendaciones semanales de enfermedades
    if (!empty($enfermedades_detectadas)) {
        $rutinaSemanal .= "\n\n📋 CONTROLES SEMANALES POR CONDICIÓN:";

        foreach ($enfermedades_detectadas as $enf_key) {
            $datos_enf = obtenerDatosEnfermedad($enf_key);
            if ($datos_enf && !empty($datos_enf['recomendaciones_semanales'])) {
                $rutinaSemanal .= "\n• {$datos_enf['nombre']}:";
                foreach ($datos_enf['recomendaciones_semanales'] as $rec) {
                    $rutinaSemanal .= "\n  - $rec";
                }
            }
        }
    }

    $rutinaMensual = "📊 RUTINA MENSUAL (IMPORTANTE TRACKING)\n" .
        "• DÍA 1 DEL MES: Pesada y registro de peso\n" .
        "• SEMANA 2: Evaluación de condición corporal (verificar costillas, cintura)\n" .
        "• SEMANA 3: Revisión de comportamiento y actividad\n" .
        "• SEMANA 4: Análisis general de salud y toma de notas\n\n" .
        "📈 TRACKING RECOMENDADO:\n" .
        "• Peso: Mantener gráfico mensual\n" .
        "• Apetito: Anotar cambios\n" .
        "• Energía: Evaluar cambios en actividad\n" .
        "• Digestion: Observar heces y consumo de agua\n" .
        "• Comportamiento: Alertar sobre cambios anormales";

    // Agregar recomendaciones mensuales de enfermedades
    if (!empty($enfermedades_detectadas)) {
        $rutinaMensual .= "\n\n🏥 CONTROLES MENSUALES POR CONDICIÓN:";

        foreach ($enfermedades_detectadas as $enf_key) {
            $datos_enf = obtenerDatosEnfermedad($enf_key);
            if ($datos_enf && !empty($datos_enf['recomendaciones_mensuales'])) {
                $rutinaMensual .= "\n• {$datos_enf['nombre']}:";
                foreach ($datos_enf['recomendaciones_mensuales'] as $rec) {
                    $rutinaMensual .= "\n  - $rec";
                }
            }
        }
    }

    // 7. INFORMACIÓN GENERAL
    $informacionGeneral = "ℹ️  INFORMACIÓN DEL PLAN\n" .
        "Mascota: " . htmlspecialchars($mascota['nombre']) . "\n" .
        "Raza: " . htmlspecialchars($mascota['raza']) . "\n" .
        "Etapa de Vida: $etapa ($rango_etapa)\n" .
        "Edad Actual: " . $edad . " años\n" .
        "Peso Actual: " . $pesoActual . " kg\n" .
        "Rango Saludable para Raza: $pesoMin - $pesoMax kg (Promedio: $pesoPromedio kg)\n" .
        "Estado de Peso: $estadoPeso (" . $porcentajeRango . "% del promedio)\n" .
        "Nivel de Actividad: " . ucfirst($nivelActividad) . "\n" .
        "Sexo: " . htmlspecialchars($mascota['sexo'] ?? 'No especificado') . "\n\n" .
        (!empty($mascota['tipo_alimento']) ? ("Tipo de alimentación reportado: " . htmlspecialchars($mascota['tipo_alimento']) . "\n") : "") .
        (!empty($overrides['porcion_aprox']) ? ("Porción aproximada reportada: " . intval($overrides['porcion_aprox']) . " g\n") : "") .
        (!empty($condicionesRaw) ? ("Condiciones reportadas: " . htmlspecialchars($condicionesRaw) . "\n") : "") .
        (!empty($sintomasRaw) ? ("Síntomas reportados: " . htmlspecialchars($sintomasRaw) . "\n") : "") .
        "⚠️  NOTA IMPORTANTE:\n" .
        "Este plan es ORIENTATIVO y está basado en promedios de raza y etapa de vida.\n" .
        "NO reemplaza la consulta veterinaria profesional.\n" .
        "Ajustar según respuesta individual de tu mascota.\n" .
        "Consulta con veterinario si hay cambios de salud, apetito o comportamiento.";

    // 8. GUARDAR PLAN EN BD (si $save==true)
    if ($save) {
        $fechaInicio = date('Y-m-d');
        $fechaFin = date('Y-m-d', strtotime('+30 days'));
        
        // Desactivar planes anteriores
        $pdo->prepare("UPDATE planes_salud SET activo = 0 WHERE mascota_id = ?")->execute([$mascotaId]);
        
        $sql = "INSERT INTO planes_salud 
                (mascota_id, fecha_inicio, fecha_fin, objetivo, plan_alimentacion, plan_ejercicio, 
                 vacunas_pendientes, examenes_recomendados, plan_bienestar_mental, plan_higiene, activo) 
                VALUES (:id, :inicio, :fin, :info, :alim, :ejer, :vac, :exam, :rutina_d, :rutina_s, 1)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $mascotaId,
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin,
            ':info' => $informacionGeneral,
            ':alim' => $planAlimentacion,
            ':ejer' => $planEjercicio,
            ':vac' => "Cronograma preventivo anual - Consultar historial de vacunas",
            ':exam' => "Chequeo físico general recomendado cada 6 meses",
            ':rutina_d' => $rutinaDiaria . "\n\n" . $rutinaSemanal,
            ':rutina_s' => $rutinaMensual
        ]);
        
        $planId = $pdo->lastInsertId();

        // 9. CREAR RECORDATORIOS AUTOMÁTICOS PARA EL MES
        $pdo->prepare("DELETE FROM recordatorios_plan WHERE plan_id IN (SELECT id FROM planes_salud WHERE mascota_id = ?)")->execute([$mascotaId]);
        
        $insertRem = $pdo->prepare("INSERT INTO recordatorios_plan (plan_id, tipo, descripcion, fecha_programada) VALUES (?, ?, ?, ?)");
        
        // Recordatorios diarios (sin gramos, usando porcentaje recomendado y ejercicio)
        for ($i = 0; $i < 30; $i++) {
            $fecha = date('Y-m-d', strtotime("+$i days"));
            $desc = "Alimentación: mantener ~{$porcentajeAlimentacionPercent}% del peso corporal (ajuste: {$ajusteTexto}) + Ejercicio: " . round($tiempoEjercicio / 2) . "m mañana y tarde";
            $insertRem->execute([$planId, 'rutina_diaria', $desc, $fecha]);
        }

        // Recordatorios semanales: revisar orejas, patas, uñas, ojos
        for ($w = 1; $w <= 4; $w++) {
            $fecha = date('Y-m-d', strtotime('+' . (3 + ($w - 1) * 7) . ' days'));
            $insertRem->execute([$planId, 'rutina_semanal', 'Revisión semanal: orejas, patas, uñas y ojos. Realizar limpieza/higiene según necesidad.', $fecha]);
        }
        
        // Recordatorio mensual
        $insertRem->execute([$planId, 'rutina_mensual', 
            'Control de Peso Mensual - Pesar y registrar peso actual para seguimiento', 
            date('Y-m-d', strtotime('+30 days'))
        ]);

        // Devolver también el contenido guardado para mostrar al cliente
        return [
            'success' => true,
            'plan_id' => $planId,
            'preview' => [
                'informacion' => $informacionGeneral,
                'plan_alimentacion' => $planAlimentacion,
                'plan_ejercicio' => $planEjercicio,
                'rutina_diaria' => $rutinaDiaria . "\n\n" . $rutinaSemanal,
                'rutina_mensual' => $rutinaMensual,
                'foto' => $mascota['foto_perfil'] ?? ''
            ]
        ];
    }

    // Si no guardamos (preview), devolvemos el contenido del plan para previsualización
    return [
        'success' => true,
        'preview' => [
            'informacion' => $informacionGeneral,
            'plan_alimentacion' => $planAlimentacion,
            'plan_ejercicio' => $planEjercicio,
            'rutina_diaria' => $rutinaDiaria . "\n\n" . $rutinaSemanal,
            'rutina_mensual' => $rutinaMensual,
            'foto' => $mascota['foto_perfil'] ?? ''
        ]
    ];
}

// Helpers
function obtenerUltimoPeso($mascotaId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT peso FROM peso_historial WHERE mascota_id = ? ORDER BY fecha DESC LIMIT 1");
    $stmt->execute([$mascotaId]);
    return $stmt->fetchColumn();
}

function obtenerVacunas($mascotaId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM mascotas_salud WHERE mascota_id = ? AND tipo = 'vacuna' ORDER BY fecha_realizado DESC");
    $stmt->execute([$mascotaId]);
    return $stmt->fetchAll();
}

/**
 * Función principal para generar plan con IA
 * Wrapper que llama a generarPlanSaludIA
 */
function generarPlanConIA($mascotaId, $overrides = [], $save = true) {
    return generarPlanSaludIA($mascotaId, $overrides, $save);
}
?>
