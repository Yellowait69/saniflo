<?php
session_start();

// Vérification de sécurité : Si non connecté, retour à la page de connexion (index.php)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$pdo = require_once __DIR__ . '/../config/db.php';

try {
    // ==========================================
    // 1. RÉCUPÉRATION DES STATISTIQUES GLOBALES (KPI)
    // ==========================================

    // --- CHIFFRE D'AFFAIRES HTVA ---
    $caGlobalQuery = $pdo->query("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé'");
    $caGlobal = $caGlobalQuery->fetchColumn() ?: 0;

    $currentMonth = date('Y-m');
    $caMensuelQuery = $pdo->prepare("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé' AND DATE_FORMAT(appointment_date, '%Y-%m') = ?");
    $caMensuelQuery->execute([$currentMonth]);
    $caMensuel = $caMensuelQuery->fetchColumn() ?: 0;

    // --- VISITEURS ---
    $visiteursGlobalQuery = $pdo->query("SELECT SUM(visits_count) FROM visitors");
    $visiteursGlobal = $visiteursGlobalQuery->fetchColumn() ?: 0;

    $visiteursMensuelQuery = $pdo->prepare("SELECT SUM(visits_count) FROM visitors WHERE DATE_FORMAT(visit_date, '%Y-%m') = ?");
    $visiteursMensuelQuery->execute([$currentMonth]);
    $visiteursMensuel = $visiteursMensuelQuery->fetchColumn() ?: 0;

    // --- RENDEZ-VOUS & MESSAGES ---
    $rdvGlobalQuery = $pdo->query("SELECT COUNT(*) FROM quote_requests");
    $rdvGlobal = $rdvGlobalQuery->fetchColumn() ?: 0;

    $rdvMensuelQuery = $pdo->prepare("SELECT COUNT(*) FROM quote_requests WHERE DATE_FORMAT(appointment_date, '%Y-%m') = ?");
    $rdvMensuelQuery->execute([$currentMonth]);
    $rdvMensuel = $rdvMensuelQuery->fetchColumn() ?: 0;

    $countNouveauxRdv = $pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'nouveau'")->fetchColumn();
    $countMsg = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();

    $tauxConversion = ($visiteursGlobal > 0) ? round(($rdvGlobal / $visiteursGlobal) * 100, 1) : 0;

    // ==========================================
    // 2. DONNÉES DYNAMIQUES POUR LES GRAPHIQUES (CHART.JS)
    // ==========================================

    // Gestion de la période demandée via l'URL (Par défaut : 6 mois)
    $range = $_GET['range'] ?? '6m';
    $rangeTitles = [
        '7d' => '7 derniers jours',
        '1m' => '30 derniers jours',
        '6m' => '6 derniers mois',
        '1y' => '12 derniers mois',
        '5y' => '5 dernières années',
        'all' => 'Depuis le début'
    ];
    $currentRangeTitle = $rangeTitles[$range] ?? $rangeTitles['6m'];

    $moisFr = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    $caLabels = []; $caData = [];
    $visLabels = []; $visData = [];

    // Configuration de l'échelle selon le filtre
    if ($range === '7d') { $interval = 'day'; $limit = 7; }
    elseif ($range === '1m') { $interval = 'day'; $limit = 30; }
    elseif ($range === '6m') { $interval = 'month'; $limit = 6; }
    elseif ($range === '1y') { $interval = 'month'; $limit = 12; }
    elseif ($range === '5y') { $interval = 'year'; $limit = 5; }
    elseif ($range === 'all') {
        $interval = 'year';
        // Trouver la date du tout premier RDV pour calculer le nombre d'années
        $minDateQuery = $pdo->query("SELECT MIN(appointment_date) FROM quote_requests WHERE status != 'annulé'");
        $minDate = $minDateQuery->fetchColumn() ?: date('Y-m-d');
        $limit = (int)date('Y') - (int)date('Y', strtotime($minDate)) + 1;
        if($limit < 1) $limit = 1;
    }

    // Boucle pour générer les points des graphiques
    for ($i = $limit - 1; $i >= 0; $i--) {
        if ($interval === 'day') {
            $dateVal = date('Y-m-d', strtotime("-$i days"));
            $label = date('d/m', strtotime("-$i days"));

            $stmtCA = $pdo->prepare("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé' AND DATE(appointment_date) = ?");
            $stmtCA->execute([$dateVal]);
            $caData[] = $stmtCA->fetchColumn() ?: 0;

            $stmtVis = $pdo->prepare("SELECT visits_count FROM visitors WHERE visit_date = ?");
            $stmtVis->execute([$dateVal]);
            $visData[] = $stmtVis->fetchColumn() ?: 0;

            $caLabels[] = $label;
            $visLabels[] = $label;

        } elseif ($interval === 'month') {
            $timestamp = strtotime("first day of -$i months");
            $dateVal = date('Y-m', $timestamp);
            $numMois = (int)date('n', $timestamp) - 1;
            $label = $moisFr[$numMois] . ' ' . date('y', $timestamp);

            $stmtCA = $pdo->prepare("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé' AND DATE_FORMAT(appointment_date, '%Y-%m') = ?");
            $stmtCA->execute([$dateVal]);
            $caData[] = $stmtCA->fetchColumn() ?: 0;

            $stmtVis = $pdo->prepare("SELECT SUM(visits_count) FROM visitors WHERE DATE_FORMAT(visit_date, '%Y-%m') = ?");
            $stmtVis->execute([$dateVal]);
            $visData[] = $stmtVis->fetchColumn() ?: 0;

            $caLabels[] = $label;
            $visLabels[] = $label;

        } elseif ($interval === 'year') {
            $dateVal = date('Y', strtotime("-$i years"));
            $label = $dateVal;

            $stmtCA = $pdo->prepare("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé' AND YEAR(appointment_date) = ?");
            $stmtCA->execute([$dateVal]);
            $caData[] = $stmtCA->fetchColumn() ?: 0;

            $stmtVis = $pdo->prepare("SELECT SUM(visits_count) FROM visitors WHERE YEAR(visit_date) = ?");
            $stmtVis->execute([$dateVal]);
            $visData[] = $stmtVis->fetchColumn() ?: 0;

            $caLabels[] = $label;
            $visLabels[] = $label;
        }
    }

    // Graphique 3 : Répartition des statuts (Filtré par la date sélectionnée)
    $statusCondition = "";
    if ($range === '7d') $statusCondition = "WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    elseif ($range === '1m') $statusCondition = "WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    elseif ($range === '6m') $statusCondition = "WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
    elseif ($range === '1y') $statusCondition = "WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    elseif ($range === '5y') $statusCondition = "WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)";

    $statutsQuery = $pdo->query("SELECT status, COUNT(*) as count FROM quote_requests $statusCondition GROUP BY status");
    $statutsLabels = [];
    $statutsData = [];
    while ($row = $statutsQuery->fetch(PDO::FETCH_ASSOC)) {
        $statutsLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
        $statutsData[] = $row['count'];
    }

    // ==========================================
    // 3. DONNÉES POUR LE TABLEAU DES DERNIERS RDV
    // ==========================================
    $lastRdvs = $pdo->query("SELECT * FROM quote_requests ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div style='color:red; padding:20px; text-align:center;'><h3>Erreur de connexion à la base de données</h3><p>" . $e->getMessage() . "</p></div>");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Saniflo Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Styles des badges pour le tableau */
        .badge-status { padding: 5px 10px; border-radius: 20px; color: #fff; font-size: 0.85rem; font-weight: bold; display: inline-block; text-align: center; min-width: 80px; }
        .bg-new { background-color: #2196F3; }
        .bg-pending { background-color: #FFC107; color: #000; }
        .bg-done { background-color: #4CAF50; }
        .bg-canceled { background-color: #F44336; }
        .client-type-icon { color: #888; margin-right: 5px; }

        /* Styles spécifiques au Dashboard */
        .dashboard-container { padding: 20px; max-width: 1400px; margin: 0 auto; }

        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .kpi-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid var(--primary); display: flex; align-items: center; justify-content: space-between; transition: transform 0.2s;}
        .kpi-card:hover { transform: translateY(-5px); }
        .kpi-info h3 { margin: 0; font-size: 0.9rem; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
        .kpi-info .kpi-value { margin: 5px 0 0 0; font-size: 2rem; font-weight: 700; color: #343a40; }
        .kpi-info .kpi-sub { font-size: 0.85rem; color: #28a745; font-weight: bold; margin-top: 5px; display: block;}
        .kpi-icon { width: 60px; height: 60px; background: rgba(0, 74, 153, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: var(--primary); }

        .kpi-card.yellow { border-left-color: var(--accent-yellow); }
        .kpi-card.yellow .kpi-icon { background: rgba(255, 193, 7, 0.2); color: #d39e00; }
        .kpi-card.green { border-left-color: #28a745; }
        .kpi-card.green .kpi-icon { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .kpi-card.purple { border-left-color: #6f42c1; }
        .kpi-card.purple .kpi-icon { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        .kpi-card.red { border-left-color: #dc3545; }
        .kpi-card.red .kpi-icon { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

        /* Filtres des graphiques */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border-radius: 20px; background: #fff; color: #666; text-decoration: none; font-size: 0.9rem; font-weight: bold; border: 1px solid #ddd; transition: all 0.3s; }
        .filter-btn:hover { background: #f4f7f6; color: var(--primary); border-color: var(--primary); }
        .filter-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 10px rgba(0, 74, 153, 0.2); }

        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;}
        .chart-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .chart-card h3 { margin-top: 0; color: #343a40; font-size: 1.1rem; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;}

        /* CORRECTION DU BUG D'AFFICHAGE */
        .chart-wrapper { position: relative; height: 300px; width: 100%; }

        @media (max-width: 992px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: #343a40; margin: 0;"><i class="fas fa-chart-line" style="color:var(--primary);"></i> Tableau de bord</h1>
        <span style="background: var(--primary); color: white; padding: 8px 15px; border-radius: 20px; font-weight: bold;">
                <i class="fas fa-user-circle"></i> Bonjour, <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?>
            </span>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-info">
                <h3>CA Global HTVA</h3>
                <p class="kpi-value"><?= number_format($caGlobal, 2, ',', ' ') ?> €</p>
                <span class="kpi-sub"><i class="fas fa-calendar-alt"></i> Ce mois : <?= number_format($caMensuel, 2, ',', ' ') ?> €</span>
            </div>
            <div class="kpi-icon"><i class="fas fa-euro-sign"></i></div>
        </div>

        <div class="kpi-card yellow">
            <div class="kpi-info">
                <h3>RDV (Total)</h3>
                <p class="kpi-value"><?= $rdvGlobal ?></p>
                <span class="kpi-sub" style="color:#d39e00;"><i class="fas fa-calendar-alt"></i> Ce mois : <?= $rdvMensuel ?></span>
            </div>
            <div class="kpi-icon"><i class="fas fa-tools"></i></div>
        </div>

        <div class="kpi-card purple">
            <div class="kpi-info">
                <h3>Conversion</h3>
                <p class="kpi-value"><?= $tauxConversion ?> %</p>
                <span class="kpi-sub" style="color:#6f42c1;"><i class="fas fa-bullseye"></i> Visiteurs convertis</span>
            </div>
            <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
        </div>

        <div class="kpi-card red">
            <div class="kpi-info">
                <h3>À Traiter</h3>
                <p class="kpi-value"><?= $countNouveauxRdv + $countMsg ?></p>
                <span class="kpi-sub" style="color:#dc3545;"><i class="fas fa-exclamation-circle"></i> <?= $countNouveauxRdv ?> RDV | <?= $countMsg ?> Message(s)</span>
            </div>
            <div class="kpi-icon"><i class="fas fa-bell"></i></div>
        </div>
    </div>

    <div class="filter-bar">
        <a href="?range=7d" class="filter-btn <?= $range === '7d' ? 'active' : '' ?>">7 Jours</a>
        <a href="?range=1m" class="filter-btn <?= $range === '1m' ? 'active' : '' ?>">1 Mois</a>
        <a href="?range=6m" class="filter-btn <?= $range === '6m' ? 'active' : '' ?>">6 Mois</a>
        <a href="?range=1y" class="filter-btn <?= $range === '1y' ? 'active' : '' ?>">1 An</a>
        <a href="?range=5y" class="filter-btn <?= $range === '5y' ? 'active' : '' ?>">5 Ans</a>
        <a href="?range=all" class="filter-btn <?= $range === 'all' ? 'active' : '' ?>">Depuis le début</a>
    </div>

    <div class="charts-grid">

        <div class="chart-card">
            <h3>Évolution du CA (HTVA) - <?= $currentRangeTitle ?></h3>
            <div class="chart-wrapper">
                <canvas id="caChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>Répartition des Statuts - <?= $currentRangeTitle ?></h3>
            <div class="chart-wrapper">
                <?php if(empty($statutsData)): ?>
                    <p style="text-align:center; color:#999; margin-top:100px;">Aucune donnée sur cette période.</p>
                <?php else: ?>
                    <canvas id="statusChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <div class="chart-card" style="grid-column: 1 / -1;">
            <h3>Trafic du site - <?= $currentRangeTitle ?></h3>
            <div class="chart-wrapper">
                <canvas id="visitorsChart"></canvas>
            </div>
        </div>

    </div>

    <div class="chart-card" style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <h3 style="margin: 0; border: none; padding: 0;"><i class="fas fa-clock" style="color:var(--primary);"></i> 5 Dernières demandes de rendez-vous</h3>
            <a href="quotes.php" class="btn-admin" style="padding: 8px 15px; font-size: 0.9rem; text-decoration: none;"><i class="fas fa-list"></i> Voir tout</a>
        </div>

        <div class="table-container" style="box-shadow: none; padding: 0; border-radius: 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                <tr style="background-color: #f4f7f6; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; text-align: left;">Date demande</th>
                    <th style="padding: 12px; text-align: left;">Client</th>
                    <th style="padding: 12px; text-align: left;">Paiement</th>
                    <th style="padding: 12px; text-align: left;">RDV planifié</th>
                    <th style="padding: 12px; text-align: center;">Statut</th>
                    <th style="padding: 12px; text-align: right;">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($lastRdvs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px; color: #666;">Aucune demande de rendez-vous pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($lastRdvs as $rdv):
                        $created = !empty($rdv['created_at']) ? date('d/m/Y', strtotime($rdv['created_at'])) : '-';
                        $appointment = !empty($rdv['appointment_date']) ? date('d/m/Y à H:i', strtotime($rdv['appointment_date'])) : '<span style="color:#999;">Non défini</span>';

                        $isCompany = !empty($rdv['is_company']) && $rdv['is_company'] == 1;
                        $clientName = htmlspecialchars(($rdv['lastname'] ?? '') . ' ' . ($rdv['firstname'] ?? ''));
                        if ($isCompany && !empty($rdv['company_name'])) {
                            $clientName = htmlspecialchars($rdv['company_name']);
                        }
                        $clientIcon = $isCompany ? '<i class="fas fa-building client-type-icon" title="Société"></i>' : '<i class="fas fa-user client-type-icon" title="Particulier"></i>';
                        $city = htmlspecialchars($rdv['billing_city'] ?? '');

                        $paymentRaw = $rdv['payment_method'] ?? '';
                        if ($paymentRaw === 'stripe') $paymentStr = '<i class="fab fa-stripe" style="color:#6772E5;"></i> En ligne';
                        elseif ($paymentRaw === 'after') $paymentStr = 'Différé (+3%)';
                        elseif ($paymentRaw === 'direct') $paymentStr = 'Sur place';
                        else $paymentStr = ucfirst($paymentRaw);

                        $statusRaw = strtolower($rdv['status'] ?? 'nouveau');
                        if ($statusRaw === 'nouveau') { $badgeClass = 'bg-new'; $statusStr = 'Nouveau'; }
                        elseif ($statusRaw === 'en_attente') { $badgeClass = 'bg-pending'; $statusStr = 'En attente'; }
                        elseif ($statusRaw === 'traité') { $badgeClass = 'bg-done'; $statusStr = 'Traité'; }
                        elseif ($statusRaw === 'annulé') { $badgeClass = 'bg-canceled'; $statusStr = 'Annulé'; }
                        else { $badgeClass = 'bg-new'; $statusStr = ucfirst($statusRaw); }
                        ?>
                        <tr style="border-bottom: 1px solid #eee; <?= $statusRaw == 'nouveau' ? 'background-color: #f0f8ff;' : '' ?>">
                            <td style="padding: 12px;"><?= $created ?></td>
                            <td style="padding: 12px;">
                                <strong><?= $clientIcon . $clientName ?></strong><br>
                                <small style="color: #777;"><?= $city ?></small>
                            </td>
                            <td style="padding: 12px;"><?= $paymentStr ?></td>
                            <td style="padding: 12px;"><strong><?= $appointment ?></strong></td>
                            <td style="padding: 12px; text-align: center;">
                                    <span class="badge-status <?= $badgeClass ?>">
                                        <?= $statusStr ?>
                                    </span>
                            </td>
                            <td style="padding: 12px; text-align: right;">
                                <a href="quote_edit.php?id=<?= $rdv['id'] ?>" class="btn-action btn-edit" title="Voir les détails" style="background-color: var(--primary); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; display: inline-block;">
                                    <i class="fas fa-eye"></i> Ouvrir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const primaryBlue = '#004a99';
    const accentYellow = '#ffc107';

    // 1. Graphique Chiffre d'Affaires (Line)
    const ctxCa = document.getElementById('caChart')?.getContext('2d');
    if(ctxCa) {
        new Chart(ctxCa, {
            type: 'line',
            data: {
                labels: <?= json_encode($caLabels) ?>,
                datasets: [{
                    label: 'CA HTVA (€)',
                    data: <?= json_encode($caData) ?>,
                    borderColor: primaryBlue,
                    backgroundColor: 'rgba(0, 74, 153, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: accentYellow,
                    pointRadius: 5,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    // 2. Graphique Visiteurs (Bar)
    const ctxVisitors = document.getElementById('visitorsChart')?.getContext('2d');
    if(ctxVisitors) {
        new Chart(ctxVisitors, {
            type: 'bar',
            data: {
                labels: <?= json_encode($visLabels) ?>,
                datasets: [{
                    label: 'Visiteurs uniques',
                    data: <?= json_encode($visData) ?>,
                    backgroundColor: accentYellow,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // 3. Graphique Statuts (Doughnut)
    const ctxStatus = document.getElementById('statusChart')?.getContext('2d');
    if(ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($statutsLabels) ?>,
                datasets: [{
                    data: <?= json_encode($statutsData) ?>,
                    backgroundColor: [
                        primaryBlue,
                        accentYellow,
                        '#28a745',
                        '#dc3545',
                        '#6c757d'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
</script>
</body>
</html>