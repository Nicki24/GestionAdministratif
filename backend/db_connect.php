<?php
$host = 'localhost';
$dbname = 'gestion_bordereaux';
$username = 'root';
$password = ''; // Remplace par ton mot de passe si nécessaire
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>