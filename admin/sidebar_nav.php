<?php
/* ═══════════════════════════════════════════════════════
   SIDEBAR GALA AGRO — À coller dans chaque page admin
   Détecte automatiquement la page active + filtre les liens par rôle
   ═══════════════════════════════════════════════════════ */
$cp = basename($_SERVER['PHP_SELF']);
function navClass(string $file, string $current): string {
    return $current === $file ? 'nav-item active' : 'nav-item';
}

$sb_role = $_SESSION['role'] ?? 'commercial';

// Matrice d'accès
$sb_seeDashboard = in_array($sb_role, ['admin', 'rh', 'commercial'], true);
$sb_seeCommandes = in_array($sb_role, ['admin', 'commercial'], true);
$sb_seeProduits  = $sb_role === 'admin';
$sb_seeMessages  = in_array($sb_role, ['admin', 'commercial'], true);
$sb_seeCand      = in_array($sb_role, ['admin', 'rh'], true);
$sb_seeGalerie   = $sb_role === 'admin';
?>
<style>
/* ═══════════════════════════════════════════
   VARIABLES GLOBALES
═══════════════════════════════════════════ */
:root {
    --green:       #16a34a;
    --green-light: #dcfce7;
    --green-mid:   #22c55e;
    --dark:        #0f172a;
    --slate:       #64748b;
    --border:      #e2e8f0;
    --bg:          #f1f5f9;
    --red:         #ef4444;
    --white:       #ffffff;
    --sidebar-w:   260px;
    --header-h:    68px;
}

/* ═══════════════════════════════════════════
   LAYOUT
═══════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; }
.layout { display: flex; min-height: 100vh; }
.main {
    margin-left: var(--sidebar-w);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    transition: margin-left .38s cubic-bezier(.16,1,.3,1);
}

/* ═══════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════ */
.sidebar {
    width: var(--sidebar-w);
    background: #0f172a;
    position: fixed; top: 0; left: 0; bottom: 0;
    display: flex; flex-direction: column;
    z-index: 900;
    transition: transform .38s cubic-bezier(.16,1,.3,1);
    overflow: hidden;
}
.sidebar::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 30% 0%, rgba(22,163,74,.18) 0%, transparent 60%);
    pointer-events: none;
}

/* Brand */
.sidebar-brand {
    display: flex; align-items: center; gap: 12px;
    padding: 22px 20px 18px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    position: relative; z-index: 1;
    flex-shrink: 0;
}
.brand-logo {
    width: 42px; height: 42px; border-radius: 13px;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 18px; color: #fff;
    box-shadow: 0 4px 16px rgba(22,163,74,.5);
    flex-shrink: 0;
}
.brand-name { font-size: .95rem; font-weight: 800; color: #fff; letter-spacing: -.02em; }
.brand-sub  { font-size: .58rem; font-weight: 600; color: rgba(255,255,255,.35);
              text-transform: uppercase; letter-spacing: .14em; margin-top: 2px; }

/* Nav body */
.nav-body {
    flex: 1; overflow-y: auto; padding: 10px;
    scrollbar-width: none; position: relative; z-index: 1;
}
.nav-body::-webkit-scrollbar { display: none; }
.nav-label {
    font-size: .58rem; font-weight: 700; letter-spacing: .18em;
    color: rgba(255,255,255,.25); text-transform: uppercase;
    padding: 14px 12px 5px;
}

/* Nav item */
.nav-item {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 13px; border-radius: 11px; margin-bottom: 2px;
    color: rgba(255,255,255,.55); font-weight: 500; font-size: .875rem;
    text-decoration: none; position: relative;
    transition: background .16s, color .16s;
}
.nav-item:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.9); }
.nav-item:hover .nav-icon { background: rgba(255,255,255,.1); }
.nav-item.active { background: rgba(22,163,74,.22); color: #4ade80; font-weight: 600; }
.nav-item.active::before {
    content: ''; position: absolute; left: 0; top: 50%;
    transform: translateY(-50%);
    height: 55%; width: 3px; border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #16a34a, #22c55e);
}
.nav-item.active .nav-icon { background: rgba(22,163,74,.3); border-color: rgba(34,197,94,.3); }

/* Nav icon */
.nav-icon {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; color: inherit;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    transition: background .16s, border-color .16s;
}
.nav-text { flex: 1; }
.nav-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 6px;
    border-radius: 10px; font-size: .62rem; font-weight: 700;
    background: var(--red); color: #fff;
    box-shadow: 0 2px 8px rgba(239,68,68,.35);
}

