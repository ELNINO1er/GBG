<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/campaign.php';

admin_require();
$id = (int)($_GET['id'] ?? 0);
gbg_ensure_campaign_documents_schema();
$stmt = gbg_db()->prepare('SELECT * FROM campagne_documents WHERE id = ?');
$stmt->execute([$id]);
$document = $stmt->fetch();
if (!$document) {
    http_response_code(404);
    exit('Document introuvable.');
}
$path = gbg_campaign_document_storage_dir() . '/' . basename((string)$document['nom_stockage']);
if (!is_file($path)) {
    http_response_code(404);
    exit('Fichier introuvable.');
}
$name = preg_replace('/[^\pL\pN._ -]+/u', '_', (string)$document['nom_original']) ?: 'document';
header('Content-Type: ' . $document['mime_type']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="document"; filename*=UTF-8\'\'' . rawurlencode($name));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
