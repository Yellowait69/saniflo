<section id="devis-wizard" class="section-padding">
    <div class="container">
        <div class="wizard-container">
            <div class="section-title">
                <h2>Prise de rendez-vous</h2>
                <p>Vos informations complètes pour une intervention efficace.</p>
            </div>

            <?= $message_status ?? '' ?>

            <div id="zone-alert" style="display:none; margin-bottom: 20px; padding: 15px; border-radius: 8px; border-left: 5px solid #ff9800; background: #fff3e0; color: #e65100;">
                <i class="fas fa-exclamation-triangle"></i> <span id="zone-alert-text"></span>
            </div>

            <div class="wizard-steps">
                <div class="step-indicator active" data-step="0" data-title="Profil">1</div>
                <div class="step-indicator" data-step="1" data-title="Appareil">2</div>
                <div class="step-indicator" data-step="2" data-title="Coordonnées">3</div>
                <div class="step-indicator" data-step="3" data-title="Date">4</div>
            </div>

            <form id="wizardForm" action="index.php#devis-wizard" method="POST">

                <div class="step active-step">
                    <h3><i class="fas fa-user-circle"></i> Votre Profil</h3>

                    <div class="form-group" style="justify-content: center; gap: 30px; margin-bottom: 30px;">
                        <label class="radio-card">
                            <input type="radio" name="is_company" value="0" checked onclick="toggleCompanyFields(false)">
                            <span><i class="fas fa-home"></i> Particulier</span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="is_company" value="1" onclick="toggleCompanyFields(true)">
                            <span><i class="fas fa-building"></i> Société</span>
                        </label>
                    </div>

                    <div id="private-fields">
                        <div class="form-group">
                            <label>Année de première occupation (TVA)</label>
                            <input type="number" name="housing_year" placeholder="Ex: 2010" min="1800" max="<?= date('Y') ?>">
                            <small style="color:#666; font-size:0.8rem;">* Si > 10 ans : TVA 6%. Sinon : TVA 21%.</small>
                        </div>
                    </div>

                    <div id="company-fields" style="display:none; background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label>Nom de la société</label>
                            <input type="text" name="company_name" placeholder="Raison Sociale">
                        </div>
                        <div class="form-group">
                            <label>Numéro de TVA</label>
                            <input type="text" name="vat_number" placeholder="BE 0XXX.XXX.XXX">
                        </div>
                        <div class="form-group">
                            <label>Régime TVA</label>
                            <select name="vat_regime">
                                <option value="21">TVA 21% (Non assujetti)</option>
                                <option value="0">TVA 0% (Autoliquidation / Co-contractant)</option>
                                <option value="6">TVA 6% (Syndic / Logement privé > 10 ans)</option>
                            </select>
                        </div>
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="step">
                    <h3><i class="fas fa-tools"></i> Votre Appareil</h3>

                    <div class="form-group">
                        <label>Type d'intervention</label>
                        <select name="service_type" id="service_type" onchange="updateWizardPrice()" required>
                            <option value="entretien_gaz_viessmann" data-price="160">Entretien GAZ - Viessmann (160€ HTVA)</option>
                            <option value="entretien_mazout_viessmann" data-price="190">Entretien MAZOUT - Viessmann (190€ HTVA)</option>
                            <option value="entretien_adoucisseur_bwt" data-price="140">Entretien Adoucisseur - BWT (140€ HTVA)</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Modèle</label>
                            <input type="text" name="device_model" placeholder="Ex: Vitodens 200-W" required>
                        </div>
                        <div class="form-group">
                            <label>Puissance (kW)</label>
                            <input type="text" name="device_kw" placeholder="Ex: 24 kW">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>N° de série</label>
                            <input type="text" name="device_serial" placeholder="Voir plaque signalétique">
                        </div>
                        <div class="form-group">
                            <label>Année</label>
                            <input type="number" name="device_year" placeholder="Ex: 2018">
                        </div>
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                        <button type="button" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="step">
                    <h3><i class="fas fa-file-invoice"></i> Vos Coordonnées</h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group"><input type="text" name="lastname" placeholder="Nom" required></div>
                        <div class="form-group"><input type="text" name="firstname" placeholder="Prénom" required></div>
                    </div>
                    <div class="form-group"><input type="email" name="email" placeholder="E-mail" required></div>
                    <div class="form-group"><input type="tel" name="tel" placeholder="GSM" required></div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                        <input type="text" name="billing_street" placeholder="Rue et numéro" required>
                        <input type="text" name="billing_box" placeholder="Boîte">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-top: 15px;">
                        <input type="text" name="zip" id="wizard_zip" placeholder="Code Postal" required>
                        <input type="text" name="billing_city" placeholder="Commune" required>
                    </div>

                    <div style="margin-top: 20px;">
                        <label style="font-weight: bold; display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="worksite_same" id="worksite_check" checked onchange="toggleWorksite(this.checked)">
                            Adresse de chantier identique ?
                        </label>
                    </div>

                    <div id="worksite-fields" style="display: none; margin-top: 15px; background: #f9f9f9; padding: 15px; border-radius: 8px;">
                        <h4>📍 Adresse du chantier</h4>
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-bottom: 15px;">
                            <input type="text" name="worksite_zip" id="worksite_zip" placeholder="CP Chantier">
                            <input type="text" name="worksite_city" placeholder="Commune">
                        </div>
                        <input type="text" name="worksite_street" placeholder="Rue et numéro" style="width:100%;">
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                        <button type="button" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="step">
                    <h3><i class="fas fa-calendar-alt"></i> Planification</h3>

                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; color: #0d47a1;">
                        <i class="fas fa-info-circle"></i> Voici les prochaines disponibilités pour votre zone (<strong>CP <span id="display-zip">...</span></strong>).
                    </div>

                    <div id="slots-loader" style="text-align:center; padding: 20px; display:none;">
                        <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; color: #0070cd;"></i>
                        <p>Recherche des dates disponibles...</p>
                    </div>

                    <div class="form-group">
                        <label>Dates disponibles (Lundi uniquement)</label>
                        <select id="date_select" required style="background: white;">
                            <option value="">-- Sélectionnez d'abord vos coordonnées --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Heure de passage</label>
                        <select id="time_slots" disabled style="background: #f4f7f6;">
                            <option value="">-- Choisissez une date --</option>
                        </select>
                    </div>

                    <input type="hidden" name="appointment_date" id="final_date">
                    <input type="hidden" name="appointment_time" id="final_time">

                    <div class="payment-selection" style="margin-top:20px; border-top: 1px solid #eee; padding-top: 20px;">
                        <h4>Mode de paiement</h4>
                        <div style="display: flex; gap: 20px;">
                            <label class="radio-option">
                                <input type="radio" name="payment_method" value="direct" checked onchange="updateWizardPrice()">
                                <span>Direct (Sur place)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="payment_method" value="after" onchange="updateWizardPrice()">
                                <span>Après intervention (+3% frais)</span>
                            </label>
                        </div>
                    </div>

                    <div class="price-display" style="background: #f1f8e9; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center; font-weight: bold; color: #2e7d32;">
                        Total HTVA estimé : <span id="display_price">160.00</span> €
                        <input type="hidden" name="total_price_htva" id="input_price" value="160">
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <textarea name="description" placeholder="Commentaires additionnels (Accès, code porte, étage...)"></textarea>
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                        <button type="submit" class="btn-primary" id="confirm-btn" disabled>Confirmer le Rendez-vous</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</section>