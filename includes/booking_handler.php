<?php
// --- Connexion à la base de données ---
require_once "db_connection.php";

// --- Démarrage de la session utilisateur ---
session_start();

// --- Vérification de l'authentification ---
if (!isset($_SESSION["user_id"])) {
    $response = array(
        "success" => false,
        "message" => "Vous devez être connecté pour effectuer cette action."
    );
    echo json_encode($response);
    exit;
}

// --- Vérification de la méthode HTTP ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response = array(
        "success" => false,
        "message" => "Méthode non autorisée."
    );
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION["user_id"];

$booking_id = isset($_POST["booking_id"]) ? intval($_POST["booking_id"]) : 0;
$action = isset($_POST["action"]) ? $_POST["action"] : "";

// --- Validation des paramètres reçus ---
if ($booking_id <= 0 || empty($action)) {
    $response = array(
        "success" => false,
        "message" => "Paramètres manquants ou invalides."
    );
    echo json_encode($response);
    exit;
}

// --- Vérification de la propriété de la réservation ---
$check_ownership_sql = "SELECT b.*, p.owner_id 
                        FROM bookings b
                        JOIN properties p ON b.property_id = p.id
                        WHERE b.id = ?";

if ($stmt = mysqli_prepare($conn, $check_ownership_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $booking_id);

    // --- Exécution de la requête préparée ---
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        // --- Vérification de la réservation et de son propriétaire ---
        if ($booking = mysqli_fetch_assoc($result)) {
            // --- Vérification des droits de l'utilisateur sur la réservation ---
            if ($booking["owner_id"] != $user_id) {
                $response = array(
                    "success" => false,
                    "message" => "Vous n'avez pas les droits pour modifier cette réservation."
                );
                echo json_encode($response);
                exit;
            }

            // --- Traitement de l'action demandée (confirmation ou annulation) ---
            switch ($action) {
                case "confirm":
                    $update_sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ?";

                    if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "i", $booking_id);

                        // --- Exécution de la mise à jour de la réservation ---
                        if (mysqli_stmt_execute($update_stmt)) {
                            $response = array(
                                "success" => true,
                                "message" => "Réservation confirmée avec succès."
                            );
                            echo json_encode($response);
                        } else {
                            $response = array(
                                "success" => false,
                                "message" => "Erreur lors de la confirmation de la réservation."
                            );
                            echo json_encode($response);
                        }

                        mysqli_stmt_close($update_stmt);
                    }
                    break;

                case "cancel":
                    $update_sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ?";

                    if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "i", $booking_id);

                        // --- Exécution de la mise à jour de la réservation ---
                        if (mysqli_stmt_execute($update_stmt)) {
                            $response = array(
                                "success" => true,
                                "message" => "Réservation annulée avec succès."
                            );
                            echo json_encode($response);
                        } else {
                            $response = array(
                                "success" => false,
                                "message" => "Erreur lors de l'annulation de la réservation."
                            );
                            echo json_encode($response);
                        }

                        mysqli_stmt_close($update_stmt);
                    }
                    break;

                default:
                    $response = array(
                        "success" => false,
                        "message" => "Action non reconnue."
                    );
                    echo json_encode($response);
                    break;
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Réservation introuvable."
            );
            echo json_encode($response);
        }
    } else {
        $response = array(
            "success" => false,
            "message" => "Erreur de requête."
        );
        echo json_encode($response);
    }
    mysqli_stmt_close($stmt);
} else {
    $response = array(
        "success" => false,
        "message" => "Erreur de préparation de la requête."
    );
    echo json_encode($response);
}

// --- Fermeture de la connexion à la base de données ---
mysqli_close($conn);
?>


