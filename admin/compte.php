<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';

$admin = admin_require();
$db = gbg_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $actuel = (string)($_POST['actuel'] ?? '');
    $nouveau = (string)($_POST['nouveau'] ?? '');
    $nouveau2 = (string)($_POST['nouveau2'] ?? '');

    $stmt = $db->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
    $stmt->execute([$admin['id']]);
    $hash = (string)$stmt->fetchColumn();

    if (!password_verify($actuel, $hash)) {
        flash('Mot de passe actuel incorrect.', 'error');
    } elseif (strlen($nouveau) < 8) {
        flash('Le nouveau mot de passe doit faire au moins 8 caracteres.', 'error');
    } elseif ($nouveau !== $nouveau2) {
        flash('La confirmation ne correspond pas.', 'error');
    } else {
        $upd = $db->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
        $upd->execute([password_hash($nouveau, PASSWORD_DEFAULT), $admin['id']]);
        flash('Mot de passe mis a jour.', 'success');
    }
    redirect('compte.php');
}

admin_header('Mon compte', '');
?>
<h1>Mon compte</h1>
<p class="sub">Connecte en tant que <strong><?= e($admin['nom']) ?></strong>.</p>

<div class="card" style="max-width:520px">
  <h2>Changer le mot de passe</h2>
  <form method="post" action="compte.php">
    <?= csrf_field() ?>
    <label>Mot de passe actuel</label>
    <input type="password" name="actuel" required autocomplete="current-password">
    <label>Nouveau mot de passe <span class="muted">(8 caracteres min.)</span></label>
    <input type="password" name="nouveau" required autocomplete="new-password">
    <label>Confirmer le nouveau mot de passe</label>
    <input type="password" name="nouveau2" required autocomplete="new-password">
    <p style="margin-top:20px"><button class="btn" type="submit">Mettre a jour</button></p>
  </form>
</div>
<?php
admin_footer();
