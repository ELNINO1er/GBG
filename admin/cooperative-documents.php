<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';

$admin = admin_require();
$db = gbg_db();

// Migration idempotente pour que la fonctionnalite soit disponible aussi
// sur une installation existante sans reimport manuel du schema complet.
$db->exec("CREATE TABLE IF NOT EXISTS cooperative_documents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cooperative_id INT UNSIGNED NOT NULL,
  type_document VARCHAR(80) NOT NULL DEFAULT 'Autre document',
  titre VARCHAR(255) NOT NULL,
  numero_reference VARCHAR(120) NOT NULL DEFAULT '',
  produit VARCHAR(150) NOT NULL DEFAULT '',
  date_delivrance DATE NULL,
  date_expiration DATE NULL,
  statut VARCHAR(30) NOT NULL DEFAULT 'valide',
  nom_original VARCHAR(255) NOT NULL,
  nom_stockage VARCHAR(255) NOT NULL,
  taille_octets INT UNSIGNED NOT NULL DEFAULT 0,
  mime_type VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_doc_cooperative (cooperative_id),
  KEY idx_doc_expiration (date_expiration),
  CONSTRAINT fk_doc_cooperative FOREIGN KEY (cooperative_id)
    REFERENCES cooperatives (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$id = (int)($_GET['id'] ?? $_POST['cooperative_id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM cooperatives WHERE id = ?');
$stmt->execute([$id]);
$coop = $stmt->fetch();
if (!$coop) {
    flash('Cooperative introuvable.', 'error');
    redirect('cooperatives.php');
}

$storageDir = dirname(__DIR__) . '/storage/cooperative-documents';

if (isset($_GET['download'])) {
    $documentId = (int)$_GET['download'];
    $stmt = $db->prepare('SELECT * FROM cooperative_documents WHERE id = ? AND cooperative_id = ?');
    $stmt->execute([$documentId, $id]);
    $document = $stmt->fetch();
    if (!$document) {
        http_response_code(404);
        exit('Document introuvable.');
    }
    $path = $storageDir . '/' . basename((string)$document['nom_stockage']);
    if (!is_file($path)) {
        http_response_code(404);
        exit('Fichier introuvable.');
    }
    $downloadName = preg_replace('/[^\pL\pN._ -]+/u', '_', (string)$document['nom_original']) ?: 'document.pdf';
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="document.pdf"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? 'upload');

    if ($action === 'delete') {
        $documentId = (int)($_POST['document_id'] ?? 0);
        $stmt = $db->prepare('SELECT nom_stockage FROM cooperative_documents WHERE id = ? AND cooperative_id = ?');
        $stmt->execute([$documentId, $id]);
        $document = $stmt->fetch();
        if ($document) {
            $db->prepare('DELETE FROM cooperative_documents WHERE id = ? AND cooperative_id = ?')->execute([$documentId, $id]);
            $path = $storageDir . '/' . basename((string)$document['nom_stockage']);
            if (is_file($path)) {
                @unlink($path);
            }
            flash('Document supprime.', 'success');
        }
        redirect('cooperative-documents.php?id=' . $id);
    }

    $file = $_FILES['document'] ?? null;
    $title = trim((string)($_POST['titre'] ?? ''));
    $type = trim((string)($_POST['type_document'] ?? 'Certificat Fairtrade'));
    $reference = trim((string)($_POST['numero_reference'] ?? ''));
    $produit = trim((string)($_POST['produit'] ?? ''));
    $issued = trim((string)($_POST['date_delivrance'] ?? ''));
    $expires = trim((string)($_POST['date_expiration'] ?? ''));
    $status = (string)($_POST['statut'] ?? 'valide');
    $allowedStatuses = ['valide', 'expire', 'en_attente'];

    $error = '';
    if ($title === '') {
        $error = 'Indiquez un titre pour le document.';
    } elseif (!$file || (int)$file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Selectionnez un fichier PDF valide.';
    } elseif ((int)$file['size'] <= 0 || (int)$file['size'] > 10 * 1024 * 1024) {
        $error = 'Le PDF doit avoir une taille inferieure a 10 Mo.';
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = 'Le statut choisi est invalide.';
    } elseif (($issued !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issued)) || ($expires !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires))) {
        $error = 'Une date renseignee est invalide.';
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file((string)$file['tmp_name']);
        $magic = file_get_contents((string)$file['tmp_name'], false, null, 0, 5);
        if ($mime !== 'application/pdf' || $magic !== '%PDF-') {
            $error = 'Seuls les vrais fichiers PDF sont acceptes.';
        }
    }

    if ($error !== '') {
        flash($error, 'error');
    } else {
        if (!is_dir($storageDir) && !mkdir($storageDir, 0750, true) && !is_dir($storageDir)) {
            throw new RuntimeException('Impossible de creer le dossier de stockage.');
        }
        $storedName = bin2hex(random_bytes(20)) . '.pdf';
        $destination = $storageDir . '/' . $storedName;
        if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible d’enregistrer le PDF.');
        }
        $originalName = basename((string)$file['name']);
        try {
            $ins = $db->prepare('INSERT INTO cooperative_documents
                (cooperative_id, type_document, titre, numero_reference, produit, date_delivrance,
                 date_expiration, statut, nom_original, nom_stockage, taille_octets, mime_type,
                 created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $ins->execute([
                $id, $type, $title, $reference, $produit,
                $issued !== '' ? $issued : null, $expires !== '' ? $expires : null,
                $status, $originalName, $storedName, (int)$file['size'], 'application/pdf',
                (int)($admin['id'] ?? 0) ?: null, date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            @unlink($destination);
            throw $e;
        }
        flash('PDF ajoute avec succes.', 'success');
        redirect('cooperative-documents.php?id=' . $id);
    }
}

$stmt = $db->prepare('SELECT * FROM cooperative_documents WHERE cooperative_id = ? ORDER BY created_at DESC');
$stmt->execute([$id]);
$documents = $stmt->fetchAll();

admin_header('Documents - ' . $coop['nom_cooperative'], 'cooperatives.php');
?>
<div class="toolbar">
  <div>
    <h1>Documents et certifications</h1>
    <p class="sub" style="margin-bottom:0"><?= e($coop['nom_cooperative']) ?> · <?= e($coop['localite']) ?></p>
  </div>
  <span class="spacer"></span>
  <a class="btn sec" href="cooperatives.php">&larr; Retour aux cooperatives</a>
</div>

<div class="card">
  <h2>Ajouter un PDF</h2>
  <p class="muted">Le document sera rattache uniquement a cette cooperative. Taille maximale : 10 Mo.</p>
  <form method="post" enctype="multipart/form-data" action="cooperative-documents.php?id=<?= $id ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload">
    <input type="hidden" name="cooperative_id" value="<?= $id ?>">
    <div class="row">
      <div><label for="type_document">Type de document</label><select id="type_document" name="type_document"><option>Certificat Fairtrade</option><option>Certificat biologique</option><option>Agrement</option><option>Rapport</option><option>Autre document</option></select></div>
      <div><label for="titre">Titre *</label><input id="titre" name="titre" required value="<?= e($_POST['titre'] ?? 'Certificat Fairtrade') ?>"></div>
    </div>
    <div class="row">
      <div><label for="numero_reference">Numero / reference</label><input id="numero_reference" name="numero_reference" value="<?= e($_POST['numero_reference'] ?? '') ?>" placeholder="Ex. FLO ID 49908"></div>
      <div><label for="produit">Produit concerne</label><input id="produit" name="produit" value="<?= e($_POST['produit'] ?? '') ?>" placeholder="Ex. Cacao"></div>
    </div>
    <div class="row">
      <div><label for="date_delivrance">Date de delivrance</label><input id="date_delivrance" type="date" name="date_delivrance" value="<?= e($_POST['date_delivrance'] ?? '') ?>"></div>
      <div><label for="date_expiration">Date d'expiration</label><input id="date_expiration" type="date" name="date_expiration" value="<?= e($_POST['date_expiration'] ?? '') ?>"></div>
      <div><label for="statut">Statut</label><select id="statut" name="statut"><option value="valide">Valide</option><option value="en_attente">En attente</option><option value="expire">Expire</option></select></div>
    </div>
    <label for="document">Fichier PDF *</label>
    <input id="document" name="document" type="file" accept="application/pdf,.pdf" required>
    <p style="margin:20px 0 0"><button class="btn" type="submit">Enregistrer le PDF</button></p>
  </form>
</div>

<div class="card">
  <h2>Documents enregistres (<?= count($documents) ?>)</h2>
  <?php if (!$documents): ?>
    <p class="muted">Aucun document pour cette cooperative. Utilisez le formulaire ci-dessus pour ajouter le certificat.</p>
  <?php else: ?>
    <div class="tablewrap"><table>
      <thead><tr><th>Document</th><th>Reference</th><th>Produit</th><th>Validite</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody><?php foreach ($documents as $document): ?>
        <tr>
          <td><strong><?= e($document['titre']) ?></strong><div class="muted"><?= e($document['type_document']) ?> · <?= number_format((int)$document['taille_octets'] / 1024, 0, ',', ' ') ?> Ko</div></td>
          <td><?= e($document['numero_reference'] ?: '—') ?></td>
          <td><?= e($document['produit'] ?: '—') ?></td>
          <td><span class="muted">Delivre le</span> <?= $document['date_delivrance'] ? e(date('d/m/Y', strtotime($document['date_delivrance']))) : '—' ?><br><span class="muted">Expire le</span> <?= $document['date_expiration'] ? e(date('d/m/Y', strtotime($document['date_expiration']))) : '—' ?></td>
          <td><span class="badge <?= $document['statut'] === 'valide' ? 'ok' : ($document['statut'] === 'expire' ? 'no' : 'grey') ?>"><?= e(ucfirst(str_replace('_', ' ', $document['statut']))) ?></span></td>
          <td><div class="table-actions"><a class="btn sm sec" target="_blank" rel="noopener" href="cooperative-documents.php?id=<?= $id ?>&amp;download=<?= (int)$document['id'] ?>">Voir</a><form method="post" action="cooperative-documents.php?id=<?= $id ?>" data-confirm="Supprimer définitivement ce document ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="cooperative_id" value="<?= $id ?>"><input type="hidden" name="document_id" value="<?= (int)$document['id'] ?>"><button class="btn sm danger" type="submit">Supprimer</button></form></div></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php admin_footer();
