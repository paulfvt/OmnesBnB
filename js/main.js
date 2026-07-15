// Ce script gère plusieurs fonctionnalités de l'interface utilisateur du site OmnesBnB
// Les commentaires expliquent chaque partie pour bien comprendre le rôle de chaque bloc

document.addEventListener('DOMContentLoaded', function() {
    // --- Menu mobile ---
    // Permet d'ouvrir/fermer le menu sur mobile en cliquant sur l'icône hamburger
    const mobileMenuToggle = document.querySelector('.navbar-toggler');
    if(mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            // On ajoute ou retire la classe 'menu-open' au body pour afficher/cacher le menu
            document.body.classList.toggle('menu-open');
        });
    }

    // --- Validation des dates de réservation ---
    // On récupère les champs de date de début et de fin
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if(startDateInput && endDateInput) {
        // On empêche de choisir une date de début dans le passé
        const today = new Date();
        const todayFormatted = today.toISOString().split('T')[0];
        startDateInput.min = todayFormatted;


        // Quand la date de début change, on ajuste la date minimale de fin
        startDateInput.addEventListener('change', function() {
            if(startDateInput.value) {
                endDateInput.min = startDateInput.value;
                // Si la date de fin est avant la date de début, on la corrige
                if(endDateInput.value && endDateInput.value < startDateInput.value) {
                    endDateInput.value = startDateInput.value;
                }
            }
        });
    }

    // --- Prévisualisation d'image pour une propriété ---
    // Permet d'afficher un aperçu de l'image sélectionnée avant l'envoi du formulaire
    const propertyImageInput = document.getElementById('property_image');
    const imagePreview = document.getElementById('image_preview');

    if(propertyImageInput && imagePreview) {
        propertyImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if(file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    // On affiche l'image dans la page
                    imagePreview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" alt="Property preview">`;
                }

                reader.readAsDataURL(file);
            }
        });
    }

    // --- Prévisualisation de la photo de profil ---
    // Même principe que pour la propriété, mais pour la photo de profil utilisateur
    const profileImageInput = document.getElementById('profile_image');
    const profilePreview = document.getElementById('profile_preview');

    if(profileImageInput && profilePreview) {
        profileImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if(file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    profilePreview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded-circle profile-avatar" alt="Profile preview">`;
                }

                reader.readAsDataURL(file);
            }
        });
    }

    // --- Indicateur de force du mot de passe ---
    // (à compléter pour afficher la force à l'utilisateur)
    const passwordInput = document.getElementById('password');

    if(passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            // On vérifie différents critères pour estimer la sécurité du mot de passe
            if(password.length >= 8) strength += 1;
            if(password.match(/[a-z]+/)) strength += 1;
            if(password.match(/[A-Z]+/)) strength += 1;
            if(password.match(/[0-9]+/)) strength += 1;
            if(password.match(/[^a-zA-Z0-9]+/)) strength += 1;

            // Ici, on pourrait afficher la force à l'utilisateur
        });
    }

    // --- Calcul du prix total en fonction du nombre de nuits ---
    const pricePerNightInput = document.getElementById('price_per_night');
    const numberOfNightsInput = document.getElementById('number_of_nights');
    const totalPriceDisplay = document.getElementById('total_price');

    function calculateTotalPrice() {
        if(pricePerNightInput && numberOfNightsInput && totalPriceDisplay) {
            const pricePerNight = parseFloat(pricePerNightInput.value) || 0;
            const numberOfNights = parseInt(numberOfNightsInput.value) || 0;
            const totalPrice = pricePerNight * numberOfNights;

            totalPriceDisplay.textContent = totalPrice.toFixed(2) + ' €';
        }
    }

    if(pricePerNightInput && numberOfNightsInput) {
        pricePerNightInput.addEventListener('input', calculateTotalPrice);
        numberOfNightsInput.addEventListener('input', calculateTotalPrice);
    }
});
