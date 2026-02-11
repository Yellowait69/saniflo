<nav>
    <div class="logo">
        <a href="#"><img src="img/logo-saniflo.png" alt="Saniflo SRL Logo"></a>
    </div>
    <ul class="nav-links">
        <li><a href="#accueil">Accueil</a></li>
        <li><a href="#apropos">À Propos</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#realisations">Réalisations</a></li>
        <li><a href="tel:0495501717" class="btn-nav" style="background-color: var(--accent-yellow); color: var(--primary-dark) !important;">
                <i class="fas fa-phone-alt"></i> 0495 50 17 17</a>
        </li>
        <li><a href="#nb-avis-top">Avis</a></li>
    </ul>
    <div class="burger"><i class="fas fa-bars"></i></div>
</nav>

<header id="accueil">
    <div class="hero-overlay"></div>
    <div class="container hero-grid">

        <div class="hero-text-side">
            <span class="badge" style="
                position: absolute;
                top: 50px;           /* Ajustez cette valeur pour monter/descendre le badge */
                left: 50%;            /* Place le début du badge à 50% de la largeur */
                transform: translateX(-50%); /* Recule le badge de 50% de sa propre taille pour être parfaitement centré */
                font-size: 1.2rem;
                font-weight: 700;
                padding: 8px 25px;
                letter-spacing: 2px;
                z-index: 99;          /* S'assure qu'il est au-dessus */
                white-space: nowrap;  /* Empêche le texte de se couper */
            ">Depuis 1997</span>

            <h1 style="margin-top: 50px;">Votre Expert en <br>Chauffage & Sanitaire</h1>

            <p>Installation, rénovation et dépannage dans tout le Brabant Wallon. <br>Une expertise dirigée par <strong>Jean-François Dengis</strong> et <strong>Florence Lambinon</strong>.</p>

            <div style="margin-top: 35px; padding: 25px; background: rgba(0, 0, 0, 0.4); border-radius: 12px; border-left: 4px solid var(--accent-yellow); backdrop-filter: blur(8px);">
                <p style="margin-bottom: 20px; font-weight: 600; color: #fff; font-size: 1.1rem;">
                    <i class="fas fa-mouse-pointer"></i> Comment pouvons-nous vous aider ?
                </p>

                <div style="display: flex; flex-direction: column; gap: 15px;">

                    <a href="#devis-wizard" style="text-decoration: none; background: var(--accent-yellow); color: var(--primary-dark); padding: 12px 20px; border-radius: 8px; display: flex; align-items: center; gap: 15px; transition: transform 0.3s; box-shadow: 0 4px 10px rgba(255, 196, 0, 0.3);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div style="background: rgba(255,255,255,0.3); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-calendar-check" style="font-size: 1.2rem; color: var(--primary-dark);"></i>
                        </div>
                        <div>
                            <span style="display: block; font-weight: 800; font-size: 1rem; text-transform: uppercase;">Planifier un Entretien</span>
                            <span style="display: block; font-size: 0.8rem; font-weight: 600; opacity: 0.9;">Agenda en ligne (Gaz, Mazout, Adoucisseur)</span>
                        </div>
                    </a>

                    <a href="https://saniflo.sechauffermoinscher.be/actions/fr/devis" target="_blank" style="text-decoration: none; background: var(--primary-blue); color: white; padding: 12px 20px; border-radius: 8px; display: flex; align-items: center; gap: 15px; transition: background 0.3s;" onmouseover="this.style.background='var(--primary-dark)'" onmouseout="this.style.background='var(--primary-blue)'">
                        <div style="background: rgba(255,255,255,0.2); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-file-invoice-dollar" style="font-size: 1.2rem; color: white;"></i>
                        </div>
                        <div>
                            <span style="display: block; font-weight: 700; font-size: 1rem;">Demander un Devis Gratuit : Estimation</span>
                            <span style="display: block; font-size: 0.8rem; opacity: 0.9;">Installation/remplacement de chaudière Viessmann</span>
                        </div>
                    </a>

                    <a href="#contact" style="text-decoration: none; background: rgba(255,255,255,0.1); color: white; padding: 12px 20px; border-radius: 8px; display: flex; align-items: center; gap: 15px; border: 1px solid rgba(255,255,255,0.2); transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                        <div style="background: rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-tools" style="font-size: 1.1rem; color: white;"></i>
                        </div>
                        <div>
                            <span style="display: block; font-weight: 600; font-size: 1rem;">Contact pour les autres demandes</span>
                            <span style="display: block; font-size: 0.8rem; opacity: 0.7;">Renseignements, autres demandes d'intervention,...</span>
                        </div>
                    </a>

                </div>
            </div>
        </div>

        <div class="hero-spacer"></div>

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
        // Sélectionne le contenu qui suit juste après le header cliqué
        var content = headerElement.nextElementSibling;
        var arrow = headerElement.querySelector('.region-arrow');

        // Bascule l'affichage
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