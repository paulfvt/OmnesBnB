<?php
// --- Démarrage de la session utilisateur ---
session_start();

// --- Suppression de toutes les variables de session ---
$_SESSION = array();

// --- Destruction de la session si elle existe ---
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// --- Redémarrage d'une session pour afficher un message de déconnexion ---
session_start();
$_SESSION["message"] = "Vous avez été déconnecté avec succès.";
$_SESSION["message_type"] = "success";

// --- Redirection vers la page d'accueil ---
header("location: ../index.php");
exit;
?>
