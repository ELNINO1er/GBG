<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/layout.php';

admin_require();
$db = gbg_db();

/** Genere un mot de passe lisible. */
function gbg_gen_password(int $len = 10): string
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $p = '';
    for ($i = 0; $i < $len; $i++) {
        $p .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $p;
}

/** Construit un identifiant unique a partir du nom. */
function gbg_make_username(string $nom, int $id): string
{
    $base = strtolower(preg_replace('/[^a-z0-9]+/i', '', $nom));
    $base = substr($base, 0, 16) ?: 'coop';
    return $base . $id;
}

// ---- Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $op = $_POST['op'] ?? '';

    // Genere les acces manquants et renvoie un CSV telechargeable
    if ($op === 'generate_all' || $op === 'regenerate_all') {
        $onlyMissing = ($op === 'generate_all');
        $sql = 'SELECT id, nom_cooperative, region, login_username FROM cooperatives WHERE actif = 1';
        if ($onlyMissing) {
            $sql .= ' AND login_username IS NULL';
        }
        $sql .= ' ORDER BY nom_cooperative';
        $coops = $db->query($sql)->fetchAll();

        $upd = $db->prepare('UPDATE cooperatives SET login_username=?, login_password_hash=? WHERE id=?');
        $rows = [];
        foreach ($coops as $c) {
            $username = $c['login_username'] ?: gbg_make_username($c['nom_cooperative'], (int)$c['id']);
            $password = gbg_gen_password();
            $upd->execute([$username, password_hash($password, PASSWORD_DEFAULT), $c['id']]);
            $rows[] = [$c['nom_cooperative'], $c['region'], $username, $password];
        }

        // Sortie CSV (separateur ; pour Excel FR)
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="acces-cooperatives-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
        fputcsv($out, ['Cooperative', 'Region', 'Identifiant', 'Mot de passe'], ';');
        foreach ($rows as $r) {
            fputcsv($out, $r, ';');
        }
        fclose($out);
        exit;
    }
}

// ---- Statistiques ----
$totalActives = (int)$db->query('SELECT COUNT(*) FROM cooperatives WHERE actif=1')->fetchColumn();
$avecAcces    = (int)$db->query('SELECT COUNT(*) FROM cooperatives WHERE actif=1 AND login_username IS NOT NULL')->fetchColumn();
$sansAcces    = $totalActives - $avecAcces;

$liste = $db->query(
    'SELECT nom_cooperative, region, login_username FROM cooperatives WHERE actif=1 ORDER BY nom_cooperative'
)->fetchAll();

admin_header('Acces cooperatives', 'cooperatives.php');
?>
<h1>Acces a l'espace cooperatives</h1>
<p class="sub">Generez en une fois les identifiants de connexion de toutes les cooperatives.</p>

<div class="grid cols-3">
  <div class="stat"><div class="n"><?= $totalActives ?></div><div class="l">Cooperatives actives</div></div>
  <div class="stat"><div class="n"><?= $avecAcces ?></div><div class="l">Acces deja crees</div></div>
  <div class="stat"><div class="n"><?= $sansAcces ?></div><div class="l">Sans acces</div></div>
</div>

<div class="card">
  <h2>Generer les acces</h2>
  <p class="muted">Le mot de passe n'est visible qu'au moment de la generation (il est ensuite stocke chiffre). Le fichier CSV telecharge contient les identifiants + mots de passe a communiquer a chaque cooperative. Conservez-le en lieu sur.</p>
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:14px">
    <form method="post" action="acces-coop.php">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="generate_all">
      <button class="btn" type="submit" <?= $sansAcces === 0 ? 'disabled' : '' ?>>
        Generer les <?= $sansAcces ?> acces manquants + telecharger CSV
      </button>
    </form>
    <form method="post" action="acces-coop.php" onsubmit="return confirm('Regenerer TOUS les mots de passe ? Les anciens ne fonctionneront plus.');">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="regenerate_all">
      <button class="btn sec" type="submit">Tout regenerer (nouveaux mots de passe)</button>
    </form>
  </div>
</div>

<div class="card">
  <h2>Etat des acces (<?= count($liste) ?>)</h2>
  <div class="tablewrap">
  <table>
    <thead><tr><th>Cooperative</th><th>Region</th><th>Identifiant</th><th>Acces</th></tr></thead>
    <tbody>
    <?php foreach ($liste as $c): ?>
      <tr>
        <td><?= e($c['nom_cooperative']) ?></td>
        <td><?= e($c['region']) ?></td>
        <td><?= $c['login_username'] ? e($c['login_username']) : '<span class="muted">-</span>' ?></td>
        <td><?= $c['login_username'] ? '<span class="badge ok">actif</span>' : '<span class="badge grey">non</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php
admin_footer();
