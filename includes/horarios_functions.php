<?php
/**
 * RUGAL - Horarios Helpers
 */

/**
 * Checks if an ally is currently open based on their schedule JSON
 * @param string $horarioJson The JSON string from database
 * @return array ['status' => 'open'|'closed', 'label' => 'Abierto Ahora'|'Cerrado Ahora', 'color' => '#...']
 */
function getAllyCurrentStatus($horarioJsonRaw) {
    if (!$horarioJsonRaw) {
        return ['status' => 'closed', 'label' => 'Sin Horario', 'color' => '#94a3b8'];
    }

    $dias_map = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];

    $dia_hoy = $dias_map[date('l')];
    $hora_ahora = date('H:i');

    // Clean control characters
    $cleanHorario = preg_replace('/[[:cntrl:]]/', '', $horarioJsonRaw);
    $horario = json_decode($cleanHorario, true);

    if ($horario && isset($horario[$dia_hoy])) {
        $h = $horario[$dia_hoy];
        if (($h['abierto'] ?? '0') == '1') {
            $apertura = $h['apertura'] ?? '00:00';
            $cierre = $h['cierre'] ?? '23:59';
            
            if ($hora_ahora >= $apertura && $hora_ahora < $cierre) {
                return [
                    'status' => 'open',
                    'label' => 'Abierto Ahora',
                    'color' => '#10b981',
                    'badge_class' => 'status-open',
                    'dia_espanol' => $dia_hoy
                ];
            }
        }
    }

    return [
        'status' => 'closed',
        'label' => 'Cerrado Ahora',
        'color' => '#ef4444',
        'badge_class' => 'status-closed',
        'dia_espanol' => $dia_hoy
    ];
}
