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

            // --- A. FORMULAIRE DE CONTACT (Simple) ---
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

            // --- B. TRAITEMENT DU WIZARD (Prise de Rendez-vous Complexe) ---
            if (isset($_POST['appointment_date'])) {
                try {
                    // 1. Vérification du quota (Max 6 RDV par Lundi)
                    $dateRdv = $_POST['appointment_date'];
                    $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM quote_requests WHERE appointment_date = ?");
                    $stmtCount->execute([$dateRdv]);
                    $count = $stmtCount->fetchColumn();

                    if ($count >= 6) {
                        $message_status = '<div class="alert error">Désolé, ce lundi est déjà complet (maximum atteint).</div>';
                    } else {
                        // 2. Préparation des données

                        // Données Entreprise / Particulier
                        $is_company = (int)($_POST['is_company'] ?? 0);

                        // Calcul de la date complète (Date + Heure)
                        $full_date = $dateRdv . ' ' . ($_POST['appointment_time'] ?? '08:00:00');

                        // Gestion Chantier (Coché = 1, sinon 0)
                        // Note : dans le HTML, la checkbox s'appelle souvent 'worksite_same'
                        $worksite_same = isset($_POST['worksite_same']) ? 1 : 0;

                        // Requête SQL mise à jour avec les nouveaux champs
                        $sql = "INSERT INTO quote_requests (
                            is_company, company_name, vat_number, vat_regime, housing_year,
                            firstname, lastname, email, phone, 
                            billing_street, billing_box, billing_zip, billing_city,
                            worksite_same_as_billing, worksite_name, worksite_street, worksite_box, worksite_zip, worksite_city, worksite_phone, worksite_email,
                            device_model, device_serial, device_year, device_kw,
                            appointment_date, payment_method, total_price_htva, description, status
                        ) VALUES (
                            ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, 'nouveau'
                        )";

                        $stmt = $this->pdo->prepare($sql);

                        $stmt->execute([
                            // Bloc 1 : Statut
                            $is_company,
                            $_POST['company_name'] ?? null,
                            $_POST['vat_number'] ?? null,
                            $_POST['vat_regime'] ?? null,
                            !empty($_POST['housing_year']) ? $_POST['housing_year'] : null,

                            // Bloc 2 : Contact Principal
                            htmlspecialchars(strip_tags(trim($_POST['firstname']))),
                            htmlspecialchars(strip_tags(trim($_POST['lastname']))),
                            filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
                            $_POST['tel'] ?? null,

                            // Bloc 3 : Facturation (Attention aux noms des inputs HTML correspondants)
                            // Le wizard envoie 'street' -> billing_street, 'zip' -> billing_zip, etc.
                            $_POST['street'] ?? '',      // Input name="street" devient billing_street
                            $_POST['billing_box'] ?? null,
                            $_POST['zip'] ?? '',         // Input name="zip" devient billing_zip
                            $_POST['city'] ?? '',        // Input name="city" devient billing_city

                            // Bloc 4 : Chantier
                            $worksite_same,
                            $_POST['worksite_name'] ?? null,
                            $_POST['worksite_street'] ?? null,
                            $_POST['worksite_box'] ?? null,
                            $_POST['worksite_zip'] ?? null,
                            $_POST['worksite_city'] ?? null,
                            $_POST['worksite_phone'] ?? null,
                            $_POST['worksite_email'] ?? null,

                            // Bloc 5 : Appareil
                            $_POST['device_model'] ?? null,
                            $_POST['device_serial'] ?? null,
                            !empty($_POST['device_year']) ? $_POST['device_year'] : null,
                            $_POST['device_kw'] ?? null,

                            // Bloc 6 : RDV & Paiement
                            $full_date,
                            $_POST['payment_method'] ?? 'intervention',
                            $_POST['total_price_htva'] ?? 0,
                            $_POST['description'] ?? null
                        ]);

                        $message_status = '<div class="alert success">Votre rendez-vous a été confirmé avec succès. Vous recevrez un email de confirmation.</div>';
                    }
                } catch (Exception $e) {
                    // Pour le débogage, vous pouvez afficher $e->getMessage(), mais en prod évitez.
                    $message_status = '<div class="alert error">Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.</div>';
                }
            }
        }

        // 4. Récupération des données via les Modèles (inchangé)
        try {
            $certifications = Certification::getAll($this->pdo);
            $teamMembers = Team::getAll($this->pdo);
            $services = Service::getAll($this->pdo);
            $projects = Project::getAll($this->pdo);
        } catch (Exception $e) {
            $certifications = [];
            $teamMembers = [];
            $services = [];
        }

        // 5. Chargement de la Vue
        require __DIR__ . '/../views/home.php';
    }
}