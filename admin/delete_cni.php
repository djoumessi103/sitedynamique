<?php
session_start();
if (!isset($_SESSION['admin_logged'])) exit(json_encode(['success' => false, 'message' => 'Non autorisé']));

require_once '../includes/db.php';

$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? ''; // On ajoute un paramètre pour savoir quoi faire

header('Content-Type: application/json');

// CAS : Suppression de toute la ligne
if ($action === 'supprimer_tout') {
    // 1. Récupérer les noms des fichiers pour les supprimer physiquement
    $stmt = $pdo->prepare("SELECT cni_file, bon_commande FROM commandes WHERE id = ?");
    $stmt->execute([$id]);
    $cmd = $stmt->fetch();

    if ($cmd) {
        // Suppression physique des fichiers
        foreach (['cni_file', 'bon_commande'] as $col) {
            if (!empty($cmd[$col]) && $cmd[$col] !== 'null' && file_exists('../uploads/' . $cmd[$col])) {
                unlink('../uploads/' . $cmd[$col]);
            }
        }
        // 2. Suppression de la ligne en base de données
        $stmtDel = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
        $stmtDel->execute([$id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Commande introuvable']);
    }
}
?>