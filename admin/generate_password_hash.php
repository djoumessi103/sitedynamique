<?php
/* ═══════════════════════════════════════════════════════
   UTILITAIRE TEMPORAIRE — Générateur de hash mot de passe
   ⚠️ À SUPPRIMER DU SERVEUR JUSTE APRÈS USAGE
   ═══════════════════════════════════════════════════════ */
$hash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mdp'])) {
    $hash = password_hash($_POST['mdp'], PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Générateur de hash — Temporaire</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:system-ui,-apple-system,sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
.card{background:#fff;padding:32px;border-radius:18px;box-shadow:0 8px 32px rgba(0,0,0,.08);max-width:460px;width:100%;}
h1{font-size:1.15rem;margin:0 0 18px;color:#0f172a;}
input{width:100%;padding:13px;border:1px solid #e2e8f0;border-radius:10px;font-size:.95rem;margin-bottom:12px;}
button{width:100%;padding:13px;background:#16a34a;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:.92rem;cursor:pointer;}
button:hover{background:#15803d;}
.result{margin-top:18px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-family:monospace;font-size:.76rem;word-break:break-all;color:#15803d;line-height:1.5;}
.warn{margin-top:18px;padding:13px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;color:#b91c1c;font-size:.8rem;font-weight:600;}
</style>
</head>
<body>
<div class="card">
    <h1>🔐 Générateur de hash mot de passe</h1>
    <form method="POST">
        <input type="text" name="mdp" placeholder="Mot de passe en clair" required autofocus>
        <button type="submit">Générer le hash</button>
    </form>
    <?php if ($hash): ?>
    <div class="result"><?= htmlspecialchars($hash) ?></div>
    <?php endif; ?>
    <div class="warn">⚠️ Supprimez ce fichier de votre serveur dès que vous avez copié vos hashs — il ne doit jamais rester en production.</div>
</div>
</body>
</html>
