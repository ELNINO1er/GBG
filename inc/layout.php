<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Gabarit HTML partage du back-office GBG.
 * admin_header($titre, $active) / admin_footer().
 */

function admin_header(string $titre, string $active = ''): void
{
    $admin = $_SESSION['admin'] ?? ['nom' => ''];
    $nav = [
        'index.php'        => 'Tableau de bord',
        'cooperatives.php' => 'Cooperatives',
        'campagnes.php'    => 'Campagnes',
    ];
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titre) ?> - GBG Admin</title>
<link rel="shortcut icon" href="../assets/img/logo/favcion.png">
<style>
:root{--vert:#143c28;--vert2:#1f5c3d;--or:#c8a24b;--bg:#f4f6f4;--txt:#1c2a22;--muted:#6b7a70;--line:#e2e8e3;--danger:#b3261e;--ok:#1f7a3d;}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,Arial,sans-serif;background:var(--bg);color:var(--txt)}
a{color:var(--vert2)}
.topbar{background:var(--vert);color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:60px}
.topbar .brand{font-weight:700;letter-spacing:.5px}
.topbar .brand span{color:var(--or)}
.topbar .brand-logo{display:flex;align-items:center;color:#fff;font-weight:700;text-decoration:none}
.topbar .brand-logo img{height:34px;width:auto;display:block}
.topbar nav{display:flex;gap:4px;flex:1;margin:0 32px}
.topbar nav a{color:#dfe7e1;text-decoration:none;padding:8px 14px;border-radius:6px;font-size:14px}
.topbar nav a:hover{background:rgba(255,255,255,.1)}
.topbar nav a.active{background:var(--vert2);color:#fff}
.topbar .user{font-size:13px;color:#cdd8d1}
.topbar .user a{color:var(--or);margin-left:12px;text-decoration:none}
.wrap{max-width:1120px;margin:28px auto;padding:0 20px}
h1{font-size:22px;margin:0 0 4px}
.sub{color:var(--muted);font-size:14px;margin:0 0 22px}
.card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:22px;margin-bottom:20px;box-shadow:0 1px 2px rgba(0,0,0,.03)}
.card h2{font-size:16px;margin:0 0 14px}
.grid{display:grid;gap:16px}
.grid.cols-4{grid-template-columns:repeat(4,1fr)}
.grid.cols-3{grid-template-columns:repeat(3,1fr)}
.grid.cols-2{grid-template-columns:repeat(2,1fr)}
@media(max-width:820px){.grid.cols-4,.grid.cols-3,.grid.cols-2{grid-template-columns:1fr 1fr}}
.stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:18px}
.stat .n{font-size:28px;font-weight:700;color:var(--vert)}
.stat .l{font-size:13px;color:var(--muted);margin-top:2px}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--line);vertical-align:top}
th{background:#fafbfa;color:var(--muted);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.4px}
tr:hover td{background:#fbfcfb}
.badge{display:inline-block;font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600}
.badge.ok{background:#e4f4ea;color:var(--ok)}
.badge.no{background:#fbe7e6;color:var(--danger)}
.badge.grey{background:#eceff0;color:var(--muted)}
.btn{display:inline-block;background:var(--vert);color:#fff;border:none;border-radius:7px;padding:10px 18px;font-size:14px;cursor:pointer;text-decoration:none;font-weight:600}
.btn:hover{background:var(--vert2)}
.btn.sec{background:#fff;color:var(--vert);border:1px solid var(--vert)}
.btn.sec:hover{background:#f0f5f2}
.btn.sm{padding:6px 12px;font-size:13px}
.btn.danger{background:var(--danger)}
input,select,textarea{width:100%;padding:10px 12px;border:1px solid #cfd8d1;border-radius:7px;font-size:14px;font-family:inherit;background:#fff}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--vert2);box-shadow:0 0 0 3px rgba(31,92,61,.12)}
label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px;color:#33453b}
.field{margin-bottom:6px}
.row{display:flex;gap:16px;flex-wrap:wrap}
.row>div{flex:1;min-width:180px}
.flash{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px}
.flash-success{background:#e4f4ea;color:#0f5a29;border:1px solid #b6e0c4}
.flash-error{background:#fbe7e6;color:#8a1e18;border:1px solid #f0c2bf}
.flash-info{background:#eaf1fb;color:#1c4785;border:1px solid #c4d6f0}
.toolbar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.toolbar .spacer{flex:1}
.muted{color:var(--muted);font-size:13px}
.tablewrap{overflow-x:auto}
.pill-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}
.pill-filter a{font-size:13px;padding:5px 12px;border:1px solid var(--line);border-radius:20px;text-decoration:none;color:var(--muted);background:#fff}
.pill-filter a.on{background:var(--vert);color:#fff;border-color:var(--vert)}
</style>
</head>
<body>
<div class="topbar">
  <a href="index.php" class="brand-logo"><img src="../assets/img/logo/logo-light.png" alt="GBG" onerror="this.style.display='none';this.insertAdjacentHTML('afterend','GLOBAL BUSINESS GROUP')"></a>
  <nav>
    <?php foreach ($nav as $href => $label): ?>
      <a href="<?= e($href) ?>" class="<?= $active === $href ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="user"><a href="compte.php" style="color:#dfe7e1;text-decoration:none"><?= e($admin['nom']) ?></a><a href="logout.php">Deconnexion</a></div>
</div>
<div class="wrap">
<?= flash_render() ?>
<?php
}

function admin_footer(): void
{
    ?>
</div>
</body>
</html>
<?php
}
