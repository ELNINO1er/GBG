<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

redirect('../connexion.php?role=admin');

gbg_session();
if (admin_current()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');
    if (admin_login($u, $p)) {
        redirect('index.php');
    }
    $error = 'Identifiants incorrects.';
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Connexion - GBG Admin</title>
<link rel="shortcut icon" href="../assets/img/logo/favcion.png">
<style>
body{margin:0;font-family:'Segoe UI',system-ui,Arial,sans-serif;background:#143c28;
  display:flex;min-height:100vh;align-items:center;justify-content:center;color:#1c2a22}
.box{background:#fff;border-radius:14px;padding:36px 32px;width:340px;box-shadow:0 18px 50px rgba(0,0,0,.3)}
.box h1{font-size:19px;margin:0 0 4px;color:#143c28}
.box .brand span{color:#c8a24b}
.box p{margin:0 0 22px;color:#6b7a70;font-size:13px}
label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px}
input{width:100%;padding:11px 12px;border:1px solid #cfd8d1;border-radius:8px;font-size:14px;box-sizing:border-box}
input:focus{outline:none;border-color:#1f5c3d;box-shadow:0 0 0 3px rgba(31,92,61,.12)}
button{width:100%;margin-top:22px;background:#143c28;color:#fff;border:none;border-radius:8px;
  padding:12px;font-size:15px;font-weight:600;cursor:pointer}
button:hover{background:#1f5c3d}
.err{background:#fbe7e6;color:#8a1e18;border:1px solid #f0c2bf;padding:10px 12px;
  border-radius:8px;font-size:13px;margin-bottom:8px}
</style>
</head>
<body>
<form class="box" method="post" action="login.php">
  <h1 class="brand">GLOBAL BUSINESS <span>GROUP</span></h1>
  <p>Back-office &middot; Communication cooperatives</p>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <label for="u">Identifiant</label>
  <input id="u" name="username" autocomplete="username" autofocus required>
  <label for="p">Mot de passe</label>
  <input id="p" name="password" type="password" autocomplete="current-password" required>
  <button type="submit">Se connecter</button>
</form>
</body>
</html>
