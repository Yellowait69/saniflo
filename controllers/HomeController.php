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
            // Tente de récupérer les configurations si la table 'settings' existe
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
    // PAGE D'ACCUEIL
    // =================================================================
    public function index() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        $settings = $this->getSettings(); // Récupère les textes/images dynamiques

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
                            // --- COPIE A INFO@SANIFLO.BE ---
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
                        $clientStatus = $_POST['client_status'] ?? 'new'; // new ou existing
                        $device_model = ($clientStatus === 'new') ? ($_POST['device_model'] ?? null) : 'Client Existant';
                        $device_year = ($clientStatus === 'new' && !empty($_POST['device_year'])) ? $_POST['device_year'] : null;
                        $device_kw = ($clientStatus === 'new') ? ($_POST['device_kw'] ?? null) : null;

                        $service = $_POST['service_type'] ?? 'Intervention';
                        $paymentMethod = $_POST['payment_method'] ?? 'stripe';
                        $descUser = $_POST['description'] ?? '';

                        // CALCUL EXACT DE LA TVA (Selon le choix utilisateur)
                        $tauxTVA = 0.21;
                        $vat_regime = 21;
                        if ($is_company) {
                            $vat_regime = isset($_POST['vat_regime']) ? (int)$_POST['vat_regime'] : 21;
                            $tauxTVA = $vat_regime / 100;
                        } else {
                            $vat_regime = isset($_POST['vat_rate']) ? (int)$_POST['vat_rate'] : 21;
                            $tauxTVA = $vat_regime / 100;
                        }

                        $priceHtva = (float)($_POST['total_price_htva'] ?? 0); // On récupère le prix de base calculé en JS
                        $priceTTC = $priceHtva * (1 + $tauxTVA);
                        if ($paymentMethod === 'after') {
                            $priceTTC *= 1.03; // Frais admin 3%
                        }

                        $serviceMap = [
                            'entretien_gaz_viessmann' => 'Entretien Gaz Viessmann',
                            'entretien_mazout_viessmann' => 'Entretien Mazout Viessmann',
                            'entretien_adoucisseur_bwt' => 'Entretien Adoucisseur'
                        ];
                        $serviceLabel = $serviceMap[$service] ?? ucwords(str_replace('_', ' ', $service));

                        // Statuts
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
                            $full_datetime, $paymentMethod, $priceHtva, $descUser, $initialStatus, $initialPaymentStatus, $tokenEdit
                        ]);
                        $lastInsertId = $this->pdo->lastInsertId();

                        // PREPARATION DES DATA POUR L'EMAIL (Communs aux deux méthodes)
                        $emailData = [
                            'prenom' => $prenom, 'nom' => $nom, 'email' => $emailClient, 'tel' => $telephone,
                            'service' => $serviceLabel, 'date' => $dateRdv, 'heure' => $heureRdv,
                            'adresse' => $fullAddress, 'appareil' => "$device_model ($device_year) $device_kw",
                            'paymentMethod' => $paymentMethod, 'totalTTC' => $priceTTC, 'token' => $tokenEdit
                        ];

                        if ($paymentMethod === 'stripe') {
                            // === PAIEMENT EN LIGNE (STRIPE) ===
                            Stripe::setApiKey('sk_test_51SzZKnCHl8KtnRhXbncHLuJeJt8Oye1xLhdhxudVZCtcmOEu3YbkFX09WpIv60Iik4qpKcVghYyOU0Nd1zvqWfee00aruMK55x');
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];

                            $checkout_session = Session::create([
                                'payment_method_types' => ['card'],
                                'line_items' => [[
                                    'price_data' => [
                                        'currency' => 'eur',
                                        'product_data' => ['name' => 'Intervention: ' . $serviceLabel, 'description' => "Date: $dateRdv à $heureRdv"],
                                        'unit_amount' => (int)round($priceTTC * 100),
                                    ],
                                    'quantity' => 1,
                                ]],
                                'mode' => 'payment',
                                'success_url' => "$protocol://$host/public/payment_success.php?session_id={CHECKOUT_SESSION_ID}",
                                'cancel_url' => "$protocol://$host/index.php?page=reservation&msg=cancel",
                            ]);

                            $this->pdo->prepare("UPDATE quote_requests SET stripe_session_id = ? WHERE id = ?")->execute([$checkout_session->id, $lastInsertId]);

                            header("HTTP/1.1 303 See Other");
                            header("Location: " . $checkout_session->url);
                            exit;

                        } else {
                            // === PAIEMENT SUR PLACE / APRÈS INTERVENTION ===
                            $googleDesc = "Client: $prenom $nom\nTél: $telephone\nAdresse: $fullAddress\nAppareil: $device_model\nPaiement: APRÈS INTERVENTION (" . number_format($priceTTC, 2) . "€)\nNote: $descUser";
                            $logic->addEvent(['summary' => "En Ligne: $nom $prenom - $serviceLabel", 'location' => $fullAddress, 'description' => $googleDesc, 'date' => $dateRdv, 'time' => $heureRdv]);

                            // Envoi du mail hyper détaillé + copie Info
                            $this->sendReservationEmail($emailData);

                            $message_status = '<div class="alert success" style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:8px;">Rendez-vous confirmé ! Un email récapitulatif avec le montant vous a été envoyé.</div>';
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
    // NOUVEAU HELPER : ENVOI DE L'EMAIL DE RÉSERVATION COMPLET
    // =================================================================
    private function sendReservationEmail($data) {
        $settings = $this->getSettings();
        $adminCustomText = $settings['email_confirmation'] ?? "Nous vous confirmons que votre intervention a bien été enregistrée dans notre planning.";

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $linkEdit = "$protocol://$host/index.php?page=modifier_rdv&token=" . $data['token'] . "#gerer-intervention";
        $logoUrl = "$protocol://$host/img/logo-saniflo.png";

        $dateFr = date('d/m/Y', strtotime($data['date']));
        $montantFormat = number_format($data['totalTTC'], 2, ',', ' ');

        $paymentInfoHTML = "";
        if ($data['paymentMethod'] === 'after') {
            $paymentInfoHTML = "
            <div style='background: #e8f5e9; padding: 15px; border-radius: 8px; border: 1px solid #a5d6a7; margin-top: 20px; color: #1b5e20;'>
                <h3 style='margin-top:0;'>Montant à régler après l'intervention : $montantFormat € (TVAC)</h3>
                <p style='font-size: 0.9rem; margin-bottom:0;'><i>Ce montant inclut les frais administratifs liés au paiement différé. Le technicien vous fournira les modalités de paiement sur place.</i></p>
            </div>";
        } else {
            $paymentInfoHTML = "<p style='color: #2e7d32; font-weight:bold;'>Intervention payée en ligne ($montantFormat € TVAC).</p>";
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

        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Saniflo SRL <info@saniflo.be>\r\n";
        // --- LA COPIE EST ENVOYÉE ICI AUTOMATIQUEMENT ---
        $headers .= "Bcc: info@saniflo.be\r\n";

        mail($data['email'], $subject, $body, $headers);
    }

    // =================================================================
    // NOUVEAU : CRON JOB (RAPPEL J-2)
    // À exécuter chaque jour à 08:00 via le serveur:
    // wget -qO- https://votre-site.be/index.php?page=cron_reminders
    // =================================================================
    public function cron_reminders() {
        $settings = $this->getSettings();
        $rappelText = $settings['email_reminder'] ?? "Ceci est un rappel pour votre rendez-vous prévu dans deux jours avec Saniflo SRL.";

        // On cherche les rdv prévus dans exactement 2 jours (et non annulés)
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

    // (La méthode modifier_rdv() reste identique à votre version optimisée, je ne la remets pas en entier pour économiser de la place, mais elle ne change pas).
    public function modifier_rdv() { /* Code existant de la modification */ }
}
?>