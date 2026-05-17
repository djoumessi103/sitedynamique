<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';

$messages = $pdo->query("SELECT * FROM contacts ORDER BY date_envoi DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des Messages</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a' } } } }
    </script>
</head>
<body class="bg-slate-50 font-sans min-h-screen flex">

    <aside class="w-64 bg-galaDark text-white p-6 flex flex-col justify-between shadow-xl">
        <div>
            <div class="mb-10">
                <h1 class="text-2xl font-black text-galaGreen">Gala Admin Panel</h1>
                <p class="text-xs text-slate-400">Version Pro 2026</p>
            </div>
            <nav class="space-y-2">
                <a href="messages.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl bg-galaGreen font-bold text-white shadow-md">
                    <i class="fas fa-envelope"></i> <span>Messages</span>
                </a>
                <a href="products_manager.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition">
                    <i class="fas fa-box-open"></i> <span>Produits & Stocks</span>
                </a>
                <a href="gallery.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition">
                    <i class="fas fa-images"></i> <span>Galerie</span>
                </a>
                <div class="pt-6 my-4 border-t border-slate-800">
                    <a href="../index.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl text-amber-400 hover:bg-amber-500/10 transition font-medium">
                        <i class="fas fa-globe"></i> <span>Consulter le site</span>
                    </a>
                </div>
            </nav>
        </div>
        <a href="logout.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl text-rose-400 hover:bg-rose-500/10 transition font-bold">
            <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
        </a>
    </aside>

    <main class="flex-1 p-8 md:p-12 overflow-y-auto">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-black text-slate-800">Messages Reçus</h2>
                <p class="text-sm text-slate-400 mt-1">Interactions prospects via formulaire vitrine</p>
            </div>
            <span class="bg-galaGreen/10 text-galaGreen px-4 py-2 rounded-xl text-sm font-black border border-galaGreen/20">
                <?= count($messages) ?> Message(s)
            </span>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Client</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Contact</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Message</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="5" class="p-10 text-center text-slate-400 italic">Aucune interaction disponible.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($messages as $m): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="p-5 font-bold text-slate-800"><?= htmlspecialchars($m['nom_complet']) ?></td>
                        <td class="p-5">
                            <a href="tel:<?= $m['telephone'] ?>" class="text-galaGreen hover:underline font-semibold flex items-center space-x-2">
                                <i class="fas fa-phone-alt text-xs"></i> <span><?= htmlspecialchars($m['telephone']) ?></span>
                            </a>
                        </td>
                        <td class="p-5 text-slate-600 max-w-xs truncate" title="<?= htmlspecialchars($m['message']) ?>"><?= htmlspecialchars($m['message']) ?></td>
                        <td class="p-5 text-sm text-slate-400"><?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?></td>
                        <td class="p-5 text-center">
                            <a href="delete_message.php?id=<?= $m['id'] ?>" onclick="return confirm('Confirmer la suppression définitive ?');" class="text-rose-400 hover:text-rose-600 p-2 transition">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>