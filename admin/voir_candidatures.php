<?php
require_once '../includes/db.php';
$current_page = 'voir_candidatures.php';

$query = $pdo->query("SELECT * FROM candidatures ORDER BY date_candidature DESC");
$candidatures = $query->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Candidatures</title>
    <script src="../assets/tailwind.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a', galaGold: '#f8f9f8f1', } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans min-h-screen flex flex-col md:flex-row">

    <div class="md:hidden bg-galaGold p-4 flex justify-between items-center shadow-md">
        <h1 class="font-black text-galaGreen">Gala <span class="text-[#E30613]">Admin</span></h1>
        <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-xl text-slate-700">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <aside id="mobile-menu" class="hidden md:flex w-full md:w-64 bg-galaGold text-galaGreen p-6 flex-col justify-between shadow-xl md:min-h-screen sticky top-0 z-50">
        <div>
            <div class="mb-10 hidden md:block">
                <h1 class="text-2xl font-black text-galaGreen">Gala<span class="text-[#E30613]"> Admin</span></h1>
                <p class="text-xs font-bold text-slate-700/80">Espace Restreint</p>
            </div>
            <nav class="space-y-2">
                <a href="messages.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold text-slate-700 hover:bg-black/5"><i class="fas fa-envelope w-5"></i> <span>Messages</span></a>
                <a href="products_manager.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold text-slate-700 hover:bg-black/5"><i class="fas fa-box w-5"></i> <span>Produits</span></a>
                <a href="gallery.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold text-slate-700 hover:bg-black/5"><i class="fas fa-images w-5"></i> <span>Galerie</span></a>
                <a href="voir_candidatures.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold bg-galaGreen text-white shadow-md">
                    <i class="fas fa-users w-5"></i> <span>Candidatures</span>
                </a>
                <a href="admin_commandes.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'admin_commandes.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5'?>">
                    <i class="fas fa-shopping-cart w-5"></i> <span>Finaliser commandes</span>
                </a>
                <a href="../index.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold text-[#E30613] hover:bg-black/5"><i class="fas fa-globe w-5"></i> <span>Consulter le site</span></a>
            </nav>
        </div>
        <a href="logout.php" class="flex items-center space-x-3 p-3 rounded-xl text-rose-700 hover:bg-rose-700/10 transition font-bold mt-10 md:mt-auto">
            <i class="fas fa-sign-out-alt w-5"></i> <span>Déconnexion</span>
        </a>
    </aside>

    <main class="flex-1 p-4 md:p-8">
        <div class="max-w-6xl mx-auto">
            <header class="mb-8">
                <h2 class="text-2xl md:text-3xl font-black text-slate-800">Candidatures reçues</h2>
                <p class="text-slate-500 text-sm md:text-base">Gérez les dossiers des nouveaux talents.</p>
            </header>

            <div class="bg-white rounded-2xl md:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px] text-left">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Candidat</th>
                                <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Poste</th>
                                <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Contact</th>
                                <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center">Documents</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach($candidatures as $c): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 md:p-5 font-semibold text-slate-800 text-sm"><?= htmlspecialchars($c['nom_complet']) ?></td>
                                <td class="p-4 md:p-5 text-slate-600 text-sm"><?= htmlspecialchars($c['poste']) ?></td>
                                <td class="p-4 md:p-5 text-xs md:text-sm text-slate-500">
                                    <div class="truncate max-w-[150px]"><?= htmlspecialchars($c['email']) ?></div>
                                    <div class="font-bold text-galaDark"><?= htmlspecialchars($c['telephone']) ?></div>
                                </td>
                                <td class="p-4 md:p-5 flex justify-center items-center gap-2">
                                    <a href="../uploads/candidatures/<?= $c['cv_url'] ?>" target="_blank" class="p-2 md:px-4 md:py-2 bg-galaGreen text-white rounded-lg text-xs font-bold hover:bg-green-700 transition">
                                        <i class="fas fa-file-pdf"></i><span class="hidden md:inline ml-1">CV</span>
                                    </a>
                                    <a href="../uploads/candidatures/<?= $c['lettre_url'] ?>" target="_blank" class="p-2 md:px-4 md:py-2 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-black transition">
                                        <i class="fas fa-file-alt"></i><span class="hidden md:inline ml-1">Lettre</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>