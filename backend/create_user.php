<?php
// Activer l'affichage des erreurs (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

header('Content-Type: application/json');

// Vérifier que la requête est bien POST pour la sécurité
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

class UserManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createOrUpdateUser($email, $password, $userType = 'user') { // 'user' par défaut au lieu de 'client'
        // Validation des entrées
        $email = trim($email);
        $password = trim($password);
        
        if (empty($email) || empty($password)) {
            throw new Exception("Email et mot de passe sont requis");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format d'email invalide: $email");
        }
        
        if (strlen($password) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères");
        }
        
        // Générer le hash bcrypt
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        if ($hashed_password === false) {
            throw new Exception("Erreur lors du hachage du mot de passe");
        }
        
        // Vérifier si l'utilisateur existe déjà
        $stmt = $this->pdo->prepare("SELECT id, type_utilisateur FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Mettre à jour le mot de passe et le type
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET mot_de_passe = ?, type_utilisateur = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $userType, $email]);
            $action = "mis à jour";
        } else {
            // Créer un nouvel utilisateur
            $stmt = $this->pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe, type_utilisateur) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hashed_password, $userType]);
            $action = "créé";
        }
        
        return [
            'action' => $action,
            'email' => $email,
            'user_type' => $userType
        ];
    }
}

try {
    // MODIFICATION IMPORTANTE : Définir les bons types d'utilisateurs
    $usersToProcess = [
        [
            'email' => 'norlandehubery@gmail.com',
            'password' => 'CoachPro123',
            'type' => 'admin' // Norlande = Admin avec tous les droits
        ],
        [
            'email' => 'ornellaclaudia0@gmail.com',
            'password' => 'ClaudiaSecure2025!',
            'type' => 'user' // Ornella = User avec droits limités
        ]
    ];
    
    $userManager = new UserManager($pdo);
    $results = [];
    
    foreach ($usersToProcess as $userData) {
        $result = $userManager->createOrUpdateUser(
            $userData['email'], 
            $userData['password'],
            $userData['type'] ?? 'user' // Valeur par défaut = 'user'
        );
        
        $results[] = [
            'success' => true,
            'message' => "Utilisateur {$result['action']} avec succès!",
            'email' => $result['email'],
            'user_type' => $result['user_type'],
            'action' => $result['action']
        ];
        
        error_log("Utilisateur traité: {$result['email']} - Type: {$result['user_type']}");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tous les utilisateurs ont été traités avec succès!',
        'users' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Erreur create_user: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>