<?php
session_start();
$allowed_roles = ['admin'];
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$error = ""; $success = "";
$current_page = basename($_SERVER['PHP_SELF']);

$items_per_page = 8;
$page   = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $items_per_page;

$total_items = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);
if ($page > $total_pages && $total_pages > 0) { $page = $total_pages; $offset = ($page-1)*$items_per_page; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $titre    = htmlspecialchars(trim($_POST['titre'] ?? ''));
    $alt_text = htmlspecialchars(trim($_POST['alt_text'] ?? ''));
    $file_tmp = $_FILES['photo']['tmp_name'];
    $ext      = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
        $new_name = uniqid('gala_', true) . '.' . $ext;
        $target   = "../assets/img/gallery/" . $new_name;
        if (!is_dir('../assets/img/gallery/')) mkdir('../assets/img/gallery/', 0775, true);
        if (move_uploaded_file($file_tmp, $target)) {
            $stmt = $pdo->prepare("INSERT INTO gallery (titre, image_url) VALUES (?, ?)");
            $stmt->execute([$titre ?: $alt_text ?: $new_name, $new_name]);
            $success = "Média publié avec succès dans la galerie.";
            $total_items = $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
            $total_pages = ceil($total_items / $items_per_page);
        } else { $error = "Échec du transfert sur le serveur."; }
    } else { $error = "Format non accepté — utilisez JPG, PNG ou WEBP."; }
}

$stmt = $pdo->prepare("SELECT * FROM gallery ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,        PDO::PARAM_INT);
$stmt->execute();
$photos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Galerie Médias — Gala Admin</title>
<script src="../assets/tailwind.js"></script>
<script>tailwind.config={theme:{extend:{colors:{galaGreen:'#16a34a',galaDark:'#0f172a'}}}}</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ══ MOBILE TOPBAR ══ */
.mob-topbar {
    display: none;
    position: sticky; top: 0; z-index: 990;
    align-items: center; justify-content: space-between;
    padding: 0 16px; height: 58px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
}
@media (max-width: 768px) { .mob-topbar { display: flex; } }

/* ══ UPLOAD CARD ══ */
.upload-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 28px;
    margin-bottom: 32px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.upload-card-title {
    font-size: .7rem; font-weight: 800; letter-spacing: .14em;
    text-transform: uppercase; color: #94a3b8;
    margin-bottom: 22px;
    display: flex; align-items: center; gap: 8px;
}
.upload-card-title::before {
    content: '';
    display: inline-block; width: 3px; height: 14px;
    border-radius: 2px;
    background: linear-gradient(180deg,#16a34a,#22c55e);
}

/* ── Form grid ── */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}
@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
}

