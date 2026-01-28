<?php
// On inclut la configuration de la base de données
$pdo = require_once __DIR__ . '/../config/db.php';

// Configuration
$userToReset = 'saniflojf';     // NOUVEAU NOM D'UTILISATEUR
$newPassword = 'Vivelecafe12';         // Le mot de passe temporaire souhaité

// 1. Hachage du mot de passe (Sécurité)
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    // 2. Mise à jour en base de données
    // On met à jour à la fois le mot de passe ET on s'assure que le username est correct si l'ID est 1
    // (Cette requête est plus flexible : elle mettra à jour le mot de passe si l'utilisateur 'saniflojf' existe déjà)
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$newHash, $userToReset]);

    // Si aucune ligne n'est touchée, c'est peut-être que l'utilisateur s'appelle encore 'Jean-François'
    if ($stmt->rowCount() == 0) {
        // Tentative de renommage de 'Jean-François' vers 'saniflojf' et changement de mot de passe
        $stmt2 = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE username = 'Jean-François'");
        $stmt2->execute([$userToReset, $newHash]);

        if ($stmt2->rowCount() > 0) {
            echo "<h1 style='color:green;'>✅ Utilisateur renommé et mot de passe réinitialisé !</h1>";
        } else {
            echo "<h1 style='color:orange;'>Aucun utilisateur trouvé</h1>";
            echo "<p>Impossible de trouver 'Jean-François' ou 'saniflojf' dans la base.</p>";
            exit;
        }
    }

    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h1 style='color:green;'>✅ Succès !</h1>";
    echo "<p>Nouvel identifiant : <strong>$userToReset</strong></p>";
    echo "<p>Nouveau mot de passe : <strong>$newPassword</strong></p>";
    echo "<hr style='width:300px;'>";
    echo "<p><a href='../admin/' style='background:#0070cd; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Se connecter</a></p>";
    echo "<p style='color:red; font-weight:bold; margin-top:30px;'>⚠️ IMPORTANT : Supprimez ce fichier après usage !</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<h1 style='color:red;'>Erreur</h1>";
    echo $e->getMessage();
}
?>