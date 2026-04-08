<?php
require_once __DIR__ . '/../db.php';

function guardarSeguimiento($pdo, $user_id, $mascota_id, $fecha, $datos, $observaciones = '') {
    $stmt = $pdo->prepare("INSERT INTO seguimientos_diarios (mascota_id, user_id, fecha, datos, observaciones) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$mascota_id, $user_id, $fecha, json_encode($datos), $observaciones]);
    $id = $pdo->lastInsertId();

    // Detectar alertas según reglas
    $alertas = detectarAlertas($pdo, $id, $datos);

    return ['id' => $id, 'alertas' => $alertas];
}

function detectarAlertas($pdo, $seguimiento_id, $datos) {
    $alertasGeneradas = [];

    // Normalizar flags
    $s = $datos; // shorthand

    // Reglas de ejemplo
    // Digestiva
    if (!empty($s['vomitos']) && in_array($s['apetito'] ?? 'Normal', ['Hiporexia','Anorexia']) && ($s['actividad'] ?? 'Normal') === 'Letárgico') {
        $tipo = 'Alerta Digestiva';
        $exp = 'Combinación de vómitos + disminución del apetito + letargia: compatible con compromiso gastrointestinal.';
        $sintomas = ['vomitos','apetito','actividad'];
        $alertasGeneradas[] = crearAlerta($pdo, $seguimiento_id, $tipo, $sintomas, $exp);
    }

    // Dermatológica
    if (!empty($s['prurito']) && !empty($s['lesiones_cutaneas']) && !empty($s['alopecia'])) {
        $tipo = 'Alerta Dermatológica';
        $exp = 'Prurito + lesiones cutáneas + alopecia: patrón compatible con dermatitis o infestaciones cutáneas.';
        $sintomas = ['prurito','lesiones_cutaneas','alopecia'];
        $alertasGeneradas[] = crearAlerta($pdo, $seguimiento_id, $tipo, $sintomas, $exp);
    }

    // Metabólica
    if (($s['consumo_agua'] ?? 'Normal') === 'Polidipsia' && ($s['orina'] ?? 'Normal') === 'Poliuria') {
        $tipo = 'Alerta Metabólica';
        $exp = 'Polidipsia y poliuria: posible alteración endocrina (ej. diabetes) o renal. Requiere evaluación.';
        $sintomas = ['consumo_agua','orina'];
        $alertasGeneradas[] = crearAlerta($pdo, $seguimiento_id, $tipo, $sintomas, $exp);
    }

    // Locomotora
    if (!empty($s['claudicacion']) && !empty($s['dolor_al_tocar'])) {
        $tipo = 'Alerta Locomotora';
        $exp = 'Claudicación y dolor a la palpación: posible compromiso ortopédico o articular.';
        $sintomas = ['claudicacion','dolor_al_tocar'];
        $alertasGeneradas[] = crearAlerta($pdo, $seguimiento_id, $tipo, $sintomas, $exp);
    }

    return $alertasGeneradas;
}

function crearAlerta($pdo, $seguimiento_id, $tipo, $sintomas, $explicacion) {
    $stmt = $pdo->prepare("INSERT INTO alertas (seguimiento_id, tipo, sintomas, explicacion) VALUES (?, ?, ?, ?)");
    $stmt->execute([$seguimiento_id, $tipo, json_encode($sintomas), $explicacion]);
    return ['id' => $pdo->lastInsertId(), 'tipo' => $tipo, 'sintomas' => $sintomas, 'explicacion' => $explicacion];
}

function obtenerSeguimientos($pdo, $mascota_id, $desde = null, $hasta = null) {
    $sql = "SELECT * FROM seguimientos_diarios WHERE mascota_id = ?";
    $params = [$mascota_id];
    if ($desde) {
        $sql .= " AND fecha >= ?"; $params[] = $desde;
    }
    if ($hasta) {
        $sql .= " AND fecha <= ?"; $params[] = $hasta;
    }
    $sql .= " ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['datos'] = json_decode($r['datos'], true);
    }
    return $rows;
}

function generarPreconsulta($pdo, $mascota_id, $dias = 7, $cita_id = null) {
    $hasta = date('Y-m-d');
    $desde = date('Y-m-d', strtotime("-" . intval($dias) . " days"));
    $seguimientos = obtenerSeguimientos($pdo, $mascota_id, $desde, $hasta);

    // Consolidar información
    $count = count($seguimientos);
    $resumenPartes = [];
    $alertasAcumuladas = [];

    foreach (array_slice($seguimientos, 0, 20) as $s) {
        // extraer alertas relacionadas
        $stmt = $pdo->prepare("SELECT * FROM alertas WHERE seguimiento_id = ?");
        $stmt->execute([$s['id']]);
        $als = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($als as $a) {
            $alertasAcumuladas[] = $a;
        }
    }

    // Detectar cambios de comportamiento simples: contar días con anorexia/hiporexia/vómitos/letargia
    $contador = ['Hiporexia'=>0,'Anorexia'=>0,'Vomitos'=>0,'Letargia'=>0];
    foreach ($seguimientos as $s) {
        $d = $s['datos'];
        if (isset($d['apetito']) && ($d['apetito'] === 'Hiporexia')) $contador['Hiporexia']++;
        if (isset($d['apetito']) && ($d['apetito'] === 'Anorexia')) $contador['Anorexia']++;
        if (!empty($d['vomitos'])) $contador['Vomitos']++;
        if (isset($d['actividad']) && $d['actividad'] === 'Letárgico') $contador['Letargia']++;
    }

    // Crear texto automático
    $frases = [];
    if ($contador['Hiporexia'] + $contador['Anorexia'] > 0) {
        $diasF = $contador['Hiporexia'] + $contador['Anorexia'];
        $frases[] = "Paciente con $diasF días de disminución del apetito";
    }
    if ($contador['Vomitos'] > 0) {
        $frases[] = ($contador['Vomitos']>1) ? "vómitos intermitentes" : "vómitos";
    }
    if ($contador['Letargia'] > 0) {
        $frases[] = "letargia";
    }

    $texto = "";
    if (!empty($frases)) {
        $texto = "Paciente con " . implode(', ', $frases) . ".\n";
    }

    if (!empty($alertasAcumuladas)) {
        $tipos = array_unique(array_map(fn($a)=>$a['tipo'],$alertasAcumuladas));
        $texto .= "Rugal detecta patrón(s): " . implode(', ', $tipos) . ".";
    } else {
        $texto .= "No se detectaron alertas automáticas relevantes en los últimos $dias días.";
    }

    $datosGuardados = ['seguimientos_count'=> $count, 'alertas'=> $alertasAcumuladas];

    $stmt = $pdo->prepare("INSERT INTO preconsultas (mascota_id, cita_id, dias_considerados, resumen, datos) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$mascota_id, $cita_id, $dias, $texto, json_encode($datosGuardados)]);
    return ['id' => $pdo->lastInsertId(), 'resumen' => $texto, 'datos' => $datosGuardados];
}
