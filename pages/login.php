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

// --- Inclusion du header (barre de navigation, etc.) ---
include "../includes/header.php";

// --- Ajout du CSS spécifique à la page de connexion ---
echo '<link rel="stylesheet" href="../css/login.css">';

$email = $password = "";
$email_err = $password_err = $login_err = "";

// --- Traitement du formulaire de connexion ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Validation de l'email ---
    if (empty(trim($_POST["email"]))) {
        $email_err = "Veuillez entrer votre email.";
    } else {
        $email = trim($_POST["email"]);
        // --- Vérification du domaine de l'email ---
        if (!preg_match("/@(omnesintervenant\.com|ece\.fr|edu\.ece\.fr)$/", $email)) {
            $email_err = "Seules les adresses email d'Omnes sont autorisées.";
        }
    }

    // --- Validation du mot de passe ---
    if (empty(trim($_POST["password"]))) {
        $password_err = "Veuillez entrer votre mot de passe.";
    } else {
        $password = trim($_POST["password"]);
    }

    // --- Authentification de l'utilisateur ---
    if (empty($email_err) && empty($password_err)) {
        $sql = "SELECT id, email, password, first_name, last_name, phone_number, profile_image, user_type, is_verified FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                // --- Vérification de l'existence de l'utilisateur ---
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $email, $hashed_password, $first_name, $last_name, $phone_number, $profile_image, $user_type, $is_verified);
                    if (mysqli_stmt_fetch($stmt)) {
                        // --- Vérification du mot de passe ---
                        if (password_verify($password, $hashed_password)) {
                            // --- Création des variables de session ---
                            $_SESSION["user_id"] = $id;
                            $_SESSION["email"] = $email;
                            $_SESSION["first_name"] = $first_name;
                            $_SESSION["last_name"] = $last_name;
                            $_SESSION["phone_number"] = $phone_number;
                            $_SESSION["profile_image"] = $profile_image;
                            $_SESSION["user_type"] = $user_type;
                            $_SESSION["is_verified"] = $is_verified;
                            // Utiliser la redirection JavaScript au lieu de header()
                            echo "<script>window.location.href='../index.php';</script>";
                            exit;
                        } else {
                            $login_err = "Email ou mot de passe incorrect.";
                        }
                    }
                } else {
                    $login_err = "Email ou mot de passe incorrect.";
                }
            } else {
                echo "Une erreur est survenue. Veuillez réessayer.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<div class="login-container">
    <div class="login-box">
        <h2>Connexion</h2>
        <p>Connectez-vous à votre compte OmnesBnB</p>

        <?php if (!empty($login_err)) : ?>
            <div class="error-msg"><?php echo $login_err; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="email">Adresse email</label>
            <input type="email" name="email" id="email" value="<?php echo $email; ?>" class="<?php echo !empty($email_err) ? 'input-error' : ''; ?>">
            <span class="error-msg"><?php echo $email_err; ?></span>

            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" class="<?php echo !empty($password_err) ? 'input-error' : ''; ?>">
            <span class="error-msg"><?php echo $password_err; ?></span>

            <button type="submit">Connexion</button>
        </form>
        <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous</a></p>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
