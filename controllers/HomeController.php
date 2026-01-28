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
        $message_status = '';

        // 3. Traitement des Formulaires (POST)
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // --- A. FORMULAIRE DE CONTACT (Amélioré avec l'objet) ---
            if (isset($_POST['nom']) && isset($_POST['message']) && !isset($_POST['appointment_date'])) {
                $nom = htmlspecialchars(strip_tags(trim($_POST['nom'])));
                $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                $tel = htmlspecialchars(strip_tags(trim($_POST['tel'])));
                $objet = htmlspecialchars(strip_tags(trim($_POST['objet'] ?? 'Information')));
                $message = htmlspecialchars(strip_tags(trim($_POST['message'])));

                if (!empty($nom) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $stmt = $this->pdo->prepare("INSERT INTO messages (nom, email, telephone, subject, message) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$nom, $email, $tel, $objet, $message]);
                        $message_status = '<div class="alert success">Merci ! Votre demande a bien été enregistrée.</div>';
                    } catch (Exception $e) {
                        $message_status = '<div class="alert error">Erreur technique lors de l\'envoi.</div>';
                    }
                } else {
                    $message_status = '<div class="alert error">Veuillez remplir correctement tous les champs.</div>';
                }
            }

            // --- B. TRAITEMENT DU WIZARD (Demande de Devis/Entretien) ---
            if (isset($_POST['appointment_date'])) {
                try {
                    // Vérification de la limite de 6 rendez-vous par lundi
                    $dateRdv = $_POST['appointment_date'];
                    $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM quote_requests WHERE appointment_date = ?");
                    $stmtCount->execute([$dateRdv]);
                    $count = $stmtCount->fetchColumn();

                    if ($count >= 6) {
                        $message_status = '<div class="alert error">Désolé, ce lundi est déjà complet pour les réservations en ligne.</div>';
                    } else {
                        // Récupération et nettoyage des données
                        $is_company = (int)($_POST['is_company'] ?? 0);
                        $firstname = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
                        $lastname = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
                        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                        $zip = htmlspecialchars(strip_tags(trim($_POST['zip'])));
                        $full_date = $dateRdv . ' ' . ($_POST['appointment_time'] ?? '08:00:00');

                        $sql = "INSERT INTO quote_requests (
                            is_company, company_name, vat_number, vat_regime, 
                            firstname, lastname, email, phone, street, zip, city, 
                            device_model, device_serial, device_year, 
                            appointment_date, payment_method, total_price_htva, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'nouveau')";

                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute([
                            $is_company,
                            $_POST['company_name'] ?? null,
                            $_POST['vat_number'] ?? null,
                            $_POST['vat_regime'] ?? null,
                            $firstname,
                            $lastname,
                            $email,
                            $_POST['tel'] ?? null,
                            $_POST['street'] ?? '',
                            $zip,
                            $_POST['city'] ?? '',
                            $_POST['device_model'] ?? null,
                            $_POST['device_serial'] ?? null,
                            $_POST['device_year'] ?? null,
                            $full_date,
                            $_POST['payment_method'] ?? 'intervention',
                            $_POST['total_price_htva'] ?? 0
                        ]);

                        $message_status = '<div class="alert success">Votre rendez-vous a été pré-enregistré avec succès.</div>';
                    }
                } catch (Exception $e) {
                    $message_status = '<div class="alert error">Erreur lors de l\'enregistrement du rendez-vous.</div>';
                }
            }
        }

        // 4. Récupération des données via les Modèles
        try {
            $certifications = Certification::getAll($this->pdo);
            $teamMembers = Team::getAll($this->pdo);
            $services = Service::getAll($this->pdo);
        } catch (Exception $e) {
            $certifications = [];
            $teamMembers = [];
            $services = [];
        }

        // 5. Chargement de la Vue
        require __DIR__ . '/../views/home.php';
    }
}