/**
 * scripts.js - Version Corrigée Saniflo SRL
 * Fixes: Bug "Données manquantes" + Validation Zip + Flatpickr
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
                    // --- AJOUT : Validation Code Postal avant d'aller au calendrier ---
                    // Si on est à l'étape 3 (index 2) et qu'on veut aller à la 4 (Planification)
                    if (currentStep === 2) {
                        const zipVal = document.getElementById('wizard_zip').value;
                        if (!zipVal) {
                            alert("Veuillez entrer un code postal valide.");
                            return;
                        }
                    }

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

// --- 5. LOGIQUE DE PLANIFICATION INTELLIGENTE (API + FLATPICKR) ---
document.addEventListener('DOMContentLoaded', function() {
    const zipInput = document.getElementById('wizard_zip');
    const calendarInput = document.getElementById('calendar_picker');
    const timeSelect = document.getElementById('time_slots');
    const loader = document.getElementById('slot-loader');

    let fp = null; // Instance Flatpickr

    // 1. Initialisation de Flatpickr sur le champ date
    if (calendarInput) {
        fp = flatpickr(calendarInput, {
            locale: "fr",
            minDate: "today",
            disable: [
                function(date) {
                    // Désactiver tout ce qui n'est pas un Lundi (1)
                    return (date.getDay() !== 1);
                }
            ],
            onChange: function(selectedDates, dateStr, instance) {
                fetchSlots(dateStr);
            }
        });
    }

    // 2. Fonction pour appeler l'API PHP
    function fetchSlots(dateStr) {
        // --- CORRECTION BUG MAJEUR ---
        // Si la date est vide (ex: effacement ou reset), on ne fait RIEN.
        // Cela empêche l'appel API qui causait l'erreur "Données manquantes".
        if (!dateStr) return;

        const zip = zipInput.value;

        // Sécurité : Si le zip est vide, on empêche la sélection
        if (!zip) {
            alert("Veuillez d'abord entrer votre Code Postal à l'étape précédente.");
            if (fp) fp.clear();
            return;
        }

        // Interface UI : Chargement
        timeSelect.innerHTML = '<option value="">Chargement...</option>';
        timeSelect.disabled = true;
        if(loader) loader.style.display = 'block';

        // Appel AJAX vers notre fichier PHP intermédiaire
        fetch(`public/api_slots.php?date=${dateStr}&zip=${zip}`)
            .then(response => response.json())
            .then(data => {
                if(loader) loader.style.display = 'none';
                timeSelect.innerHTML = ''; // Clear options

                if (data.error) {
                    // Cas d'erreur (Zone interdite, Lundi complet, Mauvaise zone...)
                    alert(data.error);
                    if (fp) fp.clear(); // On efface la date invalide
                    timeSelect.innerHTML = '<option value="">Date non disponible</option>';
                } else if (data.slots && data.slots.length > 0) {
                    // Cas Succès : On affiche les créneaux
                    timeSelect.disabled = false;

                    // Ajouter option par défaut
                    let defaultOpt = document.createElement('option');
                    defaultOpt.value = "";
                    defaultOpt.text = "Choisir une heure";
                    timeSelect.add(defaultOpt);

                    // Ajouter les heures reçues de l'API
                    data.slots.forEach(slot => {
                        let opt = document.createElement('option');
                        opt.value = slot;
                        opt.text = slot;
                        timeSelect.add(opt);
                    });
                } else {
                    // Cas bizarre : pas d'erreur mais pas de slots (ex: tout est pris)
                    timeSelect.innerHTML = '<option value="">Complet ce jour-là</option>';
                    alert("Aucun créneau disponible pour ce lundi (Complet ou restrictions géographiques).");
                }
            })
            .catch(err => {
                console.error(err);
                if(loader) loader.style.display = 'none';
                alert("Erreur technique : Impossible de récupérer les disponibilités. Veuillez réessayer.");
            });
    }

    // 3. Réinitialiser le calendrier si on change le code postal après coup
    if (zipInput) {
        zipInput.addEventListener('change', function() {
            // --- CORRECTION BUG ---
            // On utilise clear(false) : le 'false' signifie "Ne déclenche PAS l'événement onChange".
            // Donc fetchSlots() n'est PAS appelé, et on évite l'erreur.
            if (fp) fp.clear(false);

            if (timeSelect) {
                timeSelect.innerHTML = '<option value="">-- Sélectionnez une date d\'abord --</option>';
                timeSelect.disabled = true;
            }
        });
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