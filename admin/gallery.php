<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
require_once '../includes/db.php';

$error = ""; $success = "";
$current_page = basename($_SERVER['PHP_SELF']);

// ==========================================
// CONFIGURATION DE LA PAGINATION
// ==========================================
$items_per_page = 8; // Nombre maximum de photos par page
$page = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $items_per_page;

// Calcul du nombre total de photos
$total_items = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

if ($page > $total_pages && $total_pages > 0) { 
    $page = $total_pages; 
    $offset = ($page - 1) * $items_per_page; 
}
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $titre = htmlspecialchars($_POST['titre']);
    $file_name = $_FILES['photo']['name'];
    $file_tmp = $_FILES['photo']['tmp_name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'webp');

    if (in_array($ext, $allowed)) {
        $new_name = uniqid('gala_', true) . '.' . $ext;
        $target = "../assets/img/gallery/" . $new_name;

        if (!is_dir('../assets/img/gallery/')) {
            mkdir('../assets/img/gallery/', 0775, true);
        }

        if (move_uploaded_file($file_tmp, $target)) {
            $stmt = $pdo->prepare("INSERT INTO gallery (titre, image_url) VALUES (?, ?)");
            $stmt->execute([$titre, $new_name]);
            $success = "Image ajoutée à la galerie avec succès !";
            
            // Re-calculer le total après insertion pour une pagination juste
            $total_items = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
            $total_pages = ceil($total_items / $items_per_page);
        } else {
            $error = "Échec du transfert sur le serveur.";
        }
    } else {
        $error = "Format de fichier non accepté (JPG, PNG, WEBP uniquement).";
    }
}

