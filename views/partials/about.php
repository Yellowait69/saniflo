<section id="apropos">
    <div class="container">
        <div class="section-title">
            <h2>Qui sommes-nous ?</h2>
            <p>Une société familiale au service de votre confort</p>
        </div>

        <div class="about-grid">
            <div class="about-text">
                <h3>Notre Histoire</h3>
                <p>Créée en 1997, <strong>Saniflo SRL</strong> est une société familiale implantée au cœur du Brabant Wallon (Dion-Valmont). Spécialisée en chauffage, adoucisseur et énergie renouvelable, nous mettons un point d'honneur à la qualité.</p>
                <p>Des formations permanentes nous permettent d'installer des produits peu énergivores. Conseils, devis gratuits, entretiens et garanties sont assurés par le gérant lui-même.</p>

                <div class="target-box">
                    <h4><i class="fas fa-users"></i> Pour VOUS</h4>
                    <p>Que vous soyez <strong>propriétaire, locataire, ou une société</strong>. Que vous occupiez une nouvelle construction ou une maison ancienne. Vos desideratas et votre confort sont au cœur de nos préoccupations. Actualiser votre installation, c'est réaliser de réelles économies.</p>
                </div>
            </div>

            <div class="team-wrapper">
                <?php if (!empty($teamMembers)): ?>
                    <?php foreach ($teamMembers as $member): ?>
                        <?php
                        $imgHtml = '<i class="fas fa-user-tie"></i>';
                        if (stripos($member['name'], 'Florence') !== false) {
                            $imgHtml = '<img src="img/Florence.jpg" alt="Florence">';
                        } elseif (stripos($member['name'], 'Jean') !== false || stripos($member['name'], 'JF') !== false) {
                            $imgHtml = '<img src="img/JF.jpg" alt="JF">';
                        }
                        ?>
                        <div class="team-card">
                            <div class="team-img"><?= $imgHtml ?></div>
                            <div class="team-info">
                                <h4><?= htmlspecialchars($member['name']) ?></h4>
                                <span class="role"><?= htmlspecialchars($member['role']) ?></span>
                                <p><?= htmlspecialchars($member['bio']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Erreur chargement équipe.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>