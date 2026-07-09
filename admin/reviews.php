<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

$editReview = null;
$msg = '';
$msgType = 'success';

// --- GESTION DES MESSAGES DE RETOUR ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') { $msg = "Avis ajouté avec succès."; }
    elseif ($_GET['msg'] === 'updated') { $msg = "Avis mis à jour avec succès."; }
    elseif ($_GET['msg'] === 'deleted') { $msg = "Avis supprimé avec succès."; }
    elseif ($_GET['msg'] === 'csrf') { $msg = "Erreur de sécurité (CSRF)."; $msgType = "error"; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // VÉRIFICATION CSRF GLOBALE
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
        header("Location: reviews.php?msg=csrf");
        exit;
    }

    // --- SUPPRESSION (DELETE) ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        header("Location: reviews.php?msg=deleted");
        exit;
    }

    // --- AJOUT ET MISE À JOUR (CREATE / UPDATE) ---
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $id = $_POST['review_id'] ?? '';
        $author_name = htmlspecialchars(trim($_POST['author_name'] ?? ''));
        $avatar_color = $_POST['avatar_color'] ?? '#4285F4';
        $rating = (int)($_POST['rating'] ?? 5);
        $review_date = $_POST['review_date'] ?? date('Y-m-d');
        $review_text = htmlspecialchars(trim($_POST['review_text'] ?? ''));

        if (empty($id)) {
            // CREATE
            $stmt = $pdo->prepare("INSERT INTO reviews (author_name, avatar_color, rating, review_date, review_text) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$author_name, $avatar_color, $rating, $review_date, $review_text]);
            header("Location: reviews.php?msg=created");
            exit;
        } else {
            // UPDATE
            $stmt = $pdo->prepare("UPDATE reviews SET author_name=?, avatar_color=?, rating=?, review_date=?, review_text=? WHERE id=?");
            $stmt->execute([$author_name, $avatar_color, $rating, $review_date, $review_text, $id]);
            header("Location: reviews.php?msg=updated");
            exit;
        }
    }
}

// --- CHARGEMENT POUR ÉDITION (READ 1) ---
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editReview = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- LECTURE DE TOUS LES AVIS (READ ALL) ---
$reviews = $pdo->query("SELECT * FROM reviews ORDER BY review_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Avis Clients</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <h2>Gestion des Avis Google</h2>

    <?php if($msg): ?>
        <div style="background: <?= $msgType === 'error' ? '#f8d7da' : '#d4edda' ?>; color: <?= $msgType === 'error' ? '#721c24' : '#155724' ?>; padding:15px; border-radius:5px; margin-bottom:20px; border: 1px solid <?= $msgType === 'error' ? '#f5c6cb' : '#c3e6cb' ?>;">
            <i class="fas <?= $msgType === 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #ddd;">
        <h3 style="margin-top:0; color:var(--primary);"><?= $editReview ? 'Modifier l\'avis' : 'Ajouter un nouvel avis' ?></h3>

        <form method="POST" class="crud-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="review_id" value="<?= $editReview['id'] ?? '' ?>">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="form-group">
                    <label>Nom du client</label>
                    <input type="text" name="author_name" value="<?= htmlspecialchars($editReview['author_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Date de l'avis</label>
                    <input type="date" name="review_date" value="<?= htmlspecialchars($editReview['review_date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Couleur de l'avatar (Style Google)</label>
                    <select name="avatar_color" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                        <option value="#4285F4" <?= ($editReview['avatar_color'] ?? '') === '#4285F4' ? 'selected' : '' ?>>Bleu (#4285F4)</option>
                        <option value="#34A853" <?= ($editReview['avatar_color'] ?? '') === '#34A853' ? 'selected' : '' ?>>Vert (#34A853)</option>
                        <option value="#FBBC05" <?= ($editReview['avatar_color'] ?? '') === '#FBBC05' ? 'selected' : '' ?>>Jaune (#FBBC05)</option>
                        <option value="#EA4335" <?= ($editReview['avatar_color'] ?? '') === '#EA4335' ? 'selected' : '' ?>>Rouge (#EA4335)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Note sur 5</label>
                    <input type="number" name="rating" min="1" max="5" value="<?= htmlspecialchars($editReview['rating'] ?? 5) ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label>Texte de l'avis</label>
                <textarea name="review_text" rows="4" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; resize:vertical;"><?= htmlspecialchars($editReview['review_text'] ?? '') ?></textarea>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-admin" style="padding: 10px 20px; font-weight:bold;">
                    <i class="fas fa-save"></i> <?= $editReview ? 'Mettre à jour' : 'Ajouter l\'avis' ?>
                </button>
                <?php if($editReview): ?>
                    <a href="reviews.php" style="margin-left: 15px; color: var(--primary); text-decoration:none;"><i class="fas fa-times"></i> Annuler la modification</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Note</th>
                <th>Avis</th>
                <th style="text-align: right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($reviews as $r): ?>
                <tr>
                    <td style="white-space: nowrap;"><strong><?= date('d/m/Y', strtotime($r['review_date'])) ?></strong></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="background-color: <?= htmlspecialchars($r['avatar_color']) ?>; color:white; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:1.1rem;">
                                <?= strtoupper(substr(htmlspecialchars($r['author_name']), 0, 1)) ?>
                            </div>
                            <strong><?= htmlspecialchars($r['author_name']) ?></strong>
                        </div>
                    </td>
                    <td style="color: #FBBC05;">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i class="<?= $i <= $r['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                        <?php endfor; ?>
                    </td>
                    <td><small style="color:#555;">"<?= htmlspecialchars(substr($r['review_text'], 0, 80)) ?><?= strlen($r['review_text']) > 80 ? '...' : '' ?>"</small></td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="?edit=<?= $r['id'] ?>" class="btn-action btn-edit" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn-action btn-delete" title="Supprimer" style="cursor:pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($reviews)): ?>
                <tr><td colspan="5" style="text-align:center; padding: 20px; color:#777;">Aucun avis enregistré pour le moment.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>