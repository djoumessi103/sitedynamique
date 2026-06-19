<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';

// ═══════════════════════════════════════════════════════
//  ÉTAT DES NOTIFICATIONS — persistant en base (et non en
//  session), pour ne JAMAIS perdre le suivi entre deux
//  connexions, onglets ou appareils différents.
// ═══════════════════════════════════════════════════════
function gala_notif_state(PDO $pdo): array {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_notif_state (
        id INT PRIMARY KEY,
        last_seen_messages DATETIME NULL,
        last_seen_candidatures DATETIME NULL,
        last_seen_commandes DATETIME NULL
    )");

    $row = $pdo->query("SELECT * FROM admin_notif_state WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // Première activation : on considère l'historique existant comme "déjà vu"
        // pour ne pas notifier d'un coup de dizaines d'anciennes entrées.
        $pdo->exec("INSERT INTO admin_notif_state (id, last_seen_messages, last_seen_candidatures, last_seen_commandes)
                    VALUES (1, NOW(), NOW(), NOW())");
        $row = $pdo->query("SELECT * FROM admin_notif_state WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    }

    // Sécurité : si une colonne existait déjà mais est NULL (ex: ajout récent de la colonne commandes)
    if (empty($row['last_seen_commandes'])) {
        $pdo->exec("UPDATE admin_notif_state SET last_seen_commandes = NOW() WHERE id = 1");
        $row['last_seen_commandes'] = date('Y-m-d H:i:s');
    }

    return $row;
}

// ═══════════════════════════════════════════════════════
//  API JSON — NOTIFICATIONS EN TEMPS RÉEL
//  (Messages de contact + Candidatures + Commandes)
// ═══════════════════════════════════════════════════════
if (isset($_GET['api'])) {
    // On capture toute sortie parasite (warning PHP, BOM, espace...)
    // pour qu'elle ne vienne jamais corrompre le JSON renvoyé.
    ob_start();

    try {
        $state = gala_notif_state($pdo);

        if ($_GET['api'] === 'notifications') {
            $newMessages = $pdo->prepare(
                "SELECT id, nom_complet, message, telephone, date_envoi
                 FROM contacts WHERE date_envoi > ? ORDER BY date_envoi DESC LIMIT 8"
            );
            $newMessages->execute([$state['last_seen_messages']]);
            $msgs = $newMessages->fetchAll(PDO::FETCH_ASSOC);

            $newCandidatures = $pdo->prepare(
                "SELECT id, nom_complet, poste, email, telephone, created_at
                 FROM candidatures WHERE created_at > ? ORDER BY created_at DESC LIMIT 8"
            );
            $newCandidatures->execute([$state['last_seen_candidatures']]);
            $cands = $newCandidatures->fetchAll(PDO::FETCH_ASSOC);

            $newCommandes = $pdo->prepare(
                "SELECT id, nom, prenom, nom_marche, region, date_commande
                 FROM commandes WHERE date_commande > ? ORDER BY date_commande DESC LIMIT 8"
            );
            $newCommandes->execute([$state['last_seen_commandes']]);
            $cmds = $newCommandes->fetchAll(PDO::FETCH_ASSOC);

            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'      => true,
                'messages'     => $msgs,
                'candidatures' => $cands,
                'commandes'    => $cmds,
                'total'        => count($msgs) + count($cands) + count($cmds)
            ]);
            exit;
        }

        if ($_GET['api'] === 'mark_read') {
            $type = $_GET['type'] ?? 'all';
            $sets = [];
            if ($type === 'all' || $type === 'messages')     $sets[] = "last_seen_messages = NOW()";
            if ($type === 'all' || $type === 'candidatures') $sets[] = "last_seen_candidatures = NOW()";
            if ($type === 'all' || $type === 'commandes')    $sets[] = "last_seen_commandes = NOW()";
            if ($sets) {
                $pdo->exec("UPDATE admin_notif_state SET " . implode(', ', $sets) . " WHERE id = 1");
            }
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'ok' => true]);
            exit;
        }

        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'invalid_action']);
        exit;

    } catch (Throwable $e) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ═══════════════════════════════════════════════════════
//  STATISTIQUES GÉNÉRALES
// ═══════════════════════════════════════════════════════
$totalMessages     = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$totalCandidatures = $pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn();
$totalCommandes    = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$totalProduits     = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Notifications initiales (pour badge au tout premier chargement de la page)
$notifState = gala_notif_state($pdo);

$initNewMsgs = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE date_envoi > ?");
$initNewMsgs->execute([$notifState['last_seen_messages']]);

$initNewCands = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE created_at > ?");
$initNewCands->execute([$notifState['last_seen_candidatures']]);

$initNewCmds = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE date_commande > ?");
$initNewCmds->execute([$notifState['last_seen_commandes']]);

$initBadge = (int)$initNewMsgs->fetchColumn() + (int)$initNewCands->fetchColumn() + (int)$initNewCmds->fetchColumn();

