<?php
declare(strict_types=1);

/**
 * Assistant d'installation web (premier lancement).
 *  - Verifie la connexion a la base (via inc/config.local.php).
 *  - Cree les tables (idempotent).
 *  - Cree le premier compte administrateur.
 *  - Se verrouille automatiquement des qu'un admin existe.
 *
 * A utiliser une seule fois apres la mise en ligne, puis supprimer ce fichier.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

$step = 'form';
$errors = [];
$config = gbg_config();

/** Teste la connexion sans base puis avec base. */
function setup_try_connect(array $c): PDO
{
    return new PDO(
        sprintf('mysql:host=%s;charset=%s', $c['db_host'], $c['db_charset']),
        $c['db_user'],
        $c['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

// L'installation est-elle deja faite ?
$alreadyInstalled = false;
try {
    $db = gbg_db();
    $n = (int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($n > 0) {
        $alreadyInstalled = true;
    }
} catch (Throwable $e) {
    // base ou tables absentes : installation a faire
}

if ($alreadyInstalled) {
    // Verrou : plus rien a installer
    ?><!doctype html><meta charset="utf-8"><title>Installation</title>
    <div style="font-family:Arial,sans-serif;max-width:520px;margin:80px auto;text-align:center;color:#1c2a22">
      <h1 style="color:#143c28">Installation deja effectuee</h1>
      <p style="color:#6b7a70">Un compte administrateur existe deja. Par securite, supprimez le fichier <code>admin/setup.php</code> du serveur.</p>
      <p><a href="login.php" style="color:#1f5c3d">Aller a la connexion &rarr;</a></p>
    </div><?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $user = trim((string)($_POST['username'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    $nom  = trim((string)($_POST['nom'] ?? 'Administrateur GBG'));

    if ($user === '' || strlen($user) < 3) {
        $errors[] = 'Identifiant : 3 caracteres minimum.';
    }
    if (strlen($pass) < 8) {
        $errors[] = 'Mot de passe : 8 caracteres minimum.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (!$errors) {
        try {
            $schema = (string)file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
            $schema = preg_replace('/^\s*--.*$/m', '', $schema);
            $statements = array_filter(array_map('trim', explode(';', $schema)));

            // Strategie 1 (mutualise) : la base existe deja -> on cree juste les tables
            $pdo = null;
            try {
                $pdo = new PDO(
                    sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['db_host'], $config['db_name'], $config['db_charset']),
                    $config['db_user'], $config['db_pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                foreach ($statements as $stmt) {
                    // on saute CREATE DATABASE / USE (base deja selectionnee)
                    if (preg_match('/^\s*(CREATE\s+DATABASE|USE)\b/i', $stmt)) {
                        continue;
                    }
                    $pdo->exec($stmt);
                }
            } catch (PDOException $e) {
                // Strategie 2 (local/VPS) : base absente -> on la cree entierement
                $pdo = setup_try_connect($config);
                foreach ($statements as $stmt) {
                    if ($stmt !== '') {
                        $pdo->exec($stmt);
                    }
                }
            }

            // 2) Compte admin
            $db = gbg_db();
            $exists = (int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
            if ($exists === 0) {
                $ins = $db->prepare(
                    'INSERT INTO admin_users (username, password_hash, nom, actif, created_at)
                     VALUES (?, ?, ?, 1, ?)'
                );
                $ins->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $nom, date('Y-m-d H:i:s')]);
            }
            $step = 'done';
        } catch (Throwable $e) {
            $errors[] = 'Connexion base impossible : ' . $e->getMessage()
                . ' — verifiez inc/config.local.php (hote, nom, utilisateur, mot de passe).';
        }
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Installation - GBG</title>
<style>
body{margin:0;font-family:'Segoe UI',system-ui,Arial,sans-serif;background:#143c28;
  display:flex;min-height:100vh;align-items:center;justify-content:center;color:#1c2a22;padding:20px}
.box{background:#fff;border-radius:14px;padding:34px;width:420px;box-shadow:0 18px 50px rgba(0,0,0,.3)}
h1{font-size:20px;margin:0 0 4px;color:#143c28}
p.sub{margin:0 0 20px;color:#6b7a70;font-size:13px}
label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px}
input{width:100%;padding:11px 12px;border:1px solid #cfd8d1;border-radius:8px;font-size:14px;box-sizing:border-box}
input:focus{outline:none;border-color:#1f5c3d;box-shadow:0 0 0 3px rgba(31,92,61,.12)}
button{width:100%;margin-top:22px;background:#143c28;color:#fff;border:none;border-radius:8px;padding:12px;font-size:15px;font-weight:600;cursor:pointer}
button:hover{background:#1f5c3d}
.err{background:#fbe7e6;color:#8a1e18;border:1px solid #f0c2bf;padding:10px 12px;border-radius:8px;font-size:13px;margin-bottom:8px}
.ok{background:#e4f4ea;color:#0f5a29;border:1px solid #b6e0c4;padding:14px;border-radius:8px;font-size:14px}
code{background:#f0f5f2;padding:1px 6px;border-radius:4px}
.warn{font-size:12px;color:#8a1e18;margin-top:14px}
</style>
</head>
<body>
<div class="box">
<?php if ($step === 'done'): ?>
  <h1>Installation terminee</h1>
  <div class="ok">
    Base de donnees et compte administrateur crees.
  </div>
  <p class="warn">&#9888; Important : supprimez maintenant le fichier <code>admin/setup.php</code> du serveur pour des raisons de securite.</p>
  <p style="text-align:center;margin-top:18px"><a href="login.php" style="color:#1f5c3d;font-weight:600">Se connecter au back-office &rarr;</a></p>
<?php else: ?>
  <h1>Installation GBG</h1>
  <p class="sub">Creation de la base et du premier compte administrateur.</p>
  <?php foreach ($errors as $er): ?><div class="err"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" action="setup.php">
    <?= csrf_field() ?>
    <label>Nom affiche</label>
    <input name="nom" value="<?= e($_POST['nom'] ?? 'Administrateur GBG') ?>">
    <label>Identifiant administrateur *</label>
    <input name="username" value="<?= e($_POST['username'] ?? '') ?>" required>
    <label>Mot de passe * <span style="font-weight:400;color:#6b7a70">(8 caracteres min.)</span></label>
    <input name="password" type="password" required>
    <label>Confirmer le mot de passe *</label>
    <input name="password2" type="password" required>
    <button type="submit">Installer</button>
  </form>
  <p class="sub" style="margin-top:16px">Prerequis : la base doit exister cote hebergeur (hPanel) et ses identifiants renseignes dans <code>inc/config.local.php</code>.</p>
<?php endif; ?>
</div>
</body>
</html>
