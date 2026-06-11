<?php
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM candidatures WHERE id = ?");
    $result = $stmt->execute([$id]);

    // On renvoie un JSON pour le JavaScript
    echo json_encode(['success' => $result]);
}
?>