<?php
declare(strict_types=1);

/**
 * Envoi d'email via SMTP (reutilise la logique du formulaire de contact),
 * avec support du HTML et d'un corps texte alternatif.
 *
 * Fonction principale : gbg_send_mail($config, $to, $subject, $htmlBody).
 */

function mailer_clean_header(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', $value));
}

function mailer_smtp_read($socket): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function mailer_smtp_command($socket, string $command, array $expected): string
{
    fwrite($socket, $command . "\r\n");
    $response = mailer_smtp_read($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $expected, true)) {
        throw new RuntimeException('SMTP: ' . trim($response));
    }
    return $response;
}

/**
 * Ouvre une session SMTP authentifiee et la garde ouverte pour envoyer
 * plusieurs messages a la suite (envoi groupe efficace).
 *
 * @return resource socket SMTP pret a l'emploi (apres AUTH)
 */
function gbg_smtp_open(array $config)
{
    $host = (string)($config['smtp_host'] ?? '');
    $port = (int)($config['smtp_port'] ?? 587);
    $encryption = (string)($config['smtp_encryption'] ?? 'tls');
    $username = (string)($config['smtp_username'] ?? '');
    $password = (string)($config['smtp_password'] ?? '');

    if ($host === '' || $username === '' || $password === '') {
        throw new RuntimeException('Configuration SMTP incomplete.');
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
    $socket = fsockopen($remote, $port, $errno, $errstr, 20);
    if (!$socket) {
        throw new RuntimeException('Connexion SMTP impossible: ' . $errstr);
    }
    stream_set_timeout($socket, 30);

    mailer_smtp_read($socket);
    $ehlo = 'EHLO gbg-ci.com';
    mailer_smtp_command($socket, $ehlo, [250]);

    if ($encryption === 'tls') {
        mailer_smtp_command($socket, 'STARTTLS', [220]);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Activation TLS impossible.');
        }
        mailer_smtp_command($socket, $ehlo, [250]);
    }

    mailer_smtp_command($socket, 'AUTH LOGIN', [334]);
    mailer_smtp_command($socket, base64_encode($username), [334]);
    mailer_smtp_command($socket, base64_encode($password), [235]);

    return $socket;
}

/** Envoie un message HTML sur une session SMTP deja ouverte. */
function gbg_smtp_send($socket, array $config, string $to, string $subject, string $htmlBody): void
{
    $fromEmail = (string)($config['from_email'] ?? $config['smtp_username'] ?? '');
    $fromName  = mailer_clean_header((string)($config['from_name'] ?? 'Global Business Group'));
    $boundary  = 'gbg-' . bin2hex(random_bytes(8));

    mailer_smtp_command($socket, 'RSET', [250]);
    mailer_smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
    mailer_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
    mailer_smtp_command($socket, 'DATA', [354]);

    $textBody = trim(html_entity_decode(strip_tags(str_replace(
        ['<br>', '<br/>', '<br />', '</p>'],
        "\n",
        $htmlBody
    )), ENT_QUOTES, 'UTF-8'));

    $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers = [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'To: ' . $to,
        'Subject: ' . $subjectEnc,
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    $body  = '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($textBody)) . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $body .= '--' . $boundary . "--\r\n";

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    // Protection "dot stuffing"
    $message = preg_replace('/^\./m', '..', $message);

    fwrite($socket, $message . "\r\n.\r\n");
    $response = mailer_smtp_read($socket);
    $code = (int)substr($response, 0, 3);
    if ($code !== 250) {
        throw new RuntimeException('Message refuse: ' . trim($response));
    }
}

function gbg_smtp_close($socket): void
{
    try {
        mailer_smtp_command($socket, 'QUIT', [221]);
    } catch (Throwable $e) {
        // on ferme quand meme
    }
    if (is_resource($socket)) {
        fclose($socket);
    }
}

/** Envoi unitaire (ouvre/ferme une session) - pratique pour un test. */
function gbg_send_mail(array $config, string $to, string $subject, string $htmlBody): void
{
    $socket = gbg_smtp_open($config);
    try {
        gbg_smtp_send($socket, $config, $to, $subject, $htmlBody);
    } finally {
        gbg_smtp_close($socket);
    }
}
