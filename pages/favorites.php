<?php
// Démarrer la session PHP pour accéder aux variables de session utilisateur
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifie si l'utilisateur est connecté, sinon le redirige vers la page de login
if (!isset($_SESSION["user_id"])) {
    $_SESSION["message"] = "Vous devez vous connecter pour accéder à vos favoris.";
    $_SESSION["message_type"] = "warning";
    header("location: login.php");
    exit;
}

// Inclusion de la connexion à la base de données
require_once "../includes/db_connection.php";

// Inclusion du header (barre de navigation, etc.)
include "../includes/header.php";

// Ajout du CSS spécifique à la page des favoris
echo '<link rel="stylesheet" href="../css/favorites.css">';

// Si l'utilisateur veut retirer un favori (via un paramètre GET)
if(isset($_GET["remove"]) && !empty($_GET["remove"])) {
    $property_id = $_GET["remove"];
    $user_id = $_SESSION["user_id"];

    // Prépare et exécute la requête pour supprimer le favori
    $sql = "DELETE FROM favorites WHERE user_id = ? AND property_id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $property_id);

        if(mysqli_stmt_execute($stmt)) {
            $_SESSION["message"] = "Propriété supprimée des favoris avec succès.";
            $_SESSION["message_type"] = "success";

            // On enregistre l'activité de suppression dans la table user_activity
            $activity_sql = "INSERT INTO user_activity (user_id, activity_type, property_id) VALUES (?, 'favorite_remove', ?)";
            $activity_stmt = mysqli_prepare($conn, $activity_sql);
            mysqli_stmt_bind_param($activity_stmt, "ii", $user_id, $property_id);
            mysqli_stmt_execute($activity_stmt);

            // On recharge la page pour mettre à jour la liste
            echo "<script>window.location.href='favorites.php';</script>";
            exit;
        } else {
            $_SESSION["message"] = "Une erreur est survenue. Veuillez réessayer.";
            $_SESSION["message_type"] = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// Récupère la liste des favoris de l'utilisateur connecté
$user_id = $_SESSION["user_id"];
$favorites = [];

$sql = "SELECT p.*, f.created_at AS favorite_date 
        FROM properties p 
        JOIN favorites f ON p.id = f.property_id
        WHERE f.user_id = ? 
        ORDER BY f.created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $favorites[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>
<!-- Affichage de la liste des favoris -->
<div class="container py-4">
    <h1 class="mb-4">Mes favoris</h1>

    <?php if(empty($favorites)): ?>
        <!-- Message si aucun favori -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Vous n'avez pas encore de favoris. Explorez nos <a href="../pages/search.php" class="alert-link">logements disponibles</a> et ajoutez-les à vos favoris!
        </div>
        <div class="mt-4">
            <a href="search.php" class="btn btn-outline-primary">
                <i class="fas fa-search me-2"></i>Découvrir plus de logements
            </a>
        </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach($favorites as $property): ?>
        <div class="col mb-4">
            <div class="property-card" data-property-id="<?php echo $property['id']; ?>">
                <div class="position-relative">
                    <div class="property-image-container">
                        <?php if(!empty($property['main_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($property['main_image']); ?>" class="property-image" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        <?php else: ?>
                        <img src="../assets/property_images/default.jpg" class="property-image" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        <?php endif; ?>
                    </div>

                    <!-- Bouton pour retirer des favoris -->
                    <button class="favorite-button favorited" data-property-id="<?php echo $property['id']; ?>">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                <div class="property-info">
                    <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                    <p class="card-text">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                    </p>
                    <!-- Détails du logement -->
                    <div class="property-details mb-3">
                        <div>
                            <i class="fas fa-home me-1"></i>
                            <?php echo htmlspecialchars(ucfirst($property['property_type'])); ?> -
                            <?php echo htmlspecialchars($property['rooms']); ?> pièce(s)
                        </div>
                        <div>
                            <i class="fas fa-user-friends me-1"></i>
                            <?php echo htmlspecialchars($property['max_guests']); ?> voyageur(s) max
                        </div>
                    </div>
                    <!-- Prix par nuit -->
                    <div class="reservation-price">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-euro-sign me-1"></i>
                                Prix par nuit
                            </div>
                            <span class="price-amount"><?php echo number_format($property['price'], 2, ',', ' '); ?> €</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <a href="property-details.php?id=<?php echo $property['id']; ?>" class="btn btn-sm btn-outline-secondary me-1">Voir détails</a>
                            <a href="reservation.php?property_id=<?php echo $property['id']; ?>" class="btn btn-sm btn-primary">Réserver</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<!-- Inclusion du script JS pour gérer les favoris côté client -->
<script src="../js/favorites.js"></script>
<?php include_once "../includes/footer.php"; ?>
