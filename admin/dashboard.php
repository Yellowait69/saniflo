<?php
session_start();

// Vérification de sécurité stricte
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$pdo = require_once __DIR__ . '/../config/db.php';

try {
    // ==========================================
    // 1. RÉCUPÉRATION DES STATISTIQUES GLOBALES (KPI)
    // ==========================================
    $currentMonth = date('Y-m');

    // --- CHIFFRE D'AFFAIRES HTVA ---
    $caGlobal = (float)($pdo->query("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé'")->fetchColumn() ?: 0);

    // On utilise created_at (date de la commande) et non appointment_date
    $caMensuelQuery = $pdo->prepare("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $caMensuelQuery->execute([$currentMonth]);
    $caMensuel = (float)($caMensuelQuery->fetchColumn() ?: 0);

    // --- VISITEURS ---
    $visiteursGlobal = (int)($pdo->query("SELECT SUM(visits_count) FROM visitors")->fetchColumn() ?: 0);

    $visiteursMensuelQuery = $pdo->prepare("SELECT SUM(visits_count) FROM visitors WHERE DATE_FORMAT(visit_date, '%Y-%m') = ?");
    $visiteursMensuelQuery->execute([$currentMonth]);
    $visiteursMensuel = (int)($visiteursMensuelQuery->fetchColumn() ?: 0);

    // --- RENDEZ-VOUS & MESSAGES ---
    $rdvGlobal = (int)($pdo->query("SELECT COUNT(*) FROM quote_requests")->fetchColumn() ?: 0);

    // On utilise created_at pour les RDV du mois (prise de contact ce mois-ci)
    $rdvMensuelQuery = $pdo->prepare("SELECT COUNT(*) FROM quote_requests WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $rdvMensuelQuery->execute([$currentMonth]);
    $rdvMensuel = (int)($rdvMensuelQuery->fetchColumn() ?: 0);

    $countNouveauxRdv = (int)$pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'nouveau'")->fetchColumn();
    $countMsg = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();

    $tauxConversion = ($visiteursGlobal > 0) ? round(($rdvGlobal / $visiteursGlobal) * 100, 1) : 0;

    // ==========================================
    // 2. DONNÉES DYNAMIQUES POUR LES GRAPHIQUES (CHART.JS)
    // ==========================================

    $range = $_GET['range'] ?? '6m';
    $rangeTitles = [
        '7d'  => '7 derniers jours',
        '1m'  => '30 derniers jours',
        '6m'  => '6 derniers mois',
        '1y'  => '12 derniers mois',
        '5y'  => '5 dernières années',
        'all' => 'Depuis le début'
    ];
    $currentRangeTitle = $rangeTitles[$range] ?? $rangeTitles['6m'];
    $moisFr = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    $caLabels = []; $caData = [];
    $visLabels = []; $visData = [];

    // Définition des intervalles et limites selon le filtre
    if ($range === '7d') { $interval = 'day'; $limit = 7; }
    elseif ($range === '1m') { $interval = 'day'; $limit = 30; }
    elseif ($range === '6m') { $interval = 'month'; $limit = 6; }
    elseif ($range === '1y') { $interval = 'month'; $limit = 12; }
    elseif ($range === '5y') { $interval = 'year'; $limit = 5; }
    else {
        // Période "all" : calcul dynamique basé sur created_at
        $interval = 'year';
        $minDate = $pdo->query("SELECT MIN(created_at) FROM quote_requests WHERE status != 'annulé'")->fetchColumn();
        $minDate = ($minDate && strtotime($minDate)) ? $minDate : date('Y-m-d');
        $limit = (int)date('Y') - (int)date('Y', strtotime($minDate)) + 1;
        $limit = max(1, $limit); // Sécurité anti-boucle infinie
    }

    // OPTIMISATION MAJEURE : Préparation des requêtes hors de la boucle
    $stmtCA_day = $pdo->prepare("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé' AND DATE(created_at) = ?");
    $stmtVis_day = $pdo->prepare("SELECT SUM(visits_count) FROM visitors WHERE DATE(visit_date) = ?");

    $stmtCA_month = $pdo->prepare("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmtVis_month = $pdo->prepare("SELECT SUM(visits_count) FROM visitors WHERE DATE_FORMAT(visit_date, '%Y-%m') = ?");

    $stmtCA_year = $pdo->prepare("SELECT SUM(total_price_htva) FROM quote_requests WHERE status != 'annulé' AND YEAR(created_at) = ?");
    $stmtVis_year = $pdo->prepare("SELECT SUM(visits_count) FROM visitors WHERE YEAR(visit_date) = ?");

    // Génération des points (du plus ancien au plus récent)
    for ($i = $limit - 1; $i >= 0; $i--) {
        if ($interval === 'day') {
            $dateVal = date('Y-m-d', strtotime("-$i days"));
            $label = date('d/m', strtotime("-$i days"));

            $stmtCA_day->execute([$dateVal]);
            $caData[] = (float)($stmtCA_day->fetchColumn() ?: 0);

            $stmtVis_day->execute([$dateVal]);
            $visData[] = (int)($stmtVis_day->fetchColumn() ?: 0);

        } elseif ($interval === 'month') {
            $timestamp = strtotime("first day of -$i months");
            $dateVal = date('Y-m', $timestamp);
            $numMois = (int)date('n', $timestamp) - 1;
            $label = $moisFr[$numMois] . ' ' . date('y', $timestamp);

            $stmtCA_month->execute([$dateVal]);
            $caData[] = (float)($stmtCA_month->fetchColumn() ?: 0);

            $stmtVis_month->execute([$dateVal]);
            $visData[] = (int)($stmtVis_month->fetchColumn() ?: 0);

        } elseif ($interval === 'year') {
            $dateVal = date('Y', strtotime("-$i years"));
            $label = $dateVal;

            $stmtCA_year->execute([$dateVal]);
            $caData[] = (float)($stmtCA_year->fetchColumn() ?: 0);

            $stmtVis_year->execute([$dateVal]);
            $visData[] = (int)($stmtVis_year->fetchColumn() ?: 0);
        }
        $caLabels[] = $label;
        $visLabels[] = $label;
    }

    // Graphique 3 : Répartition des statuts (Filtré sur created_at)
    $statusCondition = "";
    if ($range === '7d') $statusCondition = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    elseif ($range === '1m') $statusCondition = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    elseif ($range === '6m') $statusCondition = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
    elseif ($range === '1y') $statusCondition = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
    elseif ($range === '5y') $statusCondition = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)";

    $statutsQuery = $pdo->query("SELECT status, COUNT(*) as count FROM quote_requests $statusCondition GROUP BY status");
    $statutsLabels = [];
    $statutsData = [];
    while ($row = $statutsQuery->fetch(PDO::FETCH_ASSOC)) {
        $statutsLabels[] = ucfirst(str_replace('_', ' ', htmlspecialchars($row['status'])));
        $statutsData[] = (int)$row['count'];
    }

    // ==========================================
    // 3. TABLEAU DES DERNIERS RDV
    // ==========================================
    $lastRdvs = $pdo->query("SELECT * FROM quote_requests ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur critique Dashboard : " . $e->getMessage());
    die("<div style='color:#721c24; background:#f8d7da; padding:20px; text-align:center; border:1px solid #f5c6cb; border-radius:5px; margin:20px; font-family:sans-serif;'>
            <h3>Erreur de connexion à la base de données</h3>
            <p>Le tableau de bord est temporairement indisponible. Veuillez contacter l'administrateur système.</p>
         </div>");
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
        :root {
            --primary: #004a99;
            --accent-yellow: #ffc107;
        }

        /* Badges de statut */
        .badge-status { padding: 5px 12px; border-radius: 20px; color: #fff; font-size: 0.85rem; font-weight: bold; display: inline-block; text-align: center; min-width: 90px; }
        .bg-new { background-color: #2196F3; }
        .bg-pending { background-color: #FFC107; color: #000; }
        .bg-done { background-color: #4CAF50; }
        .bg-canceled { background-color: #F44336; }
        .client-type-icon { color: #888; margin-right: 6px; }

        /* Structure Dashboard */
        .dashboard-container { padding: 20px; max-width: 1400px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .kpi-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border-left: 5px solid var(--primary); display: flex; align-items: center; justify-content: space-between; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .kpi-info h3 { margin: 0; font-size: 0.9rem; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
        .kpi-info .kpi-value { margin: 8px 0 0 0; font-size: 2.2rem; font-weight: 700; color: #343a40; line-height: 1; }
        .kpi-info .kpi-sub { font-size: 0.85rem; color: #28a745; font-weight: 600; margin-top: 8px; display: block; }
        .kpi-icon { width: 65px; height: 65px; background: rgba(0, 74, 153, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--primary); }

        .kpi-card.yellow { border-left-color: var(--accent-yellow); }
        .kpi-card.yellow .kpi-icon { background: rgba(255, 193, 7, 0.15); color: #d39e00; }
        .kpi-card.green { border-left-color: #28a745; }
        .kpi-card.green .kpi-icon { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .kpi-card.purple { border-left-color: #6f42c1; }
        .kpi-card.purple .kpi-icon { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        .kpi-card.red { border-left-color: #dc3545; }
        .kpi-card.red .kpi-icon { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

        /* Filtres */
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 18px; border-radius: 20px; background: #fff; color: #555; text-decoration: none; font-size: 0.9rem; font-weight: 600; border: 1px solid #e0e0e0; transition: all 0.3s ease; }
        .filter-btn:hover { background: #f8f9fa; color: var(--primary); border-color: var(--primary); }
        .filter-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 10px rgba(0, 74, 153, 0.25); }

        /* Graphiques */
        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;}
        .chart-card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .chart-card h3 { margin-top: 0; color: #2c3e50; font-size: 1.15rem; margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; font-weight: 600; }
        .chart-wrapper { position: relative; height: 320px; width: 100%; }

        @media (max-width: 992px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; flex-wrap: wrap; gap: 15px;">
        <h1 style="color: #2c3e50; margin: 0; font-size: 1.8rem;"><i class="fas fa-chart-line" style="color:var(--primary); margin-right: 10px;"></i> Tableau de bord</h1>
        <span style="background: linear-gradient(135deg, var(--primary), #003366); color: white; padding: 10px 20px; border-radius: 25px; font-weight: 600; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <i class="fas fa-user-circle" style="margin-right: 5px;"></i> Bonjour, <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?>
        </span>
    </div>

    <!-- KPI Section -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-info">
                <h3>CA Global HTVA</h3>
                <p class="kpi-value"><?= number_format($caGlobal, 2, ',', ' ') ?> €</p>
                <span class="kpi-sub"><i class="fas fa-arrow-up"></i> Ce mois : <?= number_format($caMensuel, 2, ',', ' ') ?> €</span>
            </div>
            <div class="kpi-icon"><i class="fas fa-euro-sign"></i></div>
        </div>

        <div class="kpi-card yellow">
            <div class="kpi-info">
                <h3>RDV (Total)</h3>
                <p class="kpi-value"><?= number_format($rdvGlobal, 0, ',', ' ') ?></p>
                <span class="kpi-sub" style="color:#d39e00;"><i class="fas fa-calendar-check"></i> Ce mois : <?= $rdvMensuel ?></span>
            </div>
            <div class="kpi-icon"><i class="fas fa-tools"></i></div>
        </div>

        <div class="kpi-card purple">
            <div class="kpi-info">
                <h3>Conversion</h3>
                <p class="kpi-value"><?= number_format($tauxConversion, 1, ',', ' ') ?> %</p>
                <span class="kpi-sub" style="color:#6f42c1;"><i class="fas fa-bullseye"></i> Visiteurs convertis</span>
            </div>
            <div class="kpi-icon"><i class="fas fa-percentage"></i></div>
        </div>

        <div class="kpi-card red">
            <div class="kpi-info">
                <h3>Action Requise</h3>
                <p class="kpi-value"><?= $countNouveauxRdv + $countMsg ?></p>
                <span class="kpi-sub" style="color:#dc3545;"><i class="fas fa-exclamation-triangle"></i> <?= $countNouveauxRdv ?> RDV | <?= $countMsg ?> Message(s)</span>
            </div>
            <div class="kpi-icon"><i class="fas fa-bell"></i></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <a href="?range=7d" class="filter-btn <?= $range === '7d' ? 'active' : '' ?>">7 Jours</a>
        <a href="?range=1m" class="filter-btn <?= $range === '1m' ? 'active' : '' ?>">30 Jours</a>
        <a href="?range=6m" class="filter-btn <?= $range === '6m' ? 'active' : '' ?>">6 Mois</a>
        <a href="?range=1y" class="filter-btn <?= $range === '1y' ? 'active' : '' ?>">1 An</a>
        <a href="?range=5y" class="filter-btn <?= $range === '5y' ? 'active' : '' ?>">5 Ans</a>
        <a href="?range=all" class="filter-btn <?= $range === 'all' ? 'active' : '' ?>">Historique complet</a>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Évolution du Chiffre d'Affaires - <?= htmlspecialchars($currentRangeTitle) ?></h3>
            <div class="chart-wrapper">
                <canvas id="caChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>Répartition des Statuts</h3>
            <div class="chart-wrapper">
                <?php if(empty($statutsData)): ?>
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: #999; font-style: italic;">Aucune donnée sur cette période.</div>
                <?php else: ?>
                    <canvas id="statusChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <div class="chart-card" style="grid-column: 1 / -1;">
            <h3>Trafic du site web - <?= htmlspecialchars($currentRangeTitle) ?></h3>
            <div class="chart-wrapper">
                <canvas id="visitorsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Appointments Table -->
    <div class="chart-card" style="margin-bottom: 40px; padding: 0; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid #f0f0f0; background: #fafafa;">
            <h3 style="margin: 0; border: none; padding: 0;"><i class="fas fa-clock" style="color:var(--primary); margin-right: 8px;"></i> 5 Dernières demandes de rendez-vous</h3>
            <a href="quotes.php" class="btn-admin" style="background: var(--primary); color: white; padding: 8px 16px; font-size: 0.9rem; font-weight: 600; text-decoration: none; border-radius: 6px; transition: opacity 0.2s;"><i class="fas fa-list" style="margin-right: 5px;"></i> Voir tout</a>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 850px; text-align: left;">
                <thead>
                <tr style="background-color: #fff; border-bottom: 2px solid #eaeaea; color: #555; font-size: 0.9rem;">
                    <th style="padding: 15px 25px;">Date demande</th>
                    <th style="padding: 15px 25px;">Client / Société</th>
                    <th style="padding: 15px 25px;">Paiement</th>
                    <th style="padding: 15px 25px;">RDV planifié</th>
                    <th style="padding: 15px 25px; text-align: center;">Statut</th>
                    <th style="padding: 15px 25px; text-align: right;">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($lastRdvs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #888; font-style: italic;">Aucune demande de rendez-vous pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($lastRdvs as $rdv):
                        $created = !empty($rdv['created_at']) ? date('d/m/Y', strtotime($rdv['created_at'])) : '-';
                        $appointment = !empty($rdv['appointment_date']) ? date('d/m/Y à H:i', strtotime($rdv['appointment_date'])) : '<span style="color:#aaa; font-style: italic;">À définir</span>';

                        $isCompany = !empty($rdv['is_company']) && $rdv['is_company'] == 1;
                        $clientName = htmlspecialchars(($rdv['lastname'] ?? '') . ' ' . ($rdv['firstname'] ?? ''));
                        if ($isCompany && !empty($rdv['company_name'])) {
                            $clientName = htmlspecialchars($rdv['company_name']);
                        }
                        $clientIcon = $isCompany ? '<i class="fas fa-building client-type-icon" title="Société"></i>' : '<i class="fas fa-user client-type-icon" title="Particulier"></i>';
                        $city = htmlspecialchars($rdv['billing_city'] ?? '');

                        $paymentRaw = $rdv['payment_method'] ?? '';
                        if ($paymentRaw === 'stripe') $paymentStr = '<span style="color:#6772E5; font-weight:600;"><i class="fab fa-stripe"></i> En ligne</span>';
                        elseif ($paymentRaw === 'after') $paymentStr = 'Différé (+3%)';
                        elseif ($paymentRaw === 'direct') $paymentStr = 'Sur place';
                        else $paymentStr = htmlspecialchars(ucfirst($paymentRaw));

                        $statusRaw = strtolower($rdv['status'] ?? 'nouveau');
                        if ($statusRaw === 'nouveau') { $badgeClass = 'bg-new'; $statusStr = 'Nouveau'; }
                        elseif ($statusRaw === 'en_attente') { $badgeClass = 'bg-pending'; $statusStr = 'En attente'; }
                        elseif ($statusRaw === 'traité') { $badgeClass = 'bg-done'; $statusStr = 'Traité'; }
                        elseif ($statusRaw === 'annulé') { $badgeClass = 'bg-canceled'; $statusStr = 'Annulé'; }
                        else { $badgeClass = 'bg-new'; $statusStr = htmlspecialchars(ucfirst($statusRaw)); }
                        ?>
                        <tr style="border-bottom: 1px solid #f5f5f5; transition: background 0.2s; <?= $statusRaw == 'nouveau' ? 'background-color: #f4fbff;' : '' ?>" onmouseover="this.style.backgroundColor='#f9f9f9';" onmouseout="this.style.backgroundColor='<?= $statusRaw == 'nouveau' ? '#f4fbff' : '#fff' ?>';">
                            <td style="padding: 15px 25px; color: #555;"><?= $created ?></td>
                            <td style="padding: 15px 25px;">
                                <strong style="color: #333; font-size: 1.05rem;"><?= $clientIcon . ' ' . $clientName ?></strong><br>
                                <small style="color: #888;"><i class="fas fa-map-marker-alt" style="margin-right:4px; font-size:0.8rem;"></i><?= $city ?></small>
                            </td>
                            <td style="padding: 15px 25px;"><?= $paymentStr ?></td>
                            <td style="padding: 15px 25px; color: #444; font-weight: 500;"><?= $appointment ?></td>
                            <td style="padding: 15px 25px; text-align: center;">
                                <span class="badge-status <?= $badgeClass ?>"><?= $statusStr ?></span>
                            </td>
                            <td style="padding: 15px 25px; text-align: right;">
                                <a href="quote_edit.php?id=<?= (int)$rdv['id'] ?>" title="Voir les détails" style="background-color: #eef2f5; color: var(--primary); padding: 8px 14px; border-radius: 6px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.2s; border: 1px solid #dce4eb;" onmouseover="this.style.backgroundColor='var(--primary)'; this.style.color='#fff';" onmouseout="this.style.backgroundColor='#eef2f5'; this.style.color='var(--primary)';">
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
    // Configuration globale Chart.js
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.color = '#6c757d';

    const primaryBlue = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#004a99';
    const accentYellow = getComputedStyle(document.documentElement).getPropertyValue('--accent-yellow').trim() || '#ffc107';

    // 1. Graphique Chiffre d'Affaires (Line avec dégradé)
    const ctxCa = document.getElementById('caChart')?.getContext('2d');
    if(ctxCa) {
        let gradient = ctxCa.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(0, 74, 153, 0.4)');
        gradient.addColorStop(1, 'rgba(0, 74, 153, 0.0)');

        new Chart(ctxCa, {
            type: 'line',
            data: {
                labels: <?= json_encode($caLabels) ?>,
                datasets: [{
                    label: 'CA HTVA',
                    data: <?= json_encode($caData) ?>,
                    borderColor: primaryBlue,
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: primaryBlue,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 10,
                        titleFont: { size: 13 },
                        bodyFont: { size: 14, weight: 'bold' },
                        callbacks: { label: function(context) { return context.parsed.y.toLocaleString('fr-FR') + ' €'; } }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0', drawBorder: false } },
                    x: { grid: { display: false, drawBorder: false } }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    // 2. Graphique Visiteurs (Barres arrondies)
    const ctxVisitors = document.getElementById('visitorsChart')?.getContext('2d');
    if(ctxVisitors) {
        new Chart(ctxVisitors, {
            type: 'bar',
            data: {
                labels: <?= json_encode($visLabels) ?>,
                datasets: [{
                    label: 'Visiteurs Uniques',
                    data: <?= json_encode($visData) ?>,
                    backgroundColor: accentYellow,
                    hoverBackgroundColor: '#e0a800',
                    borderRadius: 6,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { padding: 10, bodyFont: { weight: 'bold' } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0f0f0', drawBorder: false } },
                    x: { grid: { display: false, drawBorder: false } }
                }
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
                    backgroundColor: [ primaryBlue, accentYellow, '#28a745', '#dc3545', '#6c757d', '#17a2b8' ],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, padding: 20, font: { size: 12 } } },
                    tooltip: { padding: 10, bodyFont: { weight: 'bold' } }
                }
            }
        });
    }
</script>
</body>
</html>