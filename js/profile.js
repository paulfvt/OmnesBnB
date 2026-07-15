// Script pour gérer l'upload et la prévisualisation de la photo de profil utilisateur
// Les commentaires expliquent chaque partie pour bien comprendre le rôle de chaque bloc

// On attend que le contenu de la page soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    // On récupère le champ d'input pour la photo de profil
    const profileImageInput = document.getElementById('profile_image');
    if (profileImageInput) {
        // Quand l'utilisateur sélectionne une image
        profileImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // On lit le fichier image pour l'afficher en aperçu
                const reader = new FileReader();
                reader.onload = function(e) {
                    // On met à jour la source de l'avatar affiché sur la page
                    const profileAvatar = document.querySelector('.profile-avatar');
                    if (profileAvatar) {
                        profileAvatar.src = e.target.result;
                    }
                };
                // On lit le fichier sous forme d'URL de données
                reader.readAsDataURL(file);
            }
        });
    }
});