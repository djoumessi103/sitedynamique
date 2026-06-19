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
    <style>
        /* ══════════════════════════════════════════
 /* ══════════════════════════════════════════
   HAMBURGER BUTTON & NAV - VERSION CORRIGÉE
══════════════════════════════════════════ */

/* 1. BUTTON HAMBURGER */
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
    transition: transform 0.45s cubic-bezier(0.23,1,0.32,1), opacity 0.3s ease, width 0.35s cubic-bezier(0.23,1,0.32,1);
}
.abar:nth-child(1) { width: 20px; }
.abar:nth-child(2) { width: 14px; align-self: flex-start; margin-left: 8px; }
.abar:nth-child(3) { width: 18px; }

#admin-menu-btn.open .abar:nth-child(1) { width: 20px; transform: translateY(7px) rotate(45deg); }
#admin-menu-btn.open .abar:nth-child(2) { opacity: 0; transform: scaleX(0); }
#admin-menu-btn.open .abar:nth-child(3) { width: 20px; transform: translateY(-7px) rotate(-45deg); }

/* 2. OVERLAY */
#admin-overlay {
    position: fixed; inset: 0; z-index: 8997;
    background: rgba(2,6,23,0); /* Transparent par défaut */
    pointer-events: none;
    transition: background 0.4s ease;
}
#admin-overlay.active { 
    background: rgba(2,6,23,0.6); 
    pointer-events: auto; 
}

/* 3. MOBILE DRAWER */
#admin-mobile-nav {
    position: fixed; top: 0; right: 0; bottom: 0;
    width: min(88vw, 300px); z-index: 8999;
    display: flex; flex-direction: column; overflow: hidden;
    transform: translateX(105%); /* Caché à droite */
    transition: transform 0.48s cubic-bezier(0.16,1,0.3,1);
    background: #fff;
    box-shadow: -10px 0 40px rgba(0,0,0,0.12);
    border-left: 1px solid #f1f5f9;
}
#admin-mobile-nav.active { transform: translateX(0); } /* Visible */


/* 3. L'overlay sombre */
#admin-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: none; /* Caché par défaut */
    z-index: 999;
}

#admin-overlay.active {
    display: block;
}
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
        <a href="products_manager.php" class="amn-link">
            <span class="amn-icon" style="color:#f59e0b"><i class="fas fa-box"></i></span>
            <span class="amn-link-text">Produits<span class="amn-link-sub">Gérer la gamme</span></span>
            <i class="fas fa-chevron-right amn-arrow"></i>
        </a>
        <a href="gallery.php" class="amn-link active-link">
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
    }

    // ══ AUTO-DISMISS DES ALERTES (succès / erreur upload) ══
    document.addEventListener("DOMContentLoaded", function() {
        const alerts = document.querySelectorAll('.auto-dismiss-alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.classList.add('opacity-0', 'scale-95');
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 4000);
        });
    });

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