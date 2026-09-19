<?php
// On vérifie si Render nous fournit l'environnement en ligne
$internal_url = getenv('DATABASE_URL'); 

if ($internal_url) {
    // --- CONFIGURATION EN LIGNE (RENDER / POSTGRESQL) ---
    // On extrait les informations de l'URL fournie par Render
    $dbopts = parse_url($internal_url);
    
    $host = $dbopts["host"];
    $port = $dbopts["port"];
    $user = $dbopts["user"];
    $pass = $dbopts["pass"];
    $dbname = ltrim($dbopts["path"], '/');

    try {
        // Connexion via le pilote PostgreSQL (pgsql)
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        die("Erreur de connexion en ligne : " . $e->getMessage());
    }

} else {
    // --- CONFIGURATION LOCALE (XAMPP / MYSQL) ---
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'gala_agro');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        die("Erreur de connexion locale : " . $e->getMessage());
    }
}

$host = 'localhost';
$dbname = 'gala_agro';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

?>