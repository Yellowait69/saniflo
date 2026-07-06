/**
 * scripts.js - Version Optimisée Finale
 * Navigation, Formulaires, Logique Wizard (TVA, API, UI) et Correctifs visuels.
 */

// ==========================================
// INITIALISATION GLOBALE AU CHARGEMENT
// ==========================================
document.addEventListener('DOMContentLoaded', function() {

    // 1. CORRECTIF SCROLL : Force la page à s'afficher en haut au chargement
    setTimeout(function() {
        window.scrollTo(0, 0);
    }, 15);

    // 2. NAVIGATION & MENU BURGER
    const navLinks = document.querySelector('.nav-links');
    const burger = document.querySelector('.burger');

    if (burger) {
        burger.addEventListener('click', () => navLinks.classList.toggle('active'));
    }

    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => navLinks.classList.remove('active'));
    });

    // 3. FORMULAIRE DE CONTACT (Changement dynamique du placeholder)
    const contactForm = document.querySelector('.contact-form form');
    if (contactForm) {
        const selectObjet = contactForm.querySelector('select[name="objet"]');
        const messageArea = contactForm.querySelector('textarea[name="message"]');

        if (selectObjet && messageArea) {
            const placeholders = {
                'devis': "Décrivez votre projet (installation, remplacement...) pour un devis précis.",
                'entretien': "Précisez la marque, le modèle et l'année de votre appareil pour un entretien.",
                'default': "Comment pouvons-nous vous aider ?"
            };

            selectObjet.addEventListener('change', function() {
                messageArea.placeholder = placeholders[this.value] || placeholders['default'];
            });
        }
    }

    // 4. INITIALISATION DU WIZARD DE RENDEZ-VOUS
    const steps = document.querySelectorAll('.step');
    if (steps.length > 0) {
        initWizard(steps);
        initDynamicUI();
    }
});

// ==========================================
// LOGIQUE DU WIZARD (ÉTAPE PAR ÉTAPE ET API)
// ==========================================
let availableDaysData = []; // Stockage global des créneaux API

function initWizard(steps) {
    const indicators = document.querySelectorAll('.step-indicator');
    let currentStep = 0;

    showStep(currentStep);

    function showStep(n) {
        steps.forEach((step, index) => {
            step.classList.toggle('active-step', index === n);
        });
        updateIndicators(n);
    }

    function updateIndicators(n) {
        indicators.forEach((ind, index) => {
            ind.classList.toggle('active', index === n);
            if (index < n) {
                ind.classList.add('completed');
                ind.innerHTML = '<i class="fas fa-check"></i>';
            } else {
                ind.classList.remove('completed');
                ind.innerHTML = index + 1;
            }
        });
    }

    // --- BOUTON SUIVANT ---
    document.querySelectorAll('.next-btn').forEach(button => {
        button.addEventListener('click', () => {
            const currentStepEl = steps[currentStep];

            // Vérification uniquement des champs requis, visibles et non désactivés
            const currentInputs = Array.from(currentStepEl.querySelectorAll('input, select, textarea'))
                .filter(input => input.hasAttribute('required') && !input.disabled && input.offsetParent !== null);

            let isValid = true;
            for (let input of currentInputs) {
                if (!input.checkValidity()) {
                    input.reportValidity(); // Affiche la bulle d'erreur native du navigateur
                    isValid = false;
                    break; // Arrête la boucle à la première erreur
                }
            }

            if (isValid) {
                // Transition vers l'étape de Planification (Étape 3 -> 4)
                if (currentStep === 2) {
                    const zipVal = document.getElementById('wizard_zip').value;
                    if (!zipVal) {
                        alert("Veuillez entrer un code postal valide.");
                        return;
                    }

                    // Mise à jour de l'affichage du CP pour le client
                    const displayZip = document.getElementById('display-zip');
                    if (displayZip) displayZip.innerText = zipVal;

                    // Lancement de la recherche API des dates
                    fetchNextDates(zipVal);
                }

                currentStep++;
                showStep(currentStep);
            }
        });
    });

    // --- BOUTON PRÉCÉDENT ---
    document.querySelectorAll('.prev-btn').forEach(button => {
        button.addEventListener('click', () => {
            currentStep--;
            showStep(currentStep);
        });
    });

    // --- ÉCOUTEURS POUR LA SÉLECTION DE DATE/HEURE ---
    const dateSelect = document.getElementById('date_select');
    if (dateSelect) {
        dateSelect.addEventListener('change', function() {
            const selectedDateIso = this.value;
            const timeSelect = document.getElementById('time_slots');
            const confirmBtn = document.getElementById('confirm-btn');

            if (timeSelect) {
                timeSelect.innerHTML = '<option value="">-- Choisissez une heure --</option>';
                timeSelect.disabled = true;
            }
            if (confirmBtn) confirmBtn.disabled = true;

            if (!selectedDateIso) return;

            // Récupération des heures pour la date choisie
            const dayData = availableDaysData.find(d => d.date_iso === selectedDateIso);

            if (dayData && dayData.slots.length > 0) {
                if (timeSelect) {
                    timeSelect.disabled = false;
                    dayData.slots.forEach(slot => {
                        let opt = document.createElement('option');
                        opt.value = slot;
                        opt.text = slot;
                        timeSelect.add(opt);
                    });
                }
                const finalDateInput = document.getElementById('final_date');
                if (finalDateInput) finalDateInput.value = selectedDateIso;
            }
        });
    }

    const timeSelect = document.getElementById('time_slots');
    if (timeSelect) {
        timeSelect.addEventListener('change', function() {
            const selectedTime = this.value;
            const confirmBtn = document.getElementById('confirm-btn');

            if (selectedTime) {
                const finalTimeInput = document.getElementById('final_time');
                if (finalTimeInput) finalTimeInput.value = selectedTime;

                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.style.opacity = "1";
                    confirmBtn.style.cursor = "pointer";
                }
            } else {
                if (confirmBtn) confirmBtn.disabled = true;
            }
        });
    }
}

