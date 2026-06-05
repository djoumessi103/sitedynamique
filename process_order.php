<?php
// process_order.php
session_start();
// Correction du chemin : on cherche db.php dans le dossier 'includes' situé au même niveau
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

// Vérification stricte de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Méthode attendue: POST, Reçu: " . $_SERVER['REQUEST_METHOD']]);
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qtyOrder = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($productId <= 0 || $qtyOrder <= 0) {
    echo json_encode(["success" => false, "error" => "Données de commande invalides."]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT stock, nom FROM products WHERE id = ? FOR UPDATE");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "error" => "Produit introuvable."]);
        exit;
    }

    if ($qtyOrder > (int)$product['stock']) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "error" => "Stock insuffisant."]);
        exit;
    }

    $newStock = (int)$product['stock'] - $qtyOrder;
    $updateStmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $updateStmt->execute([$newStock, $productId]);

    $pdo->commit();
    echo json_encode(["success" => true, "new_stock" => $newStock]);

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode(["success" => false, "error" => "Erreur serveur : " . $e->getMessage()]);
}