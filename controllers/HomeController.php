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
    // PAGE D'ACCUEIL (Vitrine uniquement)
    // =================================================================
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

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
    // PAGE DE CONTACT DÉDIÉE (AVEC ENVOI D'EMAIL ET LOGO)
    // =================================================================
    public function contact() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $message_status = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur de sécurité. Votre session a expiré ou la requête est invalide. Veuillez rafraîchir la page.</div>';
            } else {
                if (isset($_POST['nom']) && isset($_POST['message']) && !isset($_POST['appointment_date'])) {
                    $nom = htmlspecialchars(strip_tags(trim($_POST['nom'])));
                    $emailClient = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                    $tel = htmlspecialchars(strip_tags(trim($_POST['tel'])));
                    $objetRaw = htmlspecialchars(strip_tags(trim($_POST['objet'] ?? 'info')));
                    $messageContent = htmlspecialchars(strip_tags(trim($_POST['message'])));

                    if (!empty($nom) && !empty($emailClient) && !empty($messageContent) && filter_var($emailClient, FILTER_VALIDATE_EMAIL)) {
                        try {
                            // 1. Enregistrement en base de données
                            $stmt = $this->pdo->prepare("INSERT INTO messages (nom, email, telephone, subject, message) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$nom, $emailClient, $tel, $objetRaw, $messageContent]);

                            // 2. Préparation du libellé de l'objet pour le mail
                            $labels = [
                                'devis' => 'Demande de devis',
                                'entretien' => 'Demande d\'entretien',
                                'info' => 'Demande d\'information',
                                'autre' => 'Autre demande'
                            ];
                            $objetLabel = $labels[$objetRaw] ?? 'Prise de contact';

                            // 3. ENVOI DE L'EMAIL DE CONFIRMATION AU CLIENT
                            $to = $emailClient;
                            $subject = "Accusé de réception - Saniflo SRL";

                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $logoUrl = $protocol . "://" . $host . "/img/logo-saniflo.png";

                            $emailBody = "
                            <html>
                            <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
                                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e0e0e0; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                                    
                                    <div style='text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0;'>
                                        <img src='$logoUrl' alt='Saniflo SRL' style='max-width: 200px; height: auto;'>
                                    </div>

                                    <h2 style='color: #004a99; font-size: 1.4rem;'>Bonjour $nom,</h2>
                                    <p>Nous avons bien reçu votre message concernant : <strong style='color: #004a99;'>$objetLabel</strong>.</p>
                                    <p>Toute l'équipe de <strong>Saniflo SRL</strong> vous remercie pour votre confiance. Nous traiterons votre demande avec la plus grande attention et l'un de nos experts reviendra vers vous très prochainement.</p>
                                    
                                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #ffc107;'>
                                        <p style='margin-top: 0; font-size: 1.1rem; color: #004a99; border-bottom: 1px solid #ddd; padding-bottom: 10px;'><strong>Rappel de votre demande :</strong></p>
                                        <ul style='list-style-type: none; padding-left: 0; font-size: 0.95rem; color: #444;'>
                                            <li style='margin-bottom: 8px;'><strong>Nom :</strong> $nom</li>
                                            <li style='margin-bottom: 8px;'><strong>Email :</strong> $emailClient</li>
                                            <li style='margin-bottom: 8px;'><strong>Téléphone :</strong> $tel</li>
                                            <li style='margin-bottom: 8px;'><strong>Objet :</strong> $objetLabel</li>
                                        </ul>
                                        <p style='font-size: 0.95rem; color: #444; margin-bottom: 5px; margin-top: 15px;'><strong>Votre message :</strong></p>
                                        <div style='font-size: 0.95rem; color: #555; font-style: italic; background: #fff; padding: 15px; border: 1px solid #e0e0e0; border-radius: 6px;'>
                                            " . nl2br($messageContent) . "
                                        </div>
                                    </div>

                                    <p style='margin-top: 30px; font-size: 1rem;'>Cordialement,<br><strong style='color: #004a99;'>L'équipe Saniflo SRL</strong></p>
                                    
                                    <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 0.8rem; color: #999;'>
                                        <p style='margin: 0;'><strong>Saniflo SRL</strong> - Votre expert en Chauffage & Sanitaire</p>
                                        <p style='margin: 5px 0;'>Rue de Fontenelle 15, 1325 Dion-Valmont</p>
                                        <p style='margin: 0;'><a href='$protocol://$host' style='color: #004a99; text-decoration: none;'>www.saniflo.be</a> | 0495 50 17 17</p>
                                    </div>

                                </div>
                            </body>
                            </html>";

                            $headers = "MIME-Version: 1.0" . "\r\n";
                            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                            $headers .= "From: Saniflo SRL <info@saniflo.be>" . "\r\n";
                            $headers .= "Reply-To: info@saniflo.be" . "\r\n";

                            mail($to, $subject, $emailBody, $headers);

                            $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #a5d6a7;">Merci ! Votre demande a été envoyée et un mail de confirmation avec le récapitulatif vous a été adressé.</div>';
                        } catch (Exception $e) {
                            $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur technique lors de l\'envoi.</div>';
                        }
                    }
                }
            }
        }

        try {
            $certifications = Certification::getAll($this->pdo);
        } catch (Exception $e) {
            $certifications = [];
        }

        require __DIR__ . '/../views/contact_page.php';
    }

    // =================================================================
    // LOGIQUE DE MODIFICATION DE RENDEZ-VOUS (NOUVEAU - GESTION AGENDA)
    // =================================================================
    public function modifier_rdv() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $token = $_GET['token'] ?? '';
        $message_status = '';

        if (empty($token)) {
            header("Location: index.php"); exit;
        }

        // 1. Récupération du rendez-vous via le token
        $stmt = $this->pdo->prepare("SELECT * FROM quote_requests WHERE edit_token = ?");
        $stmt->execute([$token]);
        $rdv = $stmt->fetch();

        if (!$rdv) {
            die("Lien invalide ou expiré.");
        }

        // Raccourci pour simplifier l'utilisation du code postal dans la vue
        $rdv['zip'] = $rdv['billing_zip'] ?? '';

        // 2. Vérification de la règle des 7 jours
        $dateRdv = new DateTime($rdv['appointment_date']);
        $maintenant = new DateTime();
        $interval = $maintenant->diff($dateRdv);
        $joursRestants = (int)$interval->format('%r%a');

        $peutModifier = ($joursRestants >= 7);

        // 3. Traitement de la demande de modification (Annulation ou Reprogrammation)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $peutModifier && $rdv['status'] !== 'annulé') {
            $action = $_POST['action'] ?? '';
            $logic = new PlanningLogic($this->pdo);

            // Infos actuelles du rendez-vous
            $currentDate = $dateRdv->format('Y-m-d');
            $currentTime = $dateRdv->format('H:i');
            $clientName = $rdv['lastname'];

            // === OPTION 1 : ANNULATION PURE ===
            if ($action === 'cancel') {
                $eventId = $logic->findEventId($currentDate, $currentTime, $clientName);
                if ($eventId) {
                    $logic->deleteEvent($eventId);
                }

                $stmt = $this->pdo->prepare("UPDATE quote_requests SET status = 'annulé', appointment_date = NULL WHERE id = ?");
                $stmt->execute([$rdv['id']]);

                $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px; border:1px solid #c8e6c9;"><i class="fas fa-check-circle"></i> Votre rendez-vous a bien été annulé et la place a été libérée dans notre agenda.</div>';
                $rdv['status'] = 'annulé';
            }

            // === OPTION 2 : REPROGRAMMATION ===
            elseif ($action === 'reschedule') {
                $newDate = $_POST['new_date'] ?? '';
                $newTime = $_POST['new_time'] ?? '';

                if ($newDate && $newTime) {
                    // Double vérification : le créneau est-il toujours dispo ?
                    $slotsCheck = $logic->getAvailableSlots($newDate, $rdv['zip']);

                    if (!isset($slotsCheck['error']) && in_array($newTime, $slotsCheck['slots'])) {

                        // 1. Supprimer l'ancien de Google Agenda
                        $eventId = $logic->findEventId($currentDate, $currentTime, $clientName);
                        if ($eventId) {
                            $logic->deleteEvent($eventId);
                        }

                        // 2. Créer le nouveau dans Google Agenda
                        $eventSummary = "En Ligne (DÉPLACÉ): {$rdv['lastname']} {$rdv['firstname']} - {$rdv['phone']}";
                        $fullAddress = "{$rdv['billing_street']}, {$rdv['zip']} {$rdv['billing_city']}";
                        $description  = "Client: {$rdv['firstname']} {$rdv['lastname']}\n";
                        $description .= "Tél: {$rdv['phone']}\n";
                        $description .= "Email: {$rdv['email']}\n";
                        $description .= "Adresse: $fullAddress\n";
                        $description .= "Note: RDV DÉPLACÉ PAR LE CLIENT.\n";

                        $logic->addEvent([
                            'summary' => $eventSummary,
                            'location' => $fullAddress,
                            'description' => $description,
                            'date' => $newDate,
                            'time' => $newTime
                        ]);

                        // 3. Mettre à jour la base de données
                        $newFullDateTime = $newDate . ' ' . $newTime . ':00';
                        $stmt = $this->pdo->prepare("UPDATE quote_requests SET appointment_date = ? WHERE id = ?");
                        $stmt->execute([$newFullDateTime, $rdv['id']]);

                        $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px; border:1px solid #c8e6c9;"><i class="fas fa-check-circle"></i> Votre rendez-vous a bien été déplacé au <strong>'.date('d/m/Y', strtotime($newDate)).' à '.$newTime.'</strong>.</div>';

                        // Mise à jour de la variable locale pour la vue
                        $rdv['appointment_date'] = $newFullDateTime;
                    } else {
                        $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; border:1px solid #ef9a9a;"><i class="fas fa-exclamation-circle"></i> Ce créneau n\'est malheureusement plus disponible. Veuillez en choisir un autre.</div>';
                    }
                }
            }
        }

        // --- CORRECTION : Chargement des agréments pour le header/footer ---
        try {
            $certifications = Certification::getAll($this->pdo);
        } catch (Exception $e) {
            $certifications = [];
        }

        require __DIR__ . '/../views/modifier_rdv.php';
    }

    // =================================================================
    // PAGE DE RÉSERVATION (Wizard + Google Agenda + Stripe)
    // =================================================================
    public function reservation() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $message_status = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur de sécurité. Votre session a expiré ou la requête est invalide. Veuillez rafraîchir la page.</div>';
            } else {

                if (isset($_POST['appointment_date'])) {
                    try {
                        // Récupération des données
                        $dateRdv = $_POST['appointment_date'];
                        $heureRdv = $_POST['appointment_time'] ?? '';
                        $zip = (int)($_POST['zip'] ?? 0);

                        // On génère un jeton unique pour permettre la modification ultérieure
                        $tokenEdit = bin2hex(random_bytes(16));

                        // Vérification de la dispo via PlanningLogic
                        $logic = new PlanningLogic($this->pdo);
                        $slotsCheck = $logic->getAvailableSlots($dateRdv, $zip);

                        if (isset($slotsCheck['error'])) {
                            throw new Exception($slotsCheck['error']);
                        }
                        if (!in_array($heureRdv, $slotsCheck['slots'])) {
                            throw new Exception("Attention : Ce créneau ($heureRdv) n'est plus disponible. Veuillez réessayer.");
                        }

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

                        // CALCUL DE LA TVA
                        $priceHtva = (float)($_POST['total_price_htva'] ?? 0);
                        $housingYear = !empty($_POST['housing_year']) ? (int)$_POST['housing_year'] : date('Y');

                        $ageBatiment = date('Y') - $housingYear;
                        $tauxTVA = ($ageBatiment >= 10) ? 0.06 : 0.21;

                        $priceTTC = $priceHtva * (1 + $tauxTVA);

                        // Transformation du nom du service
                        $serviceMap = [
                            'entretien_gaz_viessmann' => 'Entretien Gaz',
                            'entretien_mazout_viessmann' => 'Entretien Mazout',
                            'entretien_adoucisseur_bwt' => 'Entretien Adoucisseur'
                        ];
                        $serviceLabel = $serviceMap[$service] ?? ucwords(str_replace('_', ' ', $service));

                        $initialStatus = ($paymentMethod === 'stripe') ? 'en_attente' : 'nouveau';
                        $initialPaymentStatus = ($paymentMethod === 'stripe') ? 'unpaid' : 'pending_on_site';

                        // INSERTION EN BASE (avec le edit_token)
                        $sql = "INSERT INTO quote_requests (
                            is_company, company_name, vat_number, vat_regime, housing_year,
                            firstname, lastname, email, phone, 
                            billing_street, billing_box, billing_zip, billing_city,
                            worksite_same_as_billing, worksite_name, worksite_street, worksite_box, worksite_zip, worksite_city, worksite_phone, worksite_email,
                            device_model, device_serial, device_year, device_kw,
                            appointment_date, payment_method, total_price_htva, description, status, payment_status, edit_token
                        ) VALUES (
                            ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, ?, ?, ?
                        )";

                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute([
                            $is_company, $_POST['company_name'] ?? null, $_POST['vat_number'] ?? null, $_POST['vat_regime'] ?? null, !empty($_POST['housing_year']) ? $_POST['housing_year'] : null,
                            $prenom, $nom, $emailClient, $telephone,
                            $rue, $_POST['billing_box'] ?? null, $cp, $ville,
                            $worksite_same, $_POST['worksite_name'] ?? null, $_POST['worksite_street'] ?? null, $_POST['worksite_box'] ?? null, $_POST['worksite_zip'] ?? null, $_POST['worksite_city'] ?? null, $_POST['worksite_phone'] ?? null, $_POST['worksite_email'] ?? null,
                            $_POST['device_model'] ?? null, $_POST['device_serial'] ?? null, !empty($_POST['device_year']) ? $_POST['device_year'] : null, $_POST['device_kw'] ?? null,
                            $full_datetime, $paymentMethod, $priceHtva, $descUser, $initialStatus, $initialPaymentStatus, $tokenEdit
                        ]);

                        $lastInsertId = $this->pdo->lastInsertId();

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
                                'success_url' => $publicUrl . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
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

                            // On envoie le mail de confirmation avec le lien de modification
                            $this->sendReservationEmail($emailClient, $prenom, $nom, $serviceLabel, $dateRdv, $heureRdv, $tokenEdit);

                            $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #a5d6a7;">C\'est confirmé ! Le rendez-vous est ajouté à l\'agenda. Un email récapitulatif vous a été envoyé.</div>';
                        }

                    } catch (Exception $e) {
                        $message_status = '<div class="alert error" style="background:#ffebee; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">Erreur : ' . $e->getMessage() . '</div>';
                    }
                }
            }
        }

        try {
            $certifications = Certification::getAll($this->pdo);
        } catch (Exception $e) {
            $certifications = [];
        }

        require __DIR__ . '/../views/reservation.php';
    }

    // =================================================================
    // HELPER : ENVOI DE L'EMAIL DE RÉSERVATION (NOUVEAU)
    // =================================================================
    private function sendReservationEmail($to, $prenom, $nom, $service, $date, $heure, $token) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        // --- CORRECTION : Ajout de l'ancre #gerer-intervention au lien ---
        $link = $protocol . "://" . $host . "/index.php?page=modifier_rdv&token=" . $token . "#gerer-intervention";
        $logoUrl = $protocol . "://" . $host . "/img/logo-saniflo.png";

        $dateFr = date('d/m/Y', strtotime($date));
        $subject = "Confirmation de votre rendez-vous - Saniflo SRL";

        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e0e0e0; padding: 30px; border-radius: 12px;'>
                <div style='text-align: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px;'>
                    <img src='$logoUrl' style='max-width: 200px;'>
                </div>
                
                <h2 style='color: #004a99;'>Confirmation de votre rendez-vous</h2>
                <p>Bonjour $prenom $nom,</p>
                <p>Nous vous confirmons que votre intervention pour : <strong>$service</strong> a bien été enregistrée dans notre planning.</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #004a99;'>
                    <p style='margin: 0; font-size: 1.1rem;'><strong>Date :</strong> Le $dateFr</p>
                    <p style='margin: 5px 0 0 0; font-size: 1.1rem;'><strong>Heure d'arrivée estimée :</strong> $heure</p>
                </div>
                
                <div style='background: #fffdf5; padding: 20px; border-radius: 8px; border: 1px solid #ffe0b2; margin: 30px 0;'>
                    <p style='margin-top: 0; color: #e65100; font-weight: bold;'>Un imprévu ?</p>
                    <p style='font-size: 0.95rem; color: #555;'>Vous avez la possibilité de demander une modification de votre créneau jusqu'à <strong>7 jours avant</strong> la date prévue.</p>
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='$link' style='display: inline-block; background: #ffc107; color: #000; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Modifier mon rendez-vous</a>
                    </div>
                </div>

                <p style='font-size: 0.85rem; color: #777;'>Note : Passé ce délai de 7 jours, toute modification devra se faire impérativement par téléphone au 0495 50 17 17.</p>
                
                <p style='margin-top: 30px;'>L'équipe Saniflo SRL vous remercie pour votre confiance.</p>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Saniflo SRL <info@saniflo.be>\r\n";
        mail($to, $subject, $body, $headers);
    }
}
?>