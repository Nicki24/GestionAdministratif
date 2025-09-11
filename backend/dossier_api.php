<?php
// dossier_api.php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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

$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {
    case "GET":
        if (isset($_GET["id_dossier"])) {
            $stmt = $pdo->prepare("SELECT * FROM dossier WHERE id_dossier = ?");
            $stmt->execute([$_GET["id_dossier"]]);
            $dossier = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                "status" => $dossier ? "success" : "error",
                "data" => $dossier,
                "message" => $dossier ? null : "Dossier non trouvé"
            ]);
        } elseif (isset($_GET["id_bordereau"])) {
            $stmt = $pdo->prepare("SELECT * FROM dossier WHERE id_bordereau = ?");
            $stmt->execute([$_GET["id_bordereau"]]);
            $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                "status" => "success",
                "data" => $dossiers
            ]);
        } else {
            $stmt = $pdo->query("SELECT * FROM dossier ORDER BY id_dossier DESC");
            $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                "status" => "success",
                "data" => $dossiers
            ]);
        }
        break;

    case "POST":
        $input = json_decode(file_get_contents("php://input"), true);
        if (!empty($input["id_bordereau"]) && !empty($input["matricule"])) {
            $stmt = $pdo->prepare("INSERT INTO dossier (id_bordereau, matricule) VALUES (?, ?)");
            $stmt->execute([$input["id_bordereau"], $input["matricule"]]);
            $id_dossier = $pdo->lastInsertId();
            echo json_encode([
                "status" => "success",
                "message" => "Dossier ajouté avec succès",
                "data" => ["id_dossier" => $id_dossier]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Champs manquants (id_bordereau, matricule)"]);
        }
        break;

    case "PUT":
        if (!isset($_GET["id_dossier"])) {
            echo json_encode(["status" => "error", "message" => "ID requis pour la modification"]);
            break;
        }
        $input = json_decode(file_get_contents("php://input"), true);
        if (!empty($input["id_bordereau"]) && !empty($input["matricule"])) {
            $stmt = $pdo->prepare("UPDATE dossier SET id_bordereau = ?, matricule = ? WHERE id_dossier = ?");
            $stmt->execute([$input["id_bordereau"], $input["matricule"], $_GET["id_dossier"]]);
            echo json_encode(["status" => "success", "message" => "Dossier modifié avec succès"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Champs manquants (id_bordereau, matricule)"]);
        }
        break;

    case "DELETE":
        if (!isset($_GET["id_dossier"])) {
            echo json_encode(["status" => "error", "message" => "ID requis pour la suppression"]);
            break;
        }
        $stmt = $pdo->prepare("DELETE FROM dossier WHERE id_dossier = ?");
        $stmt->execute([$_GET["id_dossier"]]);
        echo json_encode(["status" => "success", "message" => "Dossier supprimé avec succès"]);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Méthode non autorisée"]);
}
?>