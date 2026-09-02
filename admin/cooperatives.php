<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';

admin_require();
$db = gbg_db();

$filtre  = $_GET['filtre'] ?? '';
$region  = trim((string)($_GET['region'] ?? ''));
$q       = trim((string)($_GET['q'] ?? ''));

$where = ['1=1'];
$params = [];
if ($filtre === 'sans_email') {
    $where[] = 'email_valide = 0';
} elseif ($filtre === 'avec_email') {
    $where[] = 'email_valide = 1';
} elseif ($filtre === 'avec_acces') {
    $where[] = 'login_username IS NOT NULL';
}
if ($region !== '') {
    $where[] = 'region = ?';
    $params[] = $region;
}
if ($q !== '') {
    $where[] = '(nom_cooperative LIKE ? OR pca_nom LIKE ? OR localite LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql = 'SELECT * FROM cooperatives WHERE ' . implode(' AND ', $where)
     . ' ORDER BY nom_cooperative ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$coops = $stmt->fetchAll();

$regions = $db->query(
    "SELECT region, COUNT(*) n FROM cooperatives WHERE region <> '' GROUP BY region ORDER BY region"
)->fetchAll();

admin_header('Cooperatives', 'cooperatives.php');
?>
<h1>Cooperatives</h1>
<p class="sub"><?= count($coops) ?> cooperative(s) affichee(s).</p>

<div class="toolbar">
  <form method="get" action="cooperatives.php" class="row" style="flex:1;align-items:flex-end">
    <div style="max-width:280px">
      <label>Recherche</label>
      <input name="q" value="<?= e($q) ?>" placeholder="Nom, PCA, localite, email...">
    </div>
    <div style="max-width:220px">
      <label>Region</label>
      <select name="region" onchange="this.form.submit()">
        <option value="">Toutes</option>
        <?php foreach ($regions as $r): ?>
          <option value="<?= e($r['region']) ?>" <?= $region === $r['region'] ? 'selected' : '' ?>>
            <?= e($r['region']) ?> (<?= (int)$r['n'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="max-width:120px"><button class="btn" type="submit">Filtrer</button></div>
  </form>
  <a class="btn sec" href="acces-coop.php">Acces coop</a>
  <a class="btn sec" href="import.php">Importer</a>
  <a class="btn" href="cooperative-edit.php">+ Ajouter</a>
</div>

<div class="pill-filter">
  <a href="cooperatives.php" class="<?= $filtre === '' ? 'on' : '' ?>">Toutes</a>
  <a href="cooperatives.php?filtre=avec_email" class="<?= $filtre === 'avec_email' ? 'on' : '' ?>">Avec email</a>
  <a href="cooperatives.php?filtre=sans_email" class="<?= $filtre === 'sans_email' ? 'on' : '' ?>">Sans email</a>
  <a href="cooperatives.php?filtre=avec_acces" class="<?= $filtre === 'avec_acces' ? 'on' : '' ?>">Acces espace cree</a>
</div>

<div class="card">
<div class="tablewrap">
<table>
  <thead><tr>
    <th>Cooperative</th><th>PCA</th><th>Region</th><th>Email</th><th>Contact</th><th>Espace</th><th></th>
  </tr></thead>
  <tbody>
  <?php foreach ($coops as $c): ?>
    <tr>
      <td>
        <strong><?= e($c['nom_cooperative']) ?></strong>
        <?php if (!$c['actif']): ?><span class="badge grey">inactif</span><?php endif; ?>
        <div class="muted"><?= e($c['localite']) ?></div>
      </td>
      <td><?= e($c['pca_nom']) ?></td>
      <td><?= e($c['region']) ?></td>
      <td>
        <?php if ($c['email_valide']): ?>
          <?= e($c['email']) ?>
          <?php if ($c['emails_extra']): ?><div class="muted">+ <?= e($c['emails_extra']) ?></div><?php endif; ?>
        <?php else: ?>
          <span class="badge no">manquant</span>
        <?php endif; ?>
      </td>
      <td><?= e($c['contact_pca'] ?: $c['contact']) ?></td>
      <td>
        <?php if ($c['login_username']): ?>
          <span class="badge ok">actif</span>
        <?php else: ?>
          <span class="badge grey">non</span>
        <?php endif; ?>
      </td>
      <td><a class="btn sm sec" href="cooperative-edit.php?id=<?= (int)$c['id'] ?>">Editer</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$coops): ?>
    <tr><td colspan="7" class="muted">Aucune cooperative ne correspond.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php
admin_footer();
