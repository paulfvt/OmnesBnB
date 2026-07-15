<?php
// --- Démarrage de la session utilisateur ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Vérification de l'authentification ---
if (!isset($_SESSION["user_id"])) {
    $_SESSION["message"] = "Vous devez vous connecter pour accéder à vos locations.";
    $_SESSION["message_type"] = "warning";
    header("location: login.php");
    exit;
}

// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";

// --- Inclusion du header (barre de navigation, etc.) ---
include "../includes/header.php";

// --- Ajout du CSS spécifique à la page de locations et dashboard ---
echo '<link rel="stylesheet" href="../css/my-rentals.css">';
echo '<link rel="stylesheet" href="../css/dashboard.css">';

// Initialisation des variables
$my_properties = [];
$user_id = $_SESSION["user_id"];

// --- Requête pour récupérer les propriétés de l'utilisateur ---
$properties_sql = "SELECT p.*, 
                    (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) as booking_count,
                    (SELECT SUM(total_price) FROM bookings WHERE property_id = p.id AND status = 'confirmed') as total_earned
                    FROM properties p 
                    WHERE p.owner_id = ?";

// Préparation et exécution de la requête
if ($stmt = mysqli_prepare($conn, $properties_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        // Parcours des propriétés récupérées
        while ($property = mysqli_fetch_assoc($result)) {
            // Récupérer les réservations pour cette propriété
            $property['bookings'] = [];
            $bookings_sql = "SELECT b.*, u.first_name, u.last_name
                            FROM bookings b
                            JOIN users u ON b.user_id = u.id
                            WHERE b.property_id = ? AND b.status != 'cancelled'";

            // Préparation et exécution de la requête pour les réservations
            if ($bookings_stmt = mysqli_prepare($conn, $bookings_sql)) {
                mysqli_stmt_bind_param($bookings_stmt, "i", $property['id']);

                if (mysqli_stmt_execute($bookings_stmt)) {
                    $bookings_result = mysqli_stmt_get_result($bookings_stmt);

                    // Parcours des réservations et ajout des informations sur le locataire
                    while ($booking = mysqli_fetch_assoc($bookings_result)) {
                        $booking['tenant_name'] = $booking['first_name'] . ' ' . $booking['last_name'];
                        $booking['tenant_id'] = $booking['user_id'];
                        $property['bookings'][] = $booking;
                    }
                }
                mysqli_stmt_close($bookings_stmt);
            }
            $my_properties[] = $property;
        }
    }
    mysqli_stmt_close($stmt);
}

// Initialisation des tableaux pour les propriétés louées et l'historique
$rented_properties = [];
$past_rentals = [];

// --- Requête pour récupérer les propriétés louées par l'utilisateur ---
$rented_sql = "SELECT b.*, p.title, p.location, p.price, p.main_image,
                u.first_name as owner_first_name, u.last_name as owner_last_name, u.id as owner_id
                FROM bookings b
                JOIN properties p ON b.property_id = p.id
                JOIN users u ON p.owner_id = u.id
                WHERE b.user_id = ? AND b.status = 'confirmed' AND b.end_date >= CURDATE()
                ORDER BY b.start_date ASC";

// Préparation et exécution de la requête pour les propriétés louées
if ($rented_stmt = mysqli_prepare($conn, $rented_sql)) {
    mysqli_stmt_bind_param($rented_stmt, "i", $user_id);

    if (mysqli_stmt_execute($rented_stmt)) {
        $rented_result = mysqli_stmt_get_result($rented_stmt);
        // Parcours des réservations louées
        while ($booking = mysqli_fetch_assoc($rented_result)) {
            $rented_property = [
                'id' => $booking['property_id'],
                'title' => $booking['title'],
                'location' => $booking['location'],
                'owner_name' => $booking['owner_first_name'] . ' ' . $booking['owner_last_name'],
                'owner_id' => $booking['owner_id'],
                'price' => $booking['price'],
                'image' => $booking['main_image'],
                'start_date' => $booking['start_date'],
                'end_date' => $booking['end_date'],
                'total_price' => $booking['total_price'],
                'status' => $booking['status']
            ];
            $rented_properties[] = $rented_property;
        }
    }
    mysqli_stmt_close($rented_stmt);
}

