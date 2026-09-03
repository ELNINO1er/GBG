<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/campaign.php';

$admin = admin_require();
$db = gbg_db();

// Migration legere pour autoriser plusieurs regions sur les installations existantes.
try {
    $column = $db->query("SHOW COLUMNS FROM campagnes LIKE 'filtre_region'")->fetch();
    if ($column && strtolower((string)$column['Type']) !== 'text') {
        $db->exec("ALTER TABLE campagnes MODIFY filtre_region TEXT NOT NULL");
    }
} catch (Throwable $e) {
    // Le champ VARCHAR existant reste utilisable pour quelques regions si ALTER est interdit.
}

$id = (int)($_GET['id'] ?? 0);
$camp = null;
if ($id > 0) {
    $stmt = $db->prepare('SELECT * FROM campagnes WHERE id = ?');
    $stmt->execute([$id]);
    $camp = $stmt->fetch();
    if (!$camp) {
        flash('Campagne introuvable.', 'error');
        redirect('campagnes.php');
    }
    if ($camp['statut'] !== 'brouillon') {
        // Une campagne lancee (en cours ou envoyee) n'est plus modifiable
        redirect('campagne-view.php?id=' . $id);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sujet   = trim((string)$_POST['sujet']);
    $contenu = trim((string)$_POST['contenu']);
    $regionsSelected = array_values(array_unique(array_filter(array_map(
        static fn($value) => str_replace('|', '', trim((string)$value)),
        (array)($_POST['filtre_regions'] ?? [])
    ))));
    $region  = implode('|', $regionsSelected);
    $canaux  = $_POST['canal'] ?? [];
    $publiee = in_array('espace', (array)$canaux, true) ? 1 : 0;
    $canal   = implode('+', array_intersect(['email', 'espace'], (array)$canaux)) ?: 'email';
    $now = date('Y-m-d H:i:s');

    if ($sujet === '' || $contenu === '') {
        flash('Le sujet et le contenu sont obligatoires.', 'error');
    } elseif ($camp) {
        $db->prepare(
            'UPDATE campagnes SET sujet=?, contenu=?, canal=?, publiee=?, filtre_region=? WHERE id=?'
        )->execute([$sujet, $contenu, $canal, $publiee, $region, $id]);
        flash('Brouillon enregistre.', 'success');
        redirect('campagne-view.php?id=' . $id);
    } else {
        $db->prepare(
            'INSERT INTO campagnes (sujet, contenu, canal, publiee, filtre_region, statut, created_by, created_at)
             VALUES (?,?,?,?,?,\'brouillon\',?,?)'
        )->execute([$sujet, $contenu, $canal, $publiee, $region, $admin['id'], $now]);
        flash('Brouillon cree. Verifiez puis lancez l\'envoi.', 'success');
        redirect('campagne-view.php?id=' . $db->lastInsertId());
    }
}

$regions = $db->query(
    "SELECT region, COUNT(*) n FROM cooperatives WHERE region <> '' AND actif=1 GROUP BY region ORDER BY region"
)->fetchAll();

$v = static fn(string $k) => e($camp[$k] ?? '');
$canalArr = $camp ? explode('+', $camp['canal']) : ['email'];
$selectedRegions = $camp ? gbg_campaign_regions($camp) : [];

admin_header($camp ? 'Modifier campagne' : 'Nouvelle campagne', 'campagnes.php');
?>
<h1><?= $camp ? 'Modifier la campagne' : 'Nouvelle campagne' ?></h1>
<p class="sub"><a href="campagnes.php">&larr; Retour aux campagnes</a></p>

<form method="post" action="campagne-edit.php<?= $camp ? '?id=' . $id : '' ?>">
  <?= csrf_field() ?>
  <div class="card">
    <h2>Message</h2>
    <label>Sujet *</label>
    <input name="sujet" value="<?= $v('sujet') ?>" required placeholder="Ex. Bulletin d'information - campagne cacao 2026">
    <label>Contenu * <span class="muted">(le HTML simple est autorise : &lt;b&gt;, &lt;br&gt;, &lt;p&gt;, liens...)</span></label>
    <textarea name="contenu" rows="12" required placeholder="Bonjour,&#10;&#10;Nous vous informons que..."><?= $v('contenu') ?></textarea>
  </div>

  <div class="card">
    <h2>Diffusion</h2>
    <label>Canaux</label>
    <label style="font-weight:400"><input type="checkbox" name="canal[]" value="email" style="width:auto" <?= in_array('email', $canalArr, true) ? 'checked' : '' ?>> Envoyer par email aux cooperatives joignables</label>
    <label style="font-weight:400"><input type="checkbox" name="canal[]" value="espace" style="width:auto" <?= in_array('espace', $canalArr, true) ? 'checked' : '' ?>> Publier dans l'espace cooperatives</label>

    <label style="margin-top:16px">Cibler une ou plusieurs regions <span class="muted">(optionnel)</span></label>
    <select name="filtre_regions[]" multiple data-placeholder="Toutes les regions">
      <?php foreach ($regions as $r): ?>
        <option value="<?= e($r['region']) ?>" <?= in_array($r['region'], $selectedRegions, true) ? 'selected' : '' ?>>
          <?= e($r['region']) ?> (<?= (int)$r['n'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
    <p class="muted" style="margin-top:10px">Ne selectionnez aucune region pour cibler toutes les regions. Vous pouvez rechercher puis choisir plusieurs regions.</p>
  </div>

  <p><button class="btn" type="submit">Enregistrer le brouillon</button></p>
</form>
<?php
admin_footer();
