<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Authentification pour deux espaces distincts :
 *  - admin        (back-office GBG)
 *  - cooperative  (espace des cooperatives)
 * Les deux utilisent la meme session mais des cles differentes.
 */

// ---------------- ADMIN ----------------

/**
 * Normalise uniquement l'identifiant saisi.
 * Les claviers mobiles et les copier-coller peuvent ajouter des espaces
 * insecables ou modifier la casse sans que l'utilisateur le remarque.
 */
function auth_normalize_username(string $username): string
{
    $username = str_replace(["\u{00A0}", "\u{202F}", "\u{2007}"], ' ', $username);
    $username = preg_replace('/\s+/u', ' ', trim($username)) ?? trim($username);
    return mb_strtolower($username, 'UTF-8');
}

function admin_current(): ?array
{
    gbg_session();
    return $_SESSION['admin'] ?? null;
}

function admin_login(string $username, string $password): bool
{
    $wantedUsername = auth_normalize_username($username);
    $stmt = gbg_db()->query('SELECT * FROM admin_users WHERE actif = 1');
    $user = null;
    foreach ($stmt->fetchAll() as $candidate) {
        if (hash_equals(auth_normalize_username((string)$candidate['username']), $wantedUsername)) {
            $user = $candidate;
            break;
        }
    }

    if (!$user) {
        return false;
    }

    // On essaie d'abord le mot de passe exact. Un espace exterieur ajoute
    // involontairement par un copier-coller mobile est ignore en secours.
    $passwordOk = password_verify($password, $user['password_hash']);
    $trimmedPassword = trim($password);
    if (!$passwordOk && $trimmedPassword !== $password) {
        $passwordOk = password_verify($trimmedPassword, $user['password_hash']);
    }
    if (!$passwordOk) {
        return false;
    }

    gbg_session();
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id'  => (int)$user['id'],
        'nom' => $user['nom'] !== '' ? $user['nom'] : $user['username'],
    ];

    $upd = gbg_db()->prepare('UPDATE admin_users SET last_login = ? WHERE id = ?');
    $upd->execute([date('Y-m-d H:i:s'), $user['id']]);

    return true;
}

function admin_require(): array
{
    $admin = admin_current();
    if (!$admin) {
        redirect('login.php');
    }
    return $admin;
}

function admin_logout(): void
{
    gbg_session();
    unset($_SESSION['admin']);
}

// ---------------- COOPERATIVE ----------------

function coop_current(): ?array
{
    gbg_session();
    return $_SESSION['coop'] ?? null;
}

function coop_login(string $username, string $password): bool
{
    $stmt = gbg_db()->prepare(
        'SELECT * FROM cooperatives WHERE login_username = ? AND actif = 1 LIMIT 1'
    );
    $stmt->execute([$username]);
    $coop = $stmt->fetch();

    if (!$coop || empty($coop['login_password_hash'])
        || !password_verify($password, $coop['login_password_hash'])) {
        return false;
    }

    gbg_session();
    session_regenerate_id(true);
    $_SESSION['coop'] = [
        'id'     => (int)$coop['id'],
        'nom'    => $coop['nom_cooperative'],
        'region' => $coop['region'],
    ];
    return true;
}

function coop_require(): array
{
    $coop = coop_current();
    if (!$coop) {
        redirect('login.php');
    }
    return $coop;
}

function coop_logout(): void
{
    gbg_session();
    unset($_SESSION['coop']);
}
