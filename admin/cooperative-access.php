<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

admin_require();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cooperatives.php');
}
csrf_check();

$db = gbg_db();
$id = (int)($_POST['id'] ?? 0);
$op = $_POST['op'] ?? '';

$stmt = $db->prepare('SELECT * FROM cooperatives WHERE id = ?');
$stmt->execute([$id]);
$coop = $stmt->fetch();
if (!$coop) {
    flash('Cooperative introuvable.', 'error');
    redirect('cooperatives.php');
}

if ($op === 'revoke') {
    $db->prepare('UPDATE cooperatives SET login_username=NULL, login_password_hash=NULL WHERE id=?')
       ->execute([$id]);
    flash('Acces revoque.', 'success');
    redirect('cooperative-edit.php?id=' . $id);
}

if ($op === 'generate') {
    // Identifiant : garde l'existant sinon en genere un a partir du nom
    $username = $coop['login_username'];
    if (!$username) {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '', $coop['nom_cooperative']));
        $base = substr($base, 0, 16) ?: 'coop';
        $username = $base . $coop['id'];
    }
    // Mot de passe lisible aleatoire
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $password = '';
    for ($i = 0; $i < 10; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->prepare('UPDATE cooperatives SET login_username=?, login_password_hash=? WHERE id=?')
       ->execute([$username, $hash, $id]);

    flash("Acces cree/regenere. Identifiant : $username  |  Mot de passe : $password  (a communiquer a la cooperative, non re-affichable).", 'success');
    redirect('cooperative-edit.php?id=' . $id);
}

redirect('cooperative-edit.php?id=' . $id);
