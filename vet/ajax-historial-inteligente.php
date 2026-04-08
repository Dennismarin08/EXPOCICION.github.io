<?php
/**
 * RUGAL — Historial Clínico Interno (IA Local Entrenable)
 * Ruta: /vet/ajax-historial-inteligente.php
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/check-auth.php';

checkRole('veterinaria');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['mascota_id'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
    exit;
}

$mascotaId     = intval($_POST['mascota_id']);
$userId        = $_SESSION['user_id'];

// Mapear usuario → aliado veterinaria
$stmtAli = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND tipo = 'veterinaria'");
$stmtAli->execute([$userId]);
$aliRow        = $stmtAli->fetch(PDO::FETCH_ASSOC);
$veterinariaId = $aliRow['id'] ?? $userId;

try {
    // ── 1. Autorización ──────────────────────────────────────────────────────
    $stmtAuth = $pdo->prepare("SELECT 1 FROM citas WHERE mascota_id = ? AND veterinaria_id = ? LIMIT 1");
    $stmtAuth->execute([$mascotaId, $veterinariaId]);
    if (!$stmtAuth->fetch()) {
        echo json_encode(['success' => false, 'message' => 'No tienes autorización para ver el historial de este paciente.']);
        exit;
    }

    // ── 2. Datos de la mascota ────────────────────────────────────────────────
    $stmtM = $pdo->prepare("SELECT m.*, u.nombre as dueno_nombre, u.telefono as dueno_tel
        FROM mascotas m
        LEFT JOIN usuarios u ON m.user_id = u.id
        WHERE m.id = ?");
    $stmtM->execute([$mascotaId]);
    $mascota = $stmtM->fetch(PDO::FETCH_ASSOC);
    if (!$mascota) {
        echo json_encode(['success' => false, 'message' => 'Mascota no encontrada.']);
        exit;
    }

    // ── 3. Historial de vacunas ───────────────────────────────────────────────
    $vacunas = [];
    try {
        // Corregido: En RUGAL las vacunas están en mascotas_salud
        $sv = $pdo->prepare("
            SELECT nombre_evento as nombre_vacuna, fecha_realizado as fecha_aplicacion, proxima_fecha as proxima_dosis 
            FROM mascotas_salud WHERE mascota_id = ? AND tipo = 'vacuna' ORDER BY fecha_realizado DESC LIMIT 10
        ");
        $sv->execute([$mascotaId]);
        $vacunas = $sv->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $vacunas = []; }

    // ── 4. Plan de salud mensual (síntomas crónicos) ──────────────────────────
    $sintomasPlan = [];
    $planTextoRaw = '';
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'planes_salud_mensual'");
        if ($check && $check->fetch()) {
            $sp = $pdo->prepare("SELECT datos_json as datos FROM planes_salud_mensual WHERE mascota_id = ? ORDER BY created_at DESC LIMIT 1");
            $sp->execute([$mascotaId]);
            $planRow = $sp->fetch(PDO::FETCH_ASSOC);
            if ($planRow && !empty($planRow['datos'])) {
                $decoded = json_decode($planRow['datos'], true);
                if (isset($decoded['sintomas']) && is_array($decoded['sintomas'])) {
                    foreach ($decoded['sintomas'] as $clave => $det) {
                        $nombre = ucfirst(str_replace('_', ' ', $clave));
                        $sev    = $det['severidad'] ?? 'leve';
                        $sintomasPlan[] = "$nombre (severidad: $sev)";
                    }
                }
                // Texto libre del plan para el prompt
                if (isset($decoded['observaciones'])) $planTextoRaw = $decoded['observaciones'];
            }
        }
    } catch (Exception $e) { $sintomasPlan = []; }

    // ── 5. Bitácora diaria — últimos 14 días ─────────────────────────────────
    $seguimientos = [];
    try {
        $sd = $pdo->prepare("
            SELECT fecha, datos, observaciones
            FROM seguimientos_diarios
            WHERE mascota_id = ? AND fecha >= DATE_SUB(CURRENT_DATE(), INTERVAL 14 DAY)
            ORDER BY fecha DESC
        ");
        $sd->execute([$mascotaId]);
        $seguimientos = $sd->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $seguimientos = []; }

    // Procesar seguimientos para el prompt
    $diarioTexto = [];
    foreach ($seguimientos as $dia) {
        $fecha  = $dia['fecha'];
        $datos  = json_decode($dia['datos'] ?? '{}', true) ?: [];
        $lineas = ["Fecha: $fecha"];
        foreach ($datos as $k => $v) {
            if (!empty($v)) $lineas[] = "  - " . ucfirst($k) . ": $v";
        }
        if (!empty($dia['observaciones'])) $lineas[] = "  - Observación propietario: " . $dia['observaciones'];
        $diarioTexto[] = implode("\n", $lineas);
    }

    // ── 6. Historial de peso ──────────────────────────────────────────────────
    $pesos = [];
    try {
        $sw = $pdo->prepare("SELECT peso, fecha FROM peso_historial WHERE mascota_id = ? ORDER BY fecha DESC LIMIT 5");
        $sw->execute([$mascotaId]);
        $pesos = $sw->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $pesos = []; }

    // ── 7. Citas recientes ────────────────────────────────────────────────────
    $citasTexto = [];
    try {
        $sc = $pdo->prepare("
            SELECT c.fecha_hora, c.estado, c.motivo, c.tipo_cita, s.nombre as servicio
            FROM citas c
            LEFT JOIN servicios_veterinaria s ON c.servicio_id = s.id
            WHERE c.mascota_id = ? AND c.veterinaria_id = ?
            ORDER BY c.fecha_hora DESC LIMIT 8
        ");
        $sc->execute([$mascotaId, $veterinariaId]);
        foreach ($sc->fetchAll(PDO::FETCH_ASSOC) as $ci) {
            $label  = $ci['servicio'] ?? $ci['tipo_cita'] ?? $ci['motivo'] ?? 'Consulta';
            $citasTexto[] = date('d/m/Y', strtotime($ci['fecha_hora'])) . " — $label ({$ci['estado']})";
        }
    } catch (Exception $e) { $citasTexto = []; }

    // ── 8. Motor de Análisis Local (Sistema de Conocimiento Propio) ───────────
    $keywords = [];
    // Extraer palabras clave de síntomas del plan
    foreach($sintomasPlan as $s) { $keywords = array_merge($keywords, explode(' ', strtolower($s))); }
    // Extraer palabras clave de la bitácora
    foreach($seguimientos as $dia) {
        $d = json_decode($dia['datos'], true);
        if(isset($d['vomitos']) && $d['vomitos']) $keywords[] = 'vomito';
        if(isset($d['diarrea']) && $d['diarrea']) $keywords[] = 'diarrea';
        if(isset($d['prurito']) && $d['prurito']) $keywords[] = 'picazon';
        if(!empty($dia['observaciones'])) $keywords = array_merge($keywords, explode(' ', strtolower($dia['observaciones'])));
    }
    $keywords = array_unique(array_filter($keywords, fn($w) => strlen($w) > 3));

    // Buscar en la base de datos de "Entrenamiento" (ia_perros)
    $analisisLocal = [];
    if(!empty($keywords)) {
        $placeholders = implode(',', array_fill(0, count($keywords), '?'));
        $types = str_repeat('s', count($keywords));
        // Motor Inteligente: Filtra por Keywords + Edad + Peso + Raza
        $mascotaEdad = intval($mascota['edad_anios'] ?? 0);
        $mascotaPeso = floatval($mascota['peso'] ?? 0);
        $mascotaRaza = strtolower($mascota['raza'] ?? '');

        $sqlLocal = "
            SELECT DISTINCT t.tema, t.respuesta, t.categoria_enfermedad
            FROM preguntas p 
            JOIN temas t ON t.id = p.tema_id 
            WHERE p.texto REGEXP ?
            AND (? BETWEEN t.edad_min AND t.edad_max)
            AND (? BETWEEN t.peso_min AND t.peso_max)
            AND (t.raza_especifica IS NULL OR t.raza_especifica = '' OR ? LIKE CONCAT('%', LOWER(t.raza_especifica), '%'))
        ";
        
        $stmtLocal = $pdo->prepare($sqlLocal);
        $regex = implode('|', $keywords);
        $stmtLocal->execute([$regex, $mascotaEdad, $mascotaPeso, $mascotaRaza]);
        
        while($row = $stmtLocal->fetch()) {
            $color = ($row['categoria_enfermedad'] == 'Urgencia') ? '🔴' : '🔸';
            $analisisLocal[] = "$color **{$row['tema']}:** {$row['respuesta']}";
        }
    }

    // Generar el Reporte Estructurado Local
    $edadStr  = ($mascota['edad_anios'] ?? 0) . ' años ' . ($mascota['edad_meses'] ?? 0) . ' meses';
    $pesoStr  = !empty($pesos) ? $pesos[0]['peso'] . ' kg' : 'No registrado';
    
    $htmlReporte = "📋 **Resumen:** Paciente {$mascota['especie']} ({$mascota['raza']}) de {$edadStr} con peso de {$pesoStr}.\n\n";
    
    if(!empty($analisisLocal)) {
        $htmlReporte .= "🩺 **Hallazgos detectados (Entrenamiento Local):**\n" . implode("\n", $analisisLocal) . "\n\n";
    } else {
        $htmlReporte .= "🩺 **Hallazgos:** No se detectaron anomalías críticas en las bitácoras recientes.\n\n";
    }

    // Lógica de Alertas de Vacunas (Procesamiento local)
    $alertasVacunas = [];
    foreach($vacunas as $v) {
        if(!empty($v['proxima_dosis']) && strtotime($v['proxima_dosis']) < time()) {
            $alertasVacunas[] = "⚠️ Vacuna vencida: {$v['nombre_vacuna']}";
        }
    }
    
    if(!empty($alertasVacunas)) {
        $htmlReporte .= "📅 **Alertas de Vacunación:**\n" . implode("\n", $alertasVacunas) . "\n\n";
    }

    $htmlReporte .= "💡 **Sugerencia:** Revisar estado de ánimo general durante la consulta física.";

    // ── 10. Construir HTML de respuesta ──────────────────────────────────────
    ob_start();
    ?>
    <!-- Cabecera paciente -->
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #e2e8f0;">
        <div style="width:50px;height:50px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#a855f7);display:flex;align-items:center;justify-content:center;font-size:22px;color:white;flex-shrink:0;">
            <?php echo $mascota['especie'] === 'gato' ? '🐱' : '🐾'; ?>
        </div>
        <div>
            <div style="font-size:18px;font-weight:800;color:#0f172a;"><?php echo htmlspecialchars($mascota['nombre']); ?></div>
            <div style="font-size:13px;color:#64748b;"><?php echo htmlspecialchars($mascota['raza']); ?> · <?php echo $edadStr; ?> · <?php echo $pesoStr; ?></div>
            <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Dueño: <?php echo htmlspecialchars($mascota['dueno_nombre']); ?></div>
        </div>
    </div>

    <!-- Respuesta Motor Local -->
    <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:16px;padding:20px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#0ea5e9,#3b82f6);display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-database" style="color:white;font-size:12px;"></i>
                </div>
            <span style="font-size:13px;font-weight:700;color:#0369a1;">Análisis de Conocimiento Local (Entrenado)</span>
            </div>
        <div style="font-size:13.5px;line-height:1.8;color:#1e293b;white-space:pre-wrap;"><?php 
            // Convertir markdown simple a negritas
            echo str_replace('**', '', $htmlReporte); 
        ?></div>
    </div>

    <!-- Vacunas -->
    <div style="padding:14px;border-radius:12px;border:1px solid #bfdbfe;background:#eff6ff;margin-bottom:14px;">
        <div style="font-size:13px;font-weight:700;color:#1e40af;margin-bottom:8px;">💉 Historial de vacunas</div>
        <?php if (!empty($vacunas)): ?>
            <div style="font-size:12px;color:#1e3a5f;line-height:1.7;">
            <?php foreach(array_slice($vacunas, 0, 5) as $v):
                $prox = !empty($v['proxima_dosis']) ? ' — Próxima: <strong>' . $v['proxima_dosis'] . '</strong>' : '';
                $vencida = !empty($v['proxima_dosis']) && strtotime($v['proxima_dosis']) < time();
            ?>
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                    <span><?php echo $vencida ? '🔴' : '✅'; ?></span>
                    <span><?php echo htmlspecialchars($v['nombre_vacuna']); ?> (<?php echo $v['fecha_aplicacion']; ?>)<?php echo $prox; ?></span>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="margin:0;font-size:13px;color:#64748b;">Sin registro de vacunas.</p>
        <?php endif; ?>
    </div>

    <!-- Disclaimer -->
    <p style="font-size:11px;color:#94a3b8;text-align:center;margin:0;font-style:italic;">
        Este análisis es orientativo y no reemplaza el juicio clínico del veterinario. No constituye diagnóstico médico.
    </p>
    <?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
?>