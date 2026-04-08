<?php
/**
 * RUGAL - Funciones de Sistema Premium
 * Gestión de suscripciones y acceso premium
 */

require_once 'db.php';

// =====================================================
// FUNCIONES DE VERIFICACIÓN PREMIUM
// =====================================================

/**
 * Verificar si un usuario tiene premium activo
 */
function esPremium($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM suscripciones
            WHERE user_id = ?
            AND estado = 'activa'
            AND fecha_fin >= CURDATE()
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
        
    } catch (Exception $e) {
        error_log("Error al verificar premium: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener suscripción activa del usuario
 */
function obtenerSuscripcionActiva($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, p.nombre as plan_nombre, p.descripcion as plan_descripcion
            FROM suscripciones s
            JOIN planes_premium p ON s.plan_id = p.id
            WHERE s.user_id = ?
            AND s.estado = 'activa'
            AND s.fecha_fin >= CURDATE()
            ORDER BY s.fecha_fin DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Error al obtener suscripción: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtener días restantes de premium
 */
function diasRestantesPremium($userId) {
    $suscripcion = obtenerSuscripcionActiva($userId);
    
    if (!$suscripcion) {
        return 0;
    }
    
    $fechaFin = new DateTime($suscripcion['fecha_fin']);
    $hoy = new DateTime();
    $diff = $hoy->diff($fechaFin);
    
    return $diff->days;
}

/**
 * Redirigir a página de upgrade si no es premium
 */
function requierePremium($userId, $redirectUrl = 'upgrade-premium.php') {
    if (!esPremium($userId)) {
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// =====================================================
// FUNCIONES DE PLANES
// =====================================================

/**
 * Obtener todos los planes premium
 */
function obtenerPlanesPremium() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT * FROM planes_premium
            WHERE activo = 1
            ORDER BY duracion_dias ASC
        ");
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener planes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener plan por ID
 */
function obtenerPlan($planId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM planes_premium WHERE id = ?");
        $stmt->execute([$planId]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Error al obtener plan: " . $e->getMessage());
        return null;
    }
}

// =====================================================
// FUNCIONES DE SUSCRIPCIÓN
// =====================================================

/**
 * Crear suscripción premium
 */
function crearSuscripcion($userId, $planId, $metodoPago, $montoPagado) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Obtener plan
        $plan = obtenerPlan($planId);
        if (!$plan) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Plan no encontrado'];
        }
        
        // Calcular fechas
        $fechaInicio = date('Y-m-d');
        $fechaFin = date('Y-m-d', strtotime("+{$plan['duracion_dias']} days"));
        
        // Crear suscripción
        $stmt = $pdo->prepare("
            INSERT INTO suscripciones (user_id, plan_id, fecha_inicio, fecha_fin, metodo_pago, monto_pagado)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $planId, $fechaInicio, $fechaFin, $metodoPago, $montoPagado]);
        $suscripcionId = $pdo->lastInsertId();
        
        // Registrar pago
        $stmt = $pdo->prepare("
            INSERT INTO pagos (user_id, suscripcion_id, monto, metodo_pago, estado)
            VALUES (?, ?, ?, ?, 'completado')
        ");
        $stmt->execute([$userId, $suscripcionId, $montoPagado, $metodoPago]);
        
        // Actualizar campo premium en usuarios
        $stmt = $pdo->prepare("UPDATE usuarios SET premium = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => '¡Suscripción activada!',
            'suscripcion_id' => $suscripcionId,
            'fecha_fin' => $fechaFin
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al crear suscripción: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al procesar suscripción'];
    }
}

/**
 * Cancelar suscripción
 */
function cancelarSuscripcion($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE suscripciones
            SET estado = 'cancelada'
            WHERE user_id = ?
            AND estado = 'activa'
        ");
        $stmt->execute([$userId]);
        
        // Actualizar campo premium en usuarios
        $stmt = $pdo->prepare("UPDATE usuarios SET premium = 0 WHERE id = ?");
        $stmt->execute([$userId]);
        
        return ['success' => true, 'message' => 'Suscripción cancelada'];
        
    } catch (Exception $e) {
        error_log("Error al cancelar suscripción: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al cancelar suscripción'];
    }
}

/**
 * Actualizar suscripciones expiradas (ejecutar diariamente)
 */
function actualizarSuscripcionesExpiradas() {
    global $pdo;
    
    try {
        // Marcar suscripciones expiradas
        $stmt = $pdo->query("
            UPDATE suscripciones
            SET estado = 'expirada'
            WHERE estado = 'activa'
            AND fecha_fin < CURDATE()
        ");
        
        // Actualizar campo premium en usuarios sin suscripción activa
        $stmt = $pdo->query("
            UPDATE usuarios u
            SET premium = 0
            WHERE premium = 1
            AND NOT EXISTS (
                SELECT 1 FROM suscripciones s
                WHERE s.user_id = u.id
                AND s.estado = 'activa'
                AND s.fecha_fin >= CURDATE()
            )
        ");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error al actualizar suscripciones: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener historial de suscripciones del usuario
 */
function obtenerHistorialSuscripciones($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, p.nombre as plan_nombre
            FROM suscripciones s
            JOIN planes_premium p ON s.plan_id = p.id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error al obtener historial: " . $e->getMessage());
        return [];
    }
}


