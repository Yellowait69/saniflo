<?php
session_start();

// Si déjà connecté, on redirige vers le tableau de bord
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

// === NOUVEAU : GÉNÉRATION DU TOKEN CSRF DE LOGIN ===
if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}
// ===================================================

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // === VÉRIFICATION DU TOKEN CSRF DE LOGIN ===
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['login_csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité CSRF : Tentative de connexion bloquée.");
    }
    // ===========================================

    // Connexion DB
    $pdo = require_once __DIR__ . '/../config/db.php';

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Vérification sécurisée
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Régénération de l'ID de session pour éviter le vol de session (Fixation)
        session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user['username'];

        // === NOUVEAU : GÉNÉRATION DU TOKEN CSRF ADMIN DÈS LA CONNEXION ===
        // On s'assure que le panel a un jeton tout neuf dès l'entrée
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

        // Nettoyage du token de login devenu inutile
        unset($_SESSION['login_csrf_token']);
        // =================================================================

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Identifiant ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="login-container">
    <div class="login-box">
        <h2>
            <i class="fas fa-shield-alt" style="color:var(--secondary);"></i> Saniflo Admin
        </h2>

        <?php if($error): ?>
            <div style="background:#ffebee; color:var(--danger); padding:10px; border-radius:6px; margin-bottom:20px; font-size:0.9rem; border:1px solid #ffcdd2;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['login_csrf_token'] ?>">

            <div style="position:relative;">
                <input type="text" name="username" placeholder="Utilisateur" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div style="position:relative;">
                <input type="password" name="password" placeholder="Mot de passe" required>
            </div>

            <button type="submit" class="btn-admin">
                Se connecter <i class="fas fa-sign-in-alt" style="margin-left:5px;"></i>
            </button>
        </form>

        <p style="margin-top:25px; font-size:0.9rem; opacity:0.8;">
            <a href="../" style="display:flex; align-items:center; justify-content:center; gap:5px; color:var(--text-muted);">
                <i class="fas fa-arrow-left"></i> Retour au site
            </a>
        </p>
    </div>
</div>

</body>
</html>