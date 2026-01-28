<footer class="main-footer">
    <div class="container">
        <div class="footer-grid">

            <div class="footer-col brand-col">
                <img src="img/logo-removebg-preview.png" alt="Saniflo Logo" class="footer-logo">

                <p>Votre expert en confort thermique et sanitaire dans le Brabant Wallon depuis 1997. Qualité, service et expertise familiale.</p>
            </div>

            <div class="footer-col links-col">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="#accueil">Accueil</a></li>
                    <li><a href="#apropos">À Propos</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#devis">Demande de Devis</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>

            <div class="footer-col contact-col">
                <h4>Nous contacter</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Rue de Fontenelle, 15<br>1325 Dion-Valmont</li>
                    <li><i class="fas fa-phone-alt"></i> <a href="tel:0495501717">0495 50 17 17</a></li>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:info@saniflo.be">info@saniflo.be</a></li>
                </ul>
            </div>

            <div class="footer-col legal-col">
                <h4>Informations Légales</h4>
                <p><strong>Saniflo SRL</strong></p>
                <p>TVA : BE 0461.290.428</p>
                <p>Agrément Gaz : CERGA / G1 / G2</p>
                <p>Agrément Mazout : L1 / TV</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 1997 - <?= date("Y") ?> <strong>Saniflo SRL</strong>. Tous droits réservés.</p>
            <div class="legal-links">
                <a href="#" onclick="openModal('modal-privacy'); return false;">Politique de confidentialité (RGPD)</a>
                <span class="separator">|</span>
                <a href="#" onclick="openModal('modal-terms'); return false;">Mentions Légales</a>
            </div>
        </div>
    </div>
</footer>

<div id="modal-privacy" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('modal-privacy')">&times;</span>
        <h2>Politique de Confidentialité (RGPD)</h2>
        <div class="modal-body-scroll">
            <p><strong>1. Responsable du traitement</strong><br>
                Le responsable du traitement des données est Saniflo SRL, situé Rue de Fontenelle 15, 1325 Dion-Valmont (BE 0461.290.428).</p>

            <p><strong>2. Données collectées</strong><br>
                Via nos formulaires (contact et devis), nous collectons : Nom, Prénom, Email, Téléphone, Adresse et informations relatives à votre projet. Ces données sont strictement nécessaires pour répondre à votre demande.</p>

            <p><strong>3. Utilisation des données</strong><br>
                Vos données sont utilisées uniquement pour : l'établissement de devis, la prise de rendez-vous et la facturation. Elles ne sont <strong>jamais vendues</strong> à des tiers.</p>

            <p><strong>4. Conservation</strong><br>
                Les données de devis sans suite sont conservées 3 ans maximum. Les données de facturation sont conservées selon les obligations légales (7 à 10 ans).</p>

            <p><strong>5. Vos droits</strong><br>
                Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et de suppression de vos données. Contactez-nous à info@saniflo.be pour exercer ce droit.</p>
        </div>
    </div>
</div>

<div id="modal-terms" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('modal-terms')">&times;</span>
        <h2>Mentions Légales</h2>
        <div class="modal-body-scroll">
            <p><strong>Éditeur du site :</strong> Saniflo SRL</p>
            <p><strong>Siège social :</strong> Rue de Fontenelle 15, 1325 Dion-Valmont, Belgique</p>
            <p><strong>Numéro d'entreprise (BCE) :</strong> BE 0461.290.428</p>
            <p><strong>Gérants :</strong> Jean-François Dengis & Florence Lambinon</p>
            <p><strong>Hébergement :</strong> Alwaysdata - 91 rue du Faubourg Saint Honoré, 75008 Paris.</p>
            <p><strong>Propriété intellectuelle :</strong> Tous les contenus (textes, images, logo) présents sur ce site sont la propriété exclusive de Saniflo SRL. Toute reproduction est interdite sans autorisation.</p>
        </div>
    </div>
</div>