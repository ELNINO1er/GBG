<?php
declare(strict_types=1);

/**
 * Amorce commune a toutes les pages dynamiques.
 * - Fuseau horaire
 * - Sessions durcies (httponly / samesite / secure sous HTTPS)
 * - Gestion d'erreurs adaptee prod/debug (pas de fuite de stacktrace en prod)
 *
 * A inclure en tout premier dans chaque point d'entree.
 */

require_once __DIR__ . '/db.php'; // fournit gbg_config()

$__cfg = gbg_config();
$__debug = (bool)($__cfg['debug'] ?? false);

date_default_timezone_set((string)($__cfg['timezone'] ?? 'Africa/Abidjan'));

// ---- Affichage / journalisation des erreurs ----
error_reporting(E_ALL);
if ($__debug) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Page d'erreur propre en production (evite le stacktrace brut a l'ecran)
if (!$__debug) {
    set_exception_handler(static function (Throwable $e): void {
        error_log('[GBG] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        echo '<!doctype html><meta charset="utf-8"><title>Erreur</title>'
           . '<div style="font-family:Arial,sans-serif;max-width:520px;margin:80px auto;text-align:center;color:#1c2a22">'
           . '<h1 style="color:#143c28">Une erreur est survenue</h1>'
           . '<p style="color:#6b7a70">Le service rencontre un probleme technique. Merci de reessayer dans un instant.</p>'
           . '</div>';
        exit;
    });
}

// ---- Parametres de session securises ----
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (($_SERVER['HTTPS'] ?? '') === 'on')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_name('GBGSESSID');
}
