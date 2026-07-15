// Script pour gérer la recherche de logements (filtres, affichage dynamique, etc.)
// Les commentaires expliquent chaque partie pour bien comprendre le rôle de chaque bloc

document.addEventListener('DOMContentLoaded', function() {
    // Gestion des filtres de recherche
    const filterForm = document.getElementById('search-filters');
    const priceRange = document.getElementById('price-range');
    const priceValue = document.getElementById('price-value');

    // Gestion de l'affichage de la plage de prix
    if (priceRange && priceValue) {
        priceRange.addEventListener('input', function() {
            priceValue.textContent = priceRange.value + '€';
        });
    }

    // Soumission du formulaire de filtre
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            // Ici, vous pouvez gérer la soumission du formulaire si nécessaire
        });
    }

    // Gestion des boutons de favoris
    const favoriteButtons = document.querySelectorAll('.favorite-button');

    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const propertyId = this.dataset.propertyId;
            const isFavorite = this.classList.contains('favorited');

            // Changement de l'état du bouton favori
            this.classList.toggle('favorited');

            if (isFavorite) {
                this.innerHTML = '<i class="far fa-heart"></i>';
            } else {
                this.innerHTML = '<i class="fas fa-heart"></i>';
            }

            // Envoi de la requête pour ajouter/enlever des favoris
            fetch('omnesbnb-equipe-2h/includes/favorite_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `property_id=${propertyId}&action=${isFavorite ? 'remove' : 'add'}`
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // En cas d'erreur, on remet l'état précédent du bouton
                        button.classList.toggle('favorited');
                        button.innerHTML = isFavorite ?
                            '<i class="fas fa-heart"></i>' :
                            '<i class="far fa-heart"></i>';

                        // Redirection si nécessaire
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert visuel en cas d'erreur
                    button.classList.toggle('favorited');
                    button.innerHTML = isFavorite ?
                        '<i class="fas fa-heart"></i>' :
                        '<i class="far fa-heart"></i>';
                });
        });
    });

    // Gestion de l'affichage des filtres supplémentaires
    const filterToggle = document.getElementById('filter-toggle');
    const filtersContainer = document.getElementById('filters-container');

    if (filterToggle && filtersContainer) {
        filterToggle.addEventListener('click', function() {
            filtersContainer.classList.toggle('d-none');
            filtersContainer.classList.toggle('d-block');
        });
    }
});
