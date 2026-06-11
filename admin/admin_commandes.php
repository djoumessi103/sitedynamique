<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
require_once '../includes/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Statistiques
$topClient = $pdo->query("SELECT nom, prenom, COUNT(*) as nb FROM commandes GROUP BY nom, prenom ORDER BY nb DESC LIMIT 1")->fetch();
$ventesParRegion = $pdo->query("SELECT region, COUNT(*) as ventes FROM commandes GROUP BY region")->fetchAll();
$commandes = $pdo->query("SELECT * FROM commandes ORDER BY date_commande DESC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard 2026</title>
    <script src="../assets/tailwind.js"></script>
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
  @media print {
    
    /* 1. Masque tous les éléments inutiles (Sidebar, menus, boutons) */
    body, body * {
        visibility: hidden;
        overflow: hidden !important; /* Force le masquage du débordement */
     
    }

    /* 2. Affiche uniquement la zone d'impression */
    #printableArea, #printableArea * {
        visibility: visible;
    }

    /* 3. Force la zone à prendre toute la largeur et supprime les barres de défilement */
    #printableArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        overflow: hidden !important; /* Empêche l'apparition de barres de défilement */
    }
/* Nettoyage des bordures arrondies à l'impression si nécessaire */
    #printableArea .bg-white {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    /* 4. Masque les boutons d'action (Supprimer, etc.) à l'impression */
    .no-print {
        display: none !important;
    }
    /* 5. Optimisation papier : bordures et police */
    table { border-collapse: collapse !important; width: 100% !important; }
    th, td { border: 1px solid #cbd5e1 !important; padding: 8px !important; }
    thead { background-color: #f8fafc !important; }
    /* 5. Supprime les marges par défaut du navigateur */
    @page {
        margin: 1cm;
    }
}

        canvas { max-height: 250px !important; }
        /* Style épuré type Amazon */
#statsPanel table { border-collapse: collapse; }
#statsPanel td { border-bottom: 1px solid #f1f5f9; padding: 12px 0; }
#statsPanel tr:last-child td { border-bottom: none; }

    </style>
</head>
<body class="bg-slate-50 min-h-screen flex">

    <aside class="w-64 bg-white p-6 shadow-xl hidden md:flex flex-col min-h-screen">
        <h1 class="text-2xl font-black text-green-700 mb-10">Gala Admin</h1>
        <nav class="space-y-4">
            <a href="#" class="block p-3 bg-green-600 text-white rounded-xl font-bold"><i class="fas fa-shopping-cart mr-2"></i> Finaliser Commandes</a>
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
                </a>
                <a href="http://localhost/sitedynamique/index.php#accueil" class="flex items-center space-x-3 p-3 rounded-xl transition font-semibold <?= ($current_page == 'http://localhost/sitedynamique/index.php#accueil') ? 'bg-galaGreen text-white shadow-md' : 'text-[#E30613] hover:bg-black/5' ?>">
                    <i class="fas fa-globe w-5"></i> <span>Consulter le site</span>
                </a>
            <a href="logout.php" class="flex items-center space-x-3 p-3 rounded-xl text-rose-700 hover:bg-rose-700/10 transition font-bold mt-auto">
            <i class="fas fa-sign-out-alt w-5"></i> <span>Déconnexion</span>
        </a>
        </nav>
    </aside>

    <main class="flex-1 p-4 md:p-10">
    <div class="mb-8">
    <button onclick="toggleStats()" class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-bold hover:bg-green-700 transition shadow-lg">
        <i class="fas fa-chart-pie mr-2"></i> Visualiser les Statistiques
    </button>

    <div id="statsPanel" class="hidden grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        <?php foreach(['Region' => 'Ventes par Région', 'Marche' => 'Ventes par Marché', 'Client' => 'Top Clients'] as $id => $title): ?>
        <div class="bg-white p-6 rounded-3xl shadow-lg border border-slate-100 flex flex-col">
            <h4 class="font-bold text-slate-800 mb-4 text-center"><?= $title ?></h4>
            <div class="h-48 mb-6"><canvas id="chart<?= $id ?>"></canvas></div>
            <div class="mt-auto border-t pt-4"><table class="w-full text-xs text-slate-600"><tbody id="table<?= $id ?>"></tbody></table></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-center bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
    
    <div class="lg:col-span-1">
        <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">
            <i class="fa fa-filter mr-2 text-green-600"></i> Filtres de recherche
        </h3>
        <p class="text-xs text-slate-400 font-bold uppercase mt-1">Gala Mayo - Admin 2026</p>
    </div>

    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <div class="relative">
            <select id="filterClient" onchange="filterData()" class="w-full appearance-none p-4 rounded-xl border-2 border-slate-100 bg-slate-50 font-bold text-slate-700 outline-none focus:border-green-500 transition-all">
                <option value="">👤 Tous les clients</option>
                <?php 
                $clients = $pdo->query("SELECT DISTINCT nom, prenom FROM commandes")->fetchAll();
                foreach($clients as $c) echo "<option value='{$c['nom']} {$c['prenom']}'>{$c['nom']} {$c['prenom']}</option>";
                ?>
            </select>
        </div>
        
        <div class="relative">
            <select id="filterMarche" onchange="filterData()" class="w-full appearance-none p-4 rounded-xl border-2 border-slate-100 bg-slate-50 font-bold text-slate-700 outline-none focus:border-green-500 transition-all">
                <option value="">📍 Tous les marchés</option>
                <?php 
                $marches = $pdo->query("SELECT DISTINCT nom_marche FROM commandes")->fetchAll();
                foreach($marches as $m) echo "<option value='{$m['nom_marche']}'>{$m['nom_marche']}</option>";
                ?>
            </select>
        </div>

        <div class="relative">
            <select id="filterRegion" onchange="filterData()" class="w-full appearance-none p-4 rounded-xl border-2 border-slate-100 bg-slate-50 font-bold text-slate-700 outline-none focus:border-green-500 transition-all">
                <option value="">🌍 Toutes les régions</option>
                <?php 
                $regions = $pdo->query("SELECT DISTINCT region FROM commandes")->fetchAll();
                foreach($regions as $r) echo "<option value='{$r['region']}'>{$r['region']}</option>";
                ?>
            </select>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-10 items-end">
    
    <div class="xl:col-span-1">
        <button onclick="window.print()" class="w-full flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white px-6 py-4 rounded-2xl font-bold transition-all shadow-lg hover:shadow-xl active:scale-95">
            <i class="fa fa-print mr-3"></i> Imprimer le rapport
        </button>
        <p class="text-[10px] text-slate-400 font-bold uppercase mt-3 text-center tracking-widest">Format optimisé A4</p>
    </div>

    <div class="xl:col-span-2">
        <div id="topClientCard" class="bg-gradient-to-r from-green-600 to-emerald-700 p-8 rounded-3xl text-white shadow-lg shadow-green-500/20 flex items-center justify-between transition-all hover:shadow-green-500/30">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-crown text-yellow-300"></i>
                    <h3 class="text-xs font-black uppercase opacity-90 tracking-widest">Top Client 2026</h3>
                </div>
                <p id="topClientName" class="text-2xl lg:text-3xl font-black">Chargement...</p>
            </div>
            
            <button onclick="accorderPrime()" class="bg-white/10 backdrop-blur-sm hover:bg-white text-white hover:text-emerald-800 px-6 py-3 rounded-2xl font-bold text-sm transition-all border border-white/20 hover:border-transparent shadow-lg">
                <i class="fas fa-gift mr-2"></i> Prime
            </button>
        </div>
    </div>
</div>
<div id="printableArea" class="w-full">
   <div class="mb-8 p-6 border-b-4 border-slate-800">
    <h2 class="text-4xl font-bold text-slate-800 text-center uppercase tracking-wider">
        Liste des finalisations commandes
    </h2>
</div>

    <div class="bg-white shadow-sm border border-slate-200 overflow-hidden w-full">
        <div class="overflow-x-auto">
            <table id="ordersTable" class="w-full border-collapse">
                <thead class="bg-slate-100 border-b-2 border-slate-200">
                    <tr class="text-left text-xs font-black text-slate-500 uppercase">
                        <th class="px-8 py-6 border-r border-slate-200">Date</th>
                        <th class="px-8 py-6 border-r border-slate-200">Client</th>
                        <th class="px-8 py-6 border-r border-slate-200">Info CNI</th>
                        <th class="px-6 py-3 border-r border-slate-200">N° Commercial</th>
                        <th class="px-8 py-6 border-r border-slate-200">Détails Panier</th>
                        <th class="px-8 py-6 border-r border-slate-200">Documents</th>
                        <th class="px-8 py-6 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
    <?php while ($c = $commandes->fetch()): ?>
    <tr class="hover:bg-slate-50 transition" 
        data-client="<?= strtolower(htmlspecialchars(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? ''))) ?>" 
        data-marche="<?= strtolower(htmlspecialchars($c['nom_marche'] ?? '')) ?>" 
        data-region="<?= strtolower(htmlspecialchars($c['region'] ?? '')) ?>">
        
        <td class="px-8 py-6 text-sm text-slate-500 font-bold border-r border-slate-200">
            <?= date('d/m/Y', strtotime($c['date_commande'])) ?>
        </td>
        
        <td class="px-8 py-6 border-r border-slate-200">
            <div class="font-bold"><?= htmlspecialchars(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? '')) ?></div>
            <div class="text-xs text-green-600 font-bold"><?= htmlspecialchars(($c['nom_marche'] ?? '') . ' - ' . ($c['region'] ?? '')) ?></div>
        </td>
        
        <td class="px-8 py-6 text-sm text-slate-600 font-mono border-r border-slate-200"><?= htmlspecialchars($c['cni'] ?? 'N/A') ?></td>
        
        <td class="px-6 py-4 text-sm font-bold border-r border-slate-200"><?= htmlspecialchars($c['num_commercial'] ?? 'N/A') ?></td>
        
        <td class="px-8 py-6 text-xs text-slate-600 max-w-[250px] whitespace-pre-line border-r border-slate-200">
            <?= htmlspecialchars($c['details_panier'] ?? '') ?>
        </td>
        
        <td class="px-8 py-6 space-y-2 border-r border-slate-200">
            <?php if (!empty($c['cni_file'])): ?>
                <a href="../uploads/<?= $c['cni_file'] ?>" target="_blank" class="block text-green-600 font-bold text-xs">Voir CNI</a>
            <?php endif; ?>
            <?php if (!empty($c['bon_commande'])): ?>
                <a href="../uploads/<?= $c['bon_commande'] ?>" target="_blank" class="block text-red-600 font-bold text-xs">Voir Bon</a>
            <?php endif; ?>
        </td>
        
        <td class="p-6 no-print text-center">
            <button onclick="supprimerLigne(<?php echo $c['id']; ?>)" class="text-red-600 font-bold">Supprimer</button>
        </td>
    </tr>
    <?php endwhile; ?>
