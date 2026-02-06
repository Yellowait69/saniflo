/**
 * scripts.js - Version Consolidée Saniflo SRL (Wizard & Planification)
 * Logic: 8 slots/Monday, 2 reserved (Max 6 web), Zip code restrictions.
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

        document.querySelectorAll('.next-btn').forEach(button => {
            button.addEventListener('click', () => {
                const currentStepEl = steps[currentStep];
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

        document.querySelectorAll('.prev-btn').forEach(button => {
            button.addEventListener('click', () => {
                currentStep--;
                showStep(currentStep);
            });
        });
    }
});

// --- 4. CALCUL DES TARIFS & PAIEMENT ---
function calculatePrice(basePrice, paymentMethod) {
    if (paymentMethod === 'after') {
        return (basePrice * 1.03).toFixed(2);
    }
    return basePrice.toFixed(2);
}

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

function toggleCompanyFields(isCompany) {
    const companyFields = document.getElementById('company-fields');
    const privateFields = document.getElementById('private-fields');
    if (companyFields) companyFields.style.display = isCompany ? 'block' : 'none';
    if (privateFields) privateFields.style.display = isCompany ? 'none' : 'block';
}

function toggleWorksite(isSame) {
    const worksiteFields = document.getElementById('worksite-fields');
    if (worksiteFields) {
        worksiteFields.style.display = isSame ? 'none' : 'block';
    }
}

// --- 5. LOGIQUE DE PLANIFICATION (CRITÈRES JEAN-FRANÇOIS) ---
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('wizard_date');
    const zipInput = document.getElementById('wizard_zip');
    const timeSelect = document.getElementById('wizard_time');

    if (dateInput && zipInput && timeSelect) {

        // Restriction : Uniquement les lundis
        dateInput.addEventListener('input', function() {
            if (!this.value) return;
            const date = new Date(this.value);
            const day = date.getUTCDay(); // 1 = Lundi
            if (day !== 1) {
                alert("Attention : Jean-François effectue les entretiens uniquement le lundi.");
                this.value = '';
            }
        });

        // Filtrage dynamique des heures selon le Code Postal
        function filterTimesByZip() {
            const zip = parseInt(zipInput.value);
            const options = timeSelect.querySelectorAll('option');

            // Réinitialiser l'affichage
            options.forEach(opt => opt.style.display = 'block');

            if (isNaN(zip)) return;

            // REGLE : 1980 et plus -> Uniquement sur demande
            if (zip >= 1980) {
                alert("Pour les codes postaux 1980 et plus, l'intervention se fait uniquement sur demande. Veuillez nous contacter.");
                zipInput.value = '';
                return;
            }

            // REGLE : 1400-1499 et 1500-1970 -> Uniquement 8h ou 15h30
            if ((zip >= 1400 && zip <= 1499) || (zip >= 1500 && zip <= 1970)) {
                options.forEach(opt => {
                    if (opt.value !== "08:00" && opt.value !== "15:30") {
                        opt.style.display = 'none';
                    }
                });
                // Si l'heure actuellement choisie devient invalide, on reset
                if (timeSelect.value !== "08:00" && timeSelect.value !== "15:30") {
                    timeSelect.value = "08:00";
                }
            }

            // REGLE : 1000-1210 (Bruxelles) -> Doivent être consécutifs
            if (zip >= 1000 && zip <= 1210) {
                // Note : On laisse le choix mais on informe l'utilisateur
                console.log("Zone Bruxelles : L'heure pourra être réajustée pour garantir des r-v consécutifs.");
            }
        }

        zipInput.addEventListener('input', filterTimesByZip);
        zipInput.addEventListener('change', filterTimesByZip);
    }
});

// --- 6. GESTION DES MODALES ---
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