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
        // GET : Lister tous les bordereaux, un bordereau spécifique ou les statistiques
        case 'GET':
            if (isset($_GET['stats']) && isset($_GET['period']) && is_numeric($_GET['period'])) {
                $period = (int)$_GET['period'];
                $stmt = $pdo->prepare("SELECT DATE(date_creation) AS day, COUNT(*) AS count FROM bordereau WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(date_creation)");
                $stmt->execute([$period]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($result);
            } elseif (isset($_GET['id_bordereau']) && is_numeric($_GET['id_bordereau'])) {
                $id_bordereau = (int)$_GET['id_bordereau'];
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

        // POST : Créer un bordereau
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            file_put_contents('debug.log', "[POST] Données reçues : " . json_encode($data) . "\n", FILE_APPEND);

            // Vérifier les champs obligatoires
            if (!isset($data['id_bordereau'], $data['matricules'], $data['objet'], $data['statut']) ||
                !is_numeric($data['id_bordereau']) || empty($data['matricules']) || empty($data['objet']) || empty($data['statut'])) {
                http_response_code(400);
                $error = ['error' => 'Les champs id_bordereau, matricules, objet et statut sont requis'];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            $id_bordereau = (int)$data['id_bordereau'];
            $matricules = is_array($data['matricules']) ? $data['matricules'] : explode(',', trim($data['matricules']));
            $reference = isset($data['reference']) ? trim($data['reference']) : '';
            $objet = trim($data['objet']);
            $statut = $data['statut'];

            // Valider les matricules (6 chiffres chacun)
            foreach ($matricules as $matricule) {
                $matricule = trim($matricule);
                if (!preg_match('/^\d{6}$/', $matricule)) {
                    http_response_code(400);
                    $error = ['error' => "Le matricule '$matricule' doit contenir exactement 6 chiffres"];
                    file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break 2;
                }
            }

            // Valider la longueur de la référence
            if (strlen($reference) > 50) {
                http_response_code(400);
                $error = ['error' => 'La référence dépasse la limite de 50 caractères'];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            // Valider la longueur de l'objet
            if (strlen($objet) > 500) {
                http_response_code(400);
                $error = ['error' => 'La description dépasse la limite de 500 caractères'];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            // Valider le statut
            if (!in_array($statut, ['Mandatement', 'Secours', 'VISA'])) {
                http_response_code(400);
                $error = ['error' => 'Statut invalide : choisissez parmi Mandatement, Secours ou VISA'];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            // Vérifier si l'id_bordereau existe déjà (strictement interdit en POST)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bordereau WHERE id_bordereau = ?");
            $stmt->execute([$id_bordereau]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                $error = ['error' => "Cet id_bordereau ($id_bordereau) existe déjà"];
                file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
                break;
            }

            // Vérifier l'unicité des paires (id_bordereau, matricule)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bordereau WHERE id_bordereau = ? AND matricule = ?");
            foreach ($matricules as $matricule) {
                $matricule = trim($matricule);
                $stmt->execute([$id_bordereau, $matricule]);
                if ($stmt->fetchColumn() > 0) {
                    http_response_code(400);
                    $error = ['error' => "La paire id_bordereau ($id_bordereau) et matricule ($matricule) existe déjà"];
                    file_put_contents('debug.log', "[POST] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break 2;
                }
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO bordereau (id_bordereau, matricule, reference, objet, statut, date_creation) VALUES (?, ?, ?, ?, ?, NOW())");
                foreach ($matricules as $matricule) {
                    $matricule = trim($matricule);
                    $stmt->execute([$id_bordereau, $matricule, $reference, $objet, $statut]);
                }
                $pdo->commit();
                http_response_code(201);
                $response = ['message' => 'Bordereau créé', 'id_bordereau' => $id_bordereau, 'matricules' => $matricules];
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

        // PUT : Mettre à jour un bordereau spécifique
        case 'PUT':
            if (isset($_GET['id_bordereau']) && is_numeric($_GET['id_bordereau'])) {
                $id_bordereau = (int)$_GET['id_bordereau'];
                $data = json_decode(file_get_contents('php://input'), true);
                file_put_contents('debug.log', "[PUT] Données reçues pour id_bordereau $id_bordereau : " . json_encode($data) . "\n", FILE_APPEND);

                // Vérifier les champs fournis
                if (!isset($data['matricules'], $data['objet'], $data['statut']) ||
                    empty($data['matricules']) || empty($data['objet']) || empty($data['statut'])) {
                    http_response_code(400);
                    $error = ['error' => 'Les champs matricules, objet et statut sont requis'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                $matricules = is_array($data['matricules']) ? $data['matricules'] : explode(',', trim($data['matricules']));
                $reference = isset($data['reference']) ? trim($data['reference']) : '';
                $objet = trim($data['objet']);
                $statut = $data['statut'];

                // Valider les matricules (6 chiffres chacun)
                foreach ($matricules as $matricule) {
                    $matricule = trim($matricule);
                    if (!preg_match('/^\d{6}$/', $matricule)) {
                        http_response_code(400);
                        $error = ['error' => "Le matricule '$matricule' doit contenir exactement 6 chiffres"];
                        file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                        echo json_encode($error);
                        break 2;
                    }
                }

                // Valider la longueur de la référence
                if (strlen($reference) > 50) {
                    http_response_code(400);
                    $error = ['error' => 'La référence dépasse la limite de 50 caractères'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                // Valider la longueur de l'objet
                if (strlen($objet) > 500) {
                    http_response_code(400);
                    $error = ['error' => 'La description dépasse la limite de 500 caractères'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                // Valider le statut
                if (!in_array($statut, ['Mandatement', 'Secours', 'VISA'])) {
                    http_response_code(400);
                    $error = ['error' => 'Statut invalide : choisissez parmi Mandatement, Secours ou VISA'];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                    break;
                }

                try {
                    $pdo->beginTransaction();
                    // Supprimer les anciennes lignes pour cet id_bordereau
                    $stmt = $pdo->prepare("DELETE FROM bordereau WHERE id_bordereau = ?");
                    $stmt->execute([$id_bordereau]);

                    // Insérer les nouvelles lignes
                    $stmt = $pdo->prepare("INSERT INTO bordereau (id_bordereau, matricule, reference, objet, statut, date_creation) VALUES (?, ?, ?, ?, ?, NOW())");
                    foreach ($matricules as $matricule) {
                        $matricule = trim($matricule);
                        $stmt->execute([$id_bordereau, $matricule, $reference, $objet, $statut]);
                    }
                    $pdo->commit();
                    $response = ['message' => 'Bordereau mis à jour'];
                    file_put_contents('debug.log', "[PUT] Succès : " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    http_response_code(500);
                    $error = ['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()];
                    file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                }
            } else {
                http_response_code(400);
                $error = ['error' => 'ID bordereau invalide'];
                file_put_contents('debug.log', "[PUT] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                echo json_encode($error);
            }
            break;

        // DELETE : Supprimer un bordereau spécifique
        case 'DELETE':
            if (isset($_GET['id_bordereau']) && is_numeric($_GET['id_bordereau'])) {
                $id_bordereau = (int)$_GET['id_bordereau'];
                $stmt = $pdo->prepare("DELETE FROM bordereau WHERE id_bordereau = ?");
                $stmt->execute([$id_bordereau]);
                if ($stmt->rowCount() > 0) {
                    $response = ['message' => 'Bordereau supprimé'];
                    file_put_contents('debug.log', "[DELETE] Succès : " . json_encode($response) . "\n", FILE_APPEND);
                    echo json_encode($response);
                } else {
                    http_response_code(404);
                    $error = ['error' => 'Bordereau non trouvé'];
                    file_put_contents('debug.log', "[DELETE] Erreur : " . json_encode($error) . "\n", FILE_APPEND);
                    echo json_encode($error);
                }
            } else {
                http_response_code(400);
                $error = ['error' => 'ID bordereau invalide'];
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