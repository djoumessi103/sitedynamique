<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
require_once '../includes/db.php';

$success = ""; $error = "";

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
            // Nettoyage et unicité du nom de fichier
            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadFileDir = '../assets/img/';
            
            // Créer le dossier s'il n'existe pas
            if(!is_dir($uploadFileDir)){
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $img = $newFileName;
            }
        }
    }

    if ($id > 0) {
        // Mode Modification
        if ($img !== "default.png") {
            // Si une nouvelle image a été téléchargée, on la met à jour
            $stmt = $pdo->prepare("UPDATE products SET nom = ?, format = ?, prix = ?, stock = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$nom, $format, $prix, $stock, $img, $id]);
        } else {
            // Sinon, on garde l'ancienne image
            $stmt = $pdo->prepare("UPDATE products SET nom = ?, format = ?, prix = ?, stock = ? WHERE id = ?");
            $stmt->execute([$nom, $format, $prix, $stock, $id]);
        }
        $success = "Le produit a bien été mis à jour.";
    } else {
        // Mode Ajout
        $stmt = $pdo->prepare("INSERT INTO products (nom, format, prix, stock, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $format, $prix, $stock, $img]);
        $success = "Nouveau produit ajouté au catalogue.";
    }
}

// Suppression Produit
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$del_id]);
    header('Location: products_manager.php');
    exit;
}

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Produits & Prix - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a' } } } }
    </script>
</head>
<body class="bg-slate-50 font-sans flex min-h-screen">

    <aside class="w-64 bg-galaDark text-white p-6 flex flex-col justify-between shadow-xl">
        <div>
            <div class="mb-10">
                <h1 class="text-2xl font-black text-galaGreen"> Mayonnaise Gala Admin </h1>
            </div>
            <nav class="space-y-2">
                <a href="messages.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition">
                    <i class="fas fa-envelope"></i> <span>Messages</span>
                </a>
                <a href="products_manager.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl bg-galaGreen font-bold text-white shadow-md">
                    <i class="fas fa-box-open"></i> <span>Produits & Stocks</span>
                </a>
                <a href="gallery.php" class="flex items-center space-x-3 py-3 px-4 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition">
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
        <h2 class="text-3xl font-black text-slate-800 mb-2">Catalogue Produits</h2>
        <p class="text-sm text-slate-400 mb-10">Ajustez les tarifs, modifiez les images et passez les articles en rupture en mettant le stock à 0.</p>

        <?php if($success): ?>
            <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl mb-6 font-bold border border-emerald-100"><?= $success ?></div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm mb-10">
            <h3 id="formTitle" class="font-bold text-lg mb-4 text-slate-700 flex items-center space-x-2">
                <i class="fas fa-plus-circle text-galaGreen"></i> <span>Ajouter ou Modifier un Produit</span>
            </h3>
            
            <form method="POST" id="productForm" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <input type="hidden" name="id" id="prodId" value="">
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nom du Produit</label>
                    <input type="text" name="nom" id="prodNom" class="w-full p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-galaGreen/50" required>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Format (ex: 1L, 500g)</label>
                    <input type="text" name="format" id="prodFormat" class="w-full p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-galaGreen/50" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Prix (FCFA)</label>
                    <input type="number" name="prix" id="prodPrix" class="w-full p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-galaGreen/50" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Quantité Stock</label>
                    <input type="number" name="stock" id="prodStock" class="w-full p-3 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-galaGreen/50" required>
                </div>
                
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Image du produit (JPG, PNG, WEBP)</label>
                    <input type="file" name="image" id="prodImg" class="w-full p-2.5 rounded-xl border border-slate-200 outline-none bg-slate-50 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-galaDark file:text-white hover:file:bg-galaGreen cursor-pointer">
                </div>
                
                <div class="md:col-span-2 flex justify-end space-x-2">
                    <button type="button" onclick="resetForm()" class="bg-slate-100 text-slate-600 px-5 py-3 rounded-xl font-bold text-sm hover:bg-slate-200 transition">Annuler</button>
                    <button type="submit" class="bg-galaDark text-white hover:bg-galaGreen px-6 py-3 rounded-xl font-bold text-sm transition shadow-md">Enregistrer</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase">Visuel</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase">Désignation</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase">Format</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase">Tarif Actuel</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase">Statut Stock</th>
                        <th class="p-5 text-xs font-bold text-slate-500 uppercase text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($products as $p): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="p-5">
                            <img src="../assets/img/<?= htmlspecialchars($p['image_url'] ?: 'default.png') ?>" class="w-12 h-12 object-contain rounded-lg border bg-white p-1">
                        </td>
                        <td class="p-5 font-bold text-slate-800"><?= htmlspecialchars($p['nom']) ?></td>
                        <td class="p-5 text-slate-500 font-semibold"><?= htmlspecialchars($p['format'] ?? 'N/A') ?></td>
                        <td class="p-5 text-galaGreen font-black"><?= number_format($p['prix'] ?? 0, 0, ',', ' ') ?> FCFA</td>
                        <td class="p-5">
                            <?php if (($p['stock'] ?? 1) <= 0): ?>
                                <span class="bg-rose-50 text-rose-600 px-3 py-1 rounded-full text-xs font-black border border-rose-100">RUPTURE</span>
                            <?php else: ?>
                                <span class="bg-emerald-50 text-emerald-600 px-3 py-1 rounded-full text-xs font-bold border border-emerald-100">En Stock (<?= $p['stock'] ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-5 text-center flex justify-center space-x-3 h-full items-center">
                            <button onclick='editProduct(<?= json_encode($p) ?>)' class="text-amber-500 hover:text-amber-600 p-1 transition" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="products_manager.php?delete=<?= $p['id'] ?>" onclick="return confirm('Supprimer définitivement ce produit ?');" class="text-rose-400 hover:text-rose-600 p-1 transition" title="Supprimer">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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