<?php
session_start();
require_once '../includes/db.php';
$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :user");
    $stmt->execute([':user' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_logged'] = true;
        header('Location: messages.php');
        exit;
    } else {
        $erreur = "Identifiants d'accès invalides.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Administration - Gala</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a' } } } }
    </script>
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 md:p-10 rounded-[2.5rem] shadow-2xl w-full max-w-md">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-black text-slate-800">Gala Admin Panel</h2>
            <p class="text-slate-400 text-sm mt-1">Espace de gestion restreint</p>
        </div>
        
        <?php if($erreur): ?>
            <div class="bg-rose-50 text-rose-700 p-4 rounded-xl mb-6 text-sm font-semibold border border-rose-100 text-center"><?= $erreur ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Identifiant</label>
                <input type="text" name="username" class="w-full p-4 rounded-2xl border border-slate-200 focus:ring-2 focus:ring-galaGreen/50 outline-none transition" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Mot de passe</label>
                <input type="password" name="password" class="w-full p-4 rounded-2xl border border-slate-200 focus:ring-2 focus:ring-galaGreen/50 outline-none transition" required>
            </div>
            <button type="submit" class="w-full bg-galaDark text-white font-bold py-4 rounded-2xl hover:bg-galaGreen transition-all duration-300 shadow-xl">
                S'authentifier
            </button>
        </form>
    </div>
</body>
</html>