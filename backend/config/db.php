<?php
// backend/config/db.php
// Connexion PDO centralisée à la base de données

$host = '127.0.0.1';
$db   = 'gestion_bordereaux';   // ta base déjà créée
$user = 'root';                 // identifiant MySQL par défaut (Wamp)
$pass = '';                     // mot de passe MySQL (souvent vide sous Wamp)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Connexion échouée : ' . $e->getMessage()]);
    exit;
}
