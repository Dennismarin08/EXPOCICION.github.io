<?php
/**
 * RUGAL - PlanSaludGenerator
 * Clase principal para generar planes de salud personalizados
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/razas_data.php';
require_once __DIR__ . '/enfermedades_data.php';

class PlanSaludGenerator {
    private $pdo;
    private $mascota;
    private $overrides;
    private $datosRaza;
    private $enfermedadesDetectadas = [];
    
    const ETAPA_CACHORRO = 'Cachorro';
    const ETAPA_ADULTO = 'Adulto';
    const ETAPA_SENIOR = 'Senior';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Genera un plan de salud completo
     */
    public function generar($mascotaId, $overrides = [], $save = true) {
        try {
            $this->overrides = $overrides;
            
            // 1. Cargar mascota
            if (!$this->cargarMascota($mascotaId)) {
                return ['success' => false, 'error' => 'Mascota no encontrada'];
            }
            
            // 2. Aplicar overrides
            $this->aplicarOverrides();
            
            // 3. Obtener datos de raza
            $this->datosRaza = obtenerDatosRaza($this->mascota['raza']);
            
            // 4. Detectar enfermedades
            $this->detectarEnfermedades();
            
            // 5. Generar plan
            $plan = [
                'informacion' => $this->generarInformacionGeneral(),
                'plan_alimentacion' => $this->generarPlanAlimentacion(),
                'plan_ejercicio' => $this->generarPlanEjercicio(),
                'rutina_diaria' => $this->generarRutinaDiaria(),
                'rutina_semanal' => $this->generarRutinaSemanal(),
                'rutina_mensual' => $this->generarRutinaMensual(),
                'recomendaciones' => $this->generarRecomendaciones(),
                'alertas' => $this->generarAlertas()
            ];
            
            // 6. Guardar o retornar preview
            if ($save) {
                return $this->guardarPlan($plan);
            }
            
            return [
                'success' => true,
                'preview' => $plan,
                'mascota' => [
                    'nombre' => $this->mascota['nombre'],
                    'foto' => $this->mascota['foto_perfil'] ?? ''
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error generando plan: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function cargarMascota($mascotaId) {
        $stmt = $this->pdo->prepare("SELECT * FROM mascotas WHERE id = ?");
        $stmt->execute([$mascotaId]);
        $this->mascota = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($this->mascota);
    }
    
    private function aplicarOverrides() {
        $campos = ['edad', 'peso', 'raza', 'nivel_actividad', 'tipo_alimento', 
                   'porcion_aprox', 'marca_comercial', 'condiciones', 'sintomas', 'enfermedades'];
        
        foreach ($campos as $campo) {
            if (!empty($this->overrides[$campo])) {
                $this->mascota[$campo] = $this->overrides[$campo];
            }
        }
    }
    
    private function detectarEnfermedades() {
        $sintomas = strtolower($this->overrides['sintomas'] ?? '');
        $condiciones = strtolower($this->overrides['condiciones'] ?? '');
        $enfermedadesInput = $this->overrides['enfermedades'] ?? '';
        
        $detectadas = detectarEnfermedades($sintomas, $condiciones);
        
        if (!empty($enfermedadesInput)) {
            $seleccionadas = array_map('trim', explode(',', $enfermedadesInput));
            $detectadas = array_unique(array_merge($detectadas, $seleccionadas));
        }
        
        $this->enfermedadesDetectadas = $detectadas;
    }
    
    private function getEtapaVida() {
        $edad = floatval($this->mascota['edad'] ?? 0);
        
        if ($edad < 1) {
            return ['etapa' => self::ETAPA_CACHORRO, 'rango' => '0 a 12 meses'];
        } elseif ($edad >= 7) {
            return ['etapa' => self::ETAPA_SENIOR, 'rango' => '7+ años'];
        }
        
        return ['etapa' => self::ETAPA_ADULTO, 'rango' => '1 a 7 años'];
    }
    
    private function calcularEstadoPeso() {
        $pesoActual = floatval($this->mascota['peso'] ?? 0);
        $pesoMin = floatval($this->datosRaza['peso_min'] ?? 0);
        $pesoMax = floatval($this->datosRaza['peso_max'] ?? 0);
        $pesoPromedio = ($pesoMin + $pesoMax) / 2;
        
        if ($pesoActual < $pesoMin && $pesoMin > 0) {
            return [
                'estado' => 'POR DEBAJO',
                'descripcion' => 'Delgadez',
                'porcentaje' => round(($pesoActual / $pesoPromedio) * 100, 1),
                'recomendacion' => 'Aumentar ingesta gradualmente'
            ];
        } elseif ($pesoActual > $pesoMax && $pesoMax > 0) {
            return [
                'estado' => 'POR ENCIMA',
                'descripcion' => 'Sobrepeso',
                'porcentaje' => round(($pesoActual / $pesoPromedio) * 100, 1),
                'recomendacion' => 'Reducir calorías y aumentar ejercicio'
            ];
        }
        
        return [
            'estado' => 'ÓPTIMO',
            'descripcion' => 'Peso saludable',
            'porcentaje' => 100,
            'recomendacion' => 'Mantener rutina actual'
        ];
    }
    
    private function generarInformacionGeneral() {
        $etapa = $this->getEtapaVida();
        $estadoPeso = $this->calcularEstadoPeso();
        $nombre = htmlspecialchars($this->mascota['nombre']);
        
        $info = "📋 INFORMACIÓN GENERAL\n\n";
        $info .= "Mascota: {$nombre}\n";
        $info .= "Raza: " . htmlspecialchars($this->mascota['raza']) . "\n";
        $info .= "Etapa: {$etapa['etapa']} ({$etapa['rango']})\n";
        $info .= "Edad: " . floatval($this->mascota['edad']) . " años\n";
        $info .= "Peso: " . floatval($this->mascota['peso']) . " kg\n";
        $info .= "Estado: {$estadoPeso['estado']} - {$estadoPeso['descripcion']}\n";
        $info .= "Actividad: " . ucfirst($this->mascota['nivel_actividad'] ?? 'medio') . "\n";
        
        if (!empty($this->enfermedadesDetectadas)) {
            $info .= "\n⚠️ CONDICIONES:\n";
            foreach ($this->enfermedadesDetectadas as $enf) {
                $datos = obtenerDatosEnfermedad($enf);
                if ($datos) {
                    $info .= "• {$datos['nombre']}\n";
                }
            }
        }
        
        $info .= "\n💡 NOTA: Plan orientativo, no reemplaza consulta veterinaria.";
        
        return $info;
    }
    
    private function generarPlanAlimentacion() {
        $etapa = $this->getEtapaVida();
        $estadoPeso = $this->calcularEstadoPeso();
        $nivelActividad = strtolower($this->mascota['nivel_actividad'] ?? 'medio');
        $peso = floatval($this->mascota['peso'] ?? 0);
        
        // Porcentajes base
        $porcentajes = [
            self::ETAPA_CACHORRO => 3.5,
            self::ETAPA_SENIOR => 2.2,
            self::ETAPA_ADULTO => [
                'bajo' => 2.0,
                'medio' => 2.5,
                'alto' => 3.0
            ]
        ];
        
        $porcentajeBase = ($etapa['etapa'] === self::ETAPA_ADULTO) 
            ? ($porcentajes[$etapa['etapa']][$nivelActividad] ?? 2.5)
            : $porcentajes[$etapa['etapa']];
        
        // Ajuste por peso
        $ajuste = ($estadoPeso['estado'] === 'POR DEBAJO') ? 15 : 
                  (($estadoPeso['estado'] === 'POR ENCIMA') ? -15 : 0);
        
        $porcentajeFinal = $porcentajeBase + ($ajuste / 100 * $porcentajeBase);
        $gramosDia = round(($peso * $porcentajeFinal) / 100 * 1000);
        
        $frecuencias = [
            self::ETAPA_CACHORRO => ['veces' => 4, 'desc' => '4 comidas al día'],
            self::ETAPA_ADULTO => ['veces' => 2, 'desc' => '2 comidas (mañana/tarde)'],
            self::ETAPA_SENIOR => ['veces' => 2, 'desc' => '2 comidas controladas']
        ];
        
        $frecuencia = $frecuencias[$etapa['etapa']];
        $gramosPorComida = round($gramosDia / $frecuencia['veces']);
        
        $plan = "🍖 PLAN DE ALIMENTACIÓN\n\n";
        $plan .= "Porcentaje: " . round($porcentajeFinal, 1) . "% del peso\n";
        $plan .= "Total diario: ~{$gramosDia}g\n";
        $plan .= "Frecuencia: {$frecuencia['desc']}\n";
        $plan .= "Por comida: ~{$gramosPorComida}g\n\n";
        
        $plan .= "📌 RECOMENDACIONES:\n";
        $plan .= "• Alimento de calidad para {$etapa['etapa']}\n";
        $plan .= "• Agua fresca siempre disponible\n";
        $plan .= "• Transición de dieta en 7-10 días\n";
        
        if ($estadoPeso['estado'] !== 'ÓPTIMO') {
            $plan .= "• {$estadoPeso['recomendacion']}\n";
        }
        
        // Ajustes por enfermedades
        foreach ($this->enfermedadesDetectadas as $enf) {
            if (in_array($enf, ['gastroenteritis_leve', 'gastroenteritis_grave'])) {
                $plan .= "\n⚕️ Dieta blanda: arroz + pollo desgrasado\n";
            }
            if ($enf === 'insuficiencia_renal') {
                $plan .= "\n⚕️ Alimento renal específico\n";
            }
        }
        
        if (!empty($this->mascota['marca_comercial'])) {
            $plan .= "\n🔖 Marca: " . htmlspecialchars($this->mascota['marca_comercial']) . "\n";
        }
        
        return $plan;
    }
    
    private function generarPlanEjercicio() {
        $etapa = $this->getEtapaVida();
        $nivelActividad = strtolower($this->mascota['nivel_actividad'] ?? 'medio');
        
        $config = [
            self::ETAPA_CACHORRO => ['min' => 20, 'max' => 30, 'intensidad' => 'Baja'],
            self::ETAPA_SENIOR => ['min' => 20, 'max' => 40, 'intensidad' => 'Baja'],
            self::ETAPA_ADULTO => [
                'bajo' => ['min' => 20, 'max' => 30, 'intensidad' => 'Baja'],
                'medio' => ['min' => 45, 'max' => 60, 'intensidad' => 'Media'],
                'alto' => ['min' => 60, 'max' => 90, 'intensidad' => 'Alta']
            ]
        ];
        
        $cfg = ($etapa['etapa'] === self::ETAPA_ADULTO) 
            ? ($config[$etapa['etapa']][$nivelActividad] ?? $config[$etapa['etapa']]['medio'])
            : $config[$etapa['etapa']];
        
        // Ajustes por condiciones
        if (in_array('displasia_cadera', $this->enfermedadesDetectadas) || 
            in_array('problemas_cardiacos', $this->enfermedadesDetectadas)) {
            $cfg = ['min' => 15, 'max' => 30, 'intensidad' => 'Baja (ajustada)'];
        }
        
        $plan = "🏃 PLAN DE EJERCICIO\n\n";
        $plan .= "Intensidad: {$cfg['intensidad']}\n";
        $plan .= "Tiempo: {$cfg['min']}-{$cfg['max']} min/día\n\n";
        
        $plan .= "📅 DISTRIBUCIÓN:\n";
        $plan .= "• Mañana: " . round($cfg['max'] / 2) . " min\n";
        $plan .= "• Tarde: " . round($cfg['max'] / 2) . " min\n\n";
        
        $plan .= "💡 CONSEJOS:\n";
        $plan .= "• Rutina fija (mismo horario)\n";
        $plan .= "• No ejercitar 1h antes/después de comer\n";
        $plan .= "• En calor, ejercitar en horas frescas\n";
        
        // Alerta braquicéfalos
        if (preg_match('/bulldog|pug|carlino|shih tzu/i', strtolower($this->mascota['raza'] ?? ''))) {
            $plan .= "\n⚠️ Raza braquicéfala: evitar ejercicio intenso en calor\n";
        }
        
        return $plan;
    }
    
    private function generarRutinaDiaria() {
        $etapa = $this->getEtapaVida();
        
        $rutinas = [
            self::ETAPA_CACHORRO => [
                '07:00' => 'Desayuno (1/4 ración)',
                '10:00' => 'Merienda (1/4)',
                '13:00' => 'Almuerzo (1/4)',
                '16:00' => 'Merienda (1/4)',
                '19:00' => 'Cena (último 1/4)',
                '20:00' => 'Paseo corto (10-15 min)',
                '21:00' => 'Descanso'
            ],
            self::ETAPA_ADULTO => [
                '07:00' => 'Desayuno (1/2 ración)',
                '08:30' => 'Ejercicio mañana (30-45 min)',
                '12:00' => 'Juego/Estimulación (15 min)',
                '18:00' => 'Cena (1/2 ración)',
                '19:30' => 'Ejercicio tarde (30-45 min)',
                '20:00' => 'Revisión: orejas, patas, uñas',
                '21:00' => 'Descanso'
            ],
            self::ETAPA_SENIOR => [
                '07:00' => 'Desayuno (1/2 ración controlada)',
                '08:30' => 'Caminata suave (15-20 min)',
                '12:00' => 'Estimulación mental (10 min)',
                '18:00' => 'Cena (1/2 ración controlada)',
                '19:30' => 'Paseo corto y suave (15-20 min)',
                '20:00' => 'Revisión de confort',
                '21:00' => 'Descanso'
            ]
        ];
        
        $rutina = "☀️ RUTINA DIARIA - {$etapa['etapa']}\n\n";
        
        foreach ($rutinas[$etapa['etapa']] as $hora => $actividad) {
            $rutina .= "• {$hora} - {$actividad}\n";
        }
        
        if (!empty($this->enfermedadesDetectadas)) {
            $rutina .= "\n💊 CUIDADOS ESPECÍFICOS:\n";
            foreach ($this->enfermedadesDetectadas as $enf) {
                $datos = obtenerDatosEnfermedad($enf);
                if ($datos && !empty($datos['recomendaciones_diarias'])) {
                    $rutina .= "• {$datos['nombre']}: " . $datos['recomendaciones_diarias'][0] . "\n";
                }
            }
        }
        
        return $rutina;
    }
    
    private function generarRutinaSemanal() {
        $rutina = "📅 RUTINA SEMANAL\n\n";
        
        $actividades = [
            'Lunes' => 'Cepillado completo',
            'Martes' => 'Revisión de ojos',
            'Miércoles' => 'Revisión de oídos',
            'Jueves' => 'Cepillado de dientes',
            'Viernes' => 'Revisión de patas y uñas',
            'Sábado' => 'Juego interactivo extendido',
            'Domingo' => 'Baño e inspección profunda'
        ];
        
        foreach ($actividades as $dia => $actividad) {
            $rutina .= "• {$dia}: {$actividad}\n";
        }
        
        return $rutina;
    }
    
    private function generarRutinaMensual() {
        $rutina = "📊 RUTINA MENSUAL\n\n";
        
        $rutina .= "• Día 1: Pesada y registro\n";
        $rutina .= "• Semana 2: Evaluación condición corporal\n";
        $rutina .= "• Semana 3: Revisión comportamiento\n";
        $rutina .= "• Semana 4: Análisis general de salud\n\n";
        
        $rutina .= "📈 TRACKING:\n";
        $rutina .= "• Peso: gráfico mensual\n";
        $rutina .= "• Apetito: anotar cambios\n";
        $rutina .= "• Energía: evaluar actividad\n";
        $rutina .= "• Digestión: observar heces\n";
        
        return $rutina;
    }
    
    private function generarRecomendaciones() {
        $etapa = $this->getEtapaVida();
        
        $recs = "💡 RECOMENDACIONES\n\n";
        
        switch ($etapa['etapa']) {
            case self::ETAPA_CACHORRO:
                $recs .= "🐕 CACHORROS:\n";
                $recs .= "• Socialización temprana\n";
                $recs .= "• Vacunación según calendario\n";
                $recs .= "• Evitar ejercicio excesivo\n";
                break;
                
            case self::ETAPA_SENIOR:
                $recs .= "🐕 SENIORS:\n";
                $recs .= "• Chequeos cada 6 meses\n";
                $recs .= "• Monitorear artritis/dolor\n";
                $recs .= "• Suplementos articulares\n";
                break;
                
            default:
                $recs .= "🐕 ADULTOS:\n";
                $recs .= "• Rutina consistente\n";
                $recs .= "• Chequeo anual\n";
                $recs .= "• Desparasitación regular\n";
        }
        
        return $recs;
    }
    
    private function generarAlertas() {
        $sintomas = strtolower($this->overrides['sintomas'] ?? '');
        
        $urgencias = [
            'vomito_sangre' => '🚨 Vómito con sangre - ATENCIÓN INMEDIATA',
            'dificultad_respirar' => '🚨 Dificultad para respirar - URGENCIA',
            'convulsiones' => '🚨 Convulsiones - EMERGENCIA',
            'colapso' => '🚨 Colapso - EMERGENCIA'
        ];
        
        $alertas = [];
        foreach ($urgencias as $keyword => $mensaje) {
            if (strpos($sintomas, $keyword) !== false) {
                $alertas[] = $mensaje;
            }
        }
        
        if (empty($alertas)) {
            return null;
        }
        
        return "⚠️ ALERTAS:\n\n" . implode("\n", $alertas) . 
               "\n\n➡️ Contactar veterinario inmediatamente.";
    }
    
    private function guardarPlan($plan) {
        $fechaInicio = date('Y-m-d');
        $fechaFin = date('Y-m-d', strtotime('+30 days'));
        
        // Desactivar planes anteriores
        $this->pdo->prepare("UPDATE planes_salud SET activo = 0 WHERE mascota_id = ?")
                   ->execute([$this->mascota['id']]);
        
        // Insertar nuevo plan
        $sql = "INSERT INTO planes_salud 
                (mascota_id, fecha_inicio, fecha_fin, objetivo, plan_alimentacion, 
                 plan_ejercicio, vacunas_pendientes, examenes_recomendados, 
                 plan_bienestar_mental, plan_higiene, activo) 
                VALUES (:mascota_id, :inicio, :fin, :objetivo, :alimentacion, 
                        :ejercicio, :vacunas, :examenes, :rutina_d, :rutina_s, 1)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':mascota_id' => $this->mascota['id'],
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin,
            ':objetivo' => $plan['informacion'],
            ':alimentacion' => $plan['plan_alimentacion'],
            ':ejercicio' => $plan['plan_ejercicio'],
            ':vacunas' => "Cronograma preventivo anual",
            ':examenes' => "Chequeo físico cada 6 meses",
            ':rutina_d' => $plan['rutina_diaria'] . "\n\n" . $plan['rutina_semanal'],
            ':rutina_s' => $plan['rutina_mensual']
        ]);
        
        $planId = $this->pdo->lastInsertId();
        
        // Crear recordatorios automáticos
        $this->crearRecordatorios($planId);
        
        return [
            'success' => true,
            'plan_id' => $planId,
            'preview' => $plan,
            'mascota' => [
                'nombre' => $this->mascota['nombre'],
                'foto' => $this->mascota['foto_perfil'] ?? ''
            ]
        ];
    }
    
    private function crearRecordatorios($planId) {
        // Limpiar recordatorios antiguos
        $this->pdo->prepare("DELETE FROM recordatorios_plan WHERE plan_id IN 
            (SELECT id FROM planes_salud WHERE mascota_id = ?)")
            ->execute([$this->mascota['id']]);
        
        $insert = $this->pdo->prepare("INSERT INTO recordatorios_plan 
            (plan_id, tipo, descripcion, fecha_programada) VALUES (?, ?, ?, ?)");
        
        // Recordatorios diarios por 30 días
        for ($i = 0; $i < 30; $i++) {
            $fecha = date('Y-m-d', strtotime("+{$i} days"));
            $desc = "Seguir plan: alimentación + ejercicio según indicaciones";
            $insert->execute([$planId, 'rutina_diaria', $desc, $fecha]);
        }
        
        // Recordatorios semanales
        for ($w = 1; $w <= 4; $w++) {
            $fecha = date('Y-m-d', strtotime('+' . (3 + ($w - 1) * 7) . ' days'));
            $insert->execute([$planId, 'rutina_semanal', 
                'Revisión: orejas, patas, uñas, ojos', $fecha]);
        }
        
        // Recordatorio mensual
        $insert->execute([$planId, 'rutina_mensual',
            'Control de peso mensual', date('Y-m-d', strtotime('+30 days'))]);
    }
}
