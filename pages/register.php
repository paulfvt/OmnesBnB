<?php
// --- Démarrage de la session utilisateur ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Redirection si déjà connecté ---
if (isset($_SESSION["user_id"])) {
    header("location: ../index.php");
    exit;
}

// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";

// --- Initialisation des variables ---
$email = $password = $confirm_password = $first_name = $last_name = $phone_number = "";
$email_err = $password_err = $confirm_password_err = $first_name_err = $last_name_err = $phone_number_err = $terms_err = "";

// --- Traitement du formulaire lors de la soumission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Validation de l'email ---
    if (empty(trim($_POST["email"]))) {
        $email_err = "Veuillez entrer un email.";
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Format d'email invalide.";
        } elseif (!preg_match("/@(omnesintervenant\\.com|ece\\.fr|edu\\.ece\\.fr)$/", $email)) {
            $email_err = "Seules les adresses email d'Omnes sont autorisées.";
        } else {
            $sql = "SELECT id FROM users WHERE email = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $param_email);
                $param_email = $email;
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        $email_err = "Cette adresse email est déjà utilisée.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    // --- Validation du prénom ---
    if (empty(trim($_POST["first_name"]))) {
        $first_name_err = "Veuillez entrer votre prénom.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    // --- Validation du nom ---
    if (empty(trim($_POST["last_name"]))) {
        $last_name_err = "Veuillez entrer votre nom.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    // --- Validation du mot de passe ---
    if (empty(trim($_POST["password"]))) {
        $password_err = "Veuillez entrer un mot de passe.";
    } elseif (strlen(trim($_POST["password"])) < 8) {
        $password_err = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $password = trim($_POST["password"]);
    }

    // --- Validation de la confirmation du mot de passe ---
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Veuillez confirmer le mot de passe.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Les mots de passe ne correspondent pas.";
        }
    }

    // --- Validation du numéro de téléphone ---
    if (empty(trim($_POST["phone_number"]))) {
        $phone_number_err = "Veuillez entrer votre numéro de téléphone.";
    } elseif (!preg_match("/^[0-9+\s()-]{8,20}$/", trim($_POST["phone_number"]))) {
        $phone_number_err = "Format de numéro de téléphone invalide.";
    } else {
        $phone_number = trim($_POST["phone_number"]);
    }

    // --- Validation des conditions d'utilisation ---
    if (!isset($_POST['terms'])) {
        $terms_err = "Vous devez accepter les conditions pour vous inscrire.";
    }

    // --- Insertion dans la base de données si pas d'erreurs ---
    if (empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($first_name_err) && empty($last_name_err) && empty($phone_number_err) && empty($terms_err)) {
        $user_type = preg_match("/@(ece\\.fr|omnesintervenant\\.com)$/", $email) ? 'staff' : 'student';
        $default_profile_image = "assets/profile_images/default-profile.jpg";
        $sql = "INSERT INTO users (email, password, first_name, last_name, phone_number, user_type, profile_image, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, FALSE)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssss", $param_email, $param_password, $param_first_name, $param_last_name, $param_phone_number, $param_user_type, $param_profile_image);
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_phone_number = $phone_number;
            $param_user_type = $user_type;
            $param_profile_image = $default_profile_image;
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Inscription réussie. Vous pouvez maintenant vous connecter.";
                $_SESSION['message_type'] = "success";
                header("location: login.php");
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }

    // --- Fermeture de la connexion à la base de données ---
    mysqli_close($conn);
}

// --- Inclusion de l'en-tête ---
include "../includes/header.php";
echo '<link rel="stylesheet" href="../css/register.css">';
?>


<div class="container">
    <div class="form-card">
        <h2>Inscription</h2>
        <p>Créez votre compte OmnesBnB avec une adresse Omnes</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label>Prénom :
                <input type="text" name="first_name" value="<?php echo $first_name; ?>">
                <span class="error"><?php echo $first_name_err; ?></span>
            </label>

            <label>Nom :
                <input type="text" name="last_name" value="<?php echo $last_name; ?>">
                <span class="error"><?php echo $last_name_err; ?></span>
            </label>

            <label>Email :
                <input type="email" name="email" value="<?php echo $email; ?>">
                <span class="error"><?php echo $email_err; ?></span>
            </label>

            <label>Mot de passe :
                <input type="password" name="password">
                <span class="error"><?php echo $password_err; ?></span>
            </label>

            <label>Confirmer le mot de passe :
                <input type="password" name="confirm_password">
                <span class="error"><?php echo $confirm_password_err; ?></span>
            </label>

            <label>Numéro de téléphone :
                <input type="text" name="phone_number" value="<?php echo $phone_number; ?>">
                <span class="error"><?php echo $phone_number_err; ?></span>
            </label>

            <label class="checkbox">
                <input type="checkbox" name="terms" value="1">
                J'accepte les <a href="#">conditions générales d'utilisation</a>
                <span class="error"><?php echo $terms_err; ?></span>
            </label>

            <button type="submit">S'inscrire</button>

            <p class="link">Vous avez déjà un compte ? <a href="login.php">Connectez-vous ici</a></p>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
