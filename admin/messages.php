<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';
$current_page = basename($_SERVER['PHP_SELF']);

// ==========================================
// CONFIGURATION DE LA PAGINATION
// ==========================================
$items_per_page = 10; // Mettez une valeur plus grande que votre nombre de messages (58)
$page = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $items_per_page;

$total_items = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// ... le reste de votre code (le prepare, le bindValue, etc.) ne change pas

if ($page > $total_pages && $total_pages > 0) { 
    $page = $total_pages; 
    $offset = ($page - 1) * $items_per_page; 
}

$stmt = $pdo->prepare("SELECT * FROM contacts ORDER BY date_envoi DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll();
// ==========================================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des Messages</title>
    <script src="../assets/tailwind.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a', galaGold: '#f8f9f8f1', } } } }
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
         <a href="voir_candidatures.php" class="amn-link active-link">
            <span class="amn-icon" style="color:#3b82f6"><i class="fas fa-envelope"></i></span>
            <span class="amn-link-text">Messages<span class="amn-link-sub">Boîte de réception</span></span>
            <i class="fas fa-chevron-right amn-arrow"></i>
        </a>
        <a href="products_manager.php" class="amn-link">
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
    <!-- CONTENU PRINCIPAL -->
    <main class="flex-1 p-4 sm:p-6 md:p-10 w-full overflow-x-hidden">
        
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-black text-slate-800">Messages Reçus</h2>
                <p class="text-sm text-slate-400 mt-1">Interactions prospects via formulaire vitrine</p>
            </div>
            <div>
                <span class="bg-galaGreen/10 text-galaGreen px-4 py-2 rounded-xl text-sm font-black border border-galaGreen/20 inline-block">
                    <?= $total_items ?> Message(s)
                </span>
            </div>
        </div>

        <!-- TABLEAU REVISITÉ -->
        <div class="w-full bg-white rounded-2xl md:rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse table-auto">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase bg-slate-50/50">
                            <th class="p-4 md:p-5 w-12 hidden lg:table-cell">Nº</th>
                            <th class="p-4 md:p-5">Expéditeur & Message</th>
                            <th class="p-4 md:p-5 hidden sm:table-cell">Téléphone</th>
                            <th class="p-4 md:p-5 hidden md:table-cell">Date d'envoi</th>
                            <th class="p-4 md:p-5 text-center w-40">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">

                       <?php foreach ($messages as $key => $m): ?>
    <tr class="hover:bg-slate-50/80 transition" data-id="<?= $m['id'] ?>">
        
        <td class="p-4 md:p-5 font-semibold text-slate-400 hidden lg:table-cell">
            <?= $offset + $key + 1 ?>
        </td>
        
        <td class="p-4 md:p-5 max-w-0 sm:max-w-xs md:max-w-md lg:max-w-lg">
            <div class="font-bold text-slate-800 text-sm md:text-base flex flex-wrap items-center gap-2">
                <span><?= htmlspecialchars($m['nom_complet']) ?></span>
                <span class="text-[10px] font-medium text-slate-400 md:hidden block">
                    • <?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?>
                </span>
            </div>
            <div class="text-xs md:text-sm text-slate-500 mt-1 truncate">
                <?= htmlspecialchars($m['message']) ?>
            </div>
        </td>
        
        <td class="p-4 md:p-5 whitespace-nowrap hidden sm:table-cell">
            <a href="tel:<?= $m['telephone'] ?>" class="text-galaGreen hover:underline font-semibold text-sm flex items-center space-x-2">
                <i class="fas fa-phone-alt text-[10px]"></i> <span><?= htmlspecialchars($m['telephone']) ?></span>
            </a>
        </td>
        
        <td class="p-4 md:p-5 text-xs md:text-sm text-slate-400 whitespace-nowrap hidden md:table-cell">
            <?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?>
        </td>
        
        <td class="p-4 md:p-5 text-center whitespace-nowrap">
            <div class="flex items-center justify-center space-x-2">
                <button type="button"
                        onclick="openMessageModal(<?= htmlspecialchars(json_encode($m['nom_complet'], JSON_HEX_APOS|JSON_HEX_QUOT)) ?>, <?= htmlspecialchars(json_encode($m['telephone'], JSON_HEX_APOS|JSON_HEX_QUOT)) ?>, <?= htmlspecialchars(json_encode($m['message'], JSON_HEX_APOS|JSON_HEX_QUOT)) ?>, '<?= date('d/m/Y à H:i', strtotime($m['date_envoi'])) ?>')" 
                        class="group flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 hover:bg-blue-600 transition-all duration-300 shadow-sm"
                        title="Lire le message">
                    <i class="fas fa-eye text-blue-500 group-hover:text-white transition-colors text-sm"></i>
                </button>

                <button type="button"
                        onclick="supprimerMessage(<?= $m['id'] ?>)"
                        class="group flex items-center justify-center w-8 h-8 rounded-full bg-red-50 hover:bg-red-600 transition-all duration-300 shadow-sm"
                        title="Supprimer">
                    <i class="fas fa-trash-alt text-red-500 group-hover:text-white transition-colors text-sm"></i>
                </button>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
            <div class="p-4 md:p-5 border-t border-slate-100 bg-slate-50/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                    Page <?= $page ?> sur <?= $total_pages ?>
                </span>
                
                <div class="flex items-center space-x-1 justify-center">
                    <a href="?p=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold transition hover:bg-slate-50 <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
                        <i class="fas fa-chevron-left mr-1"></i> Précédent
                    </a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?p=<?= $i ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= $i == $page ? 'bg-galaGreen text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <a href="?p=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-bold transition hover:bg-slate-50 <?= $page >= $total_pages ? 'pointer-events-none opacity-40' : '' ?>">
                        Suivant <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ==========================================
         MODALE DE LECTURE FIABILISÉE
         ========================================== -->
    <div id="messageModal" class="hidden fixed inset-0 z-[999] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <!-- Fond sombre cliquable pour fermer -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeMessageModal()"></div>

            <!-- Box Modale -->
            <div class="relative inline-block bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-slate-100 z-50">
                <div class="bg-white p-6 sm:p-8">
                    
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-black text-slate-900" id="modalNom">---</h3>
                            <p class="text-xs font-medium text-slate-400 mt-1" id="modalDate">---</p>
                        </div>
                        <button type="button" onclick="closeMessageModal()" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl bg-slate-50 transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 mb-6">
                        <p class="text-sm md:text-base text-slate-700 leading-relaxed whitespace-pre-wrap" id="modalCorps">---</p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Contact direct :</span>
                        <a id="modalTelHref" href="#" class="bg-galaGreen text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md hover:bg-galaGreen/90 transition flex items-center space-x-2">
                            <i class="fas fa-phone-alt text-xs"></i> <span id="modalTelTxt">---</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS MODALE -->
    <script>
        function openMessageModal(nom, tel, message, dateEnvoi) {
            document.getElementById('modalNom').textContent = nom;
            document.getElementById('modalDate').innerHTML = '<i class="far fa-clock mr-1"></i> Reçu le ' + dateEnvoi;
            document.getElementById('modalCorps').textContent = message;
            document.getElementById('modalTelTxt').textContent = tel;
            document.getElementById('modalTelHref').href = 'tel:' + tel;

            document.getElementById('messageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMessageModal() {
            document.getElementById('messageModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") closeMessageModal();
        });
     function supprimerMessage(id) {
    Swal.fire({
        title: 'Supprimer ce message ?',
        text: "Cette action est irréversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
        customClass: { popup: 'rounded-3xl' }
    }).then((result) => {
        if (result.isConfirmed) {
            // Appel spécifique à delete_messages.php
            fetch('delete_messages.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const row = document.querySelector(`tr[data-id='${id}']`);
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 500);
                    Swal.fire('Supprimé !', 'Le message a été retiré.', 'success');
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