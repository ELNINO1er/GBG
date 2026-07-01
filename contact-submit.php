<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Methode non autorisee.'
    ]);
    exit;
}

function field(string $name): string
{
    return trim((string)($_POST[$name] ?? ''));
}

function clean_header(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', $value));
}

function smtp_read($socket): string
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

function smtp_command($socket, string $command, array $expected): string
{
    fwrite($socket, $command . "\r\n");
    $response = smtp_read($socket);
    $code = (int)substr($response, 0, 3);

    if (!in_array($code, $expected, true)) {
        throw new RuntimeException('SMTP error after command: ' . $command . ' / ' . trim($response));
    }

    return $response;
}

function smtp_send(array $config, string $to, string $subject, string $body, string $replyTo, string $replyName): bool
{
    $host = (string)($config['smtp_host'] ?? '');
    $port = (int)($config['smtp_port'] ?? 587);
    $encryption = (string)($config['smtp_encryption'] ?? 'tls');
    $username = (string)($config['smtp_username'] ?? '');
    $password = (string)($config['smtp_password'] ?? '');
    $fromEmail = (string)($config['from_email'] ?? $username);
    $fromName = clean_header((string)($config['from_name'] ?? 'Global Business Group'));

    if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
        return false;
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
    $socket = fsockopen($remote, $port, $errno, $errstr, 20);

    if (!$socket) {
        throw new RuntimeException('Connexion SMTP impossible: ' . $errstr);
    }

    stream_set_timeout($socket, 20);
    smtp_read($socket);
    smtp_command($socket, 'EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'gbg-ci.com'), [250]);

    if ($encryption === 'tls') {
        smtp_command($socket, 'STARTTLS', [220]);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Activation TLS impossible.');
        }
        smtp_command($socket, 'EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'gbg-ci.com'), [250]);
    }

    smtp_command($socket, 'AUTH LOGIN', [334]);
    smtp_command($socket, base64_encode($username), [334]);
    smtp_command($socket, base64_encode($password), [235]);
    smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
    smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
    smtp_command($socket, 'DATA', [354]);

    $headers = [
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . ($replyName !== '' ? clean_header($replyName) . ' <' . $replyTo . '>' : $replyTo),
        'To: ' . $to,
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $body);
    fwrite($socket, $message . "\r\n.\r\n");
    $response = smtp_read($socket);
    $code = (int)substr($response, 0, 3);

    smtp_command($socket, 'QUIT', [221]);
    fclose($socket);

    return $code === 250;
}

$config = [
    'smtp_host' => getenv('GBG_SMTP_HOST') ?: 'smtp.hostinger.com',
    'smtp_port' => (int)(getenv('GBG_SMTP_PORT') ?: 587),
    'smtp_encryption' => getenv('GBG_SMTP_ENCRYPTION') ?: 'tls',
    'smtp_username' => getenv('GBG_SMTP_USERNAME') ?: 'infos@gbg-ci.com',
    'smtp_password' => getenv('GBG_SMTP_PASSWORD') ?: '',
    'from_email' => getenv('GBG_FROM_EMAIL') ?: 'infos@gbg-ci.com',
    'from_name' => getenv('GBG_FROM_NAME') ?: 'Global Business Group',
    'to_email' => getenv('GBG_TO_EMAIL') ?: 'infos@gbg-ci.com',
];

$localConfig = __DIR__ . '/contact-config.php';
if (is_file($localConfig)) {
    $loadedConfig = require $localConfig;
    if (is_array($loadedConfig)) {
        $config = array_merge($config, $loadedConfig);
    }
}

$nom = field('nom');
$prenom = field('prenom');
$organisation = field('organisation');
$email = field('email');
$telephone = field('telephone');
$entite = field('entite');
$message = field('message');

if ($nom === '' || $email === '' || $entite === '' || $message === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Veuillez renseigner le nom, l\'email, l\'entite concernee et le message.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Veuillez saisir une adresse email valide.'
    ]);
    exit;
}

$allowedEntities = [
    'GBG',
    'GBCC',
    'SCMC',
    'KIC',
    'Restaurant Assiette Durable',
    'SCMC Chocolaterie',
];

if (!in_array($entite, $allowedEntities, true)) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Veuillez selectionner une entite valide.'
    ]);
    exit;
}

$to = clean_header((string)($config['to_email'] ?? 'infos@gbg-ci.com'));
$subject = clean_header('Demande site GBG - ' . $entite);
$replyTo = clean_header($email);
$fromName = clean_header(trim($prenom . ' ' . $nom));

$body = implode("\n", [
    'Nouvelle demande depuis le site GBG',
    '',
    'Nom : ' . $nom,
    'Prenom : ' . $prenom,
    'Organisation : ' . $organisation,
    'Email : ' . $email,
    'Telephone : ' . $telephone,
    'Entite concernee : ' . $entite,
    '',
    'Message :',
    $message,
]);

try {
    $sent = smtp_send($config, $to, $subject, $body, $replyTo, $fromName);
} catch (Throwable $exception) {
    $sent = false;
}

if (!$sent) {
    $fallbackHeaders = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . clean_header((string)$config['from_name']) . ' <' . clean_header((string)$config['from_email']) . '>',
        'Reply-To: ' . ($fromName !== '' ? $fromName . ' <' . $replyTo . '>' : $replyTo),
        'X-Mailer: PHP/' . phpversion(),
    ];

    $sent = mail($to, $subject, $body, implode("\r\n", $fallbackHeaders));
}

if (!$sent) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Le serveur n\'a pas pu envoyer le message. Verifiez la configuration email de l\'hebergement.'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Merci. Votre message a bien ete envoye a GBG.'
]);
