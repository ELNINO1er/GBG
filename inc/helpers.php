<?php
declare(strict_types=1);

/**
 * Fonctions utilitaires : securite, echappement, emails, gabarit HTML.
 */

/** Echappe une chaine pour affichage HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Demarre la session si besoin (parametres de cookie definis par bootstrap.php). */
function gbg_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        require_once __DIR__ . '/bootstrap.php';
        session_start();
    }
}

/** Retourne (et cree au besoin) le jeton CSRF de la session. */
function csrf_token(): string
{
    gbg_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Champ input cache contenant le jeton CSRF. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Verifie le jeton CSRF d'une requete POST ; stoppe si invalide. */
function csrf_check(): void
{
    gbg_session();
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Requete invalide (CSRF).');
    }
}

/**
 * Nettoie et valide une cellule email qui peut contenir plusieurs adresses
 * separees par / , ; ou espace. Corrige quelques erreurs frequentes.
 *
 * @return array{primary:string, extra:array<string>, invalid:array<string>}
 */
function parse_emails(string $raw): array
{
    $raw = trim($raw);
    $primary = '';
    $extra = [];
    $invalid = [];

    if ($raw === '') {
        return ['primary' => '', 'extra' => [], 'invalid' => []];
    }

    // Separateurs possibles : / , ; espace | retour ligne
    $parts = preg_split('/[\/;,|\s]+/u', $raw) ?: [];

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        // Corrections courantes : virgule dans "gmail,com", accents parasites.
        $part = str_replace([',com', ' '], ['.com', ''], $part);
        $part = strtolower($part);

        if (filter_var($part, FILTER_VALIDATE_EMAIL)) {
            if ($primary === '') {
                $primary = $part;
            } elseif (!in_array($part, $extra, true) && $part !== $primary) {
                $extra[] = $part;
            }
        } else {
            $invalid[] = $part;
        }
    }

    return ['primary' => $primary, 'extra' => $extra, 'invalid' => $invalid];
}

/**
 * Deduit une region a partir de la localite (heuristique simple, editable).
 * Beaucoup de localites du fichier sont des villes ; on renvoie la ville
 * nettoyee comme "region" par defaut.
 */
function deduire_region(string $localite): string
{
    $l = trim($localite);
    if ($l === '') {
        return '';
    }
    // On garde la premiere partie avant / ou espace multiple.
    $l = preg_split('/[\/]/u', $l)[0] ?? $l;
    $l = trim($l);
    // Normalise la casse (MAN -> Man, san-pedro -> San-Pedro)
    return mb_convert_case($l, MB_CASE_TITLE, 'UTF-8');
}

/** Redirection interne. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Message flash simple. */
function flash(string $msg, string $type = 'info'): void
{
    gbg_session();
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function flash_render(): string
{
    gbg_session();
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $out = '';
    foreach ($_SESSION['flash'] as $f) {
        $cls = $f['type'] === 'error' ? 'flash-error'
             : ($f['type'] === 'success' ? 'flash-success' : 'flash-info');
        $out .= '<div class="flash ' . $cls . '">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}
