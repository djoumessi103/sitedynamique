<?php
require_once 'includes/db.php'; 
echo "";
// IMPORTANT : Si le formulaire envoie 'nom' et 'tel', 
// ne cherchez pas 'nom_complet' ou 'telephone' dans $_POST
$nom = $_POST['nom'] ?? ''; 
$telephone = $_POST['tel'] ?? '';
$message = $_POST['message'] ?? '';

try {
    $sql = "INSERT INTO contacts (nom_complet, telephone, message, date_envoi) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom, $telephone, $message]);
    
    echo "✅ Enregistré avec succès !";
} catch (Exception $e) {
    // Ceci est la ligne magique : elle vous dira SI la table est mal nommée ou si une colonne manque
    http_response_code(500);
    echo "❌ Erreur SQL : " . $e->getMessage();
}