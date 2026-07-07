<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">

    <title><?= htmlspecialchars($settings['site_title'] ?? 'Saniflo SRL | Chauffagiste & Sanitaire Expert') ?></title>
    <meta name="description" content="<?= htmlspecialchars($settings['site_description'] ?? 'Expert en chauffage, sanitaire et traitement des eaux. Entretien, dépannage et installation. Prenez rendez-vous en ligne avec Saniflo SRL.') ?>">
    <meta name="keywords" content="chauffagiste, sanitaire, entretien chaudière, Viessmann, BWT, plomberie, Brabant Wallon, Bruxelles, Namur">
    <meta name="author" content="Saniflo SRL">
    <meta name="robots" content="index, follow">

    <meta property="og:title" content="Saniflo SRL | Chauffagiste & Sanitaire Expert">
    <meta property="og:description" content="Votre expert de confiance pour l'entretien et l'installation de vos systèmes de chauffage et sanitaires.">
    <meta property="og:image" content="https://www.saniflo.be/img/logo-saniflo.png">
    <meta property="og:url" content="https://www.saniflo.be">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_BE">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">

    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Plumber",
            "name": "Saniflo SRL",
            "image": "https://www.saniflo.be/img/logo-saniflo.png",
            "@id": "https://www.saniflo.be",
            "url": "https://www.saniflo.be",
            "telephone": "+32495501717",
            "email": "info@saniflo.be",
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "Rue de Fontenelle 15",
                "addressLocality": "Dion-Valmont",
                "postalCode": "1325",
                "addressCountry": "BE"
            },
            "geo": {
                "@type": "GeoCoordinates",
                "latitude": 50.7042,
                "longitude": 4.6367
            },
            "openingHoursSpecification": {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": [
                    "Monday", "Tuesday", "Wednesday", "Thursday", "Friday"
                ],
                "opens": "08:00",
                "closes": "18:00"
            },
            "priceRange": "$$"
        }
    </script>
</head>
<body>

<?php
// NB: Les variables $settings, $certifications, $teamMembers, $services et $projects
// sont automatiquement transmises ici depuis la fonction index() du HomeController.

// 1. En-tête (Logo, Nav, Hero - Modifié dynamiquement)
include __DIR__ . '/partials/header.php';

// 2. Présentation
include __DIR__ . '/partials/about.php';

// 3. Services (Boucle sur les services de la base de données)
include __DIR__ . '/partials/services.php';

// 4. Portfolio / Réalisations (Boucle sur les projets)
include __DIR__ . '/partials/portfolio.php';

// 5. Avis clients
include __DIR__ . '/partials/reviews.php';

// Note : Le formulaire de contact a été déplacé sur la page dédiée (views/contact_page.php).
// La page d'accueil est désormais 100% allégée et orientée "Vitrine".

// 6. Pied de page
include __DIR__ . '/partials/footer.php';
?>

<script src="js/scripts.js" defer></script>

</body>
</html>