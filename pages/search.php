<?php
// --- Démarrage de la session utilisateur ---
// On vérifie si une session existe déjà, sinon on la démarre pour gérer l'utilisateur connecté
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";

// --- Inclusion du header (barre de navigation, etc.) ---
include_once "../includes/header.php";

// --- Ajout du CSS spécifique à la page de recherche ---
echo '<link rel="stylesheet" href="../css/search.css">';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$location = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';
$check_in = isset($_GET['check_in']) ? mysqli_real_escape_string($conn, $_GET['check_in']) : '';
$check_out = isset($_GET['check_out']) ? mysqli_real_escape_string($conn, $_GET['check_out']) : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;
$property_type = isset($_GET['property_type']) ? mysqli_real_escape_string($conn, $_GET['property_type']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$min_rooms = isset($_GET['min_rooms']) ? (int)$_GET['min_rooms'] : 1;

// --- Construction de la requête de recherche de propriétés ---
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM favorites WHERE property_id = p.id AND user_id = ?) AS is_favorite
        FROM properties p WHERE is_published = TRUE";

$params = [$user_id];
$types = "i";

// --- Filtrage par emplacement (ville ou localisation) ---
if (!empty($location)) {
    $sql .= " AND (city LIKE ? OR location LIKE ?)";
    $params[] = "%$location%";
    $params[] = "%$location%";
    $types .= "ss";
}

// --- Filtrage par type de propriété ---
if (!empty($property_type)) {
    $sql .= " AND property_type = ?";
    $params[] = $property_type;
    $types .= "s";
}

// --- Filtrage par dates d'arrivée et de départ (disponibilité) ---
if (!empty($check_in) && !empty($check_out)) {
    $sql .= " AND p.id NOT IN (
                SELECT property_id FROM bookings 
                WHERE (start_date <= ? AND end_date >= ?) 
                OR (start_date <= ? AND end_date >= ?) 
                OR (start_date >= ? AND end_date <= ?)
                AND status = 'confirmed'
             )";
    $params[] = $check_out;
    $params[] = $check_in;
    $params[] = $check_in;
    $params[] = $check_out;
    $params[] = $check_in;
    $params[] = $check_out;
    $types .= "ssssss";
}

// --- Filtrage par nombre de voyageurs ---
$sql .= " AND max_guests >= ?";
$params[] = $guests;
$types .= "i";

// --- Filtrage par plage de prix ---
$sql .= " AND price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

// --- Filtrage par nombre minimum de pièces ---
$sql .= " AND rooms >= ?";
$params[] = $min_rooms;
$types .= "i";

// --- Tri des résultats par prix croissant ---
$sql .= " ORDER BY price ASC";

$stmt = mysqli_prepare($conn, $sql);

// --- Liaison des paramètres et exécution de la requête ---
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($result);

// --- Fonction pour obtenir l'image principale d'une propriété ---
function getPropertyMainImage($conn, $property_id, $main_image) {
    if (!empty($main_image) && file_exists("../" . $main_image)) {
        return $main_image;
    }

    // Essayer d'obtenir la première image à partir de la table property_images
    $img_sql = "SELECT image_path FROM property_images WHERE property_id = ? LIMIT 1";
    $img_stmt = mysqli_prepare($conn, $img_sql);
    mysqli_stmt_bind_param($img_stmt, "i", $property_id);
    mysqli_stmt_execute($img_stmt);
    $img_result = mysqli_stmt_get_result($img_stmt);

    if ($img_row = mysqli_fetch_assoc($img_result)) {
        return $img_row['image_path'];
    }
    return "assets/property_images/default.jpg";
}

// --- Récupération des villes pour le filtre ---
$cities_query = "SELECT DISTINCT city FROM properties WHERE is_published = TRUE ORDER BY city";
$cities_result = mysqli_query($conn, $cities_query);

