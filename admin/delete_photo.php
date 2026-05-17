<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit('Accès restreint aux administrateurs.');
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT image_url FROM gallery WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $photo = $stmt->fetch();

    if ($photo) {
        $path = "../assets/img/gallery/" . $photo['image_url'];
        if (file_exists($path)) {
            unlink($path);
        }
        $delete = $pdo->prepare("DELETE FROM gallery WHERE id = :id");
        $delete->execute([':id' => $id]);
    }
}

header('Location: gallery.php');
exit;
?>