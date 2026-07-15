// Script pour gérer la galerie d'images d'une propriété (affichage, navigation, etc.)
// Les commentaires expliquent chaque partie pour bien comprendre le rôle de chaque bloc

document.addEventListener('DOMContentLoaded', function() {

    initImageCarousel();
});

function initImageCarousel() {
    // Récupération des éléments nécessaires à la navigation dans le carousel
    const carouselSlides = document.querySelectorAll('.carousel-slide');
    const prevBtn = document.querySelector('.carousel-nav.prev');
    const nextBtn = document.querySelector('.carousel-nav.next');
    const indicators = document.querySelectorAll('.indicator');
    const thumbnails = document.querySelectorAll('.thumbnail');

    // Si aucun slide n'est présent, on sort de la fonction
    if (carouselSlides.length === 0) return;

    let currentIndex = 0;
    const maxIndex = carouselSlides.length - 1;

    // Fonction pour afficher le slide correspondant à l'index passé en paramètre
    function showSlide(index) {
        // Masquer tous les slides et n'afficher que celui sélectionné
        carouselSlides.forEach(slide => slide.classList.remove('active'));
        carouselSlides[index].classList.add('active');

        // Mise à jour des indicateurs de navigation
        indicators.forEach(indicator => indicator.classList.remove('active'));
        if (indicators[index]) {
            indicators[index].classList.add('active');
        }

        // Mise à jour des vignettes (thumbnails) et défilement automatique de la vignette active au centre
        thumbnails.forEach(thumb => thumb.classList.remove('active'));
        if (thumbnails[index]) {
            thumbnails[index].classList.add('active');
            const thumbContainer = document.querySelector('.thumbnail-container');
            if (thumbContainer) {
                const thumbPosition = thumbnails[index].offsetLeft;
                thumbContainer.scrollLeft = thumbPosition - (thumbContainer.clientWidth / 2) + (thumbnails[index].clientWidth / 2);
            }
        }

        currentIndex = index;
    }

    // Événement pour le bouton précédent
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            let newIndex = currentIndex - 1;
            if (newIndex < 0) newIndex = maxIndex;
            showSlide(newIndex);
        });
    }

    // Événement pour le bouton suivant
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            let newIndex = currentIndex + 1;
            if (newIndex > maxIndex) newIndex = 0;
            showSlide(newIndex);
        });
    }

    // Événements pour les indicateurs de navigation
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function () {
            showSlide(index);
        });
    });

    // Événements pour les vignettes (thumbnails)
    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', function () {
            showSlide(index);
        });
    });

    // Navigation au clavier (flèches gauche et droite)
    document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') {
            let newIndex = currentIndex - 1;
            if (newIndex < 0) newIndex = maxIndex;
            showSlide(newIndex);
        } else if (e.key === 'ArrowRight') {
            let newIndex = currentIndex + 1;
            if (newIndex > maxIndex) newIndex = 0;
            showSlide(newIndex);
        }
    });

    // Gestion des gestes tactiles pour les appareils mobiles
    let touchStartX = 0;
    let touchEndX = 0;

    const carousel = document.querySelector('.image-carousel');
    if (carousel) {
        carousel.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });

        carousel.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {

            // Détection du swipe à gauche ou à droite
            if (touchEndX < touchStartX - 50) {
                let newIndex = currentIndex + 1;
                if (newIndex > maxIndex) newIndex = 0;
                showSlide(newIndex);
            } else if (touchEndX > touchStartX + 50) {
                let newIndex = currentIndex - 1;
                if (newIndex < 0) newIndex = maxIndex;
                showSlide(newIndex);
            }
        }
    }
}

