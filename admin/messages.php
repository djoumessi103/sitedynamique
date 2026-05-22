<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';
$current_page = basename($_SERVER['PHP_SELF']);

// ==========================================
// CONFIGURATION DE LA PAGINATION
// ==========================================
$items_per_page = 5; // Nombre de messages maximum par page
$page = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $items_per_page;

// Calcul du nombre total de messages
$total_items = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

if ($page > $total_pages && $total_pages > 0) { 
    $page = $total_pages; 
    $offset = ($page - 1) * $items_per_page; 
}

// Récupération des messages pour la page actuelle
$stmt = $pdo->prepare("SELECT * FROM contacts ORDER BY date_envoi DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll();
// ==========================================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin - Gestion des Messages</title>
    <script src="../assets/tailwind.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a', galaGold: '#f8f9f8f1', } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans min-h-screen flex flex-col md:flex-row">

    <aside class="hidden md:flex w-64 bg-galaGold text-galaGreen p-6 flex-col justify-between shadow-xl min-h-screen sticky top-0">
        <div>
            <div class="mb-10">
                <h1 class="text-2xl font-black text-galaGreen">Gala<span class="text-[#E30613]"> Admin</span></h1>
                <p class="text-xs font-bold text-slate-700/80">Espace Restreint</p>
            </div>
            <nav class="space-y-2">
                <a href="messages.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'messages.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5' ?>">
                    <i class="fas fa-envelope w-5 <?= ($current_page == 'messages.php') ? 'text-white' : 'text-slate-600' ?>"></i> 
                    <span>Messages</span>
                </a>
                
                <a href="products_manager.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'products_manager.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5' ?>">
                    <i class="fas fa-box w-5 <?= ($current_page == 'products_manager.php') ? 'text-white' : 'text-slate-600' ?>"></i> 
                    <span>Produits</span>
                </a>
                
                <a href="gallery.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'gallery.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5' ?>">
                    <i class="fas fa-images w-5 <?= ($current_page == 'gallery.php') ? 'text-white' : 'text-slate-600' ?>"></i> 
                    <span>Galerie</span>
                </a>
            </nav>
        </div>
        
        <a href="logout.php" class="flex items-center space-x-3 p-3 rounded-xl text-rose-700 hover:bg-rose-700/10 transition font-bold mt-auto">
            <i class="fas fa-sign-out-alt w-5"></i> <span>Déconnexion</span>
        </a>
    </aside>

    <div class="md:hidden bg-galaGold text-galaGreen px-4 py-3 flex items-center justify-between sticky top-0 z-50 shadow-md">
        <h1 class="text-lg font-black text-galaGreen">Gala<span class="text-[#E30613]"> Admin</span></h1>
        <div class="flex space-x-1 items-center">
            <a href="messages.php" class="p-2 text-xl text-slate-700"><i class="fas fa-envelope"></i></a>
            <a href="products_manager.php" class="p-2 text-xl text-white bg-galaDark rounded-xl px-3"><i class="fas fa-box"></i></a>
            <a href="gallery.php" class="p-2 text-xl text-slate-700"><i class="fas fa-images"></i></a>
            <a href="logout.php" class="p-2 text-xl text-rose-700"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
  
    <main class="flex-1 p-4 md:p-10 w-full overflow-x-hidden">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-black text-slate-800">Messages Reçus</h2>
                <p class="text-sm text-slate-400 mt-1">Interactions prospects via formulaire vitrine</p>
            </div>
            <span class="bg-galaGreen/10 text-galaGreen px-4 py-2 rounded-xl text-sm font-black border border-galaGreen/20">
                <?= $total_items ?> Message(s)
            </span>
        </div>

        <div class="w-full bg-white rounded-2xl md:rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto block w-full">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase bg-slate-50/50">
                            <th class="p-5 w-16">Nº</th>
                            <th class="p-5">Expéditeur</th>
                            <th class="p-5">Téléphone</th>
                            <th class="p-5">Message</th>
                            <th class="p-5">Date d'envoi</th>
                            <th class="p-5 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-400 font-semibold">Aucun message reçu pour le moment.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($messages as $key => $m): ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-5 font-semibold text-slate-400"><?= $offset + $key + 1 ?></td>
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

            <?php if ($total_pages > 1): ?>
            <div class="p-5 border-t border-slate-100 bg-slate-50/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                    Page <?= $page ?> sur <?= $total_pages ?>
                </span>
                
                <div class="flex items-center space-x-1">
                    <a href="?p=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold transition hover:bg-slate-50 flex items-center <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
                        <i class="fas fa-chevron-left mr-1"></i> Précédent
                    </a>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3 py-1.5 rounded-xl bg-galaGreen text-white text-xs font-black shadow-sm border border-galaGreen select-none">
                                <?= $i ?>
                            </span>
                        <?php else: ?>
                            <a href="?p=<?= $i ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold transition hover:bg-slate-50">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <a href="?p=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold transition hover:bg-slate-50 flex items-center <?= $page >= $total_pages ? 'pointer-events-none opacity-40' : '' ?>">
                        Suivant <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>