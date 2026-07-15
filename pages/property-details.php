<?php
// --- Démarrage de la session utilisateur ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";
// --- Vérification de la présence de l'identifiant du logement ---
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Aucun logement spécifié.";
    $_SESSION['message_type'] = "warning";
    header("Location: search.php");
    exit;
}
// --- Inclusion du header (barre de navigation, etc.) ---
include_once "../includes/header.php";
// --- Ajout du CSS spécifique à la page de détails ---
echo '<link rel="stylesheet" href="../css/property-details.css">';

$property_id = (int)$_GET['id'];

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$sql = "SELECT p.*, 
        u.first_name, u.last_name, u.profile_image,
        (SELECT COUNT(*) FROM favorites WHERE property_id = p.id AND user_id = ?) AS is_favorite
        FROM properties p 
        JOIN users u ON p.owner_id = u.id
        WHERE p.id = ? AND p.is_published = TRUE";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $property_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// --- Vérification de l'existence du logement dans la base de données ---
if (mysqli_num_rows($result) === 0) {
    $_SESSION['message'] = "Ce logement n'existe pas ou n'est pas disponible.";
    $_SESSION['message_type'] = "warning";
    header("Location: search.php");
    exit;
}

$property = mysqli_fetch_assoc($result);


$images_sql = "SELECT image_path FROM property_images WHERE property_id = ?";
$images_stmt = mysqli_prepare($conn, $images_sql);
mysqli_stmt_bind_param($images_stmt, "i", $property_id);
mysqli_stmt_execute($images_stmt);
$images_result = mysqli_stmt_get_result($images_stmt);

$images = [];
while ($img = mysqli_fetch_assoc($images_result)) {
    $images[] = $img['image_path'];
}


if (!empty($property['main_image'])) {

    $main_image_index = array_search($property['main_image'], $images);
    if ($main_image_index !== false) {
        array_splice($images, $main_image_index, 1);
    }
    array_unshift($images, $property['main_image']);
}

if (empty($images)) {
    $images[] = "assets/property_images/default.jpg";
}

$reviews_sql = "SELECT r.*, b.user_id, u.first_name, u.last_name, u.profile_image
                FROM reviews r
                JOIN bookings b ON r.booking_id = b.id
                JOIN users u ON b.user_id = u.id
                WHERE b.property_id = ?
                ORDER BY r.created_at DESC";

$reviews_stmt = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($reviews_stmt, "i", $property_id);
mysqli_stmt_execute($reviews_stmt);
$reviews_result = mysqli_stmt_get_result($reviews_stmt);

$reviews = [];
$total_rating = 0;
$review_count = 0;

while ($review = mysqli_fetch_assoc($reviews_result)) {
    $reviews[] = $review;
    $total_rating += $review['rating'];
    $review_count++;
}

$average_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;


if ($user_id) {
    $activity_sql = "INSERT INTO user_activity (user_id, activity_type, property_id) VALUES (?, 'property_view', ?)";
    $activity_stmt = mysqli_prepare($conn, $activity_sql);
    mysqli_stmt_bind_param($activity_stmt, "ii", $user_id, $property_id);
    mysqli_stmt_execute($activity_stmt);
}
?>