// ==========================================
// RECHERCHE API DES DATES
// ==========================================
function fetchNextDates(zip) {
    const dateSelect = document.getElementById('date_select');
    const timeSelect = document.getElementById('time_slots');
    const loader = document.getElementById('slots-loader');
    const confirmBtn = document.getElementById('confirm-btn');

    // Reset interface
    if (dateSelect) dateSelect.innerHTML = '<option value="">Recherche en cours...</option>';
    if (timeSelect) {
        timeSelect.innerHTML = '<option value="">-- Choisissez une date --</option>';
        timeSelect.disabled = true;
    }
    if (confirmBtn) confirmBtn.disabled = true;
    if (loader) loader.style.display = 'block';

    // Appel au script PHP
    fetch(`public/api_slots.php?zip=${zip}`)
        .then(response => response.json())
        .then(data => {
            if (loader) loader.style.display = 'none';

            if (data.error) {
                if (dateSelect) dateSelect.innerHTML = `<option value="">Erreur : ${data.error}</option>`;
                alert(data.error);
            } else if (data.days && data.days.length > 0) {
                availableDaysData = data.days; // Sauvegarde en mémoire

                if (dateSelect) {
                    dateSelect.innerHTML = '<option value="">-- Choisissez une date disponible --</option>';
                    data.days.forEach(day => {
                        let opt = document.createElement('option');
                        opt.value = day.date_iso;
                        opt.text = `${day.date_pretty} (${day.slots.length} créneaux)`;
                        dateSelect.add(opt);
                    });
                }
            } else {
                if (dateSelect) dateSelect.innerHTML = '<option value="">Aucune disponibilité trouvée</option>';
                alert("Aucune date disponible prochainement pour votre zone.");
            }
        })
        .catch(err => {
            console.error("Erreur API:", err);
            if (loader) loader.style.display = 'none';
            if (dateSelect) dateSelect.innerHTML = '<option value="">Erreur technique</option>';
        });
}

// ==========================================
// GESTION DYNAMIQUE DE L'UI DU FORMULAIRE
// ==========================================
function initDynamicUI() {
    // Initialisation de la TVA et de l'affichage au démarrage
    const isCompanyInit = document.getElementById('type_company') && document.getElementById('type_company').checked;
    if (window.toggleCompanyFields) window.toggleCompanyFields(isCompanyInit);

    // Calcul initial des prix au chargement
    setTimeout(updateWizardPrice, 300);
}

// ==========================================
// FONCTIONS GLOBALES (Appelées depuis le HTML)
// ==========================================

