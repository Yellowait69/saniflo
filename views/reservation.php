<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Planifier un entretien - Saniflo SRL</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Styles spécifiques et responsives pour la page de réservation */
        .reservation-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .booking-card {
            width: 100%;
            max-width: 900px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 50px;
            box-sizing: border-box;
        }

        .security-badge {
            text-align: center;
            margin-top: 25px;
            opacity: 0.8;
            font-size: 0.95rem;
            color: #555;
        }

        .alert-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-warning {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffe0b2;
        }

        /* Mode Mobile (Téléphones et petites tablettes) */
        @media (max-width: 768px) {
            #main-reservation {
                padding-top: 100px !important;
                padding-bottom: 40px !important;
            }

            .booking-card {
                padding: 25px; /* Réduction drastique du padding sur petit écran */
                border-radius: 10px;
            }

            .security-badge {
                font-size: 0.85rem;
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>

<?php
/**
 * 1. EN-TÊTE
 * Inclut le logo et la navigation mise à jour avec les liens index.php#ancres
 */
include __DIR__ . '/partials/header.php';
?>

<main id="main-reservation" style="padding-top: 140px; padding-bottom: 60px; min-height: 80vh; background: #f8f9fa;">
    <div class="container">

        <div class="reservation-wrapper">
            <div class="booking-card">

                <?php
                /**
                 * AFFICHAGE DES MESSAGES (Succès / Erreur / Annulation Stripe)
                 * La variable $message_status est générée par le HomeController
                 */
                if (!empty($message_status)) {
                    echo '<div class="alert-box">' . $message_status . '</div>';
                }

                // Message d'annulation spécifique Stripe via URL
                if (isset($_GET['msg']) && $_GET['msg'] === 'cancel') {
                    echo '<div class="alert-box alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Le paiement a été annulé. Votre créneau n\'a pas été réservé.
                          </div>';
                }
                ?>

                <?php
                /**
                 * 2. FORMULAIRE WIZARD ÉTAPE PAR ÉTAPE
                 * C'est ici que toute la logique de sélection s'affiche (fichier rendu responsive précédemment)
                 */
                include __DIR__ . '/partials/quote_wizard.php';
                ?>

            </div>
        </div>

        <div class="security-badge">
            <p><i class="fas fa-lock" style="color: #2e7d32; margin-right: 5px;"></i> Paiement 100% sécurisé via Stripe &bull; Confirmation immédiate par email</p>
        </div>

    </div>
</main>

<?php
/**
 * 3. PIED DE PAGE
 */
include __DIR__ . '/partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/fr.js"></script>

<script src="js/scripts.js"></script>

</body>
</html>