</tbody>
            </table>
        </div>
    </div>
</div>
        </div>
    </main>

<script>
let charts = {};

function toggleStats() {
    const panel = document.getElementById('statsPanel');
    panel.classList.toggle('hidden');

    if (!panel.classList.contains('hidden') && !charts.region) {
        fetch('get_stats.php')
            .then(r => r.json())
            .then(data => {
                // 1. Mise à jour du Top Client (Sécurisée)
                if (data.topClient) {
                    const el = document.getElementById('topClientName');
                    if (el) el.innerText = data.topClient.nom + ' ' + data.topClient.prenom;
                }

                // 2. Définition de la fonction render
                const render = (id, dataArr, labelKey, valKey, color) => {
                    if (!dataArr || dataArr.length === 0) return;

                    // Nettoyage si le chart existe déjà
                    if (charts[id.toLowerCase()]) charts[id.toLowerCase()].destroy();

                    charts[id.toLowerCase()] = new Chart(document.getElementById('chart' + id), {
                        type: id === 'Region' ? 'doughnut' : 'bar',
                        data: { 
                            labels: dataArr.map(x => x[labelKey] || 'Inconnu'), 
                            datasets: [{ 
                                data: dataArr.map(x => x[valKey] || 0), 
                                backgroundColor: color 
                            }] 
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } }
                        }
                    });
                    
                    document.getElementById('table' + id).innerHTML = dataArr.map(x => 
                        `<tr class="border-b border-slate-50">
                            <td class="py-2">${x[labelKey] || 'Inconnu'}</td>
                            <td class="py-2 text-right font-black text-slate-800">${x[valKey] || 0}</td>
                         </tr>`
                    ).join('');
                };

                // 3. Exécution avec les bonnes clés
                render('Region', data.region, 'region', 'nb', ['#10b981', '#059669', '#34d399']);
                render('Marche', data.marche, 'nom_marche', 'nb', '#334155');
                
                // Préparation des données client concaténées pour le graphique
                const clientsFormates = data.client.map(c => ({
                    nom_complet: c.nom + ' ' + c.prenom,
                    nb_commandes: c.nb_commandes
                }));
                render('Client', clientsFormates, 'nom_complet', 'nb_commandes', '#f59e0b');
            })
            .catch(err => console.error("Erreur chargement stats:", err));
    }
}

