<?php
// public/index.php

/**
 * 1. DÉMARRAGE DE LA SESSION
 * Indispensable pour la gestion des jetons CSRF (sécurité des formulaires)
 * et pour l'affichage des messages flash (succès/erreur).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 2. CHARGEMENT DES DÉPENDANCES (COMPOSER)
 * Nécessaire pour les bibliothèques Stripe et Google Calendar API.
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * 3. CHARGEMENT DES MODÈLES, SERVICES ET CONTRÔLEUR
 */
require_once __DIR__ . '/../models/Certification.php';
require_once __DIR__ . '/../models/Team.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../services/PlanningLogic.php';
require_once __DIR__ . '/../controllers/HomeController.php';

/**
 * 4. CONNEXION À LA BASE DE DONNÉES
 * Le fichier db.php doit retourner une instance PDO ($pdo).
 */
$pdo = require_once __DIR__ . '/../config/db.php';


/**
 * =========================================================================
 * NOUVEAU : TRACKING DES VISITEURS UNIQUES (Pour les stats du Dashboard)
 * =========================================================================
 */
if (!isset($_SESSION['visited_today'])) {
    try {
        $today = date('Y-m-d');
        // Insère une nouvelle ligne pour aujourd'hui, ou incrémente le compteur si elle existe déjà
        $stmt = $pdo->prepare("INSERT INTO visitors (visit_date, visits_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE visits_count = visits_count + 1");
        $stmt->execute([$today]);

        // Empêche de recompter ce visiteur s'il navigue sur d'autres pages aujourd'hui
        $_SESSION['visited_today'] = true;
    } catch (Exception $e) {
        // Silencieux pour ne pas bloquer le site en cas de problème avec la table
    }
}
// =========================================================================


/**
 * 5. ROUTAGE SIMPLE
 * On analyse l'URL pour savoir quelle page afficher.
 */
$controller = new HomeController($pdo);

// On récupère le paramètre "page" dans l'URL (ex: index.php?page=reservation)
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'reservation':
        // Affiche la page de prise de rendez-vous dédiée (Wizard)
        $controller->reservation();
        break;

    case 'contact':
        // Affiche la nouvelle page de contact dédiée
        $controller->contact();
        break;

    case 'modifier_rdv':
        // Affiche la page permettant au client de demander la modification de son RDV
        $controller->modifier_rdv();
        break;

    case 'home':
    default:
        // Affiche la page d'accueil (Landing Page vitrine)
        $controller->index();
        break;
}