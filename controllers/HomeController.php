<?php
// controllers/HomeController.php

// 0. CHARGEMENT DE COMPOSER (OBLIGATOIRE POUR STRIPE)
require_once __DIR__ . '/../vendor/autoload.php';

// 1. INCLUSION DE LA LOGIQUE AGENDA
require_once __DIR__ . '/../services/PlanningLogic.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

class HomeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // =================================================================
    // HELPER : RÉCUPÉRER LES PARAMÈTRES DE L'ADMIN (TEXTES/IMAGES)
    // =================================================================
    private function getSettings() {
        $settings = [];
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // Silencieux si la table n'a pas encore été créée en DB
        }
        return $settings;
    }

    // =================================================================
    // NOUVEAU HELPER : RÉCUPÉRER LES TEXTES DU SITE (ACCUEIL)
    // =================================================================
    private function getSiteContent() {
        $content = [];
        try {
            $stmt = $this->pdo->query("SELECT content_key, content_value FROM site_content");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $content[$row['content_key']] = $row['content_value'];
            }
        } catch (Exception $e) {
            // Silencieux si la table n'a pas encore été créée
        }
        return $content;
    }

    // =================================================================
    // PAGE D'ACCUEIL
    // =================================================================
    public function index() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        // Récupération des paramètres et des textes
        $settings = $this->getSettings();
        $site_content = $this->getSiteContent();

        // Initialisation des variables
        $pricingData = [];
        $products = [];
        $productCategories = [];
        $productTypes = [];
        $projectCategories = [];
        $interventionTypes = [];

        try {
            $certifications = Certification::getAll($this->pdo);
            $teamMembers = Team::getAll($this->pdo);
            $services = Service::getAll($this->pdo);
            $projects = Project::getAll($this->pdo);

            $productCategories = $this->pdo->query("SELECT * FROM product_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            $productTypes = $this->pdo->query("SELECT * FROM product_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

            $projectCategories = $this->pdo->query("SELECT * FROM project_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
            $interventionTypes = $this->pdo->query("SELECT * FROM intervention_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les tarifs depuis la table 'pricing' pour dynamiser le Wizard
            $stmt = $this->pdo->query("SELECT service_type, price_htva FROM pricing");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pricingData[$row['service_type']] = $row['price_htva'];
            }

            $sqlProd = "SELECT p.*, 
                               pc.name AS category_name, 
                               pc.slug AS category_slug,
                               pt.name AS type_name,
                               pt.slug AS type_slug
                        FROM products p 
                        LEFT JOIN product_categories pc ON p.category_id = pc.id 
                        LEFT JOIN product_types pt ON p.type_id = pt.id
                        ORDER BY p.display_order ASC, p.id DESC";
            $stmtProd = $this->pdo->query($sqlProd);
            $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $certifications = $teamMembers = $services = $projects = $products = [];
            $productCategories = $projectCategories = $productTypes = $interventionTypes = [];
        }

        require __DIR__ . '/../views/home.php';
    }

    // =================================================================
    // PAGE DE CONTACT DÉDIÉE
    // =================================================================
    public function contact() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        $message_status = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur de sécurité. Veuillez rafraîchir la page.</div>';
            } else {
                if (isset($_POST['nom']) && isset($_POST['message']) && !isset($_POST['appointment_date'])) {
                    $nom = htmlspecialchars(strip_tags(trim($_POST['nom'])));
                    $emailClient = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                    $tel = htmlspecialchars(strip_tags(trim($_POST['tel'])));
                    $objetRaw = htmlspecialchars(strip_tags(trim($_POST['objet'] ?? 'info')));
                    $messageContent = htmlspecialchars(strip_tags(trim($_POST['message'])));

                    if (!empty($nom) && filter_var($emailClient, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $stmt = $this->pdo->prepare("INSERT INTO messages (nom, email, telephone, subject, message) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$nom, $emailClient, $tel, $objetRaw, $messageContent]);

                            $labels = ['devis' => 'Demande de devis', 'entretien' => 'Demande d\'entretien', 'info' => 'Demande d\'information', 'autre' => 'Autre demande'];
                            $objetLabel = $labels[$objetRaw] ?? 'Prise de contact';

                            $to = $emailClient;
                            $subject = "Accusé de réception - Saniflo SRL";
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $logoUrl = "$protocol://$host/img/logo-saniflo.png";

                            $emailBody = "
                            <html>
                            <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; padding: 30px; border-radius: 12px;'>
                                    <div style='text-align: center; margin-bottom: 20px;'><img src='$logoUrl' alt='Saniflo SRL' style='max-width: 200px;'></div>
                                    <h2 style='color: #004a99;'>Bonjour $nom,</h2>
                                    <p>Nous avons bien reçu votre message concernant : <strong>$objetLabel</strong>.</p>
                                    <p>Toute l'équipe vous remercie. Nous traiterons votre demande très prochainement.</p>
                                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px;'>
                                        <strong>Votre message :</strong><br><i>" . nl2br($messageContent) . "</i>
                                    </div>
                                </div>
                            </body>
                            </html>";

                            $headers = "MIME-Version: 1.0\r\n";
                            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                            $headers .= "From: Saniflo SRL <info@saniflo.be>\r\n";
                            $headers .= "Bcc: info@saniflo.be\r\n";

                            mail($to, $subject, $emailBody, $headers);

                            $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px;">Votre demande a été envoyée avec succès.</div>';
                        } catch (Exception $e) {
                            $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px;">Erreur technique.</div>';
                        }
                    }
                }
            }
        }

        $certifications = Certification::getAll($this->pdo);
        require __DIR__ . '/../views/contact_page.php';
    }

    // =================================================================
    // RÉSERVATION DU RENDEZ-VOUS (WIZARD + AGENDA + STRIPE)
    // =================================================================
    public function reservation() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        $message_status = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px;">Erreur de sécurité CSRF.</div>';
            } else {

                // === SAUVEGARDE DES DONNÉES EN SESSION POUR LE BOUTON RETOUR ===
                $_SESSION['reservation_form_data'] = $_POST;

                if (isset($_POST['appointment_date'])) {
                    try {
                        $dateRdv = $_POST['appointment_date'];
                        $heureRdv = $_POST['appointment_time'] ?? '';
                        $zip = (int)($_POST['zip'] ?? 0);
                        $tokenEdit = bin2hex(random_bytes(16));

                        // Vérification Dispo Agenda
                        $logic = new PlanningLogic($this->pdo);
                        $slotsCheck = $logic->getAvailableSlots($dateRdv, $zip);
                        if (isset($slotsCheck['error']) || !in_array($heureRdv, $slotsCheck['slots'])) {
                            throw new Exception("Ce créneau n'est plus disponible.");
                        }

                        // Collecte des données
                        $is_company = (int)($_POST['is_company'] ?? 0);
                        $full_datetime = $dateRdv . ' ' . $heureRdv . ':00';

                        $nom = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
                        $prenom = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
                        $emailClient = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                        $telephone = $_POST['tel'] ?? '';
                        $rue = $_POST['billing_street'] ?? '';
                        $bte = $_POST['billing_box'] ?? '';
                        $ville = $_POST['billing_city'] ?? '';

                        $fullAddress = "$rue " . ($bte ? "Bte $bte" : "") . ", $zip $ville";

                        // Chantier
                        $worksite_same = isset($_POST['worksite_same']) ? 1 : 0;
                        $worksite_street = $_POST['worksite_street'] ?? null;
                        $worksite_box = $_POST['worksite_box'] ?? null;
                        $worksite_zip = $_POST['worksite_zip'] ?? null;
                        $worksite_city = $_POST['worksite_city'] ?? null;

                        // Appareil
                        $clientStatus = $_POST['client_status'] ?? 'new';
                        $device_model = ($clientStatus === 'new') ? ($_POST['device_model'] ?? null) : 'Client Existant';
                        $device_year = ($clientStatus === 'new' && !empty($_POST['device_year'])) ? $_POST['device_year'] : null;
                        $device_kw = ($clientStatus === 'new') ? ($_POST['device_kw'] ?? null) : null;

                        $service = $_POST['service_type'] ?? 'Intervention';
                        $paymentMethod = $_POST['payment_method'] ?? 'stripe';
                        $descUser = $_POST['description'] ?? '';

                        // CALCUL EXACT DE LA TVA
                        $tauxTVA = 0.21;
                        $vat_regime = 21;

                        if ($is_company) {
                            $vat_regime = isset($_POST['vat_rate_company']) ? (int)$_POST['vat_rate_company'] : (isset($_POST['vat_regime']) ? (int)$_POST['vat_regime'] : 21);
                            $tauxTVA = $vat_regime / 100;
                        } else {
                            $vat_regime = isset($_POST['vat_rate_private']) ? (int)$_POST['vat_rate_private'] : (isset($_POST['vat_rate']) ? (int)$_POST['vat_rate'] : 21);
                            $tauxTVA = $vat_regime / 100;
                        }

                        $truePriceHtva = 0;

                        if ($service !== 'devis' && $service !== 'entretien_autre') {
                            $stmtPrice = $this->pdo->prepare("SELECT price_htva FROM pricing WHERE service_type = ?");
                            $stmtPrice->execute([$service]);
                            $priceRow = $stmtPrice->fetch(PDO::FETCH_ASSOC);
                            if ($priceRow) {
                                $truePriceHtva = (float)$priceRow['price_htva'];
                            }
                        }

                        $fraisAdmin = 0;

                        // Calcul sur le vrai prix de base HTVA
                        $montantTVA = $truePriceHtva * $tauxTVA;
                        $sousTotalTTC = $truePriceHtva + $montantTVA;

                        if ($paymentMethod === 'after') {
                            $fraisAdmin = $sousTotalTTC * 0.03;
                        }

                        $priceTTC = $sousTotalTTC + $fraisAdmin;

                        $serviceMap = [
                            'entretien_gaz_viessmann' => 'Entretien Gaz Viessmann',
                            'entretien_mazout_viessmann' => 'Entretien Mazout Viessmann',
                            'entretien_adoucisseur_bwt' => 'Entretien Adoucisseur'
                        ];
                        $serviceLabel = $serviceMap[$service] ?? ucwords(str_replace('_', ' ', $service));

                        $initialStatus = ($paymentMethod === 'stripe') ? 'en_attente' : 'nouveau';
                        $initialPaymentStatus = ($paymentMethod === 'stripe') ? 'unpaid' : 'pending_on_site';

                        // INSERTION BASE DE DONNÉES
                        $sql = "INSERT INTO quote_requests (
                            is_company, company_name, vat_number, vat_regime, 
                            firstname, lastname, email, phone, 
                            billing_street, billing_box, billing_zip, billing_city,
                            worksite_same_as_billing, worksite_street, worksite_box, worksite_zip, worksite_city,
                            device_model, device_year, device_kw,
                            appointment_date, payment_method, total_price_htva, description, status, payment_status, edit_token
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )";
                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute([
                            $is_company, $_POST['company_name'] ?? null, $_POST['vat_number'] ?? null, $vat_regime,
                            $prenom, $nom, $emailClient, $telephone,
                            $rue, $bte, $zip, $ville,
                            $worksite_same, $worksite_street, $worksite_box, $worksite_zip, $worksite_city,
                            $device_model, $device_year, $device_kw,
                            $full_datetime, $paymentMethod, $truePriceHtva, $descUser, $initialStatus, $initialPaymentStatus, $tokenEdit
                        ]);
                        $lastInsertId = $this->pdo->lastInsertId();

                        // PREPARATION DES DATA POUR L'EMAIL
                        $emailData = [
                            'prenom' => $prenom, 'nom' => $nom, 'email' => $emailClient, 'tel' => $telephone,
                            'service' => $serviceLabel, 'date' => $dateRdv, 'heure' => $heureRdv,
                            'adresse' => $fullAddress, 'appareil' => "$device_model ($device_year) $device_kw",
                            'paymentMethod' => $paymentMethod,
                            'truePriceHtva' => $truePriceHtva,
                            'montantTVA' => $montantTVA,
                            'fraisAdmin' => $fraisAdmin,
                            'vatRate' => $vat_regime,
                            'totalTTC' => $priceTTC,
                            'descUser' => $descUser,
                            'token' => $tokenEdit
                        ];

                        if ($paymentMethod === 'stripe' && $priceTTC > 0) {
                            Stripe::setApiKey('sk_test_51SzZKnCHl8KtnRhXbncHLuJeJt8Oye1xLhdhxudVZCtcmOEu3YbkFX09WpIv60Iik4qpKcVghYyOU0Nd1zvqWfee00aruMK55x');

                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $logoUrl = "$protocol://$host/img/logo-saniflo.png";

                            $checkout_session = Session::create([
                                'payment_method_types' => ['card', 'bancontact'],
                                'customer_email' => $emailClient,
                                'line_items' => [[
                                    'price_data' => [
                                        'currency' => 'eur',
                                        'product_data' => [
                                            'name' => 'Intervention : ' . $serviceLabel,
                                            'description' => "Le " . date('d/m/Y', strtotime($dateRdv)) . " à $heureRdv chez $prenom $nom.",
                                            'images' => [$logoUrl]
                                        ],
                                        'unit_amount' => (int)round($priceTTC * 100),
                                    ],
                                    'quantity' => 1,
                                ]],
                                'mode' => 'payment',
                                'expires_at' => time() + 1860, // === EXPIRATION EXACTE DANS 31 MINUTES ===
                                'locale' => 'fr',
                                'success_url' => "$protocol://$host/public/payment_success.php?session_id={CHECKOUT_SESSION_ID}",
                                'cancel_url' => "$protocol://$host/index.php?page=reservation&msg=cancel",
                            ]);

                            $this->pdo->prepare("UPDATE quote_requests SET stripe_session_id = ? WHERE id = ?")->execute([$checkout_session->id, $lastInsertId]);

                            header("HTTP/1.1 303 See Other");
                            header("Location: " . $checkout_session->url);
                            exit;

                        } else {
                            // === PAIEMENT SUR PLACE / APRÈS INTERVENTION / DEVIS GRATUIT ===

                            // Validation réussie, on vide la session pour ne plus pré-remplir
                            unset($_SESSION['reservation_form_data']);

                            $chantierStr = $worksite_same ? "Identique à la facturation" : "$worksite_street " . ($worksite_box ? "Bte $worksite_box" : "") . ", $worksite_zip $worksite_city";
                            $gpsLocation = $worksite_same ? $fullAddress : $chantierStr;
                            $companyStr = $is_company ? "🏢 Société: " . ($_POST['company_name'] ?? 'N/A') . " (TVA: " . ($_POST['vat_number'] ?? 'N/A') . ")\n" : "";

                            $googleDesc = "🛠️ DÉTAILS DE L'INTERVENTION\n";
                            $googleDesc .= "------------------------------------------------\n";
                            $googleDesc .= "Service : $serviceLabel\n";
                            $googleDesc .= "Appareil : $device_model" . ($device_year ? " (Année: $device_year)" : "") . ($device_kw ? " - $device_kw kW" : "") . "\n";
                            $googleDesc .= "Remarques client : " . ($descUser ?: "Aucune remarque") . "\n\n";

                            $googleDesc .= "👤 COORDONNÉES CLIENT\n";
                            $googleDesc .= "------------------------------------------------\n";
                            $googleDesc .= "Nom : $prenom $nom\n";
                            $googleDesc .= $companyStr;
                            $googleDesc .= "Email : $emailClient\n";
                            $googleDesc .= "Téléphone : $telephone\n";
                            $googleDesc .= "Facturation : $fullAddress\n";
                            if (!$worksite_same) {
                                $googleDesc .= "Chantier : $chantierStr\n";
                            }
                            $googleDesc .= "\n";

                            $googleDesc .= "💳 PAIEMENT ET TARIFICATION\n";
                            $googleDesc .= "------------------------------------------------\n";
                            if ($truePriceHtva > 0) {
                                $googleDesc .= "Statut : ⚠️ À RÉGLER SUR PLACE (Bancontact / Cash)\n";
                                $googleDesc .= "Prix HTVA : " . number_format($truePriceHtva, 2, ',', ' ') . " €\n";
                                $googleDesc .= "TVA ($vat_regime%) : " . number_format($montantTVA, 2, ',', ' ') . " €\n";
                                if ($fraisAdmin > 0) {
                                    $googleDesc .= "Frais admin (3%) : " . number_format($fraisAdmin, 2, ',', ' ') . " €\n";
                                }
                                $googleDesc .= "TOTAL À PAYER : " . number_format($priceTTC, 2, ',', ' ') . " €\n";
                            } else {
                                $googleDesc .= "Statut : DEVIS / SUR PLACE\n";
                            }

                            $logic->addEvent([
                                'summary' => "🔧 $serviceLabel - $nom $prenom",
                                'location' => $gpsLocation,
                                'description' => $googleDesc,
                                'date' => $dateRdv,
                                'time' => $heureRdv
                            ]);

                            $this->sendReservationEmail($emailData);

                            $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px;">Rendez-vous confirmé ! Un email récapitulatif détaillé vous a été envoyé.</div>';
                        }

                    } catch (Exception $e) {
                        $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px;">Erreur : ' . $e->getMessage() . '</div>';
                    }
                }
            }
        }

        $certifications = Certification::getAll($this->pdo);
        require __DIR__ . '/../views/reservation.php';
    }

    // =================================================================
    // CRON : GESTION DES ABANDONS STRIPE (5 mins & 30 mins)
    // =================================================================
    public function cron_abandoned_checkouts() {
        Stripe::setApiKey('sk_test_51SzZKnCHl8KtnRhXbncHLuJeJt8Oye1xLhdhxudVZCtcmOEu3YbkFX09WpIv60Iik4qpKcVghYyOU0Nd1zvqWfee00aruMK55x');
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $logoUrl = "$protocol://$host/img/logo-saniflo.png";

        // 1. RELANCE APRÈS 5 MINUTES
        $stmt5m = $this->pdo->prepare("
            SELECT * FROM quote_requests 
            WHERE payment_method = 'stripe' AND payment_status = 'unpaid' AND status = 'en_attente' AND reminder_sent = 0 
            AND created_at <= DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt5m->execute();
        $toRemind = $stmt5m->fetchAll(PDO::FETCH_ASSOC);

        foreach($toRemind as $rdv) {
            try {
                $session = Session::retrieve($rdv['stripe_session_id']);
                if ($session->payment_status === 'unpaid' && $session->status === 'open') {

                    // Envoi du mail
                    $to = $rdv['email'];
                    $subject = "Votre rendez-vous Saniflo est en attente de paiement";
                    $checkoutUrl = $session->url;
                    $dateRdv = date('d/m/Y', strtotime($rdv['appointment_date']));
                    $heureRdv = date('H:i', strtotime($rdv['appointment_date']));

                    $body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; padding: 20px;'>
                        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #ddd;'>
                            <div style='text-align: center; margin-bottom: 20px;'><img src='$logoUrl' style='max-width: 180px;'></div>
                            <h2 style='color: #004a99;'>Bonjour {$rdv['firstname']},</h2>
                            <p>Vous avez commencé à réserver une intervention pour le <strong>$dateRdv à $heureRdv</strong>, mais le processus de paiement n'a pas été finalisé.</p>
                            <p style='color:#e65100; font-weight:bold;'>Ce créneau vous est réservé pour encore 25 minutes !</p>
                            <p>Pour confirmer votre rendez-vous, veuillez finaliser votre paiement sécurisé en cliquant sur le bouton ci-dessous :</p>
                            <div style='text-align:center; margin: 30px 0;'>
                                <a href='$checkoutUrl' style='display:inline-block; background: #28a745; color: white; padding: 14px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size:1.1rem;'>Finaliser mon paiement</a>
                            </div>
                        </div>
                    </body>
                    </html>";

                    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Saniflo SRL <info@saniflo.be>\r\n";
                    mail($to, $subject, $body, $headers);

                    // Mettre à jour la BDD pour ne pas renvoyer le mail
                    $this->pdo->prepare("UPDATE quote_requests SET reminder_sent = 1 WHERE id = ?")->execute([$rdv['id']]);
                }
            } catch(Exception $e) {}
        }

        // 2. ANNULATION APRÈS 30 MINUTES (LIBÉRATION DU CRÉNEAU)
        $stmt30m = $this->pdo->prepare("
            SELECT id, stripe_session_id FROM quote_requests 
            WHERE payment_method = 'stripe' AND payment_status = 'unpaid' AND status = 'en_attente' 
            AND created_at <= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        $stmt30m->execute();
        $toCancel = $stmt30m->fetchAll(PDO::FETCH_ASSOC);

        foreach($toCancel as $rdv) {
            try {
                $session = Session::retrieve($rdv['stripe_session_id']);
                if ($session->status === 'open') {
                    $session->expire(); // Annule de force la session Stripe
                }
            } catch(Exception $e) {}

            // On marque en annulé. Le créneau redevient instantanément libre !
            $this->pdo->prepare("UPDATE quote_requests SET status = 'annulé', payment_status = 'cancelled' WHERE id = ?")->execute([$rdv['id']]);
        }
    }

    // =================================================================
    // HELPER : ENVOI DE L'EMAIL DE RÉSERVATION COMPLET ET DÉTAILLÉ
    // =================================================================
    private function sendReservationEmail($data) {
        $settings = $this->getSettings();
        $adminCustomText = $settings['email_confirmation'] ?? "Nous vous confirmons que votre intervention a bien été enregistrée dans notre planning.";

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $linkEdit = "$protocol://$host/index.php?page=modifier_rdv&token=" . $data['token'] . "#gerer-intervention";
        $logoUrl = "$protocol://$host/img/logo-saniflo.png";

        $dateFr = date('d/m/Y', strtotime($data['date']));

        $montantHTVAFormat = number_format($data['truePriceHtva'], 2, ',', ' ');
        $montantTVAFormat = number_format($data['montantTVA'], 2, ',', ' ');
        $fraisAdminFormat = number_format($data['fraisAdmin'], 2, ',', ' ');
        $montantTTCFormat = number_format($data['totalTTC'], 2, ',', ' ');

        $descriptionHTML = "";
        if (!empty($data['descUser'])) {
            $descriptionHTML = "<li style='margin-bottom:8px; margin-top:15px; padding-top:15px; border-top: 1px solid #ddd;'><strong>Vos remarques / description de la demande :</strong><br><i style='color:#555; display:block; margin-top:5px;'>" . nl2br(htmlspecialchars($data['descUser'])) . "</i></li>";
        }

        $paymentInfoHTML = "";
        if ($data['truePriceHtva'] > 0) {
            if ($data['paymentMethod'] === 'after') {
                $paymentInfoHTML = "
                <div style='background: #e8f5e9; padding: 20px; border-radius: 8px; border: 1px solid #a5d6a7; margin-top: 20px; color: #1b5e20;'>
                    <h3 style='margin-top:0; margin-bottom: 15px; font-size: 1.1rem; border-bottom: 1px solid #a5d6a7; padding-bottom: 8px;'>Détails de la tarification</h3>
                    <table style='width: 100%; border-collapse: collapse; font-size: 0.95rem;'>
                        <tr><td style='padding: 6px 0;'>Montant HTVA :</td><td style='text-align: right;'>$montantHTVAFormat €</td></tr>
                        <tr><td style='padding: 6px 0;'>TVA ({$data['vatRate']}%) :</td><td style='text-align: right;'>$montantTVAFormat €</td></tr>
                        <tr><td style='padding: 6px 0;'>Frais administratifs de facturation (3%) :</td><td style='text-align: right;'>$fraisAdminFormat €</td></tr>
                        <tr><td style='padding: 12px 0 0 0; font-weight: bold; font-size: 1.2rem; border-top: 1px solid #a5d6a7; margin-top: 6px;'>Total à régler sur place :</td><td style='text-align: right; font-weight: bold; font-size: 1.2rem; border-top: 1px solid #a5d6a7; padding-top: 12px;'>$montantTTCFormat €</td></tr>
                    </table>
                </div>";
            } else {
                $paymentInfoHTML = "
                <div style='background: #e8f5e9; padding: 20px; border-radius: 8px; border: 1px solid #a5d6a7; margin-top: 20px; color: #1b5e20;'>
                    <h3 style='margin-top:0; margin-bottom: 15px; font-size: 1.1rem; border-bottom: 1px solid #a5d6a7; padding-bottom: 8px;'>Détails de la tarification</h3>
                    <table style='width: 100%; border-collapse: collapse; font-size: 0.95rem;'>
                        <tr><td style='padding: 6px 0;'>Montant HTVA :</td><td style='text-align: right;'>$montantHTVAFormat €</td></tr>
                        <tr><td style='padding: 6px 0;'>TVA ({$data['vatRate']}%) :</td><td style='text-align: right;'>$montantTVAFormat €</td></tr>
                        <tr><td style='padding: 12px 0 0 0; font-weight: bold; font-size: 1.2rem; border-top: 1px solid #a5d6a7; margin-top: 6px;'>Total payé en ligne :</td><td style='text-align: right; font-weight: bold; font-size: 1.2rem; border-top: 1px solid #a5d6a7; padding-top: 12px;'>$montantTTCFormat €</td></tr>
                    </table>
                </div>";
            }
        }

        $subject = "Confirmation de votre rendez-vous - Saniflo SRL";
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #ddd;'>
                <div style='text-align: center; margin-bottom: 20px;'><img src='$logoUrl' style='max-width: 180px;'></div>
                <h2 style='color: #004a99;'>Bonjour {$data['prenom']},</h2>
                <p>" . nl2br(htmlspecialchars($adminCustomText)) . "</p>
                <div style='background-color: #f4f7f6; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #004a99;'>
                    <h3 style='margin-top:0; color:#004a99;'>Récapitulatif de l'intervention</h3>
                    <ul style='list-style-type: none; padding-left: 0;'>
                        <li style='margin-bottom:8px;'><strong>Service :</strong> {$data['service']}</li>
                        <li style='margin-bottom:8px;'><strong>Date :</strong> Le $dateFr à {$data['heure']}</li>
                        <li style='margin-bottom:8px;'><strong>Adresse :</strong> {$data['adresse']}</li>
                        <li style='margin-bottom:8px;'><strong>Appareil :</strong> {$data['appareil']}</li>
                        <li style='margin-bottom:8px;'><strong>Contact :</strong> {$data['tel']}</li>
                        $descriptionHTML
                    </ul>
                </div>
                $paymentInfoHTML
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #e65100; font-weight: bold;'>Un imprévu ?</p>
                    <p style='font-size: 0.95rem;'>Vous pouvez modifier ce rendez-vous jusqu'à <strong>7 jours avant</strong> la date prévue.</p>
                    <a href='$linkEdit' style='display: inline-block; background: #ffc107; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Gérer mon rendez-vous</a>
                </div>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Saniflo SRL <info@saniflo.be>\r\nBcc: info@saniflo.be\r\n";
        mail($data['email'], $subject, $body, $headers);
    }

    // =================================================================
    // CRON JOB (RAPPEL J-2)
    // =================================================================
    public function cron_reminders() {
        $settings = $this->getSettings();
        $rappelText = $settings['email_reminder'] ?? "Ceci est un rappel pour votre rendez-vous prévu dans deux jours avec Saniflo SRL.";

        $targetDate = date('Y-m-d', strtotime('+2 days'));
        $stmt = $this->pdo->prepare("SELECT * FROM quote_requests WHERE DATE(appointment_date) = ? AND status != 'annulé'");
        $stmt->execute([$targetDate]);
        $rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $logoUrl = "$protocol://$host/img/logo-saniflo.png";

        foreach ($rendezvous as $rdv) {
            $to = $rdv['email'];
            $subject = "Rappel : Votre entretien Saniflo est prévu dans 2 jours !";
            $heure = date('H:i', strtotime($rdv['appointment_date']));
            $linkEdit = "$protocol://$host/index.php?page=modifier_rdv&token=" . $rdv['edit_token'];

            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; padding: 30px; border-radius: 12px;'>
                    <div style='text-align: center; margin-bottom: 20px;'><img src='$logoUrl' style='max-width: 150px;'></div>
                    <h2 style='color: #004a99;'>Bonjour {$rdv['firstname']},</h2>
                    <p>" . nl2br(htmlspecialchars($rappelText)) . "</p>
                    <div style='background-color: #fff3e0; padding: 20px; border-radius: 8px; border-left: 4px solid #ff9800;'>
                        <h3 style='margin: 0 0 10px 0;'>Votre rendez-vous</h3>
                        <p><strong>Date :</strong> Le " . date('d/m/Y', strtotime($targetDate)) . " à $heure</p>
                        <p><strong>Adresse :</strong> {$rdv['billing_street']}, {$rdv['billing_zip']} {$rdv['billing_city']}</p>
                    </div>
                    <p style='font-size: 0.85rem; color:#666; margin-top:20px;'>Si vous devez annuler, veuillez nous appeler d'urgence au 0495 50 17 17 ou <a href='$linkEdit'>cliquer ici</a>.</p>
                </div>
            </body>
            </html>";

            $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Saniflo SRL <info@saniflo.be>\r\n";
            mail($to, $subject, $body, $headers);
            $count++;
        }

        echo "Tâche Cron exécutée : $count rappel(s) envoyé(s).";
    }

    // =================================================================
    // GESTION DES MODIFICATIONS / ANNULATIONS DE RENDEZ-VOUS
    // =================================================================
    public function modifier_rdv() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $message_status = '';
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header("Location: index.php");
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM quote_requests WHERE edit_token = ?");
        $stmt->execute([$token]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rdv) {
            $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px;">Lien invalide ou expiré.</div>';
            require __DIR__ . '/../views/modifier_rdv.php';
            return;
        }

        $now = time();
        $rdvTime = strtotime($rdv['appointment_date']);
        $peutModifier = (($rdvTime - $now) >= (7 * 24 * 60 * 60));

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $peutModifier && $rdv['status'] !== 'annulé') {
            $action = $_POST['action'] ?? '';
            $logic = new PlanningLogic($this->pdo);

            $dateOnly = date('Y-m-d', $rdvTime);
            $timeOnly = date('H:i', $rdvTime);
            $clientName = $rdv['lastname'];

            if ($action === 'cancel') {
                $eventId = $logic->findEventId($dateOnly, $timeOnly, $clientName);
                if ($eventId) {
                    $logic->deleteEvent($eventId);
                }

                $stmt = $this->pdo->prepare("UPDATE quote_requests SET status = 'annulé' WHERE id = ?");
                $stmt->execute([$rdv['id']]);
                $rdv['status'] = 'annulé';

                $message_status = '<div class="alert success" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px;">Votre rendez-vous a été annulé avec succès.</div>';

                $this->sendNotificationEmail($rdv, 'Annulation');

            } elseif ($action === 'reschedule') {
                $newDate = $_POST['new_date'] ?? '';
                $newTime = $_POST['new_time'] ?? '';

                if (!empty($newDate) && !empty($newTime)) {
                    $zip = $rdv['worksite_zip'] ?: $rdv['billing_zip'];

                    $slotsCheck = $logic->getAvailableSlots($newDate, $zip);

                    if (isset($slotsCheck['error']) || !in_array($newTime, $slotsCheck['slots'])) {
                        $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px;">Ce créneau n\'est plus disponible. Veuillez en choisir un autre.</div>';
                    } else {
                        // On supprime l'ancien event Google
                        $eventId = $logic->findEventId($dateOnly, $timeOnly, $clientName);
                        if ($eventId) {
                            $logic->deleteEvent($eventId);
                        }

                        // --- RECONSTRUCTION DÉTAILLÉE DE L'ÉVÉNEMENT POUR LA NOUVELLE DATE ---
                        $bte = !empty($rdv['billing_box']) ? "Bte " . $rdv['billing_box'] : "";
                        $fullAddress = "{$rdv['billing_street']} $bte, {$rdv['billing_zip']} {$rdv['billing_city']}";

                        $chantierSame = $rdv['worksite_same_as_billing'];
                        $chantierStr = $chantierSame ? "Identique" : "{$rdv['worksite_street']} {$rdv['worksite_box']}, {$rdv['worksite_zip']} {$rdv['worksite_city']}";
                        $companyStr = $rdv['is_company'] ? "🏢 Société: {$rdv['company_name']} (TVA: {$rdv['vat_number']})\n" : "";

                        $googleDesc = "🔄 INTERVENTION MODIFIÉE EN LIGNE\n\n";

                        $googleDesc .= "🛠️ DÉTAILS DE L'INTERVENTION\n";
                        $googleDesc .= "------------------------------------------------\n";
                        $googleDesc .= "Appareil : {$rdv['device_model']} (Année: {$rdv['device_year']} - {$rdv['device_kw']}kW)\n";
                        $googleDesc .= "Remarques : " . ($rdv['description'] ?: "Aucune") . "\n\n";

                        $googleDesc .= "👤 COORDONNÉES CLIENT\n";
                        $googleDesc .= "------------------------------------------------\n";
                        $googleDesc .= "Nom : {$rdv['firstname']} {$rdv['lastname']}\n";
                        $googleDesc .= $companyStr;
                        $googleDesc .= "Email : {$rdv['email']}\n";
                        $googleDesc .= "Téléphone : {$rdv['phone']}\n";
                        $googleDesc .= "Adresse de chantier : " . ($chantierSame ? $fullAddress : $chantierStr) . "\n\n";

                        $googleDesc .= "💳 PAIEMENT\n";
                        $googleDesc .= "------------------------------------------------\n";
                        $methodePaiementTexte = ($rdv['payment_method'] === 'stripe') ? "✅ DÉJÀ PAYÉ EN LIGNE" : "⚠️ À RÉGLER SUR PLACE";
                        $googleDesc .= "Statut : $methodePaiementTexte\n";

                        // Paramétrage de la localisation pour le GPS
                        $locationEvent = $chantierSame ? $fullAddress : $chantierStr;

                        // Ajout du nouvel event
                        $logic->addEvent([
                            'summary' => "🔄 MODIFIÉ: {$rdv['lastname']} {$rdv['firstname']}",
                            'location' => $locationEvent,
                            'description' => $googleDesc,
                            'date' => $newDate,
                            'time' => $newTime
                        ]);

                        $newDateTime = $newDate . ' ' . $newTime . ':00';
                        $stmt = $this->pdo->prepare("UPDATE quote_requests SET appointment_date = ? WHERE id = ?");
                        $stmt->execute([$newDateTime, $rdv['id']]);

                        $rdv['appointment_date'] = $newDateTime;

                        $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px; margin-bottom:20px;">Votre rendez-vous a été déplacé avec succès au ' . date('d/m/Y', strtotime($newDate)) . ' à ' . $newTime . '.</div>';

                        $this->sendNotificationEmail($rdv, 'Modification', $newDate, $newTime);
                    }
                }
            }
        }

        require __DIR__ . '/../views/modifier_rdv.php';
    }

    // =================================================================
    // HELPER : ENVOI EMAIL SUITE À UNE MODIFICATION OU ANNULATION
    // =================================================================
    private function sendNotificationEmail($rdv, $type, $newDate = null, $newTime = null) {
        $to = $rdv['email'];
        $subject = ($type === 'Annulation') ? "Annulation de votre rendez-vous - Saniflo SRL" : "Modification de votre rendez-vous - Saniflo SRL";

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $logoUrl = "$protocol://$host/img/logo-saniflo.png";

        if ($type === 'Annulation') {
            $content = "<p>Nous vous confirmons l'annulation de votre intervention initialement prévue le <strong>" . date('d/m/Y', strtotime($rdv['appointment_date'])) . "</strong>.</p>";
        } else {
            $content = "<p>Votre intervention a bien été reprogrammée. Le technicien se présentera le <strong>" . date('d/m/Y', strtotime($newDate)) . " à $newTime</strong>.</p>";
        }

        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #ddd; padding: 30px; border-radius: 12px;'>
                <div style='text-align: center; margin-bottom: 20px;'><img src='$logoUrl' style='max-width: 150px;'></div>
                <h2 style='color: #004a99;'>Bonjour {$rdv['firstname']},</h2>
                $content
                <p style='margin-top:20px; font-size: 0.9rem; color:#666;'>Pour toute question ou urgence, n'hésitez pas à nous contacter au <strong>0495 50 17 17</strong>.</p>
                <p>L'équipe Saniflo SRL</p>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: Saniflo SRL <info@saniflo.be>\r\n";
        $headers .= "Bcc: info@saniflo.be\r\n";

        mail($to, $subject, $body, $headers);
    }
}
?>