/* Sidebar footer */
.sidebar-footer {
    padding: 10px 10px 22px;
    border-top: 1px solid rgba(255,255,255,.06);
    position: relative; z-index: 1; flex-shrink: 0;
}
.nav-logout {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 13px; border-radius: 11px;
    color: rgba(239,68,68,.75); font-weight: 600; font-size: .875rem;
    text-decoration: none;
    transition: background .16s, color .16s;
}
.nav-logout:hover { background: rgba(239,68,68,.1); color: #ef4444; }
.nav-logout .nav-icon { color: inherit; background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }

.nav-external {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 13px; border-radius: 11px; margin-bottom: 4px;
    color: rgba(255,255,255,.55); font-weight: 600; font-size: .875rem;
    text-decoration: none;
    transition: background .16s, color .16s;
}
.nav-external:hover { background: rgba(255,255,255,.07); color: #fff; }
.nav-external .nav-icon { color: inherit; background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }
.nav-external:hover .nav-icon { background: rgba(34,197,94,.18); border-color: rgba(34,197,94,.3); color: #4ade80; }

/* ═══════════════════════════════════════════
   OVERLAY MOBILE
═══════════════════════════════════════════ */
#side-overlay {
    position: fixed; inset: 0; z-index: 850;
    background: transparent; pointer-events: none;
    transition: background .35s ease, backdrop-filter .35s ease;
    backdrop-filter: blur(0px);
}
#side-overlay.active {
    background: rgba(15,23,42,.6);
    pointer-events: auto;
    backdrop-filter: blur(2px);
}

/* ═══════════════════════════════════════════
   HAMBURGER
═══════════════════════════════════════════ */
#admin-menu-btn {
    width: 42px; height: 42px;
    display: none;
    flex-direction: column;
    align-items: center; justify-content: center; gap: 5px;
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: 12px; cursor: pointer;
    transition: background .2s, border-color .2s, transform .12s;
    flex-shrink: 0;
}
#admin-menu-btn:hover  { background: var(--bg); border-color: #cbd5e1; }
#admin-menu-btn:active { transform: scale(.92); }
#admin-menu-btn.open   { background: var(--green-light); border-color: #86efac; }

.abar {
    display: block; height: 2px; border-radius: 99px;
    background: var(--dark); transform-origin: center;
    transition: transform .42s cubic-bezier(.23,1,.32,1), opacity .25s ease, width .32s cubic-bezier(.23,1,.32,1);
}
.abar:nth-child(1) { width: 20px; }
.abar:nth-child(2) { width: 13px; align-self: flex-start; margin-left: 9px; }
.abar:nth-child(3) { width: 17px; }
#admin-menu-btn.open .abar:nth-child(1) { width: 20px; transform: translateY(7px)  rotate(45deg);  }
#admin-menu-btn.open .abar:nth-child(2) { opacity: 0;  transform: scaleX(0);                       }
#admin-menu-btn.open .abar:nth-child(3) { width: 20px; transform: translateY(-7px) rotate(-45deg); }

/* ═══════════════════════════════════════════
   TABLETTE 769px–1024px : sidebar icônes seules
═══════════════════════════════════════════ */
@media (max-width: 1024px) and (min-width: 769px) {
    :root { --sidebar-w: 72px; }
    .sidebar { width: 72px; overflow: visible; }
    .sidebar-brand { padding: 20px 14px; justify-content: center; }
    .brand-name, .brand-sub { display: none; }
    .nav-label { display: none; }
    .nav-item { justify-content: center; padding: 11px; border-radius: 12px; }
    .nav-item .nav-text, .nav-item .nav-badge { display: none; }
    .nav-item:hover::after {
        content: attr(data-label);
        position: absolute; left: calc(100% + 12px); top: 50%;
        transform: translateY(-50%);
        white-space: nowrap;
        background: var(--dark); color: #fff;
        font-size: .75rem; font-weight: 600;
        padding: 6px 12px; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,.18);
        pointer-events: none; z-index: 9999;
    }
    .nav-item:hover::before { display: none; }
    .nav-item.active::after { display: none; }
    .nav-icon { width: 38px; height: 38px; font-size: 15px; margin: 0 auto; }
    .nav-logout, .nav-external { justify-content: center; padding: 11px; }
    .nav-logout span, .nav-external span { display: none; }
    .nav-logout .nav-icon, .nav-external .nav-icon { margin: 0 auto; }
}

