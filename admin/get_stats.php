<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

// 1. Liste par Marché (on compte le nombre de commandes)
$marche = $pdo->query("SELECT nom_marche, COUNT(*) as nb FROM commandes GROUP BY nom_marche")->fetchAll(PDO::FETCH_ASSOC);

// 2. Liste par Région (on compte le nombre de commandes)
$region = $pdo->query("SELECT region, COUNT(*) as nb FROM commandes GROUP BY region")->fetchAll(PDO::FETCH_ASSOC);

// 3. Liste des 5 meilleurs clients
$client = $pdo->query("SELECT nom, prenom, COUNT(*) as nb_commandes FROM commandes GROUP BY nom, prenom ORDER BY nb_commandes DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 4. Client le plus fidèle (Top unique pour la prime)
$topClient = $pdo->query("SELECT nom, prenom, COUNT(*) as nb FROM commandes GROUP BY nom, prenom ORDER BY nb DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Envoi des données encodées en JSON pour le JavaScript
echo json_encode([
    'marche'    => $marche, 
    'region'    => $region, 
    'client'    => $client, 
    'topClient' => $topClient
]);
?>