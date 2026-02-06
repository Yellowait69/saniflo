<?php

class HomeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // 1. Initialisation de la session et Sécurité
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $message_status = '';

        // 2. Traitement des Formulaires (POST)
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
                }
            }

            // --- B. TRAITEMENT DU WIZARD (Prise de Rendez-vous Saniflo) ---
            if (isset($_POST['appointment_date'])) {
                try {
                    // --- SÉCURITÉ SERVEUR : LES RÈGLES DE JEAN-FRANÇOIS ---
                    $dateRdv = $_POST['appointment_date'];
                    $heureRdv = $_POST['appointment_time'] ?? '';
                    $zip = (int)($_POST['zip'] ?? 0);
                    $dayOfWeek = date('N', strtotime($dateRdv)); // 1 = Lundi

                    // 1. Vérification Lundi uniquement
                    if ($dayOfWeek != 1) {
                        throw new Exception("Les rendez-vous ne sont possibles que le lundi.");
                    }

                    // 2. Vérification du quota (Max 6 RDV Web)
                    $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM quote_requests WHERE DATE(appointment_date) = ?");
                    $stmtCount->execute([$dateRdv]);
                    $count = $stmtCount->fetchColumn();

                    if ($count >= 6) {
                        throw new Exception("Désolé, ce lundi est déjà complet (quota web atteint).");
                    }

                    // 3. Vérification Géographique (Heures restreintes 8h/15h30)
                    $zonesRestreintes = (($zip >= 1400 && $zip <= 1499) || ($zip >= 1500 && $zip <= 1970));
                    if ($zonesRestreintes && !in_array($heureRdv, ['08:00', '15:30'])) {
                        throw new Exception("Pour votre zone ($zip), seuls les créneaux de 08:00 ou 15:30 sont disponibles.");
                    }

                    // 4. Blocage Zone 1980+
                    if ($zip >= 1980) {
                        throw new Exception("Intervention uniquement sur demande téléphonique pour cette zone.");
                    }

                    // --- PRÉPARATION DES DONNÉES ---
                    $is_company = (int)($_POST['is_company'] ?? 0);
                    $full_datetime = $dateRdv . ' ' . $heureRdv . ':00';
                    $worksite_same = isset($_POST['worksite_same']) ? 1 : 0;

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
                        // Profil & TVA
                        $is_company,
                        $_POST['company_name'] ?? null,
                        $_POST['vat_number'] ?? null,
                        $_POST['vat_regime'] ?? null,
                        !empty($_POST['housing_year']) ? $_POST['housing_year'] : null,

                        // Contact Principal
                        htmlspecialchars(strip_tags(trim($_POST['firstname']))),
                        htmlspecialchars(strip_tags(trim($_POST['lastname']))),
                        filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
                        $_POST['tel'] ?? null,

                        // Facturation (Noms mappés sur votre HTML)
                        $_POST['billing_street'] ?? '',
                        $_POST['billing_box'] ?? null,
                        $_POST['zip'] ?? '',
                        $_POST['billing_city'] ?? '',

                        // Chantier
                        $worksite_same,
                        $_POST['worksite_name'] ?? null,
                        $_POST['worksite_street'] ?? null,
                        $_POST['worksite_box'] ?? null,
                        $_POST['worksite_zip'] ?? null,
                        $_POST['worksite_city'] ?? null,
                        $_POST['worksite_phone'] ?? null,
                        $_POST['worksite_email'] ?? null,

                        // Appareil
                        $_POST['device_model'] ?? null,
                        $_POST['device_serial'] ?? null,
                        !empty($_POST['device_year']) ? $_POST['device_year'] : null,
                        $_POST['device_kw'] ?? null,

                        // RDV & Prix
                        $full_datetime,
                        $_POST['payment_method'] ?? 'direct',
                        $_POST['total_price_htva'] ?? 0,
                        $_POST['description'] ?? null
                    ]);

                    $message_status = '<div class="alert success">Votre rendez-vous du lundi ' . date('d/m/Y', strtotime($dateRdv)) . ' à ' . $heureRdv . ' a été confirmé avec succès.</div>';

                } catch (Exception $e) {
                    $message_status = '<div class="alert error">' . $e->getMessage() . '</div>';
                }
            }
        }

        // 3. Récupération des données pour la vue
        try {
            $certifications = Certification::getAll($this->pdo);
            $teamMembers = Team::getAll($this->pdo);
            $services = Service::getAll($this->pdo);
            $projects = Project::getAll($this->pdo);
        } catch (Exception $e) {
            $certifications = $teamMembers = $services = $projects = [];
        }

        require __DIR__ . '/../views/home.php';
    }
}