<?php
declare(strict_types=1);

/**
 * Configuration centrale de la partie dynamique GBG (back-office + espace coop).
 *
 * Les valeurs sensibles (mot de passe base, SMTP) peuvent etre surchargees
 * sans modifier ce fichier via inc/config.local.php (non versionne) ou via
 * des variables d'environnement.
 */

// -------- Base de donnees --------
$config = [
    'db_host' => getenv('GBG_DB_HOST') ?: '127.0.0.1',
    'db_name' => getenv('GBG_DB_NAME') ?: 'gbg_coop',
    'db_user' => getenv('GBG_DB_USER') ?: 'root',
    'db_pass' => getenv('GBG_DB_PASS') ?: '',
    'db_charset' => 'utf8mb4',

    // -------- SMTP (reutilise la meme boite que le formulaire de contact) --------
    'smtp_host'       => getenv('GBG_SMTP_HOST') ?: 'smtp.hostinger.com',
    'smtp_port'       => (int)(getenv('GBG_SMTP_PORT') ?: 587),
    'smtp_encryption' => getenv('GBG_SMTP_ENCRYPTION') ?: 'tls',
    'smtp_username'   => getenv('GBG_SMTP_USERNAME') ?: 'infos@gbg-ci.com',
    'smtp_password'   => getenv('GBG_SMTP_PASSWORD') ?: '',
    'from_email'      => getenv('GBG_FROM_EMAIL') ?: 'infos@gbg-ci.com',
    'from_name'       => getenv('GBG_FROM_NAME') ?: 'Global Business Group',

    // -------- Divers --------
    'app_name' => 'GBG - Communication cooperatives',
    // debug = true uniquement en local (affiche les erreurs). false en production.
    'debug'    => (getenv('GBG_DEBUG') === '1'),
    'timezone' => 'Africa/Abidjan',
];

// Surcharge par le fichier de config de contact deja present (SMTP)
$contactConfig = dirname(__DIR__) . '/contact-config.php';
if (is_file($contactConfig)) {
    $loaded = require $contactConfig;
    if (is_array($loaded)) {
        $config = array_merge($config, $loaded);
    }
}

// Surcharge locale dediee (prioritaire)
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    $loaded = require $localConfig;
    if (is_array($loaded)) {
        $config = array_merge($config, $loaded);
    }
}

return $config;
