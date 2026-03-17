-- =================================================================
-- 1. DATABASE CREATION & CORE TABLES (From console.sql)
-- =================================================================
DROP DATABASE IF EXISTS emensawerbeseite;
CREATE DATABASE IF NOT EXISTS emensawerbeseite
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE emensawerbeseite;

CREATE TABLE gericht(
                        id BIGINT PRIMARY KEY,
                        name VARCHAR(80) NOT NULL,
                        beschreibung VARCHAR(800) NOT NULL,
                        erfasst_am DATE NOT NULL,
                        vegetarisch BOOLEAN NOT NULL DEFAULT 0,
                        vegan BOOLEAN NOT NULL DEFAULT 0,
                        preisintern DOUBLE NOT NULL,
                        preisextern DOUBLE NOT NULL
);

CREATE TABLE allergen(
                         code CHAR(4) PRIMARY KEY,
                         name VARCHAR(300) NOT NULL,
                         typ VARCHAR(20) NOT NULL DEFAULT 'allergen'
);

CREATE TABLE kategorie(
                          id BIGINT PRIMARY KEY,
                          name VARCHAR(80) NOT NULL,
                          eltern_id int8,
                          bildname VARCHAR(200)
);

CREATE TABLE gericht_hat_allergen(
                                     code CHAR(4),
                                     gericht_id BIGINT NOT NULL
);

CREATE TABLE gericht_hat_kategorie(
                                      gericht_id BIGINT NOT NULL,
                                      kategorie_id BIGINT NOT NULL
);

CREATE TABLE besucher (
                          anzahl BIGINT NOT NULL DEFAULT 0
);
INSERT INTO besucher (anzahl) VALUES (0);

-- =================================================================
-- 2. CORE DATA INSERTS
-- =================================================================
INSERT INTO `allergen` (`code`, `name`, `typ`) VALUES
                                                   ('a', 'Getreideprodukte', 'Getreide (Gluten)'), ('a1', 'Weizen', 'Allergen'), ('a2', 'Roggen', 'Allergen'),
                                                   ('a3', 'Gerste', 'Allergen'), ('a4', 'Dinkel', 'Allergen'), ('a5', 'Hafer', 'Allergen'), ('a6', 'Dinkel', 'Allergen'),
                                                   ('b', 'Fisch', 'Allergen'), ('c', 'Krebstiere', 'Allergen'), ('d', 'Schwefeldioxid/Sulfit', 'Allergen'),
                                                   ('e', 'Sellerie', 'Allergen'), ('f', 'Milch und Laktose', 'Allergen'), ('f1', 'Butter', 'Allergen'),
                                                   ('f2', 'Käse', 'Allergen'), ('f3', 'Margarine', 'Allergen'), ('g', 'Sesam', 'Allergen'),
                                                   ('h', 'Nüsse', 'Allergen'), ('h1', 'Mandeln', 'Allergen'), ('h2', 'Haselnüsse', 'Allergen'),
                                                   ('h3', 'Walnüsse', 'Allergen'), ('i', 'Erdnüsse', 'Allergen');

INSERT INTO `gericht` (`id`, `name`, `beschreibung`, `erfasst_am`, `vegan`, `vegetarisch`, `preisintern`, `preisextern`) VALUES
                                                                                                                             (1, 'Bratkartoffeln mit Speck und Zwiebeln', 'Kartoffeln mit Zwiebeln und gut Speck', '2020-08-25', 0, 0, 2.3, 4),
                                                                                                                             (2, 'Bratkartoffeln mit bacon', 'Kartoffeln mit Bacon', '2020-08-25', 0, 0, 2.6, 4.5),
                                                                                                                             (3, 'Bratkartoffeln mit Zwiebeln', 'Kartoffeln mit Zwiebeln und ohne Speck', '2020-08-25', 1, 1, 2.3, 4),
                                                                                                                             (4, 'Grilltofu', 'Fein gewürzt und mariniert', '2020-08-25', 1, 1, 2.5, 4.5),
                                                                                                                             (5, 'Lasagne', 'Klassisch mit Bolognesesoße und Creme Fraiche', '2020-08-24', 0, 0, 2.5, 4.5),
                                                                                                                             (6, 'Lasagne vegetarisch', 'Klassisch mit Sojagranulatsoße und Creme Fraiche', '2020-08-24', 0, 1, 2.5, 4.5),
                                                                                                                             (7, 'Hackbraten', 'Nicht nur für Hacker', '2020-08-25', 0, 0, 2.5, 4),
                                                                                                                             (8, 'Gemüsepfanne', 'Gesundes aus der Region, deftig angebraten', '2020-08-25', 1, 1, 2.3, 4),
                                                                                                                             (9, 'Hühnersuppe', 'Suppenhuhn trifft Petersilie', '2020-08-25', 0, 0, 2, 3.5),
                                                                                                                             (10, 'Forellenfilet', 'mit Kartoffeln und Dilldip', '2020-08-22', 0, 0, 3.8, 5),
                                                                                                                             (11, 'Kartoffel-Lauch-Suppe', 'der klassische Bauchwärmer mit frischen Kräutern', '2020-08-22', 0, 1, 2, 3),
                                                                                                                             (12, 'Kassler mit Rosmarinkartoffeln', 'dazu Salat und Senf', '2020-08-23', 0, 0, 3.8, 5.2),
                                                                                                                             (13, 'Drei Reibekuchen mit Apfelmus', 'grob geriebene Kartoffeln aus der Region', '2020-08-23', 0, 1, 2.5, 4.5),
                                                                                                                             (14, 'Pilzpfanne', 'die legendäre Pfanne aus Pilzen der Saison', '2020-08-23', 0, 1, 3, 5),
                                                                                                                             (15, 'Pilzpfanne vegan', 'die legendäre Pfanne aus Pilzen der Saison ohne Käse', '2020-08-24', 1, 1, 3, 5),
                                                                                                                             (16, 'Käsebrötchen', 'schmeckt vor und nach dem Essen', '2020-08-24', 0, 1, 1, 1.5),
                                                                                                                             (17, 'Schinkenbrötchen', 'schmeckt auch ohne Hunger', '2020-08-25', 0, 0, 1.25, 1.75),
                                                                                                                             (18, 'Tomatenbrötchen', 'mit Schnittlauch und Zwiebeln', '2020-08-25', 1, 1, 1, 1.5),
                                                                                                                             (19, 'Mousse au Chocolat', 'sahnige schweizer Schokolade rundet jedes Essen ab', '2020-08-26', 0, 1, 1.25, 1.75),
                                                                                                                             (20, 'Suppenkreation á la Chef', 'was verschafft werden muss, gut und günstig', '2020-08-26', 0, 0, 0.5, 0.9),
                                                                                                                             (21, 'Currywurst mit Pommes', 'Leckere Currywurst mit vielen Pommes', CURDATE(), 0, 0, 3.50, 5.00);

