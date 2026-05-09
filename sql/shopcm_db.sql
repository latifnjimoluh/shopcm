-- ============================================================
-- ShopCM SQLi Platform — Script de création de la base
-- TP Sécurité Web — Injection SQL — Mai 2026
-- Exécuter via phpMyAdmin ou : mysql -u root < shopcm_db.sql
-- ============================================================

DROP DATABASE IF EXISTS shopcm_db;
CREATE DATABASE shopcm_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE shopcm_db;

-- ============================================================
-- TABLE : users
-- ============================================================
CREATE TABLE users (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  email             VARCHAR(150) NOT NULL UNIQUE,
  password          VARCHAR(255) NOT NULL,
  nom               VARCHAR(100) NOT NULL,
  prenom            VARCHAR(100) NOT NULL,
  telephone         VARCHAR(20),
  adresse           TEXT,
  date_inscription  DATETIME DEFAULT CURRENT_TIMESTAMP,
  solde_fidelite    INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : admins
-- ============================================================
CREATE TABLE admins (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  username            VARCHAR(50) NOT NULL UNIQUE,
  password            VARCHAR(255) NOT NULL,
  role                ENUM('super_admin','gestionnaire') DEFAULT 'gestionnaire',
  derniere_connexion  DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : categories
-- ============================================================
CREATE TABLE categories (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(100) NOT NULL,
  description  TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : produits
-- ============================================================
CREATE TABLE produits (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nom           VARCHAR(200) NOT NULL,
  description   TEXT,
  prix          DECIMAL(10,2) NOT NULL,
  stock         INT DEFAULT 0,
  categorie_id  INT,
  image         VARCHAR(255) DEFAULT 'placeholder.jpg',
  actif         TINYINT(1) DEFAULT 1,
  FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : commandes
-- ============================================================
CREATE TABLE commandes (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  user_id           INT,
  numero_suivi      VARCHAR(50) NOT NULL UNIQUE,
  total             DECIMAL(10,2) NOT NULL,
  statut            ENUM('en_attente','expediee','livree','annulee') DEFAULT 'en_attente',
  date_commande     DATETIME DEFAULT CURRENT_TIMESTAMP,
  adresse_livraison TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : avis
-- ============================================================
CREATE TABLE avis (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  produit_id  INT,
  user_id     INT,
  note        INT CHECK (note BETWEEN 1 AND 5),
  commentaire TEXT,
  date_avis   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : codes_promo
-- ============================================================
CREATE TABLE codes_promo (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  code                VARCHAR(50) NOT NULL UNIQUE,
  reduction_pourcent  INT NOT NULL,
  actif               TINYINT(1) DEFAULT 1,
  date_expiration     DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : secrets_internes  (table "trésor" — non liée au site)
-- ============================================================
CREATE TABLE secrets_internes (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  cle    VARCHAR(100) NOT NULL,
  valeur TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : logs_connexion
-- ============================================================
CREATE TABLE logs_connexion (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  ip          VARCHAR(45) NOT NULL,
  user_agent  TEXT,
  date_log    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONNÉES : admins
-- Mots de passe bcrypt PHP valides (PASSWORD_BCRYPT, cost=12)
-- admin     -> admin123
-- gestion   -> gestion456
-- ============================================================
INSERT INTO admins (username, password, role, derniere_connexion) VALUES
(
  'admin',
  '$2y$10$EeJIqwou7sItPD2fA2cNY.9lwE7d/kbrE/aPpuO1IiHpjSdMDLdiu',
  -- ⬆ Hash bcrypt de 'admin123'
  'super_admin',
  '2026-05-08 09:00:00'
),
(
  'gestion',
  '$2y$10$dG3/U56Jjbwia8PY4XV97uGc/N5drXU0qH9mkZamUsj3FeKYjnjeC',
  -- ⬆ Hash bcrypt de 'gestion456'
  'gestionnaire',
  '2026-05-07 14:30:00'
);

-- ============================================================
-- DONNÉES : categories
-- ============================================================
INSERT INTO categories (nom, description) VALUES
('Electronique',  'Smartphones, ordinateurs, accessoires high-tech'),
('Mode',          'Vêtements, chaussures, sacs et accessoires de mode'),
('Maison',        'Electroménager, décoration, équipements ménagers'),
('Beauté',        'Cosmétiques, parfums, soins du corps et du visage'),
('Alimentation',  'Produits alimentaires locaux et importés'),
('Sport',         'Équipements sportifs, maillots, chaussures de sport');

-- ============================================================
-- DONNÉES : users
-- Mots de passe en clair (pour la démo) :
--   mballa@gmail.com       -> pass1234
--   nkomo.marie@gmail.com  -> marie2025
--   fotso.paul@gmail.com   -> paul@CM
--   talla.s@gmail.com      -> sandrine99
--   etoa.c@gmail.com       -> christian1
--   bidoung.a@gmail.com    -> aline2026
--   mvondo.r@gmail.com     -> robert77
--   kouam.e@gmail.com      -> estelle88
-- ============================================================
INSERT INTO users (email, password, nom, prenom, telephone, adresse, date_inscription, solde_fidelite) VALUES
(
  'mballa@gmail.com',
  '$2y$10$Dz3ehVRuJV2l5z5FXSV2tOXtmPY1QU3FKHp1XGf9AZAMgufhQ3QJa',
  -- pass1234
  'Mballa', 'Jean',
  '+237 677 123 456',
  'Quartier Bastos, Yaoundé, Cameroun',
  '2025-03-15 10:22:00', 150
),
(
  'nkomo.marie@gmail.com',
  '$2y$10$enbz3p/0TARFw0/bW6p45ex3.OK/l.UXf2XyugKKQulaJ7OPLS31G',
  -- marie2025
  'Nkomo', 'Marie',
  '+237 699 234 567',
  'Akwa, Douala, Cameroun',
  '2025-04-02 14:15:00', 320
),
(
  'fotso.paul@gmail.com',
  '$2y$10$d65loqaYmd//xGZJcJ6tSebb7cdlbZyiG.bkyi9gkFRg7jX6lUOM2',
  -- paul@CM
  'Fotso', 'Paul',
  '+237 655 345 678',
  'Ndokotti, Douala, Cameroun',
  '2025-04-20 09:00:00', 75
),
(
  'talla.s@gmail.com',
  '$2y$10$RksdWFmaZ6WtfVHLSqPZ..eaW4LqVlIS3MmCMS2NWAiCNRxN47dFq',
  -- sandrine99
  'Talla', 'Sandrine',
  '+237 670 456 789',
  'Biyem-Assi, Yaoundé, Cameroun',
  '2025-05-01 11:45:00', 200
),
(
  'etoa.c@gmail.com',
  '$2y$10$Q75aBSDynjcwb1hHnfSNd.bxexfMq2bsi1livgQCc5INobvpmMCcW',
  -- christian1
  'Etoa', 'Christian',
  '+237 681 567 890',
  'Essos, Yaoundé, Cameroun',
  '2025-05-10 08:30:00', 50
),
(
  'bidoung.a@gmail.com',
  '$2y$10$cqFxduZWB67cbIzvHadTzOhZx16dN/Sv8TgGorKDnWpU9xocGQfWm',
  -- aline2026
  'Bidoung', 'Aline',
  '+237 693 678 901',
  'Bonanjo, Douala, Cameroun',
  '2025-05-20 16:00:00', 480
),
(
  'mvondo.r@gmail.com',
  '$2y$10$OYfkn61PHkA37q4n0QaD9.PMSS6MLP9WzAfSB/.iMlVoYPWB/e/hK',
  -- robert77
  'Mvondo', 'Robert',
  '+237 674 789 012',
  'Melen, Yaoundé, Cameroun',
  '2025-06-01 12:00:00', 100
),
(
  'kouam.e@gmail.com',
  '$2y$10$.Qvif2frvsNHuZwIZhD2huThjKYKSK7vLnsYYpVNY6mAh/1v3.6Y6',
  -- estelle88
  'Kouam', 'Estelle',
  '+237 665 890 123',
  'Makepe, Douala, Cameroun',
  '2025-06-15 09:30:00', 260
);

-- ============================================================
-- DONNÉES : produits (18 produits, 6 catégories)
-- ============================================================
INSERT INTO produits (nom, description, prix, stock, categorie_id, image, actif) VALUES
-- Electronique (cat 1)
('Smartphone Tecno Camon 20',
 'Écran AMOLED 6.67", 50MP, batterie 5000mAh, 8Go RAM, 256Go stockage. Idéal pour photos et vidéos.',
 185000.00, 45, 1, 'tecno_camon20.jpg', 1),

('Samsung Galaxy A54 5G',
 'Écran Super AMOLED 6.4", triple caméra 50MP, 5G, 6Go RAM, 128Go, résistant à l\'eau IP67.',
 235000.00, 28, 1, 'samsung_a54.jpg', 1),

('Écouteurs Bluetooth JBL Tune 510BT',
 'Son puissant, jusqu\'à 40h d\'autonomie, connexion multipoint, pliable, port USB-C.',
 28500.00, 80, 1, 'jbl_tune510.jpg', 1),

('Chargeur Solaire 20000mAh',
 'Batterie externe solaire double USB + USB-C, charge rapide 22.5W, idéal pour coupures d\'électricité.',
 18500.00, 60, 1, 'chargeur_solaire.jpg', 1),

-- Mode (cat 2)
('Sac à main cuir véritable',
 'Sac à main femme en cuir véritable, compartiments multiples, bandoulière amovible, coloris marron.',
 25000.00, 35, 2, 'sac_cuir.jpg', 1),

('Boubou traditionnel brodé homme',
 'Boubou grand-boubou en bazin riche, broderies dorées, taille unique ajustable, couleur blanche.',
 45000.00, 20, 2, 'boubou_homme.jpg', 1),

('Maillot équipe nationale Cameroun',
 'Maillot officiel des Lions Indomptables 2025, tissu respirant, livré avec short. Tailles S à XXL.',
 15000.00, 120, 2, 'maillot_cameroun.jpg', 1),

-- Maison (cat 3)
('Réfrigérateur LG 200L No Frost',
 'Réfrigérateur combiné LG 200 litres, No Frost, classe A+, congélateur intégré, livraison incluse.',
 320000.00, 12, 3, 'frigo_lg.jpg', 1),

('Fer à repasser Philips Vapeur',
 'Semelle en inox, 2400W, réservoir 300ml, auto-nettoyage, anticalcaire, câble 2m.',
 22000.00, 55, 3, 'fer_philips.jpg', 1),

('Ventilateur sur pied 3 vitesses',
 'Ventilateur tour oscillant, 3 vitesses, minuterie 7h, télécommande incluse, silencieux.',
 35000.00, 40, 3, 'ventilateur.jpg', 1),

-- Beauté (cat 4)
('Crème de karité pur bio 500ml',
 'Beurre de karité 100% pur, pressé à froid, non raffiné, multi-usage corps et cheveux.',
 7500.00, 200, 4, 'karite.jpg', 1),

('Parfum Oriental Oud 50ml',
 'Eau de parfum orientale aux notes de oud, santal et rose. Tenue 8-10h. Fabrication locale.',
 22000.00, 65, 4, 'parfum_oud.jpg', 1),

-- Alimentation (cat 5)
('Huile de palme rouge 5L',
 'Huile de palme artisanale non raffinée, pressée traditionnellement, riche en bêta-carotène.',
 8500.00, 150, 5, 'huile_palme.jpg', 1),

('Café Arabica du Moungo 500g',
 'Café arabica 100% camerounais, torréfaction moyenne, arômes fruités et chocolatés. Moulu ou en grains.',
 6500.00, 180, 5, 'cafe_moungo.jpg', 1),

('Poivre blanc de Penja 100g',
 'Poivre blanc Indication Géographique Protégée, récolté à la main, arômes complexes et floraux.',
 4500.00, 90, 5, 'poivre_penja.jpg', 1),

-- Sport (cat 6)
('Ballon de football Adidas Tiro',
 'Ballon officiel taille 5, couture machine, caoutchouc naturel, convient terrain synthétique et gazon.',
 18000.00, 75, 6, 'ballon_adidas.jpg', 1),

('Chaussures de sport Nike Air Max',
 'Running homme, semelle Air Max, mesh respirant, tailles 39-46, coloris noir/blanc.',
 85000.00, 30, 6, 'nike_airmax.jpg', 1),

-- Produit inactif (pour démontrer vuln #5)
('Montre connectée Samsung Galaxy Watch 6',
 'PRODUIT RETIRÉ DE LA VENTE — Montre connectée 44mm, ECG, GPS, suivi sommeil, 40h autonomie.',
 125000.00, 0, 1, 'galaxy_watch6.jpg', 0);

-- ============================================================
-- DONNÉES : commandes
-- ============================================================
INSERT INTO commandes (user_id, numero_suivi, total, statut, date_commande, adresse_livraison) VALUES
(1, 'SHOP-2025-0001', 213500.00, 'livree',    '2025-11-10 10:00:00', 'Quartier Bastos, Yaoundé'),
(2, 'SHOP-2025-0002', 320000.00, 'expediee',  '2025-12-01 14:30:00', 'Akwa, Douala'),
(3, 'SHOP-2025-0003',  47000.00, 'en_attente','2026-01-15 09:00:00', 'Ndokotti, Douala'),
(4, 'SHOP-2025-0004',  15000.00, 'livree',    '2026-02-20 11:00:00', 'Biyem-Assi, Yaoundé'),
(5, 'SHOP-2026-0001', 235000.00, 'expediee',  '2026-03-05 08:45:00', 'Essos, Yaoundé'),
(1, 'SHOP-2026-0002',  85000.00, 'en_attente','2026-04-12 15:00:00', 'Quartier Bastos, Yaoundé'),
(6, 'SHOP-2026-0003',  36000.00, 'livree',    '2026-04-18 10:30:00', 'Bonanjo, Douala'),
(7, 'SHOP-2026-0004', 185000.00, 'en_attente','2026-05-01 12:00:00', 'Melen, Yaoundé'),
(8, 'SHOP-2026-0005',  11000.00, 'annulee',   '2026-05-05 16:00:00', 'Makepe, Douala'),
(2, 'SHOP-2026-0006',  53000.00, 'expediee',  '2026-05-07 09:15:00', 'Akwa, Douala');

-- ============================================================
-- DONNÉES : avis
-- ============================================================
INSERT INTO avis (produit_id, user_id, note, commentaire, date_avis) VALUES
(1, 2, 5, 'Excellent smartphone ! La caméra est incroyable pour ce prix. Je recommande.', '2026-01-10 10:00:00'),
(1, 3, 4, 'Très bon appareil, rapide et fluide. L\'autonomie pourrait être meilleure.', '2026-01-15 14:00:00'),
(8, 1, 5, 'Réfrigérateur silencieux et efficace. La livraison était rapide. Parfait !', '2026-02-01 11:00:00'),
(7, 4, 5, 'Maillot de qualité, tissu respirant. Les Lions Indomptables !', '2026-02-10 09:00:00'),
(13, 5, 4, 'Bonne huile de palme, goût authentique. L\'emballage pourrait être amélioré.', '2026-03-05 16:00:00'),
(2, 6, 4, 'Samsung de qualité, mais un peu cher. La 5G est un vrai plus à Douala.', '2026-03-20 12:00:00'),
(17, 7, 5, 'Chaussures très confortables pour le jogging. La qualité Nike est au rendez-vous.', '2026-04-01 08:30:00'),
(5, 8, 3, 'Sac correct mais le cuir semble synthétique malgré la description. Déçue.', '2026-04-15 17:00:00');

-- ============================================================
-- DONNÉES : codes_promo
-- ============================================================
INSERT INTO codes_promo (code, reduction_pourcent, actif, date_expiration) VALUES
('NOEL2025',    20, 1, '2026-12-31'),
('PROMO10',     10, 1, '2026-12-31'),
('BIENVENUE',   15, 1, '2026-12-31'),
('CAMEROUN20',  20, 1, '2026-12-31'),
('EXPIRE2024',   5, 0, '2024-12-31');

-- ============================================================
-- DONNÉES : secrets_internes
-- (valeurs fictives au format réaliste de vraies clés)
-- ============================================================
INSERT INTO secrets_internes (cle, valeur) VALUES
('STRIPE_API_KEY',
 'sk_test_DUMMY_STRIPE_KEY_FOR_EDUCATION_ONLY_12345'),

('SMTP_PASSWORD',
 'DUMMY_SMTP_PASSWORD_X1Y2Z3'),

('JWT_SECRET',
 'DUMMY_JWT_SECRET_KEY_FOR_PEDAGOGICAL_USE_ONLY'),

('DB_ROOT_PASSWORD',
 'DUMMY_DB_ROOT_PASSWORD_2026'),

('TWILIO_AUTH_TOKEN',
 'DUMMY_TWILIO_AUTH_TOKEN_ABCDEF123456');

-- ============================================================
-- DONNÉES : logs_connexion (entrées initiales réalistes)
-- ============================================================
INSERT INTO logs_connexion (ip, user_agent, date_log) VALUES
(
  '192.168.1.105',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
  '2026-05-08 09:12:34'
),
(
  '197.234.56.89',
  'Mozilla/5.0 (Linux; Android 13; Tecno Camon 20) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.86 Mobile Safari/537.36',
  '2026-05-08 11:45:22'
),
(
  '41.202.219.67',
  'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Mobile/15E148 Safari/604.1',
  '2026-05-09 08:03:55'
);

-- ============================================================
-- Fin du script — 9 tables créées et peuplées
-- ============================================================
