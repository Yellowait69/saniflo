<?php
require_once 'auth.php';
$pdo = require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/PlanningLogic.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: quotes.php");
    exit;
}

$msg = '';
$error = '';
$logic = new PlanningLogic($pdo);

// --- FETCH INITIAL ---
$stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = ?");
$stmt->execute([$id]);
$quoteActuel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quoteActuel) {
    die("Dossier introuvable.");
}

// --- TRAITEMENT DU FORMULAIRE (UPDATE OU DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. VÉRIFICATION CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token'])) {
        die("Erreur de sécurité CSRF : L'action a été bloquée.");
    }

    $action = $_POST['action'] ?? 'update';

    // ==========================================================
    // ACTION : SUPPRESSION DÉFINITIVE (DELETE)
    // ==========================================================
    if ($action === 'delete') {
        try {
            // Suppression de l'Agenda Google si une date existait
            if (!empty($quoteActuel['appointment_date'])) {
                $oldDateOnly = date('Y-m-d', strtotime($quoteActuel['appointment_date']));
                $oldTimeOnly = date('H:i', strtotime($quoteActuel['appointment_date']));
                $eventId = $logic->findEventId($oldDateOnly, $oldTimeOnly, $quoteActuel['lastname']);
                if ($eventId) {
                    $logic->deleteEvent($eventId);
                }
            }

            // Suppression en Base de Données
            $stmt = $pdo->prepare("DELETE FROM quote_requests WHERE id = ?");
            $stmt->execute([$id]);

            // Redirection vers la liste
            header("Location: quotes.php?msg=deleted");
            exit;

        } catch (Exception $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }

    // ==========================================================
    // ACTION : MISE À JOUR (UPDATE)
    // ==========================================================
    elseif ($action === 'update') {

        // Nettoyage des données entrantes
        $firstname = htmlspecialchars(trim($_POST['firstname'] ?? ''));
        $lastname = htmlspecialchars(trim($_POST['lastname'] ?? ''));
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
        $billing_street = htmlspecialchars(trim($_POST['billing_street'] ?? ''));
        $billing_city = htmlspecialchars(trim($_POST['billing_city'] ?? ''));
        $zip = htmlspecialchars(trim($_POST['zip'] ?? '')); // La variable du formulaire reste "zip"
        $device_brand = htmlspecialchars(trim($_POST['device_brand'] ?? ''));
        $device_model = htmlspecialchars(trim($_POST['device_model'] ?? ''));
        $device_serial = htmlspecialchars(trim($_POST['device_serial'] ?? ''));
        $status = $_POST['status'] ?? 'nouveau';
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $total_price_htva = (float)($_POST['total_price_htva'] ?? 0);

        // Gestion sécurisée de la date
        $fullDate = null;
        if (!empty($_POST['date']) && !empty($_POST['time'])) {
            $fullDate = $_POST['date'] . ' ' . $_POST['time'] . ':00';
        }

        try {
            // --- 1. MISE À JOUR BDD EN PREMIER ---
            $sql = "UPDATE quote_requests SET 
                firstname=?, lastname=?, email=?, phone=?,
                billing_street=?, billing_city=?, billing_zip=?,
                device_brand=?, device_model=?, device_serial=?,
                appointment_date=?, total_price_htva=?, status=?, description=?
                WHERE id=?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $firstname, $lastname, $email, $phone,
                $billing_street, $billing_city, $zip, // On injecte la variable $zip dans billing_zip
                $device_brand, $device_model, $device_serial,
                $fullDate, $total_price_htva, $status, $description,
                $id
            ]);

            $msg = "Dossier mis à jour avec succès.";

            // --- 2. SYNCHRONISATION GOOGLE AGENDA (Seulement si BDD réussie) ---
            try {
                if ($fullDate !== $quoteActuel['appointment_date']) {

                    // A. Supprimer l'ancien créneau s'il existait
                    if (!empty($quoteActuel['appointment_date'])) {
                        $oldDateOnly = date('Y-m-d', strtotime($quoteActuel['appointment_date']));
                        $oldTimeOnly = date('H:i', strtotime($quoteActuel['appointment_date']));
                        $eventId = $logic->findEventId($oldDateOnly, $oldTimeOnly, $quoteActuel['lastname']);
                        if ($eventId) { $logic->deleteEvent($eventId); }
                    }

                    // B. Créer le nouveau créneau AVEC RECONSTRUCTION DÉTAILLÉE
                    if ($fullDate && $status !== 'annulé') {
                        $fullAddress = "$billing_street, $zip $billing_city";

                        // Récupération des infos de société pour Google
                        $is_company = $quoteActuel['is_company'] ?? 0;
                        $company_name = $quoteActuel['company_name'] ?? '';
                        $vat_number = $quoteActuel['vat_number'] ?? '';
                        $companyStr = ($is_company && !empty($company_name)) ? "🏢 Société: {$company_name} (TVA: {$vat_number})\n" : "";

                        // ---------------------------------------------------------
                        // RECONSTRUCTION DES DONNÉES FINANCIÈRES ET TECHNIQUES D'ORIGINE
                        // ---------------------------------------------------------
                        $vat_regime = (int)($quoteActuel['vat_regime'] ?? 21);
                        $payment_method = $quoteActuel['payment_method'] ?? 'after';
                        $device_year = $quoteActuel['device_year'] ?? '';
                        $device_kw = $quoteActuel['device_kw'] ?? '';

                        // Recalcul exact de la TVA et du Total
                        $montantTVA = $total_price_htva * ($vat_regime / 100);
                        $sousTotalTTC = $total_price_htva + $montantTVA;
                        $fraisAdmin = ($payment_method === 'after' && $total_price_htva > 0) ? ($sousTotalTTC * 0.03) : 0;
                        $priceTTC = $sousTotalTTC + $fraisAdmin;

                        // Gestion de l'adresse de chantier si différente de la facturation
                        $worksite_same = $quoteActuel['worksite_same_as_billing'] ?? 1;
                        $chantierStr = "Identique à la facturation";
                        if (!$worksite_same) {
                            $wStreet = $quoteActuel['worksite_street'] ?? '';
                            $wBox = $quoteActuel['worksite_box'] ? "Bte " . $quoteActuel['worksite_box'] : '';
                            $wZip = $quoteActuel['worksite_zip'] ?? '';
                            $wCity = $quoteActuel['worksite_city'] ?? '';
                            $chantierStr = trim("$wStreet $wBox, $wZip $wCity");
                        }

                        // ---------------------------------------------------------
                        // CRÉATION DE LA DESCRIPTION (Identique à HomeController)
                        // ---------------------------------------------------------
                        $googleDesc = "🔄 INTERVENTION MODIFIÉE (ADMIN)\n\n";

                        $googleDesc .= "🛠️ DÉTAILS DE L'INTERVENTION\n";
                        $googleDesc .= "------------------------------------------------\n";
                        $googleDesc .= "Appareil : $device_brand $device_model" . ($device_year ? " (Année: $device_year)" : "") . ($device_kw ? " - $device_kw kW" : "") . "\n";
                        $googleDesc .= "Remarques client : " . ($description ?: "Aucune remarque") . "\n\n";

                        $googleDesc .= "👤 COORDONNÉES CLIENT\n";
                        $googleDesc .= "------------------------------------------------\n";
                        $googleDesc .= "Nom : $firstname $lastname\n";
                        $googleDesc .= $companyStr;
                        $googleDesc .= "Email : $email\n";
                        $googleDesc .= "Téléphone : $phone\n";
                        $googleDesc .= "Facturation : $fullAddress\n";
                        if (!$worksite_same) {
                            $googleDesc .= "Chantier : $chantierStr\n";
                        }
                        $googleDesc .= "\n";

                        $googleDesc .= "💳 PAIEMENT ET TARIFICATION\n";
                        $googleDesc .= "------------------------------------------------\n";
                        if ($total_price_htva > 0) {
                            $methodePaiementTexte = ($payment_method === 'stripe') ? "✅ DÉJÀ PAYÉ EN LIGNE" : "⚠️ À RÉGLER SUR PLACE (Bancontact / Cash)";
                            $googleDesc .= "Paiement : $methodePaiementTexte\n";
                            $googleDesc .= "Dossier : " . strtoupper($status) . "\n";
                            $googleDesc .= "Prix HTVA : " . number_format($total_price_htva, 2, ',', ' ') . " €\n";
                            $googleDesc .= "TVA ($vat_regime%) : " . number_format($montantTVA, 2, ',', ' ') . " €\n";
                            if ($fraisAdmin > 0) {
                                $googleDesc .= "Frais admin (3%) : " . number_format($fraisAdmin, 2, ',', ' ') . " €\n";
                            }
                            $googleDesc .= "TOTAL À PAYER : " . number_format($priceTTC, 2, ',', ' ') . " €\n";
                        } else {
                            $googleDesc .= "Statut : DEVIS / SUR PLACE\n";
                        }

                        // Envoi à Google Agenda
                        $locationEvent = (!$worksite_same) ? $chantierStr : $fullAddress;

                        $logic->addEvent([
                            'summary' => "🔄 Modifié: $lastname $firstname",
                            'location' => $locationEvent,
                            'description' => $googleDesc,
                            'date' => $_POST['date'],
                            'time' => $_POST['time']
                        ]);
                    }
                } elseif ($status === 'annulé' && $quoteActuel['status'] !== 'annulé' && !empty($quoteActuel['appointment_date'])) {
                    // Cas : On annule sans changer la date
                    $dateOnly = date('Y-m-d', strtotime($quoteActuel['appointment_date']));
                    $timeOnly = date('H:i', strtotime($quoteActuel['appointment_date']));
                    $eventId = $logic->findEventId($dateOnly, $timeOnly, $quoteActuel['lastname']);
                    if ($eventId) { $logic->deleteEvent($eventId); }
                }

                $msg .= " Synchronisation complète avec l'Agenda réussie.";

            } catch (Exception $e) {
                // Erreur de l'API Google
                $error = "Le dossier a bien été mis à jour dans la base de données, mais une erreur de synchronisation Google est survenue : " . $e->getMessage();
            }

        } catch (PDOException $e) {
            // Erreur fatale de base de données (ex: Colonne introuvable, erreur de syntaxe)
            $error = "Erreur fatale lors de l'enregistrement en base de données : " . $e->getMessage();
        }

        // Recharger les données fraîches
        $stmt = $pdo->prepare("SELECT * FROM quote_requests WHERE id = ?");
        $stmt->execute([$id]);
        $quoteActuel = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$quote = $quoteActuel;
