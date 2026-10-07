<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/** Met a niveau les colonnes de ciblage sur une installation existante. */
function gbg_ensure_campaign_targeting_schema(): void
{
    $db = gbg_db();
    try {
        $column = $db->query("SHOW COLUMNS FROM campagnes LIKE 'filtre_region'")->fetch();
        if ($column && strtolower((string)$column['Type']) !== 'text') {
            $db->exec("ALTER TABLE campagnes MODIFY filtre_region TEXT NOT NULL");
        }
        $coopColumn = $db->query("SHOW COLUMNS FROM campagnes LIKE 'filtre_cooperatives'")->fetch();
        if (!$coopColumn) {
            $db->exec("ALTER TABLE campagnes ADD filtre_cooperatives TEXT NULL AFTER filtre_region");
            $db->exec("UPDATE campagnes SET filtre_cooperatives='' WHERE filtre_cooperatives IS NULL");
            $db->exec("ALTER TABLE campagnes MODIFY filtre_cooperatives TEXT NOT NULL");
        }
    } catch (Throwable $e) {
        throw new RuntimeException('Mise a jour du ciblage impossible : ' . $e->getMessage(), 0, $e);
    }
}

/** Cree la table des pieces jointes sur les installations deja en ligne. */
function gbg_ensure_campaign_documents_schema(): void
{
    gbg_db()->exec("CREATE TABLE IF NOT EXISTS campagne_documents (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      campagne_id INT UNSIGNED NOT NULL,
      nom_original VARCHAR(255) NOT NULL,
      nom_stockage VARCHAR(255) NOT NULL,
      mime_type VARCHAR(120) NOT NULL,
      taille_octets INT UNSIGNED NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      KEY idx_campdoc_campagne (campagne_id),
      CONSTRAINT fk_campdoc_campagne FOREIGN KEY (campagne_id)
        REFERENCES campagnes (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function gbg_campaign_document_storage_dir(): string
{
    return dirname(__DIR__) . '/storage/campaign-documents';
}

/** @return array<int,array> */
function gbg_campaign_documents(int $campaignId): array
{
    gbg_ensure_campaign_documents_schema();
    $stmt = gbg_db()->prepare('SELECT * FROM campagne_documents WHERE campagne_id = ? ORDER BY id');
    $stmt->execute([$campaignId]);
    return $stmt->fetchAll();
}

/**
 * Verifie puis enregistre jusqu'a cinq fichiers PDF, Word ou Excel.
 * @return int Nombre de fichiers ajoutes.
 */
function gbg_campaign_store_documents(int $campaignId, array $uploads): int
{
    if (!isset($uploads['name']) || $uploads['name'] === '') {
        return 0;
    }
    $names = is_array($uploads['name']) ? $uploads['name'] : [$uploads['name']];
    $tmpNames = is_array($uploads['tmp_name'] ?? null) ? $uploads['tmp_name'] : [$uploads['tmp_name'] ?? ''];
    $errors = is_array($uploads['error'] ?? null) ? $uploads['error'] : [$uploads['error'] ?? UPLOAD_ERR_NO_FILE];
    $sizes = is_array($uploads['size'] ?? null) ? $uploads['size'] : [$uploads['size'] ?? 0];
    $allowed = [
        'pdf'  => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];
    $mimeForExtension = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    $activeIndexes = array_values(array_filter(array_keys($names), static fn($i) => (int)($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));
    if (count($activeIndexes) > 5) {
        throw new RuntimeException('Vous pouvez joindre au maximum 5 documents par enregistrement.');
    }

    $validated = [];
    $totalSize = 0;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($activeIndexes as $i) {
        if ((int)$errors[$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Un document n’a pas pu etre charge. Reessayez.');
        }
        $size = (int)$sizes[$i];
        $totalSize += $size;
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            throw new RuntimeException('Chaque document doit avoir une taille inferieure a 10 Mo.');
        }
        $original = basename((string)$names[$i]);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $detectedMime = (string)$finfo->file((string)$tmpNames[$i]);
        if (!isset($allowed[$extension]) || !in_array($detectedMime, $allowed[$extension], true)) {
            throw new RuntimeException('Formats acceptes : PDF, Word (.docx) et Excel (.xlsx).');
        }
        if ($extension === 'pdf' && file_get_contents((string)$tmpNames[$i], false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('Le fichier PDF selectionne est invalide.');
        }
        $validated[] = [
            'tmp' => (string)$tmpNames[$i], 'name' => $original, 'extension' => $extension,
            'mime' => $mimeForExtension[$extension], 'size' => $size,
        ];
    }
    if ($totalSize > 20 * 1024 * 1024) {
        throw new RuntimeException('La taille totale des documents ne doit pas depasser 20 Mo.');
    }
    if (!$validated) {
        return 0;
    }

    $dir = gbg_campaign_document_storage_dir();
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossible de creer le dossier des documents.');
    }
    $db = gbg_db();
    $insert = $db->prepare('INSERT INTO campagne_documents
        (campagne_id, nom_original, nom_stockage, mime_type, taille_octets, created_at)
        VALUES (?, ?, ?, ?, ?, ?)');
    $storedPaths = [];
    try {
        foreach ($validated as $file) {
            $storedName = bin2hex(random_bytes(20)) . '.' . $file['extension'];
            $destination = $dir . '/' . $storedName;
            if (!move_uploaded_file($file['tmp'], $destination)) {
                throw new RuntimeException('Impossible d’enregistrer le document ' . $file['name'] . '.');
            }
            $storedPaths[] = $destination;
            $insert->execute([$campaignId, $file['name'], $storedName, $file['mime'], $file['size'], date('Y-m-d H:i:s')]);
        }
    } catch (Throwable $e) {
        foreach ($storedPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        throw $e;
    }
    return count($validated);
}

/**
 * Destinataires email d'une campagne : cooperatives actives, email valide,
 * eventuellement filtrees par region.
 *
 * @return array<int,array>
 */
function gbg_campaign_recipients(array $camp): array
{
    $db = gbg_db();
    $sql = 'SELECT id, nom_cooperative, email, emails_extra, region
            FROM cooperatives
            WHERE actif = 1 AND email_valide = 1';
    $params = [];
    $coopIds = gbg_campaign_cooperative_ids($camp);
    $regions = gbg_campaign_regions($camp);
    if ($coopIds) {
        $sql .= ' AND id IN (' . implode(',', array_fill(0, count($coopIds), '?')) . ')';
        array_push($params, ...$coopIds);
    } elseif ($regions) {
        $sql .= ' AND region IN (' . implode(',', array_fill(0, count($regions), '?')) . ')';
        array_push($params, ...$regions);
    }
    $sql .= ' ORDER BY nom_cooperative';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Nombre de cooperatives ciblees (toutes actives) pour publication espace. */
function gbg_campaign_audience_count(array $camp): int
{
    $db = gbg_db();
    $sql = 'SELECT COUNT(*) FROM cooperatives WHERE actif = 1';
    $params = [];
    $coopIds = gbg_campaign_cooperative_ids($camp);
    $regions = gbg_campaign_regions($camp);
    if ($coopIds) {
        $sql .= ' AND id IN (' . implode(',', array_fill(0, count($coopIds), '?')) . ')';
        array_push($params, ...$coopIds);
    } elseif ($regions) {
        $sql .= ' AND region IN (' . implode(',', array_fill(0, count($regions), '?')) . ')';
        array_push($params, ...$regions);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/** @return array<int,string> Regions ciblees ; tableau vide = toutes les regions. */
function gbg_campaign_regions(array $camp): array
{
    $raw = trim((string)($camp['filtre_region'] ?? ''));
    if ($raw === '') {
        return [];
    }
    return array_values(array_filter(array_unique(array_map('trim', explode('|', $raw)))));
}

function gbg_campaign_regions_label(array $camp): string
{
    $regions = gbg_campaign_regions($camp);
    return $regions ? implode(', ', $regions) : 'Toutes les regions';
}

/** @return array<int,int> Identifiants des cooperatives ciblees precisement. */
function gbg_campaign_cooperative_ids(array $camp): array
{
    $raw = trim((string)($camp['filtre_cooperatives'] ?? ''));
    if ($raw === '') {
        return [];
    }
    return array_values(array_filter(array_unique(array_map('intval', explode('|', $raw))), static fn($id) => $id > 0));
}

/**
 * Enrobe le contenu d'une campagne dans un gabarit email HTML aux couleurs GBG.
 */
function gbg_email_template(string $sujet, string $contenu): string
{
    $sujetEsc = e($sujet);
    // Le contenu est du HTML simple saisi par l'admin ; on l'insere tel quel.
    return <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"></head>
<body style="margin:0;background:#f4f6f4;font-family:Arial,Helvetica,sans-serif;color:#1c2a22;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f4;padding:24px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8e3;">
        <tr><td style="background:#143c28;padding:22px 28px;">
          <span style="color:#fff;font-size:18px;font-weight:bold;letter-spacing:.5px;">GLOBAL BUSINESS <span style="color:#c8a24b;">GROUP</span></span>
        </td></tr>
        <tr><td style="padding:28px;">
          <h1 style="font-size:19px;color:#143c28;margin:0 0 18px;">{$sujetEsc}</h1>
          <div style="font-size:15px;line-height:1.6;color:#2a3a30;">{$contenu}</div>
        </td></tr>
        <tr><td style="background:#f0f5f2;padding:18px 28px;font-size:12px;color:#6b7a70;">
          Global Business Group SA &middot; Riviera Triangle, Abidjan &middot; infos@gbg-ci.com<br>
          Ce message vous est adresse dans le cadre du partenariat avec les cooperatives.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
}
