<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

// ACTION : Supprimer
if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM quote_requests WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    header("Location: quotes.php?msg=deleted"); exit;
}

// ACTION : Traiter (Archiver)
if (isset($_GET['archive']) && is_numeric($_GET['archive'])) {
    $stmt = $pdo->prepare("UPDATE quote_requests SET status = 'traité' WHERE id = ?");
    $stmt->execute([$_GET['archive']]);
    header("Location: quotes.php"); exit;
}

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

<?php include 'nav.php'; ?>

<div class="container">
    <h2>Gestion des demandes d'intervention</h2>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>Date RDV</th>
                <th>Client</th>
                <th>Appareil</th>
                <th>Prix</th>
                <th>Statut</th>
                <th style="text-align: right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($quotes as $q):
                // Préparation des variables pour éviter les erreurs PHP 8.1+ sur les valeurs NULL
                $dateRdv = $q['appointment_date'] ? date('d/m/Y', strtotime($q['appointment_date'])) : 'Date non définie';
                $heureRdv = $q['appointment_date'] ? date('H:i', strtotime($q['appointment_date'])) : '';
                $nomClient = htmlspecialchars(($q['lastname'] ?? '') . ' ' . ($q['firstname'] ?? ''));
                $ville = htmlspecialchars($q['billing_city'] ?? '');
                $marque = htmlspecialchars($q['device_brand'] ?? '');
                $modele = htmlspecialchars($q['device_model'] ?? '');
                $prix = htmlspecialchars($q['total_price_htva'] ?? '0.00');
                ?>
                <tr style="<?= ($q['status'] ?? '') == 'nouveau' ? 'background:#fffbf0;' : '' ?>">
                    <td>
                        <strong><?= $dateRdv ?></strong><br>
                        <?= $heureRdv ?>
                    </td>
                    <td>
                        <strong><?= $nomClient ?></strong><br>
                        <small><?= $ville ?></small>
                    </td>
                    <td>
                        <?= $marque ?> <?= $modele ?>
                    </td>
                    <td>
                        <strong><?= $prix ?> €</strong>
                    </td>
                    <td>
                        <span class="badge <?= ($q['status'] ?? '') == 'nouveau' ? 'badge-new' : 'badge-done' ?>">
                            <?= ucfirst($q['status'] ?? 'inconnu') ?>
                        </span>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="quote_edit.php?id=<?= $q['id'] ?>" class="btn-action btn-edit" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer définitivement ce dossier ?');">
                            <input type="hidden" name="delete_id" value="<?= $q['id'] ?>">
                            <button type="submit" class="btn-action btn-delete" title="Supprimer" style="cursor:pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>

                        <?php if(($q['status'] ?? '') == 'nouveau'): ?>
                            <a href="?archive=<?= $q['id'] ?>" class="btn-action" title="Marquer comme traité"
                               style="background-color: var(--success); color: white;"
                               onclick="return confirm('Confirmer le traitement de ce dossier ?');">
                                <i class="fas fa-check"></i>
                            </a>
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