<?php
// controllers/HomeController.php

// 0. CHARGEMENT DE COMPOSER (OBLIGATOIRE POUR STRIPE)
require_once __DIR__ . '/../vendor/autoload.php';

// 1. IMPORTANT : On inclut le fichier de logique Calendrier
require_once __DIR__ . '/../services/PlanningLogic.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

class HomeController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // =================================================================
    // PAGE D'ACCUEIL (Vitrine + Formulaire de contact classique)
    // =================================================================
    public function index() {
        // Initialisation session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Génération du token CSRF
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $message_status = '';

        // Traitement du Formulaire de Contact (POST)
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // --- VÉRIFICATION DU TOKEN CSRF ---
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur de sécurité. Votre session a expiré ou la requête est invalide. Veuillez rafraîchir la page.</div>';
            } else {
                // --- FORMULAIRE DE CONTACT (Simple) ---
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
                            $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #a5d6a7;">Merci ! Votre demande a bien été enregistrée.</div>';
                        } catch (Exception $e) {
                            $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur technique lors de l\'envoi.</div>';
                        }
                    }
                }
            }
        }

        // Récupération des données pour la vue vitrine
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

    // =================================================================
    // PAGE DE RÉSERVATION (Wizard + Google Agenda + Stripe)
    // =================================================================
    public function reservation() {
        // Initialisation session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Génération du token CSRF
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $message_status = '';

        // Traitement du formulaire Wizard (POST)
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // --- VÉRIFICATION DU TOKEN CSRF ---
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur de sécurité. Votre session a expiré ou la requête est invalide. Veuillez rafraîchir la page.</div>';
            } else {

                // --- TRAITEMENT DU WIZARD ---
                if (isset($_POST['appointment_date'])) {
                    try {
                        // Récupération des données
                        $dateRdv = $_POST['appointment_date'];
                        $heureRdv = $_POST['appointment_time'] ?? '';
                        $zip = (int)($_POST['zip'] ?? 0);

                        // --- 1. SÉCURITÉ : On vérifie la dispo via PlanningLogic ---
                        // On passe $this->pdo à PlanningLogic pour la limite de 15 minutes
                        $logic = new PlanningLogic($this->pdo);
                        $slotsCheck = $logic->getAvailableSlots($dateRdv, $zip);

                        if (isset($slotsCheck['error'])) {
                            throw new Exception($slotsCheck['error']);
                        }
                        if (!in_array($heureRdv, $slotsCheck['slots'])) {
                            throw new Exception("Attention : Ce créneau ($heureRdv) n'est plus disponible. Veuillez réessayer.");
                        }

                        // --- 2. PRÉPARATION DONNÉES INSERTION ---
                        $is_company = (int)($_POST['is_company'] ?? 0);
                        $full_datetime = $dateRdv . ' ' . $heureRdv . ':00';
                        $worksite_same = isset($_POST['worksite_same']) ? 1 : 0;

                        $nom = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
                        $prenom = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
                        $emailClient = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                        $telephone = $_POST['tel'] ?? '';
                        $rue = $_POST['billing_street'] ?? '';
                        $ville = $_POST['billing_city'] ?? '';
                        $cp = $_POST['zip'] ?? '';
                        $fullAddress = "$rue, $cp $ville";
                        $service = $_POST['service_type'] ?? 'Intervention';
                        $descUser = $_POST['description'] ?? '';

                        $paymentMethod = $_POST['payment_method'] ?? 'stripe';

                        // --- CALCUL DE LA TVA ---
                        $priceHtva = (float)($_POST['total_price_htva'] ?? 0);
                        $housingYear = !empty($_POST['housing_year']) ? (int)$_POST['housing_year'] : date('Y');

                        // Si le bâtiment a 10 ans ou plus -> 6%, sinon -> 21%
                        $ageBatiment = date('Y') - $housingYear;
                        $tauxTVA = ($ageBatiment >= 10) ? 0.06 : 0.21;

                        $priceTTC = $priceHtva * (1 + $tauxTVA); // Prix final à envoyer à Stripe

                        // Transformation du nom du service
                        $serviceMap = [
                            'entretien_gaz_viessmann' => 'Entretien Gaz',
                            'entretien_mazout_viessmann' => 'Entretien Mazout',
                            'entretien_adoucisseur_bwt' => 'Entretien Adoucisseur'
                        ];
                        $serviceLabel = $serviceMap[$service] ?? ucwords(str_replace('_', ' ', $service));

                        // --- 3. INSERTION EN BASE ---
                        $initialStatus = ($paymentMethod === 'stripe') ? 'en_attente' : 'nouveau';
                        $initialPaymentStatus = ($paymentMethod === 'stripe') ? 'unpaid' : 'pending_on_site';

                        $sql = "INSERT INTO quote_requests (
                            is_company, company_name, vat_number, vat_regime, housing_year,
                            firstname, lastname, email, phone, 
                            billing_street, billing_box, billing_zip, billing_city,
                            worksite_same_as_billing, worksite_name, worksite_street, worksite_box, worksite_zip, worksite_city, worksite_phone, worksite_email,
                            device_model, device_serial, device_year, device_kw,
                            appointment_date, payment_method, total_price_htva, description, status, payment_status
                        ) VALUES (
                            ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, ?, ?
                        )";

                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute([
                            $is_company, $_POST['company_name'] ?? null, $_POST['vat_number'] ?? null, $_POST['vat_regime'] ?? null, !empty($_POST['housing_year']) ? $_POST['housing_year'] : null,
                            $prenom, $nom, $emailClient, $telephone,
                            $rue, $_POST['billing_box'] ?? null, $cp, $ville,
                            $worksite_same, $_POST['worksite_name'] ?? null, $_POST['worksite_street'] ?? null, $_POST['worksite_box'] ?? null, $_POST['worksite_zip'] ?? null, $_POST['worksite_city'] ?? null, $_POST['worksite_phone'] ?? null, $_POST['worksite_email'] ?? null,
                            $_POST['device_model'] ?? null, $_POST['device_serial'] ?? null, !empty($_POST['device_year']) ? $_POST['device_year'] : null, $_POST['device_kw'] ?? null,
                            $full_datetime, $paymentMethod, $priceHtva, $descUser, $initialStatus, $initialPaymentStatus
                        ]);

                        $lastInsertId = $this->pdo->lastInsertId();

                        // --- 4. BRANCHE CONDITIONNELLE : STRIPE OU AGENDA ---
                        if ($paymentMethod === 'stripe') {
                            // === CAS 1 : PAIEMENT EN LIGNE ===

                            Stripe::setApiKey('sk_test_51SzZKnCHl8KtnRhXbncHLuJeJt8Oye1xLhdhxudVZCtcmOEu3YbkFX09WpIv60Iik4qpKcVghYyOU0Nd1zvqWfee00aruMK55x');

                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];

                            $publicUrl = $protocol . "://" . $host . "/public";
                            $rootUrl = $protocol . "://" . $host;

                            $checkout_session = Session::create([
                                'payment_method_types' => ['card'],
                                'line_items' => [[
                                    'price_data' => [
                                        'currency' => 'eur',
                                        'product_data' => [
                                            'name' => 'Intervention: ' . $serviceLabel,
                                            'description' => 'Date: ' . $dateRdv . ' à ' . $heureRdv . ' (TVA incluse : ' . ($tauxTVA * 100) . '%)',
                                        ],
                                        'unit_amount' => (int)round($priceTTC * 100),
                                    ],
                                    'quantity' => 1,
                                ]],
                                'mode' => 'payment',
                                // En cas de succès
                                'success_url' => $publicUrl . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
                                // En cas d'annulation -> on renvoie sur la page de réservation
                                'cancel_url' => $rootUrl . '/index.php?page=reservation&msg=cancel',
                            ]);

                            $updateStmt = $this->pdo->prepare("UPDATE quote_requests SET stripe_session_id = ? WHERE id = ?");
                            $updateStmt->execute([$checkout_session->id, $lastInsertId]);

                            header("HTTP/1.1 303 See Other");
                            header("Location: " . $checkout_session->url);
                            exit;

                        } else {
                            // === CAS 2 : PAIEMENT APRÈS INTERVENTION ===
                            $googleDesc = "Client: $prenom $nom\n";
                            $googleDesc .= "Service: $serviceLabel\n";
                            $googleDesc .= "Paiement: À RÉGLER SUR PLACE / APRÈS TRAVAUX\n";
                            $googleDesc .= "Tél: $telephone\n";
                            $googleDesc .= "Email: $emailClient\n";
                            $googleDesc .= "Adresse: $fullAddress\n";
                            $googleDesc .= "Note: $descUser\n\n(Ajouté automatiquement depuis le site web)";

                            $eventSummary = "En Ligne (NON PAYÉ): $nom $prenom - $serviceLabel - $telephone";

                            $logic->addEvent([
                                'summary' => $eventSummary,
                                'location' => $fullAddress,
                                'description' => $googleDesc,
                                'date' => $dateRdv,
                                'time' => $heureRdv
                            ]);

                            $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #a5d6a7;">C\'est confirmé ! Le rendez-vous est ajouté à l\'agenda et enregistré.</div>';
                        }

                    } catch (Exception $e) {
                        $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur : ' . $e->getMessage() . '</div>';
                    }
                }
            }
        }

        // --- RÉCUPÉRATION DES CERTIFICATIONS POUR LA PAGE DE RÉSERVATION ---
        try {
            $certifications = Certification::getAll($this->pdo);
        } catch (Exception $e) {
            $certifications = [];
        }

        // On charge la nouvelle vue dédiée au lieu de home.php
        require __DIR__ . '/../views/reservation.php';
    }
}
?>