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
// Remplacez la chaîne ci-dessous par votre CLÉ SECRÈTE (Secret Key)
// ==============================================================================
Stripe::setApiKey('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');

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

            // On vérifie que le RDV existe et qu'il n'a pas déjà été traité (statut 'unpaid')
            if ($rdv && $rdv['payment_status'] === 'unpaid') {

                // --- A. Mise à jour du statut en base de données ---
                // On passe le paiement à 'paid' et le statut global à 'confirme' (optionnel)
                $updateSql = "UPDATE quote_requests SET payment_status = 'paid', status = 'confirme' WHERE id = ?";
                $pdo->prepare($updateSql)->execute([$rdv['id']]);

                // --- B. Ajout à Google Agenda ---
                $logic = new PlanningLogic();

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

                // --- C. Redirection vers l'accueil avec message de succès ---
                header("Location: index.php?msg=payment_success");
                exit;
            }
        }
    } catch (Exception $e) {
        // En cas d'erreur technique, on arrête le script et on affiche l'erreur
        die("Erreur lors de la validation du paiement : " . $e->getMessage());
    }
}

// Si pas de session_id ou paiement non validé, redirection simple vers l'accueil
header("Location: index.php");
exit;