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

try {
    switch ($method) {
        // GET : Lister tous les bordereaux ou un bordereau spécifique
        case 'GET':
            if (isset($_GET['id_bordereau']) && is_numeric($_GET['id_bordereau'])) {
                $id_bordereau = $_GET['id_bordereau'];
                $stmt = $pdo->prepare("SELECT * FROM bordereau WHERE id_bordereau = ?");
                $stmt->execute([$id_bordereau]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($result);
            } else {
                $stmt = $pdo->query("SELECT * FROM bordereau");
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($result);
            }
            break;

        // POST : Créer un bordereau (avec un ou plusieurs matricules)
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id_bordereau'], $data['reference'], $data['matricules'], $data['objet'], $data['statut'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Données manquantes']);
                break;
            }

            $id_bordereau = $data['id_bordereau'];
            $reference = $data['reference'];
            $matricules = $data['matricules'];
            $objet = $data['objet'];
            $statut = $data['statut'];

            if (!is_array($matricules) || empty($matricules)) {
                http_response_code(400);
                echo json_encode(['error' => 'Liste de matricules invalide']);
                break;
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO bordereau (id_bordereau, reference, matricule, objet, statut, date_creation) VALUES (?, ?, ?, ?, ?, NOW())");
                foreach ($matricules as $matricule) {
                    if (empty($matricule)) {
                        continue;
                    }
                    $stmt->execute([$id_bordereau, $reference, $matricule, $objet, $statut]);
                }
                $pdo->commit();
                http_response_code(201);
                echo json_encode(['message' => 'Bordereau créé']);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la création : ' . $e->getMessage()]);
            }
            break;

        // PUT : Mettre à jour un bordereau spécifique
        case 'PUT':
            if (isset($_GET['id_bordereau'], $_GET['matricule'])) {
                $id_bordereau = $_GET['id_bordereau'];
                $matricule = $_GET['matricule'];
                $data = json_decode(file_get_contents('php://input'), true);

                $reference = $data['reference'] ?? null;
                $objet = $data['objet'] ?? null;
                $statut = $data['statut'] ?? null;

                $updates = [];
                $params = [];
                if ($reference) {
                    $updates[] = "reference = ?";
                    $params[] = $reference;
                }
                if ($objet) {
                    $updates[] = "objet = ?";
                    $params[] = $objet;
                }
                if ($statut) {
                    $updates[] = "statut = ?";
                    $params[] = $statut;
                }

                if (empty($updates)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Aucune donnée à mettre à jour']);
                    break;
                }

                $params[] = $id_bordereau;
                $params[] = $matricule;
                $query = "UPDATE bordereau SET " . implode(', ', $updates) . " WHERE id_bordereau = ? AND matricule = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['message' => 'Bordereau mis à jour']);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Bordereau ou matricule non trouvé']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID bordereau ou matricule manquant']);
            }
            break;

        // DELETE : Supprimer une entrée spécifique ou toutes les entrées d’un id_bordereau
        case 'DELETE':
            if (isset($_GET['id_bordereau'], $_GET['matricule'])) {
                $id_bordereau = $_GET['id_bordereau'];
                $matricule = $_GET['matricule'];
                $stmt = $pdo->prepare("DELETE FROM bordereau WHERE id_bordereau = ? AND matricule = ?");
                $stmt->execute([$id_bordereau, $matricule]);
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['message' => 'Entrée supprimée']);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Bordereau ou matricule non trouvé']);
                }
            } elseif (isset($_GET['id_bordereau'])) {
                $id_bordereau = $_GET['id_bordereau'];
                $stmt = $pdo->prepare("DELETE FROM bordereau WHERE id_bordereau = ?");
                $stmt->execute([$id_bordereau]);
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['message' => 'Bordereau supprimé']);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Bordereau non trouvé']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID bordereau manquant']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>