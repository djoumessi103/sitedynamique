<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

// On vérifie si l'ID est bien reçu
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        // 1. Optionnel : Supprimer l'image associée du serveur
        $stmtImg = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmtImg->execute([$id]);
        $pImg = $stmtImg->fetch();
        
        if($pImg && $pImg['image_url'] !== 'default.png') {
            $path = "../assets/img/" . $pImg['image_url'];
            if(file_exists($path)) { unlink($path); }
        }

        // 2. Supprimer l'entrée en base de données
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $result = $stmt->execute([$id]);

        echo json_encode(['success' => $result]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
}
exit;
?>