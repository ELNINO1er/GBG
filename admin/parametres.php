<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';

admin_require();
$config = gbg_config();
$saved = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $host = trim((string)($_POST['smtp_host'] ?? ''));
    $port = (int)($_POST['smtp_port'] ?? 587);
    $encryption = (string)($_POST['smtp_encryption'] ?? 'tls');
    $username = trim((string)($_POST['smtp_username'] ?? ''));
    $fromEmail = trim((string)($_POST['from_email'] ?? ''));
    $fromName = trim((string)($_POST['from_name'] ?? 'Global Business Group'));
    $password = (string)($_POST['smtp_password'] ?? '');
    if ($password === '') {
        $password = (string)($config['smtp_password'] ?? '');
    }
    if ($host === '' || $username === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Verifiez l’hote, l’utilisateur et l’adresse d’expedition.';
    } elseif ($port < 1 || $port > 65535 || !in_array($encryption, ['tls', 'ssl'], true)) {
        $error = 'Port ou chiffrement SMTP invalide.';
    } else {
        $values = [
            'smtp_host' => $host, 'smtp_port' => $port, 'smtp_encryption' => $encryption,
            'smtp_username' => $username, 'smtp_password' => $password,
            'from_email' => $fromEmail, 'from_name' => $fromName,
        ];
        $content = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($values, true) . ";\n";
        $path = dirname(__DIR__) . '/inc/smtp.local.php';
        if (file_put_contents($path, $content, LOCK_EX) === false) {
            $error = 'Impossible d’enregistrer la configuration. Verifiez les permissions du dossier inc.';
        } else {
            $saved = true;
            $config = array_merge($config, $values);
        }
    }
}

admin_header('Parametres', 'parametres.php');
?>
<h1>Parametres</h1>
<p class="sub">Configurez l’envoi des campagnes sans modifier les fichiers du serveur.</p>
<?php if ($saved): ?><div class="flash flash-success">Configuration SMTP enregistree. Vous pouvez maintenant envoyer un email de test.</div><?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<div class="grid cols-2">
  <form method="post" class="card">
    <?= csrf_field() ?>
    <h2>Serveur email</h2>
    <div class="row"><div><label>Hote SMTP</label><input name="smtp_host" value="<?= e((string)$config['smtp_host']) ?>" required></div><div style="max-width:140px"><label>Port</label><input name="smtp_port" type="number" value="<?= (int)$config['smtp_port'] ?>" required></div></div>
    <label>Chiffrement</label><select name="smtp_encryption"><option value="tls" <?= $config['smtp_encryption']==='tls'?'selected':'' ?>>TLS (recommande)</option><option value="ssl" <?= $config['smtp_encryption']==='ssl'?'selected':'' ?>>SSL</option></select>
    <label>Adresse de la boite email</label><input name="smtp_username" type="email" value="<?= e((string)$config['smtp_username']) ?>" required>
    <label>Mot de passe de la boite email</label><input name="smtp_password" type="password" placeholder="<?= $config['smtp_password'] !== '' ? 'Laisser vide pour conserver le mot de passe actuel' : 'Saisir le mot de passe de la boite email' ?>" autocomplete="new-password">
    <div class="row"><div><label>Nom d’expediteur</label><input name="from_name" value="<?= e((string)$config['from_name']) ?>" required></div><div><label>Email d’expediteur</label><input name="from_email" type="email" value="<?= e((string)$config['from_email']) ?>" required></div></div>
    <button class="btn" type="submit" style="margin-top:20px">Enregistrer les parametres</button>
  </form>
  <div class="card"><h2>Configuration Hostinger recommandee</h2><table><tr><th>Hote</th><td>smtp.hostinger.com</td></tr><tr><th>Port</th><td>587</td></tr><tr><th>Chiffrement</th><td>TLS</td></tr><tr><th>Utilisateur</th><td>infos@gbg-ci.com</td></tr></table><p class="muted" style="margin-top:16px">Le mot de passe est celui de la boite email, et non celui de MySQL ou du compte administrateur.</p><a class="btn sec" href="test-smtp.php">Tester l’envoi</a></div>
</div>
<?php admin_footer(); ?>
