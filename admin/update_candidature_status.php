<?php
require_once '../includes/db.php';

if (isset($_POST['id']) && isset($_POST['statut'])) {
    $id = $_POST['id'];
    $statut = $_POST['statut'];

    // 1. Récupérer les infos du candidat (Ajout de 'poste' ici)
    $stmt = $pdo->prepare("SELECT nom_complet, email, poste FROM candidatures WHERE id = ?");
    $stmt->execute([$id]);
    $c = $stmt->fetch();

    if ($c) {
        // 2. Mettre à jour la BDD
        $update = $pdo->prepare("UPDATE candidatures SET statut = ? WHERE id = ?");
        $update->execute([$statut, $id]);

        // 3. Envoi de l'email uniquement si Validé ou Refusé
        if ($statut === 'Validé' || $statut === 'Refusé') {
            
            $to = $c['email'];
            $subject = ($statut === 'Validé') ? "Bonne nouvelle : Suite à votre candidature chez Gala Mayonnaise" : "Suivi de votre candidature - Gala Mayonnaise";

            // Modèle HTML
            $message = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #16a34a;'>Gala Mayonnaise</h2>
                    <p>Bonjour <strong>" . htmlspecialchars($c['nom_complet']) . "</strong>,</p>
                    
                    <p>Nous vous remercions de l'intérêt que vous portez à notre enseigne.</p>
                    
                    <p>Après étude de votre dossier pour le poste de <strong>" . htmlspecialchars($c['poste']) . "</strong>, nous avons le plaisir de vous informer que votre statut a été mis à jour :</p>
                    
                    <div style='background: #f8fafc; padding: 15px; border-left: 4px solid " . ($statut === 'Validé' ? '#16a34a' : '#dc2626') . "; margin: 20px 0;'>
                        <strong>Statut actuel : " . $statut . "</strong>
                    </div>
                    
                    " . ($statut === 'Validé' ? "<p>Un membre de notre équipe RH prendra contact avec vous prochainement pour fixer un entretien.</p>" : "<p>Nous conservons votre dossier dans notre base de données pour de futures opportunités correspondant à votre profil.</p>") . "
                    
                    <p>Cordialement,<br><strong>L'équipe Recrutement Gala Mayonnaise</strong></p>
                </div>
            </body>
            </html>
            ";

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: RH Supermarché SPA <rh@galamayonnaise.com>" . "\r\n";

            mail($to, $subject, $message, $headers);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Candidat introuvable']);
    }
}
?>
<?php
// Utilisation des fichiers manuels sans dossier vendor
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once '../includes/db.php';

if (isset($_POST['id']) && isset($_POST['statut'])) {
    $id = $_POST['id'];
    $statut = $_POST['statut'];

    $stmt = $pdo->prepare("SELECT nom_complet, email, poste FROM candidatures WHERE id = ?");
    $stmt->execute([$id]);
    $c = $stmt->fetch();

    if ($c && ($statut === 'Validé' || $statut === 'Refusé')) {
        $update = $pdo->prepare("UPDATE candidatures SET statut = ? WHERE id = ?");
        $update->execute([$statut, $id]);

        $mail = new PHPMailer(true);

        try {
            // Configuration serveur Gmail
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'djoujesica86@gmail.com'; // Votre adresse Gmail
            $mail->Password   = 'jvta trhv eqgj ubbv'; // LE CODE DE 16 CARACTÈRES
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Expéditeur et destinataire
            $mail->setFrom('djoujesica86@gmail.com', 'RH Gala Mayonnaise');
            $mail->addAddress($c['email']);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = ($statut === 'Validé') ? "Bonne nouvelle : Candidature retenue" : "Suivi de votre candidature";
            
            // Votre modèle HTML
            $mail->Body = "
            <div style='font-family: Arial; padding: 20px; border: 1px solid #eee;'>
                <h2>Gala Mayonnaise</h2>
                <p>Bonjour <strong>" . htmlspecialchars($c['nom_complet']) . "</strong>,</p>
                <p>Statut mis à jour : <strong>" . $statut . "</strong></p>
            </div>";

            $mail->send();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Erreur email : {$mail->ErrorInfo}"]);
        }
    }
}