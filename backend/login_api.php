<?php
// Inclure la connexion à la base de données
require_once 'db_connect.php';

// Vérifier si la requête est de type POST
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données envoyées (email et mot de passe)
    $data = json_decode(file_get_contents('php://input'), true);
    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';

    // Valider les entrées
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email et mot de passe sont requis']);
        exit;
    }

    try {
        // Requête pour récupérer l'utilisateur
        $stmt = $pdo->prepare("SELECT id, email, mot_de_passe FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Authentification réussie (ici, on pourrait générer un token JWT)
            // Pour simplifier, on retourne un message de succès avec l'ID utilisateur
            session_start();
            $_SESSION['user_id'] = $user['id'];
            echo json_encode(['success' => true, 'message' => 'Connexion réussie', 'user_id' => $user['id']]);
        } else {
            // Échec de l'authentification
            http_response_code(401);
            echo json_encode(['error' => 'Email ou mot de passe incorrect']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
    }
} else {
    // Méthode non autorisée
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}
?>