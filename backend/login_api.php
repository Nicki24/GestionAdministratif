<?php
// Activer l'affichage des erreurs (pour dev uniquement)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure le fichier de connexion
require_once 'config/db.php';

// Headers CORS pour permettre les requêtes depuis Vue.js
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Log de la méthode reçue pour debug
error_log("Méthode reçue: " . $_SERVER['REQUEST_METHOD']);

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    error_log("Erreur 405: Méthode non autorisée - " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

try {
    // Récupérer les données JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Vérifier si le JSON est valide
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON invalide: ' . json_last_error_msg());
    }

    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';

    // DEBUG: Log des données reçues
    error_log("=== DEBUG LOGIN_API ===");
    error_log("Email reçu: " . $email);
    error_log("Password reçu (longueur): " . strlen($password));
    error_log("========================");

    // Valider les entrées
    if (empty($email) || empty($password)) {
        error_log("DEBUG LOGIN - Erreur: Champs manquants");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Email et mot de passe sont requis',
            'debug' => [
                'email_received' => !empty($email),
                'password_received' => !empty($password)
            ]
        ]);
        exit();
    }

    // Vérifier le format de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Format d\'email invalide']);
        exit();
    }

    // MODIFICATION IMPORTANTE : Récupérer aussi le type_utilisateur
    $stmt = $pdo->prepare("SELECT id, email, mot_de_passe, type_utilisateur FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // DEBUG détaillé
    error_log("=== DEBUG USER QUERY ===");
    error_log("User trouvé: " . ($user ? "OUI" : "NON"));
    if ($user) {
        error_log("ID: " . $user['id']);
        error_log("Email: " . $user['email']);
        error_log("Type utilisateur: " . $user['type_utilisateur']); // AJOUT
        error_log("Hash stocké (début): " . substr($user['mot_de_passe'], 0, 20));
        error_log("Longueur hash: " . strlen($user['mot_de_passe']));
        $passwordVerify = password_verify($password, $user['mot_de_passe']);
        error_log("Password verify: " . ($passwordVerify ? "OK" : "ECHEC"));
        error_log("Password fourni (longueur): " . strlen($password));
    } else {
        error_log("Aucun utilisateur trouvé pour cet email");
    }
    error_log("========================");

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        error_log("DEBUG LOGIN - Succès: password_verify OK");
        $response = [
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'type_utilisateur' => $user['type_utilisateur'] // AJOUT IMPORTANT
            ]
        ];
        echo json_encode($response);
    } else {
        error_log("DEBUG LOGIN - ÉCHEC: User absent OU password_verify FALSE");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Email ou mot de passe incorrect',
            'debug' => [
                'user_found' => !empty($user),
                'email' => $email
            ]
        ]);
    }

} catch (Exception $e) {
    error_log("DEBUG LOGIN - Erreur générale: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur : ' . $e->getMessage(),
        'debug' => [
            'error_type' => get_class($e),
            'error_line' => $e->getLine()
        ]
    ]);
}
?>