<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/importer.php';

admin_require();

$bilan = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        flash('Aucun fichier recu ou erreur de televersement.', 'error');
    } else {
        $tmp = $_FILES['fichier']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            flash('Format non supporte. Utilisez un fichier .xlsx ou .csv.', 'error');
        } else {
            // Copie vers un fichier avec la bonne extension (l'importeur s'en sert)
            $dest = sys_get_temp_dir() . '/gbg_import_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($tmp, $dest);
            try {
                $bilan = gbg_import_cooperatives($dest);
                flash("Import termine : {$bilan['inserts']} ajout(s), {$bilan['updates']} mise(s) a jour.", 'success');
            } catch (Throwable $ex) {
                flash('Echec de l\'import : ' . $ex->getMessage(), 'error');
            } finally {
                @unlink($dest);
            }
        }
    }
}

admin_header('Importer', 'cooperatives.php');
?>
<h1>Importer des cooperatives</h1>
<p class="sub">Fichier Excel (.xlsx) ou CSV. Les cooperatives deja presentes (meme nom) sont mises a jour.</p>

<div class="card">
  <h2>Televerser un fichier</h2>
  <form method="post" action="import.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Fichier .xlsx ou .csv</label>
    <input type="file" name="fichier" accept=".xlsx,.csv" required>
    <p style="margin-top:18px"><button class="btn" type="submit">Lancer l'import</button></p>
  </form>
  <p class="muted">Colonnes attendues, dans l'ordre : N.ordre &middot; Nom PCA &middot; Nom cooperative &middot; Localite &middot; Contact PCA &middot; DR/ADG &middot; Contact &middot; Email. La 1re ligne (en-tete) est ignoree automatiquement. Les emails multiples dans une meme cellule sont eclates ; les emails invalides sont signales.</p>
</div>

<?php if ($bilan): ?>
<div class="card">
  <h2>Bilan de l'import</h2>
  <div class="grid cols-4">
    <div class="stat"><div class="n"><?= $bilan['total'] ?></div><div class="l">Lignes traitees</div></div>
    <div class="stat"><div class="n"><?= $bilan['inserts'] ?></div><div class="l">Ajouts</div></div>
    <div class="stat"><div class="n"><?= $bilan['updates'] ?></div><div class="l">Mises a jour</div></div>
    <div class="stat"><div class="n"><?= $bilan['sans_email'] ?></div><div class="l">Sans email</div></div>
  </div>
  <?php if (!empty($bilan['emails_invalides'])): ?>
    <h2 style="margin-top:20px">Emails a corriger (<?= count($bilan['emails_invalides']) ?>)</h2>
    <div class="tablewrap"><table><tbody>
      <?php foreach ($bilan['emails_invalides'] as $line): ?>
        <tr><td><?= e($line) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
  <p style="margin-top:16px"><a class="btn" href="cooperatives.php">Voir les cooperatives</a></p>
</div>
<?php endif; ?>
<?php
admin_footer();
