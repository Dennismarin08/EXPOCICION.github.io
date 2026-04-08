<?php
// Script temporal para setup del sistema "Recordarme"
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Setup - Remember Me</title>
<style>
  body { font-family: monospace; padding: 30px; background: #0f172a; color: #e2e8f0; }
  .ok  { color: #34d399; } .err { color: #f87171; } .info { color: #60a5fa; }
  .box { background: #1e293b; border-radius: 12px; padding: 20px; margin: 15px 0; border: 1px solid #334155; }
  h2   { color: #a78bfa; }
  pre  { margin: 5px 0; }
</style>
</head>
<body>
<h1>🔐 Setup: Sistema "Recordarme" — RUGAL</h1>

<?php
// 1. Verificar/crear columna remember_token
echo '<div class="box"><h2>1. Base de Datos</h2>';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'remember_token'");
    $col = $stmt->fetch();
    if ($col) {
        echo '<pre class="ok">✅ Columna "remember_token" existe. Tipo: '.$col['Type'].'</pre>';
    } else {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN remember_token VARCHAR(64) NULL DEFAULT NULL AFTER password");
        echo '<pre class="ok">✅ Columna "remember_token" CREADA exitosamente.</pre>';
    }
    // Mostrar cuántos usuarios tienen el token
    $count = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE remember_token IS NOT NULL")->fetchColumn();
    echo '<pre class="info">ℹ️ Usuarios con remember_token activo: '.$count.'</pre>';
} catch (Exception $e) {
    echo '<pre class="err">❌ Error BD: '.$e->getMessage().'</pre>';
}
echo '</div>';

// 2. Estado de la sesión actual
echo '<div class="box"><h2>2. Sesión PHP</h2>';
echo '<pre class="info">Session ID: '.session_id().'</pre>';
echo '<pre class="info">Session lifetime: '.ini_get('session.gc_maxlifetime').' seg ('.round(ini_get('session.gc_maxlifetime')/86400).' días)</pre>';
echo '<pre class="info">Cookie lifetime: '.ini_get('session.cookie_lifetime').' seg</pre>';
$params = session_get_cookie_params();
echo '<pre class="info">Cookie params: path='.$params['path'].' | httponly='.($params['httponly']?'true':'false').'</pre>';
if (isset($_SESSION['user_id'])) {
    echo '<pre class="ok">✅ Sesión activa: user_id='.$_SESSION['user_id'].' | rol='.($_SESSION['user_rol']??'N/A').'</pre>';
} else {
    echo '<pre class="err">❌ No hay sesión activa</pre>';
}
echo '</div>';

// 3. Estado de los cookies
echo '<div class="box"><h2>3. Cookies</h2>';
if (isset($_COOKIE['remember_token'])) {
    echo '<pre class="ok">✅ Cookie "remember_token" presente: '.substr($_COOKIE['remember_token'],0,16).'...</pre>';
    // Verificar contra BD
    try {
        $stmt = $pdo->prepare("SELECT id, email FROM usuarios WHERE remember_token = ? LIMIT 1");
        $stmt->execute([$_COOKIE['remember_token']]);
        $u = $stmt->fetch();
        if ($u) {
            echo '<pre class="ok">✅ Token válido → usuario: '.$u['email'].' (id='.$u['id'].')</pre>';
        } else {
            echo '<pre class="err">❌ Token en cookie NO coincide con ningún usuario en BD</pre>';
        }
    } catch(Exception $e) {
        echo '<pre class="err">Error: '.$e->getMessage().'</pre>';
    }
} else {
    echo '<pre class="err">❌ Cookie "remember_token" NO existe</pre>';
    echo '<pre class="info">ℹ️ Inicia sesión con "Recordarme" marcado para verlo aquí.</pre>';
}
if (isset($_COOKIE['PHPSESSID'])) {
    echo '<pre class="ok">✅ Cookie PHPSESSID presente (sesión viva)</pre>';
} else {
    echo '<pre class="err">⚠️  Cookie PHPSESSID no presente</pre>';
}
echo '</div>';

echo '<div class="box"><h2>4. Instrucciones</h2>';
echo '<pre class="info">1. Si todo está ✅: El sistema está listo. Elimina este archivo.</pre>';
echo '<pre class="info">2. Inicia sesión en login.php con "Recordarme" marcado.</pre>';
echo '<pre class="info">3. Recarga esta página para confirmar que el cookie aparece.</pre>';
echo '<pre class="info">4. Cierra el navegador y abre login.php — deberías ir directo al dashboard.</pre>';
echo '</div>';
?>
</body>
</html>

