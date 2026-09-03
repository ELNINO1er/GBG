-- =====================================================================
--  GBG - Base de communication avec les cooperatives (Phase 1)
--  Encodage : utf8mb4 / InnoDB
--  Importer ce fichier apres avoir selectionne la base cible dans phpMyAdmin.
-- =====================================================================

-- ---------------------------------------------------------------------
--  Comptes administrateurs du back-office GBG
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(80)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nom`           VARCHAR(150) NOT NULL DEFAULT '',
  `actif`         TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL,
  `last_login`    DATETIME     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Cooperatives
--  email_valide : 1 si au moins un email exploitable pour l'envoi
--  emails_extra : autres emails trouves dans la cellule (separes par ;)
--  login_*      : identifiants de l'espace cooperative (Phase 1)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cooperatives` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `n_ordre`         VARCHAR(20)  NOT NULL DEFAULT '',
  `nom_cooperative` VARCHAR(255) NOT NULL,
  `pca_nom`         VARCHAR(255) NOT NULL DEFAULT '',
  `localite`        VARCHAR(255) NOT NULL DEFAULT '',
  `region`          VARCHAR(120) NOT NULL DEFAULT '',
  `contact_pca`     VARCHAR(120) NOT NULL DEFAULT '',
  `dr_adg`          VARCHAR(255) NOT NULL DEFAULT '',
  `contact`         VARCHAR(120) NOT NULL DEFAULT '',
  `email`           VARCHAR(190) NOT NULL DEFAULT '',
  `emails_extra`    VARCHAR(500) NOT NULL DEFAULT '',
  `email_valide`    TINYINT(1)   NOT NULL DEFAULT 0,
  `source_feuille`  VARCHAR(40)  NOT NULL DEFAULT '',
  `actif`           TINYINT(1)   NOT NULL DEFAULT 1,
  `login_username`  VARCHAR(120) NULL,
  `login_password_hash` VARCHAR(255) NULL,
  `created_at`      DATETIME     NOT NULL,
  `updated_at`      DATETIME     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coop_login` (`login_username`),
  KEY `idx_coop_region` (`region`),
  KEY `idx_coop_email_valide` (`email_valide`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Campagnes (bulletins / messages groupes)
--  canal    : email | espace | email+espace
--  statut   : brouillon | envoyee
--  publiee  : 1 = visible dans l'espace cooperative
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campagnes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sujet`       VARCHAR(255) NOT NULL,
  `contenu`     MEDIUMTEXT   NOT NULL,
  `canal`       VARCHAR(30)  NOT NULL DEFAULT 'email',
  `statut`      VARCHAR(20)  NOT NULL DEFAULT 'brouillon',
  `publiee`     TINYINT(1)   NOT NULL DEFAULT 0,
  `filtre_region` TEXT NOT NULL,
  `filtre_cooperatives` TEXT NOT NULL,
  `nb_destinataires` INT UNSIGNED NOT NULL DEFAULT 0,
  `nb_envoyes`  INT UNSIGNED NOT NULL DEFAULT 0,
  `nb_echecs`   INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME     NOT NULL,
  `sent_at`     DATETIME     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_camp_statut` (`statut`),
  KEY `idx_camp_publiee` (`publiee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Envois : une ligne par (campagne, cooperative) pour l'email
--  statut : en_attente | envoye | echec | ignore
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `envois` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campagne_id`    INT UNSIGNED NOT NULL,
  `cooperative_id` INT UNSIGNED NOT NULL,
  `email`          VARCHAR(190) NOT NULL DEFAULT '',
  `statut`         VARCHAR(20)  NOT NULL DEFAULT 'en_attente',
  `message_erreur` VARCHAR(500) NOT NULL DEFAULT '',
  `sent_at`        DATETIME     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_env_campagne` (`campagne_id`),
  KEY `idx_env_coop` (`cooperative_id`),
  CONSTRAINT `fk_env_campagne` FOREIGN KEY (`campagne_id`)
    REFERENCES `campagnes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_env_coop` FOREIGN KEY (`cooperative_id`)
    REFERENCES `cooperatives` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
