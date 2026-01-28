<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$_GET['read']]);
    header("Location: messages.php"); exit;
}

$msgs = $pdo->query("SELECT * FROM messages ORDER BY date_envoi DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messagerie</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<header class="admin-header">
    <div class="admin-logo"><i class="fas fa-shield-alt"></i> SANIFLO ADMIN</div>
    <nav class="admin-nav">
        <a href="dashboard.php">Tableau de bord</a>
        <a href="quotes.php">Rendez-vous</a>
        <a href="messages.php" class="active">Messages</a>
        <a href="../" target="_blank">Voir le site</a>
        <a href="logout.php" class="logout-btn">Déconnexion</a>
    </nav>
</header>

<div class="container">
    <h2>Messagerie</h2>
    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>De</th>
                <th>Sujet</th>
                <th>Message</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($msgs as $m): ?>
                <tr style="<?= $m['is_read'] == 0 ? 'background:#fffbf0; font-weight:bold;' : '' ?>">
                    <td><?= date('d/m/Y H:i', strtotime($m['date_envoi'])) ?></td>
                    <td>
                        <?= htmlspecialchars($m['nom']) ?><br>
                        <small><a href="tel:<?= htmlspecialchars($m['telephone']) ?>"><?= htmlspecialchars($m['telephone']) ?></a></small>
                    </td>
                    <td><?= htmlspecialchars($m['subject'] ?? 'Contact') ?></td>
                    <td><?= nl2br(htmlspecialchars($m['message'])) ?></td>
                    <td>
                        <?php if($m['is_read'] == 0): ?>
                            <a href="?read=<?= $m['id'] ?>" class="btn-action" style="background:#2196f3; color:white;">Marquer lu</a>
                        <?php else: ?>
                            <i class="fas fa-check" style="color:green;"></i> Lu
                        <?php endif; ?>
                        <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="btn-action">Répondre</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>