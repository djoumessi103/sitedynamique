<?php
require_once '../includes/db.php';
$current_page = 'voir_candidatures.php';

$query = $pdo->query("SELECT * FROM candidatures ORDER BY created_at DESC");
$candidatures = $query->fetchAll();

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Candidatures</title>
    <script src="../assets/tailwind.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a' } } } }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
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

/* ══ RESPONSIVE TABLE → CARDS ══ */
@media (max-width: 768px) {
    #candidatures-table thead { display: none; }
    #candidatures-table tbody tr {
        display: block;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        margin-bottom: 14px;
        padding: 14px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    #candidatures-table tbody td {
        display: flex; align-items: flex-start;
        gap: 10px; padding: 8px 0;
        border: none; border-bottom: 1px solid #f1f5f9;
        font-size: 0.82rem;
    }
    #candidatures-table tbody td:last-child { border-bottom: none; }
    #candidatures-table tbody td::before {
        content: attr(data-label);
        font-weight: 800; font-size: 0.66rem;
        color: #94a3b8; text-transform: uppercase;
        letter-spacing: 0.08em; min-width: 85px;
        padding-top: 2px; flex-shrink: 0;
    }
}

@media (min-width: 1024px) {
    #admin-menu-btn, #admin-mobile-nav, #admin-overlay { display: none !important; }
}

