<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// Stats rapides
$countRdv = $pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'nouveau'")->fetchColumn();
$countMsg = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$countTotalRdv = $pdo->query("SELECT COUNT(*) FROM quote_requests")->fetchColumn();

// Derniers RDV (Limit 5)
$lastRdvs = $pdo->query("SELECT * FROM quote_requests ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord - Saniflo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <h1>Bonjour, <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></h1>

    <div class="dashboard-cards">
        <div class="card">
            <div>
                <h3>RDV à traiter</h3>
                <div class="number" style="color: var(--danger);"><?= $countRdv ?></div>
            </div>
            <i class="fas fa-calendar-check" style="color: #ffcdd2;"></i>
        </div>
        <div class="card">
            <div>
                <h3>Messages non lus</h3>
                <div class="number" style="color: var(--info);"><?= $countMsg ?></div>
            </div>
            <i class="fas fa-envelope" style="color: #bbdefb;"></i>
        </div>
        <div class="card">
            <div>
                <h3>Total Dossiers</h3>
                <div class="number" style="color: var(--text-main);"><?= $countTotalRdv ?></div>
            </div>
            <i class="fas fa-folder" style="color: #e0e0e0;"></i>
        </div>
    </div>

    <div class="table-container">
        <h3><i class="fas fa-clock" style="color:var(--primary);"></i> 5 Dernières demandes de rendez-vous</h3>
        <table>
            <thead>
            <tr>
                <th>Date demande</th>
                <th>Client</th>
                <th>Paiement</th>
                <th>Lundi demandé</th>
                <th>Statut</th>
                <th style="text-align: right;">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($lastRdvs as $rdv):
                // Sécurisation des données
                $created = $rdv['created_at'] ? date('d/m/Y', strtotime($rdv['created_at'])) : '-';
                $appointment = $rdv['appointment_date'] ? date('d/m/Y H:i', strtotime($rdv['appointment_date'])) : '-';
                $client = htmlspecialchars(($rdv['lastname'] ?? '') . ' ' . ($rdv['firstname'] ?? ''));
                $city = htmlspecialchars($rdv['billing_city'] ?? '');

                // Formattage paiement plus lisible
                $paymentRaw = $rdv['payment_method'] ?? '';
                if ($paymentRaw === 'after') $paymentStr = 'Différé (+3%)';
                elseif ($paymentRaw === 'direct') $paymentStr = 'Sur place';
                else $paymentStr = ucfirst($paymentRaw);

                $status = $rdv['status'] ?? 'nouveau';
                ?>
                <tr style="<?= $status == 'nouveau' ? 'background:#fffbf0;' : '' ?>">
                    <td><?= $created ?></td>
                    <td>
                        <strong><?= $client ?></strong><br>
                        <small><?= $city ?></small>
                    </td>
                    <td><?= htmlspecialchars($paymentStr) ?></td>
                    <td><strong><?= $appointment ?></strong></td>
                    <td>
                        <span class="badge <?= $status == 'nouveau' ? 'badge-new' : 'badge-done' ?>">
                            <?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <a href="quote_edit.php?id=<?= $rdv['id'] ?>" class="btn-action btn-edit" title="Voir les détails" style="background-color: var(--primary); color: white;">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>