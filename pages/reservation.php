<?php
// --- Démarrage de la session utilisateur ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Vérification de l'authentification ---
// On vérifie que l'utilisateur est connecté, sinon on le redirige vers la page de connexion
if (!isset($_SESSION["user_id"])) {
    $_SESSION["message"] = "Vous devez vous connecter pour accéder à vos réservations.";
    $_SESSION["message_type"] = "warning";
    header("location: login.php");
    exit;
}

// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";

// --- Inclusion du header (barre de navigation, etc.) ---
include "../includes/header.php";

// --- Ajout du CSS et JS spécifiques à la page de réservation ---
echo '<link rel="stylesheet" href="../css/reservation.css?v='.time().'">';
echo '<script src="../js/reservation.js?v='.time().'" defer></script>';

$user_id = $_SESSION["user_id"];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';

// --- Annulation d'une réservation ---
if(isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $reservation_id = $_GET['cancel'];

    $check_sql = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";
    if($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $reservation_id, $user_id);
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) > 0) {
                // La réservation appartient à l'utilisateur, on peut l'annuler
                $update_sql = "UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
                if($update_stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($update_stmt, "i", $reservation_id);
                    if(mysqli_stmt_execute($update_stmt)) {
                        $_SESSION["message"] = "Réservation annulée avec succès.";
                        $_SESSION["message_type"] = "success";
                    } else {
                        $_SESSION["message"] = "Erreur lors de l'annulation de la réservation.";
                        $_SESSION["message_type"] = "danger";
                    }
                    mysqli_stmt_close($update_stmt);
                }
            } else {
                $_SESSION["message"] = "Vous n'êtes pas autorisé à annuler cette réservation.";
                $_SESSION["message_type"] = "danger";
            }
        }
        mysqli_stmt_close($stmt);
    }

    echo "<script>window.location.href='reservation.php?tab=" . $active_tab . "';</script>";
    exit;
    exit;
}

// --- Récupération des réservations à venir ---
$upcoming_reservations = [];
$upcoming_sql = "SELECT b.*, p.title, p.main_image, p.location, p.price, u.last_name as owner_name, u.id as owner_id 
                 FROM bookings b 
                 JOIN properties p ON b.property_id = p.id 
                 JOIN users u ON p.owner_id = u.id 
                 WHERE b.user_id = ? 
                 AND (b.status = 'confirmed' OR b.status = 'pending')
                 AND b.end_date >= CURRENT_DATE() 
                 ORDER BY b.start_date ASC";

