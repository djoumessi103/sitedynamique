<?php
require_once '../includes/db.php';
$current_page = 'voir_candidatures.php';

$query = $pdo->query("SELECT * FROM candidatures ORDER BY created_at DESC");
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @media print {
        /* 1. Masquer les éléments inutiles à l'impression */
        aside, .md\:hidden, header, .no-print, .print-hidden {
            display: none !important;
        }
/* Masque tout ce qui porte la classe no-print */
        .no-print {
            display: none !important;
        }
        /* 2. Afficher l'entête spécifique à l'impression */
        .print-header {
            display: block !important;
        }

        /* 3. Mise en page générale pour le format A4 */
        body { background: white !important; padding: 0 !important; }
        main { padding: 0 !important; width: 100% !important; margin: 0 !important; }
        .bg-white { border: none !important; box-shadow: none !important; }

        /* 4. Style du tableau pour le papier */
        table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px !important; }
        th, td { border: 1px solid #cbd5e1 !important; padding: 10px !important; font-size: 12px !important; }
        thead { background-color: #f8fafc !important; }

        /* 5. Rendre le formulaire de statut lisible sur papier */
        select { 
            -webkit-appearance: none !important; 
            border: none !important; 
            font-weight: bold !important; 
            background: none !important; 
        }
    }
</style>
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
   <!-- 1. L'entête imprimable est sorti du header -->
<div class="print-header hidden mb-8 border-b-2 border-galaGreen pb-4">
    <div class="flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Mayonnaise GALA</h1>
            <p class="text-sm text-slate-500">Service des Ressources Humaines</p>
        </div>
        <div class="text-right">
            <h2 class="text-xl font-bold text-slate-800">Liste des candidatures</h2>
            <p class="text-xs text-slate-400">Date d'édition : <?= date('d/m/Y') ?></p>
        </div>
    </div>
</div>

<!-- 2. Le header normal avec le bouton -->
<header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h2 class="text-2xl md:text-3xl font-black text-slate-800">Candidatures reçues</h2>
        <p class="text-slate-500 text-sm md:text-base">Gérez les dossiers des nouveaux talents.</p>
    </div>
    <button onclick="window.print()" class=" flex items-center justify-center space-x-2 bg-slate-900 hover:bg-galaGreen text-white px-6 py-2.5 rounded-xl font-bold transition-all duration-300 shadow-lg text-sm">
        <i class="fas fa-print"></i> 
        <span>Imprimer la liste</span>
    </button>
</header>

            <div class="bg-white rounded-2xl md:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[700px] text-left">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Candidat</th>
                    <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Poste</th>
                    <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Contact</th>
                    <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center">Statut</th>
                    <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center">Documents</th>
                    <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center no-print">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach($candidatures as $c): ?>
                <tr data-id="<?= $c['id'] ?>" class="transition-all duration-500 hover:bg-slate-50">
                    <td class="p-4 md:p-5 font-semibold text-slate-800 text-sm"><?= htmlspecialchars($c['nom_complet']) ?></td>
                    <td class="p-4 md:p-5 text-slate-600 text-sm"><?= htmlspecialchars($c['poste']) ?></td>
                    <td class="p-4 md:p-5 text-xs md:text-sm text-slate-500">
                        <div class="truncate max-w-[150px]"><?= htmlspecialchars($c['email']) ?></div>
                        <div class="font-bold text-galaDark"><?= htmlspecialchars($c['telephone']) ?></div>
                    </td>
                    
                    <td class="p-4 md:p-5 text-center">
                        <select onchange="updateStatut(<?= $c['id'] ?>, this.value)" 
                                class="text-[10px] font-black uppercase px-3 py-1 rounded-full cursor-pointer outline-none transition
                                <?php echo ($c['statut'] == 'Validé') ? 'bg-green-100 text-green-700' : (($c['statut'] == 'Refusé') ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'); ?>">
                            <option value="En attente" <?= $c['statut'] == 'En attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="Validé" <?= $c['statut'] == 'Validé' ? 'selected' : '' ?>>Validé</option>
                            <option value="Refusé" <?= $c['statut'] == 'Refusé' ? 'selected' : '' ?>>Refusé</option>
                        </select>
                    </td>

                    <td class="p-4 md:p-5 flex justify-center items-center gap-2">
                        <a href="../uploads/candidatures/<?= $c['cv_url'] ?>" target="_blank" class="p-2 md:px-4 md:py-2 bg-galaGreen text-white rounded-lg text-xs font-bold hover:bg-green-700 transition">
                            <i class="fas fa-file-pdf"></i><span class="hidden md:inline ml-1">CV</span>
                        </a>
                        <a href="../uploads/candidatures/<?= $c['lettre_url'] ?>" target="_blank" class="p-2 md:px-4 md:py-2 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-black transition">
                            <i class="fas fa-file-alt"></i><span class="hidden md:inline ml-1">Lettre</span>
                        </a>
                    </td>
          <td class="p-6 text-center no-print">
    <button onclick="supprimerCandidature(<?= $c['id'] ?>)" 
            class="group flex items-center justify-center w-8 h-8 rounded-full bg-red-50 hover:bg-red-600 transition-all duration-300">
        <i class="fas fa-trash-alt text-red-500 group-hover:text-white transition-colors"></i>
    </button>
</td>
    </tr>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
     </div>
            </div>
        </div>
    </main>
<script>
function updateStatut(id, nouveauStatut) {
    fetch('update_candidature_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&statut=${nouveauStatut}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Recharge la page pour mettre à jour les couleurs des badges
            location.reload(); 
        } else {
            alert("Erreur lors de la mise à jour.");
        }
    });
}

function supprimerCandidature(id) {
    Swal.fire({
        title: 'Supprimer ce candidat ?',
        text: "Cette action est irréversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
        customClass: { popup: 'rounded-3xl' }
    }).then((result) => {
        if (result.isConfirmed) {
            // Appel AJAX pour supprimer en BDD
            fetch('supprimer_candidature.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Animation de disparition
                    const row = document.querySelector(`tr[data-id='${id}']`);
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 500);
                    
                    Swal.fire('Supprimé !', 'Le candidat a été retiré.', 'success');
                }
            });
        }
    });
}

</script>
</body>
</html>