// Activités récentes unifiées (10 dernières) — messages + candidatures + commandes
$recentActivity = $pdo->query("
    (SELECT 'message' AS type, id, nom_complet AS nom,
            message AS detail, date_envoi AS created_at FROM contacts ORDER BY date_envoi DESC LIMIT 5)
    UNION ALL
    (SELECT 'candidature' AS type, id, nom_complet AS nom,
            poste AS detail, created_at FROM candidatures ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'commande' AS type, id, TRIM(CONCAT(nom,' ',prenom)) AS nom,
            CONCAT(nom_marche, ' · ', region) AS detail, date_commande AS created_at FROM commandes ORDER BY date_commande DESC LIMIT 5)
    ORDER BY created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Messages par mois (6 derniers mois)
$msgsByMonth = $pdo->query("
    SELECT DATE_FORMAT(date_envoi,'%b') AS mois,
           MONTH(date_envoi) AS m_num,
           COUNT(*) AS total
    FROM contacts
    WHERE date_envoi >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mois, m_num ORDER BY m_num ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Statuts candidatures
$candidStatuts = $pdo->query("
    SELECT statut, COUNT(*) AS nb FROM candidatures GROUP BY statut
")->fetchAll(PDO::FETCH_ASSOC);

$current_page = 'dashboard.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gala Agro — Tableau de Bord Admin</title>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<style>
/* ═══════════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
    font-family: 'Inter', sans-serif;
    background: #f1f5f9;
    color: #1e293b;
    min-height: 100vh;
    overflow-x: hidden;
}

/* ═══════════════════════════════════════════════
   CSS VARIABLES
═══════════════════════════════════════════════ */
:root {
    --green:      #16a34a;
    --green-light:#dcfce7;
    --green-mid:  #22c55e;
    --dark:       #0f172a;
    --dark-2:     #1e293b;
    --slate:      #64748b;
    --border:     #e2e8f0;
    --bg:         #f1f5f9;
    --white:      #ffffff;
    --red:        #ef4444;
    --amber:      #f59e0b;
    --blue:       #3b82f6;
    --purple:     #8b5cf6;
    --sidebar-w:  260px;
    --header-h:   68px;
    --radius:     16px;
    --shadow:     0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.07);
    --shadow-lg:  0 8px 32px rgba(0,0,0,.12);
}

/* ═══════════════════════════════════════════════
   LAYOUT
═══════════════════════════════════════════════ */
.layout { display: flex; min-height: 100vh; }

/* ═══════════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════════ */
.sidebar {
    width: var(--sidebar-w);
    background: var(--dark);
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
    flex-shrink: 0;
}
.brand-name { font-size: .95rem; font-weight: 800; color: #fff; letter-spacing: -.01em; }
.brand-sub  { font-size: .58rem; font-weight: 600; color: rgba(255,255,255,.35);
              text-transform: uppercase; letter-spacing: .14em; margin-top: 1px; }

/* Nav sections */
.nav-body { flex: 1; overflow-y: auto; padding: 12px 10px; scrollbar-width: none; position: relative; z-index: 1; }
.nav-body::-webkit-scrollbar { display: none; }
.nav-label {
    font-size: .58rem; font-weight: 700; letter-spacing: .2em;
    color: rgba(255,255,255,.25); text-transform: uppercase;
    padding: 14px 12px 6px;
}

.nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; border-radius: 12px; margin-bottom: 2px;
    color: rgba(255,255,255,.55); font-weight: 500; font-size: .875rem;
    text-decoration: none; position: relative;
    transition: background .18s, color .18s, transform .15s;
}
.nav-item:hover  { background: rgba(255,255,255,.07); color: rgba(255,255,255,.9); }
.nav-item.active { background: rgba(22,163,74,.22); color: #4ade80; font-weight: 600; }
.nav-item.active::before {
    content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
    height: 60%; width: 3px; border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #16a34a, #22c55e);
}
.nav-icon {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    transition: background .18s;
}
.nav-item.active .nav-icon  { background: rgba(22,163,74,.3); border-color: rgba(34,197,94,.3); }
.nav-item:hover .nav-icon   { background: rgba(255,255,255,.1); }
.nav-text  { flex: 1; }
.nav-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 6px;
    border-radius: 10px; font-size: .65rem; font-weight: 700;
    background: var(--red); color: #fff;
}

/* Sidebar footer */
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
}
.nav-logout:hover { background: rgba(239,68,68,.1); color: #ef4444; }
.nav-logout .nav-icon { border-color: rgba(239,68,68,.2); }

/* ═══════════════════════════════════════════════
   MAIN CONTENT
═══════════════════════════════════════════════ */
.main { margin-left: var(--sidebar-w); flex: 1; min-width: 0; display: flex; flex-direction: column; min-height: 100vh; }

/* ═══════════════════════════════════════════════
   HEADER
═══════════════════════════════════════════════ */
.header {
    height: var(--header-h);
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
    padding: 0 clamp(14px, 4vw, 28px);
    position: sticky; top: 0; z-index: 800;
    width: 100%;
}
.header-left { display: flex; align-items: center; gap: 16px; min-width: 0; flex: 1 1 auto; overflow: hidden; }
.header-title { font-size: 1.1rem; font-weight: 700; color: var(--dark); letter-spacing: -.02em;
                 white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.header-breadcrumb { font-size: .72rem; color: var(--slate); margin-top: 1px;
                      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Search */
.search-wrap {
    display: flex; align-items: center; gap: 10px;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 12px; padding: 9px 14px;
    width: 240px; transition: border-color .2s, box-shadow .2s;
}
.search-wrap:focus-within { border-color: var(--green); box-shadow: 0 0 0 3px rgba(22,163,74,.1); }
.search-wrap input { background: none; border: none; outline: none; font-size: .83rem;
                     color: var(--dark); font-family: inherit; flex: 1; }
.search-wrap input::placeholder { color: #94a3b8; }
.search-wrap i { color: #94a3b8; font-size: 13px; }

.header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

/* ═══════════════════════════════════════════════
   NOTIFICATION BELL — SYSTÈME COMPLET 2026
═══════════════════════════════════════════════ */
.notif-wrap { position: relative; flex-shrink: 0; }

.notif-btn {
    width: 42px; height: 42px; border-radius: 12px;
    background: var(--bg); border: 1.5px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; position: relative;
    transition: background .2s, border-color .2s, transform .15s;
    color: var(--slate);
    font-size: 16px;
}
.notif-btn:hover { background: #fff; border-color: var(--green); color: var(--green); transform: scale(1.04); }
.notif-btn.has-new { color: var(--dark); }

/* Badge */
.notif-badge {
    position: absolute; top: -4px; right: -4px;
    min-width: 18px; height: 18px; padding: 0 5px;
    border-radius: 9px; font-size: .6rem; font-weight: 800;
    background: var(--red); color: #fff;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
    transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .2s;
    pointer-events: none;
}
.notif-badge.hidden  { opacity: 0; transform: scale(0); }
.notif-badge.visible { opacity: 1; transform: scale(1); }

/* Bell shake animation */
@keyframes bellShake {
    0%,100% { transform: rotate(0deg); }
    10%,50%  { transform: rotate(-12deg); }
    30%,70%  { transform: rotate(12deg); }
    90%      { transform: rotate(-5deg); }
}
.notif-btn.shake i { animation: bellShake .6s cubic-bezier(.36,.07,.19,.97); }

/* Pulse ring */
@keyframes pulseRing {
    0%   { transform: scale(.8); opacity: .8; }
    70%  { transform: scale(1.6); opacity: 0; }
    100% { transform: scale(1.6); opacity: 0; }
}
.notif-btn.has-new::after {
    content: '';
    position: absolute; inset: -4px; border-radius: 16px;
    border: 2px solid var(--red);
    animation: pulseRing 2s ease-out infinite;
    pointer-events: none;
}

/* Dropdown panel */
.notif-dropdown {
    position: absolute; top: calc(100% + 10px); right: -10px;
    width: min(380px, 90vw); max-height: 520px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    z-index: 9999;
    transform-origin: top right;
    transform: scale(.92) translateY(-8px);
    opacity: 0;
    pointer-events: none;
    transition: transform .25s cubic-bezier(.16,1,.3,1), opacity .2s ease;
}
.notif-dropdown.open {
    transform: scale(1) translateY(0);
    opacity: 1;
    pointer-events: auto;
}

.notif-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 6px;
    padding: 16px 18px 12px;
    border-bottom: 1px solid var(--border);
}
.notif-header-title { font-size: .9rem; font-weight: 700; color: var(--dark); }
.notif-mark-all {
    font-size: .72rem; font-weight: 600; color: var(--green);
    cursor: pointer; border: none; background: none;
    padding: 4px 10px; border-radius: 8px;
    transition: background .2s;
}
.notif-mark-all:hover { background: var(--green-light); }

/* Tabs */
.notif-tabs { display: flex; gap: 4px; padding: 10px 14px 0; }
.notif-tab {
    flex: 1; padding: 8px 2px; border-radius: 10px;
    font-size: .68rem; font-weight: 600;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 4px;
    transition: background .18s, color .18s;
    background: var(--bg); color: var(--slate);
    white-space: nowrap;
}
.notif-tab.active { background: var(--dark); color: #fff; }
.notif-tab-count {
    min-width: 18px; height: 18px; padding: 0 5px;
    border-radius: 9px; font-size: .6rem; font-weight: 700;
    background: var(--red); color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
}
.notif-tab.active .notif-tab-count { background: rgba(239,68,68,.8); }

/* Notification items */
.notif-list { max-height: 340px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #e2e8f0 transparent; }
.notif-list::-webkit-scrollbar { width: 4px; }
.notif-list::-webkit-scrollbar-track { background: transparent; }
.notif-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }

.notif-panel { display: none; }
.notif-panel.active { display: block; }

.notif-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 13px 18px;
    transition: background .15s;
    cursor: pointer; position: relative;
    border-bottom: 1px solid #f8fafc;
}
.notif-item:hover { background: #f8fafc; }
.notif-item.new::after {
    content: ''; position: absolute; left: 8px; top: 50%;
    transform: translateY(-50%);
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--green);
}
.notif-avatar {
    width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .85rem; color: #fff;
    background: linear-gradient(135deg, var(--green), #22c55e);
}
.notif-avatar.cand { background: linear-gradient(135deg, var(--blue), var(--purple)); }
.notif-avatar.cmd  { background: linear-gradient(135deg, var(--amber), #f97316); }
.notif-item-body { flex: 1; min-width: 0; }
.notif-item-name { font-size: .82rem; font-weight: 700; color: var(--dark);
                    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.notif-item-detail { font-size: .75rem; color: var(--slate); margin-top: 2px;
                      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.notif-item-time { font-size: .65rem; color: #94a3b8; margin-top: 3px; font-weight: 500; }

.notif-empty {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 40px 20px; color: #94a3b8;
    gap: 10px;
}
.notif-empty i { font-size: 28px; opacity: .4; }
.notif-empty p { font-size: .78rem; font-weight: 500; }

/* Footer buttons */
.notif-footer { padding: 10px 14px 14px; display: flex; gap: 8px; }
.notif-footer-btn {
    flex: 1; padding: 9px 4px; border-radius: 10px; font-size: .68rem;
    font-weight: 600; border: none; cursor: pointer;
    text-align: center; text-decoration: none;
    display: flex; align-items: center; justify-content: center; gap: 4px;
    transition: background .18s, transform .12s;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.notif-footer-btn:active { transform: scale(.97); }
.notif-footer-btn.primary { background: var(--dark); color: #fff; }
.notif-footer-btn.primary:hover { background: #1e293b; }
.notif-footer-btn.secondary { background: var(--bg); color: var(--dark); border: 1px solid var(--border); }
.notif-footer-btn.secondary:hover { background: var(--border); }

/* Admin avatar */
.admin-avatar {
    width: 38px; height: 38px; border-radius: 11px;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: .82rem; cursor: pointer;
    border: 2px solid var(--border);
    transition: border-color .2s, transform .15s;
    flex-shrink: 0;
}
.admin-avatar:hover { border-color: var(--green); transform: scale(1.04); }

/* ═══════════════════════════════════════════════
   CONTENT AREA
═══════════════════════════════════════════════ */
.content { flex: 1; min-width: 0; padding: clamp(14px, 4vw, 28px); max-width: 1400px; width: 100%; margin: 0 auto; }

/* Page header */
.page-hero { margin-bottom: 26px; }
.page-hero h1 { font-size: 1.65rem; font-weight: 800; color: var(--dark); letter-spacing: -.03em; }
.page-hero p { font-size: .85rem; color: var(--slate); margin-top: 4px; }
.page-hero-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.date-chip {
    display: flex; align-items: center; gap: 7px;
    background: #fff; border: 1px solid var(--border);
    border-radius: 10px; padding: 7px 14px;
    font-size: .77rem; font-weight: 600; color: var(--slate);
}
.date-chip i { color: var(--green); font-size: 11px; }

/* ═══════════════════════════════════════════════
   KPI CARDS
═══════════════════════════════════════════════ */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }

.kpi-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 22px 22px 18px;
    position: relative; overflow: hidden;
    box-shadow: var(--shadow);
    transition: transform .2s, box-shadow .2s;
    cursor: default;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
.kpi-card::after {
    content: '';
    position: absolute; top: 0; left: 0; right: 0;
    height: 3px; border-radius: var(--radius) var(--radius) 0 0;
}
.kpi-card.green::after  { background: linear-gradient(90deg, #16a34a, #22c55e); }
.kpi-card.blue::after   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.kpi-card.amber::after  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.kpi-card.purple::after { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

.kpi-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.kpi-icon {
    width: 46px; height: 46px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.kpi-icon.green  { background: #dcfce7; color: #16a34a; }
.kpi-icon.blue   { background: #dbeafe; color: #3b82f6; }
.kpi-icon.amber  { background: #fef3c7; color: #d97706; }
.kpi-icon.purple { background: #ede9fe; color: #7c3aed; }

.kpi-trend {
    display: flex; align-items: center; gap: 4px;
    font-size: .68rem; font-weight: 700; padding: 4px 9px;
    border-radius: 8px;
}
.kpi-trend.up   { background: #dcfce7; color: #16a34a; }
.kpi-trend.down { background: #fee2e2; color: #dc2626; }
.kpi-trend.neu  { background: #f1f5f9; color: #64748b; }

.kpi-value { font-size: 2.2rem; font-weight: 900; color: var(--dark); letter-spacing: -.04em; line-height: 1; }
.kpi-label { font-size: .75rem; font-weight: 600; color: var(--slate); margin-top: 5px; text-transform: uppercase; letter-spacing: .06em; }

/* ═══════════════════════════════════════════════
   SECTION GRID (Charts + Activity)
═══════════════════════════════════════════════ */
.section-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }

.card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.card-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px 14px; border-bottom: 1px solid #f8fafc;
}
.card-title { font-size: .88rem; font-weight: 700; color: var(--dark); }
.card-sub   { font-size: .7rem; color: var(--slate); margin-top: 2px; }
.card-body  { padding: 18px 22px; }
.card-action {
    font-size: .72rem; font-weight: 600; color: var(--green);
    text-decoration: none; padding: 6px 12px; border-radius: 8px;
    border: 1px solid var(--green-light);
    transition: background .18s;
}
.card-action:hover { background: var(--green-light); }

/* ═══════════════════════════════════════════════
   ACTIVITY FEED
═══════════════════════════════════════════════ */
.activity-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 11px 0;
    border-bottom: 1px solid #f8fafc;
    transition: background .15s;
}
.activity-item:last-child { border-bottom: none; padding-bottom: 0; }
.activity-dot {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff;
}
.activity-dot.msg  { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
.activity-dot.cand { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
.activity-dot.cmd  { background: linear-gradient(135deg, #f59e0b, #fb923c); }
.activity-info { flex: 1; min-width: 0; }
.activity-name { font-size: .82rem; font-weight: 700; color: var(--dark);
                  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.activity-detail { font-size: .73rem; color: var(--slate); margin-top: 1px;
                    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.activity-time { font-size: .65rem; color: #94a3b8; font-weight: 500; flex-shrink: 0; margin-top: 3px; }

.type-tag {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .6rem; font-weight: 700; padding: 2px 7px; border-radius: 6px;
    text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px;
}
.type-tag.msg  { background: #dbeafe; color: #1d4ed8; }
.type-tag.cand { background: #ede9fe; color: #6d28d9; }
.type-tag.cmd  { background: #fef3c7; color: #b45309; }

/* Full activity table */
.section-full { margin-bottom: 24px; }

/* ═══════════════════════════════════════════════
   HAMBURGER (mobile)
═══════════════════════════════════════════════ */
#admin-menu-btn {
    width: 42px; height: 42px;
    display: none; flex-direction: column;
    align-items: center; justify-content: center; gap: 5px;
    background: var(--bg); border: 1.5px solid var(--border);
    border-radius: 12px; cursor: pointer;
    transition: background .25s, border-color .25s, transform .15s;
}
#admin-menu-btn:active { transform: scale(.93); }
#admin-menu-btn.open { background: var(--green-light); border-color: rgba(22,163,74,.3); }
.abar {
    display: block; height: 2px; border-radius: 99px;
    background: var(--dark); transform-origin: center;
    transition: transform .4s cubic-bezier(.23,1,.32,1), opacity .25s, width .3s;
}
.abar:nth-child(1) { width: 20px; }
.abar:nth-child(2) { width: 14px; align-self: flex-start; margin-left: 9px; }
.abar:nth-child(3) { width: 18px; }
#admin-menu-btn.open .abar:nth-child(1) { width: 20px; transform: translateY(7px) rotate(45deg); }
#admin-menu-btn.open .abar:nth-child(2) { opacity: 0; transform: scaleX(0); }
#admin-menu-btn.open .abar:nth-child(3) { width: 20px; transform: translateY(-7px) rotate(-45deg); }

#side-overlay {
    position: fixed; inset: 0; z-index: 850;
    background: transparent; pointer-events: none;
    transition: background .35s;
}
#side-overlay.active { background: rgba(15,23,42,.65); pointer-events: auto; }

/* ═══════════════════════════════════════════════
   RESPONSIVE — 4 PALIERS
   1024px → tablette (sidebar en tiroir)
    768px → mobile large (grilles 1 colonne, donut empilé)
    480px → mobile standard (compact)
    360px → très petit écran
═══════════════════════════════════════════════ */

/* ── 1024px : Tablette — sidebar devient un tiroir ── */
@media (max-width: 1024px) {
    .sidebar { transform: translateX(-105%); }
    .sidebar.open { transform: translateX(0); }
    .main { margin-left: 0; }
    #admin-menu-btn { display: flex; }
    .search-wrap { width: 180px; }
    .section-grid { grid-template-columns: 1fr; }
}

/* ── 768px : Mobile large ── */
@media (max-width: 768px) {
    .header { gap: 10px; }
    .header-left { gap: 12px; }
    .header-title { font-size: 1rem; }
    .header-breadcrumb {
        max-width: 200px; overflow: hidden;
        text-overflow: ellipsis; white-space: nowrap;
    }
    .header-actions { gap: 8px; }
    .search-wrap { width: 130px; padding: 8px 12px; }

    .page-hero h1 { font-size: 1.4rem; }
    .page-hero-bar { align-items: flex-start; }

    .kpi-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
    .kpi-card { padding: 18px 16px 14px; }
    .kpi-value { font-size: 1.85rem; }
    .kpi-icon { width: 40px; height: 40px; font-size: 16px; }

    .card-head { padding: 16px 18px 12px; flex-wrap: wrap; gap: 8px; }
    .card-body { padding: 16px 18px; }

    /* Donut + légende s'empilent au lieu de côte à côte */
    .donut-body {
        flex-direction: column !important;
        gap: 14px !important;
    }
}

/* ── 480px : Mobile standard ── */
@media (max-width: 480px) {
    .header { height: 60px; }
    .header-title { font-size: .92rem; }
    .header-breadcrumb { font-size: .65rem; max-width: 140px; }
    .search-wrap { display: none; }   /* place réservée aux notifs + avatar */

    .page-hero { margin-bottom: 18px; }
    .page-hero h1 { font-size: 1.25rem; }
    .page-hero p { font-size: .78rem; }
    .date-chip { font-size: .7rem; padding: 6px 10px; }

    .kpi-grid { grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 18px; }
    .kpi-card { padding: 15px 14px 12px; border-radius: 14px; }
    .kpi-top { margin-bottom: 10px; }
    .kpi-icon { width: 36px; height: 36px; font-size: 14px; border-radius: 10px; }
    .kpi-value { font-size: 1.5rem; }
    .kpi-label { font-size: .65rem; }
    .kpi-trend { font-size: .6rem; padding: 3px 7px; }

    .card-title { font-size: .82rem; }
    .card-sub { font-size: .65rem; }
    .card-action { font-size: .68rem; padding: 5px 10px; }

    /* Dropdown notifications : passe en plein écran ancré sous le header */
    .notif-dropdown {
        position: fixed;
        top: calc(var(--header-h) + 8px);
        left: 12px; right: 12px;
        width: auto;
        max-height: calc(100vh - var(--header-h) - 24px);
    }

    .activity-name, .activity-detail { font-size: .76rem; }
    .activity-dot { width: 30px; height: 30px; font-size: 11px; }
}

/* ── 380px : protège les onglets de notifications (libellés FR plus longs) ── */
@media (max-width: 380px) {
    .notif-tab { font-size: .68rem; gap: 4px; padding: 7px 3px; }
    .notif-tab i { display: none; }
    .notif-header-title { font-size: .85rem; }
    .notif-mark-all { font-size: .68rem; padding: 4px 8px; }
}

/* ── 360px : très petits écrans ── */
@media (max-width: 360px) {
    .kpi-grid { grid-template-columns: 1fr; }
    .kpi-card { padding: 14px 16px; }
    .header-breadcrumb { display: none; }
    .sidebar { width: min(260px, 86vw); }
}

/* Loading skeleton */
@keyframes shimmer {
    0%   { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
.skeleton {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
    border-radius: 8px;
}

/* Transition overlay (page load) */
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
.content > * { animation: fadeIn .4s ease both; }
.content > *:nth-child(1) { animation-delay: .05s; }
.content > *:nth-child(2) { animation-delay: .1s; }
.content > *:nth-child(3) { animation-delay: .15s; }
.content > *:nth-child(4) { animation-delay: .2s; }
</style>
</head>
<body>

<!-- ═══════════════════════════════════════════════
     OVERLAY MOBILE
═══════════════════════════════════════════════ -->
<div id="side-overlay"></div>

<div class="layout">

<!-- ═══════════════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Navigation admin">
    <div class="sidebar-brand">
        <div class="brand-logo">G</div>
        <div>
            <div class="brand-name">Gala Agro</div>
            <div class="brand-sub">Administration</div>
        </div>
    </div>

    <nav class="nav-body">
        <div class="nav-label">Principal</div>

        <a href="dashboard.php" class="nav-item active">
            <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
            <span class="nav-text">Tableau de bord</span>
        </a>

        <a href="admin_commandes.php" class="nav-item" id="nav-cmd">
            <div class="nav-icon"><i class="fas fa-shopping-bag"></i></div>
            <span class="nav-text">Commandes</span>
            <span class="nav-badge" id="sidebar-cmd-badge" style="display:none">0</span>
        </a>

        <a href="products_manager.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-box-open"></i></div>
            <span class="nav-text">Produits</span>
        </a>

        <div class="nav-label">Clients & RH</div>

        <a href="messages.php" class="nav-item" id="nav-messages">
            <div class="nav-icon"><i class="fas fa-envelope"></i></div>
            <span class="nav-text">Messages</span>
            <span class="nav-badge" id="sidebar-msg-badge" style="display:none">0</span>
        </a>

        <a href="voir_candidatures.php" class="nav-item" id="nav-cand">
            <div class="nav-icon"><i class="fas fa-user-tie"></i></div>
            <span class="nav-text">Candidatures</span>
            <span class="nav-badge" id="sidebar-cand-badge" style="display:none">0</span>
        </a>

        <div class="nav-label">Contenu</div>

        <a href="gallery.php" class="nav-item">
            <div class="nav-icon"><i class="fas fa-images"></i></div>
            <span class="nav-text">Galerie</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-logout">
            <div class="nav-icon" style="background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.18);">
                <i class="fas fa-sign-out-alt" style="color:#ef4444;"></i>
            </div>
            <span>Déconnexion</span>
        </a>
    </div>
</aside>

<!-- ═══════════════════════════════════════════════
     MAIN
═══════════════════════════════════════════════ -->
<div class="main">

    <!-- HEADER -->
    <header class="header">
        <div class="header-left">
            <!-- Hamburger mobile -->
            <button id="admin-menu-btn" aria-label="Menu" aria-expanded="false">
                <span class="abar"></span>
                <span class="abar"></span>
                <span class="abar"></span>
            </button>

            <div>
                <div class="header-title">Tableau de bord</div>
                <div class="header-breadcrumb">Bienvenue, Admin — <span id="live-time"></span></div>
            </div>
        </div>

        <div class="header-actions">
            <!-- Search -->
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Rechercher…" id="global-search" autocomplete="off">
            </div>

            <!-- ══════ NOTIFICATION BELL ══════ -->
            <div class="notif-wrap" id="notifWrap">
                <button class="notif-btn <?= $initBadge > 0 ? 'has-new' : '' ?>"
                        id="notifBtn"
                        aria-label="Notifications"
                        aria-haspopup="true"
                        aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge <?= $initBadge > 0 ? 'visible' : 'hidden' ?>"
                          id="notifBadge"><?= $initBadge > 0 ? $initBadge : '' ?></span>
                </button>

                <!-- Dropdown -->
                <div class="notif-dropdown" id="notifDropdown" role="dialog" aria-label="Notifications">
                    <div class="notif-header">
                        <div>
                            <div class="notif-header-title">Notifications</div>
                        </div>
                        <button class="notif-mark-all" id="markAllRead">
                            <i class="fas fa-check-double" style="margin-right:4px;"></i> Tout marquer lu
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="notif-tabs">
                        <button class="notif-tab active" id="tabMsg" onclick="switchTab('msg')">
                            <i class="fas fa-envelope" style="font-size:11px;"></i> Messages
                            <span class="notif-tab-count" id="tabMsgCount">0</span>
                        </button>
                        <button class="notif-tab" id="tabCand" onclick="switchTab('cand')">
                            <i class="fas fa-user-tie" style="font-size:11px;"></i> Recrutements
                            <span class="notif-tab-count" id="tabCandCount">0</span>
                        </button>
                        <button class="notif-tab" id="tabCmd" onclick="switchTab('cmd')">
                            <i class="fas fa-shopping-bag" style="font-size:11px;"></i> Commandes
                            <span class="notif-tab-count" id="tabCmdCount">0</span>
                        </button>
                    </div>

                    <div class="notif-list">
                        <!-- Messages panel -->
                        <div class="notif-panel active" id="panelMsg">
                            <div class="notif-empty" id="emptyMsg">
                                <i class="fas fa-envelope-open-text"></i>
                                <p>Aucun nouveau message</p>
                            </div>
                        </div>
                        <!-- Candidatures panel -->
                        <div class="notif-panel" id="panelCand">
                            <div class="notif-empty" id="emptyCand">
                                <i class="fas fa-user-check"></i>
                                <p>Aucune nouvelle candidature</p>
                            </div>
                        </div>
                        <!-- Commandes panel -->
                        <div class="notif-panel" id="panelCmd">
                            <div class="notif-empty" id="emptyCmd">
                                <i class="fas fa-shopping-bag"></i>
                                <p>Aucune nouvelle commande</p>
                            </div>
                        </div>
                    </div>

                    <div class="notif-footer">
                        <a href="messages.php" class="notif-footer-btn secondary">
                            <i class="fas fa-envelope" style="font-size:11px;"></i> Messages
                        </a>
                        <a href="voir_candidatures.php" class="notif-footer-btn secondary">
                            <i class="fas fa-users" style="font-size:11px;"></i> Candidatures
                        </a>
                        <a href="admin_commandes.php" class="notif-footer-btn primary">
                            <i class="fas fa-shopping-bag" style="font-size:11px;"></i> Commandes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Admin avatar -->
            <div class="admin-avatar" title="Administrateur">A</div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════════
         CONTENT
    ═══════════════════════════════════════════════ -->
    <main class="content">

        <!-- Page Hero -->
        <div class="page-hero">
            <div class="page-hero-bar">
                <div>
                    <h1>Vue d'ensemble</h1>
                    <p>Synthèse en temps réel de l'activité Gala Agro</p>
                </div>
                <div class="date-chip">
                    <i class="fas fa-calendar-day"></i>
                    <span id="full-date"></span>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card green">
                <div class="kpi-top">
                    <div class="kpi-icon green"><i class="fas fa-envelope"></i></div>
                    <div class="kpi-trend up"><i class="fas fa-arrow-up" style="font-size:.6rem;"></i> Live</div>
                </div>
                <div class="kpi-value" data-count="<?= $totalMessages ?>">0</div>
                <div class="kpi-label">Messages reçus</div>
            </div>

            <div class="kpi-card purple">
                <div class="kpi-top">
                    <div class="kpi-icon purple"><i class="fas fa-user-tie"></i></div>
                    <div class="kpi-trend up"><i class="fas fa-arrow-up" style="font-size:.6rem;"></i> Live</div>
                </div>
                <div class="kpi-value" data-count="<?= $totalCandidatures ?>">0</div>
                <div class="kpi-label">Candidatures</div>
            </div>

            <div class="kpi-card blue">
                <div class="kpi-top">
                    <div class="kpi-icon blue"><i class="fas fa-shopping-bag"></i></div>
                    <div class="kpi-trend neu"><i class="fas fa-minus" style="font-size:.6rem;"></i> Total</div>
                </div>
                <div class="kpi-value" data-count="<?= $totalCommandes ?>">0</div>
                <div class="kpi-label">Commandes</div>
            </div>

            <div class="kpi-card amber">
                <div class="kpi-top">
                    <div class="kpi-icon amber"><i class="fas fa-box-open"></i></div>
                    <div class="kpi-trend neu"><i class="fas fa-minus" style="font-size:.6rem;"></i> Total</div>
                </div>
                <div class="kpi-value" data-count="<?= $totalProduits ?>">0</div>
                <div class="kpi-label">Produits actifs</div>
            </div>
        </div>

        <!-- Charts + Activity -->
        <div class="section-grid">

            <!-- Chart messages -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Messages — 6 derniers mois</div>
                        <div class="card-sub">Évolution des demandes clients</div>
                    </div>
                    <a href="messages.php" class="card-action">Voir tout <i class="fas fa-arrow-right" style="font-size:.65rem;"></i></a>
                </div>
                <div class="card-body">
                    <canvas id="msgChart" height="180"></canvas>
                </div>
            </div>

            <!-- Donut candidatures -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Statut des candidatures</div>
                        <div class="card-sub">Répartition par décision RH</div>
                    </div>
                    <a href="voir_candidatures.php" class="card-action">Voir tout <i class="fas fa-arrow-right" style="font-size:.65rem;"></i></a>
                </div>
                <div class="card-body donut-body" style="display:flex;align-items:center;justify-content:center;gap:30px;">
                    <canvas id="candChart" width="160" height="160" style="max-width:160px;max-height:160px;"></canvas>
                    <div id="candLegend" style="font-size:.78rem;line-height:2;"></div>
                </div>
            </div>
        </div>

        <!-- Recent activity -->
        <div class="section-full">
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Activité récente</div>
                        <div class="card-sub">10 dernières interactions clients & RH</div>
                    </div>
                </div>
                <div class="card-body" style="padding:0;">
                    <div style="padding:10px 18px;">
                        <?php if (empty($recentActivity)): ?>
                        <div class="notif-empty"><i class="fas fa-inbox"></i><p>Aucune activité récente</p></div>
                        <?php else: ?>
                        <?php foreach ($recentActivity as $act):
                            $typeClass = match($act['type']) {
                                'message'     => 'msg',
                                'candidature' => 'cand',
                                'commande'    => 'cmd',
                                default       => 'msg'
                            };
                            $typeLabel = match($act['type']) {
                                'message'     => '<i class="fas fa-envelope" style="font-size:.55rem;"></i> Message',
                                'candidature' => '<i class="fas fa-user-tie" style="font-size:.55rem;"></i> Candidature',
                                'commande'    => '<i class="fas fa-shopping-bag" style="font-size:.55rem;"></i> Commande',
                                default       => ''
                            };
                            $initials = mb_strtoupper(mb_substr($act['nom'] ?: '?', 0, 1, 'UTF-8'));
                            $ts = strtotime($act['created_at']);
                            $diff = time() - $ts;
                            if ($diff < 60)       $ago = 'À l\'instant';
                            elseif ($diff < 3600)  $ago = floor($diff/60).'min';
                            elseif ($diff < 86400) $ago = floor($diff/3600).'h';
                            else                   $ago = date('d/m/Y', $ts);
                        ?>
                        <div class="activity-item">
                            <div class="activity-dot <?= $typeClass ?>"><?= $initials ?></div>
                            <div class="activity-info">
                                <span class="type-tag <?= $typeClass ?>"><?= $typeLabel ?></span>
                                <div class="activity-name"><?= htmlspecialchars($act['nom']) ?></div>
                                <div class="activity-detail"><?= htmlspecialchars(mb_strimwidth($act['detail'], 0, 60, '…', 'UTF-8')) ?></div>
                            </div>
                            <div class="activity-time"><?= $ago ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div><!-- /.main -->
</div><!-- /.layout -->

<!-- ═══════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
// ══════════════════════════════════════════════
//  LIVE TIME & DATE
// ══════════════════════════════════════════════
(function() {
    const jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    const mois  = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];

    function update() {
        const now  = new Date();
        const h    = String(now.getHours()).padStart(2,'0');
        const m    = String(now.getMinutes()).padStart(2,'0');
        const s    = String(now.getSeconds()).padStart(2,'0');
        const el   = document.getElementById('live-time');
        const dateEl = document.getElementById('full-date');
        if (el)     el.textContent = `${h}:${m}:${s}`;
        if (dateEl) dateEl.textContent = `${jours[now.getDay()]} ${now.getDate()} ${mois[now.getMonth()]} ${now.getFullYear()}`;
    }
    update();
    setInterval(update, 1000);
})();

// ══════════════════════════════════════════════
//  KPI COUNTER ANIMATION
// ══════════════════════════════════════════════
document.querySelectorAll('.kpi-value[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count, 10);
    let current  = 0;
    const step   = Math.max(1, Math.ceil(target / 60));
    const timer  = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current;
        if (current >= target) clearInterval(timer);
    }, 18);
});

// ══════════════════════════════════════════════
//  CHARTS
// ══════════════════════════════════════════════
// Bar chart — messages par mois
const msgData  = <?= json_encode(array_column($msgsByMonth, 'total')) ?>;
const msgLabels= <?= json_encode(array_column($msgsByMonth, 'mois')) ?>;

if (document.getElementById('msgChart')) {
    new Chart(document.getElementById('msgChart'), {
        type: 'bar',
        data: {
            labels: msgLabels.length ? msgLabels : ['Jan','Fév','Mar','Avr','Mai','Jun'],
            datasets: [{
                label: 'Messages',
                data: msgData.length ? msgData : [0,0,0,0,0,0],
                backgroundColor: 'rgba(22,163,74,.15)',
                borderColor: '#16a34a',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { display: false }, tooltip: {
                backgroundColor: '#0f172a', titleColor: '#fff', bodyColor: '#94a3b8',
                cornerRadius: 10, padding: 10,
                callbacks: { label: ctx => ` ${ctx.raw} message${ctx.raw>1?'s':''}` }
            }},
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' },
                     ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 1 },
                     border: { display: false } },
                x: { grid: { display: false },
                     ticks: { color: '#64748b', font: { size: 11 } },
                     border: { display: false } }
            }
        }
    });
}

// Donut — statuts candidatures
const candRaw = <?= json_encode($candidStatuts) ?>;
const candLabels = candRaw.map(r => r.statut || 'En attente');
const candValues = candRaw.map(r => parseInt(r.nb, 10));
const palette    = { 'Validé': '#16a34a', 'Refusé': '#ef4444', 'En attente': '#f59e0b', 'default': '#8b5cf6' };
const candColors = candLabels.map(l => palette[l] || palette.default);

if (document.getElementById('candChart')) {
    new Chart(document.getElementById('candChart'), {
        type: 'doughnut',
        data: { labels: candLabels, datasets: [{ data: candValues, backgroundColor: candColors,
                borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }] },
        options: {
            responsive: false, cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', titleColor:'#fff', bodyColor:'#94a3b8',
                           cornerRadius: 10, padding: 10 }
            }
        }
    });
    const legend = document.getElementById('candLegend');
    if (legend) {
        legend.innerHTML = candLabels.map((l,i) =>
            `<div style="display:flex;align-items:center;gap:8px;">
                <span style="width:10px;height:10px;border-radius:3px;background:${candColors[i]};display:inline-block;flex-shrink:0;"></span>
                <span style="color:#334155;font-weight:600;">${l}</span>
                <span style="color:#94a3b8;font-weight:500;">— ${candValues[i]}</span>
            </div>`
        ).join('') || '<span style="color:#94a3b8;font-size:.78rem;">Aucune donnée</span>';
    }
}

// ══════════════════════════════════════════════
//  HAMBURGER SIDEBAR (mobile)
// ══════════════════════════════════════════════
(function() {
    const btn     = document.getElementById('admin-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('side-overlay');
    if (!btn) return;
    function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('active'); btn.classList.add('open'); btn.setAttribute('aria-expanded','true'); document.body.style.overflow='hidden'; }
    function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); btn.classList.remove('open'); btn.setAttribute('aria-expanded','false'); document.body.style.overflow=''; }
    btn.addEventListener('click', e => { e.stopPropagation(); sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
    overlay.addEventListener('click', closeSidebar);
    sidebar.querySelectorAll('a').forEach(l => l.addEventListener('click', () => { if(window.innerWidth < 1024) closeSidebar(); }));
})();

// ══════════════════════════════════════════════
//  NOTIFICATION SYSTEM
// ══════════════════════════════════════════════
const notifBtn      = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
const notifBadge    = document.getElementById('notifBadge');
const markAllBtn    = document.getElementById('markAllRead');

let currentTab = 'msg';
let notifData  = { messages: [], candidatures: [], commandes: [] };
let notifFetchFailed = false;

// Toggle dropdown
notifBtn.addEventListener('click', e => {
    e.stopPropagation();
    const isOpen = notifDropdown.classList.contains('open');
    if (isOpen) {
        closeNotif();
    } else {
        openNotif();
        fetchNotifications(); // refresh on open
    }
});

document.addEventListener('click', e => {
    if (!notifDropdown.contains(e.target) && e.target !== notifBtn) closeNotif();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNotif(); });

function openNotif() {
    notifDropdown.classList.add('open');
    notifBtn.setAttribute('aria-expanded', 'true');
}
function closeNotif() {
    notifDropdown.classList.remove('open');
    notifBtn.setAttribute('aria-expanded', 'false');
}

// Tab switching
function switchTab(tab) {
    currentTab = tab;
    document.getElementById('tabMsg').classList.toggle('active', tab === 'msg');
    document.getElementById('tabCand').classList.toggle('active', tab === 'cand');
    document.getElementById('tabCmd').classList.toggle('active', tab === 'cmd');
    document.getElementById('panelMsg').classList.toggle('active', tab === 'msg');
    document.getElementById('panelCand').classList.toggle('active', tab === 'cand');
    document.getElementById('panelCmd').classList.toggle('active', tab === 'cmd');
}
window.switchTab = switchTab;

// Mark all read
markAllBtn.addEventListener('click', async () => {
    try {
        const res  = await fetch('?api=mark_read&type=all&_=' + Date.now());
        const data = await res.json();
        if (!data || data.success !== true) {
            console.error('mark_read a échoué :', data);
        }
    } catch (err) {
        console.error('Erreur réseau lors du mark_read :', err);
    }
    notifData = { messages: [], candidatures: [], commandes: [] };
    renderNotifications();
    updateBadge(0);
    notifBtn.classList.remove('has-new');

    const sbMsg  = document.getElementById('sidebar-msg-badge');
    const sbCand = document.getElementById('sidebar-cand-badge');
    const sbCmd  = document.getElementById('sidebar-cmd-badge');
    [sbMsg, sbCand, sbCmd].forEach(el => { if (el) el.style.display = 'none'; });

    markAllBtn.innerHTML = '<i class="fas fa-check" style="margin-right:4px;"></i> Lu';
    setTimeout(() => { markAllBtn.innerHTML = '<i class="fas fa-check-double" style="margin-right:4px;"></i> Tout marquer lu'; }, 2000);
});

// Fetch from API
async function fetchNotifications() {
    try {
        const res = await fetch('?api=notifications&_=' + Date.now());

        if (!res.ok) {
            throw new Error('Réponse HTTP ' + res.status + ' depuis l\'API notifications');
        }

        const raw = await res.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (parseErr) {
            // La réponse n'est pas du JSON valide : probablement une erreur PHP
            // affichée avant le JSON. On le signale clairement en console pour
            // pouvoir corriger côté serveur, plutôt que d'échouer en silence.
            console.error('Réponse API notifications invalide (pas du JSON) :', raw.slice(0, 300));
            notifFetchFailed = true;
            return;
        }

        if (data.success === false) {
            console.error('Erreur API notifications :', data.error || data);
            notifFetchFailed = true;
            return;
        }

        notifFetchFailed = false;
        notifData = {
            messages:     data.messages     || [],
            candidatures: data.candidatures || [],
            commandes:    data.commandes    || []
        };
        renderNotifications();
        updateBadge(data.total || 0);

        // Update sidebar badges
        const sbMsg  = document.getElementById('sidebar-msg-badge');
        const sbCand = document.getElementById('sidebar-cand-badge');
        const sbCmd  = document.getElementById('sidebar-cmd-badge');
        const mc = notifData.messages.length;
        const cc = notifData.candidatures.length;
        const oc = notifData.commandes.length;
        if (sbMsg)  { sbMsg.textContent  = mc; sbMsg.style.display  = mc ? 'inline-flex' : 'none'; }
        if (sbCand) { sbCand.textContent = cc; sbCand.style.display = cc ? 'inline-flex' : 'none'; }
        if (sbCmd)  { sbCmd.textContent  = oc; sbCmd.style.display  = oc ? 'inline-flex' : 'none'; }

        // Bell animation on new notifications
        if (data.total > 0) {
            notifBtn.classList.add('has-new', 'shake');
            setTimeout(() => notifBtn.classList.remove('shake'), 700);
        } else {
            notifBtn.classList.remove('has-new');
        }
    } catch (err) {
        notifFetchFailed = true;
        console.error('Notif fetch error:', err);
    }
}

function updateBadge(n) {
    if (n > 0) {
        notifBadge.textContent = n > 99 ? '99+' : n;
        notifBadge.classList.remove('hidden');
        notifBadge.classList.add('visible');
    } else {
        notifBadge.classList.remove('visible');
        notifBadge.classList.add('hidden');
    }
}

function timeAgo(dateStr) {
    const d    = new Date((dateStr || '').replace(' ', 'T'));
    const diff = Math.floor((Date.now() - d) / 1000);
    if (diff < 60)    return 'À l\'instant';
    if (diff < 3600)  return `${Math.floor(diff/60)} min`;
    if (diff < 86400) return `${Math.floor(diff/3600)} h`;
    return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'short' });
}

function initials(name) {
    return (name || '?').trim().split(' ').slice(0,2).map(p => p[0]).join('').toUpperCase();
}

function renderNotifications() {
    const msgs  = notifData.messages     || [];
    const cands = notifData.candidatures || [];
    const cmds  = notifData.commandes    || [];

    document.getElementById('tabMsgCount').textContent  = msgs.length;
    document.getElementById('tabCandCount').textContent = cands.length;
    document.getElementById('tabCmdCount').textContent  = cmds.length;

    // Messages panel
    const panelMsg = document.getElementById('panelMsg');
    if (msgs.length === 0) {
        panelMsg.innerHTML = `<div class="notif-empty"><i class="fas fa-envelope-open-text"></i><p>Aucun nouveau message</p></div>`;
    } else {
        panelMsg.innerHTML = msgs.map(m => `
            <div class="notif-item new" onclick="window.location='messages.php'">
                <div class="notif-avatar">${initials(m.nom_complet)}</div>
                <div class="notif-item-body">
                    <div class="notif-item-name">${escHtml(m.nom_complet)}</div>
                    <div class="notif-item-detail">${escHtml((m.message||'').substring(0,55))}…</div>
                    <div class="notif-item-time"><i class="fas fa-clock" style="font-size:.6rem;margin-right:3px;"></i>${timeAgo(m.date_envoi)}</div>
                </div>
            </div>
        `).join('');
    }

    // Candidatures panel
    const panelCand = document.getElementById('panelCand');
    if (cands.length === 0) {
        panelCand.innerHTML = `<div class="notif-empty"><i class="fas fa-user-check"></i><p>Aucune nouvelle candidature</p></div>`;
    } else {
        panelCand.innerHTML = cands.map(c => `
            <div class="notif-item new" onclick="window.location='voir_candidatures.php'">
                <div class="notif-avatar cand">${initials(c.nom_complet)}</div>
                <div class="notif-item-body">
                    <div class="notif-item-name">${escHtml(c.nom_complet)}</div>
                    <div class="notif-item-detail"><i class="fas fa-briefcase" style="font-size:.65rem;margin-right:3px;color:#8b5cf6;"></i>${escHtml(c.poste||'Poste non précisé')}</div>
                    <div class="notif-item-time"><i class="fas fa-clock" style="font-size:.6rem;margin-right:3px;"></i>${timeAgo(c.created_at)}</div>
                </div>
            </div>
        `).join('');
    }

    // Commandes panel
    const panelCmd = document.getElementById('panelCmd');
    if (cmds.length === 0) {
        panelCmd.innerHTML = `<div class="notif-empty"><i class="fas fa-shopping-bag"></i><p>Aucune nouvelle commande</p></div>`;
    } else {
        panelCmd.innerHTML = cmds.map(c => {
            const nomClient = ((c.nom || '') + ' ' + (c.prenom || '')).trim() || 'Client';
            const marche    = [c.nom_marche, c.region].filter(Boolean).join(' · ');
            return `
            <div class="notif-item new" onclick="window.location='admin_commandes.php'">
                <div class="notif-avatar cmd">${initials(nomClient)}</div>
                <div class="notif-item-body">
                    <div class="notif-item-name">${escHtml(nomClient)}</div>
                    <div class="notif-item-detail"><i class="fas fa-store" style="font-size:.65rem;margin-right:3px;color:#f59e0b;"></i>${escHtml(marche || 'Nouvelle commande')}</div>
                    <div class="notif-item-time"><i class="fas fa-clock" style="font-size:.6rem;margin-right:3px;"></i>${timeAgo(c.date_commande)}</div>
                </div>
            </div>`;
        }).join('');
    }
}

function escHtml(str) {
    return (str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Initial fetch + polling every 20s
fetchNotifications();
setInterval(fetchNotifications, 20000);

// ══════════════════════════════════════════════
//  GLOBAL SEARCH (quick redirect)
// ══════════════════════════════════════════════
document.getElementById('global-search').addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        const q = e.target.value.trim();
        if (q) window.location.href = 'messages.php?q=' + encodeURIComponent(q);
    }
});
</script>
</body>
</html>