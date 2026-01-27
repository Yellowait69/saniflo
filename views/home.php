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
        /* CSS Critique */
        body, html { overflow-x: hidden; }
        img { max-width: 100%; height: auto; display: block; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .team-img img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
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
                <a href="#contact" class="btn-primary">Devis Gratuit</a>
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
                <p>Créée en 1997, <strong>Saniflo SRL</strong> est une société familiale...</p>
                <div class="target-box">
                    <h4><i class="fas fa-users"></i> Pour VOUS</h4>
                    <p>Que vous soyez propriétaire, locataire...</p>
                </div>
            </div>

            <div class="team-wrapper">
                <?php if (!empty($teamMembers)): ?>
                    <?php foreach ($teamMembers as $member): ?>
                        <?php
                        // Logique d'affichage simple (la logique métier est déjà faite, c'est juste de la présentation)
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

<section id="contact">
    <div class="container">
        <div class="section-title">
            <h2>Contactez-nous</h2>
            <p>Un projet ? Une urgence ? Nous sommes à votre écoute.</p>
        </div>
        <div class="contact-wrapper">
            <div class="contact-info">
                <div class="info-item"><i class="fas fa-envelope"></i><div><h4>Email</h4><p><a href="mailto:info@saniflo.be">info@saniflo.be</a></p></div></div>
            </div>

            <div class="contact-form">
                <?= $message_status ?> <form action="index.php#contact" method="POST">
                    <div class="form-group"><input type="text" name="nom" placeholder="Votre Nom" required></div>
                    <div class="form-group"><input type="email" name="email" placeholder="Votre Email" required></div>
                    <div class="form-group"><input type="tel" name="tel" placeholder="Votre Téléphone"></div>
                    <div class="form-group"><textarea name="message" rows="5" placeholder="Comment pouvons-nous vous aider ?" required></textarea></div>
                    <button type="submit" class="btn-primary full-width">Envoyer ma demande</button>
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
    const navLinks = document.querySelector('.nav-links');
    document.querySelector('.burger').addEventListener('click', () => navLinks.classList.toggle('active'));
    document.querySelectorAll('.nav-links a').forEach(link => link.addEventListener('click', () => navLinks.classList.remove('active')));
</script>

</body>
</html>