function filterData() {
    // 1. Récupération des valeurs sélectionnées
    const clientVal = document.getElementById('filterClient').value.toLowerCase();
    const marcheVal = document.getElementById('filterMarche').value.toLowerCase();
    const regionVal = document.getElementById('filterRegion').value.toLowerCase();
    
    // 2. Sélection de toutes les lignes du tableau (sauf l'en-tête)
    const rows = document.querySelectorAll('#ordersTable tbody tr');

    rows.forEach(row => {
        // Récupération des attributs de données qu'on a mis sur le <tr>
        const client = row.getAttribute('data-client') || "";
        const marche = row.getAttribute('data-marche') || "";
        const region = row.getAttribute('data-region') || "";

        // 3. Logique de filtrage : on vérifie si la ligne correspond aux 3 filtres
        const matchClient = (clientVal === "" || client.includes(clientVal));
        const matchMarche = (marcheVal === "" || marche === marcheVal);
        const matchRegion = (regionVal === "" || region === regionVal);

        // 4. Affichage ou masquage de la ligne
        if (matchClient && matchMarche && matchRegion) {
            row.style.display = ""; // Affiche la ligne
        } else {
            row.style.display = "none"; // Cache la ligne
        }
    });
}
// 3. Tri du tableau (Date par défaut)
function trierTableau() {
    let tbody = document.querySelector("#ordersTable tbody");
    let rows = Array.from(tbody.querySelectorAll("tr"));
    rows.sort((a, b) => a.cells[0].innerText.localeCompare(b.cells[0].innerText));
    rows.forEach(r => tbody.appendChild(r));
}