// --- Requête pour récupérer les locations passées où l'utilisateur était locataire ---
$past_tenant_sql = "SELECT b.*, p.title, p.location, p.price, p.main_image,
                    u.first_name as owner_first_name, u.last_name as owner_last_name, u.id as owner_id
                    FROM bookings b
                    JOIN properties p ON b.property_id = p.id
                    JOIN users u ON p.owner_id = u.id
                    WHERE b.user_id = ? AND b.end_date < CURDATE()
                    ORDER BY b.end_date DESC
                    LIMIT 10";

// Préparation et exécution de la requête pour l'historique en tant que locataire
if ($past_tenant_stmt = mysqli_prepare($conn, $past_tenant_sql)) {
    mysqli_stmt_bind_param($past_tenant_stmt, "i", $user_id);

    if (mysqli_stmt_execute($past_tenant_stmt)) {
        $past_tenant_result = mysqli_stmt_get_result($past_tenant_stmt);
        // Parcours des réservations passées en tant que locataire
        while ($booking = mysqli_fetch_assoc($past_tenant_result)) {
            $past_rental = [
                'id' => $booking['property_id'],
                'title' => $booking['title'],
                'location' => $booking['location'],
                'owner_name' => $booking['owner_first_name'] . ' ' . $booking['owner_last_name'],
                'owner_id' => $booking['owner_id'],
                'price' => $booking['price'],
                'image' => $booking['main_image'],
                'start_date' => $booking['start_date'],
                'end_date' => $booking['end_date'],
                'total_price' => $booking['total_price'],
                'status' => 'completed',
                'as_owner' => false
            ];
            $past_rentals[] = $past_rental;
        }
    }
    mysqli_stmt_close($past_tenant_stmt);
}

// --- Requête pour récupérer les locations passées où l'utilisateur était propriétaire ---
$past_owner_sql = "SELECT b.*, p.title, p.location, p.price, p.main_image,
                  u.first_name as tenant_first_name, u.last_name as tenant_last_name, u.id as tenant_id
                  FROM bookings b
                  JOIN properties p ON b.property_id = p.id
                  JOIN users u ON b.user_id = u.id
                  WHERE p.owner_id = ? AND b.end_date < CURDATE()
                  ORDER BY b.end_date DESC
                  LIMIT 10";

// Préparation et exécution de la requête pour l'historique en tant que propriétaire
if ($past_owner_stmt = mysqli_prepare($conn, $past_owner_sql)) {
    mysqli_stmt_bind_param($past_owner_stmt, "i", $user_id);

    if (mysqli_stmt_execute($past_owner_stmt)) {
        $past_owner_result = mysqli_stmt_get_result($past_owner_stmt);
        // Parcours des réservations passées en tant que propriétaire
        while ($booking = mysqli_fetch_assoc($past_owner_result)) {
            $past_rental = [
                'id' => $booking['property_id'],
                'title' => $booking['title'],
                'location' => $booking['location'],
                'tenant_name' => $booking['tenant_first_name'] . ' ' . $booking['tenant_last_name'],
                'tenant_id' => $booking['tenant_id'],
                'price' => $booking['price'],
                'image' => $booking['main_image'],
                'start_date' => $booking['start_date'],
                'end_date' => $booking['end_date'],
                'total_price' => $booking['total_price'],
                'status' => 'completed',
                'as_owner' => true
            ];
            $past_rentals[] = $past_rental;
        }
    }
    mysqli_stmt_close($past_owner_stmt);
}

// Calcul des totaux pour le tableau de bord
$total_earned = 0;
$total_spent = 0;
$total_bookings = 0;
$active_listings = 0;

// Parcours des propriétés pour calculer les revenus et le nombre de réservations
foreach ($my_properties as $property) {
    $active_listings++;
    if(isset($property['bookings'])) {
        foreach($property['bookings'] as $booking) {
            $total_earned += $booking['total_price'];
            $total_bookings++;
        }
    }
    foreach ($property['bookings'] as $booking) {
        if ($booking['status'] === 'confirmed' || $booking['status'] === 'completed') {
            $total_earned += $booking['total_price'];
        }
    }
}

// Parcours des propriétés louées pour calculer les dépenses
foreach ($rented_properties as $property) {
    if ($property['status'] === 'confirmed' || $property['status'] === 'completed') {
        $total_spent += $property['total_price'];
    }
}

