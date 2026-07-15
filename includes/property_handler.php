<?php
// --- Démarrage de la session utilisateur ---
session_start();
// --- Connexion à la base de données ---
require_once "db_connection.php";
// --- Vérification de l'authentification ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_POST['property_id']) || !isset($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants.'
    ]);
    exit;
}


$property_id = $_POST['property_id'];
$action = $_POST['action'];

switch ($action) {
    case 'toggle_status':
        if (!isset($_POST['status'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Le statut est requis.'
            ]);
            exit;
        }

        $status = (int) $_POST['status']; // 0 = inactif, 1 = actif

        $check_owner_sql = "SELECT owner_id FROM properties WHERE id = ?";
        if ($check_stmt = mysqli_prepare($conn, $check_owner_sql)) {
            mysqli_stmt_bind_param($check_stmt, "i", $property_id);

            if (mysqli_stmt_execute($check_stmt)) {
                $owner_result = mysqli_stmt_get_result($check_stmt);

                if (mysqli_num_rows($owner_result) > 0) {
                    $property_data = mysqli_fetch_assoc($owner_result);

                    if ($property_data['owner_id'] == $user_id) {
                        $update_sql = "UPDATE properties SET is_published = ? WHERE id = ?";

                        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "ii", $status, $property_id);

                            if (mysqli_stmt_execute($update_stmt)) {
                                echo json_encode([
                                    'success' => true,
                                    'message' => 'Le statut de la propriété a été mis à jour avec succès.',
                                    'new_status' => $status
                                ]);
                            } else {
                                echo json_encode([
                                    'success' => false,
                                    'message' => 'Une erreur est survenue lors de la mise à jour du statut : ' . mysqli_error($conn)
                                ]);
                            }

                            mysqli_stmt_close($update_stmt);
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Erreur de préparation de la requête : ' . mysqli_error($conn)
                            ]);
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Vous n\'êtes pas autorisé à modifier cette propriété.'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Propriété introuvable.'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la vérification de la propriété.'
                ]);
            }

            mysqli_stmt_close($check_stmt);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur de préparation de la requête.'
            ]);
        }        break;
    case 'delete':
        $check_owner_sql = "SELECT owner_id FROM properties WHERE id = ?";
        if ($check_stmt = mysqli_prepare($conn, $check_owner_sql)) {
            mysqli_stmt_bind_param($check_stmt, "i", $property_id);

            if (mysqli_stmt_execute($check_stmt)) {
                $owner_result = mysqli_stmt_get_result($check_stmt);

                if (mysqli_num_rows($owner_result) > 0) {
                    $property_data = mysqli_fetch_assoc($owner_result);

                    if ($property_data['owner_id'] == $user_id) {
                        try {
                            $delete_property_sql = "DELETE FROM properties WHERE id = ?";
                            $stmt_property = mysqli_prepare($conn, $delete_property_sql);
                            mysqli_stmt_bind_param($stmt_property, "i", $property_id);
                            mysqli_stmt_execute($stmt_property);
                            mysqli_stmt_close($stmt_property);

                            mysqli_commit($conn);

                            echo json_encode([
                                'success' => true,
                                'message' => 'La propriété a été supprimée avec succès.'
                            ]);
                        } catch (Exception $e) {
                            mysqli_rollback($conn);

                            echo json_encode([
                                'success' => false,
                                'message' => 'Une erreur est survenue lors de la suppression : ' . $e->getMessage()
                            ]);
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Vous n\'êtes pas autorisé à supprimer cette propriété.'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Propriété introuvable.'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la vérification de la propriété.'
                ]);
            }
            mysqli_stmt_close($check_stmt);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur de préparation de la requête.'
            ]);
        }
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue.'
        ]);
        break;
}

?>


