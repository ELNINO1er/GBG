<?php
declare(strict_types=1);

/**
 * Lecteur XLSX minimal (sans dependance externe).
 * Lit toutes les feuilles et renvoie leurs lignes sous forme de tableaux.
 *
 * gbg_read_xlsx($path) => [ 'sheet1' => [[c1,c2,...],[...]], 'sheet2' => [...] ]
 */
function gbg_read_xlsx(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Fichier introuvable: ' . $path);
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Impossible d\'ouvrir le classeur.');
    }

    // Table des chaines partagees
    $shared = [];
    if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        if (preg_match_all('/<si>(.*?)<\/si>/s', $ss, $m)) {
            foreach ($m[1] as $si) {
                preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $si, $t);
                $shared[] = html_entity_decode(
                    strip_tags(implode('', $t[1])),
                    ENT_QUOTES | ENT_XML1,
                    'UTF-8'
                );
            }
        }
    }

    $colIdx = static function (string $ref): int {
        preg_match('/^([A-Z]+)/', $ref, $mm);
        $letters = $mm[1] ?? 'A';
        $n = 0;
        foreach (str_split($letters) as $c) {
            $n = $n * 26 + (ord($c) - 64);
        }
        return $n - 1;
    };

    $sheets = [];
    for ($i = 1; $i <= 50; $i++) {
        $xml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
        if ($xml === false) {
            continue;
        }
        $rows = [];
        if (preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $xml, $rowMatches)) {
            foreach ($rowMatches[1] as $rowXml) {
                preg_match_all(
                    '/<c[^>]*r="([A-Z]+\d+)"(?:[^>]*t="(\w+)")?[^>]*>(?:<v>(.*?)<\/v>|<is>(?:<t[^>]*>(.*?)<\/t>)?<\/is>)?/s',
                    $rowXml,
                    $cells,
                    PREG_SET_ORDER
                );
                $line = [];
                $max = -1;
                foreach ($cells as $c) {
                    $idx = $colIdx($c[1]);
                    $type = $c[2] ?? '';
                    $val = $c[3] ?? '';
                    if ($type === 's' && $val !== '') {
                        $val = $shared[(int)$val] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $val = $c[4] ?? '';
                    }
                    $line[$idx] = trim((string)$val);
                    if ($idx > $max) {
                        $max = $idx;
                    }
                }
                $flat = [];
                for ($k = 0; $k <= $max; $k++) {
                    $flat[] = $line[$k] ?? '';
                }
                $rows[] = $flat;
            }
        }
        $sheets["sheet{$i}"] = $rows;
    }
    $zip->close();
    return $sheets;
}
