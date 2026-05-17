<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit('Accès restreint aux administrateurs.');
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

header('Location: messages.php');
exit;
?>