// --- Récupération du prix maximum pour le filtre ---
$max_price_query = "SELECT MAX(price) as max_price FROM properties";
$max_price_result = mysqli_query($conn, $max_price_query);
$max_price_row = mysqli_fetch_assoc($max_price_result);
$db_max_price = $max_price_row['max_price'];
?>
<div class="container search-page my-4">
    <h1 class="mb-4">Trouver un logement</h1>

    <div class="row">
        <!-- Colonne des filtres -->
        <div class="col-lg-3">
            <div class="d-lg-none mb-3">
                <button id="filter-toggle" class="btn btn-primary w-100">Filtres <i class="fas fa-filter"></i></button>
            </div>

            <div id="filters-container" class="d-none d-lg-block">
                <div class="search-filters">
                    <h5 class="mb-3">Filtres</h5>

                    <form id="search-filters" method="GET" action="search.php">
                        <div class="mb-3">
                            <label for="location" class="form-label">Destination</label>
                            <select class="form-select" id="location" name="location">
                                <option value="">Toutes les villes</option>
                                <?php while($city = mysqli_fetch_assoc($cities_result)): ?>
                                    <option value="<?= htmlspecialchars($city['city']) ?>"
                                        <?= ($location == $city['city']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city['city']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="check_in" class="form-label">Date d'arrivée</label>
                            <input type="date" class="form-control" id="check_in" name="check_in"
                                   value="<?= htmlspecialchars($check_in) ?>" min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="check_out" class="form-label">Date de départ</label>
                            <input type="date" class="form-control" id="check_out" name="check_out"
                                   value="<?= htmlspecialchars($check_out) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="guests" class="form-label">Nombre de voyageurs</label>
                            <select class="form-select" id="guests" name="guests">
                                <?php for($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($guests == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="property_type" class="form-label">Type de logement</label>
                            <select class="form-select" id="property_type" name="property_type">
                                <option value="">Tous les types</option>
                                <option value="apartment" <?= ($property_type == 'apartment') ? 'selected' : '' ?>>Appartement</option>
                                <option value="studio" <?= ($property_type == 'studio') ? 'selected' : '' ?>>Studio</option>
                                <option value="house" <?= ($property_type == 'house') ? 'selected' : '' ?>>Maison</option>
                                <option value="room" <?= ($property_type == 'room') ? 'selected' : '' ?>>Chambre</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="price-range" class="form-label">Prix max par nuit: <span id="price-value"><?= $max_price ?>€</span></label>
                            <input type="range" class="form-range" id="price-range" name="max_price"
                                   min="0" max="<?= $db_max_price ?>" value="<?= $max_price ?>">
                        </div>

                        <div class="mb-3">
                            <label for="min_rooms" class="form-label">Nombre minimum de pièces</label>
                            <select class="form-select" id="min_rooms" name="min_rooms">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($min_rooms == $i) ? 'selected' : '' ?>><?= $i ?>+</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <input type="hidden" name="min_price" value="0">

                        <button type="submit" class="btn btn-primary w-100">Appliquer les filtres</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Colonne des resulats -->
        <div class="col-lg-9">
            <!-- Results Count -->
            <div class="mb-4">
                <h5><?= $count ?> logement(s) trouvé(s)</h5>
            </div>

            <?php if ($count == 0): ?>
                <div class="alert alert-info">
                    Aucun logement ne correspond à vos critères. Essayez d'élargir votre recherche.
                </div>
            <?php else: ?>
                <!-- resultat  -->
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php while ($property = mysqli_fetch_assoc($result)): ?>
                        <div class="col">
                            <a href="property-details.php?id=<?= $property['id'] ?>" class="property-link">
                                <div class="card property-card h-100">
                                    <div class="position-relative">
                                        <div class="property-image-container">
                                            <?php $image_path = getPropertyMainImage($conn, $property['id'], $property['main_image']); ?>
                                            <img src="<?= htmlspecialchars("../" . $image_path) ?>" class="property-image" alt="<?= htmlspecialchars($property['title']) ?>">
                                        </div>

                                        <?php if ($user_id): ?>
                                            <button class="favorite-button <?= $property['is_favorite'] ? 'favorited' : '' ?>" data-property-id="<?= $property['id'] ?>">
                                                <i class="<?= $property['is_favorite'] ? 'fas' : 'far' ?> fa-heart"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="property-info">
                                        <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>

                                        <!-- Emplacement information -->
                                        <p class="card-text">
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?>
                                        </p>

                                        <!-- Info appart -->
                                        <div class="property-details mb-3">
                                            <div>
                                                <i class="fas fa-home me-1"></i>
                                                <?= htmlspecialchars(ucfirst($property['property_type'])) ?> -
                                                <?= htmlspecialchars($property['rooms']) ?> pièce(s)
                                            </div>
                                            <div>
                                                <i class="fas fa-user-friends me-1"></i>
                                                <?= htmlspecialchars($property['max_guests']) ?> voyageur(s) max
                                            </div>
                                        </div>

                                        <!-- Info prix  -->
                                        <div class="reservation-price">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-euro-sign me-1"></i>
                                                    Prix par nuit
                                                </div>
                                                <span class="price-amount"><?= number_format($property['price'], 2, ',', ' ') ?> €</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>