<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

try {
    // Stats rapides
    $countRdv = $pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'nouveau'")->fetchColumn();
    $countMsg = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
    $countTotalRdv = $pdo->query("SELECT COUNT(*) FROM quote_requests")->fetchColumn();

    // Derniers RDV (Limit 5)
    $lastRdvs = $pdo->query("SELECT * FROM quote_requests ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div style='color:red; padding:20px;'>Erreur de chargement des données du tableau de bord.</div>");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Saniflo Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .badge-status { padding: 5px 10px; border-radius: 20px; color: #fff; font-size: 0.85rem; font-weight: bold; display: inline-block; text-align: center; min-width: 80px; }
        .bg-new { background-color: #2196F3; } /* Bleu */
        .bg-pending { background-color: #FFC107; color: #000; } /* Jaune */
        .bg-done { background-color: #4CAF50; } /* Vert */
        .bg-canceled { background-color: #F44336; } /* Rouge */

        .client-type-icon { color: #888; margin-right: 5px; }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1><i class="fas fa-tachometer-alt" style="color:var(--primary);"></i> Bonjour, <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></h1>
    </div>

    <!-- CARTES DE STATISTIQUES -->
    <div class="dashboard-cards">
        <div class="card">
            <div>
                <h3 style="margin-top: 0; color: #555;">Nouveaux RDV</h3>
                <div class="number" style="color: var(--danger); font-size: 2rem; font-weight: bold;"><?= $countRdv ?></div>
            </div>
            <i class="fas fa-calendar-plus fa-3x" style="color: #ffcdd2;"></i>
        </div>
        <div class="card">
            <div>
                <h3 style="margin-top: 0; color: #555;">Messages non lus</h3>
                <div class="number" style="color: var(--info); font-size: 2rem; font-weight: bold;"><?= $countMsg ?></div>
            </div>
            <i class="fas fa-envelope-open-text fa-3x" style="color: #bbdefb;"></i>
        </div>
        <div class="card">
            <div>
                <h3 style="margin-top: 0; color: #555;">Total Dossiers</h3>
                <div class="number" style="color: var(--text-main); font-size: 2rem; font-weight: bold;"><?= $countTotalRdv ?></div>
            </div>
            <i class="fas fa-folder-open fa-3x" style="color: #e0e0e0;"></i>
        </div>
    </div>

    <!-- TABLEAU DES DERNIERS RENDEZ-VOUS -->
    <div class="table-container" style="margin-top: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;"><i class="fas fa-clock" style="color:var(--primary);"></i> 5 Dernières demandes de rendez-vous</h3>
            <a href="quotes.php" class="btn-admin" style="padding: 8px 15px; font-size: 0.9rem; text-decoration: none;">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
            <tr style="background-color: #f4f7f6; border-bottom: 2px solid #ddd;">
                <th style="padding: 12px; text-align: left;">Date demande</th>
                <th style="padding: 12px; text-align: left;">Client</th>
                <th style="padding: 12px; text-align: left;">Paiement</th>
                <th style="padding: 12px; text-align: left;">RDV planifié</th>
                <th style="padding: 12px; text-align: center;">Statut</th>
                <th style="padding: 12px; text-align: right;">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($lastRdvs)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #666;">Aucune demande de rendez-vous pour le moment.</td>
                </tr>
            <?php else: ?>
                <?php foreach($lastRdvs as $rdv):
                    // Sécurisation et formatage des dates
                    $created = !empty($rdv['created_at']) ? date('d/m/Y', strtotime($rdv['created_at'])) : '-';
                    $appointment = !empty($rdv['appointment_date']) ? date('d/m/Y à H:i', strtotime($rdv['appointment_date'])) : '<span style="color:#999;">Non défini</span>';

                    // Client (Société ou Particulier)
                    $isCompany = !empty($rdv['is_company']) && $rdv['is_company'] == 1;
                    $clientName = htmlspecialchars(($rdv['lastname'] ?? '') . ' ' . ($rdv['firstname'] ?? ''));
                    if ($isCompany && !empty($rdv['company_name'])) {
                        $clientName = htmlspecialchars($rdv['company_name']);
                    }
                    $clientIcon = $isCompany ? '<i class="fas fa-building client-type-icon" title="Société"></i>' : '<i class="fas fa-user client-type-icon" title="Particulier"></i>';
                    $city = htmlspecialchars($rdv['billing_city'] ?? '');

                    // Formattage du paiement
                    $paymentRaw = $rdv['payment_method'] ?? '';
                    if ($paymentRaw === 'stripe') $paymentStr = '<i class="fab fa-stripe" style="color:#6772E5;"></i> En ligne';
                    elseif ($paymentRaw === 'after') $paymentStr = 'Différé (+3%)';
                    elseif ($paymentRaw === 'direct') $paymentStr = 'Sur place';
                    else $paymentStr = ucfirst($paymentRaw);

                    // Formattage du statut
                    $statusRaw = strtolower($rdv['status'] ?? 'nouveau');
                    if ($statusRaw === 'nouveau') { $badgeClass = 'bg-new'; $statusStr = 'Nouveau'; }
                    elseif ($statusRaw === 'en_attente') { $badgeClass = 'bg-pending'; $statusStr = 'En attente'; }
                    elseif ($statusRaw === 'traité') { $badgeClass = 'bg-done'; $statusStr = 'Traité'; }
                    elseif ($statusRaw === 'annulé') { $badgeClass = 'bg-canceled'; $statusStr = 'Annulé'; }
                    else { $badgeClass = 'bg-new'; $statusStr = ucfirst($statusRaw); }
                    ?>
                    <tr style="border-bottom: 1px solid #eee; <?= $statusRaw == 'nouveau' ? 'background-color: #fffbf0;' : '' ?>">
                        <td style="padding: 12px;"><?= $created ?></td>
                        <td style="padding: 12px;">
                            <strong><?= $clientIcon . $clientName ?></strong><br>
                            <small style="color: #777;"><?= $city ?></small>
                        </td>
                        <td style="padding: 12px;"><?= $paymentStr ?></td>
                        <td style="padding: 12px;"><strong><?= $appointment ?></strong></td>
                        <td style="padding: 12px; text-align: center;">
                            <span class="badge-status <?= $badgeClass ?>">
                                <?= $statusStr ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <a href="quote_edit.php?id=<?= $rdv['id'] ?>" class="btn-action btn-edit" title="Voir les détails" style="background-color: var(--primary); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-block;">
                                <i class="fas fa-eye"></i> Ouvrir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>