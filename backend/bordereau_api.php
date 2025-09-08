<?php
// bordereau_api.php - Gestion des bordereaux avec CORS
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Définir le type de contenu JSON
header("Content-Type: application/json; charset=UTF-8");

// Connexion à MySQL
$host = "localhost";
$dbname = "gestion_bordereaux";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connexion échouée : " . $e->getMessage()]);
    exit();
}

// Méthode HTTP utilisée
$method = $_SERVER["REQUEST_METHOD"];
$statuts_valides = ['Mandatement', 'Secours', 'VISA'];

switch ($method) {
    case "GET":
        if (isset($_GET["id_bordereau"])) {
            $id = filter_var($_GET["id_bordereau"], FILTER_VALIDATE_INT);
            if (!$id) {
                echo json_encode(["status" => "error", "message" => "ID invalide"]);
                break;
            }

            $stmt = $pdo->prepare("SELECT * FROM bordereau WHERE id_bordereau = ?");
            $stmt->execute([$id]);
            $bordereau = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($bordereau) {
                $stmt = $pdo->prepare("SELECT * FROM dossier WHERE id_bordereau = ?");
                $stmt->execute([$bordereau["id_bordereau"]]);
                $bordereau["dossiers"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["status" => "success", "data" => $bordereau]);
            } else {
                echo json_encode(["status" => "error", "message" => "Aucun bordereau trouvé avec cet ID"]);
            }
        } else {
            $stmt = $pdo->query("SELECT * FROM bordereau ORDER BY date_creation DESC");
            $bordereaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($bordereaux as &$b) {
                $stmt = $pdo->prepare("SELECT * FROM dossier WHERE id_bordereau = ?");
                $stmt->execute([$b["id_bordereau"]]);
                $b["dossiers"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode(["status" => "success", "data" => $bordereaux]);
        }
        break;

    case "POST":
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(["status" => "error", "message" => "JSON invalide"]);
            break;
        }

        if (empty($input["reference"]) || empty($input["objet"]) || empty($input["statut"])) {
            echo json_encode(["status" => "error", "message" => "Champs obligatoires manquants"]);
            break;
        }

        if (!in_array($input["statut"], $statuts_valides)) {
            echo json_encode(["status" => "error", "message" => "Statut invalide. Valeurs acceptées: Mandatement, Secours, VISA"]);
            break;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO bordereau (reference, objet, statut) VALUES (?, ?, ?)");
            $stmt->execute([$input["reference"], $input["objet"], $input["statut"]]);
            $id_bordereau = $pdo->lastInsertId();

            if (!empty($input["dossiers"])) {
                foreach ($input["dossiers"] as $matricule) {
                    $matricule = trim($matricule);
                    if (!empty($matricule)) {
                        $stmt = $pdo->prepare("INSERT INTO dossier (id_bordereau, matricule) VALUES (?, ?)");
                        $stmt->execute([$id_bordereau, $matricule]);
                    }
                }
            }

            $stmt = $pdo->prepare("SELECT * FROM bordereau WHERE id_bordereau = ?");
            $stmt->execute([$id_bordereau]);
            $newBordereau = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM dossier WHERE id_bordereau = ?");
            $stmt->execute([$id_bordereau]);
            $newBordereau["dossiers"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $pdo->commit();
            
            echo json_encode([
                "status" => "success",
                "message" => "Bordereau ajouté avec succès",
                "data" => $newBordereau
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(["status" => "error", "message" => "Erreur: " . $e->getMessage()]);
        }
        break;

    case "PUT":
        if (!isset($_GET["id_bordereau"])) {
            echo json_encode(["status" => "error", "message" => "ID requis pour la modification"]);
            break;
        }

        $id = filter_var($_GET["id_bordereau"], FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(["status" => "error", "message" => "ID invalide"]);
            break;
        }

        $input = json_decode(file_get_contents("php://input"), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(["status" => "error", "message" => "JSON invalide"]);
            break;
        }

        if (empty($input["reference"]) || empty($input["objet"]) || empty($input["statut"])) {
            echo json_encode(["status" => "error", "message" => "Champs obligatoires manquants"]);
            break;
        }

        if (!in_array($input["statut"], $statuts_valides)) {
            echo json_encode(["status" => "error", "message" => "Statut invalide. Valeurs acceptées: Mandatement, Secours, VISA"]);
            break;
        }

        // Vérifier si le bordereau existe
        $stmt = $pdo->prepare("SELECT * FROM bordereau WHERE id_bordereau = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(["status" => "error", "message" => "Bordereau introuvable"]);
            break;
        }

        $stmt = $pdo->prepare("UPDATE bordereau SET reference = ?, objet = ?, statut = ? WHERE id_bordereau = ?");
        $stmt->execute([$input["reference"], $input["objet"], $input["statut"], $id]);

        $stmt = $pdo->prepare("SELECT * FROM bordereau WHERE id_bordereau = ?");
        $stmt->execute([$id]);
        $updatedBordereau = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "message" => "Bordereau modifié avec succès",
            "data" => $updatedBordereau
        ]);
        break;

    case "DELETE":
        if (!isset($_GET["id_bordereau"])) {
            echo json_encode(["status" => "error", "message" => "ID requis pour la suppression"]);
            break;
        }

        $id = filter_var($_GET["id_bordereau"], FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(["status" => "error", "message" => "ID invalide"]);
            break;
        }

        // Vérifier si le bordereau existe
        $stmt = $pdo->prepare("SELECT * FROM bordereau WHERE id_bordereau = ?");
        $stmt->execute([$id]);
        $bordereau = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bordereau) {
            echo json_encode(["status" => "error", "message" => "Bordereau introuvable"]);
            break;
        }

        $stmt = $pdo->prepare("DELETE FROM bordereau WHERE id_bordereau = ?");
        $stmt->execute([$id]);

        echo json_encode([
            "status" => "success",
            "message" => "Bordereau supprimé avec succès",
            "data" => $bordereau
        ]);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Méthode non autorisée"]);
}