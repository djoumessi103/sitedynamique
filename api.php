<?php
// api.php
require_once 'includes/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ACTION 1 : Récupérer le stock actuel (DOIT ÊTRE SÉPARÉ)
if ($action === 'get_stock') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($result ?: ["stock" => 0]);
    exit;
}

// ACTION 2 : Décrémenter le stock
if ($action === 'decrement_stock') {
    $id = (int)($_GET['product_id'] ?? 0);
    $qty = (int)($_GET['quantity'] ?? 0);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmt->execute([$qty, $id, $qty]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            echo json_encode(["success" => true]);
        } else {
            $pdo->rollBack();
            echo json_encode(["success" => false, "error" => "Stock insuffisant ou produit introuvable."]);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit;
}
?>