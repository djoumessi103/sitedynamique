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
       <style>
/* ══════════════════════════════════════════
   HAMBURGER BUTTON
══════════════════════════════════════════ */
#admin-menu-btn {
    width: 42px; height: 42px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 5px;
    background: rgba(22,163,74,0.08);
    border: 1.5px solid rgba(22,163,74,0.18);
    border-radius: 14px; cursor: pointer;
    transition: background 0.25s, border-color 0.25s, transform 0.15s;
}
#admin-menu-btn:active { transform: scale(0.93); }
#admin-menu-btn.open { background: rgba(22,163,74,0.14); border-color: rgba(22,163,74,0.3); }
.abar {
    display: block; height: 2px; border-radius: 99px;
    background: #16a34a; transform-origin: center;
    transition: transform 0.45s cubic-bezier(0.23,1,0.32,1),
                opacity 0.3s ease, width 0.35s cubic-bezier(0.23,1,0.32,1);
}
.abar:nth-child(1) { width: 20px; }
.abar:nth-child(2) { width: 14px; align-self: flex-start; margin-left: 8px; }
.abar:nth-child(3) { width: 18px; }
#admin-menu-btn.open .abar:nth-child(1) { width: 20px; transform: translateY(7px) rotate(45deg); }
#admin-menu-btn.open .abar:nth-child(2) { opacity: 0; transform: scaleX(0); }
#admin-menu-btn.open .abar:nth-child(3) { width: 20px; transform: translateY(-7px) rotate(-45deg); }

/* ══ OVERLAY ══ */
#admin-overlay {
    position: fixed; inset: 0; z-index: 8997;
    background: transparent; pointer-events: none;
    transition: background 0.4s ease;
}
#admin-overlay.active { background: rgba(2,6,23,0.6); pointer-events: auto; }

/* ══ MOBILE DRAWER ══ */
#admin-mobile-nav {
    position: fixed; top: 0; right: 0; bottom: 0;
    width: min(88vw, 300px); z-index: 8999;
    display: flex; flex-direction: column; overflow: hidden;
    transform: translateX(105%);
    transition: transform 0.48s cubic-bezier(0.16,1,0.3,1);
    background: #fff;
    box-shadow: -10px 0 40px rgba(0,0,0,0.12);
    border-left: 1px solid #f1f5f9;
}
#admin-mobile-nav.active { transform: translateX(0); }

