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