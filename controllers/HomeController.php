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
        $message_status = ''; // Pour le formulaire de contact
        $quote_status = '';   // Pour le devis

        // 3. Traitement des Formulaires (POST)
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // --- CAS A : FORMULAIRE DE DEVIS (Nouveau) ---
            if (isset($_POST['submit_quote'])) {
                // Vérification CSRF
                if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
                    $quote_status = '<div class="alert error">Erreur de sécurité (Session expirée). Veuillez rafraîchir.</div>';
                } else {
                    // Assainissement des entrées
                    $data = [
                        'energy_type'  => htmlspecialchars(trim($_POST['energy_type'] ?? '')),
                        'surface_area' => htmlspecialchars(trim($_POST['surface_area'] ?? '')),
                        'timeline'     => htmlspecialchars(trim($_POST['timeline'] ?? '')),
                        'description'  => htmlspecialchars(strip_tags(trim($_POST['description'] ?? ''))),
                        'firstname'    => htmlspecialchars(trim($_POST['firstname'] ?? '')),
                        'lastname'     => htmlspecialchars(trim($_POST['lastname'] ?? '')),
                        'email'        => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
                        'phone'        => htmlspecialchars(trim($_POST['phone'] ?? '')),
                        'street'       => htmlspecialchars(trim($_POST['street'] ?? '')),
                        'zip'          => htmlspecialchars(trim($_POST['zip'] ?? '')),
                        'city'         => htmlspecialchars(trim($_POST['city'] ?? ''))
                    ];

                    // Validation basique
                    if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL) && !empty($data['lastname'])) {
                        try {
                            // On suppose que vous avez créé le modèle Quote.php comme demandé
                            if (class_exists('Quote')) {
                                Quote::create($this->pdo, $data);
                                $quote_status = '<div class="alert success">Votre demande de devis a bien été reçue. Nous vous recontactons sous 24h.</div>';
                            } else {
                                $quote_status = '<div class="alert error">Erreur interne : Modèle de devis introuvable.</div>';
                            }
                        } catch (Exception $e) {
                            // Log l'erreur réelle pour le dév : error_log($e->getMessage());
                            $quote_status = '<div class="alert error">Une erreur est survenue lors de l\'enregistrement.</div>';
                        }
                    } else {
                        $quote_status = '<div class="alert error">Veuillez vérifier les champs obligatoires (Nom, Email).</div>';
                    }
                }
            }

            // --- CAS B : FORMULAIRE DE CONTACT (Existant) ---
            // On vérifie si c'est le formulaire de contact (pas de submit_quote)
            elseif (isset($_POST['nom']) && isset($_POST['message'])) {
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
        }

        // 4. Récupération des données via les Modèles
        try {
            $certifications = Certification::getAll($this->pdo);
            $teamMembers = Team::getAll($this->pdo);
            $services = Service::getAll($this->pdo);
        } catch (Exception $e) {
            // En production, on log l'erreur et on affiche un message gentil
            $certifications = [];
            $teamMembers = [];
            $services = [];
            // $error = "Erreur de connexion à la base de données.";
        }

        // 5. Chargement de la Vue (On passe les variables)
        require __DIR__ . '/../views/home.php';
    }
}