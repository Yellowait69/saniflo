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