// --- NOUVEAU vs ANCIEN CLIENT ---
window.toggleClientStatus = function(status) {
    const isExisting = (status === 'existing');
    const boilerFields = document.querySelectorAll('.boiler-field');
    const requiredMarks = document.querySelectorAll('.boiler-req');

    boilerFields.forEach(field => {
        field.readOnly = isExisting;
        field.style.opacity = isExisting ? '0.5' : '1';
        field.style.backgroundColor = isExisting ? '#e9ecef' : '#ffffff';

        // Gestion sécurisée du "required" pour éviter les blocages HTML5
        if (field.classList.contains('required-boiler')) {
            if (isExisting) {
                field.removeAttribute('required');
            } else {
                field.setAttribute('required', 'required');
            }
        }
    });

    requiredMarks.forEach(mark => {
        mark.style.display = isExisting ? 'none' : 'inline';
    });
};

// --- SOCIÉTÉ vs PARTICULIER ---
window.toggleCompanyFields = function(isCompany) {
    const companyFields = document.getElementById('company-fields');
    const privateFields = document.getElementById('private-fields');

    const companyNameInput = document.getElementById('company_name_input');
    const vatNumberInput = document.getElementById('vat_number_input');
    const vatRateCompany = document.getElementById('vat_rate_company');
    const vatRatePrivate = document.getElementById('vat_rate_private');

    if (companyFields) companyFields.style.display = isCompany ? 'block' : 'none';
    if (privateFields) privateFields.style.display = isCompany ? 'none' : 'block';

    // Gestion stricte des champs obligatoires selon la visibilité
    if (isCompany) {
        if (companyNameInput) companyNameInput.setAttribute('required', 'required');
        if (vatNumberInput) vatNumberInput.setAttribute('required', 'required');
        if (vatRateCompany) vatRateCompany.setAttribute('required', 'required');
        if (vatRatePrivate) vatRatePrivate.removeAttribute('required');
    } else {
        if (companyNameInput) companyNameInput.removeAttribute('required');
        if (vatNumberInput) vatNumberInput.removeAttribute('required');
        if (vatRateCompany) vatRateCompany.removeAttribute('required');
        if (vatRatePrivate) vatRatePrivate.setAttribute('required', 'required');
    }
};

// --- ADRESSE DE CHANTIER DIFFÉRENTE ---
window.toggleWorksite = function(isSame) {
    const worksiteFields = document.getElementById('worksite-fields');
    const reqFields = ['worksite_zip', 'worksite_city', 'worksite_street'];

    if (worksiteFields) {
        worksiteFields.style.display = isSame ? 'none' : 'block';

        reqFields.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (isSame) {
                    el.removeAttribute('required'); // Non requis si masqué
                } else {
                    el.setAttribute('required', 'required'); // Requis si affiché
                }
            }
        });
    }
};

// ==========================================
// CALCUL DES TARIFS ET DE LA TVA
// ==========================================
window.updateWizardPrice = function() {
    const serviceSelect = document.getElementById('service_type');
    if (!serviceSelect || serviceSelect.selectedIndex === -1) return;

    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    let basePriceHTVA = parseFloat(selectedOption.getAttribute('data-price')) || 0;

    // Frais admin si paiement après intervention (+3%)
    const paymentMethodEl = document.querySelector('input[name="payment_method"]:checked');
    const paymentMethod = paymentMethodEl ? paymentMethodEl.value : 'stripe';
    if (paymentMethod === 'after') {
        basePriceHTVA = basePriceHTVA * 1.03;
    }

    // Détermination du taux de TVA
    let vatRate = 21;
    const isCompany = document.getElementById('type_company') && document.getElementById('type_company').checked;

    if (isCompany) {
        const companyVatSelect = document.getElementById('vat_rate_company');
        if (companyVatSelect) vatRate = parseFloat(companyVatSelect.value) || 0;
    } else {
        const privateVatSelect = document.getElementById('vat_rate_private');
        if (privateVatSelect) vatRate = parseFloat(privateVatSelect.value) || 21;
    }

    // Calcul du TVAC
    const finalPriceTVAC = basePriceHTVA * (1 + (vatRate / 100));

    // Mise à jour de l'affichage et des inputs cachés
    const displayEl = document.getElementById('display_price');
    const inputHtvaEl = document.getElementById('input_price_htva');
    const inputTvacEl = document.getElementById('input_price_tvac');

    if (displayEl) displayEl.innerText = finalPriceTVAC.toFixed(2);
    if (inputHtvaEl) inputHtvaEl.value = basePriceHTVA.toFixed(2);
    if (inputTvacEl) inputTvacEl.value = finalPriceTVAC.toFixed(2);
};

// ==========================================
// GESTION DES MODALES
// ==========================================
window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "block";
        document.body.style.overflow = "hidden"; // Empêche le scroll du fond
    }
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
    }
};

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
        document.body.style.overflow = "auto";
    }
};