/* ═══════════════════════════════════════════
   MOBILE ≤768px : drawer latéral
═══════════════════════════════════════════ */
@media (max-width: 768px) {
    :root { --sidebar-w: 0px; }

    .sidebar {
        width: 270px;
        transform: translateX(-100%);
        overflow: hidden;
        box-shadow: none;
    }
    .sidebar.open {
        transform: translateX(0);
        box-shadow: 10px 0 40px rgba(0,0,0,.22);
    }

    /* Force texte complet dans le drawer (annule styles tablette icônes) */
    .sidebar .sidebar-brand { padding: 22px 20px 18px !important; justify-content: flex-start !important; }
    .sidebar .brand-name { display: block !important; }
    .sidebar .brand-sub  { display: block !important; }
    .sidebar .nav-label  { display: block !important; padding: 14px 12px 5px !important; }
    .sidebar .nav-item   { justify-content: flex-start !important; padding: 10px 13px !important; }
    .sidebar .nav-item .nav-text  { display: inline !important; }
    .sidebar .nav-item .nav-badge { display: inline-flex !important; }
    .sidebar .nav-icon { width: 34px !important; height: 34px !important; margin: 0 !important; font-size: 13px !important; }
    .sidebar .nav-logout { justify-content: flex-start !important; padding: 10px 13px !important; }
    .sidebar .nav-logout span { display: inline !important; }
    .sidebar .nav-item:hover::after { content: none !important; display: none !important; }

    .main { margin-left: 0 !important; }
    #admin-menu-btn { display: flex !important; }
}
</style>

<div id="side-overlay"></div>

<aside class="sidebar" id="sidebar" role="navigation" aria-label="Navigation admin">

    <div class="sidebar-brand">
        <div class="brand-logo">G</div>
        <div>
            <div class="brand-name">Gala Agro</div>
            <div class="brand-sub">Administration</div>
        </div>
    </div>

    <nav class="nav-body">

        <?php if ($sb_seeDashboard || $sb_seeCommandes): ?>
        <div class="nav-label">Principal</div>
        <?php endif; ?>

        <?php if ($sb_seeDashboard): ?>
        <a href="dashboard.php"
           class="<?= navClass('dashboard.php', $cp) ?>"
           data-label="Tableau de bord">
            <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
            <span class="nav-text">Tableau de bord</span>
        </a>
        <?php endif; ?>

        <?php if ($sb_seeCommandes): ?>
        <a href="admin_commandes.php"
           class="<?= navClass('admin_commandes.php', $cp) ?>"
           data-label="Commandes"
           id="nav-commandes">
            <div class="nav-icon"><i class="fas fa-shopping-bag"></i></div>
            <span class="nav-text">Commandes</span>
            <span class="nav-badge" id="sidebar-cmd-badge" style="display:none">0</span>
        </a>
        <?php endif; ?>

        <?php if ($sb_seeProduits): ?>
        <a href="products_manager.php"
           class="<?= navClass('products_manager.php', $cp) ?>"
           data-label="Produits">
            <div class="nav-icon"><i class="fas fa-box-open"></i></div>
            <span class="nav-text">Produits</span>
            <span class="nav-badge" id="sidebar-stock-badge" style="display:none">0</span>
        </a>
        <?php else: ?>
            <?php if ($sb_role === 'commercial'): ?>
            <?php
            try {
                require_once dirname(__DIR__).'/includes/db.php';
                $sb_rupture = $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();
            } catch(Throwable $e) { $sb_rupture = 0; }
            ?>
            <?php if ($sb_rupture > 0): ?>
            <div class="nav-item" style="cursor:default;opacity:.85;" title="Rupture de stock — contactez l'admin">
                <div class="nav-icon" style="background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.25);">
                    <i class="fas fa-triangle-exclamation" style="color:#ef4444;"></i>
                </div>
                <span class="nav-text" style="color:rgba(255,255,255,.7);">Rupture stock</span>
                <span class="nav-badge" style="background:#ef4444;"><?= $sb_rupture ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($sb_seeMessages || $sb_seeCand): ?>
        <div class="nav-label">Clients &amp; RH</div>
        <?php endif; ?>

        <?php if ($sb_seeMessages): ?>
        <a href="messages.php"
           class="<?= navClass('messages.php', $cp) ?>"
           data-label="Messages"
           id="nav-messages">
            <div class="nav-icon"><i class="fas fa-envelope"></i></div>
            <span class="nav-text">Messages</span>
            <span class="nav-badge" id="sidebar-msg-badge" style="display:none">0</span>
        </a>
        <?php endif; ?>

        <?php if ($sb_seeCand): ?>
        <a href="voir_candidatures.php"
           class="<?= navClass('voir_candidatures.php', $cp) ?>"
           data-label="Candidatures"
           id="nav-cand">
            <div class="nav-icon"><i class="fas fa-user-tie"></i></div>
            <span class="nav-text">Candidatures</span>
            <span class="nav-badge" id="sidebar-cand-badge" style="display:none">0</span>
        </a>
        <?php endif; ?>

        <?php if ($sb_seeGalerie): ?>
        <div class="nav-label">Contenu</div>
        <a href="gallery.php"
           class="<?= navClass('gallery.php', $cp) ?>"
           data-label="Galerie">
            <div class="nav-icon"><i class="fas fa-images"></i></div>
            <span class="nav-text">Galerie</span>
        </a>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="../index.php" target="_blank" rel="noopener" class="nav-logout" data-label="Consulter le site" style="color:rgba(255,255,255,.55);">
            <div class="nav-icon"><i class="fas fa-arrow-up-right-from-square"></i></div>
            <span>Consulter le site</span>
        </a>
        <a href="logout.php" class="nav-logout" data-label="Déconnexion">
            <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
            <span>Déconnexion</span>
        </a>
    </div>

