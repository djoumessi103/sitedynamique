<?php
session_start();
if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
require_once '../includes/db.php';
// --- AJOUT POUR BACKOFFICE LIVE UPDATE ---
if (isset($_GET['api_admin_stock'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->query("SELECT id, stock FROM products");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
// -----------------------------------------
// Interception AJAX pour la diminution automatique du stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_decrement_id'])) {
    $prodId = (int)$_POST['ajax_decrement_id'];
    $qtyToRemove = (int)$_POST['qty_to_remove'];
    
    $stmtStock = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
    $stmtStock->execute([$qtyToRemove, $prodId]);
    exit; // Arrête l'exécution pour cette requête asynchrone
}

$success = ""; $error = "";
$current_page = basename($_SERVER['PHP_SELF']);

// ==========================================
// CONFIGURATION DE LA PAGINATION
// ==========================================
$items_per_page = 5; 
$page = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $items_per_page;

$total_items = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);
if ($page > $total_pages && $total_pages > 0) { $page = $total_pages; $offset = ($page - 1) * $items_per_page; }
// ==========================================

// Traitement Ajout / Modification de Produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $format = htmlspecialchars($_POST['format']);
    $prix = (int)$_POST['prix'];
    $stock = (int)$_POST['stock'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $img = "default.png"; 
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
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
    
    $total_items = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
}

// Suppression de Produit
if (isset($_GET['delete'])) {
    $idDel = (int)$_GET['delete'];
    $stmtImg = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmtImg->execute([$idDel]);
    $pImg = $stmtImg->fetch();
    if($pImg && $pImg['image_url'] !== 'default.png') {
        $path = "../assets/img/" . $pImg['image_url'];
        if(file_exists($path)) { unlink($path); }
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$idDel]);
    $success = "Produit supprimé du catalogue.";
    
    $total_items = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);
}

