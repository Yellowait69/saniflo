<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// Action : Marquer comme traité
if (isset($_GET['archive']) && is_numeric($_GET['archive'])) {
    $stmt = $pdo->prepare("UPDATE quote_requests SET status = 'traité' WHERE id = ?");
    $stmt->execute([$_GET['archive']]);
    header("Location: quotes.php");
    exit;
}

// Récupération des données
$sql = "SELECT * FROM quote_requests ORDER BY appointment_date DESC";
$quotes = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Rendez-vous</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<header class="admin-header">
    <div class="admin-logo"><i class="fas fa-shield-alt"></i> SANIFLO ADMIN</div>
    <nav class="admin-nav">
        <a href="dashboard.php">Tableau de bord</a>
        <a href="quotes.php" class="active">Rendez-vous</a>
        <a href="messages.php">Messages</a>
        <a href="../" target="_blank">Voir le site</a>
        <a href="logout.php" class="logout-btn">Déconnexion</a>
    </nav>
</header>

<div class="container">
    <h2>Gestion des demandes d'intervention</h2>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>Date RDV</th>
                <th>Client / Adresse</th>
                <th>Appareil</th>
                <th>Prix HTVA</th>
                <th>Détails</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($quotes as $q): ?>
                <tr style="<?= $q['status'] == 'nouveau' ? 'background:#fffbf0;' : '' ?>">
                    <td>
                        <strong><?= date('d/m/Y', strtotime($q['appointment_date'])) ?></strong><br>
                        <?= date('H:i', strtotime($q['appointment_date'])) ?>
                    </td>
                    <td>
                        <i class="fas fa-user"></i> <?= htmlspecialchars($q['lastname'] . ' ' . $q['firstname']) ?><br>
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($q['billing_city']) ?> (<?= htmlspecialchars($q['zip']) ?>)<br>
                        <?php if($q['worksite_same_as_billing'] == 0): ?>
                            <small style="color:#d32f2f;">⚠ Chantier différent</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($q['device_brand']) ?> <?= htmlspecialchars($q['device_model']) ?><br>
                        <small>Série: <?= htmlspecialchars($q['device_serial'] ?? 'N/A') ?></small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($q['total_price_htva']) ?> €</strong><br>
                        <small><?= $q['payment_method'] == 'after' ? 'Paiement différé (+3%)' : 'Direct' ?></small>
                    </td>
                    <td>
                        <details>
                            <summary style="cursor:pointer; color:#0070cd;">Voir tout</summary>
                            <div style="margin-top:10px; font-size:0.9rem; line-height:1.5;">
                                <strong>Contact:</strong> <?= htmlspecialchars($q['phone']) ?> - <a href="mailto:<?= htmlspecialchars($q['email']) ?>"><?= htmlspecialchars($q['email']) ?></a><br>
                                <strong>Adresse Fact.:</strong> <?= htmlspecialchars($q['billing_street']) ?> <?= htmlspecialchars($q['billing_box']) ?>, <?= htmlspecialchars($q['zip']) ?> <?= htmlspecialchars($q['billing_city']) ?><br>

                                <?php if($q['worksite_same_as_billing'] == 0): ?>
                                    <hr>
                                    <strong>CHANTIER:</strong> <?= htmlspecialchars($q['worksite_street']) ?>, <?= htmlspecialchars($q['worksite_zip']) ?> <?= htmlspecialchars($q['worksite_city']) ?><br>
                                    <strong>Contact sur place:</strong> <?= htmlspecialchars($q['worksite_phone']) ?>
                                <?php endif; ?>

                                <hr>
                                <strong>Info Tech:</strong> Année <?= htmlspecialchars($q['device_year']) ?> - Puissance: <?= htmlspecialchars($q['device_kw']) ?><br>
                                <strong>Remarques:</strong> <em><?= htmlspecialchars($q['description']) ?></em><br>

                                <?php if($q['is_company']): ?>
                                    <hr>
                                    <strong>SOCIÉTÉ:</strong> <?= htmlspecialchars($q['company_name']) ?> - TVA: <?= htmlspecialchars($q['vat_number']) ?>
                                <?php endif; ?>
                            </div>
                        </details>
                    </td>
                    <td>
                        <?php if($q['status'] == 'nouveau'): ?>
                            <a href="?archive=<?= $q['id'] ?>" class="btn-action" style="background:#4caf50; color:white;" onclick="return confirm('Confirmer le traitement de ce dossier ?');">Traiter</a>
                        <?php else: ?>
                            <span class="badge badge-done">Archivé</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>