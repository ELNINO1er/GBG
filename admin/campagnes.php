<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/campaign.php';

admin_require();
$db = gbg_db();

$camps = $db->query(
    'SELECT * FROM campagnes ORDER BY id DESC'
)->fetchAll();

admin_header('Campagnes', 'campagnes.php');
?>
<h1>Campagnes</h1>
<p class="sub">Messages groupes envoyes par email et/ou publies dans l'espace cooperatives.</p>

<div class="toolbar">
  <div class="spacer"></div>
  <a class="btn" href="campagne-edit.php">+ Nouvelle campagne</a>
</div>

<div class="card">
<div class="tablewrap">
<table>
  <thead><tr>
    <th>Sujet</th><th>Canal</th><th>Cible</th><th>Statut</th><th>Envoyes</th><th>Echecs</th><th>Espace</th><th>Date</th><th></th>
  </tr></thead>
  <tbody>
  <?php foreach ($camps as $c): ?>
    <tr>
      <td><strong><?= e($c['sujet']) ?></strong></td>
      <td><span class="badge grey"><?= e($c['canal']) ?></span></td>
      <td><?= e(gbg_campaign_regions_label($c)) ?></td>
      <td>
        <?php if ($c['statut'] === 'envoyee'): ?>
          <span class="badge ok">Envoyee</span>
        <?php elseif ($c['statut'] === 'en_cours'): ?>
          <span class="badge grey">En cours</span>
        <?php else: ?>
          <span class="badge grey">Brouillon</span>
        <?php endif; ?>
      </td>
      <td><?= (int)$c['nb_envoyes'] ?>/<?= (int)$c['nb_destinataires'] ?></td>
      <td><?= (int)$c['nb_echecs'] ?></td>
      <td><?= $c['publiee'] ? '<span class="badge ok">publiee</span>' : '<span class="badge grey">non</span>' ?></td>
      <td class="muted"><?= e($c['sent_at'] ?: $c['created_at']) ?></td>
      <td><a class="btn sm sec" href="campagne-view.php?id=<?= (int)$c['id'] ?>">Ouvrir</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$camps): ?>
    <tr><td colspan="9" class="muted">Aucune campagne. <a href="campagne-edit.php">Creer la premiere</a>.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
<?php
admin_footer();