$stmt_products = $pdo->prepare("SELECT * FROM products ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt_products->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt_products->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_products->execute();
$products = $stmt_products->fetchAll();
// Dans admin/products_manager.php
if (isset($_POST['ajax_decrement_id'])) {
    $id = intval($_POST['ajax_decrement_id']);
    $qty = intval($_POST['qty_to_remove']);

    // Décrémentation sécurisée dans la BDD
    $stmt = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty");
    $stmt->execute([':qty' => $qty, ':id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Stock insuffisant']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin - Catalogue Produits</title>
    <script src="../assets/tailwind.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a', galaGold: '#f8f9fa', } } } }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#f1f5f9] font-sans min-h-screen flex flex-col md:flex-row antialiased text-slate-800">

    <!-- Sidebar (PC) -->
    <aside class="hidden md:flex w-64 bg-white text-slate-700 p-6 flex-col justify-between shadow-sm min-h-screen sticky top-0 border-r border-slate-200/60">
        <div>
            <div class="mb-10 px-2">
                <h1 class="text-2xl font-black text-galaGreen tracking-tight">Gala<span class="text-[#E30613]"> Admin</span></h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Espace Restreint</p>
            </div>
            <nav class="space-y-1.5">
                <a href="messages.php" class="flex items-center space-x-3 p-3 rounded-xl transition-all duration-200 font-semibold <?= ($current_page == 'messages.php') ? 'bg-galaGreen text-white shadow-md shadow-green-600/10' : 'hover:bg-slate-100 text-slate-600' ?>">
                    <i class="fas fa-envelope w-5 text-center text-lg"></i> 
                    <span>Messages</span>
                </a>
                
                <a href="products_manager.php" class="flex items-center space-x-3 p-3 rounded-xl transition-all duration-200 font-semibold <?= ($current_page == 'products_manager.php') ? 'bg-galaGreen text-white shadow-md shadow-green-600/10' : 'hover:bg-slate-100 text-slate-600' ?>">
                    <i class="fas fa-box w-5 text-center text-lg"></i> 
                    <span>Produits</span>
                </a>
                
                <a href="gallery.php" class="flex items-center space-x-3 p-3 rounded-xl transition-all duration-200 font-semibold <?= ($current_page == 'gallery.php') ? 'bg-galaGreen text-white shadow-md shadow-green-600/10' : 'hover:bg-slate-100 text-slate-600' ?>">
                    <i class="fas fa-images w-5 text-center text-lg"></i> 
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
        
        <a href="logout.php" class="flex items-center space-x-3 p-3 rounded-xl text-rose-600 hover:bg-rose-50 transition-colors duration-200 font-bold mt-auto border border-transparent hover:border-rose-100">
            <i class="fas fa-sign-out-alt w-5 text-center text-lg"></i> <span>Déconnexion</span>
        </a>
    </aside>

    <!-- Navbar Mobile -->
    <div class="md:hidden bg-white text-slate-700 px-4 py-3 flex items-center justify-between sticky top-0 z-50 shadow-sm border-b border-slate-100">
        <h1 class="text-xl font-black text-galaGreen tracking-tight">Gala<span class="text-[#E30613]"> Admin</span></h1>
        <div class="flex space-x-1 items-center">
            <a href="messages.php" class="p-2.5 text-lg text-slate-500 hover:text-galaGreen"><i class="fas fa-envelope"></i></a>
            <a href="products_manager.php" class="p-2.5 text-lg text-white bg-galaGreen rounded-xl shadow-sm"><i class="fas fa-box"></i></a>
            <a href="gallery.php" class="p-2.5 text-lg text-slate-500 hover:text-galaGreen"><i class="fas fa-images"></i></a>
            <a href="logout.php" class="p-2.5 text-lg text-rose-600"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 p-4 md:p-8 lg:p-10 w-full overflow-x-hidden">
        <div class="mb-8">
            <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">Gestion des Produits</h2>
            <p class="text-slate-500 text-sm mt-1">Ajoutez, modifiez ou retirez des articles de la vitrine en temps réel.</p>
        </div>

       <?php if (!empty($success)): ?>
    <div class="admin-alert bg-emerald-50 text-emerald-700 p-4 rounded-xl mb-6 border border-emerald-100 text-center font-semibold shadow-sm transition-all duration-500">
        <?= $success ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="admin-alert bg-rose-50 text-rose-700 p-4 rounded-xl mb-6 border border-rose-100 text-center font-semibold shadow-sm transition-all duration-500">
        <?= $error ?>
    </div>
<?php endif; ?>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8 items-start">
            
            <!-- Formulaire -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/60">
                <h3 id="formTitle" class="text-lg font-bold text-slate-900 mb-5 flex items-center space-x-2">
                    <i class="fas fa-plus-circle text-galaGreen text-xl"></i> <span>Ajouter un Produit</span>
                </h3>
                <form id="productForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="text" name="hp_field" style="display:none !important;" tabindex="-1" autocomplete="off">
                    <input type="text" name="hp_field" style="display:none !important;" tabindex="-1" autocomplete="off">
                <input type="hidden" name="id" id="prodId">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nom du produit</label>
                        <input type="text" name="nom" id="prodNom" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-galaGreen focus:ring-4 focus:ring-galaGreen/10 bg-white transition-all duration-200" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Format / Contenance</label>
                        <input type="text" name="format" id="prodFormat" placeholder="Ex: Pot de 500g, Bouteille 1L" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-galaGreen focus:ring-4 focus:ring-galaGreen/10 bg-white transition-all duration-200" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Prix (FCFA)</label>
                            <input type="number" name="prix" id="prodPrix" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-galaGreen focus:ring-4 focus:ring-galaGreen/10 bg-white transition-all duration-200" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Stock dispo</label>
                            <input type="number" name="stock" id="prodStock" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-galaGreen focus:ring-4 focus:ring-galaGreen/10 bg-white transition-all duration-200" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Image du produit</label>
                        <div class="relative flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-slate-200 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100/50 transition-colors duration-200">
                                <div class="flex flex-col items-center justify-center pt-2 pb-3">
                                    <i class="fas fa-cloud-upload-alt text-slate-400 text-xl mb-1"></i>
                                    <p class="text-xs text-slate-500 font-medium">Cliquez pour téléverser</p>
                                </div>
                                <input type="file" name="image" class="hidden" />
                            </label>
                        </div>
                    </div>
                    <div class="pt-2 flex flex-col gap-2">
                        <button type="submit" class="w-full bg-galaGreen hover:bg-slate-900 text-white p-3.5 rounded-xl font-bold transition-colors duration-200 shadow-sm shadow-green-600/10">Enregistrer</button>
                        <button type="button" onclick="resetForm()" class="w-full bg-slate-100 hover:bg-slate-200/80 text-slate-600 p-3.5 rounded-xl font-bold transition-colors duration-200">Réinitialiser</button>
                    </div>
                </form>
            </div>

            <!-- Liste des Produits (Tableau & Cartes Mobiles) -->
            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden flex flex-col justify-between min-h-[500px]">
                
                <!-- Version Mobile : Grille de cartes (cachée sur MD+) -->
                <div class="block md:hidden p-4 space-y-4">
                    <?php if(empty($products)): ?>
                        <p class="text-center text-slate-400 py-8 font-medium">Aucun produit disponible.</p>
                    <?php else: ?>
                        <?php foreach ($products as $index => $p): ?>
                            <div class="p-4 rounded-xl border <?= $p['stock'] == 0 ? 'bg-rose-50/40 border-rose-100' : 'bg-slate-50/50 border-slate-100' ?> flex gap-4 items-center relative">
                                <img src="../assets/img/<?= htmlspecialchars($p['image_url']) ?>" class="w-16 h-16 object-cover rounded-xl shadow-inner border border-slate-200/60 flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-slate-900 truncate"><?= htmlspecialchars($p['nom']) ?></h4>
                                    <p class="text-xs text-slate-500 mb-1"><?= htmlspecialchars($p['format']) ?></p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm font-black text-galaGreen"><?= number_format($p['prix'], 0, ',', ' ') ?> XAF</span>
                                        <?php if ($p['stock'] == 0): ?>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-100 text-rose-700 tracking-wide">RUPTURE</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold <?= $p['stock'] > 5 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>"><?= $p['stock'] ?> u.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1 self-start">
                                    <button onclick='editProduct(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="text-amber-500 p-2 hover:bg-amber-50 rounded-lg text-sm"><i class="fas fa-edit"></i></button>
                                    <a href="products_manager.php?delete=<?= $p['id'] ?>" onclick="return confirm('Supprimer ce produit ?');" class="text-rose-500 p-2 hover:bg-rose-50 rounded-lg text-sm"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Version Desktop : Tableau classique (caché sur Mobile) -->
                <div class="hidden md:block overflow-x-auto w-full">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase bg-slate-50/70 tracking-wider">
                                <th class="p-4 w-12 text-center">#</th>
                                <th class="p-4">Aperçu</th>
                                <th class="p-4">Désignation</th>
                                <th class="p-4">Format</th>
                                <th class="p-4">Prix</th>
                                <th class="p-4">Stock</th>
                                <th class="p-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if(empty($products)): ?>
                            <tr data-id="<?= $c['id'] ?>" class="transition-all duration-500 hover:bg-slate-50">
                                <<td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-bold">
    <span id="admin-stock-<?= $product['id'] ?>"><?= $product['stock'] ?></span> unités
</td>
                            </tr>
                            <?php else: ?>
                    <?php foreach ($products as $index => $p): ?>
    <tr data-id="<?= $p['id'] ?>" class="hover:bg-slate-50/50 transition-colors duration-150 <?= $p['stock'] == 0 ? 'bg-rose-50/20' : '' ?>">
        <td class="p-4 text-slate-400 text-xs font-bold text-center">#<?= $offset + $index + 1 ?></td>
        <td class="p-4"><img src="../assets/img/<?= htmlspecialchars($p['image_url']) ?>" class="w-11 h-11 object-cover rounded-xl shadow-sm border border-slate-200/60"></td>
        <td class="p-4 font-bold text-slate-900"><?= htmlspecialchars($p['nom']) ?></td>
        <td class="p-4 text-slate-600 text-sm"><?= htmlspecialchars($p['format']) ?></td>
        <td class="p-4 font-extrabold text-galaGreen text-sm"><?= number_format($p['prix'], 0, ',', ' ') ?> XAF</td>
        <td class="p-4">
            <?php if ($p['stock'] == 0): ?>
                <span class="px-2.5 py-1 rounded-lg text-[11px] font-black bg-rose-100 text-rose-700">RUPTURE</span>
            <?php else: ?>
                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700"><?= $p['stock'] ?> pcs</span>
            <?php endif; ?>
        </td>
        <td class="p-4 text-center whitespace-nowrap">
            <div class="flex items-center justify-center space-x-2">
                <button type="button" onclick='editProduct(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="w-9 h-9 rounded-full bg-amber-50 hover:bg-amber-500 transition-all duration-300 shadow-sm text-amber-500 hover:text-white">
                    <i class="fas fa-edit text-sm"></i>
                </button>
                <button type="button" 
        onclick="supprimerProduit(<?= $p['id'] ?>)" 
        class="group flex items-center justify-center w-9 h-9 rounded-full bg-rose-50 hover:bg-rose-600 transition-all duration-300 shadow-sm"
        title="Supprimer">
    <i class="fas fa-trash-alt text-rose-500 group-hover:text-white transition-colors text-sm"></i>
</button>
            </div>
        </td>
    </tr>
<?php endforeach; ?>

    </div>
</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination Ultra-Responsive -->
                <?php if ($total_pages > 1): ?>
                <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between bg-slate-50/50 gap-4">
                    
                    <!-- Texte explicatif -->
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider text-center sm:text-left">
                        Page <span class="text-slate-700"><?= $page ?></span> sur <span class="text-slate-700"><?= $total_pages ?></span>
                    </span>
                    
                    <!-- Boutons de navigation adaptatifs -->
                    <div class="flex items-center justify-center w-full sm:w-auto space-x-1">
                        
                        <!-- Précédent -->
                        <a href="?p=<?= $page - 1 ?>" class="flex-1 sm:flex-none text-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold transition-all duration-200 hover:bg-slate-50 active:scale-95 <?= $page <= 1 ? 'pointer-events-none opacity-40 bg-slate-100' : '' ?>">
                            <i class="fas fa-chevron-left mr-1.5"></i> Précédent
                        </a>
                        
                        <!-- Numéros de page (Visibles uniquement sur Desktop/Tablette pour éviter le débordement) -->
                        <div class="hidden md:flex items-center space-x-1 px-1">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || abs($i - $page) <= 1): ?>
                                    <a href="?p=<?= $i ?>" class="w-9 h-9 flex items-center justify-center rounded-xl text-xs font-bold border transition-all duration-200 hover:scale-105 <?= $page == $i ? 'bg-galaGreen text-white border-galaGreen shadow-sm shadow-green-600/20 scale-105' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                                    <span class="w-6 text-center text-slate-400 text-xs font-bold tracking-tight">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>

                        <!-- Suivant -->
                        <a href="?p=<?= $page + 1 ?>" class="flex-1 sm:flex-none text-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold transition-all duration-200 hover:bg-slate-50 active:scale-95 <?= $page >= $total_pages ? 'pointer-events-none opacity-40 bg-slate-100' : '' ?>">
                            Suivant <i class="fas fa-chevron-right ml-1.5"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

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
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-amber-500 text-xl"></i> <span>Modifier le produit : ' + product.nom + '</span>';
        window.scrollTo({ top: 0, behavior: 'smooth' }); // Remonte proprement sur mobile pour modifier directement
    }
    function resetForm() {
        document.getElementById('productForm').reset();
        document.getElementById('prodId').value = "";
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-galaGreen text-xl"></i> <span>Ajouter un Produit</span>';
    }
    function checkStockAndSubmit(id, productName) {
    const input = document.getElementById('quantity-' + id);
    const unitSelect = document.getElementById('unit-' + id);
    const container = document.getElementById('selector-container-' + id);
    
    const qtyRequested = parseInt(input.value) || 1;
    const maxStock = parseInt(container.getAttribute('data-stock')) || 0;
    const unitChosen = unitSelect.value;

    if (qtyRequested > maxStock) {
        showStockError(id);
        alert("Désolé, il n'y a pas assez de stock pour satisfaire cette quantité.");
        return false;
    }

    // Redirection vers le script PHP de traitement avec les paramètres de la commande
    window.location.href = `process_order.php?id=${id}&qty=${qtyRequested}&unit=${encodeURIComponent(unitChosen)}`;

}
function checkIncomingClientOrders() {
    fetch('products_manager.php?api_admin_stock=1')
    .then(response => response.json())
    .then(products => {
        products.forEach(p => {
            const adminStockSpan = document.getElementById('admin-stock-' + p.id);
            if (adminStockSpan) {
                const oldStock = parseInt(adminStockSpan.textContent);
                const newStock = parseInt(p.stock);
                
                if (oldStock !== newStock) {
                    // Mettre à jour visuellement le stock
                    adminStockSpan.textContent = newStock;
                    
                    // Optionnel : Ajouter un petit effet visuel flash ambré/jaune pour signaler le changement
                    adminStockSpan.parentElement.classList.add('bg-amber-100', 'transition-colors', 'duration-500');
                    setTimeout(() => {
                        adminStockSpan.parentElement.classList.remove('bg-amber-100');
                    }, 2000);
                }
            }
        });
    })
    .catch(err => console.error("Erreur lors de la synchronisation des stocks :", err));
}

// Vérifier les nouvelles commandes toutes les 4 secondes
setInterval(checkIncomingClientOrders, 4000);
document.addEventListener("DOMContentLoaded", function() {
    // Sélectionner toutes les alertes ayant la classe 'admin-alert'
    const alerts = document.querySelectorAll('.admin-alert');
    
    alerts.forEach(function(alert) {
        // Disparition après 4 secondes (4000 millisecondes)
        setTimeout(function() {
            // Animation de fondu fluide avec Tailwind
            alert.classList.add('opacity-0', 'scale-95');
            
            // Suppression définitive de l'élément du design après l'animation (500ms)
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 4000);
    });
});
function supprimerProduit(id) {
    Swal.fire({
        title: 'Supprimer ce produit ?',
        text: "Cette action est irréversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Oui, supprimer'
    }).then((result) => {
        if (result.isConfirmed) {
            // Appel vers le fichier dédié aux produits
            fetch('delete_products.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const row = document.querySelector(`tr[data-id='${id}']`);
                    if (row) {
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 500);
                        Swal.fire('Supprimé !', 'Le produit a été retiré.', 'success');
                    }
                }
            });
        }
    });
}
    </script>
</body>
</html>