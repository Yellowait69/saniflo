<?php

class HomeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // 1. Initialisation des variables
        $message_status = '';

        // 2. Traitement du Formulaire (Logique Contrôleur)
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nom = htmlspecialchars(strip_tags(trim($_POST['nom'])));
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $tel = htmlspecialchars(strip_tags(trim($_POST['tel'])));
            $message = htmlspecialchars(strip_tags(trim($_POST['message'])));

            if (!empty($nom) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // Logique d'envoi d'email ici...
                // mail(...);
                $message_status = '<div class="alert success">Merci ! Votre message a bien été envoyé.</div>';
            } else {
                $message_status = '<div class="alert error">Veuillez remplir correctement tous les champs.</div>';
            }
        }

        // 3. Récupération des données via les Modèles
        try {
            $certifications = Certification::getAll($this->pdo);
            $teamMembers = Team::getAll($this->pdo);
            $services = Service::getAll($this->pdo);
        } catch (Exception $e) {
            // En production, on log l'erreur et on affiche un message gentil
            $certifications = [];
            $teamMembers = [];
            $services = [];
            $error = "Erreur de connexion à la base de données.";
        }

        // 4. Chargement de la Vue (On passe les variables)
        require __DIR__ . '/../views/home.php';
    }
}