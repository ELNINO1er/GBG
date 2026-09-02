<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/xlsx_reader.php';

/**
 * Importe un fichier .xlsx/.csv de cooperatives dans la base.
 *
 * Colonnes attendues (dans cet ordre, en-tete detecte automatiquement) :
 *   N.ORDRE | NOM PCA | NOM COOPERATIVE | LOCALITE | CONTACT PCA | DR/ADG | CONTACT | EMAIL
 *
 * Comportement :
 *   - saute la ligne d'en-tete,
 *   - nettoie / valide les emails (parse_emails),
 *   - dedoublonne par nom de cooperative (met a jour si deja present),
 *   - marque email_valide = 0 quand aucun email exploitable.
 *
 * @return array bilan { total, inserts, updates, sans_email, emails_invalides:[] }
 */
function gbg_import_cooperatives(string $path): array
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $rowsBySheet = [];

    if ($ext === 'csv') {
        $rows = [];
        if (($fh = fopen($path, 'r')) !== false) {
            $delim = gbg_detect_delimiter($path);
            while (($data = fgetcsv($fh, 0, $delim)) !== false) {
                $rows[] = array_map(static fn($v) => trim((string)$v), $data);
            }
            fclose($fh);
        }
        $rowsBySheet['csv'] = $rows;
    } else {
        $rowsBySheet = gbg_read_xlsx($path);
    }

    $db = gbg_db();
    $now = date('Y-m-d H:i:s');

    $bilan = [
        'total' => 0, 'inserts' => 0, 'updates' => 0,
        'sans_email' => 0, 'emails_invalides' => [],
    ];

    $selChk = $db->prepare('SELECT id FROM cooperatives WHERE nom_cooperative = ? LIMIT 1');

    foreach ($rowsBySheet as $sheetName => $rows) {
        foreach ($rows as $row) {
            // On attend au moins un nom de cooperative en colonne 2 (index 2)
            $nom = trim($row[2] ?? '');
            $nordre = trim($row[0] ?? '');

            // Ignore les lignes vides
            if ($nom === '' && $nordre === '') {
                continue;
            }
            // Ignore les lignes d'en-tete (colonne ORDRE ou libelle de colonne)
            if (str_contains(mb_strtoupper($nordre), 'ORDRE')
                || str_contains(mb_strtoupper($nom), 'NOM DE LA SOCIETE')) {
                continue;
            }
            // Un nom de cooperative est indispensable
            if ($nom === '') {
                continue;
            }

            $pca      = trim($row[1] ?? '');
            $localite = trim($row[3] ?? '');
            $contactPca = trim($row[4] ?? '');
            $drAdg    = trim($row[5] ?? '');
            $contact  = trim($row[6] ?? '');
            $emailRaw = trim($row[7] ?? '');

            $parsed = parse_emails($emailRaw);
            $email = $parsed['primary'];
            $extra = implode(';', $parsed['extra']);
            $emailValide = $email !== '' ? 1 : 0;

            if ($emailValide === 0) {
                $bilan['sans_email']++;
            }
            if (!empty($parsed['invalid'])) {
                $bilan['emails_invalides'][] = $nom . ' : ' . implode(' ', $parsed['invalid']);
            }

            $region = deduire_region($localite);

            $selChk->execute([$nom]);
            $existing = $selChk->fetchColumn();

            if ($existing) {
                $upd = $db->prepare(
                    'UPDATE cooperatives SET n_ordre=?, pca_nom=?, localite=?, region=?,
                        contact_pca=?, dr_adg=?, contact=?, email=?, emails_extra=?,
                        email_valide=?, source_feuille=?, updated_at=?
                     WHERE id=?'
                );
                $upd->execute([
                    $nordre, $pca, $localite, $region, $contactPca, $drAdg, $contact,
                    $email, $extra, $emailValide, $sheetName, $now, $existing,
                ]);
                $bilan['updates']++;
            } else {
                $ins = $db->prepare(
                    'INSERT INTO cooperatives
                        (n_ordre, nom_cooperative, pca_nom, localite, region, contact_pca,
                         dr_adg, contact, email, emails_extra, email_valide, source_feuille,
                         actif, created_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1,?)'
                );
                $ins->execute([
                    $nordre, $nom, $pca, $localite, $region, $contactPca, $drAdg,
                    $contact, $email, $extra, $emailValide, $sheetName, $now,
                ]);
                $bilan['inserts']++;
            }
            $bilan['total']++;
        }
    }

    return $bilan;
}

/** Detecte le separateur d'un CSV (`,` ou `;`). */
function gbg_detect_delimiter(string $path): string
{
    $line = '';
    if (($fh = fopen($path, 'r')) !== false) {
        $line = (string)fgets($fh);
        fclose($fh);
    }
    return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
}
