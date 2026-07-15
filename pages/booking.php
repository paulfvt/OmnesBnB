<?php
// --- Démarrage de la session utilisateur ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Vérification de l'authentification ---
if (!isset($_SESSION["user_id"])) {
    $_SESSION["message"] = "Vous devez vous connecter pour effectuer une réservation.";
    $_SESSION["message_type"] = "warning";
    // On garde la page à laquelle l'utilisateur voulait accéder pour le rediriger après connexion
    $_SESSION["redirect_after_login"] = "property-details.php?id=" . (isset($_GET['property_id']) ? $_GET['property_id'] : '');
    header("Location: login.php");
    exit;
}

// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";

// --- Inclusion du header (barre de navigation, etc.) ---
include "../includes/header.php";

$user_id = $_SESSION["user_id"];

// --- Vérification des paramètres de réservation ---
if (!isset($_GET['property_id']) || !isset($_GET['check_in']) || !isset($_GET['check_out']) || !isset($_GET['guests'])) {
    $_SESSION["message"] = "Informations de réservation manquantes.";
    $_SESSION["message_type"] = "warning";
    header("Location: search.php");
    exit;
}

$property_id = (int)$_GET['property_id'];
$check_in = $_GET['check_in'];
$check_out = $_GET['check_out'];
$guests = (int)$_GET['guests'];

// --- Vérification de la date d'arrivée ---
$today = date('Y-m-d');
if ($check_in < $today) {
    $_SESSION["message"] = "La date d'arrivée ne peut pas être dans le passé.";
    $_SESSION["message_type"] = "warning";
    header("Location: property-details.php?id=" . $property_id);
    exit;
}

// --- Vérification de la date de départ ---
if ($check_out <= $check_in) {
    $_SESSION["message"] = "La date de départ doit être postérieure à la date d'arrivée.";
    $_SESSION["message_type"] = "warning";
    header("Location: property-details.php?id=" . $property_id);
    exit;
}

// --- Récupération des détails de la propriété ---
$property_sql = "SELECT p.*, u.first_name, u.last_name 
                FROM properties p 
                JOIN users u ON p.owner_id = u.id 
                WHERE p.id = ? AND p.is_published = TRUE";

$stmt = mysqli_prepare($conn, $property_sql);
mysqli_stmt_bind_param($stmt, "i", $property_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// --- Vérification de l'existence de la propriété ---
if (mysqli_num_rows($result) === 0) {
    $_SESSION["message"] = "Ce logement n'existe pas ou n'est pas disponible.";
    $_SESSION["message_type"] = "warning";
    header("Location: search.php");
    exit;
}

$property = mysqli_fetch_assoc($result);

// --- Vérification que le propriétaire n'essaie pas de réserver son propre logement ---
if ($property['owner_id'] == $user_id) {
    $_SESSION["message"] = "Vous ne pouvez pas réserver votre propre logement.";
    $_SESSION["message_type"] = "warning";
    header("Location: property-details.php?id=" . $property_id);
    exit;
}

//--- Vérification de la disponibilité du logement aux dates souhaitées ---
if ($check_in < $property['available_from'] || $check_out > $property['available_to']) {
    $_SESSION["message"] = "Le logement n'est pas disponible pour les dates sélectionnées.";
    $_SESSION["message_type"] = "warning";
    header("Location: property-details.php?id=" . $property_id);
    exit;
}

// --- Vérification du nombre de personnes ---
if ($guests <= 0 || $guests > $property['max_guests']) {
    $_SESSION["message"] = "Le nombre de voyageurs est invalide.";
    $_SESSION["message_type"] = "warning";
    header("Location: property-details.php?id=" . $property_id);
    exit;
}

// --- Vérification de la disponibilité du logement (pas déjà réservé) ---
$check_availability_sql = "SELECT id 
                          FROM bookings 
                          WHERE property_id = ? 
                          AND status != 'cancelled' 
                          AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?) OR (start_date >= ? AND end_date <= ?))";

$availability_stmt = mysqli_prepare($conn, $check_availability_sql);
mysqli_stmt_bind_param($availability_stmt, "issssss", $property_id, $check_out, $check_in, $check_in, $check_out, $check_in, $check_out);
mysqli_stmt_execute($availability_stmt);
$availability_result = mysqli_stmt_get_result($availability_stmt);

// --- Vérification si le logement est déjà réservé pour les dates choisies ---
if (mysqli_num_rows($availability_result) > 0) {
    $_SESSION["message"] = "Le logement est déjà réservé pour ces dates.";
    $_SESSION["message_type"] = "warning";
    header("Location: property-details.php?id=" . $property_id);
    exit;
}

// --- Calcul du nombre de nuits et du prix total ---
$check_in_date = new DateTime($check_in);
$check_out_date = new DateTime($check_out);
$interval = $check_in_date->diff($check_out_date);
$nights = $interval->days;
$total_price = $property['price'] * $nights;

