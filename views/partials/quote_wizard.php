<section id="devis-wizard" style="scroll-margin-top: 120px;" class="section-padding">

    <style>
        .responsive-grid {
            display: grid;
            gap: 15px;
        }
        .grid-1-1 { grid-template-columns: 1fr 1fr; }
        .grid-2-1 { grid-template-columns: 2fr 1fr; }
        .grid-1-2 { grid-template-columns: 1fr 2fr; }

        .radio-card-group {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
        }

        .payment-options {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Mode Mobile (Téléphones et petites tablettes) */
        @media (max-width: 768px) {
            .grid-1-1, .grid-2-1, .grid-1-2 {
                grid-template-columns: 1fr; /* Tout passe sur 1 seule colonne */
                gap: 15px;
            }
            .radio-card-group {
                flex-direction: column;
                gap: 15px;
            }
            .radio-card-group label {
                width: 100%;
            }
            .wizard-buttons {
                display: flex;
                flex-direction: column-reverse; /* Met "Suivant" au-dessus de "Précédent" sur mobile */
                gap: 15px;
                margin-top: 20px;
            }
            .wizard-buttons button {
                width: 100%; /* Boutons larges pour faciliter le clic au doigt */
                margin: 0 !important;
            }
            .payment-options {
                flex-direction: column;
                gap: 10px;
            }
            .wizard-steps {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
    </style>

    <div class="container">
        <div class="wizard-container">
            <div class="section-title">
                <h2>Prise de rendez-vous</h2>
                <p>Vos informations complètes pour une intervention efficace.</p>
            </div>

            <div id="zone-alert" style="display:none; margin-bottom: 20px; padding: 15px; border-radius: 8px; border-left: 5px solid #ff9800; background: #fff3e0; color: #e65100;">
                <i class="fas fa-exclamation-triangle"></i> <span id="zone-alert-text"></span>
            </div>

            <div class="wizard-steps">
                <div class="step-indicator active" data-step="0" data-title="Profil">1</div>
                <div class="step-indicator" data-step="1" data-title="Appareil">2</div>
                <div class="step-indicator" data-step="2" data-title="Coordonnées">3</div>
                <div class="step-indicator" data-step="3" data-title="Date">4</div>
            </div>

            <form id="wizardForm" action="index.php?page=reservation" method="POST">

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="step active-step">
                    <h3><i class="fas fa-user-circle"></i> Votre Profil</h3>

                    <div class="form-group radio-card-group">
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
                            <small style="color:#666; font-size:0.8rem; display:block; margin-top:5px;">* Si > 10 ans : TVA 6%. Sinon : TVA 21%.</small>
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

                    <div class="wizard-buttons" style="display: flex; justify-content: flex-end;">
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

                    <div class="responsive-grid grid-1-1" style="margin-bottom: 15px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Modèle</label>
                            <input type="text" name="device_model" placeholder="Ex: Vitodens 200-W" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Puissance (kW)</label>
                            <input type="text" name="device_kw" placeholder="Ex: 24 kW">
                        </div>
                    </div>

                    <div class="responsive-grid grid-1-1">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>N° de série</label>
                            <input type="text" name="device_serial" placeholder="Voir plaque signalétique">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Année</label>
                            <input type="number" name="device_year" placeholder="Ex: 2018">
                        </div>
                    </div>

                    <div class="wizard-buttons" style="display: flex; justify-content: space-between; margin-top: 20px;">
                        <button type="button" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                        <button type="button" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="step">
                    <h3><i class="fas fa-file-invoice"></i> Vos Coordonnées</h3>

                    <div class="responsive-grid grid-1-1" style="margin-bottom: 15px;">
                        <div class="form-group" style="margin-bottom: 0;"><input type="text" name="lastname" placeholder="Nom" required></div>
                        <div class="form-group" style="margin-bottom: 0;"><input type="text" name="firstname" placeholder="Prénom" required></div>
                    </div>

                    <div class="form-group"><input type="email" name="email" placeholder="E-mail" required></div>

                    <div class="form-group">
                        <input type="tel" name="tel" placeholder="GSM (ex: 0495 12 34 56)" pattern="[0-9\+\s\-\.]{8,15}" title="Veuillez entrer un numéro de téléphone valide (8 à 15 caractères)" required>
                    </div>

                    <div class="responsive-grid grid-2-1" style="margin-bottom: 15px;">
                        <input type="text" name="billing_street" placeholder="Rue et numéro" required style="margin-bottom: 0;">
                        <input type="text" name="billing_box" placeholder="Boîte" style="margin-bottom: 0;">
                    </div>

                    <div class="responsive-grid grid-1-2">
                        <input type="text" name="zip" id="wizard_zip" placeholder="Code Postal" required style="margin-bottom: 0;">
                        <input type="text" name="billing_city" placeholder="Commune" required style="margin-bottom: 0;">
                    </div>

                    <div style="margin-top: 20px; margin-bottom: 15px;">
                        <label style="font-weight: bold; display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="worksite_same" id="worksite_check" checked onchange="toggleWorksite(this.checked)" style="width: auto; margin: 0;">
                            Adresse de chantier identique ?
                        </label>
                    </div>

                    <div id="worksite-fields" style="display: none; background: #f9f9f9; padding: 15px; border-radius: 8px;">
                        <h4 style="margin-top: 0;">📍 Adresse du chantier</h4>
                        <div class="responsive-grid grid-1-2" style="margin-bottom: 15px;">
                            <input type="text" name="worksite_zip" id="worksite_zip" placeholder="CP Chantier" style="margin-bottom: 0;">
                            <input type="text" name="worksite_city" placeholder="Commune" style="margin-bottom: 0;">
                        </div>
                        <input type="text" name="worksite_street" placeholder="Rue et numéro" style="width:100%; margin-bottom: 0;">
                    </div>

                    <div class="wizard-buttons" style="display: flex; justify-content: space-between; margin-top: 20px;">
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
                        <p style="margin-top: 10px;">Recherche des dates disponibles...</p>
                    </div>

                    <div class="form-group">
                        <label>Dates disponibles (Lundi uniquement)</label>
                        <select id="date_select" required style="background: white; width: 100%;">
                            <option value="">-- Sélectionnez d'abord vos coordonnées --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Heure de passage</label>
                        <select id="time_slots" disabled style="background: #f4f7f6; width: 100%;">
                            <option value="">-- Choisissez une date --</option>
                        </select>
                    </div>

                    <input type="hidden" name="appointment_date" id="final_date">
                    <input type="hidden" name="appointment_time" id="final_time">

                    <div class="payment-selection" style="margin-top:20px; border-top: 1px solid #eee; padding-top: 20px;">
                        <h4 style="margin-bottom: 15px;">Mode de paiement</h4>
                        <div class="payment-options">
                            <label class="radio-option">
                                <input type="radio" name="payment_method" value="stripe" checked onchange="updateWizardPrice()">
                                <span>Paiement en ligne (CB)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="payment_method" value="after" onchange="updateWizardPrice()">
                                <span>Après intervention (+3%)</span>
                            </label>
                        </div>
                    </div>

                    <div class="price-display" style="background: #f1f8e9; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center; font-weight: bold; color: #2e7d32; font-size: 1.1rem;">
                        Total HTVA estimé : <span id="display_price">160.00</span> €
                        <input type="hidden" name="total_price_htva" id="input_price" value="160">
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <textarea name="description" placeholder="Commentaires additionnels (Accès, code porte, étage...)" style="width: 100%; min-height: 80px;"></textarea>
                    </div>

                    <div class="wizard-buttons" style="display: flex; justify-content: space-between; margin-top: 20px;">
                        <button type="button" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                        <button type="submit" class="btn-primary" id="confirm-btn" disabled>Confirmer le Rendez-vous</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</section>