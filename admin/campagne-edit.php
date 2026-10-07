<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/campaign.php';

$admin = admin_require();
$db = gbg_db();

gbg_ensure_campaign_targeting_schema();
gbg_ensure_campaign_documents_schema();

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
    $action = (string)($_POST['action'] ?? 'save');
    if ($action === 'delete_document' && $camp) {
        $documentId = (int)($_POST['document_id'] ?? 0);
        $stmt = $db->prepare('SELECT nom_stockage FROM campagne_documents WHERE id = ? AND campagne_id = ?');
        $stmt->execute([$documentId, $id]);
        $document = $stmt->fetch();
        if ($document) {
            $db->prepare('DELETE FROM campagne_documents WHERE id = ? AND campagne_id = ?')->execute([$documentId, $id]);
            $path = gbg_campaign_document_storage_dir() . '/' . basename((string)$document['nom_stockage']);
            if (is_file($path)) {
                @unlink($path);
            }
            flash('Piece jointe supprimee.', 'success');
        }
        redirect('campagne-edit.php?id=' . $id);
    }
    $sujet   = trim((string)$_POST['sujet']);
    $contenu = trim((string)$_POST['contenu']);
    $regionsSelected = array_values(array_unique(array_filter(array_map(
        static fn($value) => str_replace('|', '', trim((string)$value)),
        (array)($_POST['filtre_regions'] ?? [])
    ))));
    $region  = implode('|', $regionsSelected);
    $cooperativeIds = array_values(array_filter(array_unique(array_map(
        'intval', (array)($_POST['filtre_cooperatives'] ?? [])
    )), static fn($value) => $value > 0));
    $cooperativeFilter = implode('|', $cooperativeIds);
    $canaux  = $_POST['canal'] ?? [];
    $publiee = in_array('espace', (array)$canaux, true) ? 1 : 0;
    $canal   = implode('+', array_intersect(['email', 'espace'], (array)$canaux)) ?: 'email';
    $now = date('Y-m-d H:i:s');

    if ($sujet === '' || $contenu === '') {
        flash('Le sujet et le contenu sont obligatoires.', 'error');
    } elseif ($camp) {
        $db->prepare(
            'UPDATE campagnes SET sujet=?, contenu=?, canal=?, publiee=?, filtre_region=?, filtre_cooperatives=? WHERE id=?'
        )->execute([$sujet, $contenu, $canal, $publiee, $region, $cooperativeFilter, $id]);
        try {
            $added = gbg_campaign_store_documents($id, $_FILES['documents'] ?? []);
        } catch (RuntimeException $e) {
            flash($e->getMessage(), 'error');
            redirect('campagne-edit.php?id=' . $id);
        }
        flash('Brouillon enregistre.' . ($added ? " $added document(s) ajoute(s)." : ''), 'success');
        redirect('campagne-view.php?id=' . $id);
    } else {
        $db->prepare(
            'INSERT INTO campagnes (sujet, contenu, canal, publiee, filtre_region, filtre_cooperatives, statut, created_by, created_at)
             VALUES (?,?,?,?,?,?,\'brouillon\',?,?)'
        )->execute([$sujet, $contenu, $canal, $publiee, $region, $cooperativeFilter, $admin['id'], $now]);
        $newId = (int)$db->lastInsertId();
        try {
            $added = gbg_campaign_store_documents($newId, $_FILES['documents'] ?? []);
        } catch (RuntimeException $e) {
            flash('Brouillon cree, mais les documents n’ont pas ete ajoutes : ' . $e->getMessage(), 'error');
            redirect('campagne-edit.php?id=' . $newId);
        }
        flash('Brouillon cree' . ($added ? " avec $added document(s)" : '') . '. Verifiez puis lancez l\'envoi.', 'success');
        redirect('campagne-view.php?id=' . $newId);
    }
}

$regions = $db->query(
    "SELECT region, COUNT(*) n FROM cooperatives WHERE region <> '' AND actif=1 GROUP BY region ORDER BY region"
)->fetchAll();
$cooperatives = $db->query(
    "SELECT id, nom_cooperative, region FROM cooperatives WHERE actif=1 ORDER BY nom_cooperative"
)->fetchAll();

$v = static fn(string $k) => e($camp[$k] ?? '');
$canalArr = $camp ? explode('+', $camp['canal']) : ['email'];
$selectedRegions = $camp ? gbg_campaign_regions($camp) : [];
$selectedCooperatives = $camp ? gbg_campaign_cooperative_ids($camp) : [];
$documents = $camp ? gbg_campaign_documents($id) : [];

admin_header($camp ? 'Modifier campagne' : 'Nouvelle campagne', 'campagnes.php');
?>
<h1><?= $camp ? 'Modifier la campagne' : 'Nouvelle campagne' ?></h1>
<p class="sub"><a href="campagnes.php">&larr; Retour aux campagnes</a></p>

<form method="post" enctype="multipart/form-data" action="campagne-edit.php<?= $camp ? '?id=' . $id : '' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="card">
    <h2>Message</h2>
    <label>Sujet *</label>
    <input name="sujet" value="<?= $v('sujet') ?>" required placeholder="Ex. Bulletin d'information - campagne cacao 2026">
    <label>Contenu * <span class="muted">(le HTML simple est autorise : &lt;b&gt;, &lt;br&gt;, &lt;p&gt;, liens...)</span></label>
    <textarea name="contenu" rows="12" required placeholder="Bonjour,&#10;&#10;Nous vous informons que..."><?= $v('contenu') ?></textarea>
  </div>

  <div class="card">
    <h2>Documents joints</h2>
    <p class="muted">Ajoutez jusqu’a 5 fichiers PDF, Word (.docx) ou Excel (.xlsx). Maximum 10 Mo par fichier et 20 Mo au total.</p>
    <label for="campaign-documents">Choisir les documents <span class="muted">(optionnel)</span></label>
    <input id="campaign-documents" type="file" name="documents[]" accept=".pdf,.docx,.xlsx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" multiple>
    <?php if ($documents): ?>
      <div style="margin-top:18px">
        <?php foreach ($documents as $document): ?>
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-top:1px solid var(--line)">
            <span><strong><?= e($document['nom_original']) ?></strong><br><span class="muted"><?= number_format((int)$document['taille_octets'] / 1024, 0, ',', ' ') ?> Ko</span></span>
            <button class="btn sm danger" type="submit" form="remove-document-<?= (int)$document['id'] ?>">Retirer</button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
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

    <label style="margin-top:20px">Cibler une ou plusieurs cooperatives precises <span class="muted">(optionnel)</span></label>
    <select name="filtre_cooperatives[]" multiple data-placeholder="Aucune cooperative precise">
      <?php foreach ($cooperatives as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= in_array((int)$c['id'], $selectedCooperatives, true) ? 'selected' : '' ?>>
          <?= e($c['nom_cooperative']) ?><?= $c['region'] !== '' ? ' — ' . e($c['region']) : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="muted" style="margin-top:10px">Si vous selectionnez ici des cooperatives, elles seules recevront la campagne, quels que soient les choix de regions ci-dessus.</p>
  </div>

  <p><button class="btn" type="submit">Enregistrer le brouillon</button></p>
</form>
<?php foreach ($documents as $document): ?>
  <form id="remove-document-<?= (int)$document['id'] ?>" method="post" action="campagne-edit.php?id=<?= $id ?>" data-confirm="Retirer ce document de la campagne ?">
    <?= csrf_field() ?><input type="hidden" name="action" value="delete_document"><input type="hidden" name="document_id" value="<?= (int)$document['id'] ?>">
  </form>
<?php endforeach; ?>
<?php
admin_footer();
