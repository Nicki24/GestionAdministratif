<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require 'config/db.php'; // Utiliser le fichier de connexion correct

// Activer CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (préliminaires CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Log pour débogage
file_put_contents('debug.log', "[$method] Requête reçue à " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

try {
    // Vérifier que la méthode est GET
    if ($method !== 'GET') {
        throw new Exception('Méthode non autorisée', 405);
    }

    // Récupérer et valider les paramètres
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';

    file_put_contents('debug.log', "=== NOUVELLE RECHERCHE ===\n", FILE_APPEND);
    file_put_contents('debug.log', "Date: $date, Type: $type\n", FILE_APPEND);

    if (empty($date) || empty($type)) {
        throw new Exception('Les paramètres date et type sont requis', 400);
    }

    // Valider le format de la date (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Format de date invalide, utilisez YYYY-MM-DD', 400);
    }

    // Valider le type
    $validTypes = ['bordereaux', 'banques'];
    if (!in_array(strtolower($type), $validTypes)) {
        throw new Exception('Type invalide : choisissez parmi bordereaux ou banques', 400);
    }

    // Déterminer la table et les colonnes
    $table = (strtolower($type) === 'bordereaux') ? 'bordereau' : 'banque';
    $columns = ($table === 'bordereau') 
        ? 'id_bordereau, matricule, reference, objet, statut, date_creation' 
        : 'id_banque, nom, section, date_creation';

    // Préparer et exécuter la requête
    $sql = "SELECT $columns FROM $table WHERE DATE(date_creation) = :date";
    file_put_contents('debug.log', "SQL: $sql\n", FILE_APPEND);

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':date', $date);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($results);

    file_put_contents('debug.log', "Résultats: $count enregistrement(s)\n", FILE_APPEND);
    file_put_contents('debug.log', "Détails: " . json_encode($results) . "\n\n", FILE_APPEND);

    echo json_encode($results);

} catch (PDOException $e) {
    $error = 'Erreur de base de données : ' . $e->getMessage();
    file_put_contents('debug.log', "ERREUR PDO: $error\n\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => $error]);
} catch (Exception $e) {
    $error = $e->getMessage();
    $code = $e->getCode() ?: 500;
    file_put_contents('debug.log', "ERREUR: $error (Code: $code)\n\n", FILE_APPEND);
    http_response_code($code);
    echo json_encode(['error' => $error]);
}
?>