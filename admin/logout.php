<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
admin_logout();
redirect('../connexion.php?deconnecte=1&role=admin');
