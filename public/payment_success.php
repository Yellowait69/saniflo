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

// Construction de l'URL de base dynamique pour les redirections
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$publicUrl = $protocol . "://" . $host . "/public";

$session_id = $_GET['session_id'] ?? null;

if ($session_id) {
    try {
        // 3. Récupération de la session Stripe pour vérifier le statut
        $session = Session::retrieve($session_id);

        if ($session->payment_status === 'paid') {

            // 4. Recherche du rendez-vous correspondant en base
            $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE stripe_session_id = ?");
            $stmt->execute([$session_id]);
            $rdv = $stmt->fetch(PDO::FETCH_ASSOC);

            // On vérifie que le RDV existe et qu'il est encore en attente de paiement
            if ($rdv && $rdv['payment_status'] === 'unpaid') {

                // --- A. Mise à jour du statut en base de données ---
                // CRITIQUE : On passe le statut à 'nouveau' pour qu'il soit visible dans l'admin
                $updateSql = "UPDATE quote_requests SET payment_status = 'paid', status = 'nouveau' WHERE id = ?";
                $pdo->prepare($updateSql)->execute([$rdv['id']]);

                // --- B. Ajout à Google Agenda ---
                // MODIFICATION : On injecte $pdo pour la logique de planification
                $logic = new PlanningLogic($pdo);

                // Reconstruction des informations pour l'événement
                $nom = $rdv['lastname'];
                $prenom = $rdv['firstname'];
                $phone = $rdv['phone'];
                $email = $rdv['email'];
                $fullAddress = $rdv['billing_street'] . ", " . $rdv['billing_zip'] . " " . $rdv['billing_city'];

                // Calcul de la date et de l'heure séparées
                $timestamp = strtotime($rdv['appointment_date']);
                $dateOnly = date('Y-m-d', $timestamp);
                $timeOnly = date('H:i', $timestamp);

                // Construction de la description de l'événement
                $description  = "Client: $prenom $nom\n";
                $description .= "Tél: $phone\n";
                $description .= "Email: $email\n";
                $description .= "Adresse: $fullAddress\n";
                $description .= "Note: " . ($rdv['description'] ?? 'Aucune') . "\n";
                $description .= "\n--- PAIEMENT ---\n";
                $description .= "Statut: PAYÉ (Stripe)\n";
                $description .= "Montant: " . $rdv['total_price_htva'] . "€ HTVA\n";
                $description .= "Session ID: " . $session_id;

                // Titre de l'événement dans l'agenda
                $eventSummary = "En Ligne (PAYÉ): $nom $prenom - $phone";

                // Appel au service Google Agenda
                $logic->addEvent([
                    'summary'     => $eventSummary,
                    'location'    => $fullAddress,
                    'description' => $description,
                    'date'        => $dateOnly,
                    'time'        => $timeOnly
                ]);

                // --- C. Redirection vers l'accueil (Chemin dynamique absolu vers /public/) ---
                header("Location: " . $publicUrl . "/index.php?msg=payment_success");
                exit;
            }
        }
    } catch (Exception $e) {
        // En cas d'erreur technique, on enregistre l'erreur et on redirige
        error_log("Erreur validation Stripe : " . $e->getMessage());
        header("Location: " . $publicUrl . "/index.php?msg=error");
        exit;
    }
}

// Redirection par défaut
header("Location: " . $publicUrl . "/index.php");
exit;
?>