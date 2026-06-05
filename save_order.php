<?php
if (!empty($_POST['hp_field'])) { exit("Accès refusé."); }
require_once 'includes/db.php';
ob_clean(); 
header('Content-Type: application/json');

try {
    // Récupération des données du formulaire
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $cni = $_POST['cni'] ?? '';
    $nom_marche = $_POST['nom_marche'] ?? '';
    $region = $_POST['region'] ?? '';
    $details = $_POST['message'] ?? ''; // Le détail de la commande

    // Insertion dans la base de données
    $stmt = $pdo->prepare("INSERT INTO commandes (nom, prenom, cni, nom_marche, region, details_panier, date_commande) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$nom, $prenom, $cni, $nom_marche, $region, $details]);

    echo json_encode(['success' => true]);
} catch (error) {
    console.error("Erreur save_order:", error);
    // Au lieu d'une alerte simple, affichez l'erreur précise
    alert("Erreur détaillée : " + error.message);
}