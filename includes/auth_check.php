<?php
/* ═══════════════════════════════════════════════════════
   CONTRÔLE D'ACCÈS PAR RÔLE — Gala Agro Admin
   ═══════════════════════════════════════════════════════
   USAGE — à inclure en haut de chaque page protégée :

       session_start();
       $allowed_roles = ['admin', 'commercial']; // rôles autorisés sur CETTE page
       require_once '../includes/auth_check.php';

   Si $allowed_roles n'est pas défini avant l'include,
   tous les rôles connectés sont autorisés par défaut.
   ═══════════════════════════════════════════════════════ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pas connecté du tout → retour à l'écran de connexion
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}

// Aucune restriction précisée par la page = tous les rôles connectés passent
if (!isset($allowed_roles)) {
    $allowed_roles = ['admin', 'rh', 'commercial'];
}

// Sécurité : si une vieille session n'a pas de rôle, on la traite comme non autorisée
$current_role = $_SESSION['role'] ?? null;

if (!in_array($current_role, $allowed_roles, true)) {
    header('Location: access_denied.php');
    exit;
}
