<form action="index.php?page=contact" method="POST">

    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <div class="form-group" style="margin-bottom: 15px;">
        <input type="text" name="nom" placeholder="Votre Nom" required
               style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 1rem;">
    </div>

    <div class="form-group" style="margin-bottom: 15px;">
        <input type="email" name="email" placeholder="Votre Email" required
               style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 1rem;">
    </div>

    <div class="form-group" style="margin-bottom: 15px;">
        <input type="tel" name="tel" placeholder="Votre Téléphone (ex: 0495 12 34 56)"
               pattern="[0-9\+\s\-\.]{8,15}" title="Veuillez entrer un numéro de téléphone valide (8 à 15 caractères)"
               style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 1rem;">
    </div>

    <div class="form-group" style="margin-bottom: 15px;">
        <select name="objet" required
                style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; background-color: #fff; font-size: 1rem; color: #555;">
            <option value="" disabled selected>Objet de votre demande</option>
            <option value="devis">Demande de devis</option>
            <option value="entretien">Demande d'entretien</option>
            <option value="info">Demande d'information</option>
            <option value="autre">Autre chose à préciser</option>
        </select>
    </div>

    <div class="form-group" style="margin-bottom: 20px;">
        <textarea name="message" rows="5" placeholder="Comment pouvons-nous vous aider ? (Détails de votre projet, modèle d'appareil...)" required
                  style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 1rem; resize: vertical;"></textarea>
    </div>

    <button type="submit" class="btn-primary full-width"
            style="width: 100%; padding: 15px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; border: none; transition: background 0.3s;">
        Envoyer ma demande
    </button>

    <p style="font-size: 0.8rem; color: #666; margin-top: 15px; text-align: center; line-height: 1.4;">
        En envoyant ce formulaire, vous acceptez notre
        <a href="#" onclick="openModal('modal-privacy'); return false;" style="color: var(--primary-blue); text-decoration: underline; font-weight: 500;">politique de confidentialité</a>.
    </p>

</form>