if($stmt = mysqli_prepare($conn, $upcoming_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $upcoming_reservations[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// --- Récupération des réservations passées ---
// Récupérer les réservations passées de l'utilisateur
$past_reservations = [];
$past_sql = "SELECT b.*, p.title, p.main_image, p.location, p.price, u.last_name as owner_name, u.id as owner_id
             FROM bookings b 
             JOIN properties p ON b.property_id = p.id 
             JOIN users u ON p.owner_id = u.id 
             WHERE b.user_id = ? 
             AND b.status = 'confirmed' 
             AND b.end_date < CURRENT_DATE() 
             ORDER BY b.end_date DESC";

if($stmt = mysqli_prepare($conn, $past_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $past_reservations[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// --- Récupération des réservations annulées ---
// Récupérer les réservations annulées
$cancelled_reservations = [];
$cancelled_sql = "SELECT b.*, p.title, p.main_image, p.location, p.price, u.last_name as owner_name, u.id as owner_id 
                  FROM bookings b
                  JOIN properties p ON b.property_id = p.id 
                  JOIN users u ON p.owner_id = u.id 
                  WHERE b.user_id = ? 
                  AND b.status = 'cancelled' 
                  ORDER BY b.updated_at DESC";

if($stmt = mysqli_prepare($conn, $cancelled_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $cancelled_reservations[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container py-4">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-2">Mes réservations</h1>
        <p class="text-muted">Gérez vos séjours passés et à venir</p>
        <div class="separator mx-auto my-3"></div>
    </div>

    <!-- Tabs for different reservation statuses -->
    <ul class="nav nav-tabs mb-4 justify-content-center">
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab == 'upcoming' ? 'active' : ''; ?>" href="?tab=upcoming">
                <i class="fas fa-calendar-alt me-2"></i> À venir <span class="badge bg-primary ms-2"><?php echo count($upcoming_reservations); ?></span>
</a>
</li>
<li class="nav-item">
    <a class="nav-link <?php echo $active_tab == 'past' ? 'active' : ''; ?>" href="?tab=past">
        <i class="fas fa-history me-2"></i> Passées <span class="badge bg-secondary ms-2"><?php echo count($past_reservations); ?></span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link <?php echo $active_tab == 'cancelled' ? 'active' : ''; ?>" href="?tab=cancelled">
        <i class="fas fa-ban me-2"></i> Annulées <span class="badge bg-light text-dark ms-2"><?php echo count($cancelled_reservations); ?></span>
    </a>
</li>
</ul>

<!-- Tab content -->
<div class="tab-content">
    <!-- Upcoming Reservations -->
    <div class="tab-pane fade <?php echo $active_tab == 'upcoming' ? 'show active' : ''; ?>" id="upcoming">
        <?php if(empty($upcoming_reservations)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Vous n'avez pas de réservations à venir. <a href="../pages/search.php" class="alert-link">Recherchez un logement</a> pour planifier votre prochain séjour!
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($upcoming_reservations as $reservation): ?>
                    <div class="col-md-6 col-lg-4 mb-4">                            <div class="card reservation-card h-100">                            <div class="position-relative">
                                <?php if(!empty($reservation['main_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($reservation['main_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($reservation['title']); ?>">
                                <?php else: ?>
                                    <img src="../assets/property_images/default-property.jpg" class="card-img-top" alt="<?php echo htmlspecialchars($reservation['title']); ?>">
                                <?php endif; ?>

                                <?php if($reservation['status'] == 'confirmed'): ?>
                                    <div class="status-badge status-confirmed">
                                        <i class="fas fa-check-circle me-1"></i> Confirmée
                                    </div>
                                <?php elseif($reservation['status'] == 'pending'): ?>
                                    <div class="status-badge status-pending">
                                        <i class="fas fa-clock me-1"></i> En attente
                                    </div>
                                <?php elseif($reservation['status'] == 'cancelled'): ?>
                                    <div class="status-badge status-cancelled">
                                        <i class="fas fa-times-circle me-1"></i> Annulée
                                    </div>
                                <?php endif; ?></div>                                <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($reservation['title']); ?></h5>

                                <!-- Location information -->
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($reservation['location']); ?>
                                </p>

                                <!-- Host information -->
                                <p class="card-text">
                                    <i class="fas fa-user"></i>
                                    Hébergé par <?php echo htmlspecialchars($reservation['owner_name']); ?>
                                </p>

                                <!-- Reservation dates in styled box -->
                                <div class="reservation-dates">
                                    <div>
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <strong>Du</strong> <?php echo date('d/m/Y', strtotime($reservation['start_date'])); ?>
                                        <strong>au</strong> <?php echo date('d/m/Y', strtotime($reservation['end_date'])); ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-moon me-1"></i>
                                        <?php
                                        $interval = date_diff(date_create($reservation['start_date']), date_create($reservation['end_date']));
                                        echo $interval->format('%a') . ' nuit(s)';
                                        ?>
                                    </div>
                                </div>

                                <!-- Price information in styled box -->
                                <div class="reservation-price">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-euro-sign me-1"></i>
                                            Total
                                            <?php if(isset($reservation['guests'])): ?>
                                                <small class="text-muted ms-2">
                                                    <i class="fas fa-user-friends me-1 small"></i><?php echo $reservation['guests']; ?> voyageur(s)
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <span class="price-amount"><?php echo number_format($reservation['total_price'], 2, ',', ' '); ?> €</span>
                                    </div>
                                </div>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $reservation['id']; ?>">
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Cancel Modal -->
                        <div class="modal fade" id="cancelModal<?php echo $reservation['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirmer l'annulation</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Êtes-vous sûr de vouloir annuler votre réservation pour
                                            <strong><?php echo htmlspecialchars($reservation['title']); ?></strong>
                                            du <?php echo date('d/m/Y', strtotime($reservation['start_date'])); ?>
                                            au <?php echo date('d/m/Y', strtotime($reservation['end_date'])); ?> ?</p>
                                        <p class="text-danger">Cette action est irréversible.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <a href="?cancel=<?php echo $reservation['id']; ?>&tab=upcoming" class="btn btn-danger">Confirmer l'annulation</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Past Reservations -->
    <div class="tab-pane fade <?php echo $active_tab == 'past' ? 'show active' : ''; ?>" id="past">
        <?php if(empty($past_reservations)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Vous n'avez pas de réservations passées.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($past_reservations as $reservation): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card reservation-card h-100">
                            <div class="position-relative">
                                <?php if(!empty($reservation['main_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($reservation['main_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($reservation['title']); ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="../assets/property_images/default-property.jpg" class="card-img-top" alt="<?php echo htmlspecialchars($reservation['title']); ?>" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($reservation['title']); ?></h5>
                                <p class="card-text text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($reservation['location']); ?>
                                </p>
                                <p class="card-text">
                                    <span class="badge bg-secondary mb-2">Terminé</span><br>
                                    Du <?php echo date('d/m/Y', strtotime($reservation['start_date'])); ?>
                                    au <?php echo date('d/m/Y', strtotime($reservation['end_date'])); ?>
                                </p>
                                <p class="card-text">
                                    <small class="text-muted">Total payé: <?php echo number_format($reservation['total_price'], 2, ',', ' '); ?> €</small>
                                </p>
                                <!-- Suppression du bouton contacter l'hôte pour les réservations passées -->
                                <div class="action-buttons mt-3">
                                    <?php if(!isset($reservation['has_review'])): ?>
                                        <a href="review.php?booking_id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-success">
                                            Laisser un avis
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancelled Reservations -->
    <div class="tab-pane fade <?php echo $active_tab == 'cancelled' ? 'show active' : ''; ?>" id="cancelled">
        <?php if(empty($cancelled_reservations)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Vous n'avez pas de réservations annulées.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($cancelled_reservations as $reservation): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card reservation-card h-100 bg-light">
                            <div class="position-relative">
                                <?php if(!empty($reservation['main_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($reservation['main_image']); ?>" class="card-img-top opacity-50" alt="<?php echo htmlspecialchars($reservation['title']); ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="../assets/property_images/default-property.jpg" class="card-img-top opacity-50" alt="<?php echo htmlspecialchars($reservation['title']); ?>" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>

                                <div class="badge bg-danger position-absolute" style="top: 15px; left: 15px;">
                                    Annulé
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title text-muted"><?php echo htmlspecialchars($reservation['title']); ?></h5>
                                <p class="card-text text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($reservation['location']); ?>
                                </p>
                                <p class="card-text text-muted">
                                    Du <?php echo date('d/m/Y', strtotime($reservation['start_date'])); ?>
                                    au <?php echo date('d/m/Y', strtotime($reservation['end_date'])); ?>
                                </p>
                                <p class="card-text">
                                    <small class="text-muted">Annulé le <?php echo date('d/m/Y', strtotime($reservation['updated_at'])); ?></small>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>



<?php include "../includes/footer.php"; ?>