// --- Confirmation de la réservation ---
if (isset($_POST['confirm_booking'])) {    // insert dans la base
    $booking_sql = "INSERT INTO bookings (property_id, user_id, start_date, end_date, guests, total_price, status, created_at, updated_at) 
                   VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";

    $booking_stmt = mysqli_prepare($conn, $booking_sql);
    mysqli_stmt_bind_param($booking_stmt, "iissid", $property_id, $user_id, $check_in, $check_out, $guests, $total_price);
    if (mysqli_stmt_execute($booking_stmt)) {
        $booking_id = mysqli_insert_id($conn);

        // Enregistrement de l'activité de l'utilisateur
        $activity_sql = "INSERT INTO user_activity (user_id, activity_type, property_id) VALUES (?, 'booking', ?)";
        $activity_stmt = mysqli_prepare($conn, $activity_sql);
        mysqli_stmt_bind_param($activity_stmt, "ii", $user_id, $property_id);
        mysqli_stmt_execute($activity_stmt);        $_SESSION["message"] = "Réservation confirmée avec succès!";
        $_SESSION["message_type"] = "success";
        // Utiliser JavaScript pour la redirection après HTML output
        echo "<script>window.location.href='reservation.php?tab=upcoming';</script>";
        exit;
    } else {
        $_SESSION["message"] = "Une erreur est survenue lors de la réservation. Veuillez réessayer.";
        $_SESSION["message_type"] = "danger";
    }
}

// --- Récupération de l'image de la propriété ---
$images_sql = "SELECT image_path FROM property_images WHERE property_id = ? LIMIT 1";
$images_stmt = mysqli_prepare($conn, $images_sql);
mysqli_stmt_bind_param($images_stmt, "i", $property_id);
mysqli_stmt_execute($images_stmt);
$images_result = mysqli_stmt_get_result($images_stmt);

$image_path = "";
if (mysqli_num_rows($images_result) > 0) {
    $image = mysqli_fetch_assoc($images_result);
    $image_path = $image['image_path'];
} else if (!empty($property['main_image'])) {
    $image_path = $property['main_image'];
} else {
    $image_path = "assets/property_images/default.jpg";
}

// --- Récupération des informations de l'utilisateur ---
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8">
            <h2 class="mb-4">Confirmer votre réservation</h2>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Détails du séjour</h5>
                </div>
                <div class="card-body">
                    <div class="row">

                        <div class="col-md-8">
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt me-2"></i>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-user-friends me-2"></i>Logement entier · Hôte:
                            </p>
                            <p>
                                <i class="fas fa-home me-2"></i> pièce(s) ·  voyageurs max
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Arrivée</h6>
                        </div>
                        <div class="col-md-6">
                            <h6>Départ</h6>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6>Voyageurs</h6>
                        <p> voyageur(s)</p>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Vos coordonnées</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Nom</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Email</strong></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p class="mb-1"><strong>Téléphone</strong></p>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Ces informations seront partagées avec l'hôte une fois votre réservation confirmée.
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Politique d'annulation</h5>
                </div>
                <div class="card-body">
                    <p>Annulation gratuite jusqu'à 48 heures avant votre arrivée. Après cette date, des frais peuvent s'appliquer.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card booking-summary">
                <div class="card-header">
                    <h5 class="mb-0">Résumé du prix</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo htmlspecialchars($property['price']); ?>€ x <?php echo $nights; ?> nuits</span>
                        <span><?php echo number_format($property['price'] * $nights, 2, ',', ' '); ?>€</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong class="text-primary"><?php echo number_format($total_price, 2, ',', ' '); ?>€</strong>
                    </div>

                    <form method="post" action="">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                J'accepte les <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">conditions générales</a>
                            </label>
                        </div>

                        <button type="submit" name="confirm_booking" class="btn btn-primary btn-lg w-100">
                            Confirmer et payer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Conditions générales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Conditions de réservation</h6>
                    <p>En effectuant cette réservation, vous acceptez les conditions suivantes :</p>
                    <ul>
                        <li>Le paiement est dû au moment de la réservation.</li>
                        <li>Les annulations sont gratuites jusqu'à 48 heures avant l'arrivée.</li>
                        <li>Des frais peuvent s'appliquer pour les annulations tardives ou les non-présentations.</li>
                        <li>L'heure d'arrivée est généralement à partir de 15h00 et l'heure de départ est avant 11h00, sauf indication contraire de l'hôte.</li>
                        <li>Vous vous engagez à respecter les règles de la maison établies par l'hôte.</li>
                    </ul>

                    <h6>Politique de remboursement</h6>
                    <p>Remboursement intégral pour les annulations effectuées dans les 48 heures suivant la réservation, si la date d'arrivée est dans plus de 14 jours.</p>
                    <p>Remboursement de 50% pour les annulations effectuées au moins 7 jours avant la date d'arrivée.</p>
                    <p>Aucun remboursement pour les annulations effectuées moins de 7 jours avant la date d'arrivée.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>


<?php include "../includes/footer.php"; ?>