INSERT INTO `gericht_hat_allergen` (`code`, `gericht_id`) VALUES
                                                              ('h', 1), ('a3', 1), ('a4', 1), ('f1', 3), ('a6', 3), ('i', 3), ('a3', 4), ('f1', 4), ('a4', 4), ('h3', 4),
                                                              ('d', 6), ('h1',7), ('a2', 7), ('h3', 7), ('c', 7), ('a3', 8), ('h3', 10), ('d', 10), ('f', 10), ('f2', 12),
                                                              ('h1', 12), ('a5',12), ('c', 1), ('a2', 9), ('i', 14), ('f1', 1), ('a1', 15), ('a4', 15), ('i', 15),
                                                              ('f3', 15), ('h3', 15);

INSERT INTO `kategorie` (`id`, `eltern_id`, `name`, `bildname`) VALUES
                                                                    (1, NULL, 'Aktionen', 'kat_aktionen.png'), (2, NULL, 'Menus', 'kat_menu.gif'),
                                                                    (3, 2, 'Hauptspeisen', 'kat_menu_haupt.bmp'), (4, 2, 'Vorspeisen', 'kat_menu_vor.svg'),
                                                                    (5, 2, 'Desserts', 'kat_menu_dessert.pic'), (6, 1, 'Mensastars', 'kat_stars.tif'),
                                                                    (7, 1, 'Erstiewoche', 'kat_erties.jpg');

INSERT INTO `gericht_hat_kategorie` (`kategorie_id`, `gericht_id`) VALUES
                                                                       (3, 1), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 9), (4, 16), (4, 17), (4, 18),
                                                                       (5, 16), (5, 17), (5, 18), (6, 18), (3, 21);

UPDATE allergen SET name = 'Kamut' WHERE code = 'a6';

-- =================================================================
-- 3. CONSTRAINTS & KEYS (From consoleD.sql & console.sql)
-- =================================================================
ALTER TABLE gericht_hat_kategorie ADD CONSTRAINT pk_gericht_hat_kategorie PRIMARY KEY (gericht_id, kategorie_id);
ALTER TABLE gericht ADD INDEX idx_gericht_name (name);
ALTER TABLE gericht ADD CONSTRAINT unique_gericht_name UNIQUE (name);

ALTER TABLE gericht_hat_kategorie ADD CONSTRAINT fk_ghk_gericht FOREIGN KEY (gericht_id) REFERENCES gericht(id) ON DELETE CASCADE;
ALTER TABLE gericht_hat_kategorie ADD CONSTRAINT fk_ghk_kategorie FOREIGN KEY (kategorie_id) REFERENCES kategorie(id) ON DELETE CASCADE;

ALTER TABLE gericht_hat_allergen ADD CONSTRAINT fk_gha_gericht FOREIGN KEY (gericht_id) REFERENCES gericht(id) ON DELETE CASCADE;
ALTER TABLE gericht_hat_allergen ADD CONSTRAINT fk_gha_code FOREIGN KEY (code) REFERENCES allergen(code) ON UPDATE CASCADE;

ALTER TABLE kategorie ADD CONSTRAINT fk_kategorie_eltern FOREIGN KEY (eltern_id) REFERENCES kategorie(id) ON DELETE SET NULL;

