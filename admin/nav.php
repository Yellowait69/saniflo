<?php
// admin/nav.php

// Détection automatique de la page courante et de la table pour activer le menu
$page = basename($_SERVER['PHP_SELF']);
$table = $_GET['table'] ?? '';

// Variables pour garder les groupes "actifs" en surbrillance
$isCatalogueOpen = in_array($table, ['products', 'product_categories', 'product_types', 'services', 'pricing']);
$isPreuveSocialeOpen = in_array($table, ['projects', 'project_categories', 'intervention_types']) || $page === 'reviews.php';
$isConfigOpen = in_array($table, ['site_content', 'settings', 'team', 'certifications', 'users']);
?>
<header class="admin-header">
    <div class="admin-logo">
        <i class="fas fa-shield-alt" style="color:var(--secondary);"></i> SANIFLO ADMIN
    </div>

    <nav class="admin-nav">
        <a href="dashboard.php" class="<?= $page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line" style="margin-right:8px;"></i> Tableau de bord
        </a>

        <a href="quotes.php" class="<?= in_array($page, ['quotes.php', 'quote_edit.php']) ? 'active' : '' ?>">
            <i class="fas fa-calendar-check" style="margin-right:8px;"></i> Rendez-vous
        </a>

        <a href="messages.php" class="<?= $page === 'messages.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope" style="margin-right:8px;"></i> Messages
        </a>

        <div class="nav-group <?= $isCatalogueOpen ? 'active' : '' ?>">
            <div class="nav-summary"><i class="fas fa-box" style="margin-right:8px;"></i> Catalogue</div>
            <div class="nav-group-content">
                <a href="content.php?table=products" class="<?= in_array($table, ['products', 'product_categories', 'product_types']) ? 'active' : '' ?>">Produits</a>
                <a href="content.php?table=services" class="<?= $table === 'services' ? 'active' : '' ?>">Services</a>
                <a href="content.php?table=pricing" class="<?= $table === 'pricing' ? 'active' : '' ?>">Tarifs</a>
            </div>
        </div>

        <div class="nav-group <?= $isPreuveSocialeOpen ? 'active' : '' ?>">
            <div class="nav-summary"><i class="fas fa-camera" style="margin-right:8px;"></i> Preuve Sociale</div>
            <div class="nav-group-content">
                <a href="reviews.php" class="<?= $page === 'reviews.php' ? 'active' : '' ?>">Avis Clients</a>
                <a href="content.php?table=projects" class="<?= in_array($table, ['projects', 'project_categories', 'intervention_types']) ? 'active' : '' ?>">Portfolio (Chantiers)</a>
            </div>
        </div>

        <div class="nav-group <?= $isConfigOpen ? 'active' : '' ?>">
            <div class="nav-summary"><i class="fas fa-cogs" style="margin-right:8px;"></i> Configuration</div>
            <div class="nav-group-content">
                <a href="content.php?table=settings" class="<?= $table === 'settings' ? 'active' : '' ?>">Paramètres globaux</a>
                <a href="content.php?table=site_content" class="<?= $table === 'site_content' ? 'active' : '' ?>">Textes du site</a>
                <a href="content.php?table=team" class="<?= $table === 'team' ? 'active' : '' ?>">Équipe</a>
                <a href="content.php?table=certifications" class="<?= $table === 'certifications' ? 'active' : '' ?>">Agréments</a>
                <a href="content.php?table=users" class="<?= $table === 'users' ? 'active' : '' ?>">Administrateurs</a>
            </div>
        </div>

        <a href="../" target="_blank" class="site-link-btn">
            <i class="fas fa-external-link-alt" style="margin-right:8px;"></i> Voir le site
        </a>

        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt" style="margin-right:8px;"></i> Déconnexion
        </a>
    </nav>
</header>