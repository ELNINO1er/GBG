# Deploiement en ligne (Hostinger mutualise)

Guide de mise en production du module de communication cooperatives sur
`gbg-ci.com`. Duree estimee : 15-20 minutes.

---

## 1. Envoyer les fichiers sur le serveur

Via le **Gestionnaire de fichiers** de hPanel (ou FTP), copiez tout le contenu
du projet dans le dossier `public_html/` (a la racine du domaine). Les nouveaux
dossiers/fichiers a envoyer :

```
inc/            admin/          espace/         sql/
.htaccess (mis a jour)          .gitignore
```

> Ne pas envoyer `inc/config.local.php` s'il contient vos identifiants locaux :
> vous le recreerez directement sur le serveur (etape 3).

## 2. Creer la base de donnees (hPanel)

1. hPanel -> **Bases de donnees -> Bases de donnees MySQL**.
2. Creez une base (ex. `u123_gbg`) et un utilisateur avec un mot de passe fort.
3. Notez : **nom de la base**, **nom d'utilisateur**, **mot de passe**, **hote**
   (souvent `localhost` chez Hostinger).

## 3. Configurer les acces (base + email)

Sur le serveur, copiez `inc/config.local.example.php` en
**`inc/config.local.php`** et renseignez :

```php
return [
    'db_host' => 'localhost',
    'db_name' => 'u123_gbg',
    'db_user' => 'u123_gbguser',
    'db_pass' => 'VOTRE_MOT_DE_PASSE_BASE',

    'smtp_host'       => 'smtp.hostinger.com',
    'smtp_port'       => 587,
    'smtp_encryption' => 'tls',
    'smtp_username'   => 'infos@gbg-ci.com',
    'smtp_password'   => 'MOT_DE_PASSE_BOITE_EMAIL',
    'from_email'      => 'infos@gbg-ci.com',
    'from_name'       => 'Global Business Group',

    'debug' => false,
];
```

> Le mot de passe email est celui de la boite `infos@gbg-ci.com`
> (hPanel -> Emails). C'est la meme boite que le formulaire de contact.

## 4. Installer les tables + le compte admin

Ouvrez dans le navigateur :

```
https://gbg-ci.com/admin/setup.php
```

- L'assistant cree les tables et votre **premier compte administrateur**
  (choisissez identifiant + mot de passe fort).
- Une fois termine, **supprimez le fichier `admin/setup.php`** du serveur
  (l'assistant vous le rappelle et se verrouille de lui-meme).

## 5. Importer les cooperatives

`https://gbg-ci.com/admin/login.php` -> connectez-vous -> **Cooperatives ->
Importer** -> televersez `COOPERATIVES DU PROJET-1.xlsx`.

## 6. Tester l'email avant tout envoi reel

Back-office -> **Tester la configuration email** (`admin/test-smtp.php`) ->
envoyez un test vers votre propre adresse. Si vous le recevez, tout est pret.

## 7. Generer les acces cooperatives

**Cooperatives -> Acces coop -> Generer les acces** : telechargez le CSV des
identifiants/mots de passe et transmettez a chaque cooperative les siens.

---

## Verifications de securite (post-installation)

- [ ] `admin/setup.php` supprime du serveur.
- [ ] Mot de passe admin par defaut change (ou defini via setup).
- [ ] `inc/config.local.php` present et **non** accessible en http
      (teste : `https://gbg-ci.com/inc/config.local.php` doit renvoyer une erreur 403).
- [ ] `debug` = false dans la config.
- [ ] Le site force bien HTTPS (deja gere par `.htaccess`).

## En cas d'envoi volumineux

L'envoi email se fait **par lots** avec reprise : si l'hebergeur coupe la
requete (limite de temps), la campagne passe en "Envoi en cours" et un bouton
**"Continuer l'envoi"** apparait pour traiter les emails restants. Relancez-le
autant de fois que necessaire.

Hostinger applique des **limites d'envoi horaires**. Pour ~70 cooperatives c'est
generalement suffisant en une fois ; sinon, espacez les relances.

## Depannage

| Symptome | Piste |
|----------|-------|
| Page blanche / erreur 500 | Mettez `debug => true` temporairement, rechargez, lisez le message, puis remettez `false`. |
| "Connexion base impossible" | Verifiez les 4 valeurs `db_*` dans `config.local.php`. |
| Email non recu | Verifiez le mot de passe de la boite, testez via `test-smtp.php`, regardez les spams. |
| setup.php dit "deja installe" | Normal si un admin existe. Supprimez le fichier. |
