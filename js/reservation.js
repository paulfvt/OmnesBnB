// Script pour gérer les interactions de réservation (sélection de dates, calcul du prix, validation, etc.)
// Les commentaires expliquent chaque partie pour bien comprendre le rôle de chaque bloc

document.addEventListener('DOMContentLoaded', function() {
    // Récupération du formulaire de réservation
    const reservationForm = document.getElementById('reservation-form');

    // Vérifie si le formulaire existe avant d'ajouter des écouteurs d'événements
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            let isValid = true;

            // Récupération des champs de date d'arrivée et de départ
            const checkinDate = document.getElementById('checkin-date');
            const checkoutDate = document.getElementById('checkout-date');

            // Validation des dates sélectionnées
            if (checkinDate && checkoutDate) {
                const checkin = new Date(checkinDate.value);
                const checkout = new Date(checkoutDate.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                // Vérifie si les dates sont valides
                if (isNaN(checkin.getTime()) || isNaN(checkout.getTime())) {
                    isValid = false;
                    alert('Veuillez sélectionner des dates valides.');
                }
                // Vérifie si la date d'arrivée est dans le passé
                else if (checkin < today) {
                    isValid = false;
                    checkinDate.classList.add('is-invalid');
                    alert('La date d\'arrivée ne peut pas être dans le passé.');
                }
                // Vérifie si la date de départ est après la date d'arrivée
                else if (checkout <= checkin) {
                    isValid = false;
                    checkoutDate.classList.add('is-invalid');
                    alert('La date de départ doit être après la date d\'arrivée.');
                }
                // Si les dates sont valides, on retire les classes d'invalidité
                else {
                    checkinDate.classList.remove('is-invalid');
                    checkoutDate.classList.remove('is-invalid');
                }
            }
            // Récupération du champ de nombre de voyageurs
            const guestCount = document.getElementById('guest-count');
            const maxGuests = guestCount ? parseInt(guestCount.dataset.maxGuests) : 0;

            // Validation du nombre de voyageurs
            if (guestCount && (isNaN(guestCount.value) || parseInt(guestCount.value) <= 0)) {
                isValid = false;
                guestCount.classList.add('is-invalid');
                alert('Veuillez sélectionner un nombre de voyageurs valide.');
            }
            // Vérifie si le nombre de voyageurs ne dépasse pas la capacité maximale
            else if (guestCount && parseInt(guestCount.value) > maxGuests) {
                isValid = false;
                guestCount.classList.add('is-invalid');
                alert(`Le nombre maximum de voyageurs pour cette propriété est de ${maxGuests}.`);
            }
            // Si le nombre de voyageurs est valide, on retire la classe d'invalidité
            else if (guestCount) {
                guestCount.classList.remove('is-invalid');
            }

            // Validation du mode de paiement sélectionné
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                isValid = false;
                alert('Veuillez sélectionner un mode de paiement.');
            }

            // Si des erreurs de validation sont présentes, on empêche l'envoi du formulaire
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // Fonction pour mettre à jour le prix total en fonction des dates et du tarif nocturne
    const updateTotalPrice = function() {
        const checkinDate = document.getElementById('checkin-date');
        const checkoutDate = document.getElementById('checkout-date');
        const nightlyRate = document.getElementById('nightly-rate');
        const totalPriceElement = document.getElementById('total-price');

        // Vérifie si les éléments nécessaires sont présents dans le DOM
        if (checkinDate && checkoutDate && nightlyRate && totalPriceElement) {
            const checkin = new Date(checkinDate.value);
            const checkout = new Date(checkoutDate.value);
            const pricePerNight = parseFloat(nightlyRate.dataset.price || 0);

            // Calcule le prix total si les dates sont valides
            if (!isNaN(checkin.getTime()) && !isNaN(checkout.getTime()) && checkout > checkin) {
                const timeDiff = checkout.getTime() - checkin.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));

                const totalPrice = nights * pricePerNight;

                // Mise à jour du texte et de la valeur du prix total
                totalPriceElement.textContent = totalPrice.toFixed(2) + ' €';

                const totalPriceInput = document.getElementById('total-price-input');
                if (totalPriceInput) {
                    totalPriceInput.value = totalPrice.toFixed(2);
                }
            }
        }
    };

    // Récupération des éléments de date d'arrivée et de départ
    const checkinDate = document.getElementById('checkin-date');
    const checkoutDate = document.getElementById('checkout-date');
    // Ajout d'un écouteur d'événement pour mettre à jour le prix total lors du changement de date d'arrivée
    if (checkinDate) {
        checkinDate.addEventListener('change', updateTotalPrice);
    }

    // Ajout d'un écouteur d'événement pour mettre à jour le prix total lors du changement de date de départ
    if (checkoutDate) {
        checkoutDate.addEventListener('change', updateTotalPrice);
    }
    // Appel initial pour définir le prix total au chargement de la page
    updateTotalPrice();
});

