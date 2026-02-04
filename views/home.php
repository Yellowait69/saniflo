<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Saniflo SRL | Chauffagiste Expert</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php
// 1. En-tête (Logo, Nav, Hero)
include __DIR__ . '/partials/header.php';

// 2. Présentation
include __DIR__ . '/partials/about.php';

// 3. Services
include __DIR__ . '/partials/services.php';

// AJOUT : 3b. Portfolio / Réalisations
include __DIR__ . '/partials/portfolio.php';

// 4. Module de Prise de Rendez-vous (Wizard)
// Ce fichier doit être créé dans views/partials/quote_wizard.php
include __DIR__ . '/partials/quote_wizard.php';
include __DIR__ . '/partials/reviews.php';
// 5. Contact & Footer
include __DIR__ . '/partials/contact.php';
include __DIR__ . '/partials/footer.php';
?>

<script src="js/scripts.js"></script>

</body>
</html>