<?php
// public/index.php

// 1. Démarrage de la session
// Indispensable pour la gestion des jetons CSRF et l'affichage des messages de succès/erreur
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Chargement des dépendances via Composer
// Stripe et Google API en dépendent.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// 3. Chargement des modèles et services
// On inclut les fichiers nécessaires au fonctionnement du site
require_once __DIR__ . '/../models/Certification.php';
require_once __DIR__ . '/../models/Team.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../services/PlanningLogic.php'; // Inclus ici pour être disponible partout
require_once __DIR__ . '/../controllers/HomeController.php';

// 4. Connexion à la base de données
// Ce fichier doit retourner l'instance PDO ($pdo)
$pdo = require_once __DIR__ . '/../config/db.php';

// 5. Routage et exécution
// On injecte l'instance PDO dans le contrôleur.
// Le contrôleur l'utilisera ensuite pour PlanningLogic afin de gérer les blocages de 15 min.
$controller = new HomeController($pdo);
$controller->index();