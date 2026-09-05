<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

$coop = coop_require();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $stmt = gbg_db()->prepare('SELECT login_password_hash FROM cooperatives WHERE id = ?');
    $stmt->execute([$coop['id']]);
    $hash = (string)$stmt->fetchColumn();
    if (!password_verify($current, $hash)) {
        $error = 'Le mot de passe actuel est incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'Le nouveau mot de passe doit contenir au moins 8 caracteres.';
    } elseif ($new !== $confirm) {
        $error = 'La confirmation ne correspond pas au nouveau mot de passe.';
    } else {
        $upd = gbg_db()->prepare('UPDATE cooperatives SET login_password_hash=?, updated_at=? WHERE id=?');
        $upd->execute([password_hash($new, PASSWORD_DEFAULT), date('Y-m-d H:i:s'), $coop['id']]);
        $success = 'Votre mot de passe a bien ete modifie.';
    }
}
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Mon compte - GBG</title><link rel="shortcut icon" href="../assets/img/logo/favcion.png">
<style>:root{--g:#0d3b29;--g2:#176344;--bg:#f2f6f3;--line:#dce5df;--muted:#66756d}*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:"Segoe UI",system-ui,Arial;color:#17231d}.top{height:72px;padding:0 28px;background:var(--g);display:flex;align-items:center;justify-content:space-between}.top img{width:176px}.top a{color:#fff;text-decoration:none}.wrap{max-width:640px;margin:38px auto;padding:0 20px}.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:28px;box-shadow:0 8px 26px rgba(20,61,43,.06)}h1{margin:0 0 5px}.sub{color:var(--muted);margin:0 0 24px}label{display:block;font-size:13px;font-weight:700;margin:15px 0 6px}input{width:100%;padding:12px;border:1px solid #cbd8d0;border-radius:9px;font-size:15px}button{margin-top:22px;background:var(--g);color:#fff;border:0;border-radius:9px;padding:12px 18px;font-weight:700}.msg{padding:12px;border-radius:9px;margin-bottom:15px}.ok{background:#e7f5eb;color:#236438}.err{background:#fbe9e7;color:#8c2820}.back{display:inline-block;margin-top:18px;color:var(--g2)}@media(max-width:640px){.top{height:62px;padding:9px 12px;position:sticky;top:0;z-index:10}.top img{width:128px}.top>a:last-child{padding:9px 10px;border-radius:8px;background:#a64035;font-size:12px;font-weight:700}.wrap{margin:18px auto;padding:0 12px}.card{padding:20px 16px;border-radius:13px}h1{font-size:22px}.sub{font-size:13px;line-height:1.5;margin-bottom:18px}input{padding:13px 12px;font-size:16px}button{width:100%;min-height:48px;font-size:14px}.back{display:block;text-align:center;padding:10px}}</style></head>
<body><header class="top"><a href="index.php"><img src="../assets/img/logo/logo-light.svg" alt="Global Business Group"></a><a href="logout.php">Deconnexion</a></header><main class="wrap"><div class="card"><h1>Mon compte</h1><p class="sub"><?= e($coop['nom']) ?> · Modifiez votre mot de passe en toute securite.</p><?php if($error):?><div class="msg err"><?= e($error) ?></div><?php endif;?><?php if($success):?><div class="msg ok"><?= e($success) ?></div><?php endif;?><form method="post"><?= csrf_field() ?><label>Mot de passe actuel</label><input name="current_password" type="password" required autocomplete="current-password"><label>Nouveau mot de passe</label><input name="new_password" type="password" minlength="8" required autocomplete="new-password"><label>Confirmer le nouveau mot de passe</label><input name="confirm_password" type="password" minlength="8" required autocomplete="new-password"><button type="submit">Modifier mon mot de passe</button></form><a class="back" href="index.php">← Retour aux publications</a></div></main></body></html>
