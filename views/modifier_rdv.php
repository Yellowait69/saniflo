<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer mon rendez-vous - Saniflo SRL</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<main style="padding: 150px 20px; text-align: center; background: #f8f9fa; min-height: 70vh;">
    <div class="container" style="max-width: 750px; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">

        <i class="fas fa-calendar-alt" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 20px;"></i>

        <?php if (!empty($message_status)): ?>
            <?= $message_status ?>
            <div style="margin-top: 30px;">
                <a href="index.php" class="btn-primary" style="padding: 10px 20px; text-decoration: none;">Retour à l'accueil</a>
            </div>

        <?php elseif (isset($rdv['status']) && $rdv['status'] === 'annulé'): ?>
            <h2 style="color: #d32f2f;">Rendez-vous annulé</h2>
            <p>Ce rendez-vous a déjà été annulé. Il n'est plus actif dans notre système.</p>
            <div style="margin-top: 30px;">
                <a href="index.php" class="btn-primary" style="padding: 10px 20px; text-decoration: none;">Retour à l'accueil</a>
            </div>

        <?php elseif (!empty($peutModifier) && $peutModifier): ?>
            <h2 id="gerer-intervention" style="scroll-margin-top: 350px;">Gérer votre intervention</h2>
            <p>Votre rendez-vous actuel est prévu le <strong><?= date('d/m/Y', strtotime($rdv['appointment_date'])) ?></strong> à <strong><?= date('H:i', strtotime($rdv['appointment_date'])) ?></strong>.</p>

            <div style="display: flex; gap: 20px; margin-top: 30px; justify-content: center; flex-wrap: wrap;">

                <form action="index.php?page=modifier_rdv&token=<?= htmlspecialchars($token ?? '') ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler définitivement ce rendez-vous ?');" style="flex: 1; min-width: 250px;">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" style="width: 100%; padding: 15px; background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                        <i class="fas fa-times-circle"></i> Annuler le RDV
                    </button>
                </form>

                <button type="button" onclick="loadDates('<?= htmlspecialchars($rdv['zip'] ?? $rdv['billing_zip'] ?? '') ?>');" style="flex: 1; min-width: 250px; padding: 15px; background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                    <i class="fas fa-calendar-plus"></i> Choisir une nouvelle date
                </button>
            </div>

            <div id="reschedule-section" style="display: none; margin-top: 40px; padding-top: 30px; border-top: 2px dashed #eee; text-align: left;">
                <h3 style="color: var(--primary-dark); text-align: center; margin-bottom: 20px;">Nouvelle disponibilité (CP : <?= htmlspecialchars($rdv['zip'] ?? $rdv['billing_zip'] ?? '') ?>)</h3>

                <form action="index.php?page=modifier_rdv&token=<?= htmlspecialchars($token ?? '') ?>" method="POST">
                    <input type="hidden" name="action" value="reschedule">

                    <div id="mod_loader" style="text-align:center; padding: 20px; display:none;">
                        <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; color: var(--primary-blue);"></i>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 8px;">1. Choisissez une nouvelle date</label>
                        <select id="mod_date_select" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
                            <option value="">Chargement...</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 8px;">2. Choisissez une nouvelle heure</label>
                        <select id="mod_time_slots" disabled style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background: #f4f7f6;">
                            <option value="">-- Choisissez d'abord une date --</option>
                        </select>
                    </div>

                    <input type="hidden" name="new_date" id="mod_final_date">
                    <input type="hidden" name="new_time" id="mod_final_time">

                    <button type="submit" id="mod_confirm_btn" class="btn-primary" disabled style="width: 100%; padding: 15px; opacity: 0.5; border: none; font-weight:bold; cursor:pointer;">
                        Confirmer le déplacement
                    </button>
                </form>
            </div>

        <?php else: ?>
            <h2 style="color: #d32f2f;">Délai de modification dépassé</h2>
            <p>Votre rendez-vous est prévu le <strong><?= date('d/m/Y', strtotime($rdv['appointment_date'])) ?></strong>.</p>
            <div style="background: #ffebee; padding: 20px; border-radius: 10px; margin-top: 20px; color: #c62828;">
                <p><i class="fas fa-exclamation-triangle"></i> Conformément à nos conditions, les modifications en ligne ne sont plus possibles moins de 7 jours avant l'intervention.</p>
            </div>
            <p style="margin-top: 20px;">Veuillez nous contacter directement par téléphone pour toute urgence : <br><strong>0495 50 17 17</strong></p>
        <?php endif; ?>

    </div>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
    let dispoData = [];

    function loadDates(zip) {
        document.getElementById('reschedule-section').style.display = 'block';
        document.getElementById('mod_loader').style.display = 'block';
        const dateSelect = document.getElementById('mod_date_select');

        fetch('api_slots.php?zip=' + zip)
            .then(res => res.json())
            .then(data => {
                document.getElementById('mod_loader').style.display = 'none';

                if (data.error) {
                    dateSelect.innerHTML = `<option value="">Erreur : ${data.error}</option>`;
                } else if (data.days && data.days.length > 0) {
                    dispoData = data.days;
                    dateSelect.innerHTML = '<option value="">-- Choisissez une date --</option>';
                    data.days.forEach(day => {
                        let opt = document.createElement('option');
                        opt.value = day.date_iso;
                        opt.text = `${day.date_pretty} (${day.slots.length} créneaux)`;
                        dateSelect.add(opt);
                    });
                } else {
                    dateSelect.innerHTML = '<option value="">Aucune disponibilité</option>';
                }
            })
            .catch(err => {
                document.getElementById('mod_loader').style.display = 'none';
                dateSelect.innerHTML = '<option value="">Erreur technique</option>';
            });
    }

    document.getElementById('mod_date_select').addEventListener('change', function() {
        const timeSelect = document.getElementById('mod_time_slots');
        const confirmBtn = document.getElementById('mod_confirm_btn');
        timeSelect.innerHTML = '<option value="">-- Choisissez une heure --</option>';
        timeSelect.disabled = true;
        timeSelect.style.background = '#f4f7f6';
        confirmBtn.disabled = true;
        confirmBtn.style.opacity = '0.5';

        const dayData = dispoData.find(d => d.date_iso === this.value);
        if (dayData && dayData.slots.length > 0) {
            timeSelect.disabled = false;
            timeSelect.style.background = '#fff';
            dayData.slots.forEach(slot => {
                let opt = document.createElement('option');
                opt.value = slot;
                opt.text = slot;
                timeSelect.add(opt);
            });
            document.getElementById('mod_final_date').value = this.value;
        }
    });

    document.getElementById('mod_time_slots').addEventListener('change', function() {
        const confirmBtn = document.getElementById('mod_confirm_btn');
        if (this.value) {
            document.getElementById('mod_final_time').value = this.value;
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
        } else {
            confirmBtn.disabled = true;
            confirmBtn.style.opacity = '0.5';
        }
    });
</script>
</body>
</html>