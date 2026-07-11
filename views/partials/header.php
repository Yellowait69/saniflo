<style>
    /* =========================================
       STYLES RESPONSIVES DE L'EN-TÊTE
       ========================================= */
    .hero-badge-date {
        position: absolute;
        top: 50px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 1.2rem;
        font-weight: 700;
        padding: 8px 25px;
        letter-spacing: 2px;
        z-index: 99;
        white-space: nowrap;
        background: var(--accent-yellow);
        color: var(--primary-dark);
        border-radius: 30px;
    }

    .hero-h1-title {
        margin-top: 50px;
    }

    .cta-box {
        margin-top: 35px;
        padding: 25px;
        background: rgba(0, 0, 0, 0.4);
        border-radius: 12px;
        border-left: 4px solid var(--accent-yellow);
        backdrop-filter: blur(8px);
    }

    .cta-btn-link {
        text-decoration: none !important;
        padding: 12px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s ease;
    }

    .cta-icon-wrapper {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Bouton Jaune (RDV) */
    .btn-yellow {
        background: var(--accent-yellow);
        color: var(--primary-dark);
        box-shadow: 0 4px 10px rgba(255, 196, 0, 0.3);
    }
    .btn-yellow:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(255, 196, 0, 0.4);
    }
    .btn-yellow .cta-icon-wrapper { background: rgba(255,255,255,0.4); }

    /* Bouton Bleu (Devis) */
    .btn-blue {
        background: var(--primary-blue);
        color: white;
    }
    .btn-blue:hover { background: var(--primary-dark); }
    .btn-blue .cta-icon-wrapper { background: rgba(255,255,255,0.2); }

    /* Bouton Transparent (Contact) */
    .btn-outline {
        background: rgba(255,255,255,0.05);
        color: white;
        border: 1px solid rgba(255,255,255,0.2);
    }
    .btn-outline:hover { background: rgba(255,255,255,0.15); }
    .btn-outline .cta-icon-wrapper { background: rgba(255,255,255,0.1); }

    /* --- ADAPTATION POUR MOBILES ET TABLETTES --- */
    @media (max-width: 768px) {
        .hero-badge-date {
            position: relative;
            top: 0;
            left: 0;
            transform: none;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 1rem;
            padding: 5px 15px;
        }

        .hero-h1-title {
            margin-top: 0;
            font-size: 2rem;
            line-height: 1.2;
        }

        .cta-box {
            padding: 15px;
            margin-top: 25px;
        }

        .cta-btn-link {
            padding: 10px 15px;
            gap: 12px;
        }

        .cta-icon-wrapper {
            width: 35px;
            height: 35px;
        }

        .cta-title { font-size: 0.9rem !important; }
        .cta-desc { font-size: 0.75rem !important; }
    }
</style>

<nav>
    <div class="logo">
        <a href="index.php"><img src="img/logo-saniflo.png" alt="Saniflo SRL Logo"></a>
    </div>
    <ul class="nav-links">
        <li><a href="index.php#accueil">Accueil</a></li>
        <li><a href="index.php#apropos">À Propos</a></li>
        <li><a href="index.php#services">Services</a></li>
        <li><a href="index.php#produits">Produits</a></li>
        <li><a href="index.php#realisations">Réalisations</a></li>
        <li><a href="tel:0495501717" class="btn-nav" style="background-color: var(--accent-yellow); color: var(--primary-dark) !important;">
                <i class="fas fa-phone-alt"></i> 0495 50 17 17</a>
        </li>
        <li><a href="index.php#nb-avis-top">Avis</a></li>
    </ul>
    <div class="burger"><i class="fas fa-bars"></i></div>
</nav>

