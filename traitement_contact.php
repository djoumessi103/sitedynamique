<?php
if (!empty($_POST['hp_field'])) { exit("Accès refusé."); }
// On tente d'inclure le fichier de config, sinon on définit la connexion ici
// require_once 'includes/db.php'; 

try {
    $pdo = new PDO("mysql:host=localhost;dbname=gala_agro", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
        $telephone = htmlspecialchars(trim($_POST['tel'] ?? ''));
        $message = htmlspecialchars(trim($_POST['message'] ?? ''));

        // Validation simple
        if (empty($nom) || empty($telephone)) {
            http_response_code(400);
            echo "❌ Erreur : Le nom et le téléphone sont obligatoires.";
            exit;
        }

        // Insertion
        $sql = "INSERT INTO contacts (nom_complet, telephone, message, date_envoi) VALUES (:nom, :tel, :msg, NOW())";
        $stmt = $pdo->prepare($sql);
        
        $resultat = $stmt->execute([
            ':nom' => $nom, 
            ':tel' => $telephone, 
            ':msg' => $message
        ]);

        // Vérification du résultat
        if ($resultat) {
            echo "✅ Votre commande a bien été transmise à nos équipes !";
        } else {
            http_response_code(500);
            echo "❌ Erreur lors de l'enregistrement.";
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo "❌ Erreur de base de données : " . $e->getMessage();
}
?>