<?php
// --- Démarrage de la session utilisateur ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";

// --- Vérification de l'authentification et des droits admin ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Vous devez être connecté pour accéder à cette page.";
    $_SESSION['message_type'] = "danger";
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$admin_check_sql = "SELECT * FROM users WHERE id = ? AND (email = 'admin@ece.fr' OR user_type = 'admin')";
$stmt = mysqli_prepare($conn, $admin_check_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$admin_result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($admin_result) == 0) {
    $_SESSION['message'] = "Vous n'avez pas les droits d'accès nécessaires.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../index.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_type = isset($_GET['delete_type']) ? $_GET['delete_type'] : '';
    if ($delete_type === 'user' && $delete_id != $user_id) {
        $sql = "DELETE FROM users WHERE id = ?";
    } elseif ($delete_type === 'property') {
        $sql = "DELETE FROM properties WHERE id = ?";
    } elseif ($delete_type === 'booking') {
        $sql = "DELETE FROM bookings WHERE id = ?";
    } elseif ($delete_type === 'review') {
        $sql = "DELETE FROM reviews WHERE id = ?";
    } else {
        $sql = '';
    }
    if ($sql) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = ucfirst($delete_type) . " supprimé avec succès.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Erreur lors de la suppression: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }
    header("Location: admin.php?tab=" . $delete_type . "s");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $type && $id) {
    if ($type === 'booking') {
        $property_id = intval($_POST['property_id']);
        $user_id_b = intval($_POST['user_id']);
        $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
        $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
        $guests = intval($_POST['guests']);
        $total_price = floatval($_POST['total_price']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $sql = "UPDATE bookings SET property_id=?, user_id=?, start_date=?, end_date=?, guests=?, total_price=?, status=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissiisi", $property_id, $user_id_b, $start_date, $end_date, $guests, $total_price, $status, $id);
    } elseif ($type === 'property') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $postal_code = mysqli_real_escape_string($conn, $_POST['postal_code']);
        $price = floatval($_POST['price']);
        $surface_area = intval($_POST['surface_area']);
        $rooms = intval($_POST['rooms']);
        $max_guests = intval($_POST['max_guests']);
        $available_from = mysqli_real_escape_string($conn, $_POST['available_from']);
        $available_to = mysqli_real_escape_string($conn, $_POST['available_to']);
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $owner_id = intval($_POST['owner_id']);
        $sql = "UPDATE properties SET title=?, description=?, property_type=?, location=?, address=?, city=?, postal_code=?, price=?, surface_area=?, rooms=?, max_guests=?, available_from=?, available_to=?, is_published=?, owner_id=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssdiiisssiii", $title, $description, $property_type, $location, $address, $city, $postal_code, $price, $surface_area, $rooms, $max_guests, $available_from, $available_to, $is_published, $owner_id, $id);
    } elseif ($type === 'review') {
        $rating = intval($_POST['rating']);
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);
        $sql = "UPDATE reviews SET rating=?, comment=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isi", $rating, $comment, $id);
    } else {
        $stmt = null;
    }
    if (isset($stmt) && $stmt && mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = ucfirst($type) . " mis à jour avec succès.";
        $_SESSION['message_type'] = "success";
        header("Location: admin.php?tab=" . $type . "s");
        exit;
    } elseif (isset($stmt)) {
        $_SESSION['message'] = "Erreur lors de la mise à jour: " . mysqli_error($conn);
        $_SESSION['message_type'] = "danger";
    }
}

