<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier mon rendez-vous - Saniflo SRL</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main style="padding: 150px 20px; text-align: center; background: #f8f9fa; min-height: 70vh;">
    <div class="container" style="max-width: 600px; background: white; padding: 40px; border-radius: 15px; shadow: 0 4px 15px rgba(0,0,0,0.1);">

        <i class="fas fa-calendar-alt" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 20px;"></i>

        <?php if ($peutModifier): ?>
            <h2>Modifier votre intervention</h2>
            <p>Votre rendez-vous est prévu le <strong><?= date('d/m/Y', strtotime($rdv['appointment_date'])) ?></strong>.</p>
            <p>Vous êtes dans le délai autorisé pour demander une modification.</p>

            <form action="" method="POST" style="margin-top: 30px;">
                <p>Souhaitez-vous que nous vous rappelions pour déplacer ce rendez-vous ?</p>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 15px;">Demander un report de rendez-vous</button>
            </form>

        <?php else: ?>
            <h2 style="color: #d32f2f;">Délai de modification dépassé</h2>
            <p>Votre rendez-vous est prévu le <strong><?= date('d/m/Y', strtotime($rdv['appointment_date'])) ?></strong>.</p>
            <div style="background: #ffebee; padding: 20px; border-radius: 10px; margin-top: 20px; color: #c62828;">
                <p><i class="fas fa-exclamation-triangle"></i> Conformément à nos conditions, les modifications en ligne ne sont plus possibles moins de 7 jours avant l'intervention.</p>
            </div>
            <p style="margin-top: 20px;">Veuillez nous contacter directement par téléphone pour toute urgence : <br><strong>0495 50 17 17</strong></p>
        <?php endif; ?>

        <?= $message_status ?>
    </div>
</main>

<?php include __DIR__ . '/partials/header.php'; ?>
</body>
</html>