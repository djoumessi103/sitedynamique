<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
require_once '../includes/db.php';

$error = ""; $success = "";
$current_page = basename($_SERVER['PHP_SELF']);

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
        } else {
            $error = "Échec du transfert sur le serveur.";
        }
    } else {
        $error = "Format de fichier non accepté (JPG, PNG, WEBP uniquement).";
    }
}

$photos = $pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin - Galerie Médias</title>
    <script src="https://cdn.tailwindcss.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a', galaGold: '#f8f9f8f1',} } } }
    </script>
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
        <div class="mb-8">
            <h2 class="text-2xl md:text-3xl font-black text-slate-800">Galerie photo dynamique</h2>
            <p class="text-slate-400 text-sm">Gérez les médias affichés dans le carrousel de la page d'accueil.</p>
        </div>

        <?php if($success): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 text-sm font-semibold border border-green-100"><?= $success ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="bg-rose-50 text-rose-700 p-4 rounded-xl mb-6 text-sm font-semibold border border-rose-100"><?= $error ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-2xl md:rounded-[2.5rem] shadow-sm border border-slate-100 mb-10">
            <form method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-end gap-4">
                <div class="w-full md:flex-1">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Légende / Titre</label>
                    <input type="text" name="titre" placeholder="Ex: Notre équipe de production" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-galaGreen/20 transition" required>
                </div>
                <div class="w-full md:w-auto">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Fichier Média</label>
                    <input type="file" name="photo" class="w-full p-2 bg-slate-50 rounded-xl border border-dashed border-slate-200" required>
                </div>
                <button type="submit" class="bg-galaGreen hover:bg-galaDark text-white px-6 py-3 rounded-xl font-bold transition shadow-md w-full md:w-auto h-[46px]">
                    Mettre en ligne
                </button>
            </form>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($photos as $p): ?>
            <div class="relative group h-44 bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100">
                <img src="../assets/img/gallery/<?= htmlspecialchars($p['image_url']) ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-galaDark/80 opacity-0 group-hover:opacity-100 transition duration-300 flex flex-col justify-between p-4 text-center">
                    <p class="text-white text-xs font-bold truncate"><?= htmlspecialchars($p['titre']) ?></p>
                    <a href="delete_photo.php?id=<?= $p['id'] ?>" onclick="return confirm('Confirmer le retrait immédiat de ce média ?');" class="text-white bg-rose-600 hover:bg-rose-700 h-9 w-9 rounded-full flex items-center justify-center mx-auto shadow-md transition">
                        <i class="fas fa-trash-alt text-sm"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>