.amn-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 52px 20px 16px; border-bottom: 1px solid #f1f5f9;
}
.amn-logo {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #16a34a, #10b981);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 900; font-size: 16px;
    box-shadow: 0 4px 12px rgba(22,163,74,0.35);
}
.amn-title { font-size: 1rem; font-weight: 900; color: #16a34a; }
.amn-sub { font-size: 0.6rem; font-weight: 700; letter-spacing: 0.12em; color: #94a3b8; text-transform: uppercase; }
.amn-close {
    width: 34px; height: 34px; border-radius: 10px;
    background: #f1f5f9; border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    color: #64748b; font-size: 14px; cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.amn-close:active { background: #dcfce7; color: #16a34a; }

.amn-body { flex: 1; overflow-y: auto; padding: 10px 12px; scrollbar-width: none; }
.amn-body::-webkit-scrollbar { display: none; }
.amn-label {
    font-size: 0.58rem; font-weight: 800; letter-spacing: 0.2em;
    color: #94a3b8; text-transform: uppercase; padding: 10px 10px 5px;
}
.amn-link {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 14px; margin-bottom: 3px;
    color: #334155; font-weight: 600; font-size: 0.9rem;
    text-decoration: none; position: relative;
    opacity: 0; transform: translateX(22px);
    transition: background 0.2s, color 0.2s,
                opacity 0.38s ease, transform 0.38s cubic-bezier(0.23,1,0.32,1);
}
.amn-link::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #16a34a, #10b981);
    opacity: 0; transition: opacity 0.2s;
}
.amn-link:hover { background: #f0fdf4; color: #16a34a; }
.amn-link:hover::before { opacity: 1; }
.amn-link.active-link { background: #f0fdf4; color: #16a34a; font-weight: 800; }
.amn-link.active-link::before { opacity: 1; }
.amn-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; background: #f8fafc; border: 1px solid #e2e8f0;
    transition: background 0.2s;
}
.amn-link:hover .amn-icon,
.amn-link.active-link .amn-icon { background: #dcfce7; border-color: #bbf7d0; }
.amn-link-text { flex: 1; }
.amn-link-sub { display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 500; margin-top: 1px; }
.amn-arrow { font-size: 10px; color: #cbd5e1; transition: transform 0.2s, color 0.2s; }
.amn-link:hover .amn-arrow { transform: translateX(3px); color: #16a34a; }

#admin-mobile-nav.active .amn-link { opacity: 1; transform: translateX(0); }
#admin-mobile-nav.active .amn-link:nth-child(1) { transition-delay: 0.06s; }
#admin-mobile-nav.active .amn-link:nth-child(2) { transition-delay: 0.11s; }
#admin-mobile-nav.active .amn-link:nth-child(3) { transition-delay: 0.16s; }
#admin-mobile-nav.active .amn-link:nth-child(4) { transition-delay: 0.21s; }
#admin-mobile-nav.active .amn-link:nth-child(5) { transition-delay: 0.26s; }
#admin-mobile-nav.active .amn-link:nth-child(6) { transition-delay: 0.31s; }

.amn-footer { padding: 14px 12px 36px; border-top: 1px solid #f1f5f9; }
.amn-logout {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 16px; border-radius: 14px;
    color: #dc2626; font-weight: 700; font-size: 0.9rem;
    text-decoration: none; background: #fff5f5;
    opacity: 0; transition: opacity 0.38s ease 0.34s, background 0.2s;
}
#admin-mobile-nav.active .amn-logout { opacity: 1; }
.amn-logout:hover { background: #fee2e2; }
   </style>
</head>
<body class="bg-slate-50 font-sans min-h-screen flex flex-col lg:flex-row">
<?php include 'sidebar_nav.php'; ?>
<!-- ══ MOBILE TOPBAR ══ -->
<header class="lg:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-slate-100 shadow-sm sticky top-0 z-[999] no-print">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-600 to-emerald-500 flex items-center justify-center text-white font-black text-sm shadow">G</div>
        <span class="font-black text-green-700 text-base">Gala <span class="text-slate-700">Admin</span></span>
    </div>
    <button id="admin-menu-btn" aria-label="Menu">
        <span class="abar"></span>
        <span class="abar"></span>
        <span class="abar"></span>
    </button>
</header>
<aside class="hidden lg:block w-64 flex-shrink-0 bg-white border-r border-slate-200 min-h-screen">
   </aside>
<!-- ══ OVERLAY ══ -->
<div id="admin-overlay"></div>

<!-- ══ MOBILE DRAWER ══ -->
<div id="admin-mobile-nav" aria-hidden="true">
    <div class="amn-header">
        <div class="flex items-center gap-3">
            <div class="amn-logo">G</div>
            <div>
                <div class="amn-title">Gala Admin</div>
                <div class="amn-sub">Dashboard 2026</div>
            </div>
        </div>
        <button class="amn-close" id="admin-nav-close"><i class="fas fa-times"></i></button>
    </div>
    <div class="amn-body">
        <div class="amn-label">Navigation</div>
        <a href="admin_commandes.php" class="amn-link">
            <span class="amn-icon" style="color:#059669"><i class="fas fa-shopping-cart"></i></span>
            <span class="amn-link-text">Commandes<span class="amn-link-sub">Finaliser les commandes</span></span>
            <i class="fas fa-chevron-right amn-arrow"></i>
        </a>
         <a href="voir_candidatures.php" class="amn-link">
            <span class="amn-icon" style="color:#3b82f6"><i class="fas fa-envelope"></i></span>
            <span class="amn-link-text">Messages<span class="amn-link-sub">Boîte de réception</span></span>
            <i class="fas fa-chevron-right amn-arrow"></i>
        </a>
        <a href="products_manager.php" class="amn-link active-link">
            <span class="amn-icon" style="color:#f59e0b"><i class="fas fa-box"></i></span>
            <span class="amn-link-text">Produits<span class="amn-link-sub">Gérer la gamme</span></span>
            <i class="fas fa-chevron-right amn-arrow"></i>
        </a>
        <a href="gallery.php" class="amn-link">
            <span class="amn-icon" style="color:#db2777"><i class="fas fa-images"></i></span>
            <span class="amn-link-text">Galerie<span class="amn-link-sub">Photos & médias</span></span>
            <i class="fas fa-chevron-right amn-arrow"></i>
        </a>
        <a href="voir_candidatures.php" class="amn-link">
            <span class="amn-icon" style="color:#8b5cf6"><i class="fas fa-users"></i></span>
            <span class="amn-link-text">Candidatures<span class="amn-link-sub">Voir les dossiers</span></span>
            <i class="fas fa-chevron-right amn-arrow"></i>
        </a>
        <a href="../index.php" class="amn-link">
            <span class="amn-icon" style="color:#E30613"><i class="fas fa-globe"></i></span>
            <span class="amn-link-text">Consulter le site<span class="amn-link-sub">Voir la vitrine</span></span>
            <i class="fas fa-chevron-right amn-arrow"></i>
        </a>
    </div>
    <div class="amn-footer">
        <a href="logout.php" class="amn-logout">
            <span class="amn-icon" style="color:#dc2626; background:#fff5f5; border-color:#fecaca"><i class="fas fa-sign-out-alt"></i></span>
            Déconnexion
        </a>
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
// ══ HAMBURGER JS ══
(function() {
    const btn     = document.getElementById('admin-menu-btn');
    const nav     = document.getElementById('admin-mobile-nav');
    const overlay = document.getElementById('admin-overlay');
    const close   = document.getElementById('admin-nav-close');
    if (!btn) return;
    function openMenu()  { nav.classList.add('active'); overlay.classList.add('active'); btn.classList.add('open'); nav.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; }
    function closeMenu() { nav.classList.remove('active'); overlay.classList.remove('active'); btn.classList.remove('open'); nav.setAttribute('aria-hidden','true'); document.body.style.overflow=''; }
    btn.addEventListener('click', e => { e.stopPropagation(); nav.classList.contains('active') ? closeMenu() : openMenu(); });
    close.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);
    nav.querySelectorAll('a').forEach(l => l.addEventListener('click', closeMenu));
})();
    </script>
</body>
</html>