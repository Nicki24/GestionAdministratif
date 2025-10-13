<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORRECTION : Inclure le bon fichier de connexion
require_once 'config/db.php';

// Headers CORS pour permettre les requêtes depuis Vue.js
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données envoyées (email et mot de passe)
    $data = json_decode(file_get_contents('php://input'), true);
    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';

    // Valider les entrées
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email et mot de passe sont requis']);
        exit;
    }

    try {
        // Requête pour récupérer l'utilisateur
        $stmt = $pdo->prepare("SELECT id, email, mot_de_passe FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Authentification réussie
            $response = [
                'success' => true, 
                'message' => 'Connexion réussie', 
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email']
                ]
            ];
            echo json_encode($response);
        } else {
            // Échec de l'authentification
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Email ou mot de passe incorrect']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    // Méthode non autorisée
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
?>