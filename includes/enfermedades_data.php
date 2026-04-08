<?php
/**
 * Datos de enfermedades y recomendaciones para planes de salud
 * Basado en la lista proporcionada por el usuario
 */

function obtenerDatosEnfermedad($enfermedad) {
    $enfermedades = [
        // ENFERMEDADES LEVES
        'gastroenteritis_leve' => [
            'nombre' => 'Gastroenteritis Leve',
            'gravedad' => 'leve',
            'sintomas' => ['diarrea ocasional', 'vómitos 1–2 veces', 'causa: comida, estrés'],
            'recomendaciones_diarias' => [
                'Alimentación: Dieta blanda, arroz hervido con pollo desgrasado, porciones pequeñas cada 3-4 horas',
                'Hidratación: Agua fresca constantemente, considerar suero oral si hay signos de deshidratación',
                'Reposo: Mantener en ambiente tranquilo, evitar ejercicio intenso',
                'Monitoreo: Observar frecuencia de deposiciones y vómitos'
            ],
            'recomendaciones_semanales' => [
                'Revisar signos de mejora: Si persiste más de 3 días, consultar veterinario',
                'Transición gradual: Volver a dieta normal en 4-5 días',
                'Control de estrés: Identificar y reducir factores estresantes'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Mantener higiene en alimentos y agua',
                'Chequeo: Si recurrente, evaluación veterinaria completa'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 3
        ],

        'dermatitis_leve' => [
            'nombre' => 'Dermatitis Leve',
            'gravedad' => 'leve',
            'sintomas' => ['picazón', 'enrojecimiento', 'alergias, pulgas'],
            'recomendaciones_diarias' => [
                'Higiene: Baño con shampoo medicado específico para piel sensible',
                'Ambiente: Mantener fresco y seco, evitar humedad excesiva',
                'Cepillado: Cepillar suavemente para remover pelo muerto y posibles parásitos',
                'Monitoreo: Observar si el picor aumenta o aparecen heridas'
            ],
            'recomendaciones_semanales' => [
                'Control de parásitos: Aplicar tratamiento antipulgas si es necesario',
                'Ambiente: Limpiar camas y áreas de descanso semanalmente',
                'Suplementos: Considerar ácidos grasos omega-3 para piel'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Mantener control de parásitos mensual',
                'Chequeo: Si crónico, evaluación alergológica'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 7
        ],

        'otitis_leve' => [
            'nombre' => 'Otitis Leve',
            'gravedad' => 'leve',
            'sintomas' => ['sacude la cabeza', 'mal olor en oído', 'secreción leve'],
            'recomendaciones_diarias' => [
                'Limpieza: Limpiar oídos con solución específica diariamente',
                'Secado: Después del baño, secar bien las orejas',
                'Monitoreo: Observar cambios en el comportamiento o aumento de secreción'
            ],
            'recomendaciones_semanales' => [
                'Limpieza profunda: Si persiste, limpieza profesional semanal',
                'Prevención: Evitar agua en oídos durante baños'
            ],
            'recomendaciones_mensuales' => [
                'Chequeo: Revisión veterinaria si recurrente',
                'Prevención: Mantener higiene auditiva regular'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 5
        ],

        'conjuntivitis_leve' => [
            'nombre' => 'Conjuntivitis Leve',
            'gravedad' => 'leve',
            'sintomas' => ['ojo rojo', 'lagrimeo', 'legañas'],
            'recomendaciones_diarias' => [
                'Limpieza: Limpiar ojos con gasa húmeda y tibia varias veces al día',
                'Ambiente: Evitar irritantes como humo o polvo',
                'Monitoreo: Observar si hay dolor o visión afectada'
            ],
            'recomendaciones_semanales' => [
                'Limpieza regular: Mantener rutina de limpieza ocular',
                'Prevención: Evitar contacto con otros animales enfermos'
            ],
            'recomendaciones_mensuales' => [
                'Chequeo: Si recurrente, evaluación oftalmológica',
                'Prevención: Mantener higiene general'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 5
        ],

        'parasitos_externos' => [
            'nombre' => 'Parásitos Externos',
            'gravedad' => 'leve',
            'sintomas' => ['pulgas', 'garrapatas'],
            'recomendaciones_diarias' => [
                'Tratamiento: Aplicar productos antiparasitarios según indicación',
                'Cepillado: Revisar y remover parásitos manualmente',
                'Ambiente: Limpiar áreas de descanso'
            ],
            'recomendaciones_semanales' => [
                'Control ambiental: Lavar camas y mantas',
                'Prevención: Mantener tratamientos preventivos'
            ],
            'recomendaciones_mensuales' => [
                'Chequeo: Control veterinario mensual',
                'Prevención: Tratamientos continuos'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 7
        ],

        'halitosis' => [
            'nombre' => 'Halitosis',
            'gravedad' => 'leve',
            'sintomas' => ['mal aliento', 'problemas dentales tempranos'],
            'recomendaciones_diarias' => [
                'Higiene dental: Cepillar dientes diariamente con pasta específica',
                'Monitoreo: Observar encías y dientes regularmente'
            ],
            'recomendaciones_semanales' => [
                'Limpieza dental: Considerar limpieza profesional semanal',
                'Alimentación: Dieta que promueva salud dental'
            ],
            'recomendaciones_mensuales' => [
                'Chequeo: Revisión dental veterinaria mensual',
                'Prevención: Mantener rutina de cepillado'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 30
        ],

        'tos_leve' => [
            'nombre' => 'Tos Leve / Resfriado',
            'gravedad' => 'leve',
            'sintomas' => ['tos ocasional', 'estornudos'],
            'recomendaciones_diarias' => [
                'Ambiente: Mantener cálido y húmedo, evitar corrientes',
                'Reposo: Evitar ejercicio intenso hasta mejorar',
                'Monitoreo: Observar si la tos empeora o aparece fiebre'
            ],
            'recomendaciones_semanales' => [
                'Ambiente controlado: Mantener temperatura estable',
                'Prevención: Evitar exposición a otros animales enfermos'
            ],
            'recomendaciones_mensuales' => [
                'Chequeo: Si recurrente, evaluación respiratoria',
                'Prevención: Mantener vacunación al día'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 7
        ],

        // ENFERMEDADES GRAVES
        'gastroenteritis_grave' => [
            'nombre' => 'Gastroenteritis Grave',
            'gravedad' => 'grave',
            'sintomas' => ['diarrea con sangre', 'vómitos constantes', 'deshidratación'],
            'recomendaciones_diarias' => [
                'URGENTE: Acudir inmediatamente al veterinario',
                'Hidratación: Fluidos intravenosos pueden ser necesarios',
                'Ayuno: Posible ayuno temporal bajo supervisión veterinaria',
                'Monitoreo: Signos vitales constantes'
            ],
            'recomendaciones_semanales' => [
                'Seguimiento veterinario: Controles diarios durante la primera semana',
                'Dieta gradual: Reintroducción de alimentos blandos lentamente'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Vacunas y control de parásitos',
                'Chequeo: Evaluación completa si recurrente'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 14
        ],

        'dermatitis_cronica' => [
            'nombre' => 'Dermatitis Crónica',
            'gravedad' => 'grave',
            'sintomas' => ['infecciones recurrentes', 'heridas abiertas', 'caída de pelo severa'],
            'recomendaciones_diarias' => [
                'Tratamiento veterinario: Antibióticos o antifúngicos según indicación',
                'Higiene: Limpieza diaria de heridas con soluciones específicas',
                'Protección: Mantener alejado de rascado y lamido'
            ],
            'recomendaciones_semanales' => [
                'Control veterinario: Revisión semanal de evolución',
                'Suplementos: Ácidos grasos y vitaminas para piel'
            ],
            'recomendaciones_mensuales' => [
                'Diagnóstico: Pruebas alérgicas y hormonales',
                'Prevención: Dieta hipoalergénica si es alérgico'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 30
        ],

        'otitis_cronica' => [
            'nombre' => 'Otitis Crónica',
            'gravedad' => 'grave',
            'sintomas' => ['dolor intenso', 'infección profunda', 'pérdida de audición'],
            'recomendaciones_diarias' => [
                'Tratamiento: Gotas óticas específicas prescritas por veterinario',
                'Limpieza: Limpieza profesional diaria inicialmente',
                'Analgésicos: Medicamentos para dolor si prescritos'
            ],
            'recomendaciones_semanales' => [
                'Seguimiento: Controles semanales con veterinario',
                'Prevención: Mantener oídos secos y limpios'
            ],
            'recomendaciones_mensuales' => [
                'Chequeo: Evaluación auditiva mensual',
                'Prevención: Control de alergias y humedad'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 21
        ],

        'displasia_cadera' => [
            'nombre' => 'Displasia de Cadera',
            'gravedad' => 'grave',
            'sintomas' => ['cojera', 'dolor al caminar', 'común en razas grandes'],
            'recomendaciones_diarias' => [
                'Ejercicio controlado: Caminatas cortas, evitar saltos y escaleras',
                'Peso: Mantener peso ideal para reducir estrés articular',
                'Analgésicos: Medicamentos antiinflamatorios si prescritos'
            ],
            'recomendaciones_semanales' => [
                'Fisioterapia: Ejercicios específicos para fortalecer músculos',
                'Suplementos: Glucosamina y condroitina'
            ],
        'recomendaciones_mensuales' => [
            'Control veterinario: Evaluación clínica mensual, radiografías solo si hay empeoramiento',
            'Prevención: Evitar sobrepeso y ejercicio excesivo en cachorros'
        ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 0 // Crónica
        ],

        'insuficiencia_renal' => [
            'nombre' => 'Insuficiencia Renal',
            'gravedad' => 'grave',
            'sintomas' => ['mucha sed', 'orina excesiva', 'pérdida de peso'],
            'recomendaciones_diarias' => [
                'Dieta específica: Alimento renal prescrito por veterinario',
                'Hidratación: Agua fresca constantemente disponible',
                'Medicamentos: Fármacos para controlar síntomas'
            ],
            'recomendaciones_semanales' => [
                'Análisis: Chequeos sanguíneos semanales inicialmente',
                'Peso: Monitoreo de peso corporal'
            ],
            'recomendaciones_mensuales' => [
                'Control veterinario: Evaluaciones mensuales',
                'Prevención: Detección temprana en chequeos rutinarios'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 0 // Crónica
        ],

        'problemas_cardiacos' => [
            'nombre' => 'Problemas Cardíacos',
            'gravedad' => 'grave',
            'sintomas' => ['fatiga', 'tos nocturna', 'dificultad respiratoria leve'],
            'recomendaciones_diarias' => [
                'Medicamentos: Administrar medicamentos cardíacos según horario',
                'Ejercicio: Actividad ligera, evitar sobreesfuerzo',
                'Ambiente: Reducir estrés y mantener fresco'
            ],
            'recomendaciones_semanales' => [
                'Control: Ecocardiogramas y análisis semanales',
                'Peso: Monitoreo de fluctuaciones'
            ],
            'recomendaciones_mensuales' => [
                'Chequeo: Evaluación cardiológica mensual',
                'Prevención: Detección temprana en adultos mayores'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 0 // Crónica
        ],

        'obesidad' => [
            'nombre' => 'Obesidad',
            'gravedad' => 'grave',
            'sintomas' => ['factor de riesgo', 'problemas articulares y cardíacos'],
            'recomendaciones_diarias' => [
                'Dieta: Control estricto de porciones y calorías',
                'Ejercicio: Caminatas diarias de 20-30 minutos',
                'Monitoreo: Peso diario o cada dos días'
            ],
            'recomendaciones_semanales' => [
                'Peso: Control semanal del peso',
                'Ajustes: Modificar dieta según progreso'
            ],
            'recomendaciones_mensuales' => [
                'Veterinario: Chequeo mensual para evaluar progreso',
                'Prevención: Educación sobre alimentación adecuada'
            ],
            'urgencia_veterinaria' => false,
            'dias_recuperacion' => 90
        ],

        // ENFERMEDADES DE URGENCIA
        'parvovirus' => [
            'nombre' => 'Parvovirus',
            'gravedad' => 'urgencia',
            'sintomas' => ['diarrea con sangre', 'vómitos constantes', 'letargo extremo'],
            'recomendaciones_diarias' => [
                'EMERGENCIA: Acudir inmediatamente al veterinario',
                'Aislamiento: Mantener alejado de otros perros',
                'Hidratación: Fluidos intravenosos necesarios',
                'Monitoreo: Signos vitales constantes'
            ],
            'recomendaciones_semanales' => [
                'Hospitalización: Posible internamiento de 3-7 días',
                'Vacunación: Completar esquema vacunal'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Vacunación anual obligatoria',
                'Chequeo: Controles post-recuperación'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 14
        ],

        'moquillo' => [
            'nombre' => 'Moquillo',
            'gravedad' => 'urgencia',
            'sintomas' => ['fiebre', 'secreción nasal', 'convulsiones'],
            'recomendaciones_diarias' => [
                'EMERGENCIA: Atención veterinaria inmediata',
                'Aislamiento: Cuarentena estricta',
                'Sintomático: Tratamiento de síntomas según indicación'
            ],
            'recomendaciones_semanales' => [
                'Seguimiento: Controles neurológicos semanales',
                'Vacunación: Completar vacunación'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Vacunación anual',
                'Chequeo: Monitoreo neurológico'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 21
        ],

        'torsion_gastrica' => [
            'nombre' => 'Torsión Gástrica',
            'gravedad' => 'urgencia',
            'sintomas' => ['abdomen hinchado', 'dolor extremo', 'intentos de vomitar sin éxito'],
            'recomendaciones_diarias' => [
                'EMERGENCIA CRÍTICA: Cirugía inmediata necesaria',
                'No alimentar: Ayuno absoluto hasta evaluación',
                'Monitoreo: Observar distensión abdominal'
            ],
            'recomendaciones_semanales' => [
                'Recuperación: Reposo absoluto post-cirugía',
                'Dieta: Alimentos blandos en porciones pequeñas'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Alimentar varias veces al día, evitar ejercicio post-comida',
                'Chequeo: Controles veterinarios regulares'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 30
        ],

        'golpe_calor' => [
            'nombre' => 'Golpe de Calor',
            'gravedad' => 'urgencia',
            'sintomas' => ['jadeo excesivo', 'lengua morada', 'colapso'],
            'recomendaciones_diarias' => [
                'EMERGENCIA: Enfriamiento inmediato y veterinario',
                'Enfriamiento: Agua fresca (no hielo), ventiladores',
                'Hidratación: Fluidos intravenosos necesarios'
            ],
            'recomendaciones_semanales' => [
                'Recuperación: Reposo en ambiente fresco',
                'Monitoreo: Signos de daño orgánico'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Evitar exposición al sol en horas pico',
                'Chequeo: Evaluación de órganos afectados'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 7
        ],

        'intoxicacion' => [
            'nombre' => 'Intoxicación / Envenenamiento',
            'gravedad' => 'urgencia',
            'sintomas' => ['vómitos', 'convulsiones', 'salivación excesiva'],
            'recomendaciones_diarias' => [
                'EMERGENCIA: Identificar tóxico y acudir a veterinario',
                'Inducción de vómito: Solo si indicado por veterinario',
                'Carbón activado: Según recomendación profesional'
            ],
            'recomendaciones_semanales' => [
                'Desintoxicación: Tratamiento de soporte',
                'Monitoreo: Función hepática y renal'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Mantener tóxicos fuera del alcance',
                'Chequeo: Evaluación post-intoxicación'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 14
        ],

        'obstruccion_intestinal' => [
            'nombre' => 'Obstrucción Intestinal',
            'gravedad' => 'urgencia',
            'sintomas' => ['no defeca', 'vómitos persistentes', 'dolor abdominal'],
            'recomendaciones_diarias' => [
                'EMERGENCIA: Cirugía puede ser necesaria',
                'Ayuno: Suspensión de alimentación',
                'Analgésicos: Para controlar dolor'
            ],
            'recomendaciones_semanales' => [
                'Recuperación: Dieta blanda post-resolución',
                'Monitoreo: Función intestinal'
            ],
            'recomendaciones_mensuales' => [
                'Prevención: Supervisar ingestión de objetos',
                'Chequeo: Controles digestivos'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 14
        ],

        'traumatismos_graves' => [
            'nombre' => 'Traumatismos Graves',
            'gravedad' => 'urgencia',
            'sintomas' => ['atropellos', 'caídas', 'sangrado intenso'],
            'recomendaciones_diarias' => [
                'EMERGENCIA: Atención veterinaria inmediata',
                'Inmovilización: No mover si hay sospecha de fracturas',
                'Control de sangrado: Compresión directa si es segura'
            ],
            'recomendaciones_semanales' => [
                'Recuperación: Reposo absoluto según lesión',
                'Fisioterapia: Según indicación veterinaria'
            ],
            'recomendaciones_mensuales' => [
                'Rehabilitación: Recuperación gradual',
                'Chequeo: Controles de evolución'
            ],
            'urgencia_veterinaria' => true,
            'dias_recuperacion' => 30
        ]
    ];

    return $enfermedades[$enfermedad] ?? null;
}

/**
 * Función para detectar enfermedades basadas en síntomas reportados
 */
function detectarEnfermedades($sintomas, $condiciones = '') {
    $enfermedades_detectadas = [];
    $sintomas_lower = strtolower($sintomas);
    $condiciones_lower = strtolower($condiciones);

    $enfermedades = [
        // Mapeo de síntomas a enfermedades
        'diarrea' => ['gastroenteritis_leve', 'gastroenteritis_grave', 'parvovirus'],
        'vómito' => ['gastroenteritis_leve', 'gastroenteritis_grave', 'torsion_gastrica', 'intoxicacion'],
        'picazón' => ['dermatitis_leve', 'dermatitis_cronica'],
        'enrojecimiento' => ['dermatitis_leve', 'conjuntivitis_leve'],
        'pulgas' => ['parasitos_externos'],
        'mal olor oído' => ['otitis_leve', 'otitis_cronica'],
        'ojo rojo' => ['conjuntivitis_leve'],
        'mal aliento' => ['halitosis'],
        'tos' => ['tos_leve', 'problemas_cardiacos'],
        'cojera' => ['displasia_cadera'],
        'sed excesiva' => ['insuficiencia_renal'],
        'pérdida peso' => ['insuficiencia_renal', 'problemas_cardiacos'],
        'fatiga' => ['problemas_cardiacos'],
        'dificultad respirar' => ['problemas_cardiacos', 'golpe_calor'],
        'sangre' => ['gastroenteritis_grave', 'parvovirus'],
        'convulsiones' => ['moquillo', 'intoxicacion'],
        'abdomen hinchado' => ['torsion_gastrica'],
        'lengua morada' => ['golpe_calor'],
        'salivación excesiva' => ['intoxicacion'],
        'no defeca' => ['obstruccion_intestinal'],
        'sangrado' => ['traumatismos_graves']
    ];

    foreach ($enfermedades as $sintoma => $lista_enfermedades) {
        if (strpos($sintomas_lower, $sintoma) !== false) {
            $enfermedades_detectadas = array_merge($enfermedades_detectadas, $lista_enfermedades);
        }
    }

    // Detectar por condiciones crónicas
    $condiciones_cronicas = [
        'diabetes' => 'diabetes',
        'artritis' => 'displasia_cadera',
        'alergia' => 'dermatitis_cronica',
        'hipotiroidismo' => 'obesidad',
        'epilepsia' => 'moquillo',
        'cardiaca' => 'problemas_cardiacos'
    ];

    foreach ($condiciones_cronicas as $condicion => $enfermedad) {
        if (strpos($condiciones_lower, $condicion) !== false) {
            $enfermedades_detectadas[] = $enfermedad;
        }
    }

    return array_unique($enfermedades_detectadas);
}

/**
 * Obtener recomendaciones prioritarias basadas en gravedad
 */
function obtenerRecomendacionesPrioritarias($enfermedades_detectadas) {
    $urgencias = [];
    $graves = [];
    $leves = [];

    foreach ($enfermedades_detectadas as $enfermedad) {
        $datos = obtenerDatosEnfermedad($enfermedad);
        if (!$datos) continue;

        if ($datos['urgencia_veterinaria']) {
            $urgencias[] = $datos;
        } elseif ($datos['gravedad'] === 'grave') {
            $graves[] = $datos;
        } else {
            $leves[] = $datos;
        }
    }

    return [
        'urgencias' => $urgencias,
        'graves' => $graves,
        'leves' => $leves
    ];
}
