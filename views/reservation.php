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
</head>
<body>

<?php
/**
 * 1. EN-TÊTE
 * Inclut le logo et la navigation mise à jour avec les liens index.php#ancres
 */
include __DIR__ . '/partials/header.php';
?>

<main id="main" style="padding-top: 120px; padding-bottom: 60px; min-height: 80vh; background: #f8f9fa;">
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="booking-card shadow-sm p-4 p-md-5 bg-white rounded border-0">

                    <?php
                    /**
                     * AFFICHAGE DES MESSAGES (Succès / Erreur / Annulation Stripe)
                     * La variable $message_status est générée par le HomeController
                     */
                    if (!empty($message_status)) {
                        echo '<div class="mb-4">' . $message_status . '</div>';
                    }

                    // Message d'annulation spécifique Stripe via URL
                    if (isset($_GET['msg']) && $_GET['msg'] === 'cancel') {
                        echo '<div class="alert warning" style="background:#fff3e0; color:#e65100; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ffe0b2;">
                                <i class="fas fa-exclamation-triangle"></i> Le paiement a été annulé. Votre créneau n\'a pas été réservé.
                              </div>';
                    }
                    ?>

                    <?php
                    /**
                     * 2. FORMULAIRE WIZARD ÉTAPE PAR ÉTAPE
                     * C'est ici que toute la logique de sélection s'affiche
                     */
                    include __DIR__ . '/partials/quote_wizard.php';
                    ?>

                </div>

                <div class="text-center mt-4" style="opacity: 0.7; font-size: 0.9rem;">
                    <p><i class="fas fa-lock"></i> Paiement 100% sécurisé via Stripe &bull; Confirmation immédiate par email</p>
                </div>
            </div>
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