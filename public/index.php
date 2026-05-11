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
 * 5. ROUTAGE SIMPLE
 * On analyse l'URL pour savoir quelle page afficher.
 */
$controller = new HomeController($pdo);

// On récupère le paramètre "page" dans l'URL (ex: index.php?page=reservation)
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'reservation':
        // Affiche la nouvelle page de prise de rendez-vous dédiée
        $controller->reservation();
        break;

    case 'home':
    default:
        // Affiche la page d'accueil (Landing Page vitrine)
        $controller->index();
        break;
}