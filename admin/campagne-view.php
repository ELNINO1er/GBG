<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/campaign.php';

admin_require();
$db = gbg_db();
gbg_ensure_campaign_targeting_schema();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM campagnes WHERE id = ?');
$stmt->execute([$id]);
$camp = $stmt->fetch();
if (!$camp) {
    flash('Campagne introuvable.', 'error');
    redirect('campagnes.php');
}

$enCours = $camp['statut'] === 'en_cours';
$isSent = in_array($camp['statut'], ['envoyee', 'en_cours'], true);
$canaux = explode('+', $camp['canal']);
$targetCoopIds = gbg_campaign_cooperative_ids($camp);
$targetCoopNames = [];
if ($targetCoopIds) {
    $targetStmt = $db->prepare('SELECT nom_cooperative FROM cooperatives WHERE id IN (' . implode(',', array_fill(0, count($targetCoopIds), '?')) . ') ORDER BY nom_cooperative');
    $targetStmt->execute($targetCoopIds);
    $targetCoopNames = $targetStmt->fetchAll(PDO::FETCH_COLUMN);
}
$targetLabel = $targetCoopNames
    ? implode(', ', $targetCoopNames)
    : gbg_campaign_regions_label($camp);

// Aperçu des destinataires (avant envoi) ou resultats (apres)
if ($isSent) {
    $envois = $db->prepare('SELECT e.*, c.nom_cooperative FROM envois e
        JOIN cooperatives c ON c.id = e.cooperative_id
        WHERE e.campagne_id = ? ORDER BY e.statut DESC, c.nom_cooperative');
    $envois->execute([$id]);
    $lignes = $envois->fetchAll();
} else {
    $lignes = gbg_campaign_recipients($camp);
}
$audience = gbg_campaign_audience_count($camp);

admin_header('Campagne', 'campagnes.php');
?>
<h1><?= e($camp['sujet']) ?></h1>
<p class="sub">
  <a href="campagnes.php">&larr; Campagnes</a> &nbsp;|&nbsp;
  Canal : <strong><?= e($camp['canal']) ?></strong> &nbsp;|&nbsp;
  Cible : <strong><?= e($targetLabel) ?></strong> &nbsp;|&nbsp;
  <?= $enCours ? '<span class="badge grey">Envoi en cours</span>' : ($isSent ? '<span class="badge ok">Envoyee</span>' : '<span class="badge grey">Brouillon</span>') ?>
</p>

<div class="grid cols-2">
  <div class="card">
    <h2>Apercu du message</h2>
    <div style="border:1px solid var(--line);border-radius:8px;padding:16px;background:#fff">
      <div style="font-size:12px;color:var(--muted);border-bottom:1px solid var(--line);padding-bottom:8px;margin-bottom:12px">
        <strong>Objet :</strong> <?= e($camp['sujet']) ?>
      </div>
      <div style="font-size:15px;line-height:1.6"><?= $camp['contenu'] /* HTML admin */ ?></div>
    </div>
  </div>

  <div class="card">
    <h2><?= $isSent ? 'Resultats' : 'Diffusion prevue' ?></h2>
    <?php
      $restants = 0;
      if ($enCours) {
          $rq = $db->prepare('SELECT COUNT(*) FROM envois WHERE campagne_id=? AND statut=\'en_attente\'');
          $rq->execute([$id]);
          $restants = (int)$rq->fetchColumn();
      }
    ?>
    <?php if (in_array('email', $canaux, true)): ?>
      <?php if ($isSent): ?>
        <div class="grid cols-3">
          <div class="stat"><div class="n"><?= (int)$camp['nb_envoyes'] ?></div><div class="l">Envoyes</div></div>
          <div class="stat"><div class="n"><?= (int)$camp['nb_echecs'] ?></div><div class="l">Echecs</div></div>
          <div class="stat"><div class="n"><?= (int)$camp['nb_destinataires'] ?></div><div class="l">Destinataires</div></div>
        </div>
      <?php else: ?>
        <p><strong><?= count($lignes) ?></strong> cooperative(s) recevront l'email (actives et avec email valide).</p>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (in_array('espace', $canaux, true)): ?>
      <p>Publication espace : <strong><?= $audience ?></strong> cooperative(s) concernee(s)
         <?= $camp['publiee'] ? '<span class="badge ok">publiee</span>' : '<span class="badge grey">a publier</span>' ?></p>
    <?php endif; ?>

    <?php if ($enCours && $restants > 0): ?>
      <div class="flash flash-info" style="margin-top:16px">Envoi en cours : <strong><?= $restants ?></strong> email(s) restant(s).</div>
      <form method="post" action="campagne-send.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn" type="submit">Continuer l'envoi</button>
      </form>
    <?php elseif (!$isSent): ?>
      <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap">
        <form method="post" action="campagne-send.php" data-confirm="La diffusion sera lancée vers les destinataires indiqués. Voulez-vous continuer ?">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $id ?>">
          <button class="btn" type="submit">Lancer la diffusion</button>
        </form>
        <a class="btn sec" href="campagne-edit.php?id=<?= $id ?>">Modifier</a>
      </div>
      <p class="muted" style="margin-top:10px">L'envoi est definitif : la campagne passera en statut "Envoyee".</p>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2><?= $isSent ? 'Detail des envois' : 'Destinataires' ?> (<?= count($lignes) ?>)</h2>
  <div class="tablewrap">
  <table>
    <thead><tr><th>Cooperative</th><th>Email</th><?php if ($isSent): ?><th>Statut</th><th>Detail</th><?php else: ?><th>Region</th><?php endif; ?></tr></thead>
    <tbody>
      <?php foreach ($lignes as $l): ?>
        <tr>
          <td><?= e($l['nom_cooperative']) ?></td>
          <td><?= e($l['email']) ?></td>
          <?php if ($isSent): ?>
            <td><?php if ($l['statut'] === 'envoye'): ?><span class="badge ok">envoye</span><?php elseif ($l['statut'] === 'echec'): ?><span class="badge no">echec</span><?php else: ?><span class="badge grey">en attente</span><?php endif; ?></td>
            <td class="muted"><?= e($l['message_erreur']) ?></td>
          <?php else: ?>
            <td><?= e($l['region']) ?></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$lignes): ?>
        <tr><td colspan="4" class="muted">Aucun destinataire email (aucune cooperative active avec email valide pour ce ciblage).</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php
admin_footer();
