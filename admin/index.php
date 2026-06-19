<?php
session_start();

// Si l'administrateur est déjà connecté, on le redirige directement vers son tableau de bord (ex: messages.php)
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: dashboard.php');
    exit;
} else {
    // Sinon, on le redirige immédiatement vers la page de connexion
    header('Location: login.php');
    exit;
}
?>