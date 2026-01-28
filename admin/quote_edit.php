<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: quotes.php"); exit; }

$msg = '';

// --- UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE quote_requests SET 
        firstname=?, lastname=?, email=?, phone=?,
        billing_street=?, billing_city=?, zip=?,
        device_brand=?, device_model=?, device_serial=?,
        appointment_date=?, total_price_htva=?, status=?, description=?
        WHERE id=?";

    // Concaténation date + heure si modifiées séparément
    $fullDate = $_POST['date'] . ' ' . $_POST['time'];

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['firstname'], $_POST['lastname'], $_POST['email'], $_POST['phone'],
        $_POST['billing_street'], $_POST['billing_city'], $_POST['zip'],
        $_POST['device_brand'], $_POST['device_model'], $_POST['device_serial'],
        $fullDate, $_POST['total_price_htva'], $_POST['status'], $_POST['description'],
        $id
    ]);
    $msg = "Dossier mis à jour avec succès.";
}

// --- FETCH ---
$stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = ?");
$stmt->execute([$id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) die("Dossier introuvable");

// Séparation date/heure pour l'affichage (Gestion sécurisée)
$dateVal = '';
$timeVal = '';
if (!empty($quote['appointment_date'])) {
    try {
        $dt = new DateTime($quote['appointment_date']);
        $dateVal = $dt->format('Y-m-d');
        $timeVal = $dt->format('H:i');
    } catch (Exception $e) {
        // En cas d'erreur de date, on laisse vide
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Édition Dossier #<?= htmlspecialchars($quote['id'] ?? '') ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
    <h2><a href="quotes.php"><i class="fas fa-arrow-left"></i></a> Édition Dossier #<?= htmlspecialchars($quote['id'] ?? '') ?></h2>

    <?php if($msg): ?><div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px;"><?= $msg ?></div><?php endif; ?>

    <form method="POST" class="crud-form">

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">

            <div>
                <h3 style="color:var(--primary); border-bottom:2px solid var(--secondary); padding-bottom:10px; margin-bottom:20px;">Client</h3>

                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="firstname" value="<?= htmlspecialchars($quote['firstname'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="lastname" value="<?= htmlspecialchars($quote['lastname'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($quote['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Tél</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($quote['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Rue</label>
                    <input type="text" name="billing_street" value="<?= htmlspecialchars($quote['billing_street'] ?? '') ?>">
                </div>

                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label style="font-weight:600; font-size:0.9rem; margin-bottom:5px; display:block;">CP</label>
                        <input type="text" name="zip" value="<?= htmlspecialchars($quote['zip'] ?? '') ?>">
                    </div>
                    <div style="flex:2;">
                        <label style="font-weight:600; font-size:0.9rem; margin-bottom:5px; display:block;">Ville</label>
                        <input type="text" name="billing_city" value="<?= htmlspecialchars($quote['billing_city'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div>
                <h3 style="color:var(--primary); border-bottom:2px solid var(--secondary); padding-bottom:10px; margin-bottom:20px;">Technique & RDV</h3>

                <div class="form-group">
                    <label>Date RDV</label>
                    <input type="date" name="date" value="<?= $dateVal ?>">
                </div>
                <div class="form-group">
                    <label>Heure</label>
                    <input type="time" name="time" value="<?= $timeVal ?>">
                </div>

                <div class="form-group">
                    <label>Marque</label>
                    <input type="text" name="device_brand" value="<?= htmlspecialchars($quote['device_brand'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Modèle</label>
                    <input type="text" name="device_model" value="<?= htmlspecialchars($quote['device_model'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Série</label>
                    <input type="text" name="device_serial" value="<?= htmlspecialchars($quote['device_serial'] ?? '') ?>">
                </div>

                <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">

                <div class="form-group">
                    <label>Statut</label>
                    <select name="status">
                        <option value="nouveau" <?= ($quote['status'] ?? '') == 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                        <option value="traité" <?= ($quote['status'] ?? '') == 'traité' ? 'selected' : '' ?>>Traité / Archivé</option>
                        <option value="annulé" <?= ($quote['status'] ?? '') == 'annulé' ? 'selected' : '' ?>>Annulé</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prix HTVA (€)</label>
                    <input type="number" step="0.01" name="total_price_htva" value="<?= htmlspecialchars($quote['total_price_htva'] ?? '') ?>" style="font-weight:bold; color:var(--primary);">
                </div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <label style="font-weight:600; margin-bottom:8px; display:block;">Notes / Description</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($quote['description'] ?? '') ?></textarea>
        </div>

        <div style="margin-top:30px; text-align:right;">
            <button type="submit" class="btn-admin" style="width:auto; padding:12px 40px;">
                <i class="fas fa-save"></i> Sauvegarder les modifications
            </button>
        </div>
    </form>
</div>
</body>
</html>