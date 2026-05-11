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

    public function index() {
        // Initialisation session
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

            // --- B. TRAITEMENT DU WIZARD (Prise de Rendez-vous + Google Agenda + Paiement) ---
            if (isset($_POST['appointment_date'])) {
                try {
                    // Récupération des données
                    $dateRdv = $_POST['appointment_date'];
                    $heureRdv = $_POST['appointment_time'] ?? '';
                    $zip = (int)($_POST['zip'] ?? 0);

                    // --- 1. SÉCURITÉ : On vérifie la dispo via PlanningLogic ---
                    $logic = new PlanningLogic();
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

                    // MISE A JOUR : Valeur par défaut 'stripe' car 'direct' n'existe plus
                    $paymentMethod = $_POST['payment_method'] ?? 'stripe';

                    $priceHtva = $_POST['total_price_htva'] ?? 0;

                    // Transformation du nom du service
                    $serviceMap = [
                        'entretien_gaz_viessmann' => 'Entretien Gaz',
                        'entretien_mazout_viessmann' => 'Entretien Mazout',
                        'entretien_adoucisseur_bwt' => 'Entretien Adoucisseur'
                    ];
                    $serviceLabel = $serviceMap[$service] ?? ucwords(str_replace('_', ' ', $service));

                    // --- 3. INSERTION EN BASE (Statut initial) ---
                    // On insère d'abord pour avoir un ID
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
                        ?, ?, ?, ?, 'nouveau', ?
                    )";

                    // Définir le statut de paiement initial
                    // Si Stripe => 'unpaid' (en attente de validation)
                    // Si Après intervention => 'pending_on_site' (à payer plus tard)
                    $initialPaymentStatus = ($paymentMethod === 'stripe') ? 'unpaid' : 'pending_on_site';

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        $is_company, $_POST['company_name'] ?? null, $_POST['vat_number'] ?? null, $_POST['vat_regime'] ?? null, !empty($_POST['housing_year']) ? $_POST['housing_year'] : null,
                        $prenom, $nom, $emailClient, $telephone,
                        $rue, $_POST['billing_box'] ?? null, $cp, $ville,
                        $worksite_same, $_POST['worksite_name'] ?? null, $_POST['worksite_street'] ?? null, $_POST['worksite_box'] ?? null, $_POST['worksite_zip'] ?? null, $_POST['worksite_city'] ?? null, $_POST['worksite_phone'] ?? null, $_POST['worksite_email'] ?? null,
                        $_POST['device_model'] ?? null, $_POST['device_serial'] ?? null, !empty($_POST['device_year']) ? $_POST['device_year'] : null, $_POST['device_kw'] ?? null,
                        $full_datetime, $paymentMethod, $priceHtva, $descUser, $initialPaymentStatus
                    ]);

                    // Récupération de l'ID du devis créé
                    $lastInsertId = $this->pdo->lastInsertId();

                    // --- 4. BRANCHE CONDITIONNELLE : STRIPE OU AGENDA ---

                    if ($paymentMethod === 'stripe') {
                        // === CAS 1 : PAIEMENT EN LIGNE ===

                        // ! REMPLACEZ PAR VOTRE CLÉ SECRÈTE STRIPE (sk_test_...) !
                        Stripe::setApiKey('sk_test_51SzZKnCHl8KtnRhXbncHLuJeJt8Oye1xLhdhxudVZCtcmOEu3YbkFX09WpIv60Iik4qpKcVghYyOU0Nd1zvqWfee00aruMK55x');

                        // Construction URL absolue
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                        $host = $_SERVER['HTTP_HOST'];
                        $scriptDir = dirname($_SERVER['PHP_SELF']); // /public normalement
                        // Nettoyage si on est dans /public/index.php
                        $baseUrl = $protocol . "://" . $host . $scriptDir;

                        // Création session Stripe
                        $checkout_session = Session::create([
                            'payment_method_types' => ['card'],
                            'line_items' => [[
                                'price_data' => [
                                    'currency' => 'eur',
                                    'product_data' => [
                                        'name' => 'Intervention: ' . $serviceLabel,
                                        'description' => 'Date: ' . $dateRdv . ' à ' . $heureRdv,
                                    ],
                                    'unit_amount' => $priceHtva * 100, // En centimes
                                ],
                                'quantity' => 1,
                            ]],
                            'mode' => 'payment',
                            'success_url' => $baseUrl . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
                            'cancel_url' => $baseUrl . '/index.php?msg=cancel',
                        ]);

                        // Mise à jour DB avec l'ID de session Stripe
                        $updateStmt = $this->pdo->prepare("UPDATE quote_requests SET stripe_session_id = ? WHERE id = ?");
                        $updateStmt->execute([$checkout_session->id, $lastInsertId]);

                        // Redirection vers Stripe
                        header("HTTP/1.1 303 See Other");
                        header("Location: " . $checkout_session->url);
                        exit; // On arrête tout ici, le script payment_success.php gérera l'agenda

                    } else {
                        // === CAS 2 : PAIEMENT APRÈS INTERVENTION ===

                        // Description détaillée Google
                        $googleDesc = "Client: $prenom $nom\n";
                        $googleDesc .= "Service: $serviceLabel\n";
                        $googleDesc .= "Paiement: À RÉGLER SUR PLACE / APRÈS TRAVAUX\n";
                        $googleDesc .= "Tél: $telephone\n";
                        $googleDesc .= "Email: $emailClient\n";
                        $googleDesc .= "Adresse: $fullAddress\n";
                        $googleDesc .= "Note: $descUser\n\n(Ajouté automatiquement depuis le site web)";

                        // Titre clair pour l'agenda
                        $eventSummary = "En Ligne (NON PAYÉ): $nom $prenom - $serviceLabel - $telephone";

                        // Ajout agenda immédiat
                        $logic->addEvent([
                            'summary' => $eventSummary,
                            'location' => $fullAddress,
                            'description' => $googleDesc,
                            'date' => $dateRdv,
                            'time' => $heureRdv
                        ]);

                        $message_status = '<div class="alert success">C\'est confirmé ! Le rendez-vous est ajouté à l\'agenda.</div>';
                    }

                } catch (Exception $e) {
                    $message_status = '<div class="alert error">Erreur : ' . $e->getMessage() . '</div>';
                }
            }
        }

        // 3. Récupération des données pour la vue (inchangé)
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
?>