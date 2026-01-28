<?php
// Initialiser la session
session_start();

// 1. Détruire toutes les variables de session
$_SESSION = array();

// 2. Si l'on souhaite tuer la session complètement, il faut aussi effacer le cookie de session.
// Note : Cela détruira la session et pas seulement les données de session !
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
header("Location: index.php");
exit;
?>