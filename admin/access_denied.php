<?php
session_start();
$role = $_SESSION['role'] ?? null;
$roleLabels = [
    'admin'      => 'Administrateur',
    'rh'         => 'Ressources Humaines',
    'commercial' => 'Commercial',
];
$roleLabel = $roleLabels[$role] ?? 'Inconnu';
$isLogged  = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accès refusé — Gala Agro Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:24px;padding:42px 36px;max-width:440px;width:100%;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.08);border:1px solid #f1f5f9;}
.icon{width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,#f59e0b,#ef4444);display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;margin:0 auto 22px;box-shadow:0 8px 24px rgba(239,68,68,.25);}
h1{font-size:1.4rem;font-weight:800;color:#0f172a;margin-bottom:10px;letter-spacing:-.02em;}
p{font-size:.88rem;color:#64748b;line-height:1.6;margin-bottom:4px;}
.role-badge{display:inline-flex;align-items:center;gap:7px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:7px 15px;font-size:.78rem;font-weight:700;color:#334155;margin:18px 0 26px;}
.role-badge i{color:#16a34a;font-size:12px;}
.actions{display:flex;flex-direction:column;gap:10px;}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;border-radius:12px;font-weight:700;font-size:.87rem;text-decoration:none;transition:opacity .15s,transform .15s;}
.btn:active{transform:scale(.97);}
.btn-primary{background:#0f172a;color:#fff;}
.btn-primary:hover{opacity:.88;}
.btn-secondary{background:#fef2f2;color:#dc2626;}
.btn-secondary:hover{background:#fee2e2;}
</style>
</head>
<body>
<div class="card">
    <div class="icon"><i class="fas fa-lock"></i></div>
    <h1>Accès refusé</h1>
    <p>Vous n'avez pas les autorisations nécessaires pour consulter cette page.</p>
    <p>Contactez un administrateur si vous pensez qu'il s'agit d'une erreur.</p>

    <?php if ($isLogged): ?>
    <div class="role-badge"><i class="fas fa-user-shield"></i> Connecté en tant que : <?= htmlspecialchars($roleLabel) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
        <a href="logout.php" class="btn btn-secondary"><i class="fas fa-sign-out-alt"></i> Se déconnecter</a>
    </div>
</div>
</body>
</html>