include_once "../includes/header.php";
?>
<div class="container mt-4">
    <h1>Panneau d'administration</h1>
    <?php
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['message_type'] . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['message'];
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        echo '<span aria-hidden="true">&times;</span>';
        echo '</button>';
        echo '</div>';
        unset($_SESSION['message']);
    }
    ?>

    <div class="row">
        <div class="col-md-3">
            <h4>Navigation</h4>
            <ul class="list-group">
                <li class="list-group-item"><a href="admin.php?tab=users">Utilisateurs</a></li>
                <li class="list-group-item"><a href="admin.php?tab=properties">Propriétés</a></li>
                <li class="list-group-item"><a href="admin.php?tab=bookings">Réservations</a></li>
                <li class="list-group-item"><a href="admin.php?tab=reviews">Avis</a></li>
            </ul>
        </div>
        <div class="col-md-9">
            <?php
            if ($type && $id) {
                if ($type === 'booking') {
                    $booking_sql = "SELECT * FROM bookings WHERE id = ?";
                    $booking_stmt = mysqli_prepare($conn, $booking_sql);
                    mysqli_stmt_bind_param($booking_stmt, "i", $id);
                    mysqli_stmt_execute($booking_stmt);
                    $booking_result = mysqli_stmt_get_result($booking_stmt);
                    $booking = mysqli_fetch_assoc($booking_result);
                    $properties = mysqli_query($conn, "SELECT id, title FROM properties ORDER BY title");
                    $users = mysqli_query($conn, "SELECT id, last_name FROM users ORDER BY last_name");
                    echo '<div class="container mt-4"><h3>Éditer une réservation</h3><form method="post">';
                    echo '<label>Propriété</label><select name="property_id" class="form-control">';
                    while ($p = mysqli_fetch_assoc($properties)) {
                        $sel = $p['id'] == $booking['property_id'] ? 'selected' : '';
                        echo "<option value='{$p['id']}' $sel>{$p['title']}</option>";
                    }
                    echo '</select>';
                    echo '<label>Utilisateur</label><select name="user_id" class="form-control">';
                    while ($u = mysqli_fetch_assoc($users)) {
                        $sel = $u['id'] == $booking['user_id'] ? 'selected' : '';
                        echo "<option value='{$u['id']}' $sel>{$u['last_name']}</option>";
                    }
                    echo '</select>';
                    echo '<label>Date début</label><input type="date" name="start_date" class="form-control" value="' . $booking['start_date'] . '">';
                    echo '<label>Date fin</label><input type="date" name="end_date" class="form-control" value="' . $booking['end_date'] . '">';
                    echo '<label>Nombre de voyageurs</label><input type="number" name="guests" class="form-control" value="' . $booking['guests'] . '">';
                    echo '<label>Prix total</label><input type="number" step="0.01" name="total_price" class="form-control" value="' . $booking['total_price'] . '">';
                    echo '<label>Statut</label><input type="text" name="status" class="form-control" value="' . $booking['status'] . '">';
                    echo '<button type="submit" class="btn btn-primary mt-2">Enregistrer</button>';
                    echo '</form></div>';
                } elseif ($type === 'property') {
                    $property_sql = "SELECT * FROM properties WHERE id = ?";
                    $property_stmt = mysqli_prepare($conn, $property_sql);
                    mysqli_stmt_bind_param($property_stmt, "i", $id);
                    mysqli_stmt_execute($property_stmt);
                    $property_result = mysqli_stmt_get_result($property_stmt);
                    $property = mysqli_fetch_assoc($property_result);
                    $owners = mysqli_query($conn, "SELECT id, last_name FROM users ORDER BY last_name");
                    echo '<div class="container mt-4"><h3>Éditer une propriété</h3><form method="post">';
                    echo '<label>Titre</label><input type="text" name="title" class="form-control" value="' . $property['title'] . '">';
                    echo '<label>Description</label><textarea name="description" class="form-control">' . $property['description'] . '</textarea>';
                    echo '<label>Type</label><input type="text" name="property_type" class="form-control" value="' . $property['property_type'] . '">';
                    echo '<label>Lieu</label><input type="text" name="location" class="form-control" value="' . $property['location'] . '">';
                    echo '<label>Adresse</label><input type="text" name="address" class="form-control" value="' . $property['address'] . '">';
                    echo '<label>Ville</label><input type="text" name="city" class="form-control" value="' . $property['city'] . '">';
                    echo '<label>Code postal</label><input type="text" name="postal_code" class="form-control" value="' . $property['postal_code'] . '">';
                    echo '<label>Prix</label><input type="number" step="0.01" name="price" class="form-control" value="' . $property['price'] . '">';
                    echo '<label>Surface (m²)</label><input type="number" name="surface_area" class="form-control" value="' . $property['surface_area'] . '">';
                    echo '<label>Pièces</label><input type="number" name="rooms" class="form-control" value="' . $property['rooms'] . '">';
                    echo '<label>Voyageurs max</label><input type="number" name="max_guests" class="form-control" value="' . $property['max_guests'] . '">';
                    echo '<label>Disponible du</label><input type="date" name="available_from" class="form-control" value="' . $property['available_from'] . '">';
                    echo '<label>Disponible au</label><input type="date" name="available_to" class="form-control" value="' . $property['available_to'] . '">';
                    echo '<label>Publié</label><input type="checkbox" name="is_published" value="1"' . ($property['is_published'] ? ' checked' : '') . '>';
                    echo '<label>Propriétaire</label><select name="owner_id" class="form-control">';
                    while ($o = mysqli_fetch_assoc($owners)) {
                        $sel = $o['id'] == $property['owner_id'] ? 'selected' : '';
                        echo "<option value='{$o['id']}' $sel>{$o['last_name']}</option>";
                    }
                    echo '</select>';
                    echo '<button type="submit" class="btn btn-primary mt-2">Enregistrer</button>';
                    echo '</form></div>';
                } elseif ($type === 'review') {
                    $review_sql = "SELECT * FROM reviews WHERE id = ?";
                    $review_stmt = mysqli_prepare($conn, $review_sql);
                    mysqli_stmt_bind_param($review_stmt, "i", $id);
                    mysqli_stmt_execute($review_stmt);
                    $review_result = mysqli_stmt_get_result($review_stmt);
                    $review = mysqli_fetch_assoc($review_result);
                    echo '<div class="container mt-4"><h3>Éditer un avis</h3><form method="post">';
                    echo '<label>Note</label><input type="number" name="rating" class="form-control" min="1" max="5" value="' . $review['rating'] . '">';
                    echo '<label>Commentaire</label><textarea name="comment" class="form-control">' . $review['comment'] . '</textarea>';
                    echo '<button type="submit" class="btn btn-primary mt-2">Enregistrer</button>';
                    echo '</form></div>';
                }
            }

            $tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
            echo '<div class="container mt-4">';
            echo '<ul class="nav nav-tabs">';
            echo '<li class="nav-item"><a class="nav-link' . ($tab=='users'?' active':'') . '" href="?tab=users">Utilisateurs</a></li>';
            echo '<li class="nav-item"><a class="nav-link' . ($tab=='properties'?' active':'') . '" href="?tab=properties">Propriétés</a></li>';
            echo '<li class="nav-item"><a class="nav-link' . ($tab=='bookings'?' active':'') . '" href="?tab=bookings">Réservations</a></li>';
            echo '<li class="nav-item"><a class="nav-link' . ($tab=='reviews'?' active':'') . '" href="?tab=reviews">Avis</a></li>';
            echo '</ul>';

            if ($tab === 'users') {
                $users = mysqli_query($conn, "SELECT id, last_name, email, user_type FROM users ORDER BY id DESC");
                echo '<h4 class="mt-3">Utilisateurs</h4><table class="table table-bordered"><tr><th>ID</th><th>Nom</th><th>Email</th><th>Type</th><th>Action</th></tr>';
                while ($u = mysqli_fetch_assoc($users)) {
                    echo '<tr><td>'.$u['id'].'</td><td>'.$u['last_name'].'</td><td>'.$u['email'].'</td><td>'.$u['user_type'].'</td>';
                    echo '<td>';
                    if ($u['id'] != $user_id) {
                        echo '<a href="?delete='.$u['id'].'&delete_type=user" class="btn btn-danger btn-sm" onclick="return confirm(\'Supprimer cet utilisateur ?\')">Supprimer</a>';
                    } else {
                        echo '<span class="text-muted">Admin</span>';
                    }
                    echo '</td></tr>';
                }
                echo '</table>';
            } elseif ($tab === 'properties') {
                $properties = mysqli_query($conn, "SELECT p.id, p.title, u.last_name as owner FROM properties p JOIN users u ON p.owner_id = u.id ORDER BY p.id DESC");
                echo '<h4 class="mt-3">Propriétés</h4><table class="table table-bordered"><tr><th>ID</th><th>Titre</th><th>Propriétaire</th><th>Action</th></tr>';
                while ($p = mysqli_fetch_assoc($properties)) {
                    echo '<tr><td>'.$p['id'].'</td><td>'.$p['title'].'</td><td>'.$p['owner'].'</td>';
                    echo '<td><a href="?type=property&id='.$p['id'].'" class="btn btn-primary btn-sm">Éditer</a> ';
                    echo '<a href="?delete='.$p['id'].'&delete_type=property" class="btn btn-danger btn-sm" onclick="return confirm(\'Supprimer cette propriété ?\')">Supprimer</a></td></tr>';
                }
                echo '</table>';
            } elseif ($tab === 'bookings') {
                $bookings = mysqli_query($conn, "SELECT b.id, p.title as property, u.last_name as user, b.start_date, b.end_date FROM bookings b JOIN properties p ON b.property_id = p.id JOIN users u ON b.user_id = u.id ORDER BY b.id DESC");
                echo '<h4 class="mt-3">Réservations</h4><table class="table table-bordered"><tr><th>ID</th><th>Propriété</th><th>Utilisateur</th><th>Début</th><th>Fin</th><th>Action</th></tr>';
                while ($b = mysqli_fetch_assoc($bookings)) {
                    echo '<tr><td>'.$b['id'].'</td><td>'.$b['property'].'</td><td>'.$b['user'].'</td><td>'.$b['start_date'].'</td><td>'.$b['end_date'].'</td>';
                    echo '<td><a href="?type=booking&id='.$b['id'].'" class="btn btn-primary btn-sm">Éditer</a> ';
                    echo '<a href="?delete='.$b['id'].'&delete_type=booking" class="btn btn-danger btn-sm" onclick="return confirm(\'Supprimer cette réservation ?\')">Supprimer</a></td></tr>';
                }
                echo '</table>';
            } elseif ($tab === 'reviews') {
                $reviews = mysqli_query($conn, "SELECT r.id, p.title as property, u.last_name as user, r.rating FROM reviews r JOIN bookings b ON r.booking_id = b.id JOIN users u ON b.user_id = u.id JOIN properties p ON b.property_id = p.id ORDER BY r.id DESC");
                echo '<h4 class="mt-3">Avis</h4><table class="table table-bordered"><tr><th>ID</th><th>Propriété</th><th>Utilisateur</th><th>Note</th><th>Action</th></tr>';
                while ($r = mysqli_fetch_assoc($reviews)) {
                    echo '<tr><td>'.$r['id'].'</td><td>'.$r['property'].'</td><td>'.$r['user'].'</td><td>'.$r['rating'].'</td>';
                    echo '<td><a href="?type=review&id='.$r['id'].'" class="btn btn-primary btn-sm">Éditer</a> ';
                    echo '<a href="?delete='.$r['id'].'&delete_type=review" class="btn btn-danger btn-sm" onclick="return confirm(\'Supprimer cet avis ?\')">Supprimer</a></td></tr>';
                }
                echo '</table>';
            }
            ?>
        </div>
    </div>
</div>

<?php
include_once "../includes/footer.php";
?>









