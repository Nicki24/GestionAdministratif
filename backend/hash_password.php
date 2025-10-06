<?php
// Inclure la connexion à la base de données (facultatif ici, juste pour générer le hachage)
require_once 'db_connect.php';

// Vérifier si un mot de passe est fourni via POST (plus sécurisé que GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    // Générer un hachage avec bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    echo json_encode(['hashed_password' => $hashed_password]);
    exit;
} else {
    // Message d'erreur si aucune donnée n'est fournie
    http_response_code(400);
    echo json_encode(['error' => 'Aucun mot de passe fourni']);
    exit;
}
?>