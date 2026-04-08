<?php
/**
 * RUGAL - Funciones del Sistema de Citas
 * Gestión de citas médicas veterinarias
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../premium-functions.php';


/**
 * Verificar si usuario puede crear más citas
 * ACTUALIZADO: Citas ilimitadas para TODOS (Free y Premium)
 */
function puedeCrearCita($userId) {
    // Todos los usuarios pueden crear citas ilimitadas
    return ['permitido' => true];
}

/**
 * Obtener disponibilidad de una veterinaria para un día específico
 */
function obtenerDisponibilidad($veterinariaId, $fecha) {
    global $pdo;
    
    $diaSemana = date('w', strtotime($fecha)); // 0=Domingo, 6=Sábado
    
    // Obtener configuración de horario
    $stmt = $pdo->prepare("
        SELECT * FROM disponibilidad_veterinaria 
        WHERE veterinaria_id = ? 
        AND dia_semana = ? 
        AND activo = 1
    ");
    $stmt->execute([$veterinariaId, $diaSemana]);
    $config = $stmt->fetch();
    
    if (!$config) {
        return []; // No trabaja este día
    }
    
    // Generar slots de tiempo
    $slots = [];
    $horaInicio = strtotime($config['hora_inicio']);
    $horaFin = strtotime($config['hora_fin']);
    $duracion = $config['duracion_cita'] * 60; // convertir a segundos
    
    $horaActual = $horaInicio;
    while ($horaActual < $horaFin) {
        $horaSlot = date('H:i:s', $horaActual);
        $fechaHora = $fecha . ' ' . $horaSlot;
        
        // Verificar si ya está ocupado
        $ocupado = verificarSlotOcupado($veterinariaId, $fechaHora);
        
        // Verificar si hay bloqueo
        $bloqueado = verificarBloqueo($veterinariaId, $fechaHora);
        
        $slots[] = [
            'hora' => $horaSlot,
            'hora_formateada' => date('h:i A', $horaActual),
            'fecha_hora' => $fechaHora,
            'disponible' => !$ocupado && !$bloqueado,
            'ocupado' => $ocupado,
            'bloqueado' => $bloqueado
        ];
        
        $horaActual += $duracion;
    }
    
    return $slots;
}

/**
 * Verificar si un slot está ocupado
 */
function verificarSlotOcupado($veterinariaId, $fechaHora) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM citas 
        WHERE veterinaria_id = ? 
        AND fecha_hora = ?
        AND estado IN ('pendiente', 'confirmada')
    ");
    $stmt->execute([$veterinariaId, $fechaHora]);
    $result = $stmt->fetch();
    
    return $result['total'] > 0;
}

/**
 * Verificar si hay un bloqueo de horario
 */
function verificarBloqueo($veterinariaId, $fechaHora) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM bloqueos_horario 
        WHERE veterinaria_id = ? 
        AND ? BETWEEN fecha_inicio AND fecha_fin
    ");
    $stmt->execute([$veterinariaId, $fechaHora]);
    $result = $stmt->fetch();
    
    return $result['total'] > 0;
}

/**
 * Crear una nueva cita
 */
function crearCita($datos) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO citas (
                user_id, mascota_id, veterinaria_id, fecha_hora,
                tipo_cita, motivo, precio_total, anticipo_requerido,
                porcentaje_anticipo, es_manual, notas_usuario
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $anticipo = $datos['precio_total'] * ($datos['porcentaje_anticipo'] / 100);
        
        $stmt->execute([
            $datos['user_id'],
            $datos['mascota_id'],
            $datos['veterinaria_id'],
            $datos['fecha_hora'],
            $datos['tipo_cita'] ?? 'consulta',
            $datos['motivo'] ?? '',
            $datos['precio_total'],
            $anticipo,
            $datos['porcentaje_anticipo'] ?? 50,
            $datos['es_manual'] ?? 0,
            $datos['notas_usuario'] ?? ''
        ]);
        
        return [
            'success' => true,
            'cita_id' => $pdo->lastInsertId(),
            'anticipo_requerido' => $anticipo
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Obtener citas de un usuario
 */
function obtenerCitasUsuario($userId, $filtro = 'todas') {
    global $pdo;
    
    $where = "c.user_id = ?";
    $params = [$userId];
    
    switch ($filtro) {
        case 'proximas':
            $where .= " AND c.fecha_hora >= NOW() AND c.estado IN ('pendiente', 'confirmada')";
            break;
        case 'pasadas':
            $where .= " AND (c.fecha_hora < NOW() OR c.estado IN ('completada', 'cancelada'))";
            break;
        case 'pendientes':
            $where .= " AND c.estado = 'pendiente'";
            break;
    }
    
    $stmt = $pdo->prepare("
        SELECT c.*, 
               m.nombre as mascota_nombre,
               a.nombre_local as veterinaria_nombre,
               a.direccion as veterinaria_direccion
        FROM citas c
        JOIN mascotas m ON c.mascota_id = m.id
        JOIN aliados a ON c.veterinaria_id = a.id
        WHERE $where
        ORDER BY c.fecha_hora DESC
    ");
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Subir comprobante de pago
 */
function subirComprobantePago($citaId, $archivo) {
    global $pdo;
    
    $uploadDir = __DIR__ . '/../uploads/comprobantes/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'comprobante_' . $citaId . '_' . time() . '.' . $extension;
    $rutaDestino = $uploadDir . $nombreArchivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        // Actualizar BD
        $stmt = $pdo->prepare("
            UPDATE citas 
            SET comprobante_pago = ?, 
                estado = 'confirmada',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute(['comprobantes/' . $nombreArchivo, $citaId]);
        
        return [
            'success' => true,
            'archivo' => $nombreArchivo
        ];
    }
    
    return ['success' => false, 'error' => 'Error al subir archivo'];
}

/**
 * Cancelar cita
 */
function cancelarCita($citaId, $userId) {
    global $pdo;
    
    // Verificar que la cita pertenece al usuario
    $stmt = $pdo->prepare("SELECT user_id FROM citas WHERE id = ?");
    $stmt->execute([$citaId]);
    $cita = $stmt->fetch();
    
    if (!$cita || $cita['user_id'] != $userId) {
        return ['success' => false, 'error' => 'Cita no encontrada'];
    }
    
    $stmt = $pdo->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ?");
    $stmt->execute([$citaId]);
    
    return ['success' => true];
}

/**
 * Obtener citas de una veterinaria
 */
function obtenerCitasVeterinaria($veterinariaId, $fecha = null) {
    global $pdo;
    
    $where = "c.veterinaria_id = ?";
    $params = [$veterinariaId];
    
    if ($fecha) {
        $where .= " AND DATE(c.fecha_hora) = ?";
        $params[] = $fecha;
    }
    
    $stmt = $pdo->prepare("
        SELECT c.*, 
               m.nombre as mascota_nombre,
               m.tipo as mascota_tipo,
               m.raza as mascota_raza,
               u.nombre as dueno_nombre,
               u.telefono as dueno_telefono
        FROM citas c
        JOIN mascotas m ON c.mascota_id = m.id
        JOIN usuarios u ON c.user_id = u.id
        WHERE $where
        ORDER BY c.fecha_hora ASC
    ");
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}
