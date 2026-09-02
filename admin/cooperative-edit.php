<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';

admin_require();
$db = gbg_db();

$id = (int)($_GET['id'] ?? 0);
$coop = null;
if ($id > 0) {
    $stmt = $db->prepare('SELECT * FROM cooperatives WHERE id = ?');
    $stmt->execute([$id]);
    $coop = $stmt->fetch();
    if (!$coop) {
        flash('Cooperative introuvable.', 'error');
        redirect('cooperatives.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && $coop) {
        $db->prepare('DELETE FROM cooperatives WHERE id = ?')->execute([$id]);
        flash('Cooperative supprimee.', 'success');
        redirect('cooperatives.php');
    }

    $nom      = trim((string)$_POST['nom_cooperative']);
    $pca      = trim((string)$_POST['pca_nom']);
    $localite = trim((string)$_POST['localite']);
    $region   = trim((string)$_POST['region']);
    $contactPca = trim((string)$_POST['contact_pca']);
    $drAdg    = trim((string)$_POST['dr_adg']);
    $contact  = trim((string)$_POST['contact']);
    $emailRaw = trim((string)$_POST['email']);
    $actif    = isset($_POST['actif']) ? 1 : 0;

    $parsed = parse_emails($emailRaw);
    $email = $parsed['primary'];
    $extra = implode(';', $parsed['extra']);
    $emailValide = $email !== '' ? 1 : 0;
    $now = date('Y-m-d H:i:s');

    if ($nom === '') {
        flash('Le nom de la cooperative est obligatoire.', 'error');
    } elseif ($coop) {
        $upd = $db->prepare(
            'UPDATE cooperatives SET nom_cooperative=?, pca_nom=?, localite=?, region=?,
                contact_pca=?, dr_adg=?, contact=?, email=?, emails_extra=?, email_valide=?,
                actif=?, updated_at=? WHERE id=?'
        );
        $upd->execute([$nom, $pca, $localite, $region, $contactPca, $drAdg, $contact,
            $email, $extra, $emailValide, $actif, $now, $id]);
        flash('Cooperative mise a jour.', 'success');
        redirect('cooperative-edit.php?id=' . $id);
    } else {
        $ins = $db->prepare(
            'INSERT INTO cooperatives (nom_cooperative, pca_nom, localite, region, contact_pca,
                dr_adg, contact, email, emails_extra, email_valide, actif, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $ins->execute([$nom, $pca, $localite, $region, $contactPca, $drAdg, $contact,
            $email, $extra, $emailValide, $actif, $now]);
        flash('Cooperative ajoutee.', 'success');
        redirect('cooperative-edit.php?id=' . $db->lastInsertId());
    }
    // recharge en cas d'erreur
    $stmt = $db->prepare('SELECT * FROM cooperatives WHERE id = ?');
    $stmt->execute([$id]);
    $coop = $stmt->fetch() ?: null;
}

$v = static fn(string $k) => e($coop[$k] ?? '');
admin_header($coop ? 'Editer cooperative' : 'Nouvelle cooperative', 'cooperatives.php');
?>
<h1><?= $coop ? e($coop['nom_cooperative']) : 'Nouvelle cooperative' ?></h1>
<p class="sub"><a href="cooperatives.php">&larr; Retour a la liste</a></p>

<form method="post" action="cooperative-edit.php<?= $coop ? '?id=' . $id : '' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="card">
    <h2>Informations</h2>
    <div class="row">
      <div><label>Nom de la cooperative *</label><input name="nom_cooperative" value="<?= $v('nom_cooperative') ?>" required></div>
      <div><label>N. ordre</label><input name="n_ordre" value="<?= $v('n_ordre') ?>" disabled></div>
    </div>
    <div class="row">
      <div><label>Nom & prenoms PCA</label><input name="pca_nom" value="<?= $v('pca_nom') ?>"></div>
      <div><label>DR / ADG</label><input name="dr_adg" value="<?= $v('dr_adg') ?>"></div>
    </div>
    <div class="row">
      <div><label>Localite</label><input name="localite" value="<?= $v('localite') ?>"></div>
      <div><label>Region</label><input name="region" value="<?= $v('region') ?>"></div>
    </div>
    <div class="row">
      <div><label>Contact PCA</label><input name="contact_pca" value="<?= $v('contact_pca') ?>"></div>
      <div><label>Contact (DR/ADG)</label><input name="contact" value="<?= $v('contact') ?>"></div>
    </div>
    <label>Email(s) de la cooperative <span class="muted">(plusieurs adresses possibles, separees par ; ou /)</span></label>
    <input name="email" value="<?= e(trim(($coop['email'] ?? '') . ($coop['emails_extra'] ?? '' ? ' ; ' . $coop['emails_extra'] : ''))) ?>" placeholder="exemple@gmail.com">
    <?php if ($coop && !$coop['email_valide']): ?>
      <p class="muted">&#9888; Aucun email valide : cette cooperative est exclue des envois email.</p>
    <?php endif; ?>
    <label style="margin-top:16px"><input type="checkbox" name="actif" value="1" style="width:auto" <?= (!$coop || $coop['actif']) ? 'checked' : '' ?>> Cooperative active</label>
    <p style="margin-top:20px"><button class="btn" type="submit">Enregistrer</button></p>
  </div>
</form>

<?php if ($coop): ?>
  <div class="card">
    <h2>Acces a l'espace cooperative</h2>
    <?php if ($coop['login_username']): ?>
      <p>Identifiant actuel : <strong><?= e($coop['login_username']) ?></strong>
         <span class="badge ok">acces actif</span></p>
      <p class="muted">Regenerer le mot de passe cree un nouveau mot de passe a communiquer a la cooperative.</p>
    <?php else: ?>
      <p class="muted">Aucun acces cree. Generez un identifiant + mot de passe pour permettre a cette cooperative de consulter ses bulletins en ligne.</p>
    <?php endif; ?>
    <form method="post" action="cooperative-access.php" style="margin-top:10px">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <button class="btn sec" type="submit" name="op" value="generate">
        <?= $coop['login_username'] ? 'Regenerer le mot de passe' : 'Creer un acces' ?>
      </button>
      <?php if ($coop['login_username']): ?>
        <button class="btn danger" type="submit" name="op" value="revoke" onclick="return confirm('Revoquer l\'acces de cette cooperative ?')">Revoquer l'acces</button>
      <?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h2>Zone sensible</h2>
    <form method="post" action="cooperative-edit.php?id=<?= $id ?>" onsubmit="return confirm('Supprimer definitivement cette cooperative ?')">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <button class="btn danger" type="submit">Supprimer la cooperative</button>
    </form>
  </div>
<?php endif; ?>
<?php
admin_footer();
