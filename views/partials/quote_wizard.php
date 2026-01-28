<section id="devis-wizard" class="section-padding">
    <div class="container">
        <div class="wizard-container">
            <div class="section-title">
                <h2>Prise de rendez-vous</h2>
                <p>Un projet spécifique ? Une urgence ? Nous sommes à votre écoute.</p>
            </div>
            <div class="wizard-steps">
                <div class="step-indicator active" data-step="0" data-title="Profil">1</div>
                <div class="step-indicator" data-step="1" data-title="Appareil">2</div>
                <div class="step-indicator" data-step="2" data-title="Rendez-vous">3</div>
                <div class="step-indicator" data-step="3" data-title="Facturation">4</div>
            </div>

            <form id="wizardForm" action="index.php#devis-wizard" method="POST">

                <div class="step active-step">
                    <h3>Vous êtes :</h3>
                    <div class="form-group">
                        <label><input type="radio" name="is_company" value="0" checked onclick="toggleCompanyFields(false)"> Particulier</label>
                        <label><input type="radio" name="is_company" value="1" onclick="toggleCompanyFields(true)"> Société</label>
                    </div>
                    <div id="company-fields" style="display:none;">
                        <input type="text" name="company_name" placeholder="Raison sociale">
                        <input type="text" name="vat_number" placeholder="N° de TVA">
                        <select name="vat_regime">
                            <option value="21">TVA 21%</option>
                            <option value="6">TVA 6% (Habitation > 10 ans)</option>
                            <option value="autoliquidation">Autoliquidation (0%)</option>
                        </select>
                    </div>
                    <div class="wizard-buttons">
                        <button type="button" class="next-btn">Suivant</button>
                    </div>
                </div>

                <div class="step">
                    <h3>Détails de l'appareil</h3>
                    <select name="service_type" id="service_type" onchange="updateWizardPrice()" required>
                        <option value="entretien_gaz_viessmann" data-price="160">Gaz Viessmann (160€)</option>
                        <option value="entretien_mazout_viessmann" data-price="190">Mazout Viessmann (190€)</option>
                        <option value="entretien_adoucisseur_bwt" data-price="140">Adoucisseur BWT (140€)</option>
                    </select>
                    <input type="text" name="device_model" placeholder="Modèle" required>
                    <input type="text" name="device_serial" placeholder="Numéro de série">
                    <input type="number" name="device_year" placeholder="Année d'installation">
                    <div class="wizard-buttons">
                        <button type="button" class="prev-btn">Précédent</button>
                        <button type="button" class="next-btn">Suivant</button>
                    </div>
                </div>

                <div class="step">
                    <h3>Planification</h3>
                    <input type="text" name="zip" id="wizard_zip" placeholder="Code Postal" required>
                    <input type="date" name="appointment_date" id="wizard_date" required>
                    <select name="appointment_time" id="wizard_time" required>
                        <option value="08:00">08:00</option>
                        <option value="09:00">09:00</option>
                        <option value="10:00">10:00</option>
                        <option value="11:00">11:00</option>
                        <option value="12:30">12:30</option>
                        <option value="13:30">13:30</option>
                        <option value="14:30">14:30</option>
                        <option value="15:30">15:30</option>
                    </select>

                    <div class="payment-selection">
                        <h4>Mode de paiement :</h4>
                        <label><input type="radio" name="payment_method" value="direct" checked onchange="updateWizardPrice()"> Direct (Prix normal)</label>
                        <label><input type="radio" name="payment_method" value="after" onchange="updateWizardPrice()"> Après intervention (+3%)</label>
                    </div>

                    <div class="price-display">
                        Prix HTVA estimé : <span id="display_price">160.00</span> €
                        <input type="hidden" name="total_price_htva" id="input_price" value="160">
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="prev-btn">Précédent</button>
                        <button type="button" class="next-btn">Suivant</button>
                    </div>
                </div>

                <div class="step">
                    <h3>Vos coordonnées</h3>
                    <input type="text" name="firstname" placeholder="Prénom" required>
                    <input type="text" name="lastname" placeholder="Nom" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="street" placeholder="Rue et numéro" required>
                    <input type="text" name="city" placeholder="Ville" required> <textarea name="description" placeholder="Commentaires éventuels"></textarea>

                    <div class="wizard-buttons">
                        <button type="button" class="prev-btn">Précédent</button>
                        <button type="submit" class="btn-primary">Confirmer le rendez-vous</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>