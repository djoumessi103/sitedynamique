<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $telephone = htmlspecialchars(trim($_POST['tel']));
    $message = htmlspecialchars(trim($_POST['message']));

    if (empty($nom) || empty($telephone)) {
        http_response_code(400);
        echo "Erreur : Le nom et le téléphone sont obligatoires.";
        exit;
    }

    try {
        $sql = "INSERT INTO contacts (nom_complet, telephone, message) VALUES (:nom, :tel, :msg)";
        $stmt = $pdo->prepare($sql);
        $resultat = $stmt->execute([':nom' => $nom, ':tel' => $telephone, ':msg' => $message]);

        if ($resultat) {
            http_response_code(200);
            echo "Parfait ! Votre message a bien été transmis.";
        } else {
            http_response_code(500);
            echo "Désolé, impossible d'enregistrer le message.";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Erreur technique : " . $e->getMessage();
    }
} else {
    http_response_code(403);
    echo "Accès refusé.";
}
?>