<?php
// public/payment_success.php

// 1. Chargement des dépendances
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../services/PlanningLogic.php'; // Pour Google Agenda

// 2. Connexion à la Base de Données
$pdo = require_once __DIR__ . '/../config/db.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

// ==============================================================================
// CONFIGURATION STRIPE
// ==============================================================================
Stripe::setApiKey('sk_test_51SzZKnCHl8KtnRhXbncHLuJeJt8Oye1xLhdhxudVZCtcmOEu3YbkFX09WpIv60Iik4qpKcVghYyOU0Nd1zvqWfee00aruMK55x');

// Construction de l'URL de base dynamique
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$publicUrl = $protocol . "://" . $host . "/public";
$rootUrl = $protocol . "://" . $host;

$session_id = $_GET['session_id'] ?? null;

if ($session_id) {
    try {
        // 3. Récupération de la session Stripe
        $session = Session::retrieve($session_id);

        if ($session->payment_status === 'paid') {

            // 4. Recherche du rendez-vous correspondant
            $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE stripe_session_id = ?");
            $stmt->execute([$session_id]);
            $rdv = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($rdv && $rdv['payment_status'] === 'unpaid') {

                // --- SÉCURITÉ : Vérification et génération du Token de modification ---
                $token = $rdv['edit_token'];
                if (empty($token)) {
                    $token = bin2hex(random_bytes(32)); // Génère un token si manquant
                }

                // --- A. Mise à jour du statut en base ---
                $updateSql = "UPDATE quote_requests SET payment_status = 'paid', status = 'nouveau', edit_token = ? WHERE id = ?";
                $pdo->prepare($updateSql)->execute([$token, $rdv['id']]);

                // --- EXTRACTION ET CALCUL DES DONNÉES ---
                $nom = $rdv['lastname'];
                $prenom = $rdv['firstname'];
                $emailClient = $rdv['email'];
                $telephone = $rdv['phone'];
                $is_company = $rdv['is_company'];

                $bte = !empty($rdv['billing_box']) ? "Bte " . $rdv['billing_box'] : "";
                $fullAddress = "{$rdv['billing_street']} $bte, {$rdv['billing_zip']} {$rdv['billing_city']}";

                $chantierSame = $rdv['worksite_same_as_billing'];
                $chantierBte = !empty($rdv['worksite_box']) ? "Bte " . $rdv['worksite_box'] : "";
                $chantierStr = $chantierSame ? "Identique à la facturation" : "{$rdv['worksite_street']} $chantierBte, {$rdv['worksite_zip']} {$rdv['worksite_city']}";
                $gpsLocation = $chantierSame ? $fullAddress : $chantierStr;

                $device_model = $rdv['device_model'];
                $device_year = $rdv['device_year'];
                $device_kw = $rdv['device_kw'];
                $descUser = $rdv['description'] ?? '';

                $timestamp = strtotime($rdv['appointment_date']);
                $dateOnly = date('Y-m-d', $timestamp);
                $timeOnly = date('H:i', $timestamp);
                $dateFr = date('d/m/Y', $timestamp);

                $truePriceHtva = (float)$rdv['total_price_htva'];
                $vat_regime = (int)$rdv['vat_regime'];
                $montantTVA = $truePriceHtva * ($vat_regime / 100);
                $totalTTC = $truePriceHtva + $montantTVA;

                // --- B. AJOUT À GOOGLE AGENDA (FORMAT DÉTAILLÉ) ---
                $logic = new PlanningLogic($pdo);

                $companyStr = $is_company ? "🏢 Société: {$rdv['company_name']} (TVA: {$rdv['vat_number']})\n" : "";

                $googleDesc = "🛠️ DÉTAILS DE L'INTERVENTION\n";
                $googleDesc .= "------------------------------------------------\n";
                $googleDesc .= "Appareil : $device_model" . ($device_year ? " (Année: $device_year)" : "") . ($device_kw ? " - $device_kw kW" : "") . "\n";
                $googleDesc .= "Remarques client : " . ($descUser ?: "Aucune remarque") . "\n\n";

                $googleDesc .= "👤 COORDONNÉES CLIENT\n";
                $googleDesc .= "------------------------------------------------\n";
                $googleDesc .= "Nom : $prenom $nom\n";
                $googleDesc .= $companyStr;
                $googleDesc .= "Email : $emailClient\n";
                $googleDesc .= "Téléphone : $telephone\n";
                $googleDesc .= "Facturation : $fullAddress\n";
                if (!$chantierSame) {
                    $googleDesc .= "Chantier : $chantierStr\n";
                }
                $googleDesc .= "\n";

                $googleDesc .= "💳 PAIEMENT ET TARIFICATION\n";
                $googleDesc .= "------------------------------------------------\n";
                $googleDesc .= "Statut : ✅ PAYÉ EN LIGNE (Stripe)\n";
                $googleDesc .= "Prix HTVA : " . number_format($truePriceHtva, 2, ',', ' ') . " €\n";
                $googleDesc .= "TVA ($vat_regime%) : " . number_format($montantTVA, 2, ',', ' ') . " €\n";
                $googleDesc .= "TOTAL PAYÉ : " . number_format($totalTTC, 2, ',', ' ') . " €\n";

                $logic->addEvent([
                    'summary'     => "✅ PAYÉ EN LIGNE: $nom $prenom",
                    'location'    => $gpsLocation, // L'adresse du chantier pour le GPS
                    'description' => $googleDesc,
                    'date'        => $dateOnly,
                    'time'        => $timeOnly
                ]);

                // --- C. ENVOI DE L'EMAIL DE CONFIRMATION COMPLET ---
                // Récupération des paramètres admin
                $settings = [];
                try {
                    $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM settings");
                    while ($rowSet = $stmtSet->fetch(PDO::FETCH_ASSOC)) {
                        $settings[$rowSet['setting_key']] = $rowSet['setting_value'];
                    }
                } catch (Exception $e) {}
                $adminCustomText = $settings['email_confirmation'] ?? "Nous vous confirmons que votre intervention a bien été enregistrée dans notre planning.";

                $linkEdit = $rootUrl . "/index.php?page=modifier_rdv&token=" . $token . "#gerer-intervention";
                $logoUrl = $rootUrl . "/img/logo-saniflo.png";

                $montantHTVAFormat = number_format($truePriceHtva, 2, ',', ' ');
                $montantTVAFormat = number_format($montantTVA, 2, ',', ' ');
                $montantTTCFormat = number_format($totalTTC, 2, ',', ' ');

                $descriptionHTML = "";
                if (!empty($descUser)) {
                    $descriptionHTML = "<li style='margin-bottom:8px; margin-top:15px; padding-top:15px; border-top: 1px solid #ddd;'><strong>Vos remarques / description de la demande :</strong><br><i style='color:#555; display:block; margin-top:5px;'>" . nl2br(htmlspecialchars($descUser)) . "</i></li>";
                }

                $paymentInfoHTML = "
                <div style='background: #e8f5e9; padding: 20px; border-radius: 8px; border: 1px solid #a5d6a7; margin-top: 20px; color: #1b5e20;'>
                    <h3 style='margin-top:0; margin-bottom: 15px; font-size: 1.1rem; border-bottom: 1px solid #a5d6a7; padding-bottom: 8px;'>Détails de la tarification</h3>
                    <table style='width: 100%; border-collapse: collapse; font-size: 0.95rem;'>
                        <tr>
                            <td style='padding: 6px 0;'>Montant HTVA :</td>
                            <td style='text-align: right;'>$montantHTVAFormat €</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0;'>TVA ($vat_regime%) :</td>
                            <td style='text-align: right;'>$montantTVAFormat €</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0 0 0; font-weight: bold; font-size: 1.2rem; border-top: 1px solid #a5d6a7; margin-top: 6px;'>Total payé en ligne :</td>
                            <td style='text-align: right; font-weight: bold; font-size: 1.2rem; border-top: 1px solid #a5d6a7; padding-top: 12px;'>$montantTTCFormat €</td>
                        </tr>
                    </table>
                    <p style='font-size: 0.85rem; margin-bottom:0; margin-top: 15px; opacity: 0.9;'><i>Intervention payée en ligne avec succès via Stripe.</i></p>
                </div>";

                $subject = "Confirmation de votre rendez-vous - Saniflo SRL";
                $emailBody = "
                <html>
                <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #ddd;'>
                        <div style='text-align: center; margin-bottom: 20px;'><img src='$logoUrl' style='max-width: 180px;'></div>
                        
                        <h2 style='color: #004a99;'>Bonjour $prenom,</h2>
                        <p>" . nl2br(htmlspecialchars($adminCustomText)) . "</p>
                        
                        <div style='background-color: #f4f7f6; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #004a99;'>
                            <h3 style='margin-top:0; color:#004a99;'>Récapitulatif de l'intervention</h3>
                            <ul style='list-style-type: none; padding-left: 0;'>
                                <li style='margin-bottom:8px;'><strong>Date :</strong> Le $dateFr à $timeOnly</li>
                                <li style='margin-bottom:8px;'><strong>Adresse :</strong> " . ($chantierSame ? $fullAddress : $chantierStr) . "</li>
                                <li style='margin-bottom:8px;'><strong>Appareil :</strong> $device_model ($device_year) $device_kw</li>
                                <li style='margin-bottom:8px;'><strong>Contact :</strong> $telephone</li>
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

                $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Saniflo SRL <info@saniflo.be>\r\n";
                $headers .= "Bcc: info@saniflo.be\r\n"; // Envoi d'une copie à vous-même

                mail($emailClient, $subject, $emailBody, $headers);

                // --- D. Redirection finale ---
                // Redirection vers la page de succès
                header("Location: " . $rootUrl . "/index.php?page=home&msg=payment_success");
                exit;
            } else {
                // Si le RDV est déjà payé ou introuvable, on redirige vers l'accueil sans erreur fatale
                header("Location: " . $rootUrl . "/index.php?page=home");
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Erreur validation Stripe : " . $e->getMessage());
        header("Location: " . $rootUrl . "/index.php?page=home&msg=error");
        exit;
    }
}

// Redirection par défaut si aucun session_id n'est fourni
header("Location: " . $rootUrl . "/index.php");
exit;