// Parcours de l'historique des locations pour ajuster les totaux en fonction du rôle (locataire/propriétaire)
foreach ($past_rentals as $rental) {
    if ($rental['status'] === 'completed') {
        if (isset($rental['as_owner']) && $rental['as_owner']) {
            $total_earned += $rental['total_price'];
        } else {
            $total_spent += $rental['total_price'];
        }
    }
}

// Calcul du solde net
$net_balance = $total_earned - $total_spent;
?>

<div class="container py-4">
    <!-- Tableau de bord - Résumé des revenus, dépenses, propriétés et réservations -->
    <div class="row mb-4">
        <h3 class="mb-4">Tableau de bord</h3>
        <!-- Carte des revenus -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 dashboard-card" data-card="revenus">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-25 p-3 mb-3" id="revenus-icon">
                        <i class="fas fa-euro-sign fa-2x text-success"></i>
                    </div>
                    <h5 class="card-title">Revenus</h5>
                    <h3 class="mb-0 text-success"><?php echo number_format($total_earned, 2); ?>€</h3>
                    <p class="text-muted mt-2 mb-0">Total reçu</p>
                </div>
            </div>
        </div>
        <!-- Carte des dépenses -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 dashboard-card" data-card="depenses">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-25 p-3 mb-3" id="depenses-icon">
                        <i class="fas fa-shopping-cart fa-2x text-danger"></i>
                    </div>
                    <h5 class="card-title">Dépenses</h5>
                    <h3 class="mb-0 text-danger"><?php echo number_format($total_spent, 2); ?>€</h3>
                    <p class="text-muted mt-2 mb-0">Total dépensé</p>
                </div>
            </div>
        </div>
        <!-- Carte des propriétés actives -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 dashboard-card" data-card="proprietes">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-25 p-3 mb-3" id="proprietes-icon">
                        <i class="fas fa-home fa-2x text-primary"></i>
                    </div>
                    <h5 class="card-title">Propriétés</h5>
                    <h3 class="mb-0 text-primary"><?php echo $active_listings; ?></h3>
                    <p class="text-muted mt-2 mb-0">Annonces actives</p>
                </div>
            </div>
        </div>
        <!-- Carte des réservations -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100 dashboard-card" data-card="reservations">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-25 p-3 mb-3" id="reservations-icon">
                        <i class="fas fa-calendar-check fa-2x text-info"></i>
                    </div>
                    <h5 class="card-title">Réservations</h5>
                    <h3 class="mb-0 text-info"><?php echo $total_bookings; ?></h3>
                    <p class="text-muted mt-2 mb-0">Total des réservations</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Détails des cartes dashboard -->
    <div id="dashboard-details" class="mb-4" style="display: none;">
        <!-- Réservations details -->
        <div id="reservations-details" class="dashboard-detail-card">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info bg-opacity-10 border-0">
                    <h5 class="mb-0 text-info"><i class="fas fa-calendar-check me-2"></i>Détails des réservations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="mb-3">Statistiques de réservation</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Total réservations
                                    <span class="badge bg-info rounded-pill"><?php echo $total_bookings; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Ce mois-ci
                                    <span class="badge bg-primary rounded-pill"><?php echo ceil($total_bookings * 0.4); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    À venir
                                    <span class="badge bg-success rounded-pill"><?php echo ceil($total_bookings * 0.6); ?></span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-8">
                            <h6 class="mb-3">Réservations récentes</h6>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Dates</th>
                                        <th>Client</th>
                                        <th>Propriété</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $recentBookings = [];
                                    // Récupération des réservations récentes pour affichage dans le tableau de bord
                                    foreach ($my_properties as $property) {
                                        foreach ($property['bookings'] as $booking) {
                                            $booking['property_title'] = $property['title'];
                                            $booking['property_id'] = $property['id'];
                                            $recentBookings[] = $booking;
                                        }
                                    }
                                    // Tri des réservations récentes par date de début
                                    usort($recentBookings, function($a, $b) {
                                        return strtotime($b['start_date']) - strtotime($a['start_date']);
                                    });

                                    $count = 0;
                                    // Affichage des 5 réservations les plus récentes
                                    foreach ($recentBookings as $booking):
                                        if ($count >= 5) break;
                                        ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($booking['tenant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                            <td>
                                                <?php if ($booking['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">En attente</span>
                                                <?php elseif ($booking['status'] == 'confirmed'): ?>
                                                    <span class="badge bg-success">Confirmé</span>
                                                <?php elseif ($booking['status'] == 'cancelled'): ?>
                                                    <span class="badge bg-danger">Annulé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Autre</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($booking['status'] == 'pending'): ?>
                                                        <button type="button" class="btn btn-success confirm-booking" data-booking-id="<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-check"></i> Confirmer
                                                        </button>
                                                        <button type="button" class="btn btn-danger cancel-booking" data-booking-id="<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-times"></i> Annuler
                                                        </button>
                                                    <?php elseif ($booking['status'] == 'confirmed'): ?>
                                                        <button type="button" class="btn btn-danger cancel-booking" data-booking-id="<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-times"></i> Annuler
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                        $count++;
                                    endforeach;
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Balance Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Balance financière</h5>
                            <p class="text-muted">Revenus moins dépenses</p>
                        </div>
                        <div>
                            <h2 class="mb-0 <?php echo ($net_balance >= 0) ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($net_balance, 2); ?>€
                            </h2>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 10px;">
                        <?php if ($net_balance >= 0): ?>
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: <?php echo min(100, ($total_earned / max(1, $total_earned + $total_spent) * 100)); ?>%"
                                 aria-valuenow="<?php echo min(100, ($total_earned / max(1, $total_earned + $total_spent) * 100)); ?>"
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        <?php else: ?>
                            <div class="progress-bar bg-danger" role="progressbar"
                                 style="width: <?php echo min(100, ($total_spent / max(1, $total_earned + $total_spent) * 100)); ?>%"
                                 aria-valuenow="<?php echo min(100, ($total_spent / max(1, $total_earned + $total_spent) * 100)); ?>"
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="my-properties-tab" data-bs-toggle="tab" data-bs-target="#my-properties" type="button" role="tab" aria-controls="my-properties" aria-selected="true">Mes logements</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">Historique</button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="myTabContent">
        <!-- My Properties Tab (as owner) -->
        <div class="tab-pane fade show active" id="my-properties" role="tabpanel" aria-labelledby="my-properties-tab">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Mes logements publiés</h3>
                <a href="publish.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Publier un logement
                </a>
            </div>
            <?php if (empty($my_properties)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Vous n'avez pas encore publié de logement. <a href="publish.php" class="alert-link">Publiez votre premier logement</a>.
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($my_properties as $property): ?>
                        <div class="col mb-4">
                            <div class="rental-card position-relative">
                                <div class="property-image-wrapper">
                                    <?php
                                    $image_path = "";
                                    // Vérification de l'existence de l'image principale de la propriété
                                    if (!empty($property['main_image']) && file_exists("../" . $property['main_image'])) {
                                        $image_path = "../" . $property['main_image'];
                                    } else {
                                        // Utilisez une image par défaut si aucune image n'est disponible
                                        $image_path = "../assets/property_images/default.jpg";
                                    }
                                    ?>
                                    <img src="<?php echo $image_path; ?>" class="property-image" alt="<?php echo htmlspecialchars($property['title']); ?>">

                                    <div class="property-type-badge">
                                        <?php
                                        // Affichage du type de propriété (appartement, maison, etc.)
                                        echo htmlspecialchars($property['property_type']);
                                        ?>
                                    </div>

                                    <span class="rental-status status-<?php echo isset($property['is_published']) && $property['is_published'] ? 'active' : 'expired'; ?>">
                                        <?php echo isset($property['is_published']) && $property['is_published'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </div>

                                <div class="rental-info">
                                    <h5 class="rental-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                    <p class="rental-location">
                                        <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($property['location']); ?>
                                    </p>

                                    <div class="rental-dates">
                                        <div><i class="fas fa-euro-sign me-2"></i><span class="rental-price"><?php echo htmlspecialchars($property['price']); ?>€</span> / nuit</div>
                                        <div><i class="fas fa-calendar-alt me-2"></i>
                                            <?php if (isset($property['available_from']) && isset($property['available_to'])): ?>
                                                Du <?php echo date('d/m/Y', strtotime($property['available_from'])); ?> au <?php echo date('d/m/Y', strtotime($property['available_to'])); ?>
                                            <?php else: ?>
                                                Dates disponibles sur demande
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if (isset($property['bookings']) && count($property['bookings']) > 0): ?>
                                        <div class="mt-3">
                                            <h6><i class="fas fa-key me-2"></i>Réservations (<?php echo count($property['bookings']); ?>)</h6>
                                            <?php foreach ($property['bookings'] as $index => $booking): ?>
                                                <?php if ($index < 2): // Limite à 2 réservations affichées pour ne pas surcharger la carte ?>
                                                    <div class="booking-item">                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <strong><?php echo htmlspecialchars($booking['tenant_name']); ?></strong>
                                                            <?php if ($booking['status'] == 'cancelled'): ?>
                                                                <span class="badge bg-danger">Annulé</span>
                                                            <?php elseif ($booking['status'] == 'confirmed'): ?>
                                                                <span class="badge bg-success">Confirmé</span>
                                                            <?php elseif ($booking['status'] == 'pending'): ?>
                                                                <span class="badge bg-warning">En attente</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><?php echo ucfirst($booking['status']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="mt-1">
                                                            <small>
                                                                <i class="fas fa-calendar-check me-1"></i>
                                                                <?php echo date('d/m/Y', strtotime($booking['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($booking['end_date'])); ?>
                                                            </small>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                                            <small class="text-primary fw-bold"><?php echo htmlspecialchars($booking['total_price']); ?>€</small>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>

                                            <?php if (count($property['bookings']) > 2): ?>
                                                <div class="text-center mt-2">
                                                    <small class="text-muted"><?php echo count($property['bookings']) - 2; ?> autres réservations...</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-3 p-3 text-center bg-light rounded">
                                            <p class="mb-0"><i class="far fa-calendar-alt me-2"></i>Aucune réservation pour l'instant</p>
                                        </div>
                                    <?php endif; ?>                                      <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline-secondary edit-property" data-property-id="<?php echo $property['id']; ?>">
                                            <i class="fas fa-edit me-1"></i>Modifier
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger toggle-property" data-property-id="<?php echo $property['id']; ?>">
                                            <?php if (isset($property['is_published']) && $property['is_published']): ?>
                                                <i class="fas fa-toggle-off me-1"></i>Désactiver
                                            <?php else: ?>
                                                <i class="fas fa-toggle-on me-1"></i>Activer
                                            <?php endif; ?>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-property" data-property-id="<?php echo $property['id']; ?>" data-property-title="<?php echo htmlspecialchars($property['title']); ?>">
                                            <i class="fas fa-trash-alt me-1"></i>Supprimer
                                        </button>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- History Tab -->
        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
            <h3 class="mb-4">Historique des locations</h3>

            <?php if (empty($past_rentals)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Vous n'avez pas encore d'historique de location.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Logement</th>
                            <th>Emplacement</th>
                            <th>Période</th>
                            <th>Montant</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($past_rentals as $rental): ?>
                            <tr>
                                <td>
                                    <a href="property-details.php?id=<?php echo $rental['id']; ?>"><?php echo $rental['title']; ?></a>
                                </td>
                                <td><?php echo $rental['location']; ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($rental['start_date'])); ?> -
                                    <?php echo date('d/m/Y', strtotime($rental['end_date'])); ?>
                                </td>
                                <td><?php echo $rental['total_price']; ?>€</td>
                                <td>
                                    <?php if (isset($rental['as_owner']) && $rental['as_owner']): ?>
                                        <span class="badge bg-primary">Propriétaire</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Locataire</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success">Terminé</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour activer/désactiver une propriété -->
<div class="modal fade" id="togglePropertyModal" tabindex="-1" aria-labelledby="togglePropertyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="togglePropertyModalLabel">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="togglePropertyModalText">Êtes-vous sûr de vouloir modifier le statut de cette propriété ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmToggleProperty">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de notification pour les résultats des actions -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="notificationIcon" class="text-center mb-3">
                </div>
                <p id="notificationModalText" class="text-center">Action effectuée avec succès.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour supprimer une propriété -->
<div class="modal fade" id="deletePropertyModal" tabindex="-1" aria-labelledby="deletePropertyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deletePropertyModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle fa-4x text-danger"></i>
                </div>
                <p id="deletePropertyModalText" class="text-center">Êtes-vous sûr de vouloir supprimer définitivement cette propriété ? Cette action est irréversible et supprimera également toutes les réservations associées.</p>
                <p class="text-center fw-bold" id="propertyTitleToDelete"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProperty">
                    <i class="fas fa-trash-alt me-1"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../js/my-rentals.js"></script>
<?php include "../includes/footer.php"; ?>
