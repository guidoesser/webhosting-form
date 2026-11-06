-- Webhosting-Antragsformular: Tabelle für eingegangene Anträge.
-- Ausführen mit:  mysql -u root -p < schema.sql

CREATE TABLE IF NOT EXISTS webhosting_antraege (
    id                      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    vorname                 VARCHAR(100)     NOT NULL,
    nachname                VARCHAR(100)     NOT NULL,
    email                   VARCHAR(255)     NOT NULL,
    status                  VARCHAR(50)      NOT NULL,          -- "Ich bin"
    benutzername            VARCHAR(100)     NOT NULL,          -- Webspace-Name
    laufzeit_semester       TINYINT UNSIGNED NOT NULL,
    projektbeschreibung     TEXT             NULL,
    weitere_ansprechpartner TEXT             NULL,
    anmerkungen             TEXT             NULL,
    nutzungsbedingungen     TINYINT(1)       NOT NULL DEFAULT 0,
    created_at              TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
