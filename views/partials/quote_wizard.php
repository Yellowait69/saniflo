<section id="devis-wizard" style="scroll-margin-top: 120px;" class="section-padding">
    <div class="container">
        <div class="wizard-container">
            <div class="section-title">
                <h2>Prise de rendez-vous</h2>
                <p>Vos informations complètes pour une intervention efficace.</p>
            </div>

            <div id="zone-alert" style="display:none; margin-bottom: 20px; padding: 15px; border-radius: 8px; border-left: 5px solid #ff9800; background: #fff3e0; color: #e65100;">
                <i class="fas fa-exclamation-triangle"></i> <span id="zone-alert-text"></span>
            </div>

            <!-- Indicateurs de progression -->
            <div class="wizard-steps">
                <div class="step-indicator active" data-step="0" data-title="Profil">1</div>
                <div class="step-indicator" data-step="1" data-title="Demande">2</div>
                <div class="step-indicator" data-step="2" data-title="Coordonnées">3</div>
                <div class="step-indicator" data-step="3" data-title="Planification">4</div>
            </div>

            <form id="wizardForm" action="index.php?page=reservation" method="POST">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <!-- ETAPE 1 : PROFIL ET TVA -->
                <div class="step active-step">
                    <h3><i class="fas fa-user-circle"></i> Votre Profil</h3>

                    <div class="form-group" style="display: flex; justify-content: center; gap: 30px; margin-bottom: 30px;">
                        <label class="radio-card">
                            <input type="radio" name="is_company" id="type_private" value="0" checked onchange="toggleCompanyFields(false); updateWizardPrice();">
                            <span><i class="fas fa-home"></i> Particulier</span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="is_company" id="type_company" value="1" onchange="toggleCompanyFields(true); updateWizardPrice();">
                            <span><i class="fas fa-building"></i> Société</span>
                        </label>
                    </div>

                    <div id="private-fields">
                        <div class="form-group">
                            <label>Âge de l'habitation (Calcul de la TVA) <span style="color:red;">*</span></label>
                            <select name="vat_rate_private" id="vat_rate_private" required onchange="updateWizardPrice()">
                                <option value="6">Première occupation à titre privé depuis PLUS de 10 ans (TVA 6%)</option>
                                <option value="21">Première occupation à titre privé depuis MOINS de 10 ans (TVA 21%)</option>
                            </select>
                        </div>
                    </div>

                    <div id="company-fields" style="display:none; background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #eee;">
                        <div class="form-group">
                            <label>Raison sociale de la société <span style="color:red;">*</span></label>
                            <input type="text" name="company_name" id="company_name_input" placeholder="Ex: Saniflo SRL">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Numéro de TVA <span style="color:red;">*</span></label>
                                <input type="text" name="vat_number" id="vat_number_input" placeholder="Ex: BE 0XXX.XXX.XXX">
                            </div>
                            <div class="form-group">
                                <label>Régime TVA <span style="color:red;">*</span></label>
                                <select name="vat_rate_company" id="vat_rate_company" onchange="updateWizardPrice()">
                                    <option value="21">TVA 21% (Non assujetti / Standard)</option>
                                    <option value="0">TVA 0% (Autoliquidation / Co-contractant)</option>
                                    <option value="6">TVA 6% (Syndic / Logement privé > 10 ans)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- ETAPE 2 : APPAREIL & INTERVENTION -->
                <div class="step">
                    <h3><i class="fas fa-tools"></i> Votre Demande</h3>

                    <div class="form-group mb-4" style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #1976d2;">
                        <label style="font-weight: bold; margin-bottom: 10px; display: block;">Êtes-vous déjà client chez Saniflo ?</label>
                        <div style="display: flex; gap: 20px;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <input type="radio" name="client_status" value="new" checked onchange="toggleClientStatus(this.value)"> Nouveau client
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <input type="radio" name="client_status" value="existing" onchange="toggleClientStatus(this.value)"> Client existant
                            </label>
                        </div>
                        <small style="display: block; margin-top: 5px; color: #0d47a1;">Si vous êtes déjà client, les informations de votre appareil ne sont pas obligatoires.</small>
                    </div>

                    <div class="form-group">
                        <label>Type de demande <span style="color:red;">*</span></label>
                        <select name="service_type" id="service_type" onchange="handleServiceLogic(); updateWizardPrice();" required style="font-weight: bold;">
                            <option value="" data-price="0">-- Choisissez une option --</option>
                            <option value="devis" data-price="0" data-type="devis">Demande de devis (Déplacement sur site)</option>
                            <option value="entretien_gaz_viessmann" data-price="160" data-type="entretien">Entretien Chaudière GAZ - Viessmann (160€ HTVA - Tous les 2 ans)</option>
                            <option value="entretien_mazout_viessmann" data-price="190" data-type="entretien">Entretien Chaudière MAZOUT - Viessmann (190€ HTVA - Tous les ans)</option>
                            <option value="entretien_adoucisseur_bwt" data-price="140" data-type="entretien">Entretien Adoucisseur - BWT (140€ HTVA - Tous les 4 ans)</option>
                            <option value="entretien_autre" data-price="0" data-type="block">Entretien d'une AUTRE MARQUE</option>
                        </select>
                    </div>

                    <div id="maintenance-disclaimer" style="display:none; background: #fff3e0; padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800; margin-bottom: 20px; font-size: 0.9rem;">
                        <strong>⚠️ Conditions d'entretien :</strong><br>
                        - L'entretien <strong>ne comprend pas</strong> les dépannages ni les pièces d'usure.<br>
                        - L'installation doit être réglementaire et conforme aux normes en vigueur.<br>
                        - Si vous souhaitez une intervention pour autre chose (réparation, fuite...), cela <strong>ne pourra pas</strong> être fait en même temps.
                    </div>

                    <div id="other-brand-alert" style="display:none; background: #ffebee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336; margin-bottom: 20px; color: #c62828;">
                        <strong>Information importante :</strong> Pour l'entretien d'une autre marque, la prise de rendez-vous en ligne n'est pas disponible. Merci de nous contacter au préalable.
                    </div>

                    <!-- Détails de l'appareil -->
                    <div id="device-details-group">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Marque <span class="boiler-req" style="color:red;">*</span></label>
                                <input type="text" name="device_brand" id="device_brand" class="boiler-field required-boiler" placeholder="Ex: Viessmann, BWT..." required>
                            </div>
                            <div class="form-group">
                                <label>Modèle <span class="boiler-req" style="color:red;">*</span></label>
                                <input type="text" name="device_model" id="device_model" class="boiler-field required-boiler" placeholder="Ex: Vitodens 200" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label>Année <span class="boiler-req" style="color:red;">*</span></label>
                                <input type="number" name="device_year" id="device_year" class="boiler-field required-boiler" placeholder="Ex: 2018" required>
                            </div>
                            <div class="form-group">
                                <label>Puissance (kW)</label>
                                <input type="text" name="device_kw" id="device_kw" class="boiler-field" placeholder="Ex: 24 kW">
                            </div>
                            <div class="form-group">
                                <label>N° de série</label>
                                <input type="text" name="device_serial" id="device_serial" class="boiler-field" placeholder="Optionnel">
                            </div>
                        </div>
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                        <button type="button" class="next-btn" id="next-btn-step2">Suivant <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- ETAPE 3 : COORDONNÉES -->
                <div class="step">
                    <h3><i class="fas fa-file-invoice"></i> Adresse de Facturation</h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group"><input type="text" name="lastname" placeholder="Nom *" required></div>
                        <div class="form-group"><input type="text" name="firstname" placeholder="Prénom *" required></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group"><input type="email" name="email" placeholder="E-mail *" required></div>
                        <div class="form-group">
                            <input type="tel" name="tel" placeholder="GSM (ex: 0495 12 34 56) *" pattern="^(?:\+32|0)[1-9][0-9]{7,8}$" title="Format exigé : 04XX XX XX XX ou +324XX XX XX XX" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                        <div class="form-group"><input type="text" name="billing_street" placeholder="Rue et numéro *" required></div>
                        <div class="form-group"><input type="text" name="billing_box" placeholder="Boîte (Bte)"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-top: 15px;">
                        <div class="form-group"><input type="text" name="zip" id="wizard_zip" placeholder="Code Postal *" required></div>
                        <div class="form-group"><input type="text" name="billing_city" placeholder="Commune *" required></div>
                    </div>

                    <div style="margin-top: 20px; background: #f0f7ff; padding: 15px; border-radius: 8px;">
                        <label style="font-weight: bold; display: flex; align-items: center; gap: 10px; cursor:pointer; color: #0056b3;">
                            <input type="checkbox" name="worksite_same" id="worksite_check" checked onchange="toggleWorksite(this.checked)">
                            Le lieu d'intervention (chantier) est identique à l'adresse de facturation
                        </label>
                    </div>

                    <div id="worksite-fields" style="display: none; margin-top: 15px; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccc;">
                        <h4 style="margin-top:0; color:#333;">📍 Adresse et contact du chantier</h4>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group"><input type="text" name="worksite_lastname" id="worksite_lastname" placeholder="Nom de l'occupant"></div>
                            <div class="form-group"><input type="text" name="worksite_firstname" id="worksite_firstname" placeholder="Prénom de l'occupant"></div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group"><input type="email" name="worksite_email" id="worksite_email" placeholder="E-mail de l'occupant"></div>
                            <div class="form-group"><input type="tel" name="worksite_tel" id="worksite_tel" placeholder="GSM de l'occupant"></div>
                        </div>

                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group"><input type="text" name="worksite_street" id="worksite_street" placeholder="Rue et numéro"></div>
                            <div class="form-group"><input type="text" name="worksite_box" placeholder="Boîte (Bte)"></div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
                            <div class="form-group"><input type="text" name="worksite_zip" id="worksite_zip" placeholder="CP Chantier"></div>
                            <div class="form-group"><input type="text" name="worksite_city" id="worksite_city" placeholder="Commune"></div>
                        </div>
                    </div>

                    <div class="wizard-buttons">
                        <button type="button" class="prev-btn"><i class="fas fa-arrow-left"></i> Précédent</button>
                        <button type="button" class="next-btn">Suivant <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- ETAPE 4 : DATE ET PAIEMENT -->
                <div class="step">
                    <h3><i class="fas fa-calendar-alt"></i> Planification & Paiement</h3>

                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; color: #0d47a1;">
                        <i class="fas fa-info-circle"></i> Voici les prochaines disponibilités pour votre zone (<strong>CP <span id="display-zip">...</span></strong>). Vous pouvez choisir le créneau qui vous convient.
                    </div>

                    <div id="slots-loader" style="text-align:center; padding: 20px; display:none;">
                        <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; color: #0070cd;"></i>
                        <p>Recherche des dates disponibles...</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Date <span style="color:red;">*</span></label>
                            <select id="date_select" name="appointment_date" required style="background: white;">
                                <option value="">-- Saisissez vos coordonnées --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Heure <span style="color:red;">*</span></label>
                            <select id="time_slots" name="appointment_time" disabled style="background: #f4f7f6;" required>
                                <option value="">-- Choisissez une date --</option>
                            </select>
                        </div>
                    </div>

                    <div class="payment-selection" style="margin-top:20px; border-top: 1px solid #eee; padding-top: 20px;">
                        <h4>Mode de paiement</h4>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <label class="radio-option">
                                <input type="radio" name="payment_method" value="stripe" checked onchange="updateWizardPrice()">
                                <span>Paiement direct en ligne (Carte Bancaire)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="payment_method" value="after" onchange="updateWizardPrice()">
                                <span>Facture après intervention (+3% de frais admin.)</span>
                            </label>
                        </div>
                    </div>

                    <div class="price-display" style="background: #f1f8e9; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center; font-size: 1.1rem; color: #2e7d32; border: 1px solid #c5e1a5;">
                        <strong>Total estimé de l'intervention (TVAC) : <span id="display_price">0.00</span> €</strong>
                        <p style="font-size: 0.85rem; margin-top: 5px; color: #555;">(Ce montant est donné à titre indicatif et ne s'applique pas aux demandes de devis)</p>
                        <input type="hidden" name="total_price_htva" id="input_price_htva" value="0">
                        <input type="hidden" name="total_price_tvac" id="input_price_tvac" value="0">
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label>Commentaires / Informations utiles :</label>
                        <textarea name="description" rows="4" placeholder="Ex: Accès difficile, code porte, chien méchant, demande spécifique..."></textarea>
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