<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

$coop = coop_require();
$db = gbg_db();

// Bulletins publies concernant cette cooperative (sa region ou toutes regions)
$stmt = $db->prepare(
    'SELECT id, sujet, contenu, sent_at, created_at, filtre_region
     FROM campagnes
     WHERE publiee = 1 AND statut = \'envoyee\'
       AND (filtre_region = \'\' OR filtre_region = ?)
     ORDER BY COALESCE(sent_at, created_at) DESC'
);
$stmt->execute([$coop['region']]);
$bulletins = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Mes bulletins - Espace cooperatives GBG</title>
<link rel="shortcut icon" href="../assets/img/logo/favcion.png">
<style>
:root{--vert:#143c28;--vert2:#1f5c3d;--or:#c8a24b;--bg:#f4f6f4;--txt:#1c2a22;--muted:#6b7a70;--line:#e2e8e3;}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,Arial,sans-serif;background:var(--bg);color:var(--txt)}
.topbar{background:linear-gradient(110deg,#0a3021,var(--vert));color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 28px;height:72px;box-shadow:0 5px 18px rgba(13,59,41,.16)}
.topbar .brand img{width:176px;height:44px;object-fit:contain;display:block}
.topbar .who{font-size:13px;color:#dce8e0;display:flex;align-items:center;gap:14px}.topbar .who a{color:#fff;background:#a64035;padding:9px 13px;border-radius:9px;text-decoration:none;font-weight:700}
.wrap{max-width:820px;margin:28px auto;padding:0 20px}
h1{font-size:22px;margin:0 0 4px}
.sub{color:var(--muted);font-size:14px;margin:0 0 24px}
.bulletin{background:#fff;border:1px solid var(--line);border-radius:15px;padding:26px;margin-bottom:18px;box-shadow:0 7px 24px rgba(20,61,43,.055)}
.bulletin h2{font-size:18px;color:var(--vert);margin:0 0 6px}
.bulletin .date{font-size:12px;color:var(--muted);margin-bottom:14px}
.bulletin .body{font-size:15px;line-height:1.65}
.empty{background:#fff;border:1px dashed var(--line);border-radius:10px;padding:40px;text-align:center;color:var(--muted)}
.tag{display:inline-block;font-size:11px;background:#eef3f0;color:var(--vert2);padding:2px 10px;border-radius:20px;margin-left:8px}
@media(max-width:640px){.topbar{height:auto;min-height:68px;padding:12px 16px}.topbar .brand img{width:145px}.topbar .who{font-size:0}.wrap{margin-top:22px}}
</style>
</head>
<body>
<div class="topbar">
  <a class="brand" href="index.php"><img src="../assets/img/logo/logo-light.svg" alt="Global Business Group"></a>
  <div class="who"><?= e($coop['nom']) ?><a href="logout.php">Déconnexion</a></div>
</div>
<div class="wrap">
  <h1>Bonjour, <?= e($coop['nom']) ?></h1>
  <p class="sub">Retrouvez ici les bulletins et informations transmis par GBG<?= $coop['region'] !== '' ? ' (region ' . e($coop['region']) . ')' : '' ?>.</p>

  <?php if (!$bulletins): ?>
    <div class="empty">Aucun bulletin publie pour le moment.<br>Vous serez informe(e) des nouvelles publications.</div>
  <?php else: ?>
    <?php foreach ($bulletins as $b): ?>
      <article class="bulletin">
        <h2><?= e($b['sujet']) ?>
          <?php if ($b['filtre_region'] === ''): ?><span class="tag">Toutes cooperatives</span>
          <?php else: ?><span class="tag"><?= e($b['filtre_region']) ?></span><?php endif; ?>
        </h2>
        <div class="date"><?= e(date('d/m/Y', strtotime($b['sent_at'] ?: $b['created_at']))) ?></div>
        <div class="body"><?= $b['contenu'] /* HTML valide par l'admin */ ?></div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</body>
</html>
