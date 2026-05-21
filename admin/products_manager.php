<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
require_once '../includes/db.php';

$success = ""; $error = "";
$current_page = basename($_SERVER['PHP_SELF']);

// Traitement Ajout / Modification de Produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $format = htmlspecialchars($_POST['format']);
    $prix = (int)$_POST['prix'];
    $stock = (int)$_POST['stock'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // Gestion du téléversement de l'image
    $img = "default.png"; // Image par défaut
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Extensions autorisées
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = '../assets/img/';
            
            if(!is_dir($uploadFileDir)){
                mkdir($uploadFileDir, 0775, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $img = $newFileName;
            }
        }
    }

    if ($id > 0) {
        if ($img !== "default.png") {
            $sql = "UPDATE products SET nom=?, format=?, prix=?, stock=?, image_url=? WHERE id=?";
            $params = [$nom, $format, $prix, $stock, $img, $id];
        } else {
            $sql = "UPDATE products SET nom=?, format=?, prix=?, stock=? WHERE id=?";
            $params = [$nom, $format, $prix, $stock, $id];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $success = "Le produit a bien été mis à jour.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (nom, format, prix, stock, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $format, $prix, $stock, $img]);
        $success = "Nouveau produit ajouté avec succès.";
    }
}

// Suppression de Produit
if (isset($_GET['delete'])) {
    $idDel = (int)$_GET['delete'];
    $stmtImg = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmtImg->execute([$idDel]);
    $pImg = $stmtImg->fetch();
    if($pImg && $pImg['image_url'] !== 'default.png') {
        $path = "../assets/img/" . $pImg['image_url'];
        $path = "../assets/img/" . $pImg['image_url'];
        if(file_exists($path)) { unlink($path); }
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$idDel]);
    $success = "Produit supprimé du catalogue.";
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin - Catalogue Produits</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a', galaGold: '#f8f9f8f1', } } } }
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
            <h2 class="text-2xl md:text-3xl font-black text-slate-800">Gestion des Produits</h2>
            <p class="text-slate-400 text-sm">Ajoutez, modifiez ou retirez des articles de la vitrine.</p>
        </div>

        <?php if($success): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 text-sm font-semibold border border-green-100"><?= $success ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            <div class="bg-white p-6 rounded-2xl md:rounded-[2.5rem] shadow-sm border border-slate-100 h-fit">
                <h3 id="formTitle" class="text-lg font-bold text-slate-800 mb-5 flex items-center space-x-2">
                    <i class="fas fa-plus-circle text-galaGreen"></i> <span>Ajouter ou Modifier un Produit</span>
                </h3>
                <form id="productForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="id" id="prodId">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Nom du produit</label>
                        <input type="text" name="nom" id="prodNom" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-galaGreen/20 transition" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Format / Contenance</label>
                        <input type="text" name="format" id="prodFormat" placeholder="Ex: Pot de 500g, Bouteille 1L" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-galaGreen/20 transition" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Prix (FCFA)</label>
                            <input type="number" name="prix" id="prodPrix" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-galaGreen/20 transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Stock dispo</label>
                            <input type="number" name="stock" id="prodStock" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-galaGreen/20 transition" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Image du produit</label>
                        <input type="file" name="image" class="w-full p-2 bg-slate-50 border border-dashed border-slate-200 rounded-xl">
                    </div>
                    <div class="pt-2 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                        <button type="submit" class="w-full bg-galaGreen hover:bg-galaDark text-white p-3 rounded-xl font-bold transition shadow-md">Enregistrer</button>
                        <button type="button" onclick="resetForm()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-600 p-3 rounded-xl font-bold transition">Réinitialiser</button>
                    </div>
                </form>
            </div>

            <div class="xl:col-span-2 bg-white rounded-2xl md:rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto block w-full">
                    <table class="w-full text-left border-collapse min-w-[600px]">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase bg-slate-50/50">
                                <th class="p-4 w-12">#</th>
                                <th class="p-4">Aperçu</th>
                                <th class="p-4">Désignation</th>
                                <th class="p-4">Format</th>
                                <th class="p-4">Prix</th>
                                <th class="p-4">Stock</th>
                                <th class="p-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $index => $p): ?>
                            <tr class="hover:bg-slate-50/50 transition border-b border-slate-50 <?= $p['stock'] == 0 ? 'bg-red-50/40 hover:bg-red-50/60' : '' ?>">
                                <td class="p-4 text-slate-400 text-sm font-bold">#<?= $index + 1 ?></td>
                                <td class="p-4">
                                    <img src="../assets/img/<?= htmlspecialchars($p['image_url']) ?>" class="w-12 h-12 object-cover rounded-xl shadow-sm border border-slate-100">
                                </td>
                                <td class="p-4 font-bold text-slate-800"><?= htmlspecialchars($p['nom']) ?></td>
                                <td class="p-4 text-slate-600"><?= htmlspecialchars($p['format']) ?></td>
                                <td class="p-4 font-bold text-galaGreen"><?= number_format($p['prix'], 0, ',', ' ') ?> XAF</td>
                                <td class="p-4">
                                    <?php if ($p['stock'] == 0): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 inline-flex items-center gap-1 animate-pulse">
                                            <i class="fas fa-exclamation-triangle"></i> RUPTURE
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $p['stock'] > 5 ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' ?>">
                                            <?= $p['stock'] ?> pces
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center space-x-1 whitespace-nowrap">
                                    <button onclick='editProduct(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="text-amber-500 hover:bg-amber-50 p-2 rounded-lg transition" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="products_manager.php?delete=<?= $p['id'] ?>" onclick="return confirm('Supprimer définitivement ce produit ?');" class="text-rose-400 hover:text-rose-600 p-2 rounded-lg transition" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
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

    <script>
    function editProduct(product) {
        document.getElementById('prodId').value = product.id;
        document.getElementById('prodNom').value = product.nom;
        document.getElementById('prodFormat').value = product.format;
        document.getElementById('prodPrix').value = product.prix;
        document.getElementById('prodStock').value = product.stock;
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-amber-500"></i> <span>Modifier le produit : ' + product.nom + '</span>';
    }
    function resetForm() {
        document.getElementById('productForm').reset();
        document.getElementById('prodId').value = "";
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-galaGreen"></i> <span>Ajouter ou Modifier un Produit</span>';
    }
    </script>
</body>
</html>