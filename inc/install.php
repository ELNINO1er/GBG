<?php
declare(strict_types=1);

/**
 * Installation / mise a jour de la base GBG.
 * Usage CLI :
 *   php inc/install.php [username_admin] [motdepasse_admin]
 *
 * - Cree la base et les tables (idempotent).
 * - Cree un compte admin s'il n'en existe aucun.
 */

require_once __DIR__ . '/db.php';

$c = gbg_config();

// 1) Connexion sans base pour la creer
$pdo = new PDO(
    sprintf('mysql:host=%s;charset=%s', $c['db_host'], $c['db_charset']),
    $c['db_user'],
    $c['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$schema = file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "schema.sql introuvable\n");
    exit(1);
}

// Retire les lignes de commentaire "--" avant de decouper
$schema = preg_replace('/^\s*--.*$/m', '', $schema);

// Execute chaque instruction
foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    $pdo->exec($stmt);
}
echo "Base et tables OK ({$c['db_name']}).\n";

// 2) Compte admin par defaut
$db = gbg_db();
$count = (int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

if ($count === 0) {
    $username = $argv[1] ?? 'admin';
    $password = $argv[2] ?? 'GbgAdmin2026!';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $db->prepare(
        'INSERT INTO admin_users (username, password_hash, nom, actif, created_at)
         VALUES (?, ?, ?, 1, ?)'
    );
    $ins->execute([$username, $hash, 'Administrateur GBG', date('Y-m-d H:i:s')]);
    echo "Compte admin cree : {$username} / {$password}\n";
    echo ">> Changez ce mot de passe apres la premiere connexion.\n";
} else {
    echo "Compte(s) admin deja present(s) : {$count}\n";
}
