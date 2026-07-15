<?php
// --- Démarrage de la session utilisateur ---
// Aucun output HTML avant session_start() pour éviter les erreurs d'en-tête
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!--
    Ce fichier contient le header HTML commun à toutes les pages du site.
    Il inclut la barre de navigation, les liens CSS principaux, et gère l'affichage du menu utilisateur.
-->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OmnesBnB - Location pour étudiants et personnel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/omnesbnb-equipe-2h/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/omnesbnb-equipe-2h/index.php">OmnesBnB</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/omnesbnb-equipe-2h/index.php">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/omnesbnb-equipe-2h/pages/search.php">Chercher un logement</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/omnesbnb-equipe-2h/pages/publish.php">Publier un logement</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="/omnesbnb-equipe-2h/pages/profile.php"><i class="fas fa-user me-2"></i>Mon profil</a></li>
                            <?php
                            $user_id = $_SESSION['user_id'];
                            $check_properties_sql = "SELECT COUNT(*) as property_count FROM properties WHERE owner_id = ?";
                            if ($stmt = mysqli_prepare($conn, $check_properties_sql)) {
                                mysqli_stmt_bind_param($stmt, "i", $user_id);

                                if (mysqli_stmt_execute($stmt)) {
                                    $result = mysqli_stmt_get_result($stmt);
                                    $property_count = mysqli_fetch_assoc($result)['property_count'];

                                    if ($property_count > 0) {
                                        echo '<li><a class="dropdown-item" href="/omnesbnb-equipe-2h/pages/my-rentals.php"><i class="fas fa-home me-2"></i>Mes locations</a></li>';
                                    }
                                }
                                mysqli_stmt_close($stmt);
                            }
                            ?>                                <li><a class="dropdown-item" href="/omnesbnb-equipe-2h/pages/reservation.php"><i class="fas fa-calendar-check me-2"></i>Mes réservations</a></li>
                            <li><a class="dropdown-item" href="/omnesbnb-equipe-2h/pages/favorites.php"><i class="fas fa-heart me-2"></i>Mes favoris</a></li>

                            <?php
                            $check_admin_sql = "SELECT user_type FROM users WHERE id = ? AND (email = 'admin@ece.fr' OR user_type = 'admin')";
                            if ($admin_stmt = mysqli_prepare($conn, $check_admin_sql)) {
                                mysqli_stmt_bind_param($admin_stmt, "i", $user_id);
                                if (mysqli_stmt_execute($admin_stmt)) {
                                    $admin_result = mysqli_stmt_get_result($admin_stmt);
                                    if (mysqli_num_rows($admin_result) > 0) {
                                        echo '<li><a class="dropdown-item" href="/omnesbnb-equipe-2h/pages/admin.php"><i class="fas fa-cog me-2"></i><strong>Administration</strong></a></li>';
                                    }
                                }
                                mysqli_stmt_close($admin_stmt);
                            }
                            ?>

                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/omnesbnb-equipe-2h/includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/omnesbnb-equipe-2h/pages/login.php">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/omnesbnb-equipe-2h/pages/register.php">Inscription</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-4"><?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
