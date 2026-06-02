<?php
session_start();
require_once 'includes/db.php';

// On s'assure que la requête arrive bien en POST et avec les paramètres nécessaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et sécurisation des données reçues
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qtyOrder = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    if ($productId <= 0 || $qtyOrder <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Données de commande invalides."]);
        exit;
    }

    try {
        // 1. Vérifier d'abord le stock actuel en Base de données
        $stmt = $pdo->prepare("SELECT stock, nom FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(440);
            echo json_encode(["error" => "Produit introuvable."]);
            exit;
        }

        $currentStock = (int)$product['stock'];

        if ($qtyOrder > $currentStock) {
            http_response_code(400);
            echo json_encode([
                "error" => "Stock insuffisant pour le produit " . $product['nom'] . ". Stock disponible : " . $currentStock
            ]);
            exit;
        }

        // 2. Décrémenter le stock dans la base de données
        $newStock = $currentStock - $qtyOrder;
        $updateStmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $updateStmt->execute([$newStock, $productId]);

        // 3. Optionnel : Vous pouvez ici insérer la commande dans une table `commandes` si nécessaire

        // Renvoi de la réponse de succès avec le nouveau stock restant
        header('Content-Type: application/json');
        echo json_encode([
            "success" => true,
            "message" => "Commande validée avec succès !",
            "new_stock" => $newStock,
            "product_id" => $productId
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur technique : " . $e->getMessage()]);
        exit;
    }
} else {
    http_response_code(403);
    echo json_encode(["error" => "Accès refusé."]);
    exit;
}