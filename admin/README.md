# GBG - Module de communication avec les cooperatives (Phase 1)

Back-office PHP/MySQL greffe sur le site statique, permettant de gerer la base
des cooperatives et d'envoyer des messages groupes (email + espace en ligne).

## Installation

### En production (mise en ligne)
Voir **`DEPLOIEMENT.md`** a la racine. En resume : creer la base chez
l'hebergeur, renseigner `inc/config.local.php`, ouvrir `/admin/setup.php`
(assistant qui cree les tables + le 1er admin), puis supprimer `setup.php`.

### En local (WAMP)
1. `php inc/install.php [identifiant] [mot_de_passe]` cree la base `gbg_coop`,
   les tables et un compte admin (par defaut `admin` / `GbgAdmin2026!`,
   **a changer via la page Mon compte**).
2. Copiez `inc/config.local.example.php` en `inc/config.local.php` (SMTP).

### Acces
- Back-office : `/admin/login.php`
- Espace cooperatives : `/espace/login.php` (lien present dans le pied de page du site)

## Pages du back-office

| Page | Role |
|------|------|
| `index.php` | Tableau de bord |
| `cooperatives.php` | Liste, recherche, filtres |
| `cooperative-edit.php` | Fiche cooperative (edition, acces, suppression) |
| `acces-coop.php` | Generation groupee des acces + export CSV |
| `import.php` | Import Excel/CSV |
| `campagnes.php` / `campagne-edit.php` / `campagne-view.php` | Campagnes et diffusion |
| `test-smtp.php` | Test de la configuration email |
| `compte.php` | Changer son mot de passe |
| `setup.php` | Assistant d'installation (a supprimer apres usage) |

## Utilisation

### Importer les cooperatives
`/admin/import.php` -> televerser le fichier `.xlsx` ou `.csv`.
Colonnes attendues (dans l'ordre) :
`N.ordre | Nom PCA | Nom cooperative | Localite | Contact PCA | DR/ADG | Contact | Email`.
- Les emails multiples dans une cellule sont eclates automatiquement.
- Les emails invalides sont signales pour correction manuelle.
- Les cooperatives deja presentes (meme nom) sont mises a jour.

### Gerer les cooperatives
`/admin/cooperatives.php` : recherche, filtres (avec/sans email, par region),
edition, et **generation d'un acces** a l'espace en ligne (identifiant + mot de
passe a communiquer a la cooperative).

### Envoyer un message groupe
1. `/admin/campagne-edit.php` : rediger sujet + contenu, choisir les canaux
   (Email et/ou Espace cooperatives), cibler eventuellement une region.
2. Verifier l'apercu et la liste des destinataires.
3. **Lancer la diffusion** : envoi email aux cooperatives joignables +
   publication dans l'espace. Le detail des envois (envoye/echec) est conserve.

## Perimetre

- **Inclus (Phase 1)** : import Excel, back-office securise, envoi email groupe,
  espace cooperatives avec login, historique et statuts.
- **Phase 2 (a prevoir)** : WhatsApp (compte WhatsApp Business API + fournisseur
  payant), envois planifies automatiques (tache planifiee / cron).

## Securite

- Mots de passe hashes (`password_hash`).
- Protection CSRF sur tous les formulaires.
- Requetes preparees (PDO) contre les injections SQL.
- Dossiers `inc/` et `sql/` interdits d'acces web (`.htaccess`).
- Fichiers `.xlsx/.csv/.sql` et `config.local.php` bloques cote serveur.

> Pensez a changer le mot de passe admin par defaut et a servir le site en HTTPS
> (deja force par le `.htaccess` en production).
