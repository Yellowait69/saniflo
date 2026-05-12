<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Contactez Saniflo SRL - Brabant Wallon</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Styles spécifiques et responsives pour la page de contact */
        .contact-page-wrapper {
            display: flex;
            gap: 40px;
            align-items: stretch;
        }

        .contact-col-info {
            flex: 1;
            min-width: 300px;
        }

        .contact-col-form {
            flex: 1.5;
            min-width: 300px;
        }

        .contact-box {
            padding: 40px;
            border-radius: 15px;
            height: 100%;
            box-sizing: border-box;
        }

        .contact-box-dark {
            background: var(--primary-dark, #003366);
            color: white;
        }

        .contact-box-light {
            background: #f8f9fa;
            border: 1px solid #eee;
        }

        .contact-icon-wrapper {
            width: 30px;
            text-align: center;
        }

        .contact-link {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-link.bold {
            font-weight: 600;
        }

        .contact-link:hover {
            color: var(--accent-yellow, #ffc107);
        }

        /* Mode Mobile et petites tablettes */
        @media (max-width: 768px) {
            .contact-page-wrapper {
                flex-direction: column; /* Empilement vertical */
                gap: 25px;
            }

            .contact-box {
                padding: 25px; /* Réduction des marges internes sur mobile */
            }

            #main-contact {
                padding-top: 100px !important; /* Ajustement du padding top pour mobile */
                padding-bottom: 40px !important;
            }
        }
    </style>
</head>
<body>

<?php
// 1. INCLUSION DE L'EN-TÊTE
include __DIR__ . '/partials/header.php';
?>

<main id="main-contact" style="padding-top: 130px; padding-bottom: 60px; min-height: 80vh; background: #fff;">
    <div class="container">

        <div id="contact-section" class="section-title text-center mb-5" style="scroll-margin-top: 120px;">
            <h2 style="font-weight: 700; color: var(--primary-dark);">Contactez-nous</h2>
            <p style="color: #666; max-width: 600px; margin: 0 auto;">Une question ? Un projet ? Notre équipe est à votre écoute.</p>
        </div>

        <?php
        // Affichage du message de succès ou d'erreur du formulaire (géré par HomeController)
        if (!empty($message_status)) {
            echo '<div class="mb-4">' . $message_status . '</div>';
        }
        ?>

        <div class="contact-page-wrapper">

            <div class="contact-col-info shadow-sm">
                <div class="contact-box contact-box-dark">
                    <h3 style="color: var(--accent-yellow, #ffc107); margin-bottom: 25px; margin-top: 0;">Nos coordonnées</h3>

                    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                        <div class="contact-icon-wrapper">
                            <i class="fas fa-map-marker-alt" style="color: var(--accent-yellow, #ffc107); font-size: 1.2rem;"></i>
                        </div>
                        <p style="margin: 0; line-height: 1.4;">Rue de Fontenelle, 15<br>1325 Dion-Valmont</p>
                    </div>

                    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                        <div class="contact-icon-wrapper">
                            <i class="fas fa-phone-alt" style="color: var(--accent-yellow, #ffc107); font-size: 1.2rem;"></i>
                        </div>
                        <p style="margin: 0;">
                            <a href="tel:0495501717" class="contact-link bold">0495 50 17 17</a>
                        </p>
                    </div>

                    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                        <div class="contact-icon-wrapper">
                            <i class="fas fa-envelope" style="color: var(--accent-yellow, #ffc107); font-size: 1.2rem;"></i>
                        </div>
                        <p style="margin: 0;">
                            <a href="mailto:info@saniflo.be" class="contact-link">info@saniflo.be</a>
                        </p>
                    </div>

                    <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 30px 0;">

                    <h4 style="color: var(--accent-yellow, #ffc107); margin-bottom: 15px; margin-top: 0;">Heures d'ouverture</h4>
                    <p style="margin: 0;"><i class="far fa-clock" style="margin-right: 10px; opacity: 0.8;"></i>Lundi - Vendredi : 08h00 - 18h00</p>
                </div>
            </div>

            <div class="contact-col-form shadow-sm">
                <div class="contact-box contact-box-light">
                    <?php include __DIR__ . '/partials/contact.php'; ?>
                </div>
            </div>

        </div>
    </div>
</main>

<?php
// 3. INCLUSION DU PIED DE PAGE
include __DIR__ . '/partials/footer.php';
?>

<script src="js/scripts.js"></script>

</body>
</html>