/* ── Field ── */
.field { display: flex; flex-direction: column; gap: 6px; }
.field-label {
    font-size: .68rem; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: #64748b;
    display: flex; align-items: center; gap: 6px;
}
.field-label i { font-size: .65rem; color: #94a3b8; }
.field-required { color: #ef4444; font-size: .7rem; }

.field-input {
    width: 100%; padding: 11px 14px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: .875rem; font-weight: 500; color: #0f172a;
    outline: none;
    transition: border-color .18s, background .18s, box-shadow .18s;
}
.field-input:focus {
    border-color: #16a34a;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(22,163,74,.1);
}
.field-input::placeholder { color: #94a3b8; font-weight: 400; }

/* ── Dropzone ── */
.dropzone {
    position: relative;
    border: 1.5px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    min-height: 46px;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    padding: 10px 14px;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    overflow: hidden;
}
.dropzone:hover, .dropzone.over {
    border-color: #16a34a;
    background: #f0fdf4;
}
.dropzone input[type=file] {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    opacity: 0; cursor: pointer; z-index: 2;
}
.dropzone-icon {
    width: 28px; height: 28px; border-radius: 8px;
    background: #dcfce7; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.dropzone-icon i { color: #16a34a; font-size: 12px; }
.dropzone-text { font-size: .8rem; font-weight: 600; color: #475569; }
.dropzone-sub  { font-size: .65rem; color: #94a3b8; font-weight: 400; }
.dropzone-preview {
    display: none;
    width: 36px; height: 36px; border-radius: 8px;
    object-fit: cover; border: 2px solid #bbf7d0;
    flex-shrink: 0;
}

/* ── Submit btn ── */
.btn-publish {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px 28px;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff; font-weight: 700; font-size: .875rem;
    border-radius: 12px; border: none; cursor: pointer;
    box-shadow: 0 4px 14px rgba(22,163,74,.3);
    transition: transform .15s, box-shadow .2s, background .2s;
    white-space: nowrap;
}
.btn-publish:hover  { background: linear-gradient(135deg,#15803d,#0f172a); box-shadow: 0 6px 20px rgba(22,163,74,.35); }
.btn-publish:active { transform: scale(.95); }
@media (max-width: 768px) { .btn-publish { width: 100%; } }

/* ── Alertes ── */
.alert {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; border-radius: 14px;
    margin-bottom: 24px; font-weight: 600; font-size: .875rem;
}
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
.alert-error   { background: #fff1f2; border: 1px solid #fecaca; color: #dc2626; }
.alert i       { font-size: 1rem; flex-shrink: 0; }

/* ══ GALLERY GRID ══ */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 32px;
}
@media (max-width: 1024px) { .gallery-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px)  { .gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
@media (max-width: 360px)  { .gallery-grid { grid-template-columns: 1fr; } }

/* ── Gallery card ── */
.gal-card {
    position: relative;
    border-radius: 18px; overflow: hidden;
    background: #f1f5f9;
    aspect-ratio: 4/3;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    border: 1px solid #e2e8f0;
    transition: box-shadow .25s, transform .25s;
}
.gal-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.14); transform: translateY(-2px); }
.gal-card img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    max-width: 100%; max-height: 100%;
    object-fit: contain; object-position: center;
    display: block;
    transition: transform .5s ease;
}
.gal-card:hover img { transform: scale(1.04); }

.gal-overlay {
    position: absolute; inset: 0; z-index: 2;
    background: linear-gradient(to top, rgba(15,23,42,.92) 0%, rgba(15,23,42,.3) 60%, transparent 100%);
    display: flex; flex-direction: column; justify-content: flex-end;
    padding: 14px;
    /* Desktop: visible au hover seulement */
    opacity: 0; transition: opacity .25s;
}
.gal-card:hover .gal-overlay { opacity: 1; }
/* Mobile: toujours visible */
@media (max-width: 768px) { .gal-overlay { opacity: 1 !important; } }

.gal-title {
    font-size: .72rem; font-weight: 700; color: #fff;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-bottom: 8px;
}
.gal-delete {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 7px; border-radius: 10px;
    background: rgba(239,68,68,.85); color: #fff;
    font-size: .65rem; font-weight: 700; text-transform: uppercase;
    text-decoration: none; letter-spacing: .06em;
    transition: background .18s;
}
.gal-delete:hover { background: #ef4444; }

/* Placeholder vide */
.gal-empty {
    grid-column: 1 / -1;
    padding: 60px 24px; text-align: center;
    background: #fff; border-radius: 20px;
    border: 1.5px dashed #e2e8f0;
    color: #94a3b8; font-weight: 600;
}
.gal-empty i { font-size: 2.5rem; display: block; margin-bottom: 12px; color: #e2e8f0; }

/* ══ PAGINATION ══ */
.pagination-wrap {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    background: #fff; border-radius: 16px; padding: 16px 20px;
    border: 1px solid #e2e8f0; margin-bottom: 16px;
}
.pagination-info { font-size: .72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .1em; }
.pagination-btns { display: flex; gap: 4px; flex-wrap: wrap; }
.pg-btn {
    min-width: 34px; height: 34px; padding: 0 10px;
    border-radius: 10px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #475569;
    font-size: .75rem; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    text-decoration: none; transition: background .15s, border-color .15s;
}
.pg-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
.pg-btn.active { background: #16a34a; border-color: #16a34a; color: #fff; }
.pg-btn.disabled { opacity: .35; pointer-events: none; }

/* ══ SECTION HEADER ══ */
.section-head {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
}
.section-head-title { font-size: 1.5rem; font-weight: 900; color: #0f172a; letter-spacing: -.02em; }
.section-head-sub   { font-size: .8rem; color: #64748b; margin-top: 2px; }
.media-count {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 10px;
    background: rgba(22,163,74,.08); border: 1px solid rgba(22,163,74,.15);
    font-size: .7rem; font-weight: 800; color: #16a34a; text-transform: uppercase; letter-spacing: .1em;
}
</style>
</head>
<body class="bg-slate-50 font-sans">
<?php include 'sidebar_nav.php'; ?>

<!-- ══ MOBILE TOPBAR ══ -->
<div class="mob-topbar no-print">
    <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#16a34a,#22c55e);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:13px;">G</div>
        <span style="font-weight:900;color:#16a34a;font-size:.95rem;">Gala <span style="color:#0f172a;">Médias</span></span>
    </div>
    <button id="admin-menu-btn" aria-label="Menu" aria-expanded="false">
        <span class="abar"></span>
        <span class="abar"></span>
        <span class="abar"></span>
    </button>
</div>

<div class="main">
<main style="padding: 24px 24px 48px; max-width: 1400px; margin: 0 auto;">

    <!-- Section header -->
    <div class="section-head">
        <div>
            <div class="section-head-title">Galerie photo</div>
            <div class="section-head-sub">Gérez les médias affichés dans le carrousel de la page d'accueil.</div>
        </div>
        <span class="media-count">
            <i class="fas fa-images" style="font-size:.65rem;"></i>
            <?= $total_items ?> Média<?= $total_items > 1 ? 's' : '' ?>
        </span>
    </div>

    <!-- Alertes -->
    <?php if (!empty($success)): ?>
    <div class="alert alert-success auto-dismiss">
        <i class="fas fa-check-circle"></i>
        <?= $success ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="alert alert-error auto-dismiss">
        <i class="fas fa-exclamation-circle"></i>
        <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- ══ FORMULAIRE UPLOAD ══ -->
    <div class="upload-card">
        <div class="upload-card-title">
            <i class="fas fa-cloud-upload-alt" style="font-size:.75rem;color:#16a34a;margin-left:4px;"></i>
            Publier un nouveau média
        </div>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-grid">

                <!-- Titre -->
                <div class="field">
                    <label class="field-label" for="f-titre">
                        <i class="fas fa-tag"></i>
                        Titre / Légende
                        <span class="field-required">*</span>
                    </label>
                    <input type="text" name="titre" id="f-titre"
                           class="field-input"
                           placeholder="Ex : Notre équipe de production 2026"
                           maxlength="120"
                           required>
                    <span style="font-size:.62rem;color:#94a3b8;margin-top:2px;">Affiché sous le média dans le carrousel</span>
                </div>

                <!-- Fichier -->
                <div class="field">
                    <label class="field-label">
                        <i class="fas fa-image"></i>
                        Fichier image
                        <span class="field-required">*</span>
                    </label>
                    <div class="dropzone" id="dropzone">
                        <input type="file" name="photo" id="photoInput"
                               accept=".jpg,.jpeg,.png,.webp"
                               required
                               onchange="handleFile(this)">
                        <img id="preview" class="dropzone-preview" alt="Aperçu">
                        <div class="dropzone-icon" id="dz-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div>
                            <div class="dropzone-text" id="dz-text">Cliquez ou déposez une image</div>
                            <div class="dropzone-sub" id="dz-sub">JPG · PNG · WEBP — max 10 Mo</div>
                        </div>
                    </div>
                </div>

                <!-- Bouton -->
                <div class="field">
                    <label class="field-label" style="visibility:hidden;">Action</label>
                    <button type="submit" class="btn-publish" id="publishBtn">
                        <i class="fas fa-paper-plane" style="font-size:.75rem;"></i>
                        Publier
                    </button>
                </div>

            </div><!-- /.form-grid -->

            <!-- Barre de progression (cachée par défaut) -->
            <div id="progressWrap" style="display:none;margin-top:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <span style="font-size:.7rem;font-weight:700;color:#475569;">Téléversement en cours…</span>
                    <span id="progressPct" style="font-size:.7rem;font-weight:800;color:#16a34a;">0%</span>
                </div>
                <div style="height:6px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                    <div id="progressBar"
                         style="height:100%;width:0%;background:linear-gradient(90deg,#16a34a,#22c55e);border-radius:4px;transition:width .2s;"></div>
                </div>
            </div>

        </form>
    </div><!-- /.upload-card -->

    <!-- ══ GRILLE PHOTOS ══ -->
    <div class="gallery-grid">
        <?php if (empty($photos)): ?>
        <div class="gal-empty">
            <i class="fas fa-images"></i>
            Aucun média dans la galerie pour l'instant.
        </div>
        <?php endif; ?>

        <?php foreach ($photos as $p): ?>
        <div class="gal-card">
            <img src="../assets/img/gallery/<?= htmlspecialchars($p['image_url']) ?>"
                 alt="<?= htmlspecialchars($p['titre']) ?>"
                 loading="lazy">
            <div class="gal-overlay">
                <div class="gal-title"><?= htmlspecialchars($p['titre']) ?></div>
                <a href="delete_photo.php?id=<?= $p['id'] ?>"
                   onclick="return confirm('Supprimer ce média de façon permanente ?')"
                   class="gal-delete">
                    <i class="fas fa-trash-alt" style="font-size:.6rem;"></i> Supprimer
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ══ PAGINATION ══ -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-wrap">
        <div class="pagination-info">Page <?= $page ?> / <?= $total_pages ?></div>
        <div class="pagination-btns">
            <a href="?p=<?= $page-1 ?>" class="pg-btn <?= $page<=1 ? 'disabled' : '' ?>">
                <i class="fas fa-chevron-left" style="font-size:.6rem;"></i>
            </a>
            <?php for ($i=1; $i<=$total_pages; $i++): ?>
            <a href="?p=<?= $i ?>" class="pg-btn <?= $i===$page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?p=<?= $page+1 ?>" class="pg-btn <?= $page>=$total_pages ? 'disabled' : '' ?>">
                <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>

</main>
</div><!-- /.main -->

<script>
/* ══ Dropzone preview ══ */
function handleFile(input) {
    const file = input.files[0];
    if (!file) return;
    const preview = document.getElementById('preview');
    const icon    = document.getElementById('dz-icon');
    const text    = document.getElementById('dz-text');
    const sub     = document.getElementById('dz-sub');
    const reader  = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
        icon.style.display    = 'none';
        text.textContent      = file.name.length > 28 ? file.name.slice(0,26)+'…' : file.name;
        sub.textContent       = (file.size/1024/1024).toFixed(2) + ' Mo';
        document.getElementById('dropzone').style.borderColor = '#16a34a';
        document.getElementById('dropzone').style.background  = '#f0fdf4';
    };
    reader.readAsDataURL(file);
}

/* ══ Drag & drop ══ */
const dz = document.getElementById('dropzone');
['dragenter','dragover'].forEach(e => dz.addEventListener(e, ev => { ev.preventDefault(); dz.classList.add('over'); }));
['dragleave','drop'].forEach(e => dz.addEventListener(e, ev => { ev.preventDefault(); dz.classList.remove('over'); }));
dz.addEventListener('drop', ev => {
    const files = ev.dataTransfer.files;
    if (files.length) {
        document.getElementById('photoInput').files = files;
        handleFile(document.getElementById('photoInput'));
    }
});

/* ══ Progression XHR simulée ══ */
document.getElementById('uploadForm').addEventListener('submit', function() {
    const wrap = document.getElementById('progressWrap');
    const bar  = document.getElementById('progressBar');
    const pct  = document.getElementById('progressPct');
    const btn  = document.getElementById('publishBtn');
    wrap.style.display = 'block';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.75rem;"></i> Publication…';
    let p = 0;
    const iv = setInterval(() => {
        p = Math.min(p + Math.random() * 18, 90);
        bar.style.width = p + '%';
        pct.textContent = Math.round(p) + '%';
    }, 200);
    setTimeout(() => { clearInterval(iv); bar.style.width='100%'; pct.textContent='100%'; }, 1800);
});

/* ══ Auto-dismiss alertes ══ */
document.querySelectorAll('.auto-dismiss').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .5s, transform .5s';
        el.style.opacity = '0'; el.style.transform = 'translateY(-8px)';
        setTimeout(() => el.remove(), 500);
    }, 4000);
});
</script>
</body>
</html>