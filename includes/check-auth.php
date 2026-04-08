<?php
// includes/check-auth.php - Verify user session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // If we are in a subdirectory (like /admin/), we need to go up one level
    $isSubdir = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/vet/') !== false || strpos($_SERVER['PHP_SELF'], '/tienda/') !== false);
    $loginPath = $isSubdir ? '../login.php' : 'login.php';
    header("Location: " . $loginPath);
    exit;
}

// Optional: You can add role checks here if needed
// $currentUserRole = $_SESSION['role'] ?? 'user';
?>