<header id="accueil">
    <div class="hero-overlay"></div>
    <div class="container hero-grid">

        <div class="hero-text-side">
            <span class="badge hero-badge-date">Depuis 1997</span>

            <?php
            // Textes par défaut si la base de données est vide
            $defaultHeroTitle = "Votre Expert en\nChauffage & Sanitaire";
            $defaultHeroSubtitle = "Installation, rénovation et dépannage dans tout le Brabant Wallon.\nUne expertise dirigée par Jean-François Dengis et Florence Lambinon.";
            ?>

            <h1 class="hero-h1-title">
                <?= nl2br(htmlspecialchars($site_content['hero_title'] ?? $defaultHeroTitle)) ?>
            </h1>

            <p>
                <?= nl2br(htmlspecialchars($site_content['hero_subtitle'] ?? $defaultHeroSubtitle)) ?>
            </p>

            <div class="cta-box">
                <p style="margin-bottom: 20px; font-weight: 600; color: #fff; font-size: 1.1rem;">
                    <i class="fas fa-mouse-pointer"></i> Comment pouvons-nous vous aider ?
                </p>

                <div style="display: flex; flex-direction: column; gap: 15px;">

                    <a href="index.php?page=reservation#devis-wizard" class="cta-btn-link btn-yellow">
                        <div class="cta-icon-wrapper">
                            <i class="fas fa-calendar-check" style="font-size: 1.2rem; color: var(--primary-dark);"></i>
                        </div>
                        <div>
                            <span class="cta-title" style="display: block; font-weight: 800; font-size: 1rem; text-transform: uppercase;">Planifier un Entretien</span>
                            <span class="cta-desc" style="display: block; font-size: 0.8rem; font-weight: 600; opacity: 0.9;">Agenda en ligne (Gaz, Mazout, Adoucisseur)</span>
                        </div>
                    </a>

                    <a href="https://saniflo.sechauffermoinscher.be/actions/fr/devis" target="_blank" class="cta-btn-link btn-blue">
                        <div class="cta-icon-wrapper">
                            <i class="fas fa-file-invoice-dollar" style="font-size: 1.2rem; color: white;"></i>
                        </div>
                        <div>
                            <span class="cta-title" style="display: block; font-weight: 700; font-size: 1rem;">Demander un Devis Gratuit : Estimation</span>
                            <span class="cta-desc" style="display: block; font-size: 0.8rem; opacity: 0.9;">Installation/remplacement de chaudière Viessmann</span>
                        </div>
                    </a>

                    <a href="index.php?page=contact#contact-section" class="cta-btn-link btn-outline">
                        <div class="cta-icon-wrapper">
                            <i class="fas fa-tools" style="font-size: 1.1rem; color: white;"></i>
                        </div>
                        <div>
                            <span class="cta-title" style="display: block; font-weight: 600; font-size: 1rem;">Contact pour les autres demandes</span>
                            <span class="cta-desc" style="display: block; font-size: 0.8rem; opacity: 0.7;">Renseignements, autres demandes d'intervention,...</span>
                        </div>
                    </a>

                </div>
            </div>
        </div>

        <div class="hero-spacer"></div>

        <div class="hero-certs-box">
            <h3><i class="fas fa-graduation-cap"></i> Agréments</h3>
            <div class="hero-certs-list">
                <?php if (!empty($certifications)):
                    $order = ['Général', 'Wallonie', 'Bruxelles', 'Flandre'];
                    $certsByRegion = [];
                    foreach ($certifications as $cert) {
                        $certsByRegion[$cert['region']][] = $cert;
                    }

                    foreach ($order as $regionName):
                        if (isset($certsByRegion[$regionName])): ?>
                            <div class="cert-group" style="margin-bottom: 5px;">
                                <div class="region-header" onclick="toggleCertRegion(this)" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <h4 class="region-label" style="margin: 0; pointer-events: none;"><?= htmlspecialchars($regionName) ?></h4>
                                    <i class="fas fa-chevron-down region-arrow" style="font-size: 0.8rem; transition: transform 0.3s;"></i>
                                </div>

                                <div class="region-content" style="display: none; padding-left: 10px; margin-top: 5px; border-left: 2px solid rgba(255,196,0,0.3);">
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

<script>
    function toggleCertRegion(headerElement) {
        var content = headerElement.nextElementSibling;
        var arrow = headerElement.querySelector('.region-arrow');

        if (content.style.display === "none") {
            content.style.display = "block";
            if(arrow) arrow.style.transform = "rotate(180deg)";
            headerElement.style.background = "rgba(255,255,255,0.05)";
        } else {
            content.style.display = "none";
            if(arrow) arrow.style.transform = "rotate(0deg)";
            headerElement.style.background = "transparent";
        }
    }
</script>