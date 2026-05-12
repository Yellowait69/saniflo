<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// ACTION : Marquer comme lu
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    // VÉRIFICATION CSRF
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_GET['csrf_token'])) {
        die("Erreur de sécurité CSRF : L'action a été bloquée.");
    }
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$_GET['read']]);
    header("Location: messages.php"); exit;
}

// ACTION : Supprimer
if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    // VÉRIFICATION CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité CSRF : L'action a été bloquée.");
    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie | Administration</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="style.css">

    <style>
        /* =========================================
           STYLES SPÉCIFIQUES À LA MESSAGERIE (UX/UI)
           ========================================= */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }

        .admin-header-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            color: #004a99; /* Var primary */
        }

        .table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
            border: 1px solid #eaeaea;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            color: #555;
            font-weight: 600;
            padding: 15px 20px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eaeaea;
        }

        td {
            padding: 20px;
            vertical-align: top;
            border-bottom: 1px solid #f0f0f0;
        }

        /* Lignes de messages */
        .msg-row {
            transition: background-color 0.2s ease;
        }
        .msg-row:hover {
            background-color: #fafafa;
        }

        /* État Non Lu */
        .msg-row.unread {
            background-color: #fffdf5;
            border-left: 4px solid #ffc107; /* Var accent */
        }
        .msg-row.unread td {
            font-weight: 600;
            color: #222;
        }

        /* État Lu */
        .msg-row.read {
            border-left: 4px solid transparent;
        }
        .msg-row.read td {
            color: #666;
        }

        /* Troncature intelligente du message */
        .msg-content-preview {
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Limite à 3 lignes */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.95rem;
            line-height: 1.5;
            max-width: 450px;
        }

        /* Badges et Infos */
        .sender-info { display: flex; flex-direction: column; gap: 3px; }
        .sender-name { font-size: 1rem; color: #004a99; }
        .sender-contact { font-size: 0.85rem; color: #888; text-decoration: none; display: flex; align-items: center; gap: 5px;}
        .sender-contact:hover { color: #004a99; }

        /* Boutons d'action */
        .action-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-reply { background: #e3f2fd; color: #1976d2; }
        .btn-reply:hover { background: #1976d2; color: #fff; transform: translateY(-2px); }

        .btn-read { background: #e8f5e9; color: #388e3c; }
        .btn-read:hover { background: #388e3c; color: #fff; transform: translateY(-2px); }

        .btn-delete { background: #ffebee; color: #d32f2f; }
        .btn-delete:hover { background: #d32f2f; color: #fff; transform: translateY(-2px); }

        .btn-disabled { background: transparent; color: #a5d6a7; cursor: default; }

        /* Alertes */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }

        /* État vide */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #888;
        }
        .empty-state i { font-size: 3rem; color: #ddd; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container" style="padding: 30px 15px; max-width: 1200px; margin: 0 auto;">

    <div class="admin-header-title">
        <i class="fas fa-inbox" style="font-size: 2rem;"></i>
        <h2 style="margin: 0;">Boîte de réception</h2>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Le message a été définitivement supprimé.
        </div>
    <?php endif; ?>

    <div class="table-container">
        <?php if (empty($msgs)): ?>
            <div class="empty-state">
                <i class="fas fa-envelope-open-text"></i>
                <h3>Votre messagerie est vide</h3>
                <p>Les nouveaux messages de vos clients apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Expéditeur</th>
                    <th>Sujet & Message</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($msgs as $m):
                    // Sécurisation des données
                    $dateObj = new DateTime($m['date_envoi']);
                    $dateFr = $dateObj->format('d/m/Y');
                    $heureFr = $dateObj->format('H:i');

                    $nom = htmlspecialchars($m['nom'] ?? 'Inconnu');
                    $tel = htmlspecialchars($m['telephone'] ?? '');
                    $email = htmlspecialchars($m['email'] ?? '');
                    $sujet = htmlspecialchars($m['subject'] ?? 'Contact');
                    $messageBrut = htmlspecialchars($m['message'] ?? '');

                    $isUnread = empty($m['is_read']);
                    $rowClass = $isUnread ? 'msg-row unread' : 'msg-row read';
                    ?>
                    <tr class="<?= $rowClass ?>">

                        <td style="white-space: nowrap;">
                            <span style="display: block; font-size: 0.95rem;"><?= $dateFr ?></span>
                            <span style="font-size: 0.8rem; color: #888;"><i class="far fa-clock"></i> <?= $heureFr ?></span>
                        </td>

                        <td>
                            <div class="sender-info">
                                <strong class="sender-name"><?= $nom ?></strong>
                                <?php if($tel): ?>
                                    <a href="tel:<?= $tel ?>" class="sender-contact" title="Appeler le client"><i class="fas fa-phone-alt"></i> <?= $tel ?></a>
                                <?php endif; ?>
                                <?php if($email): ?>
                                    <a href="mailto:<?= $email ?>" class="sender-contact" title="Envoyer un email"><i class="far fa-envelope"></i> <?= $email ?></a>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td>
                            <div style="margin-bottom: 5px;">
                                <?php if($isUnread): ?>
                                    <span style="background: var(--secondary, #ffc107); color: #000; font-size: 0.65rem; padding: 3px 8px; border-radius: 12px; margin-right: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Nouveau</span>
                                <?php endif; ?>
                                <strong style="font-size: 1.05rem;"><?= $sujet ?></strong>
                            </div>
                            <div class="msg-content-preview" title="<?= $messageBrut ?>">
                                <?= nl2br($messageBrut) ?>
                            </div>
                        </td>

                        <td style="vertical-align: middle;">
                            <div class="action-group">

                                <a href="mailto:<?= $email ?>" class="btn-action btn-reply" title="Répondre par email">
                                    <i class="fas fa-reply"></i>
                                </a>

                                <?php if($isUnread): ?>
                                    <a href="?read=<?= $m['id'] ?>&csrf_token=<?= $_SESSION['admin_csrf_token'] ?>" class="btn-action btn-read" title="Marquer comme lu">
                                        <i class="fas fa-check"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-action btn-disabled" title="Message déjà lu">
                                        <i class="fas fa-check-double"></i>
                                    </span>
                                <?php endif; ?>

                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce message ?');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
                                    <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Supprimer le message">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>