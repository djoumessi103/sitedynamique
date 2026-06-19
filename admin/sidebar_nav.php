<?php
/* ═══════════════════════════════════════════════════════
   SIDEBAR GALA AGRO — À coller dans chaque page admin
   Détecte automatiquement la page active via PHP
   ═══════════════════════════════════════════════════════ */
$cp = basename($_SERVER['PHP_SELF']);
function navClass(string $file, string $current): string {
    return $current === $file ? 'nav-item active' : 'nav-item';
}
?>

<!-- ══════════════════════════════════════════
     STYLES SIDEBAR (coller dans votre <style>)
══════════════════════════════════════════ -->
<style>
:root {
    --green:      #16a34a;
    --green-light:#dcfce7;
    --dark:       #0f172a;
    --border:     #e2e8f0;
    --bg:         #f1f5f9;
    --red:        #ef4444;
    --sidebar-w:  260px;
}

/* ── Layout ── */
.layout { display: flex; min-height: 100vh; }
.main   { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; }

/* ── Sidebar ── */
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

/* ── Brand ── */
.sidebar-brand {
    display: flex; align-items: center; gap: 12px;
    padding: 24px 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    position: relative; z-index: 1;
}
.brand-logo {
    width: 42px; height: 42px; border-radius: 13px;
    background: linear-gradient(135deg, #16a34a, #22c55e);
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 18px; color: #fff;
    box-shadow: 0 4px 16px rgba(22,163,74,.5), 0 0 0 1px rgba(34,197,94,.3);
    flex-shrink: 0; font-family: 'Inter', sans-serif;
}
.brand-name { font-size: .95rem; font-weight: 800; color: #fff; letter-spacing: -.01em; }
.brand-sub  { font-size: .58rem; font-weight: 600; color: rgba(255,255,255,.35);
              text-transform: uppercase; letter-spacing: .14em; margin-top: 1px; }

/* ── Nav body ── */
.nav-body { flex: 1; overflow-y: auto; padding: 12px 10px; scrollbar-width: none; position: relative; z-index: 1; }
.nav-body::-webkit-scrollbar { display: none; }

.nav-label {
    font-size: .58rem; font-weight: 700; letter-spacing: .2em;
    color: rgba(255,255,255,.25); text-transform: uppercase;
    padding: 14px 12px 6px;
}

/* ── Nav links ── */
.nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 12px; margin-bottom: 2px;
    color: rgba(255,255,255,.55); font-weight: 500; font-size: .875rem;
    text-decoration: none; position: relative;
    transition: background .18s, color .18s;
    font-family: 'Inter', sans-serif;
}
.nav-item:hover  { background: rgba(255,255,255,.07); color: rgba(255,255,255,.9); }
.nav-item.active { background: rgba(22,163,74,.22);   color: #4ade80; font-weight: 600; }
.nav-item.active::before {
    content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
    height: 60%; width: 3px; border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #16a34a, #22c55e);
}

/* ── Nav icon ── */
.nav-icon {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    transition: background .18s;
}
.nav-item.active .nav-icon { background: rgba(22,163,74,.3); border-color: rgba(34,197,94,.3); }
.nav-item:hover  .nav-icon { background: rgba(255,255,255,.1); }
.nav-text  { flex: 1; }

/* ── Badge rouge ── */
.nav-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 6px;
    border-radius: 10px; font-size: .63rem; font-weight: 700;
    background: #ef4444; color: #fff;
}

/* ── Sidebar footer ── */
.sidebar-footer {
    padding: 12px 10px 20px;
    border-top: 1px solid rgba(255,255,255,.06);
    position: relative; z-index: 1;
}
.nav-logout {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 12px;
    color: rgba(239,68,68,.7); font-weight: 600; font-size: .875rem;
    text-decoration: none;
    transition: background .18s, color .18s;
    font-family: 'Inter', sans-serif;
}
.nav-logout:hover { background: rgba(239,68,68,.1); color: #ef4444; }
.nav-logout .nav-icon { border-color: rgba(239,68,68,.2); }

/* ── Overlay mobile ── */
#side-overlay {
    position: fixed; inset: 0; z-index: 850;
    background: transparent; pointer-events: none;
    transition: background .35s;
}
#side-overlay.active { background: rgba(15,23,42,.65); pointer-events: auto; }

/* ── Hamburger mobile ── */
#admin-menu-btn {
    width: 42px; height: 42px;
    display: none; flex-direction: column;
    align-items: center; justify-content: center; gap: 5px;
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: 12px; cursor: pointer;
    transition: background .25s, border-color .25s, transform .15s;
}
#admin-menu-btn:active { transform: scale(.93); }
#admin-menu-btn.open   { background: var(--green-light); border-color: rgba(22,163,74,.3); }
.abar {
    display: block; height: 2px; border-radius: 99px;
    background: #0f172a; transform-origin: center;
    transition: transform .4s cubic-bezier(.23,1,.32,1), opacity .25s, width .3s;
}
.abar:nth-child(1) { width: 20px; }
.abar:nth-child(2) { width: 14px; align-self: flex-start; margin-left: 9px; }
.abar:nth-child(3) { width: 18px; }
#admin-menu-btn.open .abar:nth-child(1) { width: 20px; transform: translateY(7px) rotate(45deg); }
#admin-menu-btn.open .abar:nth-child(2) { opacity: 0; transform: scaleX(0); }
#admin-menu-btn.open .abar:nth-child(3) { width: 20px; transform: translateY(-7px) rotate(-45deg); }

