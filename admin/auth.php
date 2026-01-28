<?php
// admin/auth.php

// 1. Démarrage de la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Vérification de l'authentification
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Utilisateur non connecté -> Redirection vers le login
    header("Location: index.php");
    exit;
}

// 3. Sécurité : Déconnexion automatique après inactivité (30 minutes)
$timeout_duration = 1800; // 30 minutes en secondes

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session expirée
    session_unset();     // Vider les variables
    session_destroy();   // Détruire la session
    header("Location: index.php?msg=timeout");
    exit;
}

// Mise à jour du timestamp de dernière activité
$_SESSION['last_activity'] = time();
?>