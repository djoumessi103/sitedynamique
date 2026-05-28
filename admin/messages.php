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
$items_per_page = 5; 
$page = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $items_per_page;

$total_items = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

if ($page > $total_pages && $total_pages > 0) { 
    $page = $total_pages; 
    $offset = ($page - 1) * $items_per_page; 
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des Messages</title>
    <script src="../assets/tailwind.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a', galaGold: '#f8f9f8f1', } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans min-h-screen flex flex-col md:flex-row">

    <!-- SIDEBAR -->
    <aside class="hidden md:flex w-64 bg-galaGold text-galaGreen p-6 flex-col justify-between shadow-xl min-h-screen sticky top-0">
        <div>
            <div class="mb-10">
                <h1 class="text-2xl font-black text-galaGreen">Gala<span class="text-[#E30613]"> Admin</span></h1>
                <p class="text-xs font-bold text-slate-700/80">Espace Restreint</p>
            </div>
            <nav class="space-y-2">
                <a href="messages.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'messages.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5'?>">
                    <i class="fas fa-envelope w-5"></i> <span>Messages</span>
                </a>
                <a href="products_manager.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'products_manager.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5' ?>">
                    <i class="fas fa-box w-5"></i> <span>Produits</span>
                </a>
                <a href="gallery.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'gallery.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5' ?>">
                    <i class="fas fa-images w-5"></i> <span>Galerie</span>
                </a>
                <a href="http://localhost/sitedynamique/index.php#accueil" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'http://localhost/sitedynamique/index.php#accueil') ? 'bg-galaGreen text-white shadow-md' : 'text-[#E30613] hover:bg-black/5' ?>">
                    <i class="fas fa-images w-5"></i> <span>Consulter le site</span>
                </a>
            </nav>
        </div>
        <a href="logout.php" class="flex items-center space-x-3 p-3 rounded-xl text-rose-700 hover:bg-rose-700/10 transition font-bold mt-auto">
            <i class="fas fa-sign-out-alt w-5"></i> <span>Déconnexion</span>
        </a>
    </aside>

    <!-- NAV HAUT (Mobile) -->
    <div class="md:hidden bg-galaGold text-galaGreen px-4 py-3 flex items-center justify-between sticky top-0 z-50 shadow-md">
        <h1 class="text-lg font-black text-galaGreen">Gala<span class="text-[#E30613]"> Admin</span></h1>
        <div class="flex space-x-1 items-center">
            <a href="messages.php" class="p-2.5 text-lg rounded-xl transition <?= ($current_page == 'messages.php') ? 'bg-galaGreen text-white shadow-sm' : 'text-slate-700' ?>"><i class="fas fa-envelope"></i></a>
            <a href="products_manager.php" class="p-2.5 text-lg rounded-xl transition <?= ($current_page == 'products_manager.php') ? 'bg-galaGreen text-white shadow-sm' : 'text-slate-700' ?>"><i class="fas fa-box"></i></a>
            <a href="gallery.php" class="p-2.5 text-lg rounded-xl transition <?= ($current_page == 'gallery.php') ? 'bg-galaGreen text-white shadow-sm' : 'text-slate-700' ?>"><i class="fas fa-images"></i></a>
            <a href="logout.php" class="p-2.5 text-lg text-rose-600 rounded-xl hover:bg-rose-50 transition"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
  
    <!-- CONTENU PRINCIPAL -->
    <main class="flex-1 p-4 sm:p-6 md:p-10 w-full overflow-x-hidden">
        
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-black text-slate-800">Messages Reçus</h2>
                <p class="text-sm text-slate-400 mt-1">Interactions prospects via formulaire vitrine</p>
            </div>
            <div>
                <span class="bg-galaGreen/10 text-galaGreen px-4 py-2 rounded-xl text-sm font-black border border-galaGreen/20 inline-block">
                    <?= $total_items ?> Message(s)
                </span>
            </div>
        </div>

        <!-- TABLEAU REVISITÉ -->
        <div class="w-full bg-white rounded-2xl md:rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse table-auto">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase bg-slate-50/50">
                            <th class="p-4 md:p-5 w-12 hidden lg:table-cell">Nº</th>
                            <th class="p-4 md:p-5">Expéditeur & Message</th>
                            <th class="p-4 md:p-5 hidden sm:table-cell">Téléphone</th>
                            <th class="p-4 md:p-5 hidden md:table-cell">Date d'envoi</th>
                            <th class="p-4 md:p-5 text-center w-40">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="5" class="p-10 text-center text-slate-400 font-semibold">Aucun message reçu pour le moment.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($messages as $key => $m): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            
                            <!-- Index -->
                            <td class="p-4 md:p-5 font-semibold text-slate-400 hidden lg:table-cell">
                                <?= $offset + $key + 1 ?>
                            </td>
                            
                            <!-- Expéditeur + Message -->
                            <td class="p-4 md:p-5 max-w-0 sm:max-w-xs md:max-w-md lg:max-w-lg">
                                <div class="font-bold text-slate-800 text-sm md:text-base flex flex-wrap items-center gap-2">
                                    <span><?= htmlspecialchars($m['nom_complet']) ?></span>
                                    <span class="text-[10px] font-medium text-slate-400 md:hidden block">
                                        • <?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?>
                                    </span>
                                </div>
                                <div class="text-xs md:text-sm text-slate-500 mt-1 truncate">
                                    <?= htmlspecialchars($m['message']) ?>
                                </div>
                            </td>
                            
                            <!-- Téléphone -->
                            <td class="p-4 md:p-5 whitespace-nowrap hidden sm:table-cell">
                                <a href="tel:<?= $m['telephone'] ?>" class="text-galaGreen hover:underline font-semibold text-sm flex items-center space-x-2">
                                    <i class="fas fa-phone-alt text-[10px]"></i> <span><?= htmlspecialchars($m['telephone']) ?></span>
                                </a>
                            </td>
                            
                            <!-- Date -->
                            <td class="p-4 md:p-5 text-xs md:text-sm text-slate-400 whitespace-nowrap hidden md:table-cell">
                                <?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?>
                            </td>
                            
                            <!-- ACTIONS INDÉPENDANTES (Sans bug de clic) -->
                            <td class="p-4 md:p-5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-2">
                                    
                                    <!-- Bouton LIRE -->
                                    <button 
                                        type="button"
                                        onclick="openMessageModal(<?= htmlspecialchars(json_encode($m['nom_complet'], JSON_HEX_APOS|JSON_HEX_QUOT)) ?>, <?= htmlspecialchars(json_encode($m['telephone'], JSON_HEX_APOS|JSON_HEX_QUOT)) ?>, <?= htmlspecialchars(json_encode($m['message'], JSON_HEX_APOS|JSON_HEX_QUOT)) ?>, '<?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?>')" 
                                        class="bg-slate-100 hover:bg-galaGreen hover:text-white text-slate-700 text-xs font-bold px-3 py-1.5 rounded-lg transition shadow-sm flex items-center space-x-1">
                                        <i class="fas fa-eye text-[11px]"></i> <span>Lire</span>
                                    </button>
                                    
                                    <!-- Bouton SUPPRIMER (Explicite et visible) -->
                                    <a 
                                        href="delete_message.php?id=<?= $m['id'] ?>" 
                                        onclick="return confirm('Confirmer la suppression définitive de ce message ?');" 
                                        class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white p-2 rounded-lg transition shadow-sm text-xs" 
                                        title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="p-4 md:p-5 border-t border-slate-100 bg-slate-50/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                    Page <?= $page ?> sur <?= $total_pages ?>
                </span>
                
                <div class="flex items-center space-x-1 justify-center">
                    <a href="?p=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold transition hover:bg-slate-50 <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
                        <i class="fas fa-chevron-left mr-1"></i> Précédent
                    </a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?p=<?= $i ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $i == $page ? 'bg-galaGreen text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <a href="?p=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold transition hover:bg-slate-50 <?= $page >= $total_pages ? 'pointer-events-none opacity-40' : '' ?>">
                        Suivant <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ==========================================
         MODALE DE LECTURE FIABILISÉE
         ========================================== -->
    <div id="messageModal" class="hidden fixed inset-0 z-[999] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <!-- Fond sombre cliquable pour fermer -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeMessageModal()"></div>

            <!-- Box Modale -->
            <div class="relative inline-block bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-slate-100 z-50">
                <div class="bg-white p-6 sm:p-8">
                    
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-900" id="modalNom">---</h3>
                            <p class="text-xs font-medium text-slate-400 mt-1" id="modalDate">---</p>
                        </div>
                        <button type="button" onclick="closeMessageModal()" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl bg-slate-50 transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 mb-6">
                        <p class="text-sm md:text-base text-slate-700 leading-relaxed whitespace-pre-wrap" id="modalCorps">---</p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Contact direct :</span>
                        <a id="modalTelHref" href="#" class="bg-galaGreen text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md hover:bg-galaGreen/90 transition flex items-center space-x-2">
                            <i class="fas fa-phone-alt text-xs"></i> <span id="modalTelTxt">---</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS MODALE -->
    <script>
        function openMessageModal(nom, tel, message, dateEnvoi) {
            document.getElementById('modalNom').textContent = nom;
            document.getElementById('modalDate').innerHTML = '<i class="far fa-clock mr-1"></i> Reçu le ' + dateEnvoi;
            document.getElementById('modalCorps').textContent = message;
            document.getElementById('modalTelTxt').textContent = tel;
            document.getElementById('modalTelHref').href = 'tel:' + tel;

            document.getElementById('messageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMessageModal() {
            document.getElementById('messageModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") closeMessageModal();
        });
    </script>
</body>
</html>