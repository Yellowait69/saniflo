<?php
// Détection automatique de la page courante pour activer le menu
$page = basename($_SERVER['PHP_SELF']);
$table = $_GET['table'] ?? '';
?>
<header class="admin-header">
    <div class="admin-logo">
        <i class="fas fa-shield-alt" style="color:var(--secondary);"></i> SANIFLO ADMIN
    </div>

    <nav class="admin-nav">
        <a href="dashboard.php" class="<?= $page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line" style="margin-right:5px;"></i> Tableau de bord
        </a>

        <a href="quotes.php" class="<?= ($page == 'quotes.php' || $page == 'quote_edit.php') ? 'active' : '' ?>">
            <i class="fas fa-calendar-check" style="margin-right:5px;"></i> Rendez-vous
        </a>

        <a href="messages.php" class="<?= $page == 'messages.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope" style="margin-right:5px;"></i> Messages
        </a>

        <a href="content.php?table=services" class="<?= ($page == 'content.php' && $table == 'services') ? 'active' : '' ?>">Services</a>
        <a href="content.php?table=pricing" class="<?= ($page == 'content.php' && $table == 'pricing') ? 'active' : '' ?>">Tarifs</a>
        <a href="content.php?table=team" class="<?= ($page == 'content.php' && $table == 'team') ? 'active' : '' ?>">Équipe</a>
        <a href="content.php?table=certifications" class="<?= ($page == 'content.php' && $table == 'certifications') ? 'active' : '' ?>">Agréments</a>
        <a href="content.php?table=projects" class="<?= ($page == 'content.php' && $table == 'projects') ? 'active' : '' ?>">Portfolio</a>
        <a href="content.php?table=users" class="<?= ($page == 'content.php' && $table == 'users') ? 'active' : '' ?>">Admins</a>

        <a href="../" target="_blank" style="margin-left: 50px; border-left: 1px solid rgba(255,255,255,0.2); padding-left: 20px; border-bottom:none; opacity:0.9; font-size:0.9rem;">
            Voir site <i class="fas fa-external-link-alt" style="font-size:0.8rem; margin-left:5px;"></i>
        </a>

        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>
</header>