$dateVal = '';
$timeVal = '';
if (!empty($quote['appointment_date'])) {
    try {
        $dt = new DateTime($quote['appointment_date']);
        $dateVal = $dt->format('Y-m-d');
        $timeVal = $dt->format('H:i');
    } catch (Exception $e) {
        // Silencieux
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Édition Dossier #<?= htmlspecialchars($quote['id']) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><a href="quotes.php" style="color:var(--primary); text-decoration:none;"><i class="fas fa-arrow-left"></i> Retour</a> | Dossier #<?= htmlspecialchars($quote['id']) ?></h2>

        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer DÉFINITIVEMENT ce dossier ? Cette action est irréversible et supprimera le rendez-vous de Google Agenda.');">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" style="background:#c62828; color:white; border:none; padding:10px 15px; border-radius:5px; cursor:pointer; font-weight:bold;">
                <i class="fas fa-trash"></i> Supprimer définitivement
            </button>
        </form>
    </div>

    <?php if($msg): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> <?= $msg ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="crud-form">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['admin_csrf_token'] ?>">
        <input type="hidden" name="action" value="update">

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
            <div>
                <h3 style="color:var(--primary); border-bottom:2px solid var(--secondary); padding-bottom:10px; margin-bottom:20px;">Client</h3>

                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="firstname" value="<?= htmlspecialchars($quote['firstname'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="lastname" value="<?= htmlspecialchars($quote['lastname'] ?? '') ?>" required>
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
                    <label>Rue & Numéro</label>
                    <input type="text" name="billing_street" value="<?= htmlspecialchars($quote['billing_street'] ?? '') ?>">
                </div>

                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label style="font-weight:600; font-size:0.9rem; margin-bottom:5px; display:block;">Code Postal</label>
                        <input type="text" name="zip" value="<?= htmlspecialchars($quote['billing_zip'] ?? $quote['zip'] ?? '') ?>">
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
                    <small style="display:block; margin-top:5px; color:#1565c0;">La modification ou la suppression de la date mettra à jour Google Agenda.</small>
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
                    <label>Numéro de Série</label>
                    <input type="text" name="device_serial" value="<?= htmlspecialchars($quote['device_serial'] ?? '') ?>">
                </div>

                <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">

                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Statut</label>
                        <select name="status" style="width: 100%; padding: 8px;">
                            <option value="nouveau" <?= ($quote['status'] === 'nouveau') ? 'selected' : '' ?>>Nouveau</option>
                            <option value="en_attente" <?= ($quote['status'] === 'en_attente') ? 'selected' : '' ?>>En attente (Stripe)</option>
                            <option value="traité" <?= ($quote['status'] === 'traité') ? 'selected' : '' ?>>Traité / Archivé</option>
                            <option value="annulé" <?= ($quote['status'] === 'annulé') ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Prix HTVA (€)</label>
                        <input type="number" step="0.01" name="total_price_htva" value="<?= htmlspecialchars($quote['total_price_htva'] ?? '0') ?>" style="font-weight:bold; color:var(--primary); width: 100%;">
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <label style="font-weight:600; margin-bottom:8px; display:block;">Notes / Description (Inclus dans l'Agenda Google)</label>
            <textarea name="description" rows="5" style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"><?= htmlspecialchars($quote['description'] ?? '') ?></textarea>
        </div>

        <div style="margin-top:30px; text-align:right;">
            <button type="submit" class="btn-admin" style="width:auto; padding:15px 40px; font-size: 1.1rem; background: var(--primary); color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-save"></i> Sauvegarder et Synchroniser
            </button>
        </div>
    </form>
</div>
</body>
</html>