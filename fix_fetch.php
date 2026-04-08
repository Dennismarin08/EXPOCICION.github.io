<?php
$file = __DIR__ . '/vet/vet-dashboard.php';
$content = file_get_contents($file);

$content = str_replace(
    "fetch('ajax-validar-canje.php'", 
    "fetch('../ajax-validar-canje.php')", 
    $content
);

$content = str_replace(
    "fetch('ajax-historial-inteligente.php'", 
    "fetch('../ajax-historial-inteligente.php')", 
    $content
);

file_put_contents($file, $content);
echo "Done! Fixed fetch paths.\n";
