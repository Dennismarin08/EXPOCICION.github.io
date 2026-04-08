<?php
/**
 * RUGAL - Funciones de Sistema de Puntos
 * Gestión de puntos, niveles y recompensas
 */

require_once 'db.php';

// =====================================================
// HELPER PARA TRANSACCIONES ANIDADAS
// =====================================================
function safeBeginTransaction($pdo) {
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        return true;
    }
    return false;
}

function safeCommit($pdo, $startedTransaction) {
    if ($startedTransaction && $pdo->inTransaction()) {
        $pdo->commit();
    }
}

function safeRollBack($pdo, $startedTransaction) {
    if ($startedTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

// =====================================================
// FUNCIONES DE PUNTOS
// =====================================================

/**
 * Otorgar puntos a un usuario
 */
function otorgarPuntos($userId, $puntos, $descripcion, $tareaId = null) {
    global $pdo;
    
    $startedTransaction = safeBeginTransaction($pdo);
    
    try {
        // 1. Actualizar puntos del usuario
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET puntos = puntos + ?, 
                total_puntos_ganados = total_puntos_ganados + ?
            WHERE id = ?
        ");
        $stmt->execute([$puntos, $puntos, $userId]);
        
        // 2. Registrar en historial
        $stmt = $pdo->prepare("
            INSERT INTO puntos_historial (user_id, puntos, tipo, descripcion, tarea_id)
            VALUES (?, ?, 'ganado', ?, ?)
        ");
        $stmt->execute([$userId, $puntos, $descripcion, $tareaId]);
        
        // 3. Actualizar nivel si es necesario
        actualizarNivel($userId);
        
        safeCommit($pdo, $startedTransaction);
        return true;
        
    } catch (Exception $e) {
        safeRollBack($pdo, $startedTransaction);
        error_log("Error al otorgar puntos: " . $e->getMessage());
        return false;
    }
}

/**
 * Descontar puntos a un usuario (para canjes)
 */
function descontarPuntos($userId, $puntos, $descripcion) {
    global $pdo;
    
    $startedTransaction = safeBeginTransaction($pdo);
    
    try {
        // Verificar que tiene suficientes puntos
        $stmt = $pdo->prepare("SELECT puntos FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || $user['puntos'] < $puntos) {
             safeRollBack($pdo, $startedTransaction);
            return false;
        }
        
        // Descontar puntos
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET puntos = puntos - ?
            WHERE id = ?
        ");
        $stmt->execute([$puntos, $userId]);
        
        // Registrar en historial
        $stmt = $pdo->prepare("
            INSERT INTO puntos_historial (user_id, puntos, tipo, descripcion)
            VALUES (?, ?, 'canjeado', ?)
        ");
        $stmt->execute([$userId, $puntos, $descripcion]);
        
        // Actualizar nivel
        actualizarNivel($userId);
        
        safeCommit($pdo, $startedTransaction);
        return true;
        
    } catch (Exception $e) {
        safeRollBack($pdo, $startedTransaction);
        error_log("Error al descontar puntos: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcular nivel según puntos totales ganados
 * Sistema simplificado: solo niveles numéricos (1-10)
 */
function calcularNivel($totalPuntos) {
    // Niveles basados en experiencia: 0-50 = Nivel 1, 51-150 = Nivel 2, etc.
    if ($totalPuntos >= 1000) {
        return 10;
    } elseif ($totalPuntos >= 800) {
        return 9;
    } elseif ($totalPuntos >= 600) {
        return 8;
    } elseif ($totalPuntos >= 450) {
        return 7;
    } elseif ($totalPuntos >= 320) {
        return 6;
    } elseif ($totalPuntos >= 210) {
        return 5;
    } elseif ($totalPuntos >= 120) {
        return 4;
    } elseif ($totalPuntos >= 50) {
        return 3;
    } elseif ($totalPuntos >= 15) {
        return 2;
    } else {
        return 1;
    }
}

/**
 * Actualizar nivel del usuario
 */
function actualizarNivel($userId) {
    global $pdo;
    
    try {
        // Obtener total de puntos ganados
        $stmt = $pdo->prepare("SELECT total_puntos_ganados FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        // Calcular nuevo nivel
        $nuevoNivel = calcularNivel($user['total_puntos_ganados']);
        
        // Actualizar en BD
        $stmt = $pdo->prepare("UPDATE usuarios SET nivel = ? WHERE id = ?");
        $stmt->execute([$nuevoNivel, $userId]);
        
        return $nuevoNivel;
        
    } catch (Exception $e) {
        error_log("Error al actualizar nivel: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener historial de puntos de un usuario
 */
function obtenerHistorialPuntos($userId, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM puntos_historial 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener historial: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener puntos actuales del usuario
 */
function obtenerPuntosUsuario($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT puntos, nivel, total_puntos_ganados 
            FROM usuarios 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Error al obtener puntos: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtener información del nivel actual
 * Sistema simplificado: solo niveles numéricos (1-10)
 */
function obtenerInfoNivel($nivel) {
    // Convertir a entero si viene como string
    $nivel = is_numeric($nivel) ? intval($nivel) : 1;
    
    $niveles = [
        1 => ['nombre' => 'Nivel 1', 'color' => '#6b7280', 'min_puntos' => 0, 'max_puntos' => 14, 'multiplicador' => 1.0, 'icono' => '🐣', 'beneficio' => '¡Empieza tu aventura!'],
        2 => ['nombre' => 'Nivel 2', 'color' => '#6b7280', 'min_puntos' => 15, 'max_puntos' => 49, 'multiplicador' => 1.0, 'icono' => '⭐', 'beneficio' => '¡Sigue así!'],
        3 => ['nombre' => 'Nivel 3', 'color' => '#6b7280', 'min_puntos' => 50, 'max_puntos' => 119, 'multiplicador' => 1.0, 'icono' => '🌟', 'beneficio' => '¡Muy bien!'],
        4 => ['nombre' => 'Nivel 4', 'color' => '#6b7280', 'min_puntos' => 120, 'max_puntos' => 209, 'multiplicador' => 1.0, 'icono' => '💫', 'beneficio' => '¡Excelente progreso!'],
        5 => ['nombre' => 'Nivel 5', 'color' => '#3b82f6', 'min_puntos' => 210, 'max_puntos' => 319, 'multiplicador' => 1.0, 'icono' => '🔥', 'beneficio' => '¡Racha en camino!'],
        6 => ['nombre' => 'Nivel 6', 'color' => '#3b82f6', 'min_puntos' => 320, 'max_puntos' => 449, 'multiplicador' => 1.0, 'icono' => '⚡', 'beneficio' => '¡Casi lo logras!'],
        7 => ['nombre' => 'Nivel 7', 'color' => '#8b5cf6', 'min_puntos' => 450, 'max_puntos' => 599, 'multiplicador' => 1.0, 'icono' => '💎', 'beneficio' => '¡Eres un crack!'],
        8 => ['nombre' => 'Nivel 8', 'color' => '#8b5cf6', 'min_puntos' => 600, 'max_puntos' => 799, 'multiplicador' => 1.0, 'icono' => '🏆', 'beneficio' => '¡DOBLE XP!'],
        9 => ['nombre' => 'Nivel 9', 'color' => '#f59e0b', 'min_puntos' => 800, 'max_puntos' => 999, 'multiplicador' => 1.0, 'icono' => '👑', 'beneficio' => '¡Leyenda!'],
        10 => ['nombre' => 'Nivel 10', 'color' => '#ef4444', 'min_puntos' => 1000, 'max_puntos' => 999999, 'multiplicador' => 1.0, 'icono' => '🦁', 'beneficio' => '¡MAESTRO!']
    ];
    
    return $niveles[$nivel] ?? $niveles[1];
}

/**
 * Calcular progreso al siguiente nivel
 * Sistema simplificado: solo niveles numéricos (1-10)
 */
function calcularProgresoNivel($totalPuntos) {
    $nivel = calcularNivel($totalPuntos);
    $infoNivel = obtenerInfoNivel($nivel);
    
    // Si ya está en nivel máximo (10)
    if ($nivel >= 10) {
        return [
            'nivel_actual' => $nivel,
            'progreso' => 100,
            'puntos_faltantes' => 0,
            'siguiente_nivel' => null
        ];
    }
    
    // Siguiente nivel
    $siguienteNivel = $nivel + 1;
    $infoSiguiente = obtenerInfoNivel($siguienteNivel);
    
    $puntosFaltantes = $infoSiguiente['min_puntos'] - $totalPuntos;
    $rangoNivel = $infoSiguiente['min_puntos'] - $infoNivel['min_puntos'];
    
    // Evitar división por cero
    if ($rangoNivel <= 0) {
        $progreso = 100;
    } else {
        $progreso = (($totalPuntos - $infoNivel['min_puntos']) / $rangoNivel) * 100;
    }
    
    return [
        'nivel_actual' => $nivel,
        'progreso' => round($progreso, 1),
        'puntos_faltantes' => max(0, $puntosFaltantes),
        'siguiente_nivel' => $siguienteNivel
    ];
}

// =====================================================
// FUNCIONES DE TAREAS
// =====================================================

/**
 * Obtener tareas disponibles
 */
function obtenerTareasDisponibles($tipo = null) {
    global $pdo;
    
    try {
        if ($tipo) {
            $stmt = $pdo->prepare("
                SELECT * FROM tareas_comunidad 
                WHERE tipo = ? AND activa = 1
                AND (fecha_limite IS NULL OR fecha_limite > NOW())
                ORDER BY tipo_acceso DESC, puntos DESC
            ");
            $stmt->execute([$tipo]);
        } else {
            $stmt = $pdo->query("
                SELECT * FROM tareas_comunidad 
                WHERE activa = 1
                AND (fecha_limite IS NULL OR fecha_limite > NOW())
                ORDER BY tipo_acceso DESC, tipo, puntos DESC
            ");
        }
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener tareas: " . $e->getMessage());
        return [];
    }
}

/**
 * Verificar si una tarea ya fue completada (alguna vez)
 */
function tareaYaCompletada($userId, $tareaId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM tareas_completadas 
            WHERE user_id = ? AND tarea_id = ?
        ");
        $stmt->execute([$userId, $tareaId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
        
    } catch (Exception $e) {
        error_log("Error al verificar tarea: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estado de validación de una tarea
 * Retorna: 'aprobada', 'pendiente', 'rechazada' o null
 */
function obtenerEstadoTarea($userId, $tareaId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT estado_validacion 
            FROM tareas_completadas 
            WHERE user_id = ? AND tarea_id = ?
            ORDER BY completada_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId, $tareaId]);
        $result = $stmt->fetch();
        
        return $result ? $result['estado_validacion'] : null;
        
    } catch (Exception $e) {
        error_log("Error al obtener estado tarea: " . $e->getMessage());
        return null;
    }
}

/**
 * Verificar si una tarea ya fue completada hoy
 */
function tareaCompletadaHoy($userId, $tareaId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM tareas_completadas 
            WHERE user_id = ? 
            AND tarea_id = ? 
            AND DATE(completada_at) = CURDATE()
        ");
        $stmt->execute([$userId, $tareaId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
        
    } catch (Exception $e) {
        error_log("Error al verificar tarea: " . $e->getMessage());
        return false;
    }
}

/**
 * Completar una tarea (con evidencia opcional)
 */
function completarTarea($userId, $tareaId, $evidencia = null) {
    global $pdo;
    
    try {
        // Obtener info de la tarea
        $stmt = $pdo->prepare("SELECT * FROM tareas_comunidad WHERE id = ? AND activa = 1");
        $stmt->execute([$tareaId]);
        $tarea = $stmt->fetch();
        
        if (!$tarea) {
            return ['success' => false, 'message' => 'Tarea no encontrada'];
        }

        // 0.5 Verificar acceso Premium
        require_once 'premium-functions.php';
        if ($tarea['tipo_acceso'] === 'premium' && !esPremium($userId)) {
            return ['success' => false, 'message' => 'Esta es una tarea Premium. ¡Mejora tu cuenta para participar!'];
        }
        
        // Verificar evidencia si es requerida
        $requiereEvidencia = $tarea['requiere_evidencia'] == 1;

        if ($requiereEvidencia && empty($evidencia)) {
             return ['success' => false, 'message' => 'Esta tarea requiere foto o video como evidencia'];
        }
        
        // Verificar si ya fue completada (para tareas diarias)
        if ($tarea['tipo'] === 'diaria' && tareaCompletadaHoy($userId, $tareaId)) {
            return ['success' => false, 'message' => 'Ya completaste esta tarea hoy'];
        }
        
        // Obtener multiplicador del nivel
        $userInfo = obtenerPuntosUsuario($userId);
        $nivelInfo = obtenerInfoNivel($userInfo['nivel']);
        $puntosFinales = round($tarea['puntos'] * $nivelInfo['multiplicador']);
        
        $startedTransaction = safeBeginTransaction($pdo);
        
        // Determinar estado de validación
        // Si NO requiere evidencia, se aprueba automáticamente
        $estadoValidacion = $requiereEvidencia ? 'pendiente' : 'aprobada';
        
        // Registrar tarea completada
        $stmt = $pdo->prepare("
            INSERT INTO tareas_completadas (user_id, tarea_id, puntos_ganados, evidencia, estado_validacion)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $tareaId, $puntosFinales, $evidencia, $estadoValidacion]);
        
        // Si no requiere validación, otorgar puntos inmediatamente
        $puntosOtorgados = false;
        if ($estadoValidacion === 'aprobada') {
            $puntosOtorgados = otorgarPuntos($userId, $puntosFinales, "Tarea: " . $tarea['titulo'], $tareaId);
            
            // GAMIFICACIÓN: Procesar racha y XP
            procesarGamificacion($userId);
        }
        
        safeCommit($pdo, $startedTransaction);
        
        if ($estadoValidacion === 'pendiente') {
            return [
                'success' => true, 
                'message' => '¡Tarea enviada! Espera la validación del administrador',
                'puntos' => $puntosFinales,
                'requiere_validacion' => true
            ];
        } else {
             return [
                'success' => true, 
                'message' => '¡Tarea completada automáticamente!',
                'puntos' => $puntosFinales,
                'requiere_validacion' => false
            ];
        }
        
    } catch (Exception $e) {
        safeRollBack($pdo, $startedTransaction ?? false);
        error_log("Error al completar tarea: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al completar tarea: ' . $e->getMessage()];
    }
}

/**
 * Aprobar tarea completada (para admin)
 */
function aprobarTarea($tareaCompletadaId) {
    global $pdo;
    
    $startedTransaction = safeBeginTransaction($pdo);
    
    try {
        // Obtener info de la tarea completada
        $stmt = $pdo->prepare("
            SELECT tc.*, t.titulo
            FROM tareas_completadas tc
            JOIN tareas_comunidad t ON tc.tarea_id = t.id
            WHERE tc.id = ? AND tc.estado_validacion = 'pendiente'
        ");
        $stmt->execute([$tareaCompletadaId]);
        $tareaCompletada = $stmt->fetch();
        
        if (!$tareaCompletada) {
            safeRollBack($pdo, $startedTransaction);
            return ['success' => false, 'message' => 'Tarea no encontrada o ya validada'];
        }
        
        // Actualizar estado
        $stmt = $pdo->prepare("
            UPDATE tareas_completadas 
            SET estado_validacion = 'aprobada'
            WHERE id = ?
        ");
        $stmt->execute([$tareaCompletadaId]);
        
        // Otorgar puntos
        otorgarPuntos(
            $tareaCompletada['user_id'], 
            $tareaCompletada['puntos_ganados'], 
            "Tarea aprobada: " . $tareaCompletada['titulo'],
            $tareaCompletada['tarea_id']
        );
        
        // GAMIFICACIÓN: Procesar racha y XP
        procesarGamificacion($tareaCompletada['user_id']);
        
        safeCommit($pdo, $startedTransaction);
        
        return ['success' => true, 'message' => 'Tarea aprobada y puntos otorgados'];
        
    } catch (Exception $e) {
        safeRollBack($pdo, $startedTransaction);
        error_log("Error al aprobar tarea: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al aprobar tarea'];
    }
}

/**
 * Rechazar tarea completada (para admin)
 */
function rechazarTarea($tareaCompletadaId, $comentario = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE tareas_completadas 
            SET estado_validacion = 'rechazada', comentario_admin = ?
            WHERE id = ? AND estado_validacion = 'pendiente'
        ");
        $stmt->execute([$comentario, $tareaCompletadaId]);
        
        return ['success' => true, 'message' => 'Tarea rechazada'];
        
    } catch (Exception $e) {
        error_log("Error al rechazar tarea: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al rechazar tarea'];
    }
}

/**
 * Revocar tarea ya aprobada (quitar puntos)
 */
function revocarTarea($tareaCompletadaId, $comentario = '') {
    global $pdo;
    
    $startedTransaction = safeBeginTransaction($pdo);
    
    try {
        
        // Obtener info de la tarea completada
        $stmt = $pdo->prepare("
            SELECT tc.*, t.titulo
            FROM tareas_completadas tc
            JOIN tareas_comunidad t ON tc.tarea_id = t.id
            WHERE tc.id = ? AND tc.estado_validacion = 'aprobada'
        ");
        $stmt->execute([$tareaCompletadaId]);
        $tareaCompletada = $stmt->fetch();
        
        if (!$tareaCompletada) {
            safeRollBack($pdo, $startedTransaction);
            return ['success' => false, 'message' => 'Tarea no encontrada o no aprobada'];
        }
        
        // Actualizar estado
        $stmt = $pdo->prepare("
            UPDATE tareas_completadas 
            SET estado_validacion = 'revocada', comentario_admin = ?
            WHERE id = ?
        ");
        $stmt->execute([$comentario, $tareaCompletadaId]);
        
        // Descontar puntos
        $userId = $tareaCompletada['user_id'];
        $puntos = $tareaCompletada['puntos_ganados'];
        
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET puntos = puntos - ?,
                total_puntos_ganados = total_puntos_ganados - ?
            WHERE id = ?
        ");
        $stmt->execute([$puntos, $puntos, $userId]);
        
        // Registrar en historial como 'revocado'
        $stmt = $pdo->prepare("
            INSERT INTO puntos_historial (user_id, puntos, tipo, descripcion, tarea_id)
            VALUES (?, ?, 'revocado', ?, ?)
        ");
        $stmt->execute([$userId, $puntos, "Puntos revocados: " . $tareaCompletada['titulo'], $tareaCompletada['tarea_id']]);
        
        // Recalcular nivel (podría bajar)
        actualizarNivel($userId);
        
        safeCommit($pdo, $startedTransaction);
        
        return ['success' => true, 'message' => 'Tarea revocada y puntos descontados'];
        
    } catch (Exception $e) {
        safeRollBack($pdo, $startedTransaction);
        error_log("Error al revocar tarea: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al revocar tarea'];
    }
}

/**
 * Obtener tareas completadas del usuario
 */
function obtenerTareasCompletadas($userId, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT tc.*, t.titulo, t.descripcion, t.icono
            FROM tareas_completadas tc
            JOIN tareas_comunidad t ON tc.tarea_id = t.id
            WHERE tc.user_id = ?
            ORDER BY tc.completada_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener tareas completadas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener estadísticas de tareas del día
 */
function obtenerEstadisticasDia($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as tareas_completadas,
                SUM(puntos_ganados) as puntos_hoy
            FROM tareas_completadas
            WHERE user_id = ? AND DATE(completada_at) = CURDATE()
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        return ['tareas_completadas' => 0, 'puntos_hoy' => 0];
    }
}

// =====================================================
// FUNCIONES DE GAMIFICACIÓN (BETA)
// =====================================================

/**
 * Calcular experiencia requerida para llegar al nivel N (desde nivel 1)
 * L1->L2: 5
 * L2->L3: 10
 * L3->L4: 20
 * Formula: Cumulative = Sum(5 * 2^(i-1)) for i=1 to L-1
 */
function obtenerExperienciaParaNivel($nivelObjetivo) {
    if ($nivelObjetivo <= 1) return 0;
    
    $total = 0;
    for ($i = 1; $i < $nivelObjetivo; $i++) {
        $total += 5 * pow(2, $i - 1);
    }
    return $total;
}

/**
 * Calcular el nivel actual basado en la experiencia total
 */
function calcularNivelNumerico($xpTotal) {
    $nivel = 1;
    while (true) {
        $siguiente = $nivel + 1;
        $req = obtenerExperienciaParaNivel($siguiente);
        if ($xpTotal >= $req) {
            $nivel++;
        } else {
            break;
        }
    }
    return $nivel;
}

/**
 * Procesar progreso de gamificación (racha y niveles)
 * Se llama al completar una tarea.
 */
function procesarGamificacion($userId) {
    global $pdo;

    try {
        // Obtener datos actuales
        $stmt = $pdo->prepare("SELECT nivel_numerico, experiencia_nivel, racha_dias, ultima_tarea_fecha FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return false;

        $hoy = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));
        $ultimaFecha = $user['ultima_tarea_fecha'];
        
        $nuevaRacha = $user['racha_dias'];
        $xpGanada = 1; // Base 1 por tarea

        // 1. Calcular Racha
        if ($ultimaFecha === $hoy) {
            // Ya completó hoy, mantiene racha
            // No aumenta racha, pero sigue sumando XP (con mult si aplica)
            // User said: "hace 1 tarea y le cuenta x2" if streak >= 8
        } elseif ($ultimaFecha === $ayer) {
            // Racha continua
            $nuevaRacha++;
        } else {
            // Racha rota (o primera vez)
            $nuevaRacha = 1;
        }

        // 2. Aplicar Multiplicador de Racha (>= 8 días)
        if ($nuevaRacha >= 8) {
            $xpGanada *= 2;
        }

        // 3. Actualizar XP
        $nuevaXP = $user['experiencia_nivel'] + $xpGanada;

        // 4. Calcular Nuevo Nivel
        $nuevoNivel = calcularNivelNumerico($nuevaXP);

        // 5. Guardar en BD
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET racha_dias = ?, 
                experiencia_nivel = ?, 
                nivel_numerico = ?, 
                ultima_tarea_fecha = ? 
            WHERE id = ?
        ");
        $stmt->execute([$nuevaRacha, $nuevaXP, $nuevoNivel, $hoy, $userId]);

        return [
            'level_up' => ($nuevoNivel > $user['nivel_numerico']),
            'nuevo_nivel' => $nuevoNivel,
            'xp_ganada' => $xpGanada,
            'racha' => $nuevaRacha
        ];

    } catch (Exception $e) {
        error_log("Error en procesarGamificacion: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener info detallada de gamificación para UI
 */
function obtenerInfoGamificacion($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT nivel_numerico, experiencia_nivel, racha_dias FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) return null;
    
    $nivelActual = $data['nivel_numerico'] ?? 1;
    $xpTotal = $data['experiencia_nivel'] ?? 0;
    
    // Calcular rangos para la barra de progreso
    $xpInicioNivel = obtenerExperienciaParaNivel($nivelActual);
    $xpSiguienteNivel = obtenerExperienciaParaNivel($nivelActual + 1);
    
    $xpEnNivel = $xpTotal - $xpInicioNivel;
    $xpRequeridaNivel = $xpSiguienteNivel - $xpInicioNivel;
    
    $progreso = ($xpRequeridaNivel > 0) ? ($xpEnNivel / $xpRequeridaNivel) * 100 : 0;
    
    return [
        'nivel' => $nivelActual,
        'racha' => $data['racha_dias'] ?? 0,
        'xp_total' => $xpTotal,
        'xp_actual' => $xpEnNivel,
        'xp_required' => $xpRequeridaNivel, // Tareas para el siguiente nivel
        'progreso_porcentaje' => min(100, max(0, $progreso)),
        'multiplicador_activo' => ($data['racha_dias'] >= 8)
    ];
}

// =====================================================
// FUNCIONES DE RECOMPENSAS
// =====================================================

/**
 * Obtener recompensas disponibles
 */
function obtenerRecompensas($userId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT *, 
                   (CASE WHEN es_gratis = 1 THEN 0 ELSE precio_oferta END) as precio_final
            FROM recompensas 
            WHERE activa = 1 
            AND (stock > 0 OR stock = -1)
            AND (fecha_limite IS NULL OR fecha_limite > NOW())
            ORDER BY tipo_acceso DESC, puntos_requeridos ASC
        ");
        $recompensas = $stmt->fetchAll();
        
        // Si se proporciona userId, marcar cuáles puede canjear
        if ($userId) {
            $userInfo = obtenerPuntosUsuario($userId);
            foreach ($recompensas as &$recompensa) {
                $recompensa['puede_canjear'] = $userInfo['puntos'] >= $recompensa['puntos_requeridos'];
            }
        }
        
        return $recompensas;
        
    } catch (Exception $e) {
        error_log("Error al obtener recompensas: " . $e->getMessage());
        return [];
    }
}

/**
 * Canjear puntos por una recompensa (FLUJO AUTOMÁTICO V2)
 * Resta los puntos inmediatamente y genera el código.
 */
function canjearRecompensa($userId, $recompensaId) {
    global $pdo;
    
    $startedTransaction = safeBeginTransaction($pdo);
    
    try {
        // 1. Verificar si ya canjeó esta recompensa (Límite 1 vez)
        $stmt = $pdo->prepare("SELECT id FROM canjes WHERE user_id = ? AND recompensa_id = ?");
        $stmt->execute([$userId, $recompensaId]);
        if ($stmt->fetch()) {
            safeRollBack($pdo, $startedTransaction);
            return ['success' => false, 'message' => 'Ya has canjeado esta recompensa anteriormente.'];
        }

        // 2. Obtener recompensa
        $stmt = $pdo->prepare("SELECT * FROM recompensas WHERE id = ? AND activa = 1");
        $stmt->execute([$recompensaId]);
        $recompensa = $stmt->fetch();
        
        if (!$recompensa) {
            safeRollBack($pdo, $startedTransaction);
            return ['success' => false, 'message' => 'Recompensa no encontrada'];
        }
        
        // 2.5 Verificar acceso Premium
        require_once 'premium-functions.php';
        if ($recompensa['tipo_acceso'] === 'premium' && !esPremium($userId)) {
            safeRollBack($pdo, $startedTransaction);
            return ['success' => false, 'message' => 'Esta es una recompensa Premium. ¡Mejora tu cuenta para obtenerla!'];
        }
        
        // 3. Verificar stock
        if ($recompensa['stock'] == 0) {
            safeRollBack($pdo, $startedTransaction);
            return ['success' => false, 'message' => 'Recompensa agotada'];
        }

        // 4. Verificar fecha límite
        if ($recompensa['fecha_limite'] && strtotime($recompensa['fecha_limite']) < time()) {
            safeRollBack($pdo, $startedTransaction);
            return ['success' => false, 'message' => 'Esta oferta ha expirado'];
        }
        
        // 5. Verificar puntos
        $userInfo = obtenerPuntosUsuario($userId);
        if ($userInfo['puntos'] < $recompensa['puntos_requeridos']) {
            safeRollBack($pdo, $startedTransaction);
            return ['success' => false, 'message' => 'Puntos insuficientes'];
        }
        
        // 6. Generar Código y Activar Automáticamente
        $codigo = 'RUGAL-' . strtoupper(bin2hex(random_bytes(4)));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+48 hours')); // 48h por defecto

        // 7. Registrar canje ACTIVO e INSTANTÁNEO
        $stmt = $pdo->prepare("
            INSERT INTO canjes (user_id, recompensa_id, puntos_gastados, estado, codigo_canje, fecha_expiracion, created_at)
            VALUES (?, ?, ?, 'activo', ?, ?, NOW())
        ");
        $stmt->execute([$userId, $recompensaId, $recompensa['puntos_requeridos'], $codigo, $expiresAt]);
        $canjeId = $pdo->lastInsertId();
        
        // 8. Descontar puntos INMEDIATAMENTE
        descontarPuntos($userId, $recompensa['puntos_requeridos'], "Canje Directo: " . $recompensa['titulo']);
        
        // 9. Actualizar stock si no es ilimitado
        if ($recompensa['stock'] > 0) {
            $stmt = $pdo->prepare("UPDATE recompensas SET stock = stock - 1 WHERE id = ?");
            $stmt->execute([$recompensaId]);
        }
        
        safeCommit($pdo, $startedTransaction);
        
        return [
            'success' => true,
            'message' => '¡Recompensa activada con éxito!',
            'canje_id' => $canjeId,
            'codigo' => $codigo
        ];
        
    } catch (Exception $e) {
        safeRollBack($pdo, $startedTransaction);
        error_log("Error al canjear: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al procesar el canje'];
    }
}

/**
 * Activar un canje adquirido (Paso 2: Activación)
 * Genera el código y asigna el aliado elegido. Inicia reloj de 24h.
 */
function activarCanje($canjeId, $aliadoId, $userId) {
    global $pdo;
    
    try {
        // 1. Obtener el canje y validar que pertenece al usuario y está pendiente
        $stmt = $pdo->prepare("
            SELECT c.*, r.alcance_tipo, r.alcance_valor 
            FROM canjes c
            JOIN recompensas r ON c.recompensa_id = r.id
            WHERE c.id = ? AND c.user_id = ? AND c.estado = 'pendiente'
        ");
        $stmt->execute([$canjeId, $userId]);
        $canje = $stmt->fetch();
        
        if (!$canje) {
            return ['success' => false, 'message' => 'Cupón no encontrado o ya activado.'];
        }

        // 2. Validar que el aliado elegido es válido para esta recompensa
        // (Esto es lo que el admin configuró: global, por tipo o específicos)
        $aliadoValido = false;
        $stmtAlt = $pdo->prepare("SELECT tipo FROM aliados WHERE id = ? AND activo = 1");
        $stmtAlt->execute([$aliadoId]);
        $aliadoInfo = $stmtAlt->fetch();
        
        if (!$aliadoInfo) {
            return ['success' => false, 'message' => 'Local no encontrado o inactivo.'];
        }

        if ($canje['alcance_tipo'] === 'global') {
            $aliadoValido = true;
        } elseif ($canje['alcance_tipo'] === 'tipo_aliado') {
            if ($aliadoInfo['tipo'] === $canje['alcance_valor']) $aliadoValido = true;
        } elseif ($canje['alcance_tipo'] === 'especificos') {
            $ids = explode(',', $canje['alcance_valor']);
            if (in_array($aliadoId, $ids)) $aliadoValido = true;
        }

        if (!$aliadoValido) {
            return ['success' => false, 'message' => 'Este local no participa en esta recompensa según configuración de administrador.'];
        }

        // 3. Generar código y activar
        $codigo = 'RUGAL-' . strtoupper(bin2hex(random_bytes(6)));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $pdo->prepare("
            UPDATE canjes 
            SET aliado_id = ?, 
                codigo_canje = ?, 
                fecha_expiracion = ?, 
                estado = 'usado',
                usado_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$aliadoId, $codigo, $expiresAt, $canjeId]);
        
        return [
            'success' => true,
            'message' => '¡Cupón activado! Tienes 24 horas para reclamarlo.',
            'codigo' => $codigo,
            'fecha_expiracion' => $expiresAt
        ];
        
    } catch (Exception $e) {
        error_log("Error al activar canje: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno al activar el cupón.'];
    }
}

/**
 * Obtener canjes del usuario (incluyendo info de aliado si existe)
 */
function obtenerCanjesUsuario($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, r.titulo, r.descripcion, r.tipo, r.ubicacion_canje, r.alcance_tipo, r.alcance_valor, a.nombre_local as aliado_nombre
            FROM canjes c
            JOIN recompensas r ON c.recompensa_id = r.id
            LEFT JOIN aliados a ON c.aliado_id = a.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener canjes: " . $e->getMessage());
        return [];
    }
}

/**
 * Validar código de canje (para aliados)
 */
function validarCodigoCanje($codigo, $aliadoId = null) {
    global $pdo;
    
    try {
        $sql = "
            SELECT c.*, r.titulo, r.descripcion, r.producto_id, r.producto_tabla, u.nombre as usuario_nombre, a.nombre_local as aliado_nombre
            FROM canjes c
            JOIN recompensas r ON c.recompensa_id = r.id
            JOIN usuarios u ON c.user_id = u.id
            LEFT JOIN aliados a ON c.aliado_id = a.id
            WHERE c.codigo_canje = ?
        ";
        
        $params = [$codigo];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $canje = $stmt->fetch();
        
        if (!$canje) {
            return ['success' => false, 'message' => 'Código no encontrado'];
        }

        // NUEVO: Si tiene producto vinculado, obtener info del producto
        if (!empty($canje['producto_id']) && !empty($canje['producto_tabla'])) {
            $pId = $canje['producto_id'];
            $pTable = $canje['producto_tabla'];
            
            // Validar que la tabla sea segura (whitelist)
            if ($pTable === 'productos_tienda' || $pTable === 'productos_veterinaria') {
                $stmtP = $pdo->prepare("SELECT nombre as producto_nombre FROM $pTable WHERE id = ?");
                $stmtP->execute([$pId]);
                $prod = $stmtP->fetch();
                if ($prod) {
                    $canje['producto_vinc_nombre'] = $prod['producto_nombre'];
                }
            }
        }
        
        // Verificar si pertenece a este aliado (si se especifica)
        if ($aliadoId !== null && $canje['aliado_id'] !== null && $canje['aliado_id'] != $aliadoId) {
             return ['success' => false, 'message' => 'Este código debe ser canjeado en: ' . ($canje['aliado_nombre'] ?? 'otro local'), 'canje' => $canje];
        }
        
        // Verificar estado
        if ($canje['estado'] === 'pendiente') {
            return ['success' => false, 'message' => 'Este cupón aún no ha sido activado por el usuario.'];
        }
        
        if ($canje['estado'] === 'usado') {
            return ['success' => false, 'message' => 'Este código ya fue utilizado el ' . date('d/m/Y H:i', strtotime($canje['usado_at'])), 'canje' => $canje];
        }
        
        $expDate = new DateTime($canje['fecha_expiracion']);
        if ($canje['estado'] === 'expirado' || (new DateTime() > $expDate)) {
            return ['success' => false, 'message' => 'Este código ha expirado', 'canje' => $canje];
        }
        
        if ($canje['estado'] !== 'activo') {
            return ['success' => false, 'message' => 'El estado del cupón no permite validación (' . $canje['estado'] . ')'];
        }
        
        return ['success' => true, 'canje' => $canje];
        
    } catch (Exception $e) {
        error_log("Error al validar código: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno al validar: ' . $e->getMessage()];
    }
}

/**
 * Marcar canje como usado
 */
function marcarCanjeUsado($codigo, $aliadoId) {
    global $pdo;
    
    try {
        // Primero validamos una última vez
        $validacion = validarCodigoCanje($codigo, $aliadoId);
        if (!$validacion['success']) return $validacion;
        
        $stmt = $pdo->prepare("
            UPDATE canjes 
            SET estado = 'usado', usado_at = NOW() 
            WHERE codigo_canje = ? AND estado = 'activo'
        ");
        $stmt->execute([$codigo]);
        
        if ($stmt->rowCount() > 0) {
            // DESCUENTO DE PUNTOS REAL AL COMPLETAR EL CANJE
            $canjeInfo = $validacion['canje'];
            descontarPuntos($canjeInfo['user_id'], $canjeInfo['puntos_gastados'], "Canje Realizado: " . $canjeInfo['titulo']);
            
            return ['success' => true, 'message' => 'Canje realizado con éxito'];
        } else {
            return ['success' => false, 'message' => 'No se pudo actualizar el estado del canje'];
        }
        
    } catch (Exception $e) {
        error_log("Error al marcar como usado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al procesar el canje'];
    }
}
