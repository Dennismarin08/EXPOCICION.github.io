<?php
require_once 'db.php';
// Get the tester user id
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = 'tester@rugal.com'");
$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['rol'] = 'usuario';
    $_SESSION['premium'] = 1; 
    header('Location: tareas.php');
} else {
    echo "Test user not found, run setup-verification-v2.php first.";
}
?>
