<?php
require_once __DIR__ . '/../includes/razas_data.php';
header('Content-Type: application/json');
$razas = obtenerListaRazas();
echo json_encode(array_values($razas));
