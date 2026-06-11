<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

try {
    // 1. Récupération des données du formulaire (ajoutez $num_commercial)
    $nom = $_POST['nom'] ?? 'Inconnu';
    $prenom = $_POST['prenom'] ?? '';
    $cni = $_POST['cni'] ?? '';
    $num_commercial = $_POST['num_commercial'] ?? ''; // <--- RÉCUPÉRATION
    $nom_marche = $_POST['nom_marche'] ?? '';
    $region = $_POST['region'] ?? '';
    $details = $_POST['message'] ?? ''; 

    // 2. Gestion des fichiers (Ajoutée pour éviter les NULL dans la base)
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $cniFileName = null;
    if (isset($_FILES['cni_file']) && $_FILES['cni_file']['error'] === UPLOAD_ERR_OK) {
        $cniFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\\-_]/', '', basename($_FILES['cni_file']['name']));
        move_uploaded_file($_FILES['cni_file']['tmp_name'], $uploadDir . $cniFileName);
    }

    $bonFileName = null;
    if (isset($_FILES['bon_commande']) && $_FILES['bon_commande']['error'] === UPLOAD_ERR_OK) {
        $bonFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\\-_]/', '', basename($_FILES['bon_commande']['name']));
        move_uploaded_file($_FILES['bon_commande']['tmp_name'], $uploadDir . $bonFileName);
    }

    // 3. Insertion dans la base de données (Colonnes + Valeurs ajoutées)
    $stmt = $pdo->prepare("INSERT INTO commandes 
        (nom, prenom, cni, cni_file, num_commercial, nom_marche, region, bon_commande, details_panier, date_commande) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $nom, 
        $prenom, 
        $cni, 
        $cniFileName, 
        $num_commercial, // <--- AJOUTÉ ICI
        $nom_marche, 
        $region, 
        $bonFileName, 
        $details
    ]);
$order_id = $pdo->lastInsertId(); // Récupère l'ID généré par SQL

echo json_encode([
    'success' => true, 
    'order_id' => $order_id  // <--- CETTE LIGNE EST OBLIGATOIRE
]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}