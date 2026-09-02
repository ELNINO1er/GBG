<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/auth.php';
coop_logout();
redirect('login.php');
