<?php
/**
 * Contrôleur Frontal (Front Controller) - Saniflo Application
 * * @package   Saniflo
 * @author    Administration
 * @version   2.1.0
 */

declare(strict_types=1);

// 1. DÉMARRAGE DE LA SESSION SÉCURISÉE
if (session_status() === PHP_SESSION_NONE) {
    // Configuration pour renforcer la sécurité des cookies de session en production
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// 2. CHARGEMENT DES DÉPENDANCES & AUTOLOAD (COMPOSER)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    die("Erreur critique : L'autoloader du projet est introuvable. Veuillez exécuter 'composer install'.");
}

// 3. CHARGEMENT DES MODÈLES, SERVICES ET CONTRÔLEURS
// Note : Si vous migrez vers PSR-4 avec Composer, ces lignes pourront être supprimées.
require_once __DIR__ . '/../models/Certification.php';
require_once __DIR__ . '/../models/Team.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../services/PlanningLogic.php';
require_once __DIR__ . '/../controllers/HomeController.php';

// 4. CONNEXION À LA BASE DE DONNÉES
$pdo = require_once __DIR__ . '/../config/db.php';

if (!$pdo instanceof PDO) {
    error_log("Erreur critique : Le fichier de configuration db.php n'a pas retourné une instance PDO valide.");
    die("Une erreur technique est survenue. Veuillez réessayer plus tard.");
}

// =========================================================================
// 5. TRACKING DES VISITEURS UNIQUES (Méthode Cookie Persistant - Précision accrue)
// =========================================================================
$cookieName = 'sf_tracked_today';

if (!isset($_COOKIE[$cookieName])) {
    try {
        $today = date('Y-m-d');
        // Requête atomique préparée pour insérer la date ou incrémenter le trafic existant
        $stmt = $pdo->prepare("
            INSERT INTO visitors (visit_date, visits_count) 
            VALUES (?, 1) 
            ON DUPLICATE KEY UPDATE visits_count = visits_count + 1
        ");
        $stmt->execute([$today]);

        // Calcul précis du timestamp de ce soir à 23:59:59 pour expiration à minuit
        $midnight = strtotime('tomorrow 00:00:00') - 1;

        // Définition du cookie avec des paramètres de sécurité modernes
        setcookie($cookieName, '1', [
            'expires'  => $midnight,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,  // Empêche l'accès au cookie via JavaScript (sécurité XSS)
            'samesite' => 'Lax'   // Protection contre les attaques CSRF
        ]);
    } catch (PDOException $e) {
        // Enregistrement de l'erreur dans les logs du serveur sans perturber l'internaute
        error_log("Échec du tracking des visiteurs par cookie : " . $e->getMessage());
    }
}
// =========================================================================

// 6. ROUTAGE ET TRAITEMENT DE LA REQUÊTE
$controller = new HomeController($pdo);

// Récupération et nettoyage du paramètre "page" pour éviter les failles potentielles
$page = isset($_GET['page']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_GET['page']) : 'home';

switch ($page) {
    case 'reservation':
        // Affiche le processus de prise de rendez-vous (Wizard d'installation/dépannage)
        $controller->reservation();
        break;

    case 'contact':
        // Affiche le formulaire de contact et les informations de l'entreprise
        $controller->contact();
        break;

    case 'modifier_rdv':
        // Espace client permettant de soumettre une demande de modification de rendez-vous
        $controller->modifier_rdv();
        break;

    case 'home':
    default:
        // Page d'accueil principale (Landing Page vitrine)
        $controller->index();
        break;
}