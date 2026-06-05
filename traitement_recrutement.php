<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... votre code de récupération des données ...
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $tel = htmlspecialchars($_POST['telephone']);
    $poste = htmlspecialchars($_POST['poste']);
    // Simplification : stocker uniquement le nom du fichier pour la BDD
    $cvName = time() . '_' . basename($_FILES['cv']['name']);
    $lettreName = time() . '_' . basename($_FILES['lettre']['name']);
    
    $uploadDir = 'uploads/candidatures/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (move_uploaded_file($_FILES['cv']['tmp_name'], $uploadDir . $cvName) && 
        move_uploaded_file($_FILES['lettre']['tmp_name'], $uploadDir . $lettreName)) {

        $stmt = $pdo->prepare("INSERT INTO candidatures (nom_complet, email, telephone, poste, cv_url, lettre_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $email, $tel, $poste, $cvName, $lettreName]);

        echo json_encode(['status' => 'success', 'message' => 'Candidature transmise avec succès !']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'upload.']);
    }
}

}
?>