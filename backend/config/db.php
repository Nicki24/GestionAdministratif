<?php
// backend/config/db.php
// Connexion PDO centralisée à la base de données avec support dev/prod

// Déterminer l'environnement automatiquement
$isDevelopment = $_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1';

if ($isDevelopment) {
    // Configuration DÉVELOPPEMENT (WAMP local)
    $host = '127.0.0.1';
    $db   = 'gestion_bordereaux';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    
    // Activer les erreurs en développement
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
} else {
    // Configuration PRODUCTION (serveur distant)
    $host = 'localhost';
    $db   = 'gestion_bordereaux';
    $user = 'votre_utilisateur_prod';      // À MODIFIER
    $pass = 'votre_mot_de_passe_prod';     // À MODIFIER
    $charset = 'utf8mb4';
    
    // Désactiver l'affichage des erreurs en production
    error_reporting(0);
    ini_set('display_errors', 0);
}

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
    
    if ($isDevelopment) {
        // Message détaillé en développement
        echo json_encode([
            'success' => false,
            'error' => 'Connexion à la base de données échouée : ' . $e->getMessage(),
            'debug' => [
                'host' => $host,
                'database' => $db,
                'environment' => 'development'
            ]
        ]);
    } else {
        // Message générique en production
        echo json_encode([
            'success' => false,
            'error' => 'Erreur de connexion à la base de données'
        ]);
    }
    exit;
}
?>