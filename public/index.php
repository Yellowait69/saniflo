<?php
// 1. Démarrage de session (si besoin plus tard)
session_start();

// 2. Chargement des fichiers nécessaires (Autoloader manuel ici)
// Dans un vrai projet pro, on utiliserait Composer (vendor/autoload.php)
require_once __DIR__ . '/../models/Certification.php';
require_once __DIR__ . '/../models/Team.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../controllers/HomeController.php';

// 3. Connexion à la base de données
$pdo = require_once __DIR__ . '/../config/db.php';

// 4. Routage simple (Comme c'est un One-Page, c'est facile)
$controller = new HomeController($pdo);
$controller->index();