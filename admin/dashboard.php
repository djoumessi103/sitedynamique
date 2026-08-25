<?php
session_start();

$allowed_roles = ['admin', 'rh', 'commercial']; // le tableau de bord reste accessible à tous les rôles connectés
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$current_role  = $_SESSION['role']     ?? 'commercial';
$current_user  = $_SESSION['username'] ?? 'Admin';
$roleLabels    = ['admin' => 'Administrateur', 'rh' => 'Ressources Humaines', 'commercial' => 'Commercial'];
$currentRoleLabel = $roleLabels[$current_role] ?? 'Utilisateur';

// Visibilité des catégories de notification selon le rôle
$canSeeMsg   = in_array($current_role, ['admin', 'commercial'], true);
$canSeeCand  = in_array($current_role, ['admin', 'rh'], true);
$canSeeCmd     = in_array($current_role, ['admin', 'commercial'], true); // commandes : lien actif, accès autorisé
$canSeeStock   = in_array($current_role, ['admin', 'commercial'], true); // stock visible par admin + commercial
$stockLinkable = ($current_role === 'admin'); // lien products_manager réservé admin
$firstTab    = $canSeeMsg ? 'msg' : ($canSeeCmd ? 'cmd' : ($canSeeCand ? 'cand' : 'stock'));

// ── État "dernier vu" PERSISTANT (en base, lié au compte) ──
// Survit aux reconnexions, contrairement à $_SESSION.
function getLastSeen(PDO $pdo, string $username): array {
    $epoch = '1970-01-01 00:00:00';
    $defaults = [
        'last_seen_messages_at'     => $epoch,
        'last_seen_candidatures_at' => $epoch,
        'last_seen_commandes_at'    => $epoch,
    ];
    try {
        $stmt = $pdo->prepare(
            "SELECT
                COALESCE(last_seen_messages_at,     '1970-01-01 00:00:00') AS last_seen_messages_at,
                COALESCE(last_seen_candidatures_at, '1970-01-01 00:00:00') AS last_seen_candidatures_at,
                COALESCE(last_seen_commandes_at,    '1970-01-01 00:00:00') AS last_seen_commandes_at
             FROM users WHERE username = ?"
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return $defaults;
        // Sécurité : remplacer tout NULL résiduel
        foreach ($defaults as $key => $fallback) {
            if (empty($row[$key])) $row[$key] = $fallback;
        }
        return $row;
    } catch (Exception $e) {
        // Colonnes absentes → tout est "nouveau" → afficher tout
        return $defaults;
    }
}

// ═══════════════════════════════════════════════════════
//  API JSON — NOTIFICATIONS EN TEMPS RÉEL
// ═══════════════════════════════════════════════════════
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    if ($_GET['api'] === 'notifications') {
        $seen = getLastSeen($pdo, $_SESSION['username'] ?? '');
        $lastSeenMsg  = $seen['last_seen_messages_at'];
        $lastSeenCand = $seen['last_seen_candidatures_at'];
        $lastSeenCmd  = $seen['last_seen_commandes_at'];

        $msgs = $cands = $stockRows = $cmds = [];

        // Messages : visibles par admin + commercial
        if (in_array($current_role, ['admin', 'commercial'], true)) {
            $newMessages = $pdo->prepare(
                "SELECT id, nom_complet, message, telephone, date_envoi
                 FROM contacts WHERE date_envoi >= ? ORDER BY date_envoi DESC LIMIT 8"
            );
            $newMessages->execute([$lastSeenMsg]);
            $msgs = $newMessages->fetchAll(PDO::FETCH_ASSOC);
        }

        // Commandes : visibles par admin + commercial, avec lien (accès autorisé pour les deux)
        if (in_array($current_role, ['admin', 'commercial'], true)) {
            $newCommandes = $pdo->prepare(
                "SELECT id, nom, prenom, region, nom_marche, date_commande
                 FROM commandes WHERE date_commande >= ? ORDER BY date_commande DESC LIMIT 8"
            );
            $newCommandes->execute([$lastSeenCmd]);
            $cmds = $newCommandes->fetchAll(PDO::FETCH_ASSOC);
        }

        // Stock : visible par admin + commercial uniquement (pas RH)
        if (in_array($current_role, ['admin', 'commercial'], true)) {
            $stockRows = $pdo->query(
                "SELECT id, nom, format, stock FROM products WHERE stock = 0 ORDER BY nom ASC LIMIT 10"
            )->fetchAll(PDO::FETCH_ASSOC);
        }

        // Candidatures : visibles par admin + rh
        if (in_array($current_role, ['admin', 'rh'], true)) {
            $newCandidatures = $pdo->prepare(
                "SELECT id, nom_complet, poste, email, telephone, created_at
                 FROM candidatures WHERE created_at >= ? ORDER BY created_at DESC LIMIT 8"
            );
            $newCandidatures->execute([$lastSeenCand]);
            $cands = $newCandidatures->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'messages'     => $msgs,
            'candidatures' => $cands,
            'stock'        => $stockRows,
            'commandes'    => $cmds,
            'total'        => count($msgs) + count($cands) + count($stockRows) + count($cmds)
        ]);
        exit;
    }

    if ($_GET['api'] === 'mark_read') {
        $now = date('Y-m-d H:i:s', time() - 2); // -2s pour ne pas rater les entrées simultanées
        try {
            $upd = $pdo->prepare(
                "UPDATE users SET last_seen_messages_at = ?, last_seen_candidatures_at = ?, last_seen_commandes_at = ?
                 WHERE username = ?"
            );
            $upd->execute([$now, $now, $now, $_SESSION['username'] ?? '']);
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            // Fallback session si colonnes absentes
            $_SESSION['last_seen_messages_at']      = $now;
            $_SESSION['last_seen_candidatures_at']  = $now;
            $_SESSION['last_seen_commandes_at']     = $now;
            echo json_encode(['ok' => false, 'msg' => 'Colonnes absentes. Exécutez notifications_migration.sql']);
        }
        exit;
    }

    // ── API GESTION UTILISATEURS (admin only) ──
    if ($_GET['api'] === 'get_users' && $current_role === 'admin') {
        try {
            $users = $pdo->query("SELECT id, username, role FROM users ORDER BY role, username")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'users' => $users]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'users' => [], 'msg' => 'Erreur SQL : ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_GET['api'] === 'change_password' && $current_role === 'admin') {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $uid  = (int)($data['id']       ?? 0);
        $pwd  = trim($data['password']  ?? '');
        if (!$uid || strlen($pwd) < 6) {
            echo json_encode(['ok'=>false,'msg'=>'Données invalides.']); exit;
        }
        try {
            $hash = password_hash($pwd, PASSWORD_DEFAULT);
            $s = $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role!='admin'");
            $s->execute([$hash, $uid]);
            echo json_encode($s->rowCount()
                ? ['ok'=>true, 'msg'=>'Mot de passe modifié avec succès.']
                : ['ok'=>false,'msg'=>'Utilisateur introuvable ou non autorisé.']
            );
        } catch(Exception $e) {
            echo json_encode(['ok'=>false,'msg'=>'Erreur base de données.']);
        }
        exit;
    }

    if ($_GET['api'] === 'create_user' && $current_role === 'admin') {
        $data = json_decode(file_get_contents('php://input'), true);
        $uname = trim($data['username'] ?? '');
        $pass  = trim($data['password'] ?? '');
        $role  = $data['role'] ?? '';

        if (!$uname || !$pass || !in_array($role, ['commercial','rh'], true)) {
            echo json_encode(['ok' => false, 'msg' => 'Données invalides.']); exit;
        }
        if (strlen($pass) < 6) {
            echo json_encode(['ok' => false, 'msg' => 'Mot de passe trop court (min. 6 caractères).']); exit;
        }
        try {
            // Vérifier unicité
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $chk->execute([$uname]);
            if ($chk->fetch()) {
                echo json_encode(['ok' => false, 'msg' => 'Ce nom d\'utilisateur existe déjà.']); exit;
            }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $ins->execute([$uname, $hash, $role]);
            echo json_encode(['ok' => true, 'msg' => 'Compte créé avec succès.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Erreur SQL : ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_GET['api'] === 'delete_user' && $current_role === 'admin') {
        $data = json_decode(file_get_contents('php://input'), true);
        $uid  = (int)($data['id'] ?? 0);
        if (!$uid) { echo json_encode(['ok' => false, 'msg' => 'ID invalide.']); exit; }
        try {
            // Protéger son propre compte
            $self = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $self->execute([$uid]);
            $row  = $self->fetch();
            if ($row && $row['username'] === ($_SESSION['username'] ?? '')) {
                echo json_encode(['ok' => false, 'msg' => 'Vous ne pouvez pas supprimer votre propre compte.']); exit;
            }
            $del = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $del->execute([$uid]);
            echo json_encode(['ok' => $del->rowCount() > 0, 'msg' => $del->rowCount() > 0 ? 'Compte supprimé.' : 'Suppression non autorisée.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Erreur SQL : ' . $e->getMessage()]);
        }
        exit;
    }

    // ── API JOURNAL D'ACTIVITÉ (admin only) ──
    if ($_GET['api'] === 'get_activity_log' && $current_role === 'admin') {
        $filter = $_GET['filter'] ?? 'all'; // all | commandes | candidatures
        try {
            if ($filter === 'commandes' || $filter === 'candidatures') {
                $stmt = $pdo->prepare("SELECT * FROM activity_log WHERE table_concernee = ? ORDER BY created_at DESC LIMIT 100");
                $stmt->execute([$filter]);
            } else {
                $stmt = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 100");
            }
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'logs' => $logs]);
        } catch (Exception $e) {
            // La table n'existe probablement pas encore
            echo json_encode(['ok' => false, 'logs' => [], 'error' => 'table_missing']);
        }
        exit;
    }

    exit;
}

