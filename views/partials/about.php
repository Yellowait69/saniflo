<section id="apropos">
    <div class="container">
        <div class="section-title">
            <h2>Qui sommes-nous ?</h2>
            <p><?= htmlspecialchars($settings['about_subtitle'] ?? 'Une société familiale au service de votre confort') ?></p>
        </div>

        <div class="about-grid">
            <div class="about-text">
                <h3>Notre Histoire</h3>
                <div style="margin-bottom: 20px;">
                    <?php
                    // Texte modifiable depuis l'onglet "Paramètres" de l'admin (Clé : about_history)
                    $defaultHistory = "Créée en 1997, <strong>Saniflo SRL</strong> est une société familiale implantée au cœur du Brabant Wallon (Dion-Valmont). Spécialisée en chauffage, adoucisseur et énergie renouvelable, nous mettons un point d'honneur à la qualité.\n\nDes formations permanentes nous permettent d'installer des produits peu énergivores. Conseils, devis gratuits, entretiens et garanties sont assurés par l'administrateur lui-même.";

                    echo nl2br($settings['about_history'] ?? $defaultHistory);
                    ?>
                </div>

                <div class="target-box">
                    <h4><i class="fas fa-users"></i> Pour VOUS</h4>
                    <p>
                        <?php
                        // Texte modifiable depuis l'onglet "Paramètres" de l'admin (Clé : about_target)
                        $defaultTarget = "Que vous soyez <strong>propriétaire, locataire, ou une société</strong>. Que vous occupiez une nouvelle construction ou une maison ancienne. Vos desideratas et votre confort sont au cœur de nos préoccupations. Actualiser votre installation, c'est réaliser de réelles économies.";

                        echo nl2br($settings['about_target'] ?? $defaultTarget);
                        ?>
                    </p>
                </div>
            </div>

            <div class="team-wrapper">
                <?php if (!empty($teamMembers)): ?>
                    <?php foreach ($teamMembers as $member): ?>
                        <div class="team-card">
                            <div class="team-img">
                                <?php
                                // Utilisation dynamique de l'image issue de la base de données (Uploadée via l'admin)
                                if (!empty($member['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($member['image_url']) ?>" alt="<?= htmlspecialchars($member['name']) ?>">
                                <?php else: ?>
                                    <div style="background: #eee; height: 100%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user-tie fa-3x" style="color: #ccc;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="team-info">
                                <h4><?= htmlspecialchars($member['name']) ?></h4>
                                <span class="role"><?= htmlspecialchars($member['role']) ?></span>
                                <p><?= nl2br(htmlspecialchars($member['bio'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; width: 100%; color: #666;"><em>L'équipe est en cours de mise à jour...</em></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>