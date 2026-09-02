<?php
/**
 * Copiez ce fichier en inc/config.local.php et adaptez les valeurs.
 * Ce fichier surcharge inc/config.php (base de donnees + SMTP).
 * Ne pas versionner config.local.php (contient des secrets).
 */
return [
    // Base de donnees (production)
    'db_host' => '127.0.0.1',
    'db_name' => 'gbg_coop',
    'db_user' => 'gbg_user',
    'db_pass' => 'CHANGEZ_MOI',

    // SMTP (meme boite que le formulaire de contact)
    'smtp_host'       => 'smtp.hostinger.com',
    'smtp_port'       => 587,
    'smtp_encryption' => 'tls',
    'smtp_username'   => 'infos@gbg-ci.com',
    'smtp_password'   => 'MOT_DE_PASSE_EMAIL',
    'from_email'      => 'infos@gbg-ci.com',
    'from_name'       => 'Global Business Group',
];
