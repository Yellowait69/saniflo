<?php require 'db.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saniflo SRL | Chauffagiste Expert en Brabant Wallon</title>
    <meta name="description" content="Saniflo SRL, dirigée par Jean-François Dengis. Installation, entretien et dépannage chauffage et sanitaire dans le Brabant Wallon. Devis gratuit.">
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
        <li><a href="#services">Services</a></li>
        <li><a href="#contact" class="btn-nav">Contact</a></li>
    </ul>
    <div class="burger">
        <i class="fas fa-bars"></i>
    </div>
</nav>

<header id="accueil">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <span class="badge">Société Familiale</span>
        <h1>Votre Expert en <br>Chauffage & Sanitaire</h1>
        <p>Installation, rénovation et dépannage dans tout le Brabant Wallon. <br>Une expertise dirigée par <strong>Jean-François Dengis</strong>.</p>
        <div class="hero-buttons">
            <a href="#contact" class="btn-primary">Demander un devis gratuit</a>
            <a href="#services" class="btn-secondary">Voir nos services</a>
        </div>
    </div>
</header>

<section id="services">
    <div class="container">
        <div class="section-title">
            <h2>Nos Prestations</h2>
            <p>Des solutions durables pour votre confort thermique et sanitaire</p>
        </div>

        <div class="services-grid">
            <?php
            // On récupère les services depuis la base de données
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
                    echo '<p>Aucun service configuré pour le moment.</p>';
                }
            } catch (Exception $e) {
                echo '<p>Erreur de chargement des services.</p>';
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
                        <h4>Zone d'intervention</h4>
                        <p>Tout le Brabant Wallon</p>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone-alt"></i>
                    <div>
                        <h4>Téléphone</h4>
                        <p><a href="tel:+32000000000">+32 4XX XX XX XX</a></p> </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h4>Email</h4>
                        <p><a href="mailto:info@saniflo.be">info@saniflo.be</a></p> </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h4>Horaires</h4>
                        <p>Lundi - Vendredi: 8h00 - 18h00</p>
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
        <p>&copy; 2026 <strong>Saniflo SRL</strong> - Tous droits réservés.</p>
        <p class="signature">Site web réalisé avec passion.</p>
    </div>
</footer>

</body>
</html>