// ═══════════════════════════════════════════════════════
//  STATISTIQUES GÉNÉRALES
// ═══════════════════════════════════════════════════════
$totalMessages     = $pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
$totalCandidatures = $pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn();
$totalCommandes    = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$totalProduits     = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Produits en rupture de stock (stock = 0) — statut LIVE
$ruptureProducts = $pdo->query(
    "SELECT id, nom, format FROM products WHERE stock = 0 ORDER BY nom ASC"
)->fetchAll(PDO::FETCH_ASSOC);
$ruptureCount = count($ruptureProducts);

// Notifications initiales (pour badge au chargement) — filtrées par rôle
$seenInit     = getLastSeen($pdo, $current_user);
$lastSeenMsg  = $seenInit['last_seen_messages_at'];
$lastSeenCand = $seenInit['last_seen_candidatures_at'];
$lastSeenCmd  = $seenInit['last_seen_commandes_at'];
$initBadge = 0;

if (in_array($current_role, ['admin', 'commercial'], true)) {
    $initNewMsgs = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE date_envoi >= ?");
    $initNewMsgs->execute([$lastSeenMsg]);
    $initBadge += (int)$initNewMsgs->fetchColumn();

    $initNewCmds = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE date_commande >= ?");
    $initNewCmds->execute([$lastSeenCmd]);
    $initBadge += (int)$initNewCmds->fetchColumn();

    $initBadge += $ruptureCount;
}
if (in_array($current_role, ['admin', 'rh'], true)) {
    $initNewCands = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE created_at >= ?");
    $initNewCands->execute([$lastSeenCand]);
    $initBadge += (int)$initNewCands->fetchColumn();
}

