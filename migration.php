<?php
require_once 'includes/db.php';

$oldOrders = $pdo->query("SELECT * FROM commandes")->fetchAll();

foreach ($oldOrders as $old) {
    // 1. Transfert des infos de base
    $stmt = $pdo->prepare("INSERT INTO commandes_nouvelles (id, nom, prenom, cni, cni_file, num_commercial, nom_marche, region, bon_commande, date_commande) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$old['id'], $old['nom'], $old['prenom'], $old['cni'], $old['cni_file'], $old['num_commercial'], $old['nom_marche'], $old['region'], $old['bon_commande'], $old['date_commande']]);

    // 2. Extraction intelligente des produits
    $lignes = explode("\n", $old['details_panier']);
    foreach ($lignes as $ligne) {
        if (preg_match('/-\s*(\d+)\s+carton\(s\)\s+de\s+(.+)/i', $ligne, $m)) {
            $qty = (int)$m[1];
            $nomProduit = trim($m[2]);

            // Recherche exacte ou similaire pour trouver l'ID
            $prod = $pdo->prepare("SELECT id FROM products WHERE nom LIKE ? LIMIT 1");
            $prod->execute(["%$nomProduit%"]);
            $p = $prod->fetch();

            if ($p) {
                $pdo->prepare("INSERT INTO commande_details (commande_id, product_id, quantite) VALUES (?, ?, ?)")
                    ->execute([$old['id'], $p['id'], $qty]);
            }
        }
    }
}
echo "Migration terminée !";
?>