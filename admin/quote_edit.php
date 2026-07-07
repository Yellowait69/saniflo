<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/PlanningLogic.php'; // IMPORT DE LA SYNCHRONISATION AGENDA

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: quotes.php"); exit; }

$msg = '';
$logic = new PlanningLogic($pdo); // Initialisation du service Agenda

// --- FETCH INITIAL (Pour comparer l'ancienne date avec la nouvelle) ---
$stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = ?");
$stmt->execute([$id]);
$quoteActuel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quoteActuel) die("Dossier introuvable");

// --- UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // === VÉRIFICATION CSRF GLOBALE POUR CE FICHIER ===
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité CSRF : L'action a été bloquée.");
    }
    // =================================================

    // Concaténation date + heure
    $fullDate = $_POST['date'] . ' ' . $_POST['time'] . ':00';
    $newStatus = $_POST['status'];

    // ==========================================================
    // LOGIQUE DE SYNCHRONISATION GOOGLE AGENDA
    // ==========================================================

    // 1. Si la DATE ou l'HEURE a été modifiée
    if ($fullDate !== $quoteActuel['appointment_date']) {
        // A. Supprimer l'ancien créneau
        $oldDateOnly = date('Y-m-d', strtotime($quoteActuel['appointment_date']));
        $oldTimeOnly = date('H:i', strtotime($quoteActuel['appointment_date']));
        $eventId = $logic->findEventId($oldDateOnly, $oldTimeOnly, $quoteActuel['lastname']);
        if ($eventId) { $logic->deleteEvent($eventId); }

        // B. Créer le nouveau créneau (sauf si on est en train d'annuler)
        if ($newStatus !== 'annulé') {
            $fullAddress = "{$_POST['billing_street']}, {$_POST['zip']} {$_POST['billing_city']}";
            $logic->addEvent([
                'summary' => "Déplacé (Admin): {$_POST['lastname']} {$_POST['firstname']}",
                'location' => $fullAddress,
                'description' => "Modifié par l'administrateur. Appareil: {$_POST['device_model']}",
                'date' => $_POST['date'],
                'time' => $_POST['time']
            ]);
        }
    }
    // 2. Si le statut passe à ANNULÉ (sans changer la date)
    elseif ($newStatus === 'annulé' && $quoteActuel['status'] !== 'annulé') {
        $dateOnly = date('Y-m-d', strtotime($quoteActuel['appointment_date']));
        $timeOnly = date('H:i', strtotime($quoteActuel['appointment_date']));
        $eventId = $logic->findEventId($dateOnly, $timeOnly, $quoteActuel['lastname']);
        if ($eventId) { $logic->deleteEvent($eventId); }
    }
    // ==========================================================

    // Requête de mise à jour BDD
    $sql = "UPDATE quote_requests SET 
        firstname=?, lastname=?, email=?, phone=?,
        billing_street=?, billing_city=?, zip=?,
        device_brand=?, device_model=?, device_serial=?,
        appointment_date=?, total_price_htva=?, status=?, description=?
        WHERE id=?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['firstname'], $_POST['lastname'], $_POST['email'], $_POST['phone'],
        $_POST['billing_street'], $_POST['billing_city'], $_POST['zip'],
        $_POST['device_brand'], $_POST['device_model'], $_POST['device_serial'],
        $fullDate, $_POST['total_price_htva'], $newStatus, $_POST['description'],
        $id
    ]);

    $msg = "Dossier mis à jour avec succès et synchronisé avec l'Agenda.";

    // Recharger les données fraîchement enregistrées
    $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = ?");
    $stmt->execute([$id]);
    $quoteActuel = $stmt->fetch(PDO::FETCH_ASSOC);
}

// On assigne pour l'affichage
$quote = $quoteActuel;

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

    <?php if($msg): ?><div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px;"><i class="fas fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>

    <form method="POST" class="crud-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">

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

                <div class="form-group" style="background:#e3f2fd; padding:15px; border-radius:8px; border:1px solid #90caf9;">
                    <label style="color:#0277bd;"><i class="fab fa-google"></i> Date RDV</label>
                    <input type="date" name="date" value="<?= $dateVal ?>">

                    <label style="color:#0277bd; margin-top:10px;"><i class="far fa-clock"></i> Heure</label>
                    <input type="time" name="time" value="<?= $timeVal ?>">
                    <small style="display:block; margin-top:5px; color:#1565c0;">La modification de la date mettra à jour Google Agenda.</small>
                </div>

                <div class="form-group" style="margin-top:15px;">
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
                <i class="fas fa-save"></i> Sauvegarder et Synchroniser
            </button>
        </div>
    </form>
</div>
</body>
</html>