/**
 * scripts.js - Version Consolidée Saniflo SRL (Wizard & Planification)
 */

// --- 1. NAVIGATION & MENU BURGER ---
const navLinks = document.querySelector('.nav-links');
const burger = document.querySelector('.burger');

if (burger) {
    burger.addEventListener('click', () => navLinks.classList.toggle('active'));
}

document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => navLinks.classList.remove('active'));
});

// --- 2. GESTION DU FORMULAIRE DE CONTACT (BASIQUE) ---
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.querySelector('.contact-form form');
    if (contactForm) {
        const selectObjet = contactForm.querySelector('select[name="objet"]');
        const messageArea = contactForm.querySelector('textarea[name="message"]');

        if (selectObjet && messageArea) {
            selectObjet.addEventListener('change', function() {
                switch(this.value) {
                    case 'devis':
                        messageArea.placeholder = "Décrivez votre projet (installation, remplacement...) pour un devis précis.";
                        break;
                    case 'entretien':
                        messageArea.placeholder = "Précisez la marque, le modèle et l'année de votre appareil pour un entretien.";
                        break;
                    default:
                        messageArea.placeholder = "Comment pouvons-nous vous aider ?";
                }
            });
        }
    }
});

// --- 3. LOGIQUE DU WIZARD DE RENDEZ-VOUS ---
document.addEventListener('DOMContentLoaded', function() {
    const steps = document.querySelectorAll('.step');
    const indicators = document.querySelectorAll('.step-indicator');
    let currentStep = 0;

    if (steps.length > 0) {
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

        // Boutons "Suivant"
        document.querySelectorAll('.next-btn').forEach(button => {
            button.addEventListener('click', () => {
                // On ne valide que les champs visibles de l'étape courante
                const currentStepEl = steps[currentStep];
                // Sélectionne tous les inputs requis qui ne sont pas cachés (ex: via display:none)
                const currentInputs = Array.from(currentStepEl.querySelectorAll('input[required], select[required], textarea[required]'))
                    .filter(input => input.offsetParent !== null);

                let isValid = true;

                currentInputs.forEach(input => {
                    if (!input.checkValidity()) {
                        input.reportValidity();
                        isValid = false;
                    }
                });

                if (isValid) {
                    currentStep++;
                    showStep(currentStep);
                }
            });
        });

        // Boutons "Précédent"
        document.querySelectorAll('.prev-btn').forEach(button => {
            button.addEventListener('click', () => {
                currentStep--;
                showStep(currentStep);
            });
        });
    }
});

// --- 4. CALCUL DES TARIFS & PAIEMENT ---
/**
 * Calcule le prix final selon le mode de paiement (+3% si après intervention)
 */
function calculatePrice(basePrice, paymentMethod) {
    if (paymentMethod === 'after') {
        return (basePrice * 1.03).toFixed(2);
    }
    return basePrice.toFixed(2);
}

/**
 * Mise à jour dynamique du prix dans le Wizard
 */
function updateWizardPrice() {
    const serviceSelect = document.getElementById('service_type');
    if (!serviceSelect) return;

    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    const basePrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;

    const paymentMethodEl = document.querySelector('input[name="payment_method"]:checked');
    const paymentMethod = paymentMethodEl ? paymentMethodEl.value : 'direct';

    const finalPrice = calculatePrice(basePrice, paymentMethod);

    const displayEl = document.getElementById('display_price');
    const inputEl = document.getElementById('input_price');

    if (displayEl) displayEl.innerText = finalPrice;
    if (inputEl) inputEl.value = finalPrice;
}

/**
 * Affiche/Masque les champs Société vs Particulier
 */
function toggleCompanyFields(isCompany) {
    const companyFields = document.getElementById('company-fields');
    const privateFields = document.getElementById('private-fields'); // Contient l'année pour la TVA

    // Gestion champs Société
    if (companyFields) {
        companyFields.style.display = isCompany ? 'block' : 'none';
        const inputs = companyFields.querySelectorAll('input, select');
        inputs.forEach(input => input.required = isCompany);
    }

    // Gestion champs Particulier (Année habitation pour TVA)
    if (privateFields) {
        privateFields.style.display = isCompany ? 'none' : 'block';
        // On rend optionnel si on est une société
        const pInputs = privateFields.querySelectorAll('input');
        pInputs.forEach(input => input.required = !isCompany);
    }
}

/**
 * Affiche/Masque les champs Adresse de Chantier
 * Appelé par la checkbox "Adresse identique"
 */
function toggleWorksite(isSame) {
    const worksiteFields = document.getElementById('worksite-fields');
    if (worksiteFields) {
        // Si c'est identique (isSame = true), on cache les champs chantier
        worksiteFields.style.display = isSame ? 'none' : 'block';

        const inputs = worksiteFields.querySelectorAll('input, select');
        inputs.forEach(input => {
            // Si c'est caché, ce n'est pas requis. Si c'est visible, on active le required.
            // On peut cibler spécifiquement les champs critiques (Rue, CP, Ville)
            if (!isSame) {
                // On rend obligatoire au moins la rue, le CP et la ville
                if (input.name.includes('street') || input.name.includes('zip') || input.name.includes('city')) {
                    input.required = true;
                }
            } else {
                input.required = false;
            }
        });
    }
}

// --- 5. VALIDATION PLANIFICATION (LUNDIS & ZONES) ---
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('wizard_date');
    const zipInput = document.getElementById('wizard_zip');
    const timeSelect = document.getElementById('wizard_time');

    if (dateInput && zipInput && timeSelect) {

        // Restriction Calendrier : Uniquement les lundis
        dateInput.addEventListener('input', function() {
            if (!this.value) return;

            const date = new Date(this.value);
            const day = date.getUTCDay(); // 1 = Lundi
            if (day !== 1) {
                alert("Les entretiens sont organisés uniquement le lundi.");
                this.value = '';
                return;
            }
            validateConstraints();
        });

        function validateConstraints() {
            if (!zipInput.value) return;
            const zip = parseInt(zipInput.value);
            const selectedTime = timeSelect.value;

            if (!selectedTime) return;

            // Zones 1400-1499 et 1500-1970 : Limité à 08h00 ou 15h30
            if ((zip >= 1400 && zip <= 1499) || (zip >= 1500 && zip <= 1970)) {
                if (selectedTime !== "08:00" && selectedTime !== "15:30") {
                    alert("Pour votre zone (" + zip + "), les rendez-vous sont limités à 08h00 ou 15h30.");
                    timeSelect.value = '';
                }
            }

            // Bruxelles (1000-1210) : Information (La validation consécutive est gérée côté PHP)
            if (zip >= 1000 && zip <= 1210) {
                console.log("Zone Bruxelles détectée.");
            }
        }

        [zipInput, timeSelect].forEach(el => el.addEventListener('change', validateConstraints));
    }
});

// --- 6. GESTION DES MODALES (RGPD / LEGAL) ---
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "block";
        document.body.style.overflow = "hidden";
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
        document.body.style.overflow = "auto";
    }
};

document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        });
    }
});