</aside>

<script>
(function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('side-overlay');
    if (!sidebar) return;

    function openSidebar() {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        const btn = document.getElementById('admin-menu-btn');
        if (btn) { btn.classList.add('open'); btn.setAttribute('aria-expanded', 'true'); }
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        const btn = document.getElementById('admin-menu-btn');
        if (btn) { btn.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); }
        document.body.style.overflow = '';
    }

    // ══ Délégation d'événement sur le bouton hamburger ══
    // Fonctionne même si #admin-menu-btn est ajouté au DOM APRÈS ce script
    // (topbar mobile placée plus bas dans la page, après l'include sidebar_nav.php)
    document.addEventListener('click', e => {
        const btn = e.target.closest('#admin-menu-btn');
        if (!btn) return;
        e.stopPropagation();
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    if (overlay) overlay.addEventListener('click', closeSidebar);
    sidebar.querySelectorAll('a').forEach(l =>
        l.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); })
    );
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });

    // ══ Compatibilité : ancien tiroir mobile "amn-*" sur les pages qui l'ont encore ══
    const mobileNav = document.getElementById('admin-mobile-nav');
    const mobileClose = document.getElementById('admin-nav-close');
    const mobileOverlay = document.getElementById('admin-overlay');
    if (mobileNav) {
        function openMobile()  { mobileNav.classList.add('active');  if(mobileOverlay) mobileOverlay.classList.add('active');  document.body.style.overflow='hidden'; }
        function closeMobile() { mobileNav.classList.remove('active'); if(mobileOverlay) mobileOverlay.classList.remove('active'); document.body.style.overflow=''; }
        document.addEventListener('click', e => { if (e.target.closest('#admin-menu-btn')) { mobileNav.classList.contains('active') ? closeMobile() : openMobile(); } });
        if (mobileClose) mobileClose.addEventListener('click', closeMobile);
        if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobile);
        mobileNav.querySelectorAll('a').forEach(l => l.addEventListener('click', closeMobile));
    }
})();

// ══════════════════════════════════════════
//  BADGES SIDEBAR — alimentés depuis l'API
//  centrale de dashboard.php (mêmes notifs
//  partout, pas seulement sur le tableau de bord)
// ══════════════════════════════════════════
(function () {
    // dashboard.php a déjà sa propre fonction fetchNotifications() plus complète
    // (elle gère aussi la cloche) — on évite de sonder deux fois la même API ici.
    // (Vérifié sur l'URL plutôt que sur l'existence de la fonction, qui n'est
    // définie que plus bas dans la page au moment où ce script s'exécute.)
    if (/dashboard\.php/i.test(location.pathname)) return;

    const sbMsg   = document.getElementById('sidebar-msg-badge');
    const sbCmd   = document.getElementById('sidebar-cmd-badge');
    const sbCand  = document.getElementById('sidebar-cand-badge');
    const sbStock = document.getElementById('sidebar-stock-badge');
    if (!sbMsg && !sbCmd && !sbCand && !sbStock) return; // aucun badge présent pour ce rôle

    function setBadge(el, n) {
        if (!el) return;
        el.textContent = n;
        el.style.display = n > 0 ? 'inline-flex' : 'none';
    }

    // Construire le chemin vers dashboard.php depuis n'importe quelle page admin
    const dashPath = (function() {
        const path = location.pathname;
        const adminDir = path.substring(0, path.lastIndexOf('/') + 1);
        return adminDir + 'dashboard.php';
    })();

    async function pollSidebarBadges() {
        try {
            const res  = await fetch(dashPath + '?api=notifications&_=' + Date.now());
            if (!res.ok) return;
            const data = await res.json();
            setBadge(sbMsg,   (data.messages     || []).length);
            setBadge(sbCmd,   (data.commandes    || []).length);
            setBadge(sbCand,  (data.candidatures || []).length);
            setBadge(sbStock, (data.stock        || []).length);
        } catch (e) { /* silencieux — page hors session ou réseau coupé */ }
    }
    pollSidebarBadges();
    setInterval(pollSidebarBadges, 15000); // toutes les 15s pour plus de réactivité
})();
</script>