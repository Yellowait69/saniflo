/**
 * scripts.js - Version "Proposition Automatique"
 * Remplace le calendrier par une liste des prochaines disponibilités
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

    // Variable pour stocker les créneaux chargés depuis l'API
    let availableDaysData = [];

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

        // --- GESTION DU BOUTON SUIVANT ---
        document.querySelectorAll('.next-btn').forEach(button => {
            button.addEventListener('click', () => {
                const currentStepEl = steps[currentStep];
                // On vérifie uniquement les champs visibles requis
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
                    // Si on est à l'étape 3 (Coordonnées/Zip) et qu'on va vers l'étape 4 (Planification)
                    if (currentStep === 2) {
                        const zipVal = document.getElementById('wizard_zip').value;
                        if (!zipVal) {
                            alert("Veuillez entrer un code postal valide.");
                            return;
                        }

                        // Mise à jour de l'affichage du CP dans le message info
                        const displayZip = document.getElementById('display-zip');
                        if(displayZip) displayZip.innerText = zipVal;

                        // Lancement de la recherche automatique des dates
                        fetchNextDates(zipVal);
                    }

                    currentStep++;
                    showStep(currentStep);
                }
            });
        });

        // --- GESTION DU BOUTON PRÉCÉDENT ---
        document.querySelectorAll('.prev-btn').forEach(button => {
            button.addEventListener('click', () => {
                currentStep--;
                showStep(currentStep);
            });
        });
    }

    // --- 4. FONCTION API : CHARGER LES DATES DISPONIBLES (AUTOMATIQUE) ---
    function fetchNextDates(zip) {
        const dateSelect = document.getElementById('date_select');
        const timeSelect = document.getElementById('time_slots');
        const loader = document.getElementById('slots-loader');
        const confirmBtn = document.getElementById('confirm-btn');

        // Reset de l'interface
        if(dateSelect) dateSelect.innerHTML = '<option value="">Chargement...</option>';
        if(timeSelect) {
            timeSelect.innerHTML = '<option value="">-- Choisissez une date --</option>';
            timeSelect.disabled = true;
        }
        if(confirmBtn) confirmBtn.disabled = true;

        if(loader) loader.style.display = 'block';

        // Appel API avec le ZIP uniquement (plus de date précise)
        fetch(`public/api_slots.php?zip=${zip}`)
            .then(response => response.json())
            .then(data => {
                if(loader) loader.style.display = 'none';

                if (data.error) {
                    if(dateSelect) dateSelect.innerHTML = `<option value="">Erreur : ${data.error}</option>`;
                    alert(data.error);
                } else if (data.days && data.days.length > 0) {
                    // Sauvegarde des données reçues pour l'utilisation locale
                    availableDaysData = data.days;

                    // Remplissage du menu déroulant "Dates"
                    if(dateSelect) {
                        dateSelect.innerHTML = '<option value="">-- Choisissez une date disponible --</option>';
                        data.days.forEach(day => {
                            // day.date_iso = "2024-02-12"
                            // day.date_pretty = "Lundi 12 Février"
                            let opt = document.createElement('option');
                            opt.value = day.date_iso;
                            opt.text = `${day.date_pretty} (${day.slots.length} créneaux)`;
                            dateSelect.add(opt);
                        });
                    }
                } else {
                    if(dateSelect) dateSelect.innerHTML = '<option value="">Aucune disponibilité trouvée</option>';
                    alert("Aucune date disponible prochainement pour votre zone.");
                }
            })
            .catch(err => {
                console.error(err);
                if(loader) loader.style.display = 'none';
                if(dateSelect) dateSelect.innerHTML = '<option value="">Erreur technique</option>';
            });
    }

    // --- 5. INTERACTION : CHOIX DE LA DATE ---
    const dateSelect = document.getElementById('date_select');
    if (dateSelect) {
        dateSelect.addEventListener('change', function() {
            const selectedDateIso = this.value;
            const timeSelect = document.getElementById('time_slots');
            const confirmBtn = document.getElementById('confirm-btn');

            // Reset du champ Heure
            if(timeSelect) {
                timeSelect.innerHTML = '<option value="">-- Choisissez une heure --</option>';
                timeSelect.disabled = true;
            }
            if(confirmBtn) confirmBtn.disabled = true;

            if (!selectedDateIso) return;

            // On retrouve les heures correspondant à la date choisie dans nos données
            const dayData = availableDaysData.find(d => d.date_iso === selectedDateIso);

            if (dayData && dayData.slots.length > 0) {
                if(timeSelect) {
                    timeSelect.disabled = false;
                    dayData.slots.forEach(slot => {
                        let opt = document.createElement('option');
                        opt.value = slot;
                        opt.text = slot;
                        timeSelect.add(opt);
                    });
                }

                // Mettre à jour le champ caché Date pour le formulaire PHP
                const finalDateInput = document.getElementById('final_date');
                if(finalDateInput) finalDateInput.value = selectedDateIso;
            }
        });
    }

    // --- 6. INTERACTION : CHOIX DE L'HEURE ---
    const timeSelect = document.getElementById('time_slots');
    if (timeSelect) {
        timeSelect.addEventListener('change', function() {
            const selectedTime = this.value;
            const confirmBtn = document.getElementById('confirm-btn');

            if (selectedTime) {
                // Mettre à jour le champ caché Heure
                const finalTimeInput = document.getElementById('final_time');
                if(finalTimeInput) finalTimeInput.value = selectedTime;

                // Activer le bouton de confirmation
                if(confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.style.opacity = "1";
                    confirmBtn.style.cursor = "pointer";
                }
            } else {
                if(confirmBtn) confirmBtn.disabled = true;
            }
        });
    }
});

// --- 7. CALCUL DES TARIFS & UTILITAIRES ---
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

// --- 8. GESTION DES MODALES ---
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