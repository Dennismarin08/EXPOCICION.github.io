<?php
require_once 'db.php';
require_once 'puntos-functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
if (function_exists('getUsuario')) $user = getUsuario($userId);

$citaId = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['cita_id']) ? intval($_POST['cita_id']) : 0);
if (!$citaId) {
    echo "<p>Id de cita inválido.</p>";
    exit;
}

        $stmt = $pdo->prepare("SELECT c.*, a.nombre_local AS veterinaria_nombre, a.direccion AS veterinaria_direccion, a.titular_cuenta AS veterinaria_nequi, COALESCE(u.telefono, '') AS veterinaria_telefono FROM citas c LEFT JOIN aliados a ON a.id = c.veterinaria_id LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE c.id = ? LIMIT 1");
$stmt->execute([$citaId]);
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    echo "<p>Cita no encontrada.</p>";
    exit;
}

// Determinar montos
$anticipo = isset($cita['anticipo_requerido']) ? floatval($cita['anticipo_requerido']) : 0.0;
$total = isset($cita['precio_total']) ? floatval($cita['precio_total']) : (isset($cita['precio']) ? floatval($cita['precio']) : 0.0);

// Procesar envío a WhatsApp
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payType = $_POST['pay_type'] ?? 'anticipo';
    $nequiNumber = trim($_POST['nequi_number'] ?? '');
    $waTo = trim($_POST['wa_to'] ?? '');
    $payerName = htmlspecialchars($user['nombre'] ?? 'Usuario');

    $amount = ($payType === 'total') ? $total : $anticipo;
    if ($amount <= 0) $amount = $total;

    // Normalizar números (solo dígitos)
    $waToDigits = preg_replace('/\D+/', '', $waTo);
    $nequiDigits = preg_replace('/\D+/', '', $nequiNumber);

        // Usar el número registrado de la veterinaria como destino (Nequi y WhatsApp)
        $vetNumber = $cita['veterinaria_nequi'] ?? ($cita['veterinaria_telefono'] ?? '');
        $vetNumberDigits = preg_replace('/\D+/', '', $vetNumber);
        $vetDisplay = $vetNumber ?: '[número veterinaria]';
        $msg = "Hola, soy $payerName. Envío evidencia del pago desde este número. " .
            "Pago a la veterinaria ".($cita['veterinaria_nombre'] ?? '').": ".number_format($amount,2,',','.')." COP al número " .
            ($nequiDigits ?: $vetNumberDigits ?: '[número Nequi]')." (Nequi / WhatsApp). Por favor confirmar recepción. Cita ID: $citaId. Gracias.";

    $encoded = rawurlencode($msg);
    // Priorizar número ingresado por el usuario, si no usar el número de la veterinaria (Nequi)
    $phoneParam = $waToDigits ?: $vetNumberDigits;
    if (!$phoneParam) {
        // si no hay teléfono destino, mostrar enlace para que el usuario copie el mensaje
        $copiable = htmlspecialchars($msg);
        echo "<p>No hay número de WhatsApp destino disponible. Copia y envía este mensaje manualmente:</p>";
        echo "<textarea style='width:100%;height:140px;' readonly>$copiable</textarea>";
        echo "<p><a href=\"dashboard.php\">Volver</a></p>";
        exit;
    }

    $waUrl = "https://api.whatsapp.com/send?phone={$phoneParam}&text={$encoded}";
    header('Location: ' . $waUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pagar Cita #<?php echo $citaId; ?> - RUGAL</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        :root{--bg:#0f172a;--card:#071126;--accent:#33a1ff;--muted:rgba(255,255,255,0.72)}
        body{background:var(--bg);color:#e6eef8;font-family:Inter,system-ui,Arial,sans-serif;margin:0;padding:28px}
        .wrap{max-width:920px;margin:0 auto}
        .card{background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.04);padding:22px;border-radius:14px;box-shadow:0 8px 30px rgba(2,6,23,0.6)}
        h1{margin:0 0 8px 0;font-size:20px}
        .meta{display:flex;gap:18px;flex-wrap:wrap;margin-bottom:12px}
        .meta b{color:#fff}
        .amount-box{background:rgba(255,255,255,0.02);padding:14px;border-radius:10px;margin-top:10px}
        label{display:block;margin-bottom:8px;color:var(--muted)}
        input[type=text]{width:100%;padding:10px;border-radius:10px;background:transparent;border:1px solid rgba(255,255,255,0.04);color:inherit}
        .row{display:flex;gap:12px}
        .col{flex:1}
        .actions{display:flex;gap:12px;margin-top:16px}
        .btn{padding:12px 16px;border-radius:10px;border:none;cursor:pointer;font-weight:700}
        .btn-primary{background:linear-gradient(90deg,var(--accent),#1d91f0);color:#fff}
        .btn-ghost{background:transparent;border:1px solid rgba(255,255,255,0.06);color:var(--muted)}
        .small{font-size:13px;color:rgba(255,255,255,0.7)}
        .controls{display:flex;gap:10px;align-items:center}
        .message-preview{white-space:pre-wrap;background:rgba(0,0,0,0.25);padding:12px;border-radius:8px;margin-top:12px}
        @media (max-width:680px){.row{flex-direction:column}}
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="wrap">
        <div class="card">
            <h1>Pagar Cita <span style="opacity:0.9;">#<?php echo $citaId; ?></span></h1>
            <div class="meta">
                <div><b>Veterinaria:</b> <?php echo htmlspecialchars($cita['veterinaria_nombre'] ?? 'N/D'); ?></div>
                <div><b>Fecha:</b> <?php echo date('d M Y H:i', strtotime($cita['fecha_hora'])); ?></div>
                <div><b>Servicio:</b> <?php echo htmlspecialchars($cita['tipo_cita'] ?? 'Consulta'); ?></div>
            </div>

            <?php $vet_nequi = $cita['veterinaria_nequi'] ?? ''; $vet_phone = $cita['veterinaria_telefono'] ?? ''; ?>
            <div style="margin:12px 0; padding:12px; background:rgba(255,255,255,0.02); border-radius:10px;">
                <strong>Pagar a:</strong> <?php echo htmlspecialchars($cita['veterinaria_nombre'] ?? 'Veterinaria'); ?>
                <?php if ($vet_nequi): ?>
                    &nbsp;·&nbsp;<strong>Nequi:</strong> <?php echo htmlspecialchars($vet_nequi); ?>
                <?php endif; ?>
                <?php if ($vet_phone): ?>
                    &nbsp;·&nbsp;<strong>WhatsApp:</strong> <?php echo htmlspecialchars($vet_phone); ?>
                <?php endif; ?>
            </div>

            <div class="amount-box">
                <div class="row">
                    <div class="col">
                        <label>Selecciona cantidad a pagar</label>
                        <div class="controls">
                            <label style="margin-right:10px"><input type="radio" name="pay_type" value="anticipo" checked> Anticipo</label>
                            <label><input type="radio" name="pay_type" value="total"> Total</label>
                        </div>
                    </div>
                    <div style="min-width:220px">
                        <label>Importe seleccionado</label>
                        <div id="importe" style="font-weight:800;font-size:18px;color:#fff"><?php echo number_format($anticipo,2,',','.'); ?> COP</div>
                    </div>
                </div>
                <div style="margin-top:12px" class="small">Anticipo: <?php echo number_format($anticipo,2,',','.'); ?> COP · Total aproximado: <?php echo number_format($total,2,',','.'); ?> COP</div>
            </div>

            <form id="payForm" onsubmit="return false;">
                <div style="margin-top:16px">
                    <label>Numero de Nequi (para indicar en el mensaje)</label>
                    <input type="text" id="nequi" name="nequi_number" value="<?php echo htmlspecialchars($vet_nequi); ?>" placeholder="Ej: 316XXXXXXXX">
                </div>

                <div style="margin-top:12px">
                    <label>Numero WhatsApp destino (opcional)</label>
                    <input type="text" id="wa_to" name="wa_to" value="<?php echo htmlspecialchars($vet_phone); ?>" placeholder="57XXXXXXXXX">
                    <div class="small">Si queda vacío se intentará usar el teléfono asociado a la veterinaria.</div>
                </div>

                <div class="actions">
                    <button id="openWa" class="btn btn-primary">Abrir WhatsApp</button>
                    <button id="copyMsg" class="btn btn-ghost" type="button">Copiar mensaje</button>
                    <a href="citas.php" class="btn btn-ghost">Volver</a>
                </div>

                <div class="message-preview" id="msgPreview"></div>
            </form>
        </div>
    </div>

    <script>
    (function(){
        const anticipo = <?php echo json_encode($anticipo); ?>;
        const total = <?php echo json_encode($total); ?>;
        const citaId = <?php echo json_encode($citaId); ?>;
            const vetName = <?php echo json_encode($cita['veterinaria_nombre'] ?? 'Veterinaria'); ?>;
        const vetNequi = <?php echo json_encode(preg_replace('/\\D+/', '', ($cita['veterinaria_nequi'] ?? $cita['veterinaria_telefono'] ?? ''))); ?>;
        const payerName = <?php echo json_encode($user['nombre'] ?? 'Usuario'); ?>;
        const payerPhone = <?php echo json_encode($user['telefono'] ?? ''); ?>;

        const importeEl = document.getElementById('importe');
        const radios = Array.from(document.querySelectorAll('input[name="pay_type"]'));
        const nequiEl = document.getElementById('nequi');
        const waToEl = document.getElementById('wa_to');
        const msgPreview = document.getElementById('msgPreview');
        const openWaBtn = document.getElementById('openWa');
        const copyBtn = document.getElementById('copyMsg');

        function fmt(n){ return n.toLocaleString('es-CO', {minimumFractionDigits:2, maximumFractionDigits:2}); }

        function getSelectedAmount(){
            const sel = radios.find(r=>r.checked).value;
            return (sel === 'total') ? total : anticipo;
        }

        function buildMessage(){
            const amt = getSelectedAmount();
            let nequi = (nequiEl.value||'').replace(/\D+/g,'');
            if (!nequi && vetNequi) nequi = vetNequi;
            const fromText = payerPhone ? `desde el número ${payerPhone}` : 'desde este número';
            const vetDisplay = vetNequi || '[número veterinaria]';
            return `Hola, soy ${payerName}. Envío evidencia del pago ${fromText}. Pago a la veterinaria ${vetName}: ${fmt(amt)} COP al número ${nequi || '[número Nequi]'} (Nequi / WhatsApp). Por favor confirmar recepción al número de la veterinaria: ${vetDisplay}. Cita ID: ${citaId}. Gracias.`;
        }

        function updateUI(){
            const amt = getSelectedAmount();
            importeEl.textContent = fmt(amt) + ' COP';
            msgPreview.textContent = buildMessage();
        }

        radios.forEach(r=>r.addEventListener('change', updateUI));
        nequiEl.addEventListener('input', updateUI);
        waToEl.addEventListener('input', updateUI);

        openWaBtn.addEventListener('click', function(){
            const msg = buildMessage();
            let waPhone = waToEl.value.replace(/\D+/g,'') || vetPhone || '';
            if (!waPhone){
                alert('No hay número destino configurado. El mensaje será copiado para enviarlo manualmente.');
                navigator.clipboard.writeText(msg).then(()=>alert('Mensaje copiado al portapapeles'));
                return;
            }
            const url = 'https://api.whatsapp.com/send?phone=' + waPhone + '&text=' + encodeURIComponent(msg);
            window.open(url, '_blank');
        });

        copyBtn.addEventListener('click', function(){
            const msg = buildMessage();
            navigator.clipboard.writeText(msg).then(()=>{
                copyBtn.textContent = 'Copiado';
                setTimeout(()=>copyBtn.textContent = 'Copiar mensaje', 2000);
            });
        });

        // Init
        updateUI();
    })();
    </script>
</body>
</html>