-- =================================================================
-- 4. WUNSCHGERICHT SYSTEM (From m4console.sql)
-- =================================================================
CREATE TABLE IF NOT EXISTS ersteller (
                                         eid INT AUTO_INCREMENT PRIMARY KEY,
                                         name VARCHAR(100) NOT NULL DEFAULT 'anonym',
    email VARCHAR(100) NOT NULL
    );

CREATE TABLE IF NOT EXISTS wunschgericht (
                                             wid INT AUTO_INCREMENT PRIMARY KEY,
                                             eid INT NOT NULL,
                                             name VARCHAR(100) NOT NULL,
    beschreibung TEXT,
    erstellungsdatum DATETIME NOT NULL
    );

ALTER TABLE ersteller ADD CONSTRAINT uq_ersteller_email UNIQUE (email);
ALTER TABLE wunschgericht ADD CONSTRAINT fk_wunsch_ersteller FOREIGN KEY (eid) REFERENCES ersteller (eid) ON DELETE RESTRICT;

-- =================================================================
-- 5. BENUTZER SYSTEM & BILDER (From m5console.sql)
-- =================================================================
CREATE TABLE IF NOT EXISTS benutzer (
                                        id INT(8) NOT NULL,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(100) NOT NULL,
    passwort VARCHAR(200) NOT NULL,
    admin BOOLEAN NOT NULL DEFAULT FALSE,
    anzahlfehler INT NOT NULL DEFAULT 0,
    anzahlanmeldungen INT NOT NULL DEFAULT 0,
    letzteanmeldung DATETIME,
    letzterfehler DATETIME
    );

ALTER TABLE benutzer ADD CONSTRAINT pk_benutzer PRIMARY KEY (id);
ALTER TABLE benutzer ADD CONSTRAINT uq_benutzer_email UNIQUE (email);
ALTER TABLE benutzer MODIFY id INT(8) NOT NULL AUTO_INCREMENT;

INSERT INTO benutzer (name, email, passwort, admin) VALUES
    ('Admin', 'admin@emensa.example', 'f865b53623b121fd34ee5426c792e5c33af8c227', 1);

UPDATE benutzer
SET passwort = SHA1(CONCAT('admin123', 'YA20'))
WHERE email = 'admin@emensa.example';

CREATE TABLE IF NOT EXISTS newsletter_anmeldung (
                                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                                    name VARCHAR(100) NOT NULL,
                                                    email VARCHAR(100) NOT NULL UNIQUE,
                                                    sprache VARCHAR(50) NOT NULL
);

ALTER TABLE gericht ADD COLUMN bildname VARCHAR(200) DEFAULT NULL;
UPDATE gericht SET bildname = '01_bratkartoffel.jpg' WHERE id = 1;
UPDATE gericht SET bildname = '03_bratkartoffel.jpg' WHERE id = 3;
UPDATE gericht SET bildname = '04_tofu.jpg' WHERE id = 4;
UPDATE gericht SET bildname = '06_lasagne.jpg' WHERE id = 6;
UPDATE gericht SET bildname = '09_suppe.jpg' WHERE id = 9;
UPDATE gericht SET bildname = '10_forelle.jpg' WHERE id = 10;
UPDATE gericht SET bildname = '11_soup.jpg' WHERE id = 11;
UPDATE gericht SET bildname = '12_kassler.jpg' WHERE id = 12;
UPDATE gericht SET bildname = '13_reibekuchen.jpg' WHERE id = 13;
UPDATE gericht SET bildname = '15_pilze.jpg' WHERE id = 15;
UPDATE gericht SET bildname = '17_broetchen.jpg' WHERE id = 17;
UPDATE gericht SET bildname = '19_mousse.jpg' WHERE id = 19;
UPDATE gericht SET bildname = '20_suppe.jpg' WHERE id = 20;

-- =================================================================
-- 6. VIEWS & PROCEDURES (From m5_4.sql & m5_5.sql)
-- =================================================================
CREATE VIEW view_suppengerichte AS SELECT * FROM gericht WHERE name LIKE '%suppe%';
CREATE VIEW view_anmeldungen AS SELECT name, anzahlanmeldungen FROM benutzer ORDER BY anzahlanmeldungen DESC;
CREATE VIEW view_kategoriegerichte_vegetarisch AS
SELECT k.name AS kategorie_name, g.name AS gericht_name
FROM kategorie k
         LEFT JOIN gericht_hat_kategorie ghk ON k.id = ghk.kategorie_id
         LEFT JOIN gericht g ON ghk.gericht_id = g.id AND g.vegetarisch = 1;

DROP PROCEDURE IF EXISTS inkrementiere_anmeldungen;

CREATE PROCEDURE inkrementiere_anmeldungen(IN p_user_id INT)
BEGIN
UPDATE benutzer
SET anzahlanmeldungen = anzahlanmeldungen + 1,
    anzahlfehler = 0,
    letzteanmeldung = NOW()
WHERE id = p_user_id;
END;