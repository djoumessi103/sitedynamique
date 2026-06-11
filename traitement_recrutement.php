<?php
header('Content-Type: application/json');
require_once 'includes/db.php';

// Configuration de sécurité
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 Mo
const ALLOWED_TYPES = ['application/pdf'];
const UPLOAD_DIR = 'uploads/candidatures/';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée.");
    }

    // 1. Validation des champs obligatoires
    $required = ['nom', 'email', 'telephone', 'poste'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) throw new Exception("Le champ $field est requis.");
    }

    // 2. Traitement des fichiers (Sécurisation)
    if (!isset($_FILES['cv']) || !isset($_FILES['lettre'])) {
        throw new Exception("Veuillez fournir les deux fichiers (CV et Lettre).");
    }

    $files = ['cv' => $_FILES['cv'], 'lettre' => $_FILES['lettre']];
    $uploadedFiles = [];

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    foreach ($files as $key => $file) {
        if ($file['size'] > MAX_FILE_SIZE) throw new Exception("Le fichier $key est trop volumineux.");
        if (!in_array($file['type'], ALLOWED_TYPES)) throw new Exception("Le format du fichier $key est invalide (PDF uniquement).");

        $safeName = uniqid('cand_', true) . '_' . bin2hex(random_bytes(8)) . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $safeName)) {
            throw new Exception("Erreur lors de l'enregistrement du fichier $key.");
        }
        $uploadedFiles[$key] = $safeName;
    }

    // 3. Insertion en Base de Données
    $stmt = $pdo->prepare("INSERT INTO candidatures (nom_complet, email, telephone, poste, cv_url, lettre_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['nom'], $_POST['email'], $_POST['telephone'], $_POST['poste'],
        $uploadedFiles['cv'], $uploadedFiles['lettre']
    ]);

    echo json_encode(['success' => true, 'message' => 'Candidature enregistrée avec succès.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}