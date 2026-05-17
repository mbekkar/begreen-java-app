-- ============================================================
-- BeGreen — Schéma de base de données
-- ============================================================
-- Auteurs : Mounir Bekkar & équipe
-- Université Lumière Lyon 2
--
-- Utilisation :
--   mysql -u root -p < database/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS siteweb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE siteweb;

-- ── Utilisateurs ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS utilisateur (
    id_utilisateur INT          NOT NULL AUTO_INCREMENT,
    pseudo         VARCHAR(50)  NOT NULL UNIQUE,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,        -- bcrypt
    ville          VARCHAR(100) NOT NULL,
    points         INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Défis écologiques ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS defis (
    id_defi     INT          NOT NULL AUTO_INCREMENT,
    defi        VARCHAR(255) NOT NULL,
    description TEXT         NOT NULL,
    points      INT          NOT NULL DEFAULT 10,
    ville       VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_defi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Réalisations (défis complétés par les utilisateurs) ───────
CREATE TABLE IF NOT EXISTS realisation (
    id_realisation INT  NOT NULL AUTO_INCREMENT,
    id_utilisateur INT  NOT NULL,
    id_defi        INT  NOT NULL,
    etat           TINYINT NOT NULL DEFAULT 0,   -- 1 = fait, 0 = non fait
    PRIMARY KEY (id_realisation),
    UNIQUE KEY uq_user_defi (id_utilisateur, id_defi),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_defi)        REFERENCES defis(id_defi)              ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Amis ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS amis (
    id_amitie      INT         NOT NULL AUTO_INCREMENT,
    id_utilisateur1 INT        NOT NULL,
    id_utilisateur2 INT        NOT NULL,
    statut         VARCHAR(20) NOT NULL DEFAULT 'en attente',  -- 'en attente' | 'accepte'
    PRIMARY KEY (id_amitie),
    FOREIGN KEY (id_utilisateur1) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur2) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Données de test ───────────────────────────────────────────
INSERT IGNORE INTO defis (defi, description, points, ville) VALUES
    ('Vélo au travail',       'Utilisez le vélo pour vous rendre au travail aujourd\'hui.', 20, 'Lyon'),
    ('Repas sans viande',     'Mangez un repas entièrement végétarien aujourd\'hui.',       10, 'Lyon'),
    ('Tri des déchets',       'Triez correctement vos déchets pendant toute la journée.',  15, 'Lyon'),
    ('Douche courte',         'Limitez votre douche à 5 minutes.',                         10, 'Lyon'),
    ('Transports en commun',  'Utilisez les transports en commun au lieu de la voiture.',  20, 'Paris'),
    ('Zéro plastique',        'Évitez tout emballage plastique pendant 24h.',              25, 'Paris'),
    ('Compostage',            'Commencez à composter vos déchets organiques.',             30, 'Bordeaux'),
    ('Achat local',           'Achetez vos fruits et légumes chez un producteur local.',   20, 'Marseille');
