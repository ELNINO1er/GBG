<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/campaign.php';
require_once __DIR__ . '/../inc/mailer.php';

admin_require();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('campagnes.php');
}
csrf_check();

@set_time_limit(0);

$db = gbg_db();
$config = gbg_config();
$id = (int)($_POST['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM campagnes WHERE id = ?');
$stmt->execute([$id]);
$camp = $stmt->fetch();
if (!$camp) {
    flash('Campagne introuvable.', 'error');
    redirect('campagnes.php');
}
if ($camp['statut'] === 'envoyee') {
    flash('Cette campagne est deja entierement envoyee.', 'error');
    redirect('campagne-view.php?id=' . $id);
}

$canaux = explode('+', $camp['canal']);
$doEmail  = in_array('email', $canaux, true);
$doEspace = in_array('espace', $canaux, true);

// --- Publication espace : immediate au 1er lancement ---
$publiee = $doEspace ? 1 : (int)$camp['publiee'];

// --- Preparation de la file d'envoi email (une seule fois) ---
if ($doEmail && $camp['statut'] === 'brouillon') {
    $recipients = gbg_campaign_recipients($camp);
    $ins = $db->prepare(
        'INSERT INTO envois (campagne_id, cooperative_id, email, statut) VALUES (?,?,?,\'en_attente\')'
    );
    foreach ($recipients as $r) {
        $ins->execute([$id, $r['id'], $r['email']]);
    }
    $db->prepare('UPDATE campagnes SET nb_destinataires=?, statut=\'en_cours\', publiee=? WHERE id=?')
       ->execute([count($recipients), $publiee, $id]);
    $camp['statut'] = 'en_cours';
} elseif ($camp['statut'] === 'brouillon') {
    // espace seul, pas d'email
    $db->prepare('UPDATE campagnes SET statut=\'envoyee\', publiee=?, sent_at=? WHERE id=?')
       ->execute([$publiee, date('Y-m-d H:i:s'), $id]);
    flash('Campagne publiee dans l\'espace cooperatives.', 'success');
    redirect('campagne-view.php?id=' . $id);
}

// --- Traitement d'un lot (budget de temps pour eviter le timeout serveur) ---
$budgetSecondes = 20;
$debut = time();
$traites = 0;
$html = gbg_email_template($camp['sujet'], $camp['contenu']);

$select = $db->prepare(
    'SELECT id, cooperative_id, email FROM envois
     WHERE campagne_id = ? AND statut = \'en_attente\' ORDER BY id LIMIT 50'
);
$markOk  = $db->prepare('UPDATE envois SET statut=\'envoye\', sent_at=?, message_erreur=\'\' WHERE id=?');
$markKo  = $db->prepare('UPDATE envois SET statut=\'echec\', message_erreur=? WHERE id=?');

$socket = null;
$smtpError = '';
try {
    $socket = gbg_smtp_open($config);
} catch (Throwable $e) {
    $smtpError = $e->getMessage();
}

if ($socket !== null) {
    $stop = false;
    while (!$stop) {
        $select->execute([$id]);
        $lot = $select->fetchAll();
        if (!$lot) {
            break;
        }
        foreach ($lot as $env) {
            try {
                gbg_smtp_send($socket, $config, $env['email'], $camp['sujet'], $html);
                $markOk->execute([date('Y-m-d H:i:s'), $env['id']]);
            } catch (Throwable $e) {
                $markKo->execute([substr($e->getMessage(), 0, 480), $env['id']]);
            }
            $traites++;
            usleep(80000);
            if (time() - $debut >= $budgetSecondes) {
                $stop = true;
                break;
            }
        }
    }
    gbg_smtp_close($socket);
}

// --- Mise a jour des compteurs ---
$cnt = $db->prepare(
    'SELECT
        SUM(statut=\'envoye\') envoyes,
        SUM(statut=\'echec\') echecs,
        SUM(statut=\'en_attente\') restants
     FROM envois WHERE campagne_id = ?'
);
$cnt->execute([$id]);
$c = $cnt->fetch();
$restants = (int)$c['restants'];

$statut = $restants > 0 ? 'en_cours' : 'envoyee';
$sentAt = $restants > 0 ? $camp['sent_at'] : date('Y-m-d H:i:s');
$db->prepare('UPDATE campagnes SET statut=?, nb_envoyes=?, nb_echecs=?, publiee=?, sent_at=? WHERE id=?')
   ->execute([$statut, (int)$c['envoyes'], (int)$c['echecs'], $publiee, $sentAt, $id]);

if ($socket === null && $traites === 0) {
    flash('Envoi impossible : SMTP indisponible (' . e($smtpError) . '). Verifiez la configuration email.', 'error');
} elseif ($restants > 0) {
    flash("Lot traite : {$c['envoyes']} envoye(s), {$c['echecs']} echec(s). Il reste $restants email(s) — cliquez sur \"Continuer l'envoi\".", 'info');
} else {
    flash("Envoi termine : {$c['envoyes']} email(s) envoye(s)" . ((int)$c['echecs'] ? ", {$c['echecs']} echec(s)" : '') . '.', 'success');
}
redirect('campagne-view.php?id=' . $id);
