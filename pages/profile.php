<?php
// --- Démarrage de la session utilisateur ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Vérification de l'authentification ---
if (!isset($_SESSION["user_id"])) {
    $_SESSION["message"] = "Vous devez vous connecter pour accéder à cette page.";
    $_SESSION["message_type"] = "warning";
    header("location: login.php");
    exit;
}

// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";

// --- Inclusion du header (barre de navigation, etc.) ---
include "../includes/header.php";

// --- Ajout du CSS spécifique à la page de profil ---
echo '<link rel="stylesheet" href="../css/profile.css">';

$user_id = $_SESSION["user_id"];
$email = $first_name = $last_name = $phone = "";

$profile_image = "assets/profile_images/default.jpg";

$sql = "SELECT email, first_name, last_name, phone_number, profile_image FROM users WHERE id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $param_id);

    $param_id = $user_id;

    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_store_result($stmt);

        if(mysqli_stmt_num_rows($stmt) == 1){
            mysqli_stmt_bind_result($stmt, $email, $first_name, $last_name, $phone, $user_profile_image);

            if(mysqli_stmt_fetch($stmt)){
                if(!empty($user_profile_image)) {
                    $profile_image = $user_profile_image;
                }
            }        } else{
            $_SESSION["message"] = "Erreur lors de la récupération des informations utilisateur.";
            $_SESSION["message_type"] = "danger";
            echo "<script>window.location.href='../index.php';</script>";
            exit;
        }
    } else{
        echo "Oops! Une erreur est survenue. Veuillez réessayer plus tard.";
    }

    mysqli_stmt_close($stmt);
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_err = $first_name_err = $last_name_err = $phone_err = $current_password_err = $new_password_err = $confirm_password_err = "";

    if (empty(trim($_POST["email"]))) {
        $email_err = "Veuillez entrer un email.";
    } else {
        $email = trim($_POST["email"]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Format d'email invalide.";
        } else {
            if (!preg_match("/@(omnesintervenant\.com|ece\.fr|edu\.ece\.fr)$/", $email)) {
                $email_err = "Seules les adresses email d'Omnes sont autorisées.";
            } else {
                if ($email != $_SESSION["email"]) {
                    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";

                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "si", $param_email, $param_id);
                        $param_email = $email;
                        $param_id = $user_id;

                        if (mysqli_stmt_execute($stmt)) {
                            mysqli_stmt_store_result($stmt);

                            if (mysqli_stmt_num_rows($stmt) == 1) {
                                $email_err = "Cette adresse email est déjà utilisée.";
                            }
                        } else {
                            echo "Oops! Une erreur est survenue. Veuillez réessayer plus tard.";
                        }

                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }

    if (empty(trim($_POST["first_name"]))) {
        $first_name_err = "Veuillez entrer votre prénom.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    if (empty(trim($_POST["last_name"]))) {
        $last_name_err = "Veuillez entrer votre nom.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    if (!empty(trim($_POST["phone"]))) {
        $phone = trim($_POST["phone"]);

        if (!preg_match("/^[0-9+\s()-]{8,20}$/", $phone)) {
            $phone_err = "Format de numéro de téléphone invalide.";
        }
    }
    if (isset($_POST["current_password"]) && !empty(trim($_POST["current_password"]))) {
        $current_password = trim($_POST["current_password"]);

        $sql = "SELECT password FROM users WHERE id = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $user_id;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $hashed_password);

                    if (mysqli_stmt_fetch($stmt)) {
                        if (!password_verify($current_password, $hashed_password)) {
                            $current_password_err = "Le mot de passe actuel est incorrect.";
                        }
                    }
                }
            }

            mysqli_stmt_close($stmt);
        }

        if (empty(trim($_POST["new_password"]))) {
            $new_password_err = "Veuillez entrer le nouveau mot de passe.";
        } elseif (strlen(trim($_POST["new_password"])) < 8) {
            $new_password_err = "Le mot de passe doit contenir au moins 8 caractères.";
        } else {
            $new_password = trim($_POST["new_password"]);
        }

        if (empty(trim($_POST["confirm_password"]))) {
            $confirm_password_err = "Veuillez confirmer le nouveau mot de passe.";
        } else {
            $confirm_password = trim($_POST["confirm_password"]);
            if (empty($new_password_err) && ($new_password != $confirm_password)) {
                $confirm_password_err = "Les mots de passe ne correspondent pas.";
            }
        }
    }

    $upload_image = false;
    $new_profile_image = "";

    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
        $allowed_types = ["image/jpeg", "image/png", "image/gif"];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES["profile_image"]["type"], $allowed_types) && $_FILES["profile_image"]["size"] <= $max_size) {
            if (!file_exists("../assets/profile_images")) {
                mkdir("../assets/profile_images", 0777, true);
            }
            $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid("profile_") . "." . $file_extension;
            $upload_path = "../assets/profile_images/" . $new_filename;            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $upload_path)) {
                $upload_image = true;
                $new_profile_image = "assets/profile_images/" . $new_filename;
            }
        }
    }

    if (empty($email_err) && empty($first_name_err) && empty($last_name_err) && empty($phone_err) &&
        empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {

        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            $sql = "UPDATE users SET email = ?, first_name = ?, last_name = ?, phone_number = ?, password = ?" .
                ($upload_image ? ", profile_image = ?" : "") . " WHERE id = ?";
        } else {
            $sql = "UPDATE users SET email = ?, first_name = ?, last_name = ?, phone_number = ?" .
                ($upload_image ? ", profile_image = ?" : "") . " WHERE id = ?";
        }
        if($stmt = mysqli_prepare($conn, $sql)){
            if(!empty($current_password) && !empty($new_password) && !empty($confirm_password)){
                if($upload_image){
                    mysqli_stmt_bind_param(
                        $stmt,
                        "sssssii",
                        $param_email,
                        $param_first_name,
                        $param_last_name,
                        $param_phone,
                        $param_password,
                        $param_profile_image,
                        $param_id
                    );
                    $param_profile_image = $new_profile_image;
                } else {
                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssssi",
                        $param_email,
                        $param_first_name,
                        $param_last_name,
                        $param_phone,
                        $param_password,
                        $param_id
                    );
                }
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            } else {
                if($upload_image){
                    mysqli_stmt_bind_param(
                        $stmt,
                        "sssssii",
                        $param_email,
                        $param_first_name,
                        $param_last_name,
                        $param_phone,
                        $param_profile_image,
                        $param_id
                    );
                    $param_profile_image = $new_profile_image;
                } else {                    mysqli_stmt_bind_param(
                    $stmt,
                    "ssssi",
                    $param_email,
                    $param_first_name,
                    $param_last_name,
                    $param_phone,
                    $param_id
                );
                }
            }

            $param_email = $email;
            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_phone = $phone;
            $param_id = $user_id;

            if(mysqli_stmt_execute($stmt)){
                $_SESSION["email"] = $email;
                $_SESSION["first_name"] = $first_name;
                $_SESSION["last_name"] = $last_name;

                $_SESSION["message"] = "Votre profil a été mis à jour avec succès.";
                $_SESSION["message_type"] = "success";

                echo "<script>window.location.href='profile.php';</script>";
                exit;
            } else {
                echo "Oops! Une erreur est survenue. Veuillez réessayer plus tard.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($conn);
}
?>

<div class="container py-4">
    <div class="row">        <div class="col-md-4 mb-4">            <div class="profile-header text-center">
                <div class="profile-image-container mb-4">
                    <img src="../<?php echo htmlspecialchars($profile_image); ?>" alt="Photo de profil" class="profile-avatar">
                </div>
                <h4><?php echo htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($email); ?></p>                <div class="list-group mt-4">
                    <a href="#profile-info" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-user me-2"></i> Informations personnelles
                    </a>
                    <a href="#change-password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-lock me-2"></i> Changer de mot de passe
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="form-card">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="profile-info">
                        <h3 class="mb-4">Informations personnelles</h3>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">                            <div class="mb-4">
                                <label for="profile_image" class="form-label">Photo de profil</label>
                                <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif" onchange="previewImage(this);">
                                <div class="form-text">Format JPG, PNG ou GIF. Max 5MB.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" name="first_name" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($first_name); ?>" id="first_name" placeholder="Prénom">
                                        <label for="first_name">Prénom</label>
                                        <div class="invalid-feedback">
                                            <?php echo $first_name_err ?? ''; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" name="last_name" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($last_name); ?>" id="last_name" placeholder="Nom">
                                        <label for="last_name">Nom</label>
                                        <div class="invalid-feedback">
                                            <?php echo $last_name_err ?? ''; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" id="email" placeholder="nom@example.com">
                                <label for="email">Adresse email</label>
                                <div class="invalid-feedback">
                                    <?php echo $email_err ?? ''; ?>
                                </div>
                                <div class="form-text">Utilisez une adresse email @omnesintervenant.com, @ece.fr ou @edu.ece.fr</div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="tel" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($phone); ?>" id="phone" placeholder="Numéro de téléphone">
                                <label for="phone">Numéro de téléphone</label>
                                <div class="invalid-feedback">
                                    <?php echo $phone_err ?? ''; ?>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Mettre à jour mon profil</button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="change-password">
                        <h3 class="mb-4">Changer de mot de passe</h3>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-floating mb-3">
                                <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" id="current_password" placeholder="Mot de passe actuel">
                                <label for="current_password">Mot de passe actuel</label>
                                <div class="invalid-feedback">
                                    <?php echo $current_password_err ?? ''; ?>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" id="password" placeholder="Nouveau mot de passe">
                                <label for="password">Nouveau mot de passe</label>
                                <div class="invalid-feedback">
                                    <?php echo $new_password_err ?? ''; ?>
                                </div>

                                <!-- Password strength meter -->
                                <div class="mt-2">
                                    <div class="progress">
                                        <div id="password_strength" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirm_password" placeholder="Confirmez le mot de passe">
                                <label for="confirm_password">Confirmez le nouveau mot de passe</label>
                                <div class="invalid-feedback">
                                    <?php echo $confirm_password_err ?? ''; ?>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Mettre à jour le mot de passe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/profile.js"></script>
<?php include "../includes/footer.php"; ?>


