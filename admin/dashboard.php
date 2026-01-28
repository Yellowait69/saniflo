<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// Stats rapides
$countRdv = $pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'nouveau'")->fetchColumn();
$countMsg = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$countTotalRdv = $pdo->query("SELECT COUNT(*) FROM quote_requests")->fetchColumn();

// Derniers RDV
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

<header class="admin-header">
    <div class="admin-logo"><i class="fas fa-shield-alt"></i> SANIFLO ADMIN</div>
    <nav class="admin-nav">
        <a href="dashboard.php" class="active">Tableau de bord</a>
        <a href="quotes.php">Rendez-vous <?php if($countRdv > 0) echo "($countRdv)"; ?></a>
        <a href="messages.php">Messages <?php if($countMsg > 0) echo "($countMsg)"; ?></a>
        <a href="../" target="_blank">Voir le site <i class="fas fa-external-link-alt"></i></a>
        <a href="logout.php" class="logout-btn">Déconnexion</a>
    </nav>
</header>

<div class="container">
    <h1>Bonjour, <?= htmlspecialchars($_SESSION['admin_user']) ?></h1>

    <div class="dashboard-cards">
        <div class="card">
            <div>
                <h3>RDV à traiter</h3>
                <div class="number" style="color: #d32f2f;"><?= $countRdv ?></div>
            </div>
            <i class="fas fa-calendar-check" style="color: #ffcdd2;"></i>
        </div>
        <div class="card">
            <div>
                <h3>Messages non lus</h3>
                <div class="number" style="color: #1976d2;"><?= $countMsg ?></div>
            </div>
            <i class="fas fa-envelope" style="color: #bbdefb;"></i>
        </div>
        <div class="card">
            <div>
                <h3>Total Dossiers</h3>
                <div class="number"><?= $countTotalRdv ?></div>
            </div>
            <i class="fas fa-folder" style="color: #e0e0e0;"></i>
        </div>
    </div>

    <div class="table-container">
        <h3><i class="fas fa-clock"></i> 5 Dernières demandes de rendez-vous</h3>
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Type</th>
                <th>Lundi demandé</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($lastRdvs as $rdv): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($rdv['created_at'])) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($rdv['lastname'] . ' ' . $rdv['firstname']) ?></strong><br>
                        <small><?= htmlspecialchars($rdv['billing_city']) ?></small>
                    </td>
                    <td><?= htmlspecialchars(str_replace('_', ' ', $rdv['payment_method'])) ?></td>
                    <td><strong><?= date('d/m/Y H:i', strtotime($rdv['appointment_date'])) ?></strong></td>
                    <td>
                            <span class="badge <?= $rdv['status'] == 'nouveau' ? 'badge-new' : 'badge-done' ?>">
                                <?= ucfirst($rdv['status']) ?>
                            </span>
                    </td>
                    <td><a href="quotes.php?id=<?= $rdv['id'] ?>" class="btn-action">Voir détails</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>