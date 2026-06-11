<?php
// 1. Indiquer au navigateur qu'on envoie du JSON
header('Content-Type: application/json');

require_once '../includes/db.php';

// 2. Vérification sécurisée de l'ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    try {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        $result = $stmt->execute([$id]);

        // 3. Succès de la requête
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // 4. En cas d'erreur SQL, on renvoie une erreur JSON propre
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // 5. Cas où l'ID est absent
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
}
?>