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
        // GET : Lister tous les départements ou un département spécifique
        case 'GET':
            if (isset($_GET['id_departement']) && is_numeric($_GET['id_departement'])) {
                $id_departement = (int)$_GET['id_departement'];
                $stmt = $pdo->prepare("SELECT * FROM departement WHERE id_departement = ?");
                $stmt->execute([$id_departement]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($result ? $result : []);
            } else {
                $stmt = $pdo->query("SELECT * FROM departement");
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($result);
            }
            break;

        // POST : Créer un département
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            file_put_contents('debug.log', "[POST] Données reçues : " . json_encode($data) . "\n", FILE_APPEND);

            // Vérifier les champs obligatoires
            if (!isset($data['id_departement'], $data['destination'], $data['nature']) ||
                !is_numeric($data['id_departement']) || empty($data['destination']) || empty($data['nature'])) {
                http_response_code(400);
                $error = ['error' => 'Les champs id_departement, destination et nature sont requis'];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            $id_departement = (int)$data['id_departement'];
            $destination = trim($data['destination']);
            $nature = trim($data['nature']);
            $expediteur = isset($data['expediteur']) ? trim($data['expediteur']) : 'SRSP Atsimo Andrefana'; // Valeur par défaut si non fournie

            // Valider la longueur de destination
            if (strlen($destination) > 100) {
                http_response_code(400);
                $error = ['error' => 'La destination dépasse la limite de 100 caractères'];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            // Valider la longueur de nature
            if (strlen($nature) > 100) {
                http_response_code(400);
                $error = ['error' => 'La nature dépasse la limite de 100 caractères'];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            // Valider la longueur de expediteur
            if (strlen($expediteur) > 100) {
                http_response_code(400);
                $error = ['error' => 'L\'expéditeur dépasse la limite de 100 caractères'];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            // Vérifier si l'id_departement existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM departement WHERE id_departement = ?");
            $stmt->execute([$id_departement]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                $error = ['error' => "Cet id_departement ($id_departement) existe déjà"];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO departement (id_departement, destination, nature, expediteur) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_departement, $destination, $nature, $expediteur]);
                $pdo->commit();
                http_response_code(201);
                $response = ['message' => 'Département créé', 'id_departement' => $id_departement];
                file_put_contents('debug.log', "[POST] Succès : " . json_encode($response) . "\n", FILE_APPEND);
                echo json_encode($response);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                $error = ['error' => 'Erreur lors de la création : ' . $e->getMessage()];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
            }
            break;

        // PUT : Mettre à jour un département
        case 'PUT':
            if (isset($_GET['id_departement']) && is_numeric($_GET['id_departement'])) {
                $id_departement = (int)$_GET['id_departement'];
                $data = json_decode(file_get_contents('php://input'), true);
                file_put_contents('debug.log', "[PUT] Données reçues pour id_departement $id_departement : " . json_encode($data) . "\n", FILE_APPEND);

                // Vérifier les champs fournis
                if (!isset($data['destination'], $data['nature']) ||
                    empty($data['destination']) || empty($data['nature'])) {
                    http_response_code(400);
                    $error = ['error' => 'Les champs destination et nature sont requis'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                $destination = trim($data['destination']);
                $nature = trim($data['nature']);
                $expediteur = isset($data['expediteur']) ? trim($data['expediteur']) : 'SRSP Atsimo Andrefana'; // Valeur par défaut si non fournie

                // Valider la longueur de destination
                if (strlen($destination) > 100) {
                    http_response_code(400);
                    $error = ['error' => 'La destination dépasse la limite de 100 caractères'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                // Valider la longueur de nature
                if (strlen($nature) > 100) {
                    http_response_code(400);
                    $error = ['error' => 'La nature dépasse la limite de 100 caractères'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                // Valider la longueur de expediteur
                if (strlen($expediteur) > 100) {
                    http_response_code(400);
                    $error = ['error' => 'L\'expéditeur dépasse la limite de 100 caractères'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                try {
                    $stmt = $pdo->prepare("UPDATE departement SET destination = ?, nature = ?, expediteur = ? WHERE id_departement = ?");
                    $stmt->execute([$destination, $nature, $expediteur, $id_departement]);
                    if ($stmt->rowCount() > 0) {
                        $response = ['message' => 'Département mis à jour'];
                        file_put_contents('debug.log', "[PUT] Succès : " . json_encode($response) . "\n", FILE_APPEND);
                        echo json_encode($response);
                    } else {
                        http_response_code(404);
                        $error = ['error' => 'Département non trouvé'];
                        file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                        echo json_encode($error);
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    $error = ['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                }
            } else {
                http_response_code(400);
                $error = ['error' => 'ID département invalide'];
                file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
            }
            break;

        // DELETE : Supprimer un département
        case 'DELETE':
            if (isset($_GET['id_departement']) && is_numeric($_GET['id_departement'])) {
                $id_departement = (int)$_GET['id_departement'];
                $stmt = $pdo->prepare("DELETE FROM departement WHERE id_departement = ?");
                $stmt->execute([$id_departement]);
                if ($stmt->rowCount() > 0) {
                    $response = ['message' => 'Département supprimé'];
                    file_put_contents('debug.log', "[DELETE] Succès : " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } else {
                    http_response_code(404);
                    $error = ['error' => 'Département non trouvé'];
                    file_put_contents('debug.log', "[DELETE] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                }
            } else {
                http_response_code(400);
                $error = ['error' => 'ID département invalide'];
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