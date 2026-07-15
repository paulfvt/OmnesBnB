// Script pour gérer les interactions sur la page de gestion de mes locations (propriétaire)
// Les commentaires expliquent chaque partie pour bien comprendre le rôle de chaque bloc

document.addEventListener('DOMContentLoaded', function() {
    // Sélectionne tous les éléments ayant la classe 'dashboard-card' et 'dashboard-detail-card'
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    const dashboardDetails = document.getElementById('dashboard-details');
    const detailCards = document.querySelectorAll('.dashboard-detail-card');
    const reservationsDetails = document.getElementById('reservations-details');

    // Affiche la section des détails du tableau de bord et cache les autres sections de détails
    if (dashboardDetails && reservationsDetails) {
        dashboardDetails.style.display = 'block';
    }
    if (detailCards) {
        detailCards.forEach(card => {
            if (card.id !== 'reservations-details') { // Exclure reservations-details
                card.style.display = 'none';
            }
        });
    }

    // Ajoute des écouteurs d'événements à chaque carte du tableau de bord
    if (dashboardCards && dashboardDetails) {
        dashboardCards.forEach(card => {
            card.addEventListener('click', function() {
                const cardType = this.getAttribute('data-card');
                const detailCard = document.getElementById(`${cardType}-details`);

                dashboardDetails.style.display = 'block';

                // Cache tous les détails sauf ceux de la carte cliquée
                detailCards.forEach(card => {
                    if (card.id !== 'reservations-details') {
                        card.style.display = 'none';
                    }
                });

                // Affiche la carte de détail correspondante
                if (detailCard) {
                    detailCard.style.display = 'block';
                    detailCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    // Gestion des boutons d'édition des propriétés
    const editButtons = document.querySelectorAll('.edit-property');
    if (editButtons.length > 0) {
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const propertyId = this.getAttribute('data-property-id');
                window.location.href = `publish.php?edit=${propertyId}`;
            });
        });
    }

    // Initialisation des modales Bootstrap pour l'édition, la notification et la suppression
    const togglePropertyModalElement = document.getElementById('togglePropertyModal');
    const notificationModalElement = document.getElementById('notificationModal');
    const deletePropertyModalElement = document.getElementById('deletePropertyModal');
    let togglePropertyModal, notificationModal, deletePropertyModal;

    if (typeof bootstrap !== 'undefined') {
        if (togglePropertyModalElement) {
            togglePropertyModal = new bootstrap.Modal(togglePropertyModalElement);
        }

        if (notificationModalElement) {
            notificationModal = new bootstrap.Modal(notificationModalElement);
        }

        if (deletePropertyModalElement) {
            deletePropertyModal = new bootstrap.Modal(deletePropertyModalElement);
        }
    } else {
        console.error('Bootstrap n\'est pas chargé correctement');
    }

    // Gestion des boutons d'activation/désactivation des propriétés
    const toggleButtons = document.querySelectorAll('.toggle-property');
    let currentButton = null;
    let currentPropertyId = null;
    let currentIsActive = null;
    let currentNewStatus = null;

    if (toggleButtons.length > 0) {
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                currentButton = this;
                currentPropertyId = this.getAttribute('data-property-id');
                currentIsActive = this.querySelector('i').classList.contains('fa-toggle-off');
                currentNewStatus = currentIsActive ? 0 : 1; // 0 = inactive, 1 = active
                const statusText = currentIsActive ? 'désactiver' : 'activer';

                if (togglePropertyModal) {
                    // Mettre à jour le texte de la modale de confirmation
                    const modalText = document.getElementById('togglePropertyModalText');
                    if (modalText) {
                        modalText.textContent = `Êtes-vous sûr de vouloir ${statusText} cette propriété ?`;
                    }
                    // Afficher la modale de confirmation
                    togglePropertyModal.show();
                } else {
                    // Fallback si la modale n'est pas disponible
                    if (confirm(`Êtes-vous sûr de vouloir ${statusText} cette propriété ?`)) {
                        togglePropertyAction();
                    }
                }
            });
        });
    }
    // Gérer la confirmation de l'action depuis la modale
    const confirmToggleProperty = document.getElementById('confirmToggleProperty');
    if (confirmToggleProperty) {
        confirmToggleProperty.addEventListener('click', function() {
            togglePropertyAction();
        });
    }
    // Fonction pour traiter l'action de toggle
    function togglePropertyAction() {
        if (!currentButton || !currentPropertyId) return;

        // Cacher la modale de confirmation si elle existe
        if (togglePropertyModal) {
            togglePropertyModal.hide();
        }
        fetch('../includes/property_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `property_id=${currentPropertyId}&action=toggle_status&status=${currentNewStatus}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface utilisateur
                    if (currentIsActive) {
                        // Changer en inactif
                        currentButton.innerHTML = '<i class="fas fa-toggle-on me-1"></i>Activer';

                        // Mettre à jour les badges de statut
                        const statusBadges = document.querySelectorAll(`.rental-status`);
                        statusBadges.forEach(badge => {
                            if (badge.closest('.rental-card') &&
                                badge.closest('.rental-card').querySelector(`.toggle-property[data-property-id="${currentPropertyId}"]`)) {
                                badge.textContent = 'Inactif';
                                badge.classList.remove('status-active');
                                badge.classList.add('status-expired');
                            }
                        });
                    } else {
                        // Changer en actif
                        currentButton.innerHTML = '<i class="fas fa-toggle-off me-1"></i>Désactiver';

                        // Mettre à jour les badges de statut
                        const statusBadges = document.querySelectorAll(`.rental-status`);
                        statusBadges.forEach(badge => {
                            if (badge.closest('.rental-card') &&
                                badge.closest('.rental-card').querySelector(`.toggle-property[data-property-id="${currentPropertyId}"]`)) {
                                badge.textContent = 'Actif';
                                badge.classList.remove('status-expired');
                                badge.classList.add('status-active');
                            }
                        });
                    }

                    if (notificationModal) {
                        // Afficher le message de succès dans la modale de notification
                        const iconElement = document.getElementById('notificationIcon');
                        const messageElement = document.getElementById('notificationModalText');
                        const titleElement = document.getElementById('notificationModalLabel');

                        if (iconElement && messageElement && titleElement) {
                            // Définir le titre, l'icône et le message appropriés
                            titleElement.textContent = 'Succès';
                            iconElement.innerHTML = `<i class="fas fa-check-circle fa-4x text-success"></i>`;
                            messageElement.textContent = `Propriété ${currentIsActive ? 'désactivée' : 'activée'} avec succès.`;

                            // Afficher la modale de notification
                            notificationModal.show();
                        } else {
                            // Fallback si les éléments ne sont pas trouvés
                            alert(`Propriété ${currentIsActive ? 'désactivée' : 'activée'} avec succès.`);
                        }
                    } else {
                        // Fallback si la modale n'est pas disponible
                        alert(`Propriété ${currentIsActive ? 'désactivée' : 'activée'} avec succès.`);
                    }
                } else {
                    if (notificationModal) {
                        // Afficher le message d'erreur dans la modale de notification
                        const iconElement = document.getElementById('notificationIcon');
                        const messageElement = document.getElementById('notificationModalText');
                        const titleElement = document.getElementById('notificationModalLabel');

                        if (iconElement && messageElement && titleElement) {
                            // Définir le titre, l'icône et le message appropriés
                            titleElement.textContent = 'Erreur';
                            iconElement.innerHTML = `<i class="fas fa-exclamation-circle fa-4x text-danger"></i>`;
                            messageElement.textContent = data.message || 'Une erreur est survenue lors de la mise à jour du statut.';

                            // Afficher la modale de notification
                            notificationModal.show();
                        } else {
                            // Fallback si les éléments ne sont pas trouvés
                            alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                        }
                    } else {
                        // Fallback si la modale n'est pas disponible
                        alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);

                if (notificationModal) {
                    // En cas d'erreur de réseau ou autre
                    const iconElement = document.getElementById('notificationIcon');
                    const messageElement = document.getElementById('notificationModalText');
                    const titleElement = document.getElementById('notificationModalLabel');

                    if (iconElement && messageElement && titleElement) {
                        titleElement.textContent = 'Erreur';
                        iconElement.innerHTML = `<i class="fas fa-exclamation-circle fa-4x text-danger"></i>`;
                        messageElement.textContent = 'Une erreur de réseau est survenue. Veuillez réessayer plus tard.';

                        notificationModal.show();
                    } else {
                        alert('Une erreur de réseau est survenue. Veuillez réessayer plus tard.');
                    }
                } else {
                    alert('Une erreur de réseau est survenue. Veuillez réessayer plus tard.');
                }
            });
    }

    // Gestion des boutons de suppression de propriété
    const deleteButtons = document.querySelectorAll('.delete-property');
    let propertyToDelete = null;
    let propertyTitleToDelete = '';

    if (deleteButtons.length > 0) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                propertyToDelete = this.getAttribute('data-property-id');
                propertyTitleToDelete = this.getAttribute('data-property-title') || 'cette propriété';

                if (deletePropertyModal) {
                    const propertyTitleElement = document.getElementById('propertyTitleToDelete');
                    if (propertyTitleElement) {
                        propertyTitleElement.textContent = propertyTitleToDelete;
                    }
                    deletePropertyModal.show();
                } else {
                    if (confirm(`Êtes-vous sûr de vouloir supprimer "${propertyTitleToDelete}" ? Cette action est irréversible.`)) {
                        deletePropertyAction();
                    }
                }
            });
        });
    }
    // Gérer la confirmation de suppression depuis la modale
    const confirmDeleteProperty = document.getElementById('confirmDeleteProperty');
    if (confirmDeleteProperty) {
        confirmDeleteProperty.addEventListener('click', function() {
            deletePropertyAction();
        });
    }
    // Fonction pour traiter l'action de suppression
    function deletePropertyAction() {
        if (!propertyToDelete) return;
        // Cacher la modale de confirmation si elle existe
        if (deletePropertyModal) {
            deletePropertyModal.hide();
        }
        // Envoyer la requête AJAX pour supprimer la propriété
        fetch('../includes/property_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `property_id=${propertyToDelete}&action=delete`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (notificationModal) {
                        // Afficher le message de succès dans la modale de notification
                        const iconElement = document.getElementById('notificationIcon');
                        const messageElement = document.getElementById('notificationModalText');
                        const titleElement = document.getElementById('notificationModalLabel');
                        if (iconElement && messageElement && titleElement) {
                            // Définir le titre, l'icône et le message appropriés
                            titleElement.textContent = 'Succès';
                            iconElement.innerHTML = `<i class="fas fa-check-circle fa-4x text-success"></i>`;
                            messageElement.textContent = `Propriété "${propertyTitleToDelete}" supprimée avec succès.`;
                            // Afficher la modale de notification avec un délai pour la redirection
                            notificationModal.show();
                            // Rediriger après un court délai
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            alert(`Propriété "${propertyTitleToDelete}" supprimée avec succès.`);
                            window.location.reload();
                        }
                    } else {
                        // Fallback si la modale n'est pas disponible
                        alert(`Propriété "${propertyTitleToDelete}" supprimée avec succès.`);
                        window.location.reload();
                    }
                } else {
                    if (notificationModal) {
                        // Afficher le message d'erreur dans la modale de notification
                        const iconElement = document.getElementById('notificationIcon');
                        const messageElement = document.getElementById('notificationModalText');
                        const titleElement = document.getElementById('notificationModalLabel');
                        if (iconElement && messageElement && titleElement) {
                            // Définir le titre, l'icône et le message appropriés
                            titleElement.textContent = 'Erreur';
                            iconElement.innerHTML = `<i class="fas fa-exclamation-circle fa-4x text-danger"></i>`;
                            messageElement.textContent = data.message || 'Une erreur est survenue lors de la suppression de la propriété.';
                            // Afficher la modale de notification
                            notificationModal.show();
                        } else {
                            alert(data.message || 'Une erreur est survenue lors de la suppression de la propriété.');
                        }
                    } else {
                        alert(data.message || 'Une erreur est survenue lors de la suppression de la propriété.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (notificationModal) {
                    // En cas d'erreur de réseau ou autre
                    const iconElement = document.getElementById('notificationIcon');
                    const messageElement = document.getElementById('notificationModalText');
                    const titleElement = document.getElementById('notificationModalLabel');
                    if (iconElement && messageElement && titleElement) {
                        titleElement.textContent = 'Erreur';
                        iconElement.innerHTML = `<i class="fas fa-exclamation-circle fa-4x text-danger"></i>`;
                        messageElement.textContent = 'Une erreur de réseau est survenue. Veuillez réessayer plus tard.';

                        notificationModal.show();
                    } else {
                        alert('Une erreur de réseau est survenue. Veuillez réessayer plus tard.');
                    }
                } else {
                    alert('Une erreur de réseau est survenue. Veuillez réessayer plus tard.');
                }
            });
    }

    // Gestion des boutons de confirmation/annulation des réservations
    const confirmButtons = document.querySelectorAll('.confirm-booking');
    const cancelButtons = document.querySelectorAll('.cancel-booking');

    // Ajouter des écouteurs d'événements pour les boutons de confirmation
    if (confirmButtons.length > 0) {
        confirmButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                if (confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')) {
                    handleBookingAction(bookingId, 'confirm', this);
                }
            });
        });
    }

    // Ajouter des écouteurs d'événements pour les boutons d'annulation
    if (cancelButtons.length > 0) {
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
                    handleBookingAction(bookingId, 'cancel', this);
                }
            });
        });
    }
    // Fonction pour gérer les actions de réservation (confirmation/annulation)
    function handleBookingAction(bookingId, action, buttonElement) {
        fetch('../includes/booking_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `booking_id=${bookingId}&action=${action}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = buttonElement.closest('tr');

                    if (row) {
                        // Mettre à jour le badge de statut
                        const statusCell = row.querySelector('td:nth-child(4)'); // 4e cellule contient le statut
                        if (statusCell) {
                            if (action === 'confirm') {
                                statusCell.innerHTML = '<span class="badge bg-success">Confirmé</span>';
                            } else if (action === 'cancel') {
                                statusCell.innerHTML = '<span class="badge bg-danger">Annulé</span>';
                            }
                        }
                        // Mettre à jour les boutons d'action
                        const actionsCell = row.querySelector('td:last-child');
                        if (actionsCell) {
                            if (action === 'confirm') {
                                actionsCell.innerHTML = `
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-danger cancel-booking" data-booking-id="${bookingId}">
                                        <i class="fas fa-times"></i> Annuler
                                    </button>
                                </div>
                            `;
                                // Réattacher l'écouteur d'événement au nouveau bouton
                                const newCancelButton = actionsCell.querySelector('.cancel-booking');
                                if (newCancelButton) {
                                    newCancelButton.addEventListener('click', function() {
                                        if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
                                            handleBookingAction(bookingId, 'cancel', this);
                                        }
                                    });
                                }
                            } else if (action === 'cancel') {
                                actionsCell.innerHTML = '<span class="text-muted">Aucune action disponible</span>';
                            }
                        }
                    }
                    // Afficher un message de succès
                    if (notificationModal) {
                        const iconElement = document.getElementById('notificationIcon');
                        const messageElement = document.getElementById('notificationModalText');
                        const titleElement = document.getElementById('notificationModalLabel');

                        if (iconElement && messageElement && titleElement) {
                            titleElement.textContent = 'Succès';
                            iconElement.innerHTML = `<i class="fas fa-check-circle fa-4x text-success"></i>`;
                            messageElement.textContent = data.message || `Réservation ${action === 'confirm' ? 'confirmée' : 'annulée'} avec succès.`;

                            notificationModal.show();
                        } else {
                            alert(data.message || `Réservation ${action === 'confirm' ? 'confirmée' : 'annulée'} avec succès.`);
                        }
                    } else {
                        alert(data.message || `Réservation ${action === 'confirm' ? 'confirmée' : 'annulée'} avec succès.`);
                    }
                } else {
                    // Afficher un message d'erreur
                    if (notificationModal) {
                        const iconElement = document.getElementById('notificationIcon');
                        const messageElement = document.getElementById('notificationModalText');
                        const titleElement = document.getElementById('notificationModalLabel');

                        if (iconElement && messageElement && titleElement) {
                            titleElement.textContent = 'Erreur';
                            iconElement.innerHTML = `<i class="fas fa-exclamation-circle fa-4x text-danger"></i>`;
                            messageElement.textContent = data.message || `Une erreur est survenue lors de l'action sur la réservation.`;

                            notificationModal.show();
                        } else {
                            alert(data.message || `Une erreur est survenue lors de l'action sur la réservation.`);
                        }
                    } else {
                        alert(data.message || `Une erreur est survenue lors de l'action sur la réservation.`);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur de réseau est survenue. Veuillez réessayer plus tard.');
            });
    }
});