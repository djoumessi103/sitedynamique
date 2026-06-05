<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
// Vérification session admin (comme dans gallery.php)
if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
require_once '../includes/db.php'; // Chemin ajusté si admin est dans un sous-dossier
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupération des commandes
$commandes = $pdo->query("SELECT * FROM commandes ORDER BY date_commande DESC");
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
                <a href="voir_candidatures.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'voir_candidatures.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5'?>">
                    <i class="fas fa-users w-5"></i> <span>Candidatures</span>
                </a>
                <a href="admin_commandes.php" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'admin_commandes.php') ? 'bg-galaGreen text-white shadow-md' : 'text-slate-700 hover:bg-black/5'?>">
                    <i class="fas fa-shopping-cart w-5"></i> <span>Finaliser commandes</span>
                </a>
                <a href="http://localhost/sitedynamique/index.php#accueil" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'http://localhost/sitedynamique/index.php#accueil') ? 'bg-galaGreen text-white shadow-md' : 'text-[#E30613] hover:bg-black/5' ?>">
                    <i class="fas fa-globe w-5"></i> <span>Consulter le site</span>
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

<div class="max-w-7xl mx-auto px-4 py-12">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-black text-galaDark">Gestion des Commandes</h1>
    </div>

    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-8">
        <input type="text" id="searchInput" placeholder="Rechercher une commande (nom, marché, région...)" 
               class="w-full p-3 bg-slate-50 rounded-xl outline-none focus:ring-2 focus:ring-galaGreen">
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
         <table id="ordersTable" class="w-full">
    <thead class="bg-slate-50">
        <tr class="text-left text-xs font-black text-slate-500 uppercase">
            <th class="px-8 py-6">Date</th>
            <th class="px-8 py-6">Client</th>
            <th class="px-8 py-6">Info CNI</th>
            <th class="px-8 py-6">N° Commercial</th>
            <th class="px-8 py-6">Détails Panier</th>
            <th class="px-8 py-6">Documents</th>
            <th class="px-8 py-6">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        <?php while ($c = $commandes->fetch()): ?>
        <tr class="hover:bg-slate-50 transition">
            <td class="px-8 py-6 text-sm text-slate-500 font-bold"><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
            <td class="px-8 py-6">
                <div class="font-bold text-galaDark"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></div>
                <div class="text-xs text-galaGreen font-bold"><?= htmlspecialchars($c['nom_marche']) ?> - <?= htmlspecialchars($c['region']) ?></div>
            </td>
            <td class="px-8 py-6 text-sm text-slate-600 font-mono"><?= htmlspecialchars($c['cni']) ?></td>
            <td class="px-8 py-6 text-sm text-slate-600 font-mono"><?= htmlspecialchars($c['num_commercial'] ?: 'Non fourni') ?></td>
            <td class="px-8 py-6 text-xs text-slate-600 max-w-[250px]">
    <?php 
    // On vérifie si la valeur n'est pas 'null' (chaîne de texte) ou vide
    $details = $c['details_panier'];
    if (!empty($details) && $details !== 'null'): ?>
        <div class="whitespace-pre-line"><?= htmlspecialchars($details) ?></div>
    <?php else: ?>
        <span class="text-slate-400 italic">Aucun détail</span>
    <?php endif; ?>
</td>
            <td class="px-8 py-6 space-y-2">
                <?php 
                $cniFile = $c['cni_file'] ?? null;
                $bonFile = $c['bon_commande'] ?? null;
                ?>
                <?php if (!empty($cniFile) && $cniFile !== 'null'): ?>
                    <a href="../uploads/<?= htmlspecialchars($cniFile) ?>" target="_blank" class="block text-galaGreen font-bold underline text-xs">
                        <i class="fas fa-id-card mr-1"></i> Voir CNI
                    </a>
                    
                <?php else: ?>
                    <span class="block text-slate-300 text-xs italic">CNI manquante</span>
                <?php endif; ?>

                <?php if (!empty($bonFile) && $bonFile !== 'null'): ?>
                    <a href="../uploads/<?= htmlspecialchars($bonFile) ?>" target="_blank" class="block text-rose-600 font-bold underline text-xs">
                        <i class="fas fa-file-pdf mr-1"></i> Voir Bon
                    </a>
                <?php else: ?>
                    <span class="block text-slate-300 text-xs italic">Bon manquant</span>
                <?php endif; ?>
            </td>
      <td>
    <button type="button" 
            onclick="supprimerLigne(<?= $c['id'] ?>, this)" 
            class="text-rose-600 font-bold underline text-xs cursor-pointer hover:text-rose-800">
        <i class="fas fa-trash-alt mr-1"></i> Supprimer la ligne
    </button>
</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>  
        </div>
    </div>  
<script>
    // Supprimer toute la ligne (BDD + DOM)
    function supprimerLigne(id, element) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer toute cette commande ?')) return;

        fetch('delete_cni.php?id=' + id + '&action=supprimer_tout', {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Supprime la ligne du tableau
                element.closest('tr').remove();
            } else {
                alert('Erreur : ' + data.message);
            }
        })
        .catch(error => console.error('Erreur:', error));
    }
    // Filtre de recherche dynamique
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
</script>
</body>
</html>