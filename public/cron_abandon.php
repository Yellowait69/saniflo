<?php
// public/cron_abandon.php
// À configurer sur le cPanel pour s'exécuter toutes les 5 minutes : */5 * * * *
// Commande cPanel : /usr/local/bin/php /home/saniflo-demo/www/public/cron_abandon.php >/dev/null 2>&1

// 1. Chargement de la connexion à la base de données (On remonte d'un dossier avec /../)
$pdo = require_once __DIR__ . '/../config/db.php';

// 2. Chargement du contrôleur contenant la logique (On remonte d'un dossier avec /../)
require_once __DIR__ . '/../controllers/HomeController.php';

try {
    // 3. Initialisation du contrôleur
    $controller = new HomeController($pdo);

    // 4. Lancement de la vérification (5 min pour relance, 30 min pour annulation)
    $controller->cron_abandoned_checkouts();

    // Message de succès pour les logs du serveur Cron
    echo "[" . date('Y-m-d H:i:s') . "] Tâche Cron exécutée avec succès : Vérification des paiements Stripe abandonnés terminée.\n";

} catch (Exception $e) {
    // Enregistrement de l'erreur dans les logs PHP en cas de problème
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] Erreur lors de l'exécution du Cron Stripe : " . $e->getMessage();
    error_log($errorMessage);

    // Affichage de l'erreur (visible si vous testez le script manuellement dans le navigateur)
    echo $errorMessage . "\n";
}