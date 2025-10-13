<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

header('Content-Type: application/json');

// REMPLACEZ par le mot de passe que vous voulez utiliser
$email = 'norlandehubery@gmail.com';
$password = 'CoachPro123'; // ← Changez ce mot de passe

try {
    // Générer un hash bcrypt correct
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    echo "Mot de passe choisi: " . $password . "\n";
    echo "Hash généré: " . $hashed_password . "\n";

    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);
        $message = "✅ Utilisateur mis à jour avec succès!";
    } else {
        // Créer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO utilisateur (email, mot_de_passe) VALUES (?, ?)");
        $stmt->execute([$email, $hashed_password]);
        $message = "✅ Utilisateur créé avec succès!";
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'email' => $email,
        'password_used' => $password, // Pour vous rappeler le mot de passe
        'hashed_password' => $hashed_password
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>