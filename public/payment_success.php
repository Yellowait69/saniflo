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

                // --- A. Mise à jour du statut en base ---
                $updateSql = "UPDATE quote_requests SET payment_status = 'paid', status = 'nouveau' WHERE id = ?";
                $pdo->prepare($updateSql)->execute([$rdv['id']]);

                // --- B. Ajout à Google Agenda ---
                $logic = new PlanningLogic($pdo);

                $nom = $rdv['lastname'];
                $prenom = $rdv['firstname'];
                $phone = $rdv['phone'];
                $email = $rdv['email'];
                $fullAddress = $rdv['billing_street'] . ", " . $rdv['billing_zip'] . " " . $rdv['billing_city'];

                $timestamp = strtotime($rdv['appointment_date']);
                $dateOnly = date('Y-m-d', $timestamp);
                $timeOnly = date('H:i', $timestamp);
                $dateFr = date('d/m/Y', $timestamp);

                $description  = "Client: $prenom $nom\n";
                $description .= "Tél: $phone\n";
                $description .= "Email: $email\n";
                $description .= "Adresse: $fullAddress\n";
                $description .= "Note: " . ($rdv['description'] ?? 'Aucune') . "\n";
                $description .= "\n--- PAIEMENT ---\n";
                $description .= "Statut: PAYÉ (Stripe)\n";
                $description .= "Montant: " . $rdv['total_price_htva'] . "€ HTVA\n";

                $eventSummary = "En Ligne (PAYÉ): $nom $prenom - $phone";

                $logic->addEvent([
                    'summary'     => $eventSummary,
                    'location'    => $fullAddress,
                    'description' => $description,
                    'date'        => $dateOnly,
                    'time'        => $timeOnly
                ]);

                // --- C. ENVOI DE L'EMAIL DE CONFIRMATION (AVEC LOGO ET LIEN MODIF) ---
                $token = $rdv['edit_token'];
                $editLink = $rootUrl . "/index.php?page=modifier_rdv&token=" . $token;
                $logoUrl = $rootUrl . "/img/logo-saniflo.png";

                $subject = "Confirmation de votre rendez-vous (Payé) - Saniflo SRL";

                $emailBody = "
                <html>
                <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e0e0e0; padding: 30px; border-radius: 12px;'>
                        <div style='text-align: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px;'>
                            <img src='$logoUrl' style='max-width: 200px;'>
                        </div>
                        
                        <h2 style='color: #004a99;'>Paiement confirmé !</h2>
                        <p>Bonjour $prenom $nom,</p>
                        <p>Nous avons bien reçu votre paiement. Votre rendez-vous est officiellement confirmé dans notre agenda :</p>
                        
                        <div style='background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #004a99;'>
                            <p style='margin: 0; font-size: 1.1rem;'><strong>Date :</strong> Le $dateFr</p>
                            <p style='margin: 5px 0 0 0; font-size: 1.1rem;'><strong>Heure :</strong> $timeOnly</p>
                        </div>
                        
                        <div style='background: #fffdf5; padding: 20px; border-radius: 8px; border: 1px solid #ffe0b2; margin: 30px 0;'>
                            <p style='margin-top: 0; color: #e65100; font-weight: bold;'>Modification de rendez-vous</p>
                            <p style='font-size: 0.95rem; color: #555;'>Besoin de changer de date ? Vous pouvez le faire jusqu'à <strong>7 jours avant</strong> l'intervention :</p>
                            <div style='text-align: center; margin-top: 20px;'>
                                <a href='$editLink' style='display: inline-block; background: #ffc107; color: #000; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Modifier mon rendez-vous</a>
                            </div>
                        </div>

                        <p style='font-size: 0.85rem; color: #777;'>Note : Passé le délai de 7 jours, les modifications ne sont plus possibles via ce lien.</p>
                        <p style='margin-top: 30px;'>L'équipe Saniflo SRL vous remercie.</p>
                    </div>
                </body>
                </html>";

                $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: Saniflo SRL <info@saniflo.be>\r\n";
                mail($email, $subject, $emailBody, $headers);

                // --- D. Redirection finale ---
                header("Location: " . $publicUrl . "/index.php?msg=payment_success");
                exit;
            }
        }
    } catch (Exception $e) {
        error_log("Erreur validation Stripe : " . $e->getMessage());
        header("Location: " . $publicUrl . "/index.php?msg=error");
        exit;
    }
}

header("Location: " . $publicUrl . "/index.php");
exit;