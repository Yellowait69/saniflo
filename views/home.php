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
// On inclut chaque bloc séparément
// Note : assurez-vous que les fichiers existent dans views/partials/
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/about.php';
include __DIR__ . '/partials/services.php';

// Le module de devis local (quote_wizard.php) a été retiré
// pour être remplacé par un bouton vers un lien externe dans le header.

include __DIR__ . '/partials/contact.php';
include __DIR__ . '/partials/footer.php';
?>

<script src="js/scripts.js"></script>

</body>
</html>