// Récupération des photos pour la page actuelle uniquement
$stmt = $pdo->prepare("SELECT * FROM gallery ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$photos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin - Galerie Médias</title>
    <script src="../assets/tailwind.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a', galaGold: '#f8f9f8f1', } } } }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    <div class="md:hidden bg-galaGold text-galaGreen px-4 py-3 flex items-center justify-between sticky top-0 z-50 shadow-md">
        <h1 class="text-lg font-black text-galaGreen">Gala<span class="text-[#E30613]"> Admin</span></h1>
        <div class="flex space-x-1 items-center">
            <a href="messages.php" class="p-2 text-xl text-slate-700"><i class="fas fa-envelope"></i></a>
            <a href="products_manager.php" class="p-2 text-xl text-slate-700"><i class="fas fa-box"></i></a>
            <a href="gallery.php" class="p-2 text-xl text-white bg-galaDark rounded-xl px-3"><i class="fas fa-images"></i></a>
              <a href="voir_candidatures.php" class="p-2 text-lg rounded-xl transition <?= ($current_page == 'voir_candidatures.php') ? 'bg-galaGreen text-white shadow-sm' : 'text-slate-700' ?>"><i class="fas fa-users"></i></a>
        <a href="admin_commandes.php" class="p-2 text-lg rounded-xl transition <?= ($current_page == 'admin_commandes.php') ? 'bg-galaGreen text-white shadow-sm' : 'text-slate-700' ?>"><i class="fas fa-shopping-cart"></i></a>
        <a href="http://localhost/sitedynamique/index.php#accueil" class="p-2 text-lg text-[#E30613] rounded-xl hover:bg-black/5 transition"><i class="fas fa-globe"></i></a>
            <a href="logout.php" class="p-2 text-xl text-rose-700"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
  
    <main class="flex-1 p-4 md:p-10 w-full overflow-x-hidden flex flex-col justify-between">
        <div>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-2">
                <div>
                    <h2 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight">Galerie photo dynamique</h2>
                    <p class="text-slate-400 text-sm mt-0.5">Gérez les médias affichés dans le carrousel de la page d'accueil.</p>
                </div>
                <span class="bg-galaGreen/10 text-galaGreen px-4 py-1.5 rounded-xl text-xs font-black border border-galaGreen/10 uppercase tracking-wider">
                    <?= $total_items ?> Média(s)
                </span>
            </div>

          <?php if (!empty($success)): ?>
    <div class="auto-dismiss-alert bg-emerald-50 text-emerald-700 p-4 rounded-xl mb-6 border border-emerald-100 text-center font-semibold shadow-sm transition-all duration-500 transform ease-in-out">
        <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="auto-dismiss-alert bg-rose-50 text-rose-700 p-4 rounded-xl mb-6 border border-rose-100 text-center font-semibold shadow-sm transition-all duration-500 transform ease-in-out">
        <i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?>
    </div>
<?php endif; ?>

            <div class="bg-white p-6 rounded-2xl md:rounded-[2rem] shadow-sm border border-slate-100 mb-10 transition-all hover:shadow-md">
                <form method="POST" enctype="multipart/form-data" class="flex flex-col lg:flex-row items-end gap-5">
                    <div class="w-full lg:flex-1">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Légende / Titre du média</label>
                        <input type="text" name="titre" placeholder="Ex: Notre équipe de production..." class="w-full p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl outline-none focus:ring-2 focus:ring-galaGreen/20 focus:border-galaGreen bg-white transition font-medium" required>
                    </div>
                    
                    <div class="w-full lg:max-w-xs relative">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-wider mb-2">Fichier Média</label>
                        <div class="relative group/file cursor-pointer">
                            <input type="file" name="photo" id="photoInput" class="absolute inset-0 w-full h-full opacity-0 z-20 cursor-pointer" required onchange="updateFileName(this)">
                            <div class="w-full p-3 bg-slate-50 rounded-xl border border-dashed border-slate-300 text-slate-500 group-hover/file:bg-slate-100/70 group-hover/file:border-galaGreen transition flex items-center justify-center space-x-2 text-sm font-semibold h-[50px]">
                                <i class="fas fa-cloud-upload-alt text-galaGreen group-hover/file:scale-110 transition duration-300"></i>
                                <span id="fileLabel" class="truncate max-w-[180px]">Choisir une image</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="bg-galaGreen hover:bg-galaDark text-white px-8 py-3.5 rounded-xl font-black transition-all duration-300 shadow-md hover:shadow-lg active:scale-95 w-full lg:w-auto h-[50px] flex items-center justify-center space-x-2 text-sm tracking-wide">
                        <i class="fas fa-paper-plane text-xs"></i> <span>Mettre en ligne</span>
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-10">
                <?php if (empty($photos)): ?>
                    <div class="col-span-full bg-white p-16 text-center rounded-2xl border border-slate-100 font-semibold text-slate-400 shadow-sm">
                        <i class="fas fa-images text-3xl mb-3 text-slate-300 block"></i>
                        Aucune photo présente dans la galerie.
                    </div>
                <?php endif; ?>

                <?php foreach ($photos as $p): ?>
                <div class="relative group h-52 bg-slate-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl border border-slate-200/60 transition-all duration-500">
                    <img src="../assets/img/gallery/<?= htmlspecialchars($p['image_url']) ?>" class="w-full h-full object-contain transition-transform duration-700 ease-out group-hover:scale-110">
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-galaDark/95 via-galaDark/40 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col justify-end p-5">
                        <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 ease-out">
                            <p class="text-white text-sm font-black mb-4 truncate text-left tracking-wide drop-shadow-sm"><?= htmlspecialchars($p['titre']) ?></p>
                            <a href="delete_photo.php?id=<?= $p['id'] ?>" onclick="return confirm('Confirmer le retrait immédiat de ce média ?');" class="text-white bg-rose-600 hover:bg-rose-500 h-10 w-full rounded-xl flex items-center justify-center space-x-2 shadow-md transition-all duration-200 active:scale-95 font-bold text-xs uppercase tracking-wider">
                                <i class="fas fa-trash-alt text-xs"></i> <span>Supprimer</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="w-full bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 mt-auto">
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

    </main>

    <script>
    function updateFileName(input) {
        const label = document.getElementById('fileLabel');
        if (input.files && input.files.length > 0) {
            label.textContent = input.files[0].name;
            label.classList.remove('text-slate-500');
            label.classList.add('text-galaGreen');
        } else {
            label.textContent = "Choisir une image";
        }
        document.addEventListener("DOMContentLoaded", function() {
    // On sélectionne toutes les alertes ayant la classe 'auto-dismiss-alert'
    const alerts = document.querySelectorAll('.auto-dismiss-alert');
    
    alerts.forEach(function(alert) {
        // Le message reste visible pendant 4000 millisecondes (4 secondes)
        setTimeout(function() {
            
            // Étape A : Animation Tailwind de fondu (opacité à 0) et léger rétrécissement (scale)
            alert.classList.add('opacity-0', 'scale-95');
            
            // Étape B : Supprimer définitivement l'élément du DOM après la fin de l'animation (500ms)
            setTimeout(function() {
                alert.remove();
            }, 500);

        }, 4000); 
    });
});
.then(async response => {
    const text = await response.text();
    responseDiv.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700', 'opacity-0');
    responseDiv.classList.add('transition-all', 'duration-500'); // Pour garantir une transition fluide
    
    if (response.ok) {
        // Message de succès vert
        responseDiv.classList.add('bg-green-100', 'text-green-700');
        responseDiv.innerHTML = `<i class="fas fa-check-circle mr-2"></i> ${text}`;
        contactForm.reset(); // Vide le formulaire
        
        // --- RENDRE LE MESSAGE ÉPHÉMÈRE ---
        setTimeout(() => {
            responseDiv.classList.add('opacity-0'); // Effet de fondu
            setTimeout(() => {
                responseDiv.classList.add('hidden'); // Cache complètement
            }, 500);
        }, 4000); // Reste visible 4 secondes
        
    } else {
        // Message d'erreur rouge
        responseDiv.classList.add('bg-red-100', 'text-red-700');
        responseDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i> ${text}`;
        
        // --- RENDRE L'ERREUR ÉPHÉMÈRE ÉGALEMENT (Optionnel) ---
        setTimeout(() => {
            responseDiv.classList.add('opacity-0');
            setTimeout(() => { responseDiv.classList.add('hidden'); }, 500);
        }, 5000); // Laisse l'erreur 5 secondes pour donner le temps de lire
    }
})
    }
    </script>
</body>
</html>