<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require 'db_connect.php';

// Activer CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    switch ($method) {
        case 'GET':
            if (isset($_GET['stats']) && isset($_GET['period']) && is_numeric($_GET['period'])) {
                $period = (int)$_GET['period'];
                $stmt = $pdo->prepare("SELECT DATE(`date_creation`) AS day, COUNT(*) AS count FROM `banque` WHERE `date_creation` >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(`date_creation`)");
                $stmt->execute([$period]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                file_put_contents('debug.log', "[GET] Statistiques retournées : " . json_encode($result) . "\n", FILE_APPEND);
                echo json_encode($result);
            } else {
                $stmt = $pdo->query("SELECT * FROM `banque` ORDER BY id_banque ASC");
                $banques = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($banques);
            }
            break;

        case 'POST':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                file_put_contents('debug.log', "[POST] Données reçues : " . json_encode($data) . "\n", FILE_APPEND);

                // Vérifier les champs obligatoires
                if (!isset($data['id_banque'], $data['nom'], $data['section']) ||
                    !$data['id_banque'] || empty($data['nom']) || empty($data['section'])) {
                    http_response_code(400);
                    $error = ['error' => 'Les champs id_banque, nom et section sont requis'];
                    file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                $id_banque = $data['id_banque'];
                $nom = trim($data['nom']);
                $section = trim($data['section']);

                // Valider la longueur du nom
                if (strlen($nom) > 100) {
                    http_response_code(400);
                    $error = ['error' => 'Le nom dépasse la limite de 100 caractères'];
                    file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                // Valider la section
                if (!in_array($section, ['PVO005', 'PSC005'])) {
                    http_response_code(400);
                    $error = ['error' => 'Section invalide : choisissez parmi PVO005 ou PSC005'];
                    file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                // Vérifier si id_banque existe déjà
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `banque` WHERE id_banque = ?");
                $stmt->execute([$id_banque]);
                if ($stmt->fetchColumn() > 0) {
                    http_response_code(400);
                    $error = ['error' => "L'ID banque ($id_banque) existe déjà"];
                    file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                $stmt = $pdo->prepare("INSERT INTO `banque` (id_banque, nom, section) VALUES (?, ?, ?)");
                $stmt->execute([$id_banque, $nom, $section]);
                http_response_code(201);
                $response = ['message' => 'Banque ajoutée avec succès', 'id_banque' => $id_banque];
                file_put_contents('debug.log', "[POST] Succès : " . json_encode($response) . "\n", FILE_APPEND);
                echo json_encode($response);
            } catch (PDOException $e) {
                http_response_code(500);
                $error = ['error' => 'Erreur lors de l\'ajout: ' . $e->getMessage()];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
            }
            break;

        case 'PUT':
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                file_put_contents('debug.log', "[PUT] Données reçues : " . json_encode($data) . "\n", FILE_APPEND);

                if (!isset($data['id_banque'], $data['nom'], $data['section']) ||
                    !$data['id_banque'] || empty($data['nom']) || empty($data['section'])) {
                    http_response_code(400);
                    $error = ['error' => 'Les champs id_banque, nom et section sont requis'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                $id_banque = $data['id_banque'];
                $nom = trim($data['nom']);
                $section = trim($data['section']);

                // Valider la longueur du nom
                if (strlen($nom) > 100) {
                    http_response_code(400);
                    $error = ['error' => 'Le nom dépasse la limite de 100 caractères'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                // Valider la section
                if (!in_array($section, ['PVO005', 'PSC005'])) {
                    http_response_code(400);
                    $error = ['error' => 'Section invalide : choisissez parmi PVO005 ou PSC005'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                $stmt = $pdo->prepare("UPDATE `banque` SET nom = ?, section = ? WHERE id_banque = ?");
                $stmt->execute([$nom, $section, $id_banque]);
                if ($stmt->rowCount() > 0) {
                    $response = ['message' => 'Banque mise à jour avec succès'];
                    file_put_contents('debug.log', "[PUT] Succès : " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } else {
                    http_response_code(404);
                    $error = ['error' => 'Banque non trouvée'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                $error = ['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
                file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
            }
            break;

        case 'DELETE':
            try {
                $id_banque = $_GET['id_banque'] ?? null;
                if (!$id_banque) {
                    http_response_code(400);
                    $error = ['error' => 'ID banque requis'];
                    file_put_contents('debug.log', "[DELETE] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    exit;
                }

                $stmt = $pdo->prepare("DELETE FROM `banque` WHERE id_banque = ?");
                $stmt->execute([$id_banque]);
                if ($stmt->rowCount() > 0) {
                    $response = ['message' => 'Banque supprimée avec succès'];
                    file_put_contents('debug.log', "[DELETE] Succès : " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } else {
                    http_response_code(404);
                    $error = ['error' => 'Banque non trouvée'];
                    file_put_contents('debug.log', "[DELETE] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                $error = ['error' => 'Erreur lors de la suppression: ' . $e->getMessage()];
                file_put_contents('debug.log', "[DELETE] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
            }
            break;

        default:
            http_response_code(405);
            $error = ['error' => 'Méthode non autorisée'];
            file_put_contents('debug.log', "[DEFAULT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
            echo json_encode($error);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $error = ['error' => 'Erreur serveur : ' . $e->getMessage()];
    file_put_contents('debug.log', "[EXCEPTION] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
    echo json_encode($error);
}
?>