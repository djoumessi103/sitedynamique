<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
require_once '../includes/db.php';

$error = ""; $success = "";

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
        $error = "Format invalide (JPG, JPEG, PNG, WEBP uniquement).";
    }
}

$photos = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Galerie Médias</title>
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
                <h1 class="text-2xl font-black text-galaGreen">Mayonnaise Gala Admin </h1>
                <p class="text-xs text-slate-400"></p>
            </div>
            <nav class="space-y-2">
                <a href="messages.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition">
                    <i class="fas fa-envelope"></i> <span>Messages</span>
                </a>
                <a href="products_manager.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition">
                    <i class="fas fa-box-open"></i> <span>Produits & Stocks</span>
                </a>
                <a href="gallery.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl bg-galaGreen font-bold text-white shadow-md">
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
        <h2 class="text-3xl font-black text-slate-800 mb-8">Gestion Visuelle de la Galerie</h2>
        
        <?php if($error): ?>
            <div class="bg-rose-50 text-rose-700 p-4 rounded-xl mb-4 font-semibold border border-rose-100 text-sm"><?= $error ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl mb-4 font-semibold border border-emerald-100 text-sm"><?= $success ?></div>
        <?php endif; ?>
        
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm mb-10">
            <form method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Légende de la photo</label>
                    <input type="text" name="titre" class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-galaGreen/50 outline-none transition" required>
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fichier Média</label>
                    <input type="file" name="photo" class="w-full p-2 bg-slate-50 rounded-xl border border-dashed border-slate-200" required>
                </div>
                <button type="submit" class="bg-galaGreen hover:bg-galaDark text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 shadow-md w-full md:w-auto">
                    Mettre en ligne
                </button>
            </form>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ($photos as $p): ?>
            <div class="relative group h-44 bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100">
                <img src="../assets/img/gallery/<?= htmlspecialchars($p['image_url']) ?>" class="w-full h-full object-contain ">
                <div class="absolute inset-0 bg-galaDark/80 opacity-0 group-hover:opacity-100 transition duration-300 flex flex-col justify-between p-4 text-center">
                    <p class="text-white text-xs font-bold truncate"><?= htmlspecialchars($p['titre']) ?></p>
                    <a href="delete_photo.php?id=<?= $p['id'] ?>" onclick="return confirm('Confirmer le retrait immédiat de ce média ?');" class="text-white bg-rose-600 hover:bg-rose-700 h-9 w-9 rounded-full flex items-center justify-center mx-auto shadow-lg transition">
                        <i class="fas fa-trash-alt text-sm"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>