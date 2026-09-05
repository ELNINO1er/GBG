<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/auth.php';

gbg_session();
$role = (($_GET['role'] ?? $_POST['role'] ?? '') === 'admin') ? 'admin' : 'cooperative';
if ($role === 'admin' && admin_current()) {
    redirect('admin/index.php');
}
if ($role === 'cooperative' && coop_current()) {
    redirect('espace/index.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $ok = $role === 'admin'
        ? admin_login($username, $password)
        : coop_login($username, $password);
    if ($ok) {
        // Un navigateur ne conserve qu'un espace actif a la fois.
        unset($_SESSION[$role === 'admin' ? 'coop' : 'admin']);
        redirect($role === 'admin' ? 'admin/index.php' : 'espace/index.php');
    }
    $error = 'Identifiant ou mot de passe incorrect pour cet espace.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Connexion aux espaces - GBG</title>
<link rel="shortcut icon" href="assets/img/logo/favcion.png">
<style>
:root{--green:#0d3b29;--green2:#176344;--gold:#d1aa45;--ink:#17231d;--muted:#66756d;--line:#dce5df;--bg:#edf3ef}
*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:"Segoe UI",system-ui,Arial,sans-serif;color:var(--ink);background:radial-gradient(circle at 12% 10%,#245c45 0,transparent 34%),linear-gradient(135deg,#0a2d20,#114b34);display:grid;place-items:center;padding:28px}
.shell{width:min(980px,100%);display:grid;grid-template-columns:1.05fr .95fr;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 28px 80px rgba(0,0,0,.28)}
.intro{padding:58px 52px;background:linear-gradient(150deg,#0d3b29,#176344);color:#fff;position:relative}.intro:after{content:"";position:absolute;width:220px;height:220px;border:1px solid rgba(255,255,255,.12);border-radius:50%;right:-70px;bottom:-80px}
.logo{width:210px;height:auto;display:block;margin-bottom:52px}.eyebrow{color:#ead18b;font-size:12px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase}.intro h1{font-size:34px;line-height:1.15;margin:12px 0 16px}.intro p{color:#d9e7df;line-height:1.7;margin:0}.steps{display:grid;gap:14px;margin-top:34px}.step{display:flex;gap:12px;align-items:flex-start;font-size:14px;color:#eaf2ed}.step b{display:grid;place-items:center;flex:0 0 28px;height:28px;border-radius:50%;background:rgba(209,170,69,.2);color:#f3d986}
.panel{padding:54px 48px}.panel h2{font-size:25px;margin:0 0 7px}.subtitle{color:var(--muted);font-size:14px;margin:0 0 26px}.roles{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px}.role{border:1px solid var(--line);border-radius:12px;padding:13px 12px;text-decoration:none;color:var(--muted);font-weight:700;text-align:center;font-size:14px}.role.active{background:#eaf4ee;color:var(--green);border-color:#9cc5ae;box-shadow:inset 0 0 0 2px #79ad8f}.selected-role{margin:0 0 18px;padding:10px 12px;border-radius:9px;background:#f2f6f3;color:var(--green);font-size:13px;text-align:center}.selected-role strong{font-weight:800}
label{display:block;font-size:13px;font-weight:700;margin:15px 0 7px}input{width:100%;padding:13px 14px;border:1px solid #cbd8d0;border-radius:10px;font-size:15px}input:focus{outline:none;border-color:var(--green2);box-shadow:0 0 0 4px rgba(23,99,68,.11)}button{width:100%;margin-top:22px;border:0;border-radius:10px;padding:14px;background:var(--green);color:#fff;font-weight:800;font-size:15px;cursor:pointer}button:hover{background:var(--green2)}.err,.notice{padding:11px 13px;border-radius:9px;font-size:13px;margin-bottom:15px}.err{background:#fbe9e7;color:#8c2820;border:1px solid #efc1bd}.notice{background:#e7f5eb;color:#236438;border:1px solid #badfc4}.back{display:block;text-align:center;margin-top:18px;color:var(--muted);font-size:13px;text-decoration:none}.hint{margin-top:18px;padding-top:18px;border-top:1px solid var(--line);color:var(--muted);font-size:12px;line-height:1.55}
@media(max-width:760px){body{display:block;padding:12px;min-height:100dvh}.shell{grid-template-columns:1fr;border-radius:18px;margin:0 auto}.intro{padding:24px}.intro h1{font-size:23px;margin-bottom:10px}.intro p{font-size:13px;line-height:1.5}.logo{margin-bottom:18px;width:155px}.steps{display:none}.panel{padding:26px 20px}.panel h2{font-size:23px}.roles{grid-template-columns:1fr}.role{padding:14px 12px;font-size:15px}input{font-size:16px}button{min-height:48px}}
</style>
</head>
<body>
<main class="shell">
  <section class="intro">
    <img class="logo" src="assets/img/logo/logo-light.svg" alt="Global Business Group">
    <div class="eyebrow">Plateforme coopératives</div>
    <h1>Une information claire, au même endroit.</h1>
    <p>GBG publie ses communiqués et campagnes. Les coopératives les consultent depuis leur espace sécurisé et peuvent aussi les recevoir par e-mail.</p>
    <div class="steps">
      <div class="step"><b>1</b><span>L'administration publie une information.</span></div>
      <div class="step"><b>2</b><span>Les destinataires sont ciblés par région ou sélectionnés globalement.</span></div>
      <div class="step"><b>3</b><span>Chaque coopérative retrouve ses bulletins dans son espace.</span></div>
    </div>
  </section>
  <section class="panel">
    <h2>Bienvenue</h2>
    <p class="subtitle">Choisissez votre type de compte pour continuer.</p>
    <?php if (isset($_GET['deconnecte'])): ?><div class="notice">Vous êtes bien déconnecté.</div><?php endif; ?>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <div class="roles">
      <a class="role <?= $role === 'cooperative' ? 'active' : '' ?>" href="connexion.php?role=cooperative">Coopérative</a>
      <a class="role <?= $role === 'admin' ? 'active' : '' ?>" href="connexion.php?role=admin">Administration</a>
    </div>
    <p class="selected-role">Vous vous connectez comme <strong><?= $role === 'admin' ? 'administrateur GBG' : 'coopérative' ?></strong>.</p>
    <form method="post" action="connexion.php">
      <?= csrf_field() ?>
      <input type="hidden" name="role" value="<?= e($role) ?>">
      <label for="username">Identifiant</label>
      <input id="username" name="username" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" autofocus required>
      <label for="password">Mot de passe</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button type="submit">Se connecter à mon espace</button>
    </form>
    <div class="hint"><?= $role === 'admin' ? 'Accès réservé à l’équipe GBG chargée de gérer les coopératives et les publications.' : 'Votre identifiant est fourni par GBG lors de la création de votre accès coopérative.' ?></div>
    <a class="back" href="index.html">← Retour au site GBG</a>
  </section>
</main>
</body>
</html>
