<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id === 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    $pdo->beginTransaction(); // On ouvre la transaction pour sécuriser le stock

    // 1. Récupérer les détails de la commande AVANT suppression
    $stmt = $pdo->prepare("SELECT details_panier, cni_file, bon_commande FROM commandes WHERE id = ?");
    $stmt->execute([$id]);
    $cmd = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cmd) {
        // 2. LOGIQUE DE RÉINCRÉMENTATION
        // Analyse du texte (Exemple: "- 1 carton(s) de mayonnaise")
        // NOTE: Si vous avez plusieurs produits, il faut une boucle ici.
        // Ici, on extrait la quantité et le nom simplifié.
        if (preg_match('/(\d+)\s+carton\(s\)\s+de\s+(.+)\s+\(/i', $cmd['details_panier'], $matches)) {
            $quantite = (int)$matches[1];
            $nom_produit = trim($matches[2]);

            // Mise à jour du stock dans la table 'products'
            $update = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE nom LIKE ?");
            $update->execute([$quantite, '%' . $nom_produit . '%']);
        }

        // 3. Suppression des fichiers
        if (!empty($cmd['cni_file']) && file_exists('../uploads/' . $cmd['cni_file'])) unlink('../uploads/' . $cmd['cni_file']);
        if (!empty($cmd['bon_commande']) && file_exists('../uploads/' . $cmd['bon_commande'])) unlink('../uploads/' . $cmd['bon_commande']);

        // 4. Suppression de la commande
        $del = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
        $del->execute([$id]);

        $pdo->commit(); // Validation
        echo json_encode(['success' => true]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Commande introuvable']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?>