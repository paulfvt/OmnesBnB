<?php
// --- Démarrage de la session utilisateur ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Vérification de l'authentification ---
if (!isset($_SESSION["user_id"])) {
    $_SESSION["message"] = "Vous devez vous connecter pour publier un logement.";
    $_SESSION["message_type"] = "warning";
    header("location: login.php");
    exit;
}

// --- Connexion à la base de données ---
require_once "../includes/db_connection.php";

// --- Inclusion du header (barre de navigation, etc.) ---
include "../includes/header.php";

$title = $location = $description = $price = $start_date = $end_date = $max_guests = $type = "";
$title_err = $location_err = $description_err = $price_err = $start_date_err = $end_date_err = $max_guests_err = $type_err = $image_err = "";
$address_err = $city_err = $postal_code_err = "";

// --- Traitement du formulaire de publication ---
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // --- Validation du titre ---
    if(empty(trim($_POST["title"]))){
        $title_err = "Veuillez entrer un titre pour votre logement.";
    } elseif(strlen(trim($_POST["title"])) > 100){
        $title_err = "Le titre ne doit pas dépasser 100 caractères.";
    } else{
        $title = trim($_POST["title"]);
    }

    // --- Validation de l'emplacement ---
    if(empty(trim($_POST["location"]))){
        $location_err = "Veuillez entrer l'emplacement du logement.";
    } else{
        $location = trim($_POST["location"]);
    }

    // --- Validation de la description ---
    if(empty(trim($_POST["description"]))){
        $description_err = "Veuillez entrer une description du logement.";
    } elseif(strlen(trim($_POST["description"])) < 30){
        $description_err = "La description doit contenir au moins 30 caractères.";
    } else{
        $description = trim($_POST["description"]);
    }

    // --- Validation du prix ---
    if(empty(trim($_POST["price"]))){
        $price_err = "Veuillez entrer un prix par nuit.";
    } elseif(!is_numeric(trim($_POST["price"])) || floatval(trim($_POST["price"])) <= 0){
        $price_err = "Veuillez entrer un prix valide.";
    } else{
        $price = trim($_POST["price"]);
    }

    // --- Validation de la date de début de disponibilité ---
    if(empty(trim($_POST["start_date"]))){
        $start_date_err = "Veuillez entrer une date de disponibilité.";
    } else{
        $start_date = trim($_POST["start_date"]);
        $current_date = date('Y-m-d');

        if($start_date < $current_date){
            $start_date_err = "La date de disponibilité ne peut pas être dans le passé.";
        }
    }

    // --- Validation de la date de fin de disponibilité ---
    if(empty(trim($_POST["end_date"]))){
        $end_date_err = "Veuillez entrer une date de fin de disponibilité.";
    } else{
        $end_date = trim($_POST["end_date"]);

        if(!empty($start_date) && $end_date < $start_date){
            $end_date_err = "La date de fin ne peut pas être antérieure à la date de début.";
        }
    }

    // --- Validation du nombre maximal de personnes ---
    if(empty(trim($_POST["max_guests"]))){
        $max_guests_err = "Veuillez indiquer le nombre maximal de personnes.";
    } elseif(!ctype_digit(trim($_POST["max_guests"])) || intval(trim($_POST["max_guests"])) <= 0){
        $max_guests_err = "Veuillez entrer un nombre valide.";
    } else{
        $max_guests = trim($_POST["max_guests"]);
    }

    // --- Validation du type de logement ---
    if(empty($_POST["type"])){
        $type_err = "Veuillez sélectionner un type de logement.";
    } else{
        $type = $_POST["type"];
    }

    // --- Validation de l'adresse ---
    if(empty(trim($_POST["address"]))) {
        $address_err = "Veuillez entrer l'adresse complète du logement.";
    } else {
        $address = trim($_POST["address"]);
    }
    // --- Validation de la ville ---
    if(empty(trim($_POST["city"]))) {
        $city_err = "Veuillez entrer la ville du logement.";
    } else {
        $city = trim($_POST["city"]);
    }
    // --- Validation du code postal ---
    if(empty(trim($_POST["postal_code"]))) {
        $postal_code_err = "Veuillez entrer le code postal du logement.";
    } elseif(!preg_match('/^\d{5}$/', trim($_POST["postal_code"]))) {
        $postal_code_err = "Le code postal doit comporter 5 chiffres.";
    } else {
        $postal_code = trim($_POST["postal_code"]);
    }

    // --- Gestion des uploads d'images ---
    $upload_dir = "../assets/property_images/";
    $uploaded_images = [];
    $image_errors = [];

    if(!file_exists($upload_dir)){
        mkdir($upload_dir, 0777, true);
    }

    $image_uploaded = false;

    if(isset($_FILES["property_images"])){
        $file_count = count($_FILES["property_images"]["name"]);

        for($i = 0; $i < $file_count; $i++){
            if($_FILES["property_images"]["error"][$i] == 0){
                $image_uploaded = true;
                $file_name = $_FILES["property_images"]["name"][$i];
                $file_tmp = $_FILES["property_images"]["tmp_name"][$i];
                $file_type = $_FILES["property_images"]["type"][$i];
                $file_size = $_FILES["property_images"]["size"][$i];

                $allowed_types = ["image/jpeg", "image/png", "image/gif"];
                if(!in_array($file_type, $allowed_types)){
                    $image_errors[] = "Le fichier {$file_name} n'est pas au format autorisé (JPG, PNG, GIF).";
                    continue;
                }

                $max_size = 5 * 1024 * 1024;
                if($file_size > $max_size){
                    $image_errors[] = "Le fichier {$file_name} dépasse la taille maximale autorisée (5MB).";
                    continue;
                }
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = uniqid("property_") . "." . $file_ext;
                $upload_path = $upload_dir . $new_filename;

                if(move_uploaded_file($file_tmp, $upload_path)){
                    error_log("Image uploadée avec succès: " . $upload_path);
                    $uploaded_images[] = $upload_path;
                } else {
                    $image_errors[] = "Une erreur s'est produite lors du téléchargement du fichier {$file_name}.";
                    error_log("Erreur upload image: " . error_get_last()['message']);
                }
            }
        }
    }

    if(!$image_uploaded){
        $image_err = "Veuillez télécharger au moins une image de votre logement.";
    } elseif(!empty($image_errors)){
        $image_err = implode("<br>", $image_errors);
    }

    // --- Insertion des données dans la base si aucune erreur ---
    if(empty($title_err) && empty($location_err) && empty($description_err) && empty($price_err) &&
        empty($start_date_err) && empty($end_date_err) && empty($max_guests_err) && empty($type_err) && empty($image_err)
        && empty($address_err) && empty($city_err) && empty($postal_code_err)){
        // --- Gestion spécifique pour Paris ---
        if(preg_match('/Paris (\d+)(ème|e|er)/i', $location, $matches)) {
            $city = "Paris";
            $postal_code = "750" . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }

        // --- Mapping des types de propriété ---
        $property_type_map = [
            'rental' => 'Location complète',
            'colocation' => 'Colocation',
            'notice' => 'Je libère mon logement'
        ];
        $property_type = isset($property_type_map[$type]) ? $property_type_map[$type] : $type;
        $surface_area = isset($_POST["surface_area"]) ? intval($_POST["surface_area"]) : 25;
        $rooms = isset($_POST["rooms"]) ? intval($_POST["rooms"]) : 1;

        // --- Récupération des équipements ---
        if(isset($_POST["amenities"]) && is_array($_POST["amenities"])) {
            $amenities = $_POST["amenities"];
        } else {
            $amenities = [
                'Wi-Fi',
                'Cuisine équipée',
                'Salle de bain privée'
            ];
        }
        $main_image = "";
        if(!empty($uploaded_images)) {
            $main_image = $uploaded_images[0];
            $main_image = str_replace("../assets/", "assets/", $main_image);
        }

        // --- Requête d'insertion principale ---
        $sql = "INSERT INTO properties (owner_id, title, description, property_type, location, address, city, postal_code, 
                price, surface_area, rooms, max_guests, amenities, main_image, available_from, available_to) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "isssssssdiiiisss",
                $param_owner_id, $param_title, $param_description, $param_property_type,
                $param_location, $param_address, $param_city, $param_postal_code,
                $param_price, $param_surface_area, $param_rooms, $param_max_guests,
                $param_amenities, $param_main_image, $param_available_from, $param_available_to);

            $param_owner_id = $_SESSION["user_id"];
            $param_title = $title;
            $param_description = $description;
            $param_property_type = $property_type;
            $param_location = $location;
            $param_address = $address;
            $param_city = $city;
            $param_postal_code = $postal_code;
            $param_price = floatval($price);
            $param_surface_area = $surface_area;
            $param_rooms = $rooms;
            $param_max_guests = intval($max_guests);
            $param_amenities = implode(",", $amenities);
            $param_main_image = $main_image;
            $param_available_from = $start_date;
            $param_available_to = $end_date;

            // --- Exécution de la requête d'insertion ---
            if(mysqli_stmt_execute($stmt)){
                $property_id = mysqli_insert_id($conn);
                // --- Insertion des images supplémentaires ---
                if(count($uploaded_images) > 1) {
                    $images_sql = "INSERT INTO property_images (property_id, image_path) VALUES (?, ?)";
                    $images_stmt = mysqli_prepare($conn, $images_sql);

                    error_log("Nombre total d'images: " . count($uploaded_images));

                    for($i = 1; $i < count($uploaded_images); $i++) {
                        $image_path = str_replace("../assets/", "assets/", $uploaded_images[$i]);
                        error_log("Insertion d'image supplémentaire: " . $image_path);
                        mysqli_stmt_bind_param($images_stmt, "is", $property_id, $image_path);
                        mysqli_stmt_execute($images_stmt);

                        if(mysqli_stmt_error($images_stmt)) {
                            error_log("Erreur SQL lors de l'insertion d'image: " . mysqli_stmt_error($images_stmt));
                        }
                    }

                    mysqli_stmt_close($images_stmt);
                }

                // --- Enregistrement de l'activité de l'utilisateur ---
                $activity_sql = "INSERT INTO user_activity (user_id, activity_type, property_id) VALUES (?, ?, ?)";
                $activity_type = "property_created";

                if($activity_stmt = mysqli_prepare($conn, $activity_sql)){
                    mysqli_stmt_bind_param($activity_stmt, "isi", $_SESSION["user_id"], $activity_type, $property_id);
                    mysqli_stmt_execute($activity_stmt);
                    mysqli_stmt_close($activity_stmt);
                }                $_SESSION["message"] = "Votre logement a été publié avec succès!";
                $_SESSION["message_type"] = "success";
                echo "<script>window.location.href='my-rentals.php';</script>";
                exit;
            } else {
                $_SESSION["message"] = "Une erreur s'est produite lors de la publication du logement.";
                $_SESSION["message_type"] = "danger";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<link rel="stylesheet" href="../css/publish.css">

    <div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card">
                <h2 class="text-center mb-4">Publier un logement</h2>
                <p class="text-center mb-4">Partagez votre logement avec d'autres membres de la communauté Omnes</p>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <h5 class="mb-3">Informations de base</h5>

                    <div class="form-floating mb-3">
                        <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>" id="title" placeholder="Titre de l'annonce" maxlength="100" required>
                        <label for="title">Titre de l'annonce*</label>
                        <div class="invalid-feedback">
                            <?php echo $title_err; ?>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" name="location" class="form-control <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($location); ?>" id="location" placeholder="Quartier (ex: Paris 15ème)" required>
                        <label for="location">Quartier*</label>
                        <div class="invalid-feedback">
                            <?php echo $location_err; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" id="address" placeholder="Adresse complète" required>
                                <label for="address">Adresse complète*</label>
                                <div class="invalid-feedback">
                                    <?php echo $address_err; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" name="postal_code" class="form-control <?php echo (!empty($postal_code_err)) ? 'is-invalid' : ''; ?>" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>" id="postal_code" placeholder="Code postal" required pattern="\d{5}">
                                <label for="postal_code">Code postal*</label>
                                <div class="invalid-feedback">
                                    <?php echo $postal_code_err; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" name="city" class="form-control <?php echo (!empty($city_err)) ? 'is-invalid' : ''; ?>" value="Paris" id="city" placeholder="Ville" required>
                        <label for="city">Ville*</label>
                        <div class="invalid-feedback">
                            <?php echo $city_err; ?>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <textarea class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" name="description" id="description" placeholder="Description" style="height: 150px" required minlength="30"><?php echo htmlspecialchars($description); ?></textarea>
                        <label for="description">Description*</label>
                        <div class="invalid-feedback">
                            <?php echo $description_err; ?>
                        </div>
                        <div class="form-text">Décrivez votre logement et les modalités de location (minimum 30 caractères).</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" name="price" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($price); ?>" id="price" placeholder="Prix par nuit" min="1" step="0.01" required>
                                <label for="price">Prix par nuit (€)*</label>
                                <div class="invalid-feedback">
                                    <?php echo $price_err; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" name="max_guests" class="form-control <?php echo (!empty($max_guests_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($max_guests); ?>" id="max_guests" placeholder="Nombre max. de personnes" min="1" required>
                                <label for="max_guests">Nombre max. de personnes*</label>
                                <div class="invalid-feedback">
                                    <?php echo $max_guests_err; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" name="surface_area" class="form-control" value="<?php echo isset($_POST['surface_area']) ? htmlspecialchars($_POST['surface_area']) : '25'; ?>" id="surface_area" placeholder="Surface en m²" min="1" required>
                                <label for="surface_area">Surface en m²*</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" name="rooms" class="form-control" value="<?php echo isset($_POST['rooms']) ? htmlspecialchars($_POST['rooms']) : '1'; ?>" id="rooms" placeholder="Nombre de pièces" min="1" required>
                                <label for="rooms">Nombre de pièces*</label>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4">Disponibilité</h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="date" name="start_date" class="form-control <?php echo (!empty($start_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($start_date); ?>" id="start_date" required>
                                <label for="start_date">Disponible à partir de*</label>
                                <div class="invalid-feedback">
                                    <?php echo $start_date_err; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="date" name="end_date" class="form-control <?php echo (!empty($end_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($end_date); ?>" id="end_date" required>
                                <label for="end_date">Disponible jusqu'à*</label>
                                <div class="invalid-feedback">
                                    <?php echo $end_date_err; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4">Type de logement*</h5>

                    <div class="mb-3 <?php echo (!empty($type_err)) ? 'is-invalid' : ''; ?>">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_rental" value="rental" <?php echo (isset($_POST['type']) && $_POST['type'] == "rental") ? "checked" : ""; ?> required>
                            <label class="form-check-label" for="type_rental">
                                <strong>Location complète</strong>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_colocation" value="colocation" <?php echo (isset($_POST['type']) && $_POST['type'] == "colocation") ? "checked" : ""; ?> required>
                            <label class="form-check-label" for="type_colocation">
                                <strong>Colocation</strong>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_notice" value="notice" <?php echo (isset($_POST['type']) && $_POST['type'] == "notice") ? "checked" : ""; ?> required>
                            <label class="form-check-label" for="type_notice">
                                <strong>Je libère mon logement</strong>
                            </label>
                        </div>
                    </div>                    <div class="invalid-feedback <?php echo (!empty($type_err)) ? 'd-block' : ''; ?>">
                        <?php echo $type_err ?: 'Veuillez sélectionner un type de logement.'; // Provide default message if $type_err is empty but field is invalid ?>
                    </div>

                    <h5 class="mb-3 mt-4">Photos*</h5>
                    <p class="text-muted small mb-3">Ajoutez des photos attrayantes de votre logement (première photo = photo principale)</p>                    <div class="image-upload-wrapper mb-3 <?php echo (!empty($image_err)) ? 'is-invalid' : ''; ?>">
                        <div class="image-upload-container" id="dropzone">
                            <i class="fas fa-images fa-2x mb-2"></i>
                            <p class="mb-1">Glissez et déposez vos images ici</p>
                            <p class="text-muted small">ou</p>
                            <label for="property_images" class="btn btn-outline-primary btn-sm">
                                Parcourir les fichiers
                            </label>
                            <input class="image-upload-input" type="file" id="property_images" name="property_images[]" accept="image/jpeg, image/png, image/gif" multiple hidden required>
                        </div>
                        <div class="form-text mt-2">Formats acceptés : JPG, PNG, GIF. Max 5MB par image. Au moins une image requise.</div>
                        <div class="invalid-feedback <?php echo (!empty($image_err)) ? 'd-block' : ''; ?>">
                            <?php echo $image_err ?: 'Veuillez télécharger au moins une image.'; // Provide default message ?>
                        </div>
                    </div>


                    <div class="image-previews-container mb-4">
                        <div id="image_preview" class="row g-3">
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Publier mon logement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../js/publish.js"></script>

<?php include "../includes/footer.php"; ?>

