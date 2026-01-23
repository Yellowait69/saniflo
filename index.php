<?php
require 'db.php';

// --- TRAITEMENT DU FORMULAIRE DE CONTACT ---
$message_status = ''; // Pour stocker le message de succès ou d'erreur

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nettoyage des entrées (Sécurité)
    $nom = htmlspecialchars(strip_tags(trim($_POST['nom'])));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $tel = htmlspecialchars(strip_tags(trim($_POST['tel'])));
    $message = htmlspecialchars(strip_tags(trim($_POST['message'])));

    // Validation basique
    if (!empty($nom) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {

        // EXEMPLE : Envoi d'email (à configurer sur un serveur réel)
        $to = "info@saniflo.be";
        $subject = "Nouveau message de $nom via le site web";
        $headers = "From: $email" . "\r\n" .
            "Reply-To: $email" . "\r\n" .
            "X-Mailer: PHP/" . phpversion();

        // Simuler l'envoi (décommenter la ligne mail() sur un vrai serveur)
        // mail($to, $subject, "Tél: $tel\n\nMessage:\n$message", $headers);

        $message_status = '<div class="alert success">Merci ! Votre message a bien été envoyé. Nous vous recontacterons rapidement.</div>';
    } else {
        $message_status = '<div class="alert error">Veuillez remplir correctement tous les champs obligatoires.</div>';
    }
}
?>
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
    <style>
        /* CSS Ajouté pour les messages d'alerte */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        /* Ajustement image équipe pour qu'elle remplisse le cercle */
        .team-img img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    </style>
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
                        // LOGIQUE POUR LES IMAGES : On détecte le nom pour afficher la bonne photo
                        $name = $member['name'];
                        $imgHtml = '<i class="fas fa-user-tie"></i>'; // Par défaut

                        // Recherche de "Florence" ou "Jean" dans le nom (insensible à la casse)
                        if (stripos($name, 'Florence') !== false) {
                            $imgHtml = '<img src="img/Florence.jpg" alt="Florence Lambinon">';
                        } elseif (stripos($name, 'Jean') !== false || stripos($name, 'JF') !== false) {
                            $imgHtml = '<img src="img/JF.jpg" alt="Jean-François Dengis">';
                        }

                        echo '<div class="team-card">';
                        echo '<div class="team-img">' . $imgHtml . '</div>';
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
                    // Fallback propre si pas de services en DB
                    echo '<p style="text-align:center; width:100%;">Nos services incluent : Installation chaudières, Pompes à chaleur, Adoucisseurs d\'eau et Sanitaires complets.</p>';
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
        <div class="section-title white-title text-center compact-title">
            <h2>Agréments & Certifications</h2>
            <p>L'assurance d'un travail aux normes.</p>
        </div>

        <?php
        try {
            // Requête SQL (inchangée)
            $sql = "SELECT * FROM certifications 
                    ORDER BY FIELD(region, 'Général', 'Wallonie', 'Flandre', 'Bruxelles'), 
                    title ASC";
            $stmt = $pdo->query($sql);
            $certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($certifications) > 0) {
                // Regroupement (inchangé)
                $groupes = [];
                foreach ($certifications as $cert) {
                    $groupes[$cert['region']][] = $cert;
                }

                // Affichage
                foreach ($groupes as $regionName => $certsInRegion) {
                    // Titre de séparation stylisé
                    echo '<h3 class="region-separator"><span>' . htmlspecialchars($regionName) . '</span></h3>';

                    echo '<div class="cert-grid-compact">'; // Nouvelle classe CSS pour la grille

                    foreach ($certsInRegion as $cert) {
                        echo '
                        <div class="cert-item-sleek"> <div class="cert-content">
                                <h4>' . htmlspecialchars($cert['title']) . '</h4>
                                <p class="cert-number">' . htmlspecialchars($cert['number']) . '</p>
                            </div>
                        </div>';
                    }
                    echo '</div>';
                }

            } else {
                echo '<p class="no-data">Aucune certification.</p>';
            }
        } catch (Exception $e) { echo '<div class="alert">Erreur chargement.</div>'; }
        ?>
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
                <?php echo $message_status; ?>

                <form action="index.php#contact" method="POST">
                    <div class="form-group">
                        <input type="text" name="nom" placeholder="Votre Nom" required value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Votre Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <input type="tel" name="tel" placeholder="Votre Téléphone" value="<?php echo isset($_POST['tel']) ? htmlspecialchars($_POST['tel']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <textarea name="message" rows="5" placeholder="Comment pouvons-nous vous aider ?" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
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
    document.querySelector('.burger').addEventListener('click', () => {
        document.querySelector('.nav-links').classList.toggle('active');
    });
</script>

</body>
</html>