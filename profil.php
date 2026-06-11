<?php
// 1. Initialisation de la session et connexion base de données
session_start();
require_once 'includes/db.php'; // Remplacez par le chemin de votre fichier de connexion PDO

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Espace | Gala Agro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        // Scripts nécessaires pour les modals
        function openAvisModal(orderId) {
            document.getElementById('avisModal').classList.remove('hidden');
            document.getElementById('order_id').value = orderId;
        }
    </script>
</head>
<body class="bg-slate-50">

    <!-- Header (Exemple simple) -->
    <header class="p-6 bg-white shadow-sm text-center">
        <h1 class="text-2xl font-black text-galaDark">Mon Espace Client</h1>
    </header>

    <!-- SECTION MES COMMANDES -->
    <section id="mes-commandes" class="py-12">
        <div class="max-w-4xl mx-auto px-5">
            <h2 class="text-2xl font-black mb-8 text-galaDark">Mes Commandes</h2>
            <?php 
            $stmt = $pdo->prepare("SELECT * FROM commandes WHERE client_id = ? ORDER BY date_commande DESC");
            $mesCommandes = $stmt->fetchAll();

            if(count($mesCommandes) > 0) {
                foreach($mesCommandes as $c): ?>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-4 flex justify-between items-center">
                        <div>
                            <p class="font-bold">Commande n°<?= $c['id'] ?></p>
                            <p class="text-sm text-slate-500"><?= $c['date_commande'] ?></p>
                        </div>
                        <button onclick="openAvisModal(<?= $c['id'] ?>)" class="px-4 py-2 bg-galaGreen text-white rounded-lg text-sm font-bold hover:bg-green-700 transition">
                            Donner mon avis
                        </button>
                    </div>
                <?php endforeach; 
            } else {
                echo "<p class='text-slate-500'>Vous n'avez pas encore passé de commande.</p>";
            }
            ?>
        </div>
    </section>

    <!-- SECTION AVIS CLIENTS -->
    <section id="avis-clients" class="py-24 bg-slate-100">
        <div class="max-w-7xl mx-auto px-5">
            <h2 class="text-3xl font-black text-center mb-12 text-galaDark">Ce qu'ils pensent de nous</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php
                $avis = $pdo->query("SELECT * FROM avis_clients ORDER BY date_avis DESC LIMIT 3")->fetchAll();
                foreach($avis as $a): ?>
                    <div class="bg-white p-6 rounded-2xl shadow-lg">
                        <div class="text-amber-400 mb-2">
                            <?php for($i=0; $i<$a['note']; $i++) echo "★"; ?>
                        </div>
                        <p class="text-slate-600 text-sm mb-4">"<?= htmlspecialchars($a['commentaire']) ?>"</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- MODAL AVIS (caché par défaut) -->
    <div id="avisModal" class="hidden fixed inset-0 z-[120] flex items-center justify-center bg-slate-900/80 p-4">
        <div class="bg-white max-w-md w-full p-8 rounded-[2rem] shadow-2xl">
            <h3 class="text-xl font-black mb-4">Votre avis nous aide !</h3>
            <form action="traitement_avis.php" method="POST">
                <input type="hidden" name="order_id" id="order_id" value="">
                <input type="number" name="note" min="1" max="5" value="5" class="w-full p-2 border mb-4">
                <textarea name="commentaire" class="w-full p-4 bg-slate-50 rounded-xl mb-4" placeholder="Votre avis..."></textarea>
                <button type="submit" class="w-full py-3 bg-galaDark text-white rounded-xl font-bold">Envoyer</button>
            </form>
        </div>
    </div>

</body>
</html>