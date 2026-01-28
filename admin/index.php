<?php
session_start();
// Si déjà connecté, on va direct au dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connexion DB
    $pdo = require_once __DIR__ . '/../config/db.php';

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Vérification
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Saniflo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <h2>Saniflo Admin</h2>
        <?php if($error): ?><p style="color:red; font-size:0.9rem;"><?= $error ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit" class="btn-admin">Se connecter</button>
        </form>
        <p style="margin-top:20px; font-size:0.8rem; color:#888;"><a href="../">← Retour au site</a></p>
    </div>
</div>
</body>
</html>