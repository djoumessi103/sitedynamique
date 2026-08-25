<?php
session_start();
$allowed_roles = ['admin', 'commercial'];
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── Journalisation des suppressions de commandes (appelée par le JS) ──
if (isset($_GET['api']) && $_GET['api'] === 'log_action') {
    header('Content-Type: application/json');
    $data    = json_decode(file_get_contents('php://input'), true);
    $id      = (int)($data['id'] ?? 0);
    $details = trim($data['details'] ?? '');
    if ($id) {
        try {
            require_once '../includes/log_activity.php';
            logActivity($pdo, 'suppression', 'commandes', $id, $details ?: "Commande #$id supprimée");
        } catch (Throwable $e) {
            // Ne bloque jamais la suppression elle-même, mais on le signale dans la réponse
            echo json_encode(['ok' => false, 'msg' => 'Journal indisponible : ' . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['ok' => true]);
    exit;
}

$allCommandes   = $pdo->query("SELECT * FROM commandes ORDER BY date_commande DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalCommandes = count($allCommandes);
$clients        = $pdo->query("SELECT DISTINCT nom, prenom FROM commandes ORDER BY nom")->fetchAll();
$marches        = $pdo->query("SELECT DISTINCT nom_marche FROM commandes ORDER BY nom_marche")->fetchAll();
$regions        = $pdo->query("SELECT DISTINCT region FROM commandes ORDER BY region")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Commandes</title>
    <script src="../assets/tailwind.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { galaGreen: '#16a34a', galaDark: '#0f172a' } } } }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>


/* ══ MOBILE TOPBAR ══ */
.ac-topbar {
    display: none;
    position: sticky; top: 0; z-index: 990;
    align-items: center; justify-content: space-between;
    padding: 0 16px; height: 58px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
}
@media (max-width: 768px) { .ac-topbar { display: flex; } }

/* ══ RESPONSIVE TABLE → CARDS ══ */
@media (max-width: 768px) {
    #commandes-table thead { display: none; }
    #commandes-table tbody tr {
        display: block;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        margin-bottom: 14px;
        padding: 14px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    #commandes-table tbody td {
        display: flex; align-items: flex-start;
        gap: 10px; padding: 8px 0;
        border: none; border-bottom: 1px solid #f1f5f9;
        font-size: 0.82rem;
    }
    #commandes-table tbody td:last-child { border-bottom: none; }
    #commandes-table tbody td::before {
        content: attr(data-label);
        font-weight: 800; font-size: 0.66rem;
        color: #94a3b8; text-transform: uppercase;
        letter-spacing: 0.08em; min-width: 85px;
        padding-top: 2px; flex-shrink: 0;
    }
}

/* Cacher le span de statut sur écran */
.statut-print { display: none; }

/* ══════════════════════════════════════════
   IMPRESSION — PROFESSIONNELLE 2026
══════════════════════════════════════════ */
@media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

    .sidebar, .ac-topbar, #side-overlay, .no-print, .print-hidden { display: none !important; }

    body { background: #fff !important; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
    .main { margin-left: 0 !important; }
    main { padding: 0 !important; margin: 0 !important; }

    .print-header { display: flex !important; }

    .bg-white { border: none !important; box-shadow: none !important; border-radius: 0 !important; }
    .overflow-x-auto { overflow: visible !important; }

    table { width: 100% !important; border-collapse: collapse !important; margin-top: 0 !important; font-size: 10px !important; }
    thead tr { background: #16a34a !important; color: #fff !important; }
    thead th {
        padding: 9px 10px !important; font-weight: 800 !important;
        font-size: 9px !important; text-transform: uppercase;
        letter-spacing: 0.06em; border: none !important; color: #fff !important;
    }
    tbody tr { border-bottom: 1px solid #f1f5f9 !important; }
    tbody tr:nth-child(even) { background: #f0fdf4 !important; }
    tbody td { padding: 8px 10px !important; border: none !important; color: #1e293b !important; }

    #commandes-table thead { display: table-header-group !important; }
    #commandes-table tbody tr { display: table-row !important; border-radius: 0 !important; box-shadow: none !important; }
    #commandes-table tbody td { display: table-cell !important; border-bottom: 1px solid #f1f5f9 !important; }
    #commandes-table tbody td::before { display: none !important; }

    @page { margin: 1.2cm 1.5cm; size: A4 landscape; }
}
</style>
</head>
<body class="bg-slate-50 font-sans">
<?php include 'sidebar_nav.php'; ?>

<!-- ══ MOBILE TOPBAR ══ -->
<div class="ac-topbar no-print">
    <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#16a34a,#22c55e);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:13px;">G</div>
        <span style="font-weight:900;color:#16a34a;font-size:.95rem;">Gala <span style="color:#0f172a;">Commandes</span></span>
    </div>
    <button id="admin-menu-btn" aria-label="Menu" aria-expanded="false">
        <span class="abar"></span>
        <span class="abar"></span>
        <span class="abar"></span>
    </button>
</div>

<!-- ══ MAIN ══ -->
<div class="main">
<main class="flex-1 p-4 md:p-8">
    <div class="max-w-6xl mx-auto">

        <!-- En-tête imprimable (cachée à l'écran) -->
        <div class="print-header hidden mb-6 pb-5 border-b-2 border-green-600">
            <div class="flex justify-between items-end w-full">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#16a34a,#10b981);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:18px;">G</div>
                        <h1 style="font-size:1.6rem;font-weight:900;color:#0f172a;margin:0;">Mayonnaise GALA</h1>
                    </div>
                    <p style="font-size:0.75rem;color:#64748b;margin:0;">Service Commercial · Douala, Cameroun</p>
                </div>
                <div class="text-right">
                    <h2 style="font-size:1.1rem;font-weight:800;color:#1e293b;margin:0;">Liste des Commandes</h2>
                    <p style="font-size:0.7rem;color:#94a3b8;margin:4px 0 0;">Édité le <?= date('d/m/Y à H:i') ?> · Confidentiel</p>
                </div>
            </div>
        </div>

        <!-- En-tête écran -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
            <div>
                <h2 class="text-2xl md:text-3xl font-black text-slate-800">Commandes reçues</h2>
                <p class="text-slate-500 text-sm mt-1">Gérez les finalisations des commandes.</p>
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

        <!-- Filtres -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 no-print">
            <div class="relative">
                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-yellow-400 pointer-events-none"></i>
                <select id="filterClient" onchange="filterData()" class="w-full appearance-none p-3 pl-11 rounded-xl border-2 border-slate-100 bg-slate-50 font-bold text-slate-700 outline-none focus:border-green-500 transition-all text-sm">
                    <option value="">Tous les clients</option>
                    <?php foreach($clients as $cl): ?>
                    <option value="<?= htmlspecialchars($cl['nom'].' '.$cl['prenom']) ?>"><?= htmlspecialchars($cl['nom'].' '.$cl['prenom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="relative">
                <i class="fas fa-map-marker-alt absolute left-4 top-1/2 -translate-y-1/2 text-blue-400 pointer-events-none"></i>
                <select id="filterMarche" onchange="filterData()" class="w-full appearance-none p-3 pl-11 rounded-xl border-2 border-slate-100 bg-slate-50 font-bold text-slate-700 outline-none focus:border-green-500 transition-all text-sm">
                    <option value="">Tous les marchés</option>
                    <?php foreach($marches as $m): ?>
                    <option value="<?= htmlspecialchars($m['nom_marche']) ?>"><?= htmlspecialchars($m['nom_marche']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="relative">
                <i class="fas fa-globe absolute left-4 top-1/2 -translate-y-1/2 text-red-400 pointer-events-none"></i>
                <select id="filterRegion" onchange="filterData()" class="w-full appearance-none p-3 pl-11 rounded-xl border-2 border-slate-100 bg-slate-50 font-bold text-slate-700 outline-none focus:border-green-500 transition-all text-sm">
                    <option value="">Toutes les régions</option>
                    <?php foreach($regions as $r): ?>
                    <option value="<?= htmlspecialchars($r['region']) ?>"><?= htmlspecialchars($r['region']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Tableau -->
        <div class="bg-white rounded-2xl md:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table id="commandes-table" class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Nº</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Date</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Client</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Région / Marché</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">CNI</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">N° Commercial</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm">Détails Panier</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center">Documents</th>
                            <th class="p-4 md:p-5 font-bold text-slate-600 text-sm text-center no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach ($allCommandes as $idx => $c): ?>
                    <tr data-id="<?= $c['id'] ?>" class="transition-all duration-500 hover:bg-slate-50"
                        data-client="<?= strtolower(htmlspecialchars(($c['nom']??'').' '.($c['prenom']??''))) ?>"
                        data-marche="<?= strtolower(htmlspecialchars($c['nom_marche']??'')) ?>"
                        data-region="<?= strtolower(htmlspecialchars($c['region']??'')) ?>">

                        <td class="p-4 md:p-5 font-semibold text-slate-400 text-sm" data-label="Nº">
                            <?= $idx + 1 ?>
                        </td>
                        <td class="p-4 md:p-5 text-sm text-slate-600 font-bold" data-label="Date">
                            <div><?= date('d/m/Y', strtotime($c['date_commande'])) ?></div>
                            <div class="text-xs text-slate-400 font-normal"><?= date('H:i', strtotime($c['date_commande'])) ?></div>
                        </td>
                        <td class="p-4 md:p-5 font-semibold text-slate-800 text-sm" data-label="Client">
                            <?= htmlspecialchars(trim(($c['nom']??'').' '.($c['prenom']??''))) ?>
                        </td>
                        <td class="p-4 md:p-5 text-sm" data-label="Région / Marché">
                            <div class="font-bold text-green-700 text-xs"><?= htmlspecialchars($c['region']??'') ?></div>
                            <div class="text-slate-500 text-xs mt-0.5"><?= htmlspecialchars($c['nom_marche']??'') ?></div>
                        </td>
                        <td class="p-4 md:p-5 text-sm font-mono text-slate-600" data-label="CNI">
                            <?= htmlspecialchars($c['cni']??'N/A') ?>
                        </td>
                        <td class="p-4 md:p-5 text-sm font-bold text-slate-700" data-label="N° Commercial">
                            <?= htmlspecialchars($c['num_commercial']??'N/A') ?>
                        </td>
                        <td class="p-4 md:p-5 text-xs text-slate-600 max-w-[200px] whitespace-pre-line" data-label="Détails Panier">
                            <?= htmlspecialchars($c['details_panier']??'') ?>
                        </td>
                        <td class="p-4 md:p-5" data-label="Documents">
                            <div class="flex justify-center items-center gap-2 flex-wrap">
                                <?php if (!empty($c['cni_file'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($c['cni_file']) ?>" target="_blank"
                                   class="p-2 md:px-4 md:py-2 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition">
                                    <i class="fas fa-id-card"></i><span class="hidden md:inline ml-1">CNI</span>
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($c['bon_commande'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($c['bon_commande']) ?>" target="_blank"
                                   class="p-2 md:px-4 md:py-2 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-black transition">
                                    <i class="fas fa-file-alt"></i><span class="hidden md:inline ml-1">Bon</span>
                                </a>
                                <?php endif; ?>
                                <?php if (empty($c['cni_file']) && empty($c['bon_commande'])): ?>
                                <span class="text-slate-300 text-xs">&mdash;</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="p-4 md:p-5 text-center no-print" data-label="Actions">
                            <button onclick="supprimerLigne(<?= (int)$c['id'] ?>)"
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

        <!-- Pied de page impression -->
        <div class="print-header hidden mt-8 pt-4 border-t border-slate-200 flex justify-between items-center">
            <p style="font-size:0.65rem;color:#94a3b8;">Document confidentiel — Gala Agro SARL © <?= date('Y') ?></p>
            <p style="font-size:0.65rem;color:#94a3b8;">Total : <?= $totalCommandes ?> commande(s)</p>
        </div>

    </div>
</main>
</div><!-- /.main -->

<script>
const commandesData = <?= json_encode($allCommandes, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) ?>;
</script>

<script>
function telechargerPDF() {
    const btn = document.getElementById('btn-download-pdf');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';

    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const pageWidth = doc.internal.pageSize.getWidth();

        // ── EN-TÊTE IDENTIQUE À VOTRE DESIGN ──
        // 1. Bandeau vert
        doc.setFillColor(22, 163, 74); // #16a34a
        doc.rect(0, 0, pageWidth, 24, 'F');

        // 2. Carré du logo avec le 'G'
        doc.setFillColor(255, 255, 255);
        doc.roundedRect(10, 6, 12, 12, 2, 2, 'F');
        doc.setTextColor(22, 163, 74);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(14);
        doc.text('G', 16, 14.5, { align: 'center' });

        // 3. Titre GalaMayo
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(16);
        doc.text('GalaMayo', 28, 14.5);

        // 4. Sous-titre
        doc.setFontSize(8);
        doc.setFont('helvetica', 'normal');
        doc.text('Gestion des Commandes - Rapport complet', 28, 19);

        // ── PRÉPARATION DES DONNÉES (Nettoyage pour enlever les symboles) ──
        const body = commandesData.map((c, i) => [
            (i + 1).toString(),
            ((c.nom || '') + ' ' + (c.prenom || '')).trim(),
            (c.region || '') + ' / ' + (c.nom_marche || ''),
            c.cni || 'N/A',
            c.num_commercial || 'N/A',
            // Nettoyage des caractères invisibles qui créaient des symboles
            String(c.details_panier || '').replace(/\s+/g, ' ').trim(),
            (c.cni_file ? 'CNI' : '') + (c.bon_commande ? ' BON' : '')
        ]);

        // ── TABLEAU ──
        doc.autoTable({
            startY: 30,
            head: [['Nº', 'Client', 'Région / Marché', 'CNI', 'N° Com.', 'Détails Panier', 'Docs']],
            body: body,
            theme: 'grid',
            styles: { fontSize: 8, cellPadding: 2, overflow: 'linebreak', halign: 'left', valign: 'top' },
            headStyles: { fillColor: [22, 163, 74], textColor: [255, 255, 255] },
            columnStyles: {
                0: { cellWidth: 8 },
                5: { cellWidth: 100 } // Très large pour les détails
            },
            margin: { left: 10, right: 10 }
        });

        doc.save('Commandes_GalaMayo.pdf');
    } catch (err) {
        console.error(err);
        alert("Erreur génération PDF.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}



// ══ FILTRES ══
function filterData() {
    const cv = document.getElementById('filterClient').value.toLowerCase();
    const mv = document.getElementById('filterMarche').value.toLowerCase();
    const rv = document.getElementById('filterRegion').value.toLowerCase();
    document.querySelectorAll('#commandes-table tbody tr').forEach(row => {
        const ok = (!cv || row.dataset.client.includes(cv))
                && (!mv || row.dataset.marche === mv)
                && (!rv || row.dataset.region === rv);
        row.style.display = ok ? '' : 'none';
    });
}

// ══ SUPPRESSION ══
async function supprimerLigne(id) {
    if (!confirm('Supprimer la commande #' + id + ' ?')) return;
    try {
        const fd = new URLSearchParams({ id, action: 'supprimer_tout' });
        const res = await fetch('delete_cni.php', { method: 'POST', body: fd });
        const r = await res.json();
        if (r.success) {
            // Journal d'activité — on enregistre qui a supprimé quoi
            fetch('admin_commandes.php?api=log_action', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, details: `Commande #${id} supprimée` })
            })
            .then(r => r.json())
            .then(j => { if (j.ok === false) console.warn('Journal d\'activité :', j.msg); })
            .catch(() => console.warn('Journal d\'activité : requête réseau échouée'));

            const row = document.querySelector(`tr[data-id='${id}']`);
            row.style.opacity = '0';
            setTimeout(() => { row.remove(); }, 500);
        } else {
            alert('Erreur : ' + r.message);
        }
    } catch(e) { console.error(e); }
}
</script>
</body>
</html>