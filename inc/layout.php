<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Gabarit HTML partage du back-office GBG.
 * admin_header($titre, $active) / admin_footer().
 */

function admin_header(string $titre, string $active = ''): void
{
    $admin = $_SESSION['admin'] ?? ['nom' => ''];
    $nav = [
        'index.php'        => 'Tableau de bord',
        'cooperatives.php' => 'Cooperatives',
        'campagnes.php'    => 'Campagnes',
        'parametres.php'   => 'Parametres',
    ];
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titre) ?> - GBG Admin</title>
<link rel="shortcut icon" href="../assets/img/logo/favcion.png">
<style>
:root{--vert:#0d3b29;--vert2:#176344;--or:#d1aa45;--bg:#f2f6f3;--txt:#17231d;--muted:#66756d;--line:#dce5df;--danger:#b3261e;--ok:#1f7a3d;}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,Arial,sans-serif;background:var(--bg);color:var(--txt)}
a{color:var(--vert2)}
.topbar{background:linear-gradient(110deg,#0a3021,var(--vert));color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 28px;height:72px;box-shadow:0 5px 18px rgba(13,59,41,.16);position:sticky;top:0;z-index:20}
.topbar .brand{font-weight:700;letter-spacing:.5px}
.topbar .brand span{color:var(--or)}
.topbar .brand-logo{display:flex;align-items:center;color:#fff;font-weight:700;text-decoration:none}
.topbar .brand-logo img{height:44px;width:176px;object-fit:contain;display:block}
.topbar nav{display:flex;gap:4px;flex:1;margin:0 32px}
.topbar nav a{color:#dfe7e1;text-decoration:none;padding:10px 15px;border-radius:9px;font-size:14px;font-weight:600}
.topbar nav a:hover{background:rgba(255,255,255,.1)}
.topbar nav a.active{background:rgba(255,255,255,.13);color:#fff}
.topbar .user{font-size:13px;color:#cdd8d1}
.topbar .user{display:flex;align-items:center;gap:14px}.topbar .user a{margin:0}.topbar .user .account{color:#e8f0eb;text-decoration:none}.topbar .user .logout{color:#fff;background:#a64035;padding:9px 13px;border-radius:9px;text-decoration:none;font-weight:700}
.wrap{max-width:1180px;margin:34px auto;padding:0 22px}
h1{font-size:22px;margin:0 0 4px}
.sub{color:var(--muted);font-size:14px;margin:0 0 22px}
.card{background:#fff;border:1px solid var(--line);border-radius:15px;padding:24px;margin-bottom:20px;box-shadow:0 7px 24px rgba(20,61,43,.055)}
.card h2{font-size:16px;margin:0 0 14px}
.grid{display:grid;gap:16px}
.grid.cols-4{grid-template-columns:repeat(4,1fr)}
.grid.cols-3{grid-template-columns:repeat(3,1fr)}
.grid.cols-2{grid-template-columns:repeat(2,1fr)}
@media(max-width:820px){.grid.cols-4,.grid.cols-3,.grid.cols-2{grid-template-columns:1fr 1fr}}
.stat{background:linear-gradient(145deg,#fff,#f9fcfa);border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:0 5px 16px rgba(20,61,43,.04)}
.stat .n{font-size:28px;font-weight:700;color:var(--vert)}
.stat .l{font-size:13px;color:var(--muted);margin-top:2px}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--line);vertical-align:top}
th{background:#fafbfa;color:var(--muted);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.4px}
tr:hover td{background:#fbfcfb}
.badge{display:inline-block;font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600}
.badge.ok{background:#e4f4ea;color:var(--ok)}
.badge.no{background:#fbe7e6;color:var(--danger)}
.badge.grey{background:#eceff0;color:var(--muted)}
.btn{display:inline-block;background:var(--vert);color:#fff;border:none;border-radius:7px;padding:10px 18px;font-size:14px;cursor:pointer;text-decoration:none;font-weight:600}
.btn:hover{background:var(--vert2)}
.btn.sec{background:#fff;color:var(--vert);border:1px solid var(--vert)}
.btn.sec:hover{background:#f0f5f2}
.btn.sm{padding:6px 12px;font-size:13px}
.btn.danger{background:var(--danger)}
input,select,textarea{width:100%;padding:10px 12px;border:1px solid #cfd8d1;border-radius:7px;font-size:14px;font-family:inherit;background:#fff}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--vert2);box-shadow:0 0 0 3px rgba(31,92,61,.12)}
label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px;color:#33453b}
.field{margin-bottom:6px}
.row{display:flex;gap:16px;flex-wrap:wrap}
.row>div{flex:1;min-width:180px}
.flash{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px}
.flash-success{background:#e4f4ea;color:#0f5a29;border:1px solid #b6e0c4}
.flash-error{background:#fbe7e6;color:#8a1e18;border:1px solid #f0c2bf}
.flash-info{background:#eaf1fb;color:#1c4785;border:1px solid #c4d6f0}
.toolbar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.toolbar .spacer{flex:1}
.muted{color:var(--muted);font-size:13px}
.tablewrap{overflow-x:auto}
.pill-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}
.pill-filter a{font-size:13px;padding:5px 12px;border:1px solid var(--line);border-radius:20px;text-decoration:none;color:var(--muted);background:#fff}
.pill-filter a.on{background:var(--vert);color:#fff;border-color:var(--vert)}
.smart-select{position:relative}.smart-select>select{display:none}.smart-trigger{width:100%;display:flex;justify-content:space-between;align-items:center;padding:11px 13px;border:1px solid #cfd8d1;border-radius:9px;background:#fff;color:var(--txt);font:inherit;cursor:pointer;text-align:left}.smart-trigger:after{content:'⌄';font-weight:800}.smart-select.open .smart-trigger{border-color:var(--vert2);box-shadow:0 0 0 3px rgba(31,92,61,.12)}.smart-panel{display:none;position:absolute;z-index:100;left:0;right:0;top:calc(100% + 6px);background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 18px 45px rgba(14,47,34,.18);padding:9px}.smart-select.open .smart-panel{display:block}.smart-search{margin-bottom:7px}.smart-options{max-height:245px;overflow:auto}.smart-option{display:block;width:100%;padding:9px 10px;border:0;border-radius:7px;background:#fff;text-align:left;cursor:pointer;font:inherit;color:var(--txt)}.smart-option:hover,.smart-option.selected{background:#eaf4ee;color:var(--vert);font-weight:700}.smart-select.multiple .smart-option:before{content:'□';display:inline-block;width:22px;color:var(--muted)}.smart-select.multiple .smart-option.selected:before{content:'✓';color:var(--ok)}
.modal-backdrop{display:none;position:fixed;inset:0;z-index:1000;background:rgba(7,24,17,.62);padding:20px;place-items:center}.modal-backdrop.open{display:grid}.modal-box{width:min(430px,100%);background:#fff;border-radius:16px;padding:26px;box-shadow:0 24px 70px rgba(0,0,0,.3)}.modal-box h2{margin:0 0 8px}.modal-box p{color:var(--muted);line-height:1.55}.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}
@media(max-width:720px){.topbar{height:auto;min-height:68px;padding:12px 16px;flex-wrap:wrap}.topbar .brand-logo img{width:145px;height:38px}.topbar nav{order:3;width:100%;margin:10px 0 0;overflow-x:auto}.topbar nav a{white-space:nowrap}.topbar .user .account{display:none}.grid.cols-4,.grid.cols-3,.grid.cols-2{grid-template-columns:1fr}.wrap{margin-top:22px}}
</style>
</head>
<body>
<div class="topbar">
  <a href="index.php" class="brand-logo"><img src="../assets/img/logo/logo-light.svg" alt="Global Business Group"></a>
  <nav>
    <?php foreach ($nav as $href => $label): ?>
      <a href="<?= e($href) ?>" class="<?= $active === $href ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="user"><a class="account" href="compte.php"><?= e($admin['nom']) ?></a><a class="logout" href="logout.php">Déconnexion</a></div>
</div>
<div class="wrap">
<?= flash_render() ?>
<?php
}

function admin_footer(): void
{
    ?>
</div>
<div class="modal-backdrop" id="gbg-confirm" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
  <div class="modal-box"><h2 id="confirm-title">Confirmer l'action</h2><p id="confirm-message"></p><div class="modal-actions"><button class="btn sec" type="button" id="confirm-cancel">Annuler</button><button class="btn danger" type="button" id="confirm-ok">Confirmer</button></div></div>
</div>
<script>
document.querySelectorAll('select').forEach(function(select){
  var multiple=select.multiple;var wrap=document.createElement('div');wrap.className='smart-select'+(multiple?' multiple':'');select.parentNode.insertBefore(wrap,select);wrap.appendChild(select);
  var trigger=document.createElement('button');trigger.type='button';trigger.className='smart-trigger';wrap.appendChild(trigger);
  var panel=document.createElement('div');panel.className='smart-panel';panel.innerHTML='<input class="smart-search" type="search" placeholder="Rechercher..."><div class="smart-options"></div>';wrap.appendChild(panel);
  var box=panel.querySelector('.smart-options'),search=panel.querySelector('.smart-search');
  function render(q){box.innerHTML='';Array.from(select.options).forEach(function(o){if(q&&o.text.toLowerCase().indexOf(q.toLowerCase())<0)return;var b=document.createElement('button');b.type='button';b.className='smart-option'+(o.selected?' selected':'');b.textContent=o.text;b.onclick=function(){if(multiple){o.selected=!o.selected;}else{select.value=o.value;wrap.classList.remove('open');}select.dispatchEvent(new Event('change',{bubbles:true}));};box.appendChild(b);});}
  function sync(){var chosen=Array.from(select.selectedOptions);trigger.textContent=multiple?(chosen.length?chosen.length+' region'+(chosen.length>1?'s':'')+' selectionnee'+(chosen.length>1?'s':''):(select.dataset.placeholder||'Toutes les regions')):(select.options[select.selectedIndex]?select.options[select.selectedIndex].text:'');render(search?search.value:'');}sync();
  trigger.onclick=function(){document.querySelectorAll('.smart-select.open').forEach(function(x){if(x!==wrap)x.classList.remove('open')});wrap.classList.toggle('open');if(wrap.classList.contains('open')){search.value='';render('');search.focus();}};
  search.oninput=function(){render(search.value)};select.addEventListener('change',sync);
});
document.addEventListener('click',function(e){if(!e.target.closest('.smart-select'))document.querySelectorAll('.smart-select.open').forEach(function(x){x.classList.remove('open')});});
var modal=document.getElementById('gbg-confirm'),pending=null;
document.querySelectorAll('[data-confirm]').forEach(function(el){var event=el.tagName==='FORM'?'submit':'click';el.addEventListener(event,function(e){if(el.dataset.confirmed==='1')return;e.preventDefault();pending=el;document.getElementById('confirm-message').textContent=el.dataset.confirm;modal.classList.add('open');});});
document.getElementById('confirm-cancel').onclick=function(){modal.classList.remove('open');pending=null};
document.getElementById('confirm-ok').onclick=function(){if(!pending)return;pending.dataset.confirmed='1';modal.classList.remove('open');pending.tagName==='FORM'?pending.requestSubmit():pending.click();};
</script>
</body>
</html>
<?php
}
