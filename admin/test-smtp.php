<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/mailer.php';
require_once __DIR__ . '/../inc/campaign.php';

admin_require();
$config = gbg_config();

$resultat = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $dest = trim((string)($_POST['destinataire'] ?? ''));
    if (trim((string)$config['smtp_password']) === '') {
        $resultat = ['ok' => false, 'msg' => 'Le mot de passe SMTP de la boite infos@gbg-ci.com doit etre ajoute dans inc/config.local.php avant ce test.'];
    } elseif (!filter_var($dest, FILTER_VALIDATE_EMAIL)) {
        flash('Adresse email de test invalide.', 'error');
    } else {
        $html = gbg_email_template(
            'Test de configuration email',
            '<p>Ceci est un email de test envoye depuis le back-office GBG.</p>'
            . '<p>Si vous recevez ce message, la configuration SMTP fonctionne correctement.</p>'
        );
        try {
            gbg_send_mail($config, $dest, 'Test SMTP - GBG', $html);
            $resultat = ['ok' => true, 'msg' => "Email de test envoye a $dest. Verifiez la boite de reception (et les spams)."];
        } catch (Throwable $e) {
            $resultat = ['ok' => false, 'msg' => 'Echec : ' . $e->getMessage()];
        }
    }
}

admin_header('Test SMTP', '');
?>
<h1>Test de la configuration email</h1>
<p class="sub">Verifiez que l'envoi SMTP fonctionne avant de lancer une vraie campagne.</p>

<div class="card" style="max-width:600px">
  <h2>Parametres actuels</h2>
  <table>
    <tr><th>Hote SMTP</th><td><?= e((string)$config['smtp_host']) ?>:<?= (int)$config['smtp_port'] ?> (<?= e((string)$config['smtp_encryption']) ?>)</td></tr>
    <tr><th>Utilisateur</th><td><?= e((string)$config['smtp_username']) ?></td></tr>
    <tr><th>Expediteur</th><td><?= e((string)$config['from_name']) ?> &lt;<?= e((string)$config['from_email']) ?>&gt;</td></tr>
    <tr><th>Mot de passe</th><td><?= $config['smtp_password'] !== '' ? '<span class="badge ok">defini</span>' : '<span class="badge no">non defini</span>' ?></td></tr>
  </table>
  <p class="muted" style="margin-top:10px">Ces valeurs proviennent de <code>inc/config.local.php</code>.</p>
  <?php if ($config['smtp_password'] === ''): ?>
    <div class="flash flash-info">Vous pouvez deja publier dans l'espace des cooperatives. Seul l'envoi par email attend la configuration du mot de passe SMTP.</div>
  <?php endif; ?>
</div>

<div class="card" style="max-width:600px">
  <h2>Envoyer un email de test</h2>
  <?php if ($resultat): ?>
    <div class="flash <?= $resultat['ok'] ? 'flash-success' : 'flash-error' ?>"><?= e($resultat['msg']) ?></div>
  <?php endif; ?>
  <form method="post" action="test-smtp.php">
    <?= csrf_field() ?>
    <label>Adresse email de destination</label>
    <input name="destinataire" type="email" placeholder="vous@exemple.com" required>
    <p style="margin-top:18px"><button class="btn" type="submit" <?= $config['smtp_password'] === '' ? 'disabled title="Configurez d’abord le mot de passe SMTP"' : '' ?>>Envoyer le test</button></p>
  </form>
</div>
<?php
admin_footer();