<div class="container property-details-page my-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="property-title"><?= htmlspecialchars($property['title']) ?></h1>
            <p class="property-location">
                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?>, <?= htmlspecialchars($property['city']) ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <?php if ($user_id && $user_id != $property['owner_id']): ?>
                <button class="btn favorite-button-lg <?= $property['is_favorite'] ? 'favorited' : '' ?>"
                        data-property-id="<?= $property['id'] ?>">
                    <i class="<?= $property['is_favorite'] ? 'fas' : 'far' ?> fa-heart"></i>
                    <?= $property['is_favorite'] ? 'Retiré des favoris' : 'Ajouter aux favoris' ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="property-gallery mb-4">
        <div class="image-gallery-container">

            <div class="image-carousel">
                <div class="carousel-container">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="carousel-slide <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                            <img src="../<?= htmlspecialchars($image) ?>" class="img-fluid carousel-image" alt="<?= htmlspecialchars($property['title']) ?> image <?= $index+1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($images) > 1): ?>
                    <button class="carousel-nav prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-nav next">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <div class="carousel-indicators">
                        <?php for ($i = 0; $i < count($images); $i++): ?>
                            <span class="indicator <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="thumbnail-container d-none d-md-block">
                <div class="thumbnail-row">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                            <img src="../<?= htmlspecialchars($image) ?>" alt="Thumbnail <?= $index+1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div><div class="row">
        <div class="col-lg-8">
            <div class="host-section mb-4">
                <div class="d-flex align-items-center">
                    <img src="../<?= htmlspecialchars($property['profile_image']) ?>" class="host-image" alt="Host">
                    <div class="ms-3">
                        <h5 class="mb-0">Logement proposé par <?= htmlspecialchars($property['first_name']) ?> <?= htmlspecialchars($property['last_name']) ?></h5>
                        <p class="text-muted mb-0">Propriétaire</p>
                    </div>
                </div>
            </div>

            <div class="property-description mb-4">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($property['description'])) ?></p>
            </div>

            <div class="property-features mb-4">
                <h3>Caractéristiques</h3>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="features-list">
                            <li><i class="fas fa-home"></i> <?= htmlspecialchars($property['property_type']) ?></li>
                            <li><i class="fas fa-expand"></i> <?= htmlspecialchars($property['surface_area']) ?> m²</li>
                            <li><i class="fas fa-door-open"></i> <?= htmlspecialchars($property['rooms']) ?> pièce(s)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="features-list">
                            <li><i class="fas fa-user-friends"></i> <?= htmlspecialchars($property['max_guests']) ?> personne(s) max</li>
                            <li><i class="fas fa-calendar-alt"></i> Disponible du <?= date('d/m/Y', strtotime($property['available_from'])) ?> au <?= date('d/m/Y', strtotime($property['available_to'])) ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="property-location mb-4">
                <h3>Adresse</h3>
                <p>
                    <?= htmlspecialchars($property['address']) ?><br>
                    <?= htmlspecialchars($property['postal_code']) ?> <?= htmlspecialchars($property['city']) ?>
                </p>
                <div class="location-map">
                    <iframe
                        width="100%"
                        height="300"
                        frameborder="0"
                        scrolling="no"
                        marginheight="0"
                        marginwidth="0"
                        src="https://maps.google.com/maps?q=<?= urlencode($property['address'] . ', ' . $property['postal_code'] . ' ' . $property['city']) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed">
                    </iframe>
                </div>
            </div>

            <div class="property-reviews">
                <h3>Avis (<?= $review_count ?>)</h3>
                <?php if ($review_count > 0): ?>
                    <div class="overall-rating mb-3">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= floor($average_rating)): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i - $average_rating < 1 && $i - $average_rating > 0): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span class="ms-2"><?= $average_rating ?> sur 5</span>
                        </div>
                    </div>

                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="d-flex">
                                    <img src="../<?= htmlspecialchars($review['profile_image']) ?>" class="reviewer-image" alt="Reviewer">
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0"><?= htmlspecialchars($review['first_name']) ?> <?= htmlspecialchars($review['last_name'][0]) ?>.</h5>
                                            <span class="review-date text-muted"><?= date('d/m/Y', strtotime($review['created_at'])) ?></span>
                                        </div>
                                        <div class="rating-stars small">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="mt-2"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Aucun avis pour l'instant.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="booking-card">
                <h3 class="mb-3">Réserver ce logement</h3>
                <div class="price-info mb-3">
                    <span class="price"><?= htmlspecialchars($property['price']) ?>€</span> / nuit
                </div>
                <form action="booking.php" method="GET">
                    <input type="hidden" name="property_id" value="<?= $property_id ?>">

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="check-in" class="form-label">Arrivée</label>
                            <input type="date" class="form-control" id="check-in" name="check_in"
                                   min="<?= $property['available_from'] ?>"
                                   max="<?= $property['available_to'] ?>"
                                   required>
                        </div>
                        <div class="col-6">
                            <label for="check-out" class="form-label">Départ</label>
                            <input type="date" class="form-control" id="check-out" name="check_out"
                                   min="<?= $property['available_from'] ?>"
                                   max="<?= $property['available_to'] ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="guests" class="form-label">Voyageurs</label>
                        <select class="form-select" id="guests" name="guests">
                            <?php for ($i = 1; $i <= (int)$property['max_guests']; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> voyageur<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="total-calculation mb-3">
                        <div class="d-flex justify-content-between">
                            <span><?= htmlspecialchars($property['price']) ?>€ x <span id="nights-count">0</span> nuits</span>
                            <span id="subtotal">0€</span>
                        </div>
                    </div>

                    <div class="total-price d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong id="total-price">0€</strong>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"<?= $user_id ? '' : ' disabled' ?>>
                        <?= $user_id ? 'Réserver' : 'Connectez-vous pour réserver' ?>
                    </button>

                    <?php if (!$user_id): ?>
                        <div class="login-prompt mt-2 text-center">
                            <a href="login.php?redirect=property-details.php?id=<?= $property_id ?>">Se connecter</a> ou
                            <a href="register.php">S'inscrire</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    function calculateDays(start, end) {
        const startDate = new Date(start);
        const endDate = new Date(end);
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    }

    function updateBookingCalculation() {
        const checkIn = document.getElementById('check-in').value;
        const checkOut = document.getElementById('check-out').value;
        const pricePerNight = <?= $property['price'] ?>;

        if (checkIn && checkOut) {
            const nights = calculateDays(checkIn, checkOut);
            if (nights > 0) {
                const subtotal = nights * pricePerNight;

                document.getElementById('nights-count').textContent = nights;
                document.getElementById('subtotal').textContent = subtotal + '€';
                document.getElementById('total-price').textContent = subtotal + '€';
            }
        }
    }

    document.getElementById('check-in').addEventListener('change', updateBookingCalculation);
    document.getElementById('check-out').addEventListener('change', updateBookingCalculation);

    const favoriteButton = document.querySelector('.favorite-button-lg');
    if (favoriteButton) {
        favoriteButton.addEventListener('click', function(e) {
            e.preventDefault();

            const propertyId = this.dataset.propertyId;
            const isFavorite = this.classList.contains('favorited');

            this.classList.toggle('favorited');

            if (isFavorite) {
                this.innerHTML = '<i class="far fa-heart"></i> Ajouter aux favoris';
            } else {
                this.innerHTML = '<i class="fas fa-heart"></i> Retirer des favoris';
            }

            fetch('../includes/favorite_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `property_id=${propertyId}&action=${isFavorite ? 'remove' : 'add'}`
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        favoriteButton.classList.toggle('favorited');
                        favoriteButton.innerHTML = isFavorite ?
                            '<i class="fas fa-heart"></i> Retirer des favoris' :
                            '<i class="far fa-heart"></i> Ajouter aux favoris';

                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    }
</script>
<script src="../js/property-gallery.js"></script>

<?php include_once "../includes/footer.php"; ?>
