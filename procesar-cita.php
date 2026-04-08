<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION['user_id'];
$vetId = $_POST['veterinaria_id'] ?? 0;
$mascotaId = $_POST['mascota_id'] ?? 0;
$servicioId = $_POST['servicio_id'] ?? null;
$motivo = $_POST['motivo'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';

if (!$vetId || !$mascotaId || !$fecha || !$hora) {
    die("Faltan datos requeridos");
}

$fechaHora = $fecha . ' ' . $hora . ':00';

// Obtener info del veterinario para el precio base/anticipo
$stmt = $pdo->prepare("SELECT precio_consulta, anticipo_requerido FROM aliados WHERE id = ?");
$stmt->execute([$vetId]);
$vetInfo = $stmt->fetch();

$precioEstimado = $vetInfo['precio_consulta'] ?? 0;
// Si seleccionó servicio, usar precio del servicio
if ($servicioId) {
    $stmt = $pdo->prepare("SELECT precio FROM servicios_veterinaria WHERE id = ?");
    $stmt->execute([$servicioId]);
    $srv = $stmt->fetch();
    if ($srv) $precioEstimado = $srv['precio'];
}

// Calcular anticipo
$anticipoMonto = ($precioEstimado * ($vetInfo['anticipo_requerido'] ?? 50)) / 100;

// Insertar Cita
$stmt = $pdo->prepare("
    INSERT INTO citas (user_id, veterinaria_id, mascota_id, servicio_id, fecha_hora, motivo, estado, precio_total, anticipo_requerido, anticipo_pagado)
    VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, 0)
");

$stmt->execute([
    $userId, 
    $vetId, 
    $mascotaId, 
    $servicioId ?: null, 
    $fechaHora, 
    $motivo, 
    $precioEstimado, 
    $anticipoMonto
]);

header("Location: citas.php?agendada=1");
exit;
