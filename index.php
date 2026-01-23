<?php require 'db.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saniflo SRL | Chauffagiste Expert en Brabant Wallon</title>
    <meta name="description" content="Saniflo SRL, dirigée par Jean-François Dengis. Installation, entretien et dépannage chauffage, sanitaire et adoucisseur dans le Brabant Wallon.">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<nav>
    <div class="logo">
        <a href="#">
            <img src="img/logo-saniflo.png" alt="Saniflo SRL Logo">
        </a>
    </div>
    <ul class="nav-links">
        <li><a href="#accueil">Accueil</a></li>
        <li><a href="#apropos">À Propos</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#agrements">Agréments</a></li>
        <li><a href="#contact" class="btn-nav">Contact</a></li>
    </ul>
    <div class="burger">
        <i class="fas fa-bars"></i>
    </div>
</nav>

<header id="accueil">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <span class="badge">Depuis 1997</span>
        <h1>Votre Expert en <br>Chauffage & Sanitaire</h1>
        <p>Installation, rénovation et dépannage dans tout le Brabant Wallon. <br>Une expertise dirigée par <strong>Jean-François Dengis</strong> et <strong>Florence Lambinon</strong>.</p>
        <div class="hero-buttons">
            <a href="#contact" class="btn-primary">Devis Gratuit</a>
            <a href="#services" class="btn-secondary">Nos Services</a>
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
                <?php
                try {
                    $stmt = $pdo->query("SELECT * FROM team");
                    while ($member = $stmt->fetch()) {
                        echo '<div class="team-card">';
                        // Placeholder image si pas d'image réelle
                        echo '<div class="team-img"><i class="fas fa-user-tie"></i></div>';
                        echo '<div class="team-info">';
                        echo '<h4>' . htmlspecialchars($member['name']) . '</h4>';
                        echo '<span class="role">' . htmlspecialchars($member['role']) . '</span>';
                        echo '<p>' . htmlspecialchars($member['bio']) . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<p>Erreur chargement équipe.</p>';
                }
                ?>
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
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM services");
                if($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch()) {
                        echo '<div class="service-card">';
                        echo '<div class="icon-box"><i class="fas ' . htmlspecialchars($row['icon']) . '"></i></div>';
                        echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                        echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Mise à jour des services en cours...</p>';
                }
            } catch (Exception $e) {
                echo '<p>Erreur de chargement des services.</p>';
            }
            ?>
        </div>
    </div>
</section>

<section id="agrements" class="bg-blue">
    <div class="container">
        <div class="section-title white-title">
            <h2>Nos Agréments & Certifications</h2>
            <p>L'assurance d'un travail aux normes et sécurisé</p>
        </div>

        <div class="cert-grid">
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM certifications ORDER BY region");
                while ($cert = $stmt->fetch()) {
                    echo '<div class="cert-item">';
                    echo '<span class="region-tag">' . htmlspecialchars($cert['region']) . '</span>';
                    echo '<h4>' . htmlspecialchars($cert['title']) . '</h4>';
                    echo '<p class="cert-number">' . htmlspecialchars($cert['number']) . '</p>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<p>Info agréments indisponible.</p>';
            }
            ?>
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
                <form action="" method="POST">
                    <div class="form-group">
                        <input type="text" name="nom" placeholder="Votre Nom" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Votre Email" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="tel" placeholder="Votre Téléphone">
                    </div>
                    <div class="form-group">
                        <textarea name="message" rows="5" placeholder="Comment pouvons-nous vous aider ?" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary full-width">Envoyer ma demande</button>
                </form>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="footer-content">
        <p>&copy; 1997 - <?php echo date("Y"); ?> <strong>Saniflo SRL</strong>. Tous droits réservés.</p>
        <p class="signature">RC Nivelles 95 985 - Site web modernisé.</p>
    </div>
</footer>

<script>
    // Petit script pour le menu burger mobile
    document.querySelector('.burger').addEventListener('click', () => {
        document.querySelector('.nav-links').classList.toggle('active');
    });
</script>

</body>
</html>