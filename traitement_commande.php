<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

try {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // 1. Gestion des fichiers
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

    // 2. Récupération des données
    $nom = $_POST['nom'] ?? 'Inconnu';
    $prenom = $_POST['prenom'] ?? '';
    $cni = $_POST['cni'] ?? '';
    $num_commercial = $_POST['num_commercial'] ?? '';
    $nom_marche = $_POST['nom_marche'] ?? '';
    $region = $_POST['region'] ?? '';
    $details = $_POST['message'] ?? '';

    // 3. Insertion sécurisée
    $stmt = $pdo->prepare("INSERT INTO commandes 
        (nom, prenom, cni, cni_file, num_commercial, nom_marche, region, bon_commande, details_panier, date_commande) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([
        $nom, $prenom, $cni, $cniFileName, $num_commercial, 
        $nom_marche, $region, $bonFileName, $details
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}