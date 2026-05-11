<?php
// admin/logout.php

// Initialiser la session (vérification au préalable)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Détruire toutes les variables de session (cela supprime aussi les jetons CSRF)
$_SESSION = array();

// 2. Tuer la session complètement en effaçant le cookie de session du navigateur.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalement, on détruit la session côté serveur
session_destroy();

// 4. Redirection vers la page de connexion
// (Optionnel : ajout d'un paramètre msg pour afficher une notification sur index.php)
header("Location: index.php?msg=logged_out");
exit;
?>