<?php
session_start();
// Inclusion de votre fichier de connexion (adaptez le chemin si nécessaire)
require_once 'includes/db.php'; 

// 1. Vérification des paramètres reçus dans l'URL
if (!isset($_GET['id']) || !isset($_GET['qty']) || !isset($_GET['unit'])) {
    die("Paramètres de commande manquants.");
}

$id_produit = (int)$GET['id'];
$quantite = (int)$GET['qty'];
$unite = htmlspecialchars($_GET['unit']);

if ($id_produit <= 0 || $quantite <= 0) {
    die("Données de commande invalides.");
}

try {
    // 2. Début de la transaction pour sécuriser l'opération
    $pdo->beginTransaction();

    // 3. Requête SQL atomique : décrémente SEULEMENT si le stock est suffisant
    $stmt = $pdo->prepare("
        UPDATE products 
        SET stock = stock - :quantite 
        WHERE id = :id AND stock >= :quantite
    ");
    
    $stmt->execute([
        ':quantite' => $quantite,
        ':id'       => $id_produit
    ]);

    // 4. Vérification si la mise à jour a réussi
    if ($stmt->rowCount() === 0) {
        // Le stock a probablement changé entre-temps ou le produit n'existe pas
        throw new Exception("Le stock disponible est insuffisant pour valider votre commande.");
    }

    // [OPTIONNEL] C'est ici que vous pouvez insérer un INSERT INTO pour enregistrer la commande dans une table "orders" si vous en possédez une.

    // Validation définitive de la transaction
    $pdo->commit();

    // 5. Message de succès et redirection vers le catalogue
    echo "<script>
            alert('Votre commande de $quantite $unite a bien été prise en compte ! Le stock a été mis à jour.');
            window.location.href = 'index.php'; // Remplacez par le nom de votre page catalogue
          </script>";

} catch (Exception $e) {
    // Annulation des modifications en cas d'erreur
    $pdo->rollBack();
    echo "<script>
            alert('Erreur : " . addslashes($e->getMessage()) . "');
            window.location.href = 'index.php';
          </script>";
}
?>