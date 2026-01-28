<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// ACTION : Marquer comme lu
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$_GET['read']]);
    header("Location: messages.php"); exit;
}

// ACTION : Supprimer (Nouveau)
if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: messages.php?msg=deleted"); exit;
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
<?php include 'nav.php'; ?>

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
                <th style="text-align: right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($msgs as $m):
                // Sécurisation des données (PHP 8.1+)
                $date = $m['date_envoi'] ? date('d/m/Y H:i', strtotime($m['date_envoi'])) : '';
                $nom = htmlspecialchars($m['nom'] ?? 'Inconnu');
                $tel = htmlspecialchars($m['telephone'] ?? '');
                $email = htmlspecialchars($m['email'] ?? '');
                $sujet = htmlspecialchars($m['subject'] ?? 'Contact');
                $message = nl2br(htmlspecialchars($m['message'] ?? ''));
                $isUnread = empty($m['is_read']);
                ?>
                <tr style="<?= $isUnread ? 'background:#fffbf0; font-weight:600;' : '' ?>">
                    <td style="white-space:nowrap;"><?= $date ?></td>
                    <td>
                        <?= $nom ?><br>
                        <?php if($tel): ?>
                            <small><a href="tel:<?= $tel ?>"><?= $tel ?></a></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($isUnread): ?>
                            <i class="fas fa-circle" style="color:var(--secondary); font-size:0.6rem; vertical-align:middle;"></i>
                        <?php endif; ?>
                        <?= $sujet ?>
                    </td>
                    <td style="font-size:0.95rem; color:#555;"><?= $message ?></td>
                    <td style="text-align: right; white-space: nowrap;">

                        <a href="mailto:<?= $email ?>" class="btn-action" title="Répondre">
                            <i class="fas fa-envelope"></i>
                        </a>

                        <?php if($isUnread): ?>
                            <a href="?read=<?= $m['id'] ?>" class="btn-action" title="Marquer comme lu" style="background-color: var(--info); color: white;">
                                <i class="fas fa-check"></i>
                            </a>
                        <?php else: ?>
                            <span class="btn-action" style="background:transparent; color:var(--success); cursor:default;" title="Déjà lu">
                                <i class="fas fa-check-double"></i>
                            </span>
                        <?php endif; ?>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce message ?');">
                            <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn-action btn-delete" title="Supprimer" style="cursor:pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>