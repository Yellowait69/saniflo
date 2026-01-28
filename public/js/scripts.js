
    // Navigation Menu Burger
    const navLinks = document.querySelector('.nav-links');
    document.querySelector('.burger').addEventListener('click', () => navLinks.classList.toggle('active'));
    document.querySelectorAll('.nav-links a').forEach(link => link.addEventListener('click', () => navLinks.classList.remove('active')));

    // --- LOGIQUE WIZARD DEVIS ---
    document.addEventListener('DOMContentLoaded', function() {
    const steps = document.querySelectorAll('.step');
    const indicators = document.querySelectorAll('.step-indicator');
    let currentStep = 0;

    function showStep(n) {
    steps.forEach((step, index) => {
    step.classList.remove('active-step');
    if(index === n) step.classList.add('active-step');
});
    updateIndicators(n);
}

    function updateIndicators(n) {
    indicators.forEach((ind, index) => {
    ind.classList.remove('active', 'completed');
    if(index === n) ind.classList.add('active');
    if(index < n) {
    ind.classList.add('completed');
    ind.innerHTML = '<i class="fas fa-check"></i>';
} else {
    ind.innerHTML = index + 1;
}
});
}

    document.querySelectorAll('.next-btn').forEach(button => {
    button.addEventListener('click', () => {
    // Validation simple avant d'avancer
    const currentInputs = steps[currentStep].querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    currentInputs.forEach(input => {
    if(!input.checkValidity()) {
    input.reportValidity();
    isValid = false;
}
});

    if(isValid) {
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
});
    // --- GESTION DES MODALES (RGPD & MENTIONS LÉGALES) ---
    function openModal(modalId) {
    document.getElementById(modalId).style.display = "block";
    document.body.style.overflow = "hidden"; // Empêche le scroll derrière
}

    function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
    document.body.style.overflow = "auto"; // Réactive le scroll
}

    // Fermer la modale si on clique en dehors du contenu
    window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
    event.target.style.display = "none";
    document.body.style.overflow = "auto";
}
}

    // Fermer avec la touche Echap
    document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
    document.querySelectorAll('.modal').forEach(modal => {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
});
}
});