// Activités récentes unifiées (10 dernières)
$recentActivity = $pdo->query("
    (SELECT 'message' AS type, id, nom_complet AS nom,
            message AS detail, date_envoi AS created_at FROM contacts ORDER BY date_envoi DESC LIMIT 5)
    UNION ALL
    (SELECT 'candidature' AS type, id, nom_complet AS nom,
            poste AS detail, created_at FROM candidatures ORDER BY created_at DESC LIMIT 5)
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
   HEADER
═══════════════════════════════════════════════ */
.header {
    height: var(--header-h);
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 28px;
    position: sticky; top: 0; z-index: 800;
}
.header-left { display: flex; align-items: center; gap: 16px; }
.header-title { font-size: 1.1rem; font-weight: 700; color: var(--dark); letter-spacing: -.02em; }
.header-breadcrumb { font-size: .72rem; color: var(--slate); margin-top: 1px; }

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

.header-actions { display: flex; align-items: center; gap: 10px; }

/* ═══════════════════════════════════════════════
   NOTIFICATION BELL — SYSTÈME COMPLET 2026
═══════════════════════════════════════════════ */
.notif-wrap { position: relative; }

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
    width: 380px; max-height: 520px;
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
.notif-tabs { display: flex; gap: 4px; padding: 10px 12px 0; }
.notif-tab {
    flex: 1; padding: 8px 3px; border-radius: 10px;
    font-size: .68rem; font-weight: 600;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 4px;
    white-space: nowrap; overflow: hidden;
    transition: background .18s, color .18s;
    background: var(--bg); color: var(--slate);
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
.notif-avatar.stock { background: linear-gradient(135deg, var(--amber), var(--red)); }
.notif-avatar.cmd { background: linear-gradient(135deg, var(--green), var(--green-mid)); }
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
    flex: 1; padding: 9px; border-radius: 10px; font-size: .75rem;
    font-weight: 600; border: none; cursor: pointer;
    text-align: center; text-decoration: none;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: background .18s, transform .12s;
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
    flex-shrink: 0; position: relative;
    padding: 0; outline: none; font-family: inherit;
}
.admin-avatar:hover { border-color: var(--green); transform: scale(1.04); }
.admin-avatar::after {
    content: ''; position: absolute; bottom: -2px; right: -2px;
    width: 10px; height: 10px; border-radius: 50%;
    background: #22c55e; border: 2px solid #fff;
}

/* ══ MENU COMPTE (account dropdown) ══ */
.account-wrap { position: relative; flex-shrink: 0; }
.account-dropdown {
    position: absolute; top: calc(100% + 10px); right: -6px;
    width: 270px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    z-index: 9999;
    transform-origin: top right;
    transform: scale(.92) translateY(-8px);
    opacity: 0;
    pointer-events: none;
    transition: transform .22s cubic-bezier(.16,1,.3,1), opacity .18s ease;
}
.account-dropdown.open { transform: scale(1) translateY(0); opacity: 1; pointer-events: auto; }

.account-head {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 16px 14px;
    background: linear-gradient(135deg, #f8fafc, #f0fdf4);
    border-bottom: 1px solid var(--border);
}
.account-head-avatar {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: .9rem;
    position: relative;
}
.account-head-avatar::after {
    content: ''; position: absolute; bottom: -2px; right: -2px;
    width: 11px; height: 11px; border-radius: 50%;
    background: #22c55e; border: 2px solid #fff;
}
.account-head-info { flex: 1; min-width: 0; }
.account-head-name { font-size: .87rem; font-weight: 800; color: var(--dark);
                      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.account-head-status { font-size: .68rem; color: #16a34a; font-weight: 600; margin-top: 2px;
                        display: flex; align-items: center; gap: 5px; }
.account-head-status .dot { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; }

.account-menu { padding: 8px; }
.account-menu-item {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 11px; border-radius: 11px;
    color: #334155; font-weight: 600; font-size: .82rem;
    text-decoration: none; cursor: pointer; border: none; background: none;
    width: 100%; text-align: left; font-family: inherit;
    transition: background .16s, color .16s;
}
.account-menu-item:hover { background: var(--bg); color: var(--dark); }
.account-menu-item i:first-child { width: 17px; text-align: center; color: #64748b; font-size: 13px; }
.account-menu-item:hover i:first-child { color: var(--green); }
.account-menu-item.danger { color: #dc2626; }
.account-menu-item.danger i:first-child { color: #ef4444; }
.account-menu-item.danger:hover { background: #fef2f2; }
.account-menu-divider { height: 1px; background: var(--border); margin: 6px 4px; }

/* ══ JOURNAL D'ACTIVITÉ ══ */
.log-filters { display: flex; gap: 6px; }
.log-filter-btn {
    flex: 1; padding: 8px 6px; border-radius: 10px;
    font-size: .74rem; font-weight: 700; text-align: center;
    border: 1.5px solid var(--border); background: #fff; color: var(--slate);
    cursor: pointer; transition: background .16s, color .16s, border-color .16s;
}
.log-filter-btn.active { background: var(--dark); color: #fff; border-color: var(--dark); }

.log-list { display: flex; flex-direction: column; gap: 8px; max-height: 360px; overflow-y: auto; }
.log-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 14px; border-radius: 13px;
    background: #fff; border: 1px solid var(--border);
}
.log-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 13px;
}
.log-icon.commandes     { background: linear-gradient(135deg,#3b82f6,#60a5fa); }
.log-icon.candidatures  { background: linear-gradient(135deg,#8b5cf6,#a78bfa); }
.log-body { flex: 1; min-width: 0; }
.log-top-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.log-user { font-size: .82rem; font-weight: 700; color: var(--dark); }
.log-time { font-size: .68rem; color: #94a3b8; font-weight: 500; flex-shrink: 0; }
.log-details { font-size: .78rem; color: #475569; margin-top: 3px; line-height: 1.4; }
.log-list-empty { text-align: center; padding: 30px 20px; color: #94a3b8; font-size: .82rem; font-weight: 500; }
.log-list-empty i { font-size: 26px; opacity: .35; display: block; margin-bottom: 10px; }
.log-warn-banner {
    background: #fffbeb; border: 1px solid #fde68a; color: #92400e;
    border-radius: 12px; padding: 12px 14px; font-size: .78rem; font-weight: 600;
    display: flex; align-items: flex-start; gap: 10px; line-height: 1.5;
}
.log-warn-banner i { margin-top: 1px; }
.log-warn-banner code { background: rgba(0,0,0,.06); padding: 1px 5px; border-radius: 5px; font-size: .76rem; }

/* ═══════════════════════════════════════════════
   CONTENT AREA
═══════════════════════════════════════════════ */
.content { flex: 1; padding: 28px; max-width: 1400px; width: 100%; }

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

/* ═══════════════════════════════════════════════
   ALERTE RUPTURE DE STOCK — bannière dashboard
═══════════════════════════════════════════════ */
.stock-alert {
    display: flex; align-items: flex-start; gap: 16px;
    background: linear-gradient(135deg, #fff7ed, #fef2f2);
    border: 1px solid #fed7aa;
    border-radius: var(--radius);
    padding: 18px 20px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
}
.stock-alert-icon {
    width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--amber), var(--red));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px;
    box-shadow: 0 4px 14px rgba(239,68,68,.25);
}
.stock-alert-body { flex: 1; min-width: 0; }
.stock-alert-title { font-size: .92rem; font-weight: 800; color: #9a3412; margin-bottom: 8px; }
.stock-alert-list { display: flex; flex-wrap: wrap; gap: 6px; }
.stock-alert-chip {
    font-size: .72rem; font-weight: 600; color: #9a3412;
    background: rgba(255,255,255,.7); border: 1px solid #fed7aa;
    border-radius: 8px; padding: 4px 10px;
}
.stock-alert-chip.more { background: #9a3412; color: #fff; border-color: #9a3412; }
.stock-alert-action {
    flex-shrink: 0; align-self: center;
    display: flex; align-items: center; gap: 7px;
    background: var(--dark); color: #fff;
    font-size: .78rem; font-weight: 700;
    padding: 10px 16px; border-radius: 11px;
    text-decoration: none; white-space: nowrap;
    transition: background .18s, transform .15s;
}
.stock-alert-action:hover { background: #1e293b; transform: translateY(-1px); }

@media (max-width: 640px) {
    .stock-alert { flex-direction: column; gap: 12px; padding: 16px; }
    .stock-alert-action { align-self: stretch; justify-content: center; }
}

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

/* Full activity table */
.section-full { margin-bottom: 24px; }

/* ═══════════════════════════════════════════════
   HAMBURGER (mobile)
═══════════════════════════════════════════════ */

/* ══════════════════════════════════════════════════
   RESPONSIVE — STRATÉGIE 3 PALIERS
   Desktop ≥1025 : sidebar 260px, hamburger caché
   Tablette 769–1024 : sidebar 72px icônes, hamburger caché
   Mobile ≤768 : drawer off-screen, hamburger visible
══════════════════════════════════════════════════ */

/* ── Tablette 769–1024px ── */
@media (max-width: 1024px) and (min-width: 769px) {
    :root { --sidebar-w: 72px; }

    .sidebar { width: 72px; overflow: visible; }
    .sidebar-brand { padding: 18px 10px; justify-content: center; }
    .brand-name, .brand-sub { display: none; }
    .nav-label { display: none; }
    .nav-item { justify-content: center; padding: 11px; margin-bottom: 3px; }
    .nav-item .nav-text,
    .nav-item .nav-badge { display: none; }
    .nav-icon { width: 38px; height: 38px; font-size: 15px; margin: 0 auto; }
    .nav-item:hover::after {
        content: attr(data-label);
        position: absolute; left: calc(100% + 14px); top: 50%;
        transform: translateY(-50%);
        white-space: nowrap;
        background: #0f172a; color: #fff;
        font-size: .76rem; font-weight: 600;
        padding: 7px 13px; border-radius: 9px;
        box-shadow: 0 4px 16px rgba(0,0,0,.22);
        pointer-events: none; z-index: 9999;
    }
    .nav-item:hover::before { display: none; }
    .nav-item.active::after { display: none; }
    .nav-logout { justify-content: center; padding: 11px; }
    .nav-logout span { display: none; }
    .nav-logout .nav-icon { margin: 0 auto; }

    #admin-menu-btn { display: none !important; }

    .search-wrap { width: 180px; }
    .section-grid { grid-template-columns: 1fr; }
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .header-title { font-size: .95rem; }
    .header-breadcrumb { font-size: .65rem; }
}

/* ── Mobile ≤768px — DRAWER ── */
@media (max-width: 768px) {
    :root { --sidebar-w: 0px; }

    /* La sidebar sort de l'écran vers la gauche */
    .sidebar {
        width: 270px;
        transform: translateX(-100%);
        overflow: hidden;
        /* Force le mode "texte complet" — écrase tout ce que tablette avait changé */
    }
    /* Quand ouverte, elle revient à 0 */
    .sidebar.open {
        transform: translateX(0);
        box-shadow: 10px 0 40px rgba(0,0,0,.22);
    }

    /* === Forcer le mode "texte complet" dans le drawer === */
    .sidebar .sidebar-brand {
        padding: 22px 20px 18px !important;
        justify-content: flex-start !important;
    }
    .sidebar .brand-name { display: block !important; }
    .sidebar .brand-sub  { display: block !important; }
    .sidebar .nav-label  { display: block !important; padding: 14px 12px 5px !important; }
    .sidebar .nav-item {
        justify-content: flex-start !important;
        padding: 10px 13px !important;
        margin-bottom: 2px !important;
    }
    .sidebar .nav-item .nav-text  { display: inline !important; }
    .sidebar .nav-item .nav-badge { display: inline-flex !important; }
    .sidebar .nav-icon { width: 34px !important; height: 34px !important; margin: 0 !important; font-size: 13px !important; }
    .sidebar .nav-logout { justify-content: flex-start !important; padding: 10px 13px !important; }
    .sidebar .nav-logout span { display: inline !important; }
    /* Pas de tooltip dans le drawer */
    .sidebar .nav-item:hover::after { content: none !important; display: none !important; }

    /* Main prend toute la largeur */
    .main { margin-left: 0 !important; }

    /* Hamburger visible */
    #admin-menu-btn { display: flex !important; }

    /* Header compact */
    .header { padding: 0 14px; height: 60px; }
    .header-title { font-size: .9rem; }
    .header-breadcrumb { font-size: .6rem; }
    .search-wrap { display: none !important; }
    .header-actions { gap: 8px; }

    /* Content */
    .content { padding: 14px; }

    /* KPI 2 colonnes */
    .kpi-grid { grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
    .kpi-card { padding: 15px 13px 12px; }
    .kpi-value { font-size: 1.7rem; }
    .kpi-label { font-size: .66rem; }
    .kpi-icon { width: 36px; height: 36px; font-size: 14px; }
    .kpi-trend { font-size: .6rem; padding: 3px 6px; }
    .kpi-top { margin-bottom: 10px; }

    /* Hero */
    .page-hero { margin-bottom: 14px; }
    .page-hero h1 { font-size: 1.15rem; }
    .page-hero p { font-size: .75rem; }
    .page-hero-bar { flex-direction: column; align-items: flex-start; gap: 8px; }
    .date-chip { font-size: .69rem; padding: 5px 10px; }

    /* Section grid colonnes */
    .section-grid { grid-template-columns: 1fr; gap: 12px; margin-bottom: 14px; }
    .section-full { margin-bottom: 14px; }

    /* Cards */
    .card-head { padding: 13px 15px 10px; flex-wrap: wrap; gap: 8px; }
    .card-body { padding: 12px 14px; }
    .card-title { font-size: .82rem; }
    .card-sub   { font-size: .67rem; }

    /* Alerte stock */
    .stock-alert {
        flex-direction: column; gap: 10px;
        padding: 14px; margin-bottom: 16px;
    }
    .stock-alert-action { align-self: stretch; justify-content: center; }

    /* Activité */
    .activity-item { padding: 9px 0; }
    .activity-dot  { width: 30px; height: 30px; font-size: 11px; }
    .activity-name   { font-size: .79rem; }
    .activity-detail { font-size: .69rem; }
    .activity-time   { font-size: .61rem; }
    .type-tag { font-size: .57rem; }

    /* Notification dropdown : fixé en haut plein écran */
    .notif-dropdown {
        position: fixed !important;
        top: 62px; left: 8px; right: 8px;
        width: auto !important;
        max-height: 74vh;
        border-radius: 16px;
    }
}

/* ── Très petits écrans ≤420px ── */
@media (max-width: 420px) {
    .kpi-card { padding: 12px 10px; }
    .kpi-value { font-size: 1.5rem; }
    .kpi-icon { width: 32px; height: 32px; font-size: 12px; }
    .header-actions { gap: 5px; }
    .content { padding: 10px; }
    .notif-btn { width: 36px; height: 36px; }
    .admin-avatar { width: 34px; height: 34px; font-size: .76rem; }
    .btn-users { width: 36px; height: 36px; }
}

/* ── Onglets de la cloche : jusqu'à 4 catégories possibles (Messages/Commandes/Recrutements/Stock) ── */
@media (max-width: 400px) {
    .notif-tab { font-size: .6rem; gap: 2px; padding: 7px 2px; }
    .notif-tab i { display: none; }
    .notif-header-title { font-size: .85rem; }
    .notif-mark-all { font-size: .65rem; padding: 4px 8px; }
}

/* ── Très petits ≤360px : KPI 1 colonne ── */
@media (max-width: 360px) {
    .kpi-grid { grid-template-columns: 1fr; }
    .kpi-value { font-size: 1.9rem; }
}



/* ══════════════════════════════════════
   MODAL CHANGEMENT MOT DE PASSE
══════════════════════════════════════ */
.pwd-modal-bg {
    position: fixed; inset: 0; z-index: 10000;
    background: rgba(15,23,42,.65);
    backdrop-filter: blur(6px);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
    opacity: 0; pointer-events: none;
    transition: opacity .28s ease;
}
.pwd-modal-bg.open { opacity: 1; pointer-events: auto; }
.pwd-modal-box {
    background: #fff; border-radius: 22px;
    width: 100%; max-width: 480px;
    box-shadow: 0 32px 80px rgba(0,0,0,.22);
    transform: translateY(20px) scale(.97);
    transition: transform .3s cubic-bezier(.16,1,.3,1), opacity .3s;
    opacity: 0; overflow: hidden;
}
.pwd-modal-bg.open .pwd-modal-box { transform: none; opacity: 1; }

.pwd-modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 22px 24px 0;
}
.pwd-modal-head-left { display: flex; align-items: center; gap: 14px; }
.pwd-modal-icon {
    width: 46px; height: 46px; border-radius: 14px;
    background: linear-gradient(135deg,#0f172a,#1e293b);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 16px rgba(15,23,42,.3);
}
.pwd-modal-icon i { color: #4ade80; font-size: 17px; }
.pwd-modal-title { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
.pwd-modal-sub   { font-size: .72rem; color: #64748b; margin-top: 2px; }
.pwd-modal-close {
    width: 36px; height: 36px; border-radius: 10px;
    background: #f1f5f9; border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    color: #64748b; cursor: pointer; font-size: 14px;
    transition: background .15s, color .15s;
}
.pwd-modal-close:hover { background: #fee2e2; color: #ef4444; border-color: #fecaca; }

.pwd-modal-body { padding: 22px 24px 28px; }

/* ── User selector ── */
.pwd-user-select-wrap { position: relative; margin-bottom: 20px; }
.pwd-user-select-wrap i {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 12px; pointer-events: none;
}
.pwd-user-select {
    width: 100%; padding: 11px 12px 11px 38px;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 12px; font-size: .875rem; font-weight: 600; color: #0f172a;
    outline: none; appearance: none;
    transition: border-color .18s, background .18s, box-shadow .18s;
}
.pwd-user-select:focus {
    border-color: #16a34a; background: #fff;
    box-shadow: 0 0 0 3px rgba(22,163,74,.1);
}

/* ── Fields ── */
.pwd-fields { display: flex; flex-direction: column; gap: 14px; margin-bottom: 20px; }
.pwd-field  { display: flex; flex-direction: column; gap: 5px; }
.pwd-field-label {
    font-size: .67rem; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: #64748b;
}
.pwd-field-wrap { position: relative; }
.pwd-field-input {
    width: 100%; padding: 11px 44px 11px 14px;
    background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 12px; font-size: .875rem; font-weight: 500; color: #0f172a;
    outline: none; transition: border-color .18s, box-shadow .18s, background .18s;
}
.pwd-field-input:focus {
    border-color: #16a34a; background: #fff;
    box-shadow: 0 0 0 3px rgba(22,163,74,.1);
}
.pwd-field-input.err { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.1); }
.pwd-eye-btn {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #94a3b8; font-size: 13px; padding: 4px;
    transition: color .15s;
}
.pwd-eye-btn:hover { color: #475569; }

/* Strength bar */
.pwd-strength { margin-top: 6px; }
.pwd-strength-bar {
    height: 4px; border-radius: 3px; background: #e2e8f0;
    overflow: hidden; margin-bottom: 4px;
}
.pwd-strength-fill { height: 100%; border-radius: 3px; width: 0; transition: width .3s, background .3s; }
.pwd-strength-text { font-size: .62rem; font-weight: 600; color: #94a3b8; }

/* Alert inside modal */
.pwd-alert {
    display: none; align-items: center; gap: 10px;
    padding: 11px 14px; border-radius: 11px;
    font-size: .8rem; font-weight: 600; margin-bottom: 16px;
}
.pwd-alert.ok  { display: flex; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.pwd-alert.err { display: flex; background: #fff1f2; color: #dc2626; border: 1px solid #fecada; }

/* Divider */
.pwd-divider {
    border: none; border-top: 1px solid #f1f5f9; margin: 20px 0;
}

/* Submit */
.pwd-submit {
    width: 100%; padding: 13px;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #fff; font-weight: 800; font-size: .9rem;
    border: none; border-radius: 14px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    box-shadow: 0 4px 14px rgba(15,23,42,.28);
    transition: transform .15s, box-shadow .2s, background .2s;
}
.pwd-submit:hover { background: linear-gradient(135deg,#16a34a,#15803d); box-shadow: 0 6px 20px rgba(22,163,74,.3); }
.pwd-submit:active { transform: scale(.97); }
.pwd-submit:disabled { opacity: .6; cursor: not-allowed; }

/* ══ MODAL UTILISATEURS ══ */
.modal-bg {
    position: fixed; inset: 0; z-index: 9998;
    background: rgba(2,6,23,.65); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
    opacity: 0; pointer-events: none;
    transition: opacity .25s;
}
.modal-bg.open { opacity: 1; pointer-events: auto; }
.modal-box {
    background: #fff; border-radius: 24px;
    width: 100%; max-width: 560px;
    max-height: 90vh; overflow: hidden;
    display: flex; flex-direction: column;
    box-shadow: 0 24px 64px rgba(0,0,0,.2);
    transform: scale(.94) translateY(10px);
    transition: transform .3s cubic-bezier(.16,1,.3,1);
}
.modal-bg.open .modal-box { transform: scale(1) translateY(0); }
.modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 22px 24px 18px; border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(135deg, #f8fafc, #f0fdf4); flex-shrink: 0;
}
.modal-head-title { font-size: 1rem; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 10px; }
.modal-head-icon { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg,#16a34a,#22c55e); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; }
.modal-close { width: 34px; height: 34px; border-radius: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 13px; cursor: pointer; transition: background .2s, color .2s; }
.modal-close:hover { background: #fee2e2; color: #dc2626; }
.modal-body { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 20px; }

/* Form */
.form-section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px 20px; }
.form-section-title { font-size: .72rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .12em; margin-bottom: 14px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-field { display: flex; flex-direction: column; gap: 5px; }
.form-field label { font-size: .75rem; font-weight: 700; color: #475569; }
.form-input {
    padding: 10px 14px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; background: #fff;
    font-size: .84rem; font-family: inherit; color: #1e293b;
    outline: none; transition: border-color .2s, box-shadow .2s;
    width: 100%;
}
.form-input:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }
.form-select { padding: 10px 14px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; font-size: .84rem; font-family: inherit; color: #1e293b; outline: none; transition: border-color .2s; width: 100%; appearance: none; cursor: pointer; }
.form-select:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }
.btn-create {
    width: 100%; padding: 12px; border-radius: 12px;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff; font-weight: 800; font-size: .88rem;
    border: none; cursor: pointer; letter-spacing: .01em;
    transition: opacity .2s, transform .15s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-create:hover { opacity: .92; transform: translateY(-1px); }
.btn-create:active { transform: scale(.98); }
.btn-create:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* Alerte modale */
.modal-alert { padding: 10px 14px; border-radius: 10px; font-size: .8rem; font-weight: 600; display: none; }
.modal-alert.ok  { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 7px; }
.modal-alert.err { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; display: flex; align-items: center; gap: 7px; }

/* Liste utilisateurs */
.users-section { display: flex; flex-direction: column; gap: 10px; }
.users-section-title { font-size: .72rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .12em; }
.user-list { display: flex; flex-direction: column; gap: 8px; max-height: 240px; overflow-y: auto; }
.user-card {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 12px;
    background: #fff; border: 1px solid #e2e8f0;
    transition: border-color .2s;
}
.user-card:hover { border-color: #cbd5e1; }
.user-card-avatar {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: .8rem; color: #fff;
}
.user-card-avatar.commercial { background: linear-gradient(135deg,#f59e0b,#d97706); }
.user-card-avatar.rh         { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.user-card-info { flex: 1; min-width: 0; }
.user-card-name { font-size: .84rem; font-weight: 700; color: #1e293b; }
.user-card-role { font-size: .68rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; margin-top: 1px; }
.role-chip { padding: 3px 9px; border-radius: 7px; font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
.role-chip.commercial { background: #fef3c7; color: #92400e; }
.role-chip.rh         { background: #ede9fe; color: #5b21b6; }
.role-chip.admin      { background: #dcfce7; color: #14532d; }
.btn-del-user { width: 30px; height: 30px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; background: #fff5f5; color: #dc2626; font-size: 11px; transition: background .2s; flex-shrink: 0; }
.btn-del-user:hover { background: #fee2e2; }
.user-list-empty { text-align: center; padding: 20px; color: #94a3b8; font-size: .8rem; font-weight: 500; }

/* Bouton Users header */
.btn-users {
    width: 42px; height: 42px; border-radius: 12px;
    background: var(--bg); border: 1.5px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: var(--slate); font-size: 15px;
    transition: background .2s, border-color .2s, color .2s, transform .15s;
}
.btn-users:hover { background: #fff; border-color: var(--green); color: var(--green); transform: scale(1.04); }

@media (max-width: 640px) {
    .modal-box { border-radius: 20px; max-height: 92vh; }
    .modal-head { padding: 16px 18px 14px; }
    .modal-body { padding: 16px 18px; }
    .form-row { grid-template-columns: 1fr; }
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

<div class="layout">

<?php include 'sidebar_nav.php'; ?>

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
                <div class="header-breadcrumb">Bienvenue, <?= htmlspecialchars($current_user) ?> · <?= htmlspecialchars($currentRoleLabel) ?> — <span id="live-time"></span></div>
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
                        <?php if ($canSeeMsg): ?>
                        <button class="notif-tab <?= $firstTab === 'msg' ? 'active' : '' ?>" id="tabMsg" onclick="switchTab('msg')">
                            <i class="fas fa-envelope" style="font-size:11px;"></i> Messages
                            <span class="notif-tab-count" id="tabMsgCount">0</span>
                        </button>
                        <?php endif; ?>
                        <?php if ($canSeeCmd): ?>
                        <button class="notif-tab <?= $firstTab === 'cmd' ? 'active' : '' ?>" id="tabCmd" onclick="switchTab('cmd')">
                            <i class="fas fa-shopping-bag" style="font-size:11px;"></i> Commandes
                            <span class="notif-tab-count" id="tabCmdCount">0</span>
                        </button>
                        <?php endif; ?>
                        <?php if ($canSeeCand): ?>
                        <button class="notif-tab <?= $firstTab === 'cand' ? 'active' : '' ?>" id="tabCand" onclick="switchTab('cand')">
                            <i class="fas fa-user-tie" style="font-size:11px;"></i> Recrutements
                            <span class="notif-tab-count" id="tabCandCount">0</span>
                        </button>
                        <?php endif; ?>
                        <?php if ($canSeeStock): ?>
                        <button class="notif-tab <?= $firstTab === 'stock' ? 'active' : '' ?>" id="tabStock" onclick="switchTab('stock')">
                            <i class="fas fa-box-open" style="font-size:11px;"></i> Stock
                            <span class="notif-tab-count" id="tabStockCount">0</span>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="notif-list">
                        <?php if ($canSeeMsg): ?>
                        <!-- Messages panel -->
                        <div class="notif-panel <?= $firstTab === 'msg' ? 'active' : '' ?>" id="panelMsg">
                            <div class="notif-empty" id="emptyMsg">
                                <i class="fas fa-envelope-open-text"></i>
                                <p>Aucun nouveau message</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($canSeeCmd): ?>
                        <!-- Commandes panel -->
                        <div class="notif-panel <?= $firstTab === 'cmd' ? 'active' : '' ?>" id="panelCmd">
                            <div class="notif-empty" id="emptyCmd">
                                <i class="fas fa-shopping-bag"></i>
                                <p>Aucune nouvelle commande</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($canSeeCand): ?>
                        <!-- Candidatures panel -->
                        <div class="notif-panel <?= $firstTab === 'cand' ? 'active' : '' ?>" id="panelCand">
                            <div class="notif-empty" id="emptyCand">
                                <i class="fas fa-user-check"></i>
                                <p>Aucune nouvelle candidature</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($canSeeStock): ?>
                        <!-- Stock panel -->
                        <div class="notif-panel <?= $firstTab === 'stock' ? 'active' : '' ?>" id="panelStock">
                            <div class="notif-empty" id="emptyStock">
                                <i class="fas fa-box-open"></i>
                                <p>Aucune rupture de stock</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="notif-footer">
                        <?php if ($canSeeMsg): ?>
                        <a href="messages.php" class="notif-footer-btn secondary">
                            <i class="fas fa-envelope" style="font-size:11px;"></i> Messages
                        </a>
                        <?php endif; ?>
                        <?php if ($canSeeCmd): ?>
                        <a href="admin_commandes.php" class="notif-footer-btn secondary">
                            <i class="fas fa-shopping-bag" style="font-size:11px;"></i> Commandes
                        </a>
                        <?php endif; ?>
                        <?php if ($canSeeCand): ?>
                        <a href="voir_candidatures.php" class="notif-footer-btn <?= $canSeeMsg ? 'secondary' : 'primary' ?>">
                            <i class="fas fa-users" style="font-size:11px;"></i> RH
                        </a>
                        <?php endif; ?>
                        <?php if ($canSeeStock && $stockLinkable): ?>
                        <a href="products_manager.php" class="notif-footer-btn primary">
                            <i class="fas fa-box-open" style="font-size:11px;"></i> Stock
                        </a>
                        <?php elseif ($canSeeStock): ?>
                        <span class="notif-footer-btn primary" style="opacity:.55;cursor:not-allowed;" title="Accès réservé à l'administrateur">
                            <i class="fas fa-lock" style="font-size:11px;"></i> Stock
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($current_role === 'admin'): ?>
            <!-- Bouton gestion utilisateurs (admin only) -->
            <button class="btn-users" id="btnUsers" title="Gérer les utilisateurs" aria-label="Gérer les utilisateurs">
                <i class="fas fa-users-cog"></i>
            </button>
            <?php endif; ?>

            <!-- Compte utilisateur -->
            <div class="account-wrap" id="accountWrap">
                <button class="admin-avatar" id="accountBtn" aria-haspopup="true" aria-expanded="false" aria-label="Mon compte">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($current_user, 0, 1, 'UTF-8'))) ?>
                </button>

                <div class="account-dropdown" id="accountDropdown" role="menu" aria-label="Menu du compte">
                    <div class="account-head">
                        <div class="account-head-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($current_user, 0, 1, 'UTF-8'))) ?></div>
                        <div class="account-head-info">
                            <div class="account-head-name"><?= htmlspecialchars($current_user) ?></div>
                            <div class="account-head-status"><span class="dot"></span><?= htmlspecialchars($currentRoleLabel) ?></div>
                        </div>
                    </div>
                    <div class="account-menu">
                        <?php if ($current_role === 'admin'): ?>
                        <button class="account-menu-item" id="menuActivityLog">
                            <i class="fas fa-clock-rotate-left"></i> Journal d'activité
                        </button>
                        <div class="account-menu-divider"></div>
                        <?php endif; ?>
                        <a href="logout.php" class="account-menu-item danger">
                            <i class="fas fa-sign-out-alt"></i> Se déconnecter
                        </a>
                    </div>
                </div>
            </div>
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

        <?php if ($ruptureCount > 0 && $current_role === 'admin'): ?>
        <!-- ALERTE RUPTURE DE STOCK — visible directement sur le dashboard -->
        <div class="stock-alert">
            <div class="stock-alert-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="stock-alert-body">
                <div class="stock-alert-title">
                    <?= $ruptureCount ?> produit<?= $ruptureCount > 1 ? 's' : '' ?> en rupture de stock
                </div>
                <div class="stock-alert-list">
                    <?php foreach (array_slice($ruptureProducts, 0, 6) as $p): ?>
                    <span class="stock-alert-chip">
                        <?= htmlspecialchars($p['nom']) ?><?= $p['format'] ? ' · '.htmlspecialchars($p['format']) : '' ?>
                    </span>
                    <?php endforeach; ?>
                    <?php if ($ruptureCount > 6): ?>
                    <span class="stock-alert-chip more">+<?= $ruptureCount - 6 ?> autre<?= ($ruptureCount-6) > 1 ? 's' : '' ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="products_manager.php" class="stock-alert-action">
                Réapprovisionner <i class="fas fa-arrow-right" style="font-size:.65rem;"></i>
            </a>
        </div>
        <?php endif; ?>

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
                    <?php if ($ruptureCount > 0 && $current_role === 'admin'): ?>
                    <div class="kpi-trend down"><i class="fas fa-triangle-exclamation" style="font-size:.6rem;"></i> <?= $ruptureCount ?> rupture<?= $ruptureCount > 1 ? 's' : '' ?></div>
                    <?php else: ?>
                    <div class="kpi-trend neu"><i class="fas fa-minus" style="font-size:.6rem;"></i> Total</div>
                    <?php endif; ?>
                </div>
                <div class="kpi-value" data-count="<?= $totalProduits ?>">0</div>
                <div class="kpi-label">Produits actifs</div>
            </div>
        </div>

        <!-- Charts + Activity -->
        <div class="section-grid">

            <?php if ($canSeeMsg): ?>
            <!-- Chart messages : admin + commercial -->
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
            <?php endif; ?>

            <?php if ($canSeeCand): ?>
            <!-- Donut candidatures : admin + RH -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <div class="card-title">Statut des candidatures</div>
                        <div class="card-sub">Répartition par décision RH</div>
                    </div>
                    <a href="voir_candidatures.php" class="card-action">Voir tout <i class="fas fa-arrow-right" style="font-size:.65rem;"></i></a>
                </div>
                <div class="card-body" style="display:flex;align-items:center;justify-content:center;gap:30px;">
                    <canvas id="candChart" width="160" height="160" style="max-width:160px;max-height:160px;"></canvas>
                    <div id="candLegend" style="font-size:.78rem;line-height:2;"></div>
                </div>
            </div>
            <?php endif; ?>

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
                            $isMsg = ($act['type'] === 'message');
                            $initials = mb_strtoupper(mb_substr($act['nom'], 0, 1, 'UTF-8'));
                            $ts = strtotime($act['created_at']);
                            $diff = time() - $ts;
                            if ($diff < 60)       $ago = 'À l\'instant';
                            elseif ($diff < 3600)  $ago = floor($diff/60).'min';
                            elseif ($diff < 86400) $ago = floor($diff/3600).'h';
                            else                   $ago = date('d/m/Y', $ts);
                        ?>
                        <div class="activity-item">
                            <div class="activity-dot <?= $isMsg ? 'msg' : 'cand' ?>"><?= $initials ?></div>
                            <div class="activity-info">
                                <span class="type-tag <?= $isMsg ? 'msg' : 'cand' ?>">
                                    <?= $isMsg ? '<i class="fas fa-envelope" style="font-size:.55rem;"></i> Message' : '<i class="fas fa-user-tie" style="font-size:.55rem;"></i> Candidature' ?>
                                </span>
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
     MODAL GESTION UTILISATEURS (admin only)
═══════════════════════════════════════════════ -->
<?php if ($current_role === 'admin'): ?>
<div class="modal-bg" id="modalUsers" role="dialog" aria-modal="true" aria-label="Gestion des utilisateurs">
    <div class="modal-box">
        <div class="modal-head">
            <div class="modal-head-title">
                <div class="modal-head-icon"><i class="fas fa-users-cog"></i></div>
                Gestion des utilisateurs
            </div>
            <button class="modal-close" id="modalClose" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">

            <!-- Alerte retour -->
            <div class="modal-alert" id="modalAlert"></div>

            <!-- Créer un compte -->
            <div class="form-section">
                <div class="form-section-title"><i class="fas fa-user-plus" style="margin-right:6px;"></i>Créer un nouveau compte</div>
                <div class="form-row" style="margin-bottom:12px;">
                    <div class="form-field">
                        <label for="newUsername">Identifiant</label>
                        <input type="text" id="newUsername" class="form-input" placeholder="ex: jean.dupont" autocomplete="off">
                    </div>
                    <div class="form-field">
                        <label for="newPassword">Mot de passe</label>
                        <div style="position:relative;">
                            <input type="password" id="newPassword" class="form-input" placeholder="Min. 6 caractères" autocomplete="new-password">
                            <button type="button" onclick="togglePwd()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:13px;" id="eyeBtn"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="form-row" style="margin-bottom:16px;">
                    <div class="form-field">
                        <label for="newRole">Rôle</label>
                        <select id="newRole" class="form-select">
                            <option value="commercial">🛒 Commercial</option>
                            <option value="rh">👥 Ressources Humaines</option>
                        </select>
                    </div>
                    <div class="form-field" id="confirmField">
                        <label for="confirmPassword">Confirmer le mot de passe</label>
                        <input type="password" id="confirmPassword" class="form-input" placeholder="Répéter le mot de passe">
                    </div>
                </div>
                <button class="btn-create" id="btnCreate" onclick="createUser()">
                    <i class="fas fa-plus-circle"></i> Créer le compte
                </button>
            </div>

            <!-- Liste des comptes existants -->
            <div class="users-section">
                <div class="users-section-title"><i class="fas fa-list" style="margin-right:6px;"></i>Comptes existants</div>
                <div class="user-list" id="userList">
                    <div class="user-list-empty"><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Chargement…</div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════
     MODAL CHANGEMENT MOT DE PASSE (admin only)
═══════════════════════════════════════════════ -->
<?php if ($current_role === 'admin'): ?>
<div class="pwd-modal-bg" id="pwdModal" role="dialog" aria-modal="true" aria-label="Changer le mot de passe">
    <div class="pwd-modal-box">

        <div class="pwd-modal-head">
            <div class="pwd-modal-head-left">
                <div class="pwd-modal-icon"><i class="fas fa-key"></i></div>
                <div>
                    <div class="pwd-modal-title">Modifier le mot de passe</div>
                    <div class="pwd-modal-sub">Réinitialisation sécurisée d'un compte</div>
                </div>
            </div>
            <button class="pwd-modal-close" id="pwdModalClose" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="pwd-modal-body">

            <!-- Alerte -->
            <div class="pwd-alert" id="pwdAlert"></div>

            <!-- Sélecteur utilisateur -->
            <div class="pwd-user-select-wrap">
                <i class="fas fa-user"></i>
                <select class="pwd-user-select" id="pwdUserId">
                    <option value="">— Choisir un compte —</option>
                </select>
            </div>

            <hr class="pwd-divider">

            <!-- Champs mot de passe -->
            <div class="pwd-fields">
                <div class="pwd-field">
                    <label class="pwd-field-label">Nouveau mot de passe</label>
                    <div class="pwd-field-wrap">
                        <input type="password" class="pwd-field-input" id="pwdNew"
                               placeholder="Min. 8 caractères recommandés"
                               autocomplete="new-password"
                               oninput="pwdStrength(this.value)">
                        <button type="button" class="pwd-eye-btn" onclick="togglePwdField('pwdNew',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <!-- Barre de force -->
                    <div class="pwd-strength">
                        <div class="pwd-strength-bar"><div class="pwd-strength-fill" id="strengthFill"></div></div>
                        <div class="pwd-strength-text" id="strengthText">Entrez un mot de passe</div>
                    </div>
                </div>

                <div class="pwd-field">
                    <label class="pwd-field-label">Confirmer le mot de passe</label>
                    <div class="pwd-field-wrap">
                        <input type="password" class="pwd-field-input" id="pwdConfirm"
                               placeholder="Répéter le mot de passe"
                               autocomplete="new-password">
                        <button type="button" class="pwd-eye-btn" onclick="togglePwdField('pwdConfirm',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bouton submit -->
            <button class="pwd-submit" id="pwdSubmitBtn" onclick="submitPwdChange()">
                <i class="fas fa-shield-halved"></i>
                Enregistrer le nouveau mot de passe
            </button>

        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════
     MODAL JOURNAL D'ACTIVITÉ (admin only)
═══════════════════════════════════════════════ -->
<?php if ($current_role === 'admin'): ?>
<div class="modal-bg" id="modalLog" role="dialog" aria-modal="true" aria-label="Journal d'activité">
    <div class="modal-box">
        <div class="modal-head">
            <div class="modal-head-title">
                <div class="modal-head-icon"><i class="fas fa-clock-rotate-left"></i></div>
                Journal d'activité
            </div>
            <button class="modal-close" id="modalLogClose" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">

            <div class="log-warn-banner" id="logWarnBanner" style="display:none;">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    Cette fonctionnalité nécessite la table <code>activity_log</code>.
                    Exécutez <code>activity_log_migration.sql</code> dans phpMyAdmin, puis rouvrez ce journal.
                </div>
            </div>

            <div class="log-filters">
                <button class="log-filter-btn active" data-filter="all" onclick="loadActivityLog('all')">Tout</button>
                <button class="log-filter-btn" data-filter="commandes" onclick="loadActivityLog('commandes')">Commandes</button>
                <button class="log-filter-btn" data-filter="candidatures" onclick="loadActivityLog('candidatures')">Candidatures</button>
            </div>

            <div class="log-list" id="logList">
                <div class="log-list-empty"><i class="fas fa-spinner fa-spin"></i>Chargement…</div>
            </div>

        </div>
    </div>
</div>
<?php endif; ?>

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


// ══════════════════════════════════════════════
//  NOTIFICATION SYSTEM
// ══════════════════════════════════════════════
const notifBtn      = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
const notifBadge    = document.getElementById('notifBadge');
const markAllBtn    = document.getElementById('markAllRead');

let currentTab = '<?= $firstTab ?>';
let notifData  = { messages: [], candidatures: [], stock: [], commandes: [] };

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

// Tab switching (défensif : certains onglets n'existent pas selon le rôle connecté)
function switchTab(tab) {
    currentTab = tab;
    ['tabMsg','tabCmd','tabCand','tabStock'].forEach(id => {
        const map = { tabMsg: 'msg', tabCmd: 'cmd', tabCand: 'cand', tabStock: 'stock' };
        document.getElementById(id)?.classList.toggle('active', tab === map[id]);
    });
    ['panelMsg','panelCmd','panelCand','panelStock'].forEach(id => {
        const map = { panelMsg: 'msg', panelCmd: 'cmd', panelCand: 'cand', panelStock: 'stock' };
        document.getElementById(id)?.classList.toggle('active', tab === map[id]);
    });
}
window.switchTab = switchTab;

// Mark all read — note : la rupture de stock est un statut LIVE,
// elle n'est jamais "marquée lue" : elle disparaît seulement quand le produit est réapprovisionné.
markAllBtn.addEventListener('click', async () => {
    await fetch('?api=mark_read');
    notifData.messages     = [];
    notifData.candidatures = [];
    notifData.commandes    = [];
    renderNotifications();
    updateBadge(notifData.stock.length);
    if (notifData.stock.length === 0) notifBtn.classList.remove('has-new');
    markAllBtn.innerHTML = '<i class="fas fa-check" style="margin-right:4px;"></i> Lu';
    setTimeout(() => { markAllBtn.innerHTML = '<i class="fas fa-check-double" style="margin-right:4px;"></i> Tout marquer lu'; }, 2000);
});

// Fetch from API
async function fetchNotifications() {
    try {
        const res  = await fetch('?api=notifications&_=' + Date.now());
        const data = await res.json();
        notifData  = data;
        renderNotifications();
        updateBadge(data.total || 0);

        // Update sidebar badges
        const sbMsg   = document.getElementById('sidebar-msg-badge');
        const sbCmd   = document.getElementById('sidebar-cmd-badge');
        const sbCand  = document.getElementById('sidebar-cand-badge');
        const sbStock = document.getElementById('sidebar-stock-badge');
        const mc  = data.messages.length;
        const cmc = (data.commandes || []).length;
        const cc  = data.candidatures.length;
        const sc  = (data.stock || []).length;
        if (sbMsg)   { sbMsg.textContent   = mc;  sbMsg.style.display   = mc  ? 'inline-flex' : 'none'; }
        if (sbCmd)   { sbCmd.textContent   = cmc; sbCmd.style.display   = cmc ? 'inline-flex' : 'none'; }
        if (sbCand)  { sbCand.textContent  = cc;  sbCand.style.display  = cc  ? 'inline-flex' : 'none'; }
        if (sbStock) { sbStock.textContent = sc;  sbStock.style.display = sc  ? 'inline-flex' : 'none'; }


        // Bell animation on new notifications
        if (data.total > 0) {
            notifBtn.classList.add('has-new', 'shake');
            setTimeout(() => notifBtn.classList.remove('shake'), 700);
        } else {
            notifBtn.classList.remove('has-new');
        }
    } catch(err) { console.warn('Notif fetch error:', err); }
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
    const d    = new Date(dateStr);
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
    const msgs  = notifData.messages    || [];
    const cands = notifData.candidatures || [];
    const stock = notifData.stock        || [];
    const cmds  = notifData.commandes    || [];

    document.getElementById('tabMsgCount')?.replaceChildren(document.createTextNode(msgs.length));
    document.getElementById('tabCmdCount')?.replaceChildren(document.createTextNode(cmds.length));
    document.getElementById('tabCandCount')?.replaceChildren(document.createTextNode(cands.length));
    document.getElementById('tabStockCount')?.replaceChildren(document.createTextNode(stock.length));

    // Messages panel
    const panelMsg = document.getElementById('panelMsg');
    if (panelMsg) {
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
    }

    // Commandes panel — lien actif (le commercial a accès à admin_commandes.php)
    const panelCmd = document.getElementById('panelCmd');
    if (panelCmd) {
    if (cmds.length === 0) {
        panelCmd.innerHTML = `<div class="notif-empty"><i class="fas fa-shopping-bag"></i><p>Aucune nouvelle commande</p></div>`;
    } else {
        panelCmd.innerHTML = cmds.map(c => `
            <div class="notif-item new" onclick="window.location='admin_commandes.php'">
                <div class="notif-avatar cmd">${initials(((c.nom||'')+' '+(c.prenom||'')).trim())}</div>
                <div class="notif-item-body">
                    <div class="notif-item-name">${escHtml(((c.nom||'')+' '+(c.prenom||'')).trim() || 'Client')}</div>
                    <div class="notif-item-detail"><i class="fas fa-store" style="font-size:.65rem;margin-right:3px;color:#16a34a;"></i>${escHtml(c.nom_marche || c.region || 'Nouvelle commande')}</div>
                    <div class="notif-item-time"><i class="fas fa-clock" style="font-size:.6rem;margin-right:3px;"></i>${timeAgo(c.date_commande)}</div>
                </div>
            </div>
        `).join('');
    }
    }

    // Candidatures panel
    const panelCand = document.getElementById('panelCand');
    if (panelCand) {
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
    }

    // Stock panel — rupture de stock (statut LIVE, pas de "lu/non lu")
    const panelStock = document.getElementById('panelStock');
    if (panelStock) {
    if (stock.length === 0) {
        panelStock.innerHTML = `<div class="notif-empty"><i class="fas fa-box-open"></i><p>Aucune rupture de stock</p></div>`;
    } else {
        panelStock.innerHTML = stock.map(p => {
            const tag    = stockLinkable ? 'a' : 'div';
            const href   = stockLinkable ? `href="products_manager.php?id=${p.id}"` : '';
            const cursor = stockLinkable ? '' : 'style="cursor:default;"';
            const lock   = !stockLinkable ? `<span style="font-size:.6rem;padding:1px 6px;background:#fee2e2;color:#dc2626;border-radius:5px;font-weight:700;margin-left:5px;"><i class='fas fa-lock'></i> Admin</span>` : '';
            return `<${tag} ${href} ${cursor} class="notif-item new" style="text-decoration:none;">
                <div class="notif-avatar stock"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="notif-item-body">
                    <div class="notif-item-name">${escHtml(p.nom)} ${lock}</div>
                    <div class="notif-item-detail"><i class="fas fa-box" style="font-size:.65rem;margin-right:3px;color:#f59e0b;"></i>${escHtml(p.format || '')} — en rupture</div>
                    <div class="notif-item-time"><i class="fas fa-circle" style="font-size:.5rem;margin-right:3px;color:#ef4444;"></i>Stock : 0 unité</div>
                </div>
            </${tag}>`;
        }).join('');
        if (!stockLinkable) {
            panelStock.innerHTML += `<div style="padding:8px 14px;font-size:.7rem;color:#92400e;font-weight:700;text-align:center;background:#fffbeb;border-top:1px solid #fef3c7;">
                <i class="fas fa-lock" style="margin-right:5px;"></i>Contactez l'administrateur pour gérer le stock</div>`;
        }
    }
    }
}

function escHtml(str) {
    return (str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

const stockLinkable = <?= ($current_role === 'admin') ? 'true' : 'false' ?>;

// Initial fetch + polling every 30s
fetchNotifications();
setInterval(fetchNotifications, 15000); // toutes les 15s

// ══════════════════════════════════════════════
//  GLOBAL SEARCH (quick redirect)
// ══════════════════════════════════════════════
document.getElementById('global-search').addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        const q = e.target.value.trim();
        if (q) window.location.href = 'messages.php?q=' + encodeURIComponent(q);
    }
});

// ══════════════════════════════════════════════
//  MODAL GESTION UTILISATEURS
// ══════════════════════════════════════════════
(function() {
    const btnUsers  = document.getElementById('btnUsers');
    const modal     = document.getElementById('modalUsers');
    const btnClose  = document.getElementById('modalClose');
    if (!btnUsers || !modal) return;

    function openModal()  {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        loadUsers();
    }
    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        clearAlert();
    }

    btnUsers.addEventListener('click', openModal);
    btnClose.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // Afficher/masquer mdp
    window.togglePwd = function() {
        const inp = document.getElementById('newPassword');
        const ico = document.getElementById('eyeBtn').querySelector('i');
        if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fas fa-eye-slash'; }
        else { inp.type = 'password'; ico.className = 'fas fa-eye'; }
    };

    function showAlert(msg, type) {
        const el = document.getElementById('modalAlert');
        el.className = 'modal-alert ' + type;
        el.innerHTML = `<i class="fas fa-${type==='ok'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
        el.style.display = 'flex';
        if (type === 'ok') setTimeout(() => el.style.display = 'none', 3500);
    }
    function clearAlert() {
        const el = document.getElementById('modalAlert');
        if (el) el.style.display = 'none';
    }

    // Charger la liste des utilisateurs
    async function loadUsers() {
        const list = document.getElementById('userList');
        list.innerHTML = '<div class="user-list-empty"><i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Chargement…</div>';
        try {
            const res  = await fetch('?api=get_users');
            const data = await res.json();
            if (data.ok === false) {
                list.innerHTML = `<div class="user-list-empty" style="color:#dc2626;"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>${escHtml(data.msg || 'Erreur de chargement.')}</div>`;
                return;
            }
            const users = (data.users || []).filter(u => u.role !== 'admin');
            if (!users.length) {
                list.innerHTML = '<div class="user-list-empty"><i class="fas fa-users" style="margin-right:6px;opacity:.4;"></i>Aucun compte commercial ou RH créé.</div>';
                return;
            }
            const roleLabel = { commercial: '🛒 Commercial', rh: '👥 RH' };
            list.innerHTML = users.map(u => `
                <div class="user-card" id="uc-${u.id}">
                    <div class="user-card-avatar ${u.role}">${(u.username||'?')[0].toUpperCase()}</div>
                    <div class="user-card-info">
                        <div class="user-card-name">${escHtml(u.username)}</div>
                        <div class="user-card-role">${roleLabel[u.role] || u.role}</div>
                    </div>
                    <span class="role-chip ${u.role}">${u.role.toUpperCase()}</span>
                    <button class="btn-del-user" style="background:#f0fdf4;border-color:#bbf7d0;color:#16a34a;margin-right:4px;" onclick="openPwdModal(${u.id},'${escHtml(u.username)}')" title="Changer le mot de passe">
                        <i class="fas fa-key"></i>
                    </button>
                    <button class="btn-del-user" onclick="deleteUser(${u.id},'${escHtml(u.username)}')" title="Supprimer">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `).join('');
        } catch(e) {
            list.innerHTML = '<div class="user-list-empty" style="color:#dc2626;"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>Erreur de chargement (réponse invalide du serveur).</div>';
        }
    }

    // Créer un utilisateur
    window.createUser = async function() {
        clearAlert();
        const username = document.getElementById('newUsername').value.trim();
        const password = document.getElementById('newPassword').value.trim();
        const confirm  = document.getElementById('confirmPassword').value.trim();
        const role     = document.getElementById('newRole').value;
        const btn      = document.getElementById('btnCreate');

        if (!username || !password) { showAlert('Remplissez tous les champs.', 'err'); return; }
        if (password !== confirm)   { showAlert('Les mots de passe ne correspondent pas.', 'err'); return; }
        if (password.length < 6)   { showAlert('Mot de passe trop court (min. 6 caractères).', 'err'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création…';
        try {
            const res  = await fetch('?api=create_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password, role })
            });
            const data = await res.json();
            if (data.ok) {
                showAlert(data.msg, 'ok');
                document.getElementById('newUsername').value  = '';
                document.getElementById('newPassword').value  = '';
                document.getElementById('confirmPassword').value = '';
                loadUsers();
            } else {
                showAlert(data.msg, 'err');
            }
        } catch(e) {
            showAlert('Erreur réseau.', 'err');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus-circle"></i> Créer le compte';
        }
    };

    // Supprimer un utilisateur
    window.deleteUser = async function(id, name) {
        if (!confirm(`Supprimer le compte "${name}" ? Cette action est irréversible.`)) return;
        try {
            const res  = await fetch('?api=delete_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.ok) {
                showAlert(data.msg, 'ok');
                const card = document.getElementById('uc-' + id);
                if (card) { card.style.opacity = '0'; card.style.transform = 'scale(.95)'; card.style.transition = 'all .3s'; setTimeout(() => card.remove(), 300); }
            } else {
                showAlert(data.msg, 'err');
            }
        } catch(e) {
            showAlert('Erreur réseau.', 'err');
        }
    };

    function escHtml(s) {
        return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();


// ══════════════════════════════════════════════
//  MODAL CHANGER MOT DE PASSE
// ══════════════════════════════════════════════
(function() {
    const modal   = document.getElementById('pwdModal');
    const btnClose = document.getElementById('pwdModalClose');
    if (!modal) return;

    function openModal() { modal.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.remove('open'); document.body.style.overflow = ''; resetPwdForm(); }

    btnClose.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });

    function resetPwdForm() {
        document.getElementById('pwdNew').value = '';
        document.getElementById('pwdConfirm').value = '';
        document.getElementById('pwdNew').className = 'pwd-field-input';
        document.getElementById('pwdConfirm').className = 'pwd-field-input';
        document.getElementById('strengthFill').style.width = '0';
        document.getElementById('strengthText').textContent = 'Entrez un mot de passe';
        document.getElementById('strengthFill').style.background = '#e2e8f0';
        hidePwdAlert();
    }

    function showPwdAlert(msg, type) {
        const el = document.getElementById('pwdAlert');
        el.className = 'pwd-alert ' + type;
        el.innerHTML = `<i class="fas fa-${type==='ok'?'check-circle':'exclamation-circle'}"></i> ${msg}`;
        if (type === 'ok') setTimeout(() => { el.className = 'pwd-alert'; closeModal(); }, 2200);
    }
    function hidePwdAlert() {
        const el = document.getElementById('pwdAlert');
        if (el) el.className = 'pwd-alert';
    }

    // Charger les utilisateurs dans le sélecteur
    async function loadPwdUsers() {
        try {
            const res   = await fetch('?api=get_users');
            const data  = await res.json();
            const sel   = document.getElementById('pwdUserId');
            const users = (data.users || []).filter(u => u.role !== 'admin');
            sel.innerHTML = '<option value="">— Choisir un compte —</option>';
            users.forEach(u => {
                const o = document.createElement('option');
                o.value = u.id;
                o.textContent = u.username + ' (' + (u.role === 'commercial' ? '🛒 Commercial' : '👥 RH') + ')';
                sel.appendChild(o);
            });
        } catch(e) { /* silencieux */ }
    }

    window.openPwdModal = function(userId, username) {
        openModal();
        loadPwdUsers().then(() => {
            if (userId) document.getElementById('pwdUserId').value = userId;
        });
    };

    window.togglePwdField = function(fieldId, btn) {
        const inp = document.getElementById(fieldId);
        const ico = btn.querySelector('i');
        if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fas fa-eye-slash'; }
        else { inp.type = 'password'; ico.className = 'fas fa-eye'; }
    };

    window.pwdStrength = function(val) {
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        if (!val) { fill.style.width='0'; text.textContent='Entrez un mot de passe'; fill.style.background='#e2e8f0'; return; }
        let score = 0;
        if (val.length >= 8)  score++;
        if (val.length >= 12) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        const levels = [
            { pct:'20%', color:'#ef4444', label:'Très faible' },
            { pct:'40%', color:'#f97316', label:'Faible' },
            { pct:'60%', color:'#eab308', label:'Moyen' },
            { pct:'80%', color:'#22c55e', label:'Fort' },
            { pct:'100%',color:'#16a34a', label:'Très fort ✓' },
        ];
        const l = levels[Math.max(0, score-1)];
        fill.style.width      = l.pct;
        fill.style.background = l.color;
        text.style.color      = l.color;
        text.textContent      = l.label;
    };

    window.submitPwdChange = async function() {
        hidePwdAlert();
        const userId  = document.getElementById('pwdUserId').value;
        const newPwd  = document.getElementById('pwdNew').value.trim();
        const confirm = document.getElementById('pwdConfirm').value.trim();
        const btn     = document.getElementById('pwdSubmitBtn');

        // Validation
        if (!userId)          { showPwdAlert('Sélectionnez un utilisateur.', 'err'); return; }
        if (!newPwd)          { showPwdAlert('Entrez un nouveau mot de passe.', 'err'); return; }
        if (newPwd.length < 6){ showPwdAlert('Mot de passe trop court (minimum 6 caractères).', 'err'); return; }
        if (newPwd !== confirm){ showPwdAlert('Les mots de passe ne correspondent pas.', 'err');
            document.getElementById('pwdConfirm').className = 'pwd-field-input err'; return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';
        try {
            const res  = await fetch('?api=change_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(userId), password: newPwd })
            });
            const data = await res.json();
            if (data.ok) { showPwdAlert(data.msg || 'Mot de passe mis à jour avec succès.', 'ok'); }
            else          { showPwdAlert(data.msg || 'Erreur lors de la mise à jour.', 'err'); }
        } catch(e) {
            showPwdAlert('Erreur réseau. Réessayez.', 'err');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-shield-halved"></i> Enregistrer le nouveau mot de passe';
        }
    };
})();

// ══════════════════════════════════════════════
//  MENU COMPTE (dropdown avatar)
// ══════════════════════════════════════════════
(function() {
    const btn      = document.getElementById('accountBtn');
    const dropdown = document.getElementById('accountDropdown');
    if (!btn || !dropdown) return;

    function openDD()  { dropdown.classList.add('open');  btn.setAttribute('aria-expanded','true'); }
    function closeDD() { dropdown.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }

    btn.addEventListener('click', e => {
        e.stopPropagation();
        dropdown.classList.contains('open') ? closeDD() : openDD();
    });
    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target) && e.target !== btn) closeDD();
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDD(); });

    const menuLog = document.getElementById('menuActivityLog');
    if (menuLog) {
        menuLog.addEventListener('click', () => {
            closeDD();
            window.openActivityLogModal();
        });
    }
})();

// ══════════════════════════════════════════════
//  MODAL JOURNAL D'ACTIVITÉ
// ══════════════════════════════════════════════
(function() {
    const modal    = document.getElementById('modalLog');
    const btnClose = document.getElementById('modalLogClose');
    if (!modal) { window.openActivityLogModal = function(){}; return; }

    function openModal() {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        loadActivityLog('all');
    }
    function closeModal() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
    window.openActivityLogModal = openModal;

    btnClose.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    const icons  = { commandes: 'fa-shopping-bag', candidatures: 'fa-user-tie' };
    const labels = {
        modification_statut: 'a modifié le statut',
        suppression:         'a supprimé',
        creation:             'a créé',
    };
    const roleLabel = { admin: 'Admin', rh: 'RH', commercial: 'Commercial' };

    window.loadActivityLog = async function(filter) {
        document.querySelectorAll('.log-filter-btn').forEach(b => b.classList.toggle('active', b.dataset.filter === filter));
        const list = document.getElementById('logList');
        const warn = document.getElementById('logWarnBanner');
        list.innerHTML = '<div class="log-list-empty"><i class="fas fa-spinner fa-spin"></i>Chargement…</div>';
        try {
            const res  = await fetch('?api=get_activity_log&filter=' + filter);
            const data = await res.json();

            if (data.error === 'table_missing') {
                warn.style.display = 'flex';
                list.innerHTML = '';
                return;
            }
            warn.style.display = 'none';

            const logs = data.logs || [];
            if (!logs.length) {
                list.innerHTML = '<div class="log-list-empty"><i class="fas fa-clipboard-list"></i>Aucune activité enregistrée pour l\'instant.</div>';
                return;
            }
            list.innerHTML = logs.map(l => `
                <div class="log-item">
                    <div class="log-icon ${l.table_concernee}"><i class="fas ${icons[l.table_concernee] || 'fa-circle-info'}"></i></div>
                    <div class="log-body">
                        <div class="log-top-row">
                            <span class="log-user">${escHtml(l.username)} <span class="role-chip ${l.role}" style="margin-left:5px;">${roleLabel[l.role] || l.role}</span></span>
                            <span class="log-time">${timeAgo(l.created_at)}</span>
                        </div>
                        <div class="log-details">${labels[l.action] || escHtml(l.action)} — ${escHtml(l.details || ('#' + l.record_id))}</div>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            list.innerHTML = '<div class="log-list-empty" style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i>Erreur de chargement.</div>';
        }
    };

    function escHtml(s) {
        return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
</body>
</html>