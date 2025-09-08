<?php
require_once __DIR__ . '/config/db.php';

// Test simple : récupérer tous les bordereaux
$stmt = $pdo->query("SELECT * FROM bordereau");
$data = $stmt->fetchAll();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
