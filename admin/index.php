<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';

admin_require();
$db = gbg_db();

$totCoop   = (int)$db->query('SELECT COUNT(*) FROM cooperatives')->fetchColumn();
$totEmail  = (int)$db->query('SELECT COUNT(*) FROM cooperatives WHERE email_valide=1 AND actif=1')->fetchColumn();
$sansEmail = (int)$db->query('SELECT COUNT(*) FROM cooperatives WHERE email_valide=0')->fetchColumn();
$avecLogin = (int)$db->query('SELECT COUNT(*) FROM cooperatives WHERE login_username IS NOT NULL')->fetchColumn();
$totCamp   = (int)$db->query('SELECT COUNT(*) FROM campagnes')->fetchColumn();
$totEnvoyes= (int)$db->query("SELECT COALESCE(SUM(nb_envoyes),0) FROM campagnes")->fetchColumn();

$dernieres = $db->query(
    'SELECT id, sujet, statut, canal, nb_envoyes, nb_echecs, created_at, sent_at
     FROM campagnes ORDER BY id DESC LIMIT 6'
)->fetchAll();

admin_header('Tableau de bord', 'index.php');
?>
<h1>Tableau de bord</h1>
<p class="sub">Vue d'ensemble de la base cooperatives et des campagnes.</p>

<div class="card" style="border-left:4px solid var(--or)">
  <h2>Demarrage rapide</h2>
  <p class="muted">Suivez ces etapes dans l'ordre pour publier votre premiere information.</p>
  <div class="grid cols-4" style="margin-top:18px">
    <div><span class="badge <?= $totCoop > 0 ? 'ok' : 'grey' ?>">1</span><h3 style="font-size:14px;margin:10px 0 5px">Importer les cooperatives</h3><p class="muted">Ajoutez le fichier Excel contenant leurs coordonnees.</p><a href="import.php">Commencer &rarr;</a></div>
    <div><span class="badge <?= $avecLogin > 0 ? 'ok' : 'grey' ?>">2</span><h3 style="font-size:14px;margin:10px 0 5px">Creer leurs acces</h3><p class="muted">Generez les identifiants et mots de passe.</p><a href="acces-coop.php">Generer &rarr;</a></div>
    <div><span class="badge <?= $totCamp > 0 ? 'ok' : 'grey' ?>">3</span><h3 style="font-size:14px;margin:10px 0 5px">Rediger l'information</h3><p class="muted">Choisissez « Espace » pour la rendre visible dans leur portail.</p><a href="campagne-edit.php">Publier &rarr;</a></div>
    <div><span class="badge grey">4</span><h3 style="font-size:14px;margin:10px 0 5px">Transmettre les acces</h3><p class="muted">Envoyez a chaque cooperative sa ligne du CSV genere.</p><a href="../connexion.php?role=cooperative" target="_blank">Voir le portail &rarr;</a></div>
  </div>
</div>

<div class="grid cols-4">
  <div class="stat"><div class="n"><?= $totCoop ?></div><div class="l">Cooperatives</div></div>
  <div class="stat"><div class="n"><?= $totEmail ?></div><div class="l">Joignables par email</div></div>
  <div class="stat"><div class="n"><?= $sansEmail ?></div><div class="l">Sans email valide</div></div>
  <div class="stat"><div class="n"><?= $avecLogin ?></div><div class="l">Acces espace crees</div></div>
</div>

<div class="grid cols-2" style="margin-top:4px">
  <div class="card">
    <h2>Actions rapides</h2>
    <p><a class="btn" href="cooperatives.php">Gerer les cooperatives</a></p>
    <p><a class="btn sec" href="campagne-edit.php">Nouvelle campagne</a></p>
    <p><a class="btn sec" href="acces-coop.php">Generer les acces cooperatives</a></p>
    <p><a class="btn sec" href="import.php">Importer un fichier Excel/CSV</a></p>
    <p><a class="btn sec" href="test-smtp.php">Tester la configuration email</a></p>
    <?php if ($sansEmail > 0): ?>
      <p class="muted">&#9888; <?= $sansEmail ?> cooperative(s) sans email valide seront exclues des envois email. <a href="cooperatives.php?filtre=sans_email">Les completer</a>.</p>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>Envois</h2>
    <div class="grid cols-2">
      <div class="stat"><div class="n"><?= $totCamp ?></div><div class="l">Campagnes</div></div>
      <div class="stat"><div class="n"><?= $totEnvoyes ?></div><div class="l">Emails envoyes</div></div>
    </div>
  </div>
</div>

<div class="card">
  <h2>Dernieres campagnes</h2>
  <?php if (!$dernieres): ?>
    <p class="muted">Aucune campagne pour l'instant. <a href="campagne-edit.php">Creer la premiere</a>.</p>
  <?php else: ?>
  <div class="tablewrap">
  <table>
    <thead><tr><th>Sujet</th><th>Canal</th><th>Statut</th><th>Envoyes</th><th>Echecs</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($dernieres as $c): ?>
      <tr>
        <td><?= e($c['sujet']) ?></td>
        <td><span class="badge grey"><?= e($c['canal']) ?></span></td>
        <td>
          <?php if ($c['statut'] === 'envoyee'): ?>
            <span class="badge ok">Envoyee</span>
          <?php else: ?>
            <span class="badge grey">Brouillon</span>
          <?php endif; ?>
        </td>
        <td><?= (int)$c['nb_envoyes'] ?></td>
        <td><?= (int)$c['nb_echecs'] ?></td>
        <td class="muted"><?= e($c['sent_at'] ?: $c['created_at']) ?></td>
        <td><a class="btn sm sec" href="campagne-view.php?id=<?= (int)$c['id'] ?>">Voir</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php
admin_footer();