/* ══════════════════════════════════════════
   PRINT — PROFESSIONNEL 2026
══════════════════════════════════════════ */
@media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

    /* Masquer tout sauf le contenu imprimable */
    aside, header, .no-print, #admin-mobile-nav, #admin-overlay,
    #admin-menu-btn, .print-hidden { display: none !important; }

    body {
        background: #fff !important;
        margin: 0; padding: 0;
        font-family: 'Segoe UI', Arial, sans-serif;
    }

    main { padding: 0 !important; margin: 0 !important; }

    /* ── Entête impression ── */
    .print-header { display: flex !important; }

    /* ── Reset container ── */
    .bg-white { border: none !important; box-shadow: none !important; border-radius: 0 !important; }
    .overflow-x-auto { overflow: visible !important; }

    /* ── Table propre ── */
    table {
        width: 100% !important; border-collapse: collapse !important;
        margin-top: 0 !important; font-size: 11px !important;
    }
    thead tr {
        background: #16a34a !important;
        color: #fff !important;
    }
    thead th {
        padding: 10px 12px !important; font-weight: 800 !important;
        font-size: 10px !important; text-transform: uppercase;
        letter-spacing: 0.08em; border: none !important;
        color: #fff !important;
    }
    tbody tr { border-bottom: 1px solid #f1f5f9 !important; }
    tbody tr:nth-child(even) { background: #f8fafc !important; }
    tbody td { padding: 10px 12px !important; border: none !important; color: #1e293b !important; }

    /* Badge statut impression */
    .badge-attente { background: #fef3c7 !important; color: #92400e !important; padding: 2px 8px; border-radius: 99px; font-weight: 800; font-size: 10px; }
    .badge-valide  { background: #dcfce7 !important; color: #15803d !important; padding: 2px 8px; border-radius: 99px; font-weight: 800; font-size: 10px; }
    .badge-refuse  { background: #fee2e2 !important; color: #b91c1c !important; padding: 2px 8px; border-radius: 99px; font-weight: 800; font-size: 10px; }

    select { display: none !important; }
    .statut-print { display: inline !important; }

    @page { margin: 1.2cm 1.5cm; size: A4 landscape; }
}

/* Cacher le span de statut sur écran, visible seulement à l'impression */
.statut-print { display: none; }
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
        <a href="messages.php" class="amn-link">
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
        <a href="voir_candidatures.php" class="amn-link active-link">
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

<!-- ══ MAIN ══ -->
<main class="flex-1 p-4 md:p-8">
    <div class="max-w-6xl mx-auto">

        <!-- Entête imprimable (cachée à l'écran) -->
        <div class="print-header hidden mb-6 pb-5 border-b-2 border-green-600">
            <div class="flex justify-between items-end w-full">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#16a34a,#10b981);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:18px;">G</div>
                        <h1 style="font-size:1.6rem;font-weight:900;color:#0f172a;margin:0;">Mayonnaise GALA</h1>
                    </div>
                    <p style="font-size:0.75rem;color:#64748b;margin:0;">Service des Ressources Humaines · Douala, Cameroun</p>
                </div>
                <div class="text-right">
                    <h2 style="font-size:1.1rem;font-weight:800;color:#1e293b;margin:0;">Liste des Candidatures</h2>
                    <p style="font-size:0.7rem;color:#94a3b8;margin:4px 0 0;">Édité le <?= date('d/m/Y à H:i') ?> · Confidentiel</p>
                </div>
            </div>
        </div>

        <!-- Entête écran -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
            <div>
                <h2 class="text-2xl md:text-3xl font-black text-slate-800">Candidatures reçues</h2>
                <p class="text-slate-500 text-sm mt-1">Gérez les dossiers des nouveaux talents.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button onclick="window.print()" class="flex items-center justify-center gap-2 bg-slate-900 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg text-sm">
                    <i class="fas fa-print"></i> Imprimer la liste
                </button>
                <button onclick="telechargerPDF()" id="btn-download-pdf" class="flex items-center justify-center gap-2 bg-galaGreen hover:bg-green-800 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg text-sm">
                    <i class="fas fa-download"></i> Télécharger en PDF
                </button>
            </div>
        </header>

        <!-- Tableau -->
        <div class="bg-white rounded-2xl md:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table id="candidatures-table" class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Nº</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Candidat</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Poste</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Contact</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center">Statut</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center">Documents</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center no-print">Actions</th>
                        </tr>
                    </thead>
                   <tbody class="divide-y divide-slate-100">
    <?php foreach ($candidatures as $key => $c): ?>
        <?php
            // Calcul des styles dynamiques pour chaque candidat
            $statutClass = match($c['statut']) {
                'Validé'    => 'bg-green-100 text-green-700',
                'Refusé'    => 'bg-red-100 text-red-700',
                default     => 'bg-amber-100 text-amber-700'
            };
            $statutPrint = match($c['statut']) {
                'Validé'    => 'badge-valide',
                'Refusé'    => 'badge-refuse',
                default     => 'badge-attente'
            };
        ?>
        <tr data-id="<?= $c['id'] ?>" class="transition-all duration-500 hover:bg-slate-50">
            <td class="p-4 md:p-5 font-semibold text-slate-400 hidden lg:table-cell">
                <?= $offset + $key + 1 ?>
            </td>

            <td class="p-4 md:p-5 font-semibold text-slate-800 text-sm" data-label="Candidat">
                <?= htmlspecialchars($c['nom_complet']) ?>
            </td>

            <td class="p-4 md:p-5 text-slate-600 text-sm" data-label="Poste">
                <?= htmlspecialchars($c['poste']) ?>
            </td>

            <td class="p-4 md:p-5 text-xs md:text-sm text-slate-500" data-label="Contact">
                <div class="truncate max-w-[160px]"><?= htmlspecialchars($c['email']) ?></div>
                <div class="font-bold text-slate-800"><?= htmlspecialchars($c['telephone']) ?></div>
            </td>

            <td class="p-4 md:p-5 text-center" data-label="Statut">
                <select onchange="updateStatut(<?= $c['id'] ?>, this.value)"
                        class="text-[10px] font-black uppercase px-3 py-1 rounded-full cursor-pointer outline-none transition <?= $statutClass ?>">
                    <option value="En attente" <?= $c['statut']=='En attente'?'selected':'' ?>>En attente</option>
                    <option value="Validé"     <?= $c['statut']=='Validé'   ?'selected':'' ?>>Validé</option>
                    <option value="Refusé"     <?= $c['statut']=='Refusé'   ?'selected':'' ?>>Refusé</option>
                </select>
                <span class="statut-print <?= $statutPrint ?>"><?= htmlspecialchars($c['statut']) ?></span>
            </td>

            <td class="p-4 md:p-5" data-label="Documents">
                <div class="flex justify-center items-center gap-2">
                    <a href="../uploads/candidatures/<?= $c['cv_url'] ?>" target="_blank"
                       class="p-2 md:px-4 md:py-2 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition">
                        <i class="fas fa-file-pdf"></i><span class="hidden md:inline ml-1">CV</span>
                    </a>
                    <a href="../uploads/candidatures/<?= $c['lettre_url'] ?>" target="_blank"
                       class="p-2 md:px-4 md:py-2 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-black transition">
                        <i class="fas fa-file-alt"></i><span class="hidden md:inline ml-1">Lettre</span>
                    </a>
                </div>
            </td>

            <td class="p-4 md:p-5 text-center no-print" data-label="Actions">
                <button onclick="supprimerCandidature(<?= $c['id'] ?>)"
                        class="group flex items-center justify-center w-9 h-9 rounded-full bg-red-50 hover:bg-red-600 transition-all mx-auto">
                    <i class="fas fa-trash-alt text-red-400 group-hover:text-white transition-colors"></i>
                </button>
            </td>
        </tr>
    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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

        <!-- Pied de page impression -->
        <div class="print-header hidden mt-8 pt-4 border-t border-slate-200 flex justify-between items-center">
            <p style="font-size:0.65rem;color:#94a3b8;">Document confidentiel — Gala Agro SARL © <?= date('Y') ?></p>
            <p style="font-size:0.65rem;color:#94a3b8;">Total : <?= count($candidatures) ?> candidat(s)</p>
        </div>

    </div>
</main>

<script>
// Données des candidatures, fournies par le serveur pour la génération du PDF
const candidatsData = <?= json_encode(array_map(function($c) {
    return [
        'nom'    => $c['nom_complet'],
        'poste'  => $c['poste'],
        'email'  => $c['email'],
        'tel'    => $c['telephone'],
        'statut' => $c['statut'],
        'date'   => !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : ''
    ];
}, $candidatures), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
</script>

<script>
// ══ TÉLÉCHARGEMENT PDF (jsPDF + AutoTable — génération vectorielle fiable) ══
function telechargerPDF() {
    const btn = document.getElementById('btn-download-pdf');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération en cours...';

    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const pageWidth  = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();

      
      // ── BANDEAU D'EN-TÊTE (BLANC AVEC TEXTE SLATE BOLD ET LIGNE VERTE) ──

// 1. Fond blanc du bandeau
doc.setFillColor(255, 255, 255);
doc.rect(0, 0, pageWidth, 26, 'F');

// 2. Soulignage vert sous l'en-tête (trait de 0.5mm)
doc.setDrawColor(22, 163, 74); 
doc.setLineWidth(0.5);
doc.line(10, 25, pageWidth - 10, 25);

// 3. Carré du logo : Remplissage VERT, "G" blanc
doc.setFillColor(22, 163, 74); 
doc.roundedRect(10, 6, 13, 13, 3, 3, 'F'); 

doc.setTextColor(255, 255, 255); // "G" blanc
doc.setFont('helvetica', 'bold');
doc.setFontSize(13);
doc.text('G', 16.5, 15.3, { align: 'center' });

// 4. Titres : Couleur Slate (#475569) et Gras (Bold)
doc.setTextColor(71, 85, 105); // Slate 600
doc.setFont('helvetica', 'bold');

// Titre Gauche
doc.setFontSize(16);
doc.text('Mayonnaise GALA', 28, 12.5);
doc.setFontSize(8.5);
doc.text('Service des Ressources Humaines · Douala, Cameroun', 28, 18);

// 5. Texte à droite : Couleur Slate (#475569) et Gras (Bold)
doc.setFontSize(12);
doc.text('Liste des Candidaures', pageWidth - 10, 12.5, { align: 'right' });
doc.setFontSize(8);
doc.setFont('helvetica', 'normal'); // Date en normal pour le contraste
const now = new Date();
const dateEdition = now.toLocaleDateString('fr-FR') + ' à ' + now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
doc.text('Édité le ' + dateEdition + ' · Confidentiel', pageWidth - 10, 18, { align: 'right' });

        // ── TABLEAU ──
        const body = candidatsData.map((c, i) => [i + 1, c.nom, c.poste, c.email, c.tel, c.statut, c.date]);

        doc.autoTable({
            startY: 30,
            head: [['Nº', 'Candidat', 'Poste', 'Email', 'Téléphone', 'Statut', 'Date']],
            body: body,
            theme: 'striped',
            styles: { fontSize: 9, cellPadding: 3, textColor: [30, 41, 59], lineColor: [241, 245, 249] },
            headStyles: { fillColor: [22, 163, 74], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'left' },
            alternateRowStyles: { fillColor: [248, 250, 252] },
            columnStyles: {
                0: { cellWidth: 10, halign: 'center' },
                5: { halign: 'center', cellWidth: 26 },
                6: { halign: 'center', cellWidth: 24 }
            },
            margin: { left: 10, right: 10, bottom: 16 },
            didParseCell: function (data) {
                if (data.section === 'body' && data.column.index === 5) {
                    const val = data.cell.raw;
                    if (val === 'Validé')      { data.cell.styles.textColor = [21, 128, 61]; data.cell.styles.fillColor = [220, 252, 231]; data.cell.styles.fontStyle = 'bold'; }
                    else if (val === 'Refusé') { data.cell.styles.textColor = [185, 28, 28]; data.cell.styles.fillColor = [254, 226, 226]; data.cell.styles.fontStyle = 'bold'; }
                    else                       { data.cell.styles.textColor = [146, 64, 14]; data.cell.styles.fillColor = [254, 243, 199]; data.cell.styles.fontStyle = 'bold'; }
                }
            },
            didDrawPage: function (data) {
                doc.setFontSize(7.5);
                doc.setTextColor(148, 163, 184);
                doc.text('Document confidentiel — Gala Agro SARL © ' + now.getFullYear(), 10, pageHeight - 8);
                doc.text('Total : ' + candidatsData.length + ' candidat(s)  ·  Page ' + data.pageNumber, pageWidth - 10, pageHeight - 8, { align: 'right' });
            }
        });

        if (candidatsData.length === 0) {
            doc.setFontSize(11);
            doc.setTextColor(148, 163, 184);
            doc.text('Aucune candidature enregistrée pour le moment.', pageWidth / 2, 45, { align: 'center' });
        }

        doc.save('candidatures_gala_' + now.toISOString().slice(0, 10) + '.pdf');
    } catch (err) {
        console.error('Erreur génération PDF :', err);
        alert("Une erreur est survenue lors de la génération du PDF.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
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

// ══ STATUT ══
function updateStatut(id, nouveauStatut) {
    fetch('update_candidature_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&statut=${nouveauStatut}`
    })
    .then(r => r.json())
    .then(data => { if(data.success) location.reload(); else alert("Erreur lors de la mise à jour."); });
}

// ══ SUPPRESSION ══
function supprimerCandidature(id) {
    Swal.fire({
        title: 'Supprimer ce candidat ?',
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
            fetch('supprimer_candidature.php?id=' + id)
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    const row = document.querySelector(`tr[data-id='${id}']`);
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 500);
                    Swal.fire('Supprimé !', 'Le candidat a été retiré.', 'success');
                }
            });
        }
    });
}
</script>
</body>
</html>