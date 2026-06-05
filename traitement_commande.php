<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $details = $_POST['details_panier'] ?? '';
    $panier = json_decode($_POST['panier_data'], true);
    $uploadDir = 'uploads/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Gestion des fichiers (inchangée)
    $cniFileName = null;
    if (isset($_FILES['cni_file']) && $_FILES['cni_file']['error'] === UPLOAD_ERR_OK) {
        $cniFileName = time() . '_cni_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['cni_file']['name']));
        move_uploaded_file($_FILES['cni_file']['tmp_name'], $uploadDir . $cniFileName);
    }

    $bonFileName = null;
    if (isset($_FILES['bon_commande']) && $_FILES['bon_commande']['error'] === UPLOAD_ERR_OK) {
        $bonFileName = time() . '_bon_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['bon_commande']['name']));
        move_uploaded_file($_FILES['bon_commande']['tmp_name'], $uploadDir . $bonFileName);
    }

    try {
        // DÉBUT DE LA TRANSACTION
        $pdo->beginTransaction();

        // 1. Vérification ET décrémentation du stock
        foreach ($panier as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $stmt->execute([$item['qty'], $item['id'], $item['qty']]);

            if ($stmt->rowCount() === 0) {
                // Si aucune ligne n'est mise à jour, le stock est insuffisant
                throw new Exception("Stock insuffisant pour : " . $item['nom']);
            }
        }

        // 2. Insertion de la commande
        $stmt = $pdo->prepare("INSERT INTO commandes 
            (nom, prenom, cni, cni_file, num_commercial, nom_marche, region, bon_commande, details_panier) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_POST['nom'],
            $_POST['prenom'],
            $_POST['cni'],
            $cniFileName,
            $_POST['num_commercial'],
            $_POST['nom_marche'],
            $_POST['region'],
            $bonFileName,
            $details
        ]);

        // Validation de tout le processus
        $pdo->commit();
        echo json_encode(["success" => true]);

    } catch (Exception $e) {
        // En cas d'erreur, on annule tout
        $pdo->rollBack();
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}
?>