<?php
require_once 'db.php';
require_once 'includes/seguimiento_functions.php';
require_once 'premium-functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html'); exit;
}

$user_id = $_SESSION['user_id'];

// Obtener mascota (simple: primer mascota del usuario o id en query)
$mascota_id = $_GET['mascota_id'] ?? null;
if (!$mascota_id) {
    $stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ? ORDER BY id LIMIT 1");
    $stmt->execute([$user_id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m) { echo "<p>No tienes mascotas. <a href='agregar-mascota.php'>Agregar mascota</a></p>"; exit; }
    $mascota_id = $m['id'];
}

$isPremium = esPremium($user_id);
$ultimos = obtenerSeguimientos($pdo, $mascota_id, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Seguimiento Diario</title>
    <link rel="stylesheet" href="dashboard.css">
    <style> .field{margin-bottom:8px;} label{display:block;font-weight:700;} </style>
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content" style="margin-left:260px; padding:30px;">
    <h1>Seguimiento Diario</h1>
    <p>Registra el estado de tu mascota. Plan Free: 1 registro diario. Premium: registros ilimitados.</p>

    <form id="seguimiento-form">
        <input type="hidden" name="mascota_id" value="<?php echo htmlspecialchars($mascota_id); ?>">
        <div class="field">
            <label>Estado General - Actividad</label>
            <select name="datos[actividad]">
                <option>Normal</option>
                <option>Letárgico</option>
                <option>Hiperactivo</option>
            </select>
        </div>
        <div class="field">
            <label>Apetito</label>
            <select name="datos[apetito]"><option>Normal</option><option>Hiporexia</option><option>Anorexia</option></select>
        </div>
        <div class="field">
            <label>Consumo de agua</label>
            <select name="datos[consumo_agua]"><option>Normal</option><option>Polidipsia</option><option>Disminuido</option></select>
        </div>
        <div class="field">
            <label>Ánimo</label>
            <select name="datos[animo]"><option>Normal</option><option>Apático</option><option>Irritable</option></select>
        </div>

        <h3>Sistema Digestivo</h3>
        <div class="field"><label><input type="checkbox" name="datos[vomitos]" value="1"> Vómitos</label></div>
        <div class="field"><label><input type="checkbox" name="datos[diarrea]" value="1"> Diarrea</label></div>
        <div class="field">
            <label>Color de heces</label>
            <select name="datos[color_heces]"><option>Normal</option><option>Melena</option><option>Hematoquecia</option></select>
        </div>

        <h3>Sistema Urinario</h3>
        <div class="field"><label><input type="checkbox" name="datos[orina_normal]" value="1"> Orina normal</label></div>
        <div class="field"><label><input type="checkbox" name="datos[poliuria]" value="1"> Poliuria</label></div>
        <div class="field"><label><input type="checkbox" name="datos[disuria]" value="1"> Disuria</label></div>
        <div class="field"><label><input type="checkbox" name="datos[hematuria]" value="1"> Hematuria</label></div>

        <h3>Piel</h3>
        <div class="field"><label><input type="checkbox" name="datos[prurito]" value="1"> Prurito</label></div>
        <div class="field"><label><input type="checkbox" name="datos[alopecia]" value="1"> Alopecia</label></div>
        <div class="field"><label><input type="checkbox" name="datos[lesiones_cutaneas]" value="1"> Lesiones cutáneas</label></div>

        <h3>Dolor / Movilidad</h3>
        <div class="field"><label><input type="checkbox" name="datos[claudicacion]" value="1"> Claudicación</label></div>
        <div class="field"><label><input type="checkbox" name="datos[dolor_al_tocar]" value="1"> Dolor al tocar</label></div>
        <div class="field"><label><input type="checkbox" name="datos[rigidez]" value="1"> Rigidez</label></div>

        <div class="field">
            <label>Observaciones libres</label>
            <textarea name="observaciones" rows="4" style="width:100%"></textarea>
        </div>

        <button type="submit">Guardar Seguimiento</button>
    </form>

    <h2>Últimos registros (30d)</h2>
    <div id="lista-seguimientos">
        <?php foreach ($ultimos as $u): ?>
            <div style="padding:10px;border:1px solid rgba(0,0,0,0.05);margin-bottom:8px;border-radius:8px;">
                <div style="font-weight:700"><?php echo htmlspecialchars($u['fecha']); ?> - <?php echo htmlspecialchars($u['observaciones']?:''); ?></div>
                <div style="font-size:13px;color:#666;"><?php echo htmlspecialchars(json_encode($u['datos'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.getElementById('seguimiento-form').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    const obj = {mascota_id: fd.get('mascota_id'), observaciones: fd.get('observaciones'), datos: {}};
    for (const pair of fd.entries()){
        const k = pair[0];
        const v = pair[1];
        if (k.startsWith('datos[')){
            const key = k.substring(6, k.length-1);
            // checkboxes send value '1' only when checked; set boolean or value
            if (v === '1') obj.datos[key] = true; else obj.datos[key] = v;
        }
    }
    const res = await fetch('ajax-save-seguimiento.php', {
        method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(obj)
    });
    const j = await res.json();
    if (j.success){ alert('Guardado'); location.reload(); }
    else alert(j.error || 'Error');
});
</script>
</body>
</html>
