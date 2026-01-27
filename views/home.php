<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Saniflo SRL | Chauffagiste Expert</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- CSS BASE --- */
        body, html { overflow-x: hidden; }
        img { max-width: 100%; height: auto; display: block; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .team-img img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

        /* --- CSS WIZARD DEVIS (NOUVEAU) --- */
        #devis { background: #fff; padding: 80px 0; }
        .wizard-form { max-width: 800px; margin: 0 auto; background: #f8f9fa; padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }

        /* Progress Bar */
        .progress-bar-wrapper { display: flex; justify-content: center; align-items: center; margin-bottom: 40px; position: relative; }
        .step-indicator { width: 40px; height: 40px; border-radius: 50%; background: #e0e0e0; color: #666; display: flex; align-items: center; justify-content: center; font-weight: bold; position: relative; z-index: 2; transition: 0.3s; font-size: 1.1rem; }
        .step-indicator.active { background: var(--primary-blue, #0070cd); color: white; transform: scale(1.15); box-shadow: 0 0 15px rgba(0,112,205,0.3); }
        .step-indicator.completed { background: #4CAF50; color: white; }
        .connector { height: 3px; background: #e0e0e0; flex-grow: 1; max-width: 80px; margin: 0 10px; border-radius: 2px; }

        /* Steps */
        .step { display: none; animation: fadeIn 0.4s ease-in-out; }
        .step.active-step { display: block; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        /* Cards Options (Radio Buttons style) */
        .options-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin: 20px 0; }
        .option-card { cursor: pointer; border: 2px solid #e0e0e0; border-radius: 10px; padding: 20px 10px; text-align: center; transition: 0.2s; background: white; position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 110px; }
        .option-card:hover { border-color: var(--primary-blue, #0070cd); transform: translateY(-2px); }
        .option-card input { position: absolute; opacity: 0; cursor: pointer; height: 100%; width: 100%; top: 0; left: 0; }
        .option-card:has(input:checked) { border-color: var(--primary-blue, #0070cd); background: rgba(0, 112, 205, 0.05); box-shadow: 0 4px 10px rgba(0,112,205,0.15); }
        .option-card i { font-size: 2rem; margin-bottom: 10px; color: #999; transition:0.2s; }
        .option-card:has(input:checked) i { color: var(--primary-blue, #0070cd); }
        .option-card span { font-weight: 500; font-size: 0.95rem; color: #555; }
        .option-card:has(input:checked) span { color: var(--primary-blue, #0070cd); font-weight: 700; }

        /* Form inputs */
        .form-row-split { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .wizard-form textarea, .wizard-form input[type="text"], .wizard-form input[type="email"], .wizard-form input[type="tel"] { width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 1rem; background: #fff; }
        .field-label { display: block; font-weight: 600; color: var(--primary-dark, #004a8f); margin-bottom: 15px; font-size: 1.1rem; }
        .btn-group { display: flex; justify-content: space-between; margin-top: 30px; }

        @media(max-width: 600px) {
            .form-row-split { grid-template-columns: 1fr; }
            .connector { max-width: 15px; }
            .options-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">
        <a href="#"><img src="img/logo-saniflo.png" alt="Saniflo SRL Logo"></a>
    </div>
    <ul class="nav-links">
        <li><a href="#accueil">Accueil</a></li>
        <li><a href="#apropos">À Propos</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#devis" style="color:var(--primary-blue, #0070cd);">Devis</a></li>
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
                <a href="#devis" class="btn-primary">Devis Gratuit</a>
                <a href="#services" class="btn-secondary">Nos Services</a>
            </div>
        </div>

        <div class="hero-certs-box">
            <h3><i class="fas fa-certificate"></i> Agréments</h3>
            <div class="hero-certs-list">
                <?php if (!empty($certifications)): ?>
                    <?php foreach ($certifications as $cert): ?>
                        <div class="hero-cert-item">
                            <div class="cert-icon"><i class="fas fa-check-circle"></i></div>
                            <div>
                                <h4><?= htmlspecialchars($cert['title']) ?></h4>
                                <p><?= htmlspecialchars($cert['number']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Chargement des agréments...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

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

<section id="services">
    <div class="container">
        <div class="section-title">
            <h2>Nos Prestations</h2>
            <p>Des solutions durables pour votre confort thermique et sanitaire</p>
        </div>
        <div class="services-grid">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $row): ?>
                    <div class="service-card">
                        <div class="icon-box"><i class="fas <?= htmlspecialchars($row['icon']) ?>"></i></div>
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <p><?= htmlspecialchars($row['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; width:100%;">Nos services incluent : Installation chaudières...</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="devis">
    <div class="container">
        <div class="section-title">
            <h2>Demande de Devis en ligne</h2>
            <p>Un projet ? Une estimation rapide en 4 étapes simples.</p>
        </div>

        <?= $quote_status ?? '' ?>

        <form action="index.php#devis" method="POST" id="quoteForm" class="wizard-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="submit_quote" value="1">

            <div class="progress-bar-wrapper">
                <div class="step-indicator active">1</div>
                <div class="connector"></div>
                <div class="step-indicator">2</div>
                <div class="connector"></div>
                <div class="step-indicator">3</div>
                <div class="connector"></div>
                <div class="step-indicator">4</div>
            </div>

            <div class="step active-step" data-step="1">
                <h3 style="text-align:center; margin-bottom:25px;">1. Données du projet</h3>

                <label class="field-label">Quelle énergie ou technologie ?</label>
                <div class="options-grid">
                    <label class="option-card">
                        <input type="radio" name="energy_type" value="Gaz" required>
                        <i class="fas fa-burn"></i>
                        <span>Gaz</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="energy_type" value="Mazout">
                        <i class="fas fa-oil-can"></i>
                        <span>Mazout</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="energy_type" value="Pompe à chaleur">
                        <i class="fas fa-fan"></i>
                        <span>Pompe à Chaleur</span>
                    </label>
                </div>

                <label class="field-label" style="margin-top:30px;">Surface totale à chauffer ?</label>
                <div class="options-grid">
                    <label class="option-card">
                        <input type="radio" name="surface_area" value="<75m2" required>
                        <i class="fas fa-home" style="font-size:1rem;"></i>
                        <span>< 75m²</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="surface_area" value="<150m2">
                        <i class="fas fa-home" style="font-size:1.2rem;"></i>
                        <span>< 150m²</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="surface_area" value="<200m2">
                        <i class="fas fa-home" style="font-size:1.4rem;"></i>
                        <span>< 200m²</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="surface_area" value="+200m2">
                        <i class="fas fa-home" style="font-size:1.6rem;"></i>
                        <span>+ 200m²</span>
                    </label>
                </div>

                <div class="btn-group">
                    <div></div> <button type="button" class="btn-primary next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="step" data-step="2">
                <h3 style="text-align:center; margin-bottom:25px;">2. Planning & Description</h3>

                <label class="field-label">Quand souhaitez-vous commencer ?</label>
                <div class="options-grid">
                    <label class="option-card">
                        <input type="radio" name="timeline" value="Rapidement" required>
                        <i class="far fa-calendar-check"></i>
                        <span>Rapidement</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="timeline" value="< 3 mois">
                        <i class="far fa-calendar-alt"></i>
                        <span>< 3 mois</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="timeline" value="3-6 mois">
                        <i class="far fa-calendar"></i>
                        <span>3-6 mois</span>
                    </label>
                    <label class="option-card">
                        <input type="radio" name="timeline" value="+ 6 mois">
                        <i class="far fa-calendar-plus"></i>
                        <span>+ 6 mois</span>
                    </label>
                </div>

                <label class="field-label" style="margin-top:20px;">Description brève du projet</label>
                <textarea name="description" rows="4" placeholder="Ex: Remplacement ancienne chaudière gaz, installation chauffage sol..." style="resize: vertical;"></textarea>

                <div class="btn-group">
                    <button type="button" class="btn-secondary prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                    <button type="button" class="btn-primary next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="step" data-step="3">
                <h3 style="text-align:center; margin-bottom:25px;">3. Vos Coordonnées</h3>
                <div class="form-row-split">
                    <div class="form-group">
                        <label>Prénom *</label>
                        <input type="text" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="lastname" required>
                    </div>
                </div>
                <div class="form-row-split" style="margin-top:20px;">
                    <div class="form-group">
                        <label>E-mail *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>GSM / Téléphone</label>
                        <input type="tel" name="phone">
                    </div>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn-secondary prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                    <button type="button" class="btn-primary next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="step" data-step="4">
                <h3 style="text-align:center; margin-bottom:25px;">4. Adresse du chantier</h3>
                <div class="form-group">
                    <label>Rue + Numéro *</label>
                    <input type="text" name="street" required>
                </div>
                <div class="form-row-split" style="margin-top:20px;">
                    <div class="form-group">
                        <label>Code Postal *</label>
                        <input type="text" name="zip" required>
                    </div>
                    <div class="form-group">
                        <label>Localité *</label>
                        <input type="text" name="city" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top:25px; padding: 15px; background: #eee; border-radius:8px;">
                    <label style="font-size:0.9rem; display:flex; gap:10px; cursor:pointer;">
                        <input type="checkbox" required style="width:20px; height:20px;">
                        <span>J'accepte que Saniflo me contacte pour traiter cette demande de devis.</span>
                    </label>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn-secondary prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                    <button type="submit" class="btn-primary">Envoyer ma demande <i class="fas fa-paper-plane"></i></button>
                </div>
            </div>

        </form>
    </div>
</section>
<section id="contact">
    <div class="container">
        <div class="section-title">
            <h2>Contactez-nous</h2>
            <p>Un projet spécifique ? Une urgence ? Nous sommes à votre écoute.</p>
        </div>

        <div class="contact-wrapper">
            <div class="contact-info">
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h4>Adresse</h4>
                        <p>Rue de Fontenelle, 15<br>1325 Dion-Valmont<br>Belgique</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone-alt"></i>
                    <div>
                        <h4>Téléphones</h4>
                        <p><a href="tel:0495501717">GSM : 0495 50 17 17</a></p>
                        <p><a href="tel:010881943">Tél : 010 88 19 43</a></p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h4>Email</h4>
                        <p><a href="mailto:info@saniflo.be">info@saniflo.be</a></p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <h4>Infos Légales</h4>
                        <p>TVA : BE 0461.290.428</p>
                        <p style="font-size: 0.8rem; margin-top:5px;">Banque : BE20 3101 1818 1856</p>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <?= $message_status ?? '' ?>
                <form action="index.php#contact" method="POST">
                    <div class="form-group"><input type="text" name="nom" placeholder="Votre Nom" required></div>
                    <div class="form-group"><input type="email" name="email" placeholder="Votre Email" required></div>
                    <div class="form-group"><input type="tel" name="tel" placeholder="Votre Téléphone"></div>
                    <div class="form-group"><textarea name="message" rows="5" placeholder="Comment pouvons-nous vous aider ?" required></textarea></div>
                    <button type="submit" class="btn-primary full-width">Envoyer le message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="footer-content">
        <p>&copy; 1997 - <?= date("Y") ?> <strong>Saniflo SRL</strong>. Tous droits réservés.</p>
    </div>
</footer>

<script>
    // Navigation Menu Burger
    const navLinks = document.querySelector('.nav-links');
    document.querySelector('.burger').addEventListener('click', () => navLinks.classList.toggle('active'));
    document.querySelectorAll('.nav-links a').forEach(link => link.addEventListener('click', () => navLinks.classList.remove('active')));

    // --- LOGIQUE WIZARD DEVIS ---
    document.addEventListener('DOMContentLoaded', function() {
        const steps = document.querySelectorAll('.step');
        const indicators = document.querySelectorAll('.step-indicator');
        let currentStep = 0;

        function showStep(n) {
            steps.forEach((step, index) => {
                step.classList.remove('active-step');
                if(index === n) step.classList.add('active-step');
            });
            updateIndicators(n);
        }

        function updateIndicators(n) {
            indicators.forEach((ind, index) => {
                ind.classList.remove('active', 'completed');
                if(index === n) ind.classList.add('active');
                if(index < n) {
                    ind.classList.add('completed');
                    ind.innerHTML = '<i class="fas fa-check"></i>';
                } else {
                    ind.innerHTML = index + 1;
                }
            });
        }

        document.querySelectorAll('.next-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Validation simple avant d'avancer
                const currentInputs = steps[currentStep].querySelectorAll('input[required], textarea[required]');
                let isValid = true;
                currentInputs.forEach(input => {
                    if(!input.checkValidity()) {
                        input.reportValidity();
                        isValid = false;
                    }
                });

                if(isValid) {
                    currentStep++;
                    showStep(currentStep);
                }
            });
        });

        document.querySelectorAll('.prev-btn').forEach(button => {
            button.addEventListener('click', () => {
                currentStep--;
                showStep(currentStep);
            });
        });
    });
</script>

</body>
</html>