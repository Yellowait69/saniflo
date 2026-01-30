<nav>
    <div class="logo">
        <a href="#"><img src="img/logo-saniflo.png" alt="Saniflo SRL Logo"></a>
    </div>
    <ul class="nav-links">
        <li><a href="#accueil">Accueil</a></li>
        <li><a href="#apropos">À Propos</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#realisations">Réalisations</a></li>

        <li><a href="https://saniflo.sechauffermoinscher.be/actions/fr/devis" target="_blank" style="color:var(--primary-blue);">Devis</a></li>
        <li><a href="tel:0495501717" class="btn-nav" style="background-color: var(--accent-yellow); color: var(--primary-dark) !important;">
                <i class="fas fa-phone-alt"></i> 0495 50 17 17</a>
        </li>
        <li><a href="#contact" class="btn-nav">Contact</a></li>
    </ul>
    <div class="burger"><i class="fas fa-bars"></i></div>
</nav>

<header id="accueil">
    <div class="hero-overlay"></div>
    <div class="container hero-grid">

        <div class="hero-text-side">
            <span class="badge">Depuis 1997</span>
            <h1>Votre Expert en <br>Chauffage & Sanitaire</h1>
            <p>Installation, rénovation et dépannage dans tout le Brabant Wallon. <br>Une expertise dirigée par <strong>Jean-François Dengis</strong> et <strong>Florence Lambinon</strong>.</p>

            <div class="hero-buttons">
                <a href="https://saniflo.sechauffermoinscher.be/actions/fr/devis" target="_blank" class="btn-primary">
                    <i class="fas fa-calendar-alt"></i> Devis
                </a>
                <a href="#contact" class="btn-secondary">Nous contacter</a>
            </div>

            <div style="margin-top: 20px; font-size: 0.9rem; opacity: 0.9;">
                <p><i class="fas fa-info-circle"></i> Entretien chaudière à partir de 160€ HTVA (Gaz) / 190€ HTVA (Mazout).</p>
            </div>
        </div>

        <div class="hero-certs-box">
            <h3><i class="fas fa-certificate"></i> Agréments</h3>
            <div class="hero-certs-list">
                <?php if (!empty($certifications)):
                    $order = ['Général', 'Wallonie', 'Bruxelles', 'Flandre'];
                    $certsByRegion = [];
                    foreach ($certifications as $cert) {
                        $certsByRegion[$cert['region']][] = $cert;
                    }

                    foreach ($order as $regionName):
                        if (isset($certsByRegion[$regionName])): ?>
                            <div class="cert-group">
                                <h4 class="region-label"><?= htmlspecialchars($regionName) ?></h4>
                                <?php foreach ($certsByRegion[$regionName] as $cert): ?>
                                    <div class="hero-cert-item">
                                        <div class="cert-icon"><i class="fas fa-check-circle"></i></div>
                                        <div>
                                            <h5><?= htmlspecialchars($cert['title']) ?></h5>
                                            <p><?= htmlspecialchars($cert['number']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif;
                    endforeach; ?>
                <?php else: ?>
                    <p>Chargement des agréments...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>