<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Destinataires email d'une campagne : cooperatives actives, email valide,
 * eventuellement filtrees par region.
 *
 * @return array<int,array>
 */
function gbg_campaign_recipients(array $camp): array
{
    $db = gbg_db();
    $sql = 'SELECT id, nom_cooperative, email, emails_extra, region
            FROM cooperatives
            WHERE actif = 1 AND email_valide = 1';
    $params = [];
    $regions = gbg_campaign_regions($camp);
    if ($regions) {
        $sql .= ' AND region IN (' . implode(',', array_fill(0, count($regions), '?')) . ')';
        array_push($params, ...$regions);
    }
    $sql .= ' ORDER BY nom_cooperative';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Nombre de cooperatives ciblees (toutes actives) pour publication espace. */
function gbg_campaign_audience_count(array $camp): int
{
    $db = gbg_db();
    $sql = 'SELECT COUNT(*) FROM cooperatives WHERE actif = 1';
    $params = [];
    $regions = gbg_campaign_regions($camp);
    if ($regions) {
        $sql .= ' AND region IN (' . implode(',', array_fill(0, count($regions), '?')) . ')';
        array_push($params, ...$regions);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/** @return array<int,string> Regions ciblees ; tableau vide = toutes les regions. */
function gbg_campaign_regions(array $camp): array
{
    $raw = trim((string)($camp['filtre_region'] ?? ''));
    if ($raw === '') {
        return [];
    }
    return array_values(array_filter(array_unique(array_map('trim', explode('|', $raw)))));
}

function gbg_campaign_regions_label(array $camp): string
{
    $regions = gbg_campaign_regions($camp);
    return $regions ? implode(', ', $regions) : 'Toutes les regions';
}

/**
 * Enrobe le contenu d'une campagne dans un gabarit email HTML aux couleurs GBG.
 */
function gbg_email_template(string $sujet, string $contenu): string
{
    $sujetEsc = e($sujet);
    // Le contenu est du HTML simple saisi par l'admin ; on l'insere tel quel.
    return <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"></head>
<body style="margin:0;background:#f4f6f4;font-family:Arial,Helvetica,sans-serif;color:#1c2a22;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f4;padding:24px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8e3;">
        <tr><td style="background:#143c28;padding:22px 28px;">
          <span style="color:#fff;font-size:18px;font-weight:bold;letter-spacing:.5px;">GLOBAL BUSINESS <span style="color:#c8a24b;">GROUP</span></span>
        </td></tr>
        <tr><td style="padding:28px;">
          <h1 style="font-size:19px;color:#143c28;margin:0 0 18px;">{$sujetEsc}</h1>
          <div style="font-size:15px;line-height:1.6;color:#2a3a30;">{$contenu}</div>
        </td></tr>
        <tr><td style="background:#f0f5f2;padding:18px 28px;font-size:12px;color:#6b7a70;">
          Global Business Group SA &middot; Riviera Triangle, Abidjan &middot; infos@gbg-ci.com<br>
          Ce message vous est adresse dans le cadre du partenariat avec les cooperatives.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
}