/* ── Responsive ── */
@media (max-width: 1024px) {
    .sidebar { transform: translateX(-105%); }
    .sidebar.open { transform: translateX(0); }
    .main { margin-left: 0; }
    #admin-menu-btn { display: flex; }
}
</style>


<!-- ══════════════════════════════════════════
     HTML SIDEBAR (coller dans votre <body>)
══════════════════════════════════════════ -->

<div id="side-overlay"></div>

<aside class="sidebar" id="sidebar" role="navigation" aria-label="Navigation admin">

    <!-- Logo / Brand -->
    <div class="sidebar-brand">
        <div class="brand-logo">G</div>
        <div>
            <div class="brand-name">Gala Agro</div>
            <div class="brand-sub">Administration</div>
        </div>
    </div>

    <!-- Liens de navigation -->
    <nav class="nav-body">

        <div class="nav-label">Principal</div>

        <a href="dashboard.php" class="<?= navClass('dashboard.php', $cp) ?>">
            <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
            <span class="nav-text">Tableau de bord</span>
        </a>

        <a href="admin_commandes.php" class="<?= navClass('admin_commandes.php', $cp) ?>">
            <div class="nav-icon"><i class="fas fa-shopping-bag"></i></div>
            <span class="nav-text">Commandes</span>
        </a>

        <a href="products_manager.php" class="<?= navClass('products_manager.php', $cp) ?>">
            <div class="nav-icon"><i class="fas fa-box-open"></i></div>
            <span class="nav-text">Produits</span>
        </a>

        <div class="nav-label">Clients &amp; RH</div>

        <a href="messages.php" class="<?= navClass('messages.php', $cp) ?>" id="nav-messages">
            <div class="nav-icon"><i class="fas fa-envelope"></i></div>
            <span class="nav-text">Messages</span>
            <span class="nav-badge" id="sidebar-msg-badge" style="display:none">0</span>
        </a>

        <a href="voir_candidatures.php" class="<?= navClass('voir_candidatures.php', $cp) ?>" id="nav-cand">
            <div class="nav-icon"><i class="fas fa-user-tie"></i></div>
            <span class="nav-text">Candidatures</span>
            <span class="nav-badge" id="sidebar-cand-badge" style="display:none">0</span>
        </a>

        <div class="nav-label">Contenu</div>

        <a href="gallery.php" class="<?= navClass('gallery.php', $cp) ?>">
            <div class="nav-icon"><i class="fas fa-images"></i></div>
            <span class="nav-text">Galerie</span>
        </a>

    </nav>

    <!-- Déconnexion -->
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-logout">
            <div class="nav-icon" style="background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.18);">
                <i class="fas fa-sign-out-alt" style="color:#ef4444;"></i>
            </div>
            <span>Déconnexion</span>
        </a>
    </div>

</aside>


<!-- ══════════════════════════════════════════
     JS HAMBURGER MOBILE (coller avant </body>)
══════════════════════════════════════════ -->
<script>
(function () {
    const btn     = document.getElementById('admin-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('side-overlay');
    if (!btn || !sidebar) return;

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        btn.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', e => {
        e.stopPropagation();
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);
    sidebar.querySelectorAll('a').forEach(l =>
        l.addEventListener('click', () => { if (window.innerWidth < 1024) closeSidebar(); })
    );
})();
</script>