async function supprimerLigne(id) {
    if(!confirm('Supprimer la commande ID ' + id + ' ?')) return;

    try {
        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('action', 'supprimer_tout'); // Garder pour cohérence avec votre PHP

        const response = await fetch('delete_cni.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // RAFRAÎCHISSEMENT FORCE : 
            // C'est le seul moyen de reconstruire le tableau PHP proprement 
            // sans modifier toute votre structure.
            window.location.reload(); 
        } else {
            alert("Erreur : " + result.message);
        }
    } catch (error) {
        console.error("Erreur:", error);
    }
}

// Exemple simple pour remplir le nom du top client
window.addEventListener('DOMContentLoaded', () => {
    // Supposons que vous récupériez le nom via une fonction ou PHP
    const topClient = "Dongmo Odette"; // Exemple
    document.getElementById('topClientName').innerText = topClient;
});

function accorderPrime() {
    alert("Prime accordée avec succès pour l'année 2026 !");
    // Ajoutez ici votre appel AJAX pour sauvegarder en base de données
}
// Fonction pour charger les données "Top Client" dès l'ouverture
function chargerTopClient() {
    fetch('get_stats.php')
        .then(r => r.json())
        .then(data => {
            if (data.topClient) {
                const el = document.getElementById('topClientName');
                if (el) {
                    el.innerText = data.topClient.nom + ' ' + data.topClient.prenom;
                }
            }
        })
        .catch(err => console.error("Erreur chargement auto :", err));
}

// Lancement automatique au chargement de la page
window.onload = chargerTopClient;
</script>
</body>
</html>