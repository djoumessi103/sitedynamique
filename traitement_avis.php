<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

try {
    $note = $_POST['note'] ?? 5;
    $order_id = $_POST['order_id'] ?? 0;
    $nom_client = $_POST['nom_client'] ?? 'Anonyme'; // Récupération du nom

    // Mise à jour de votre requête SQL
    $stmt = $pdo->prepare("INSERT INTO avis_clients (note, order_id, nom_client, date_avis) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$note, $order_id, $nom_client]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>