<?php

class HomeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // 1. Initialisation de la session et Sécurité (CSRF)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // 2. Initialisation des variables d'affichage
        $message_status = ''; // Pour le formulaire de contact uniquement

        // 3. Traitement des Formulaires (POST)
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // --- FORMULAIRE DE CONTACT ---
            if (isset($_POST['nom']) && isset($_POST['message'])) {
                $nom = htmlspecialchars(strip_tags(trim($_POST['nom'])));
                $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                $tel = htmlspecialchars(strip_tags(trim($_POST['tel'])));
                $message = htmlspecialchars(strip_tags(trim($_POST['message'])));

                if (!empty($nom) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Logique d'envoi d'email (ex: mail())
                    $message_status = '<div class="alert success">Merci ! Votre message a bien été envoyé.</div>';
                } else {
                    $message_status = '<div class="alert error">Veuillez remplir correctement tous les champs.</div>';
                }
            }
        }

        // 4. Récupération des données via les Modèles
        try {
            $certifications = Certification::getAll($this->pdo);
            $teamMembers = Team::getAll($this->pdo);
            $services = Service::getAll($this->pdo);
        } catch (Exception $e) {
            // En cas d'erreur, on initialise des tableaux vides pour éviter les erreurs dans la vue
            $certifications = [];
            $teamMembers = [];
            $services = [];
        }

        // 5. Chargement de la Vue
        require __DIR__ . '/../views/home.php';
    }
}