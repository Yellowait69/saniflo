-- 1. Configuration de base
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ========================================================
-- 2. Table : USERS (Administrateurs)
-- ========================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `username` varchar(50) NOT NULL,
                         `password` varchar(255) NOT NULL,
                         `email` varchar(100) DEFAULT NULL,
                         `created_at` datetime DEFAULT current_timestamp(),
                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`username`, `password`, `email`) VALUES
    ('Jean-François', '$2y$10$8.w.1/5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0.1.2.3.4.5.6', 'info@saniflo.be');

-- ========================================================
-- 3. Table : SERVICES
-- ========================================================
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `title` varchar(100) NOT NULL,
                            `description` text NOT NULL,
                            `icon` varchar(50) NOT NULL,
                            `display_order` int(11) DEFAULT 0,
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `services` (`title`, `description`, `icon`) VALUES
                                                            ('Chauffage & Énergie', 'Installations de chauffage, chauffe-eau solaire ou pompe à chaleur Viessmann assurant un excellent rendement. Garantie de 10 ans sur installations entretenues.', 'fa-fire'),
                                                            ('Adoucisseur d\'eau', 'Le calcaire est un isolant qui augmente votre consommation. Nos adoucisseurs protègent vos installations et électroménagers.', 'fa-tint'),
                                                            ('Sanitaire & Salle de Bain', 'Rénovation complète ou partielle, remplacement de boiler, robinetterie, recherche de fuite. Gestion de A à Z.', 'fa-bath');

-- ========================================================
-- 4. Table : PROJECTS (Réalisations / Portfolio)
-- ========================================================
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `title` varchar(100) NOT NULL,
                            `city` varchar(100) DEFAULT NULL,
                            `category` varchar(100) DEFAULT NULL,
                            `description` text,
                            `image_url` varchar(255) NOT NULL,
                            `date_completion` date DEFAULT NULL,
                            `created_at` datetime DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `projects` (`title`, `city`, `category`, `description`, `image_url`, `date_completion`) VALUES
    ('Salle de bain moderne', 'Wavre', 'Sanitaire', 'Rénovation complète avec douche à l\'italienne.', 'img/portfolio/sdb-wavre.jpg', '2023-11-15');

-- ========================================================
-- 5. Table : TEAM (L'Équipe)
-- ========================================================
DROP TABLE IF EXISTS `team`;
CREATE TABLE `team` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(100) NOT NULL,
                        `role` varchar(100) NOT NULL,
                        `bio` text NOT NULL,
                        `image_url` varchar(255) DEFAULT 'img/default-avatar.png',
                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `team` (`name`, `role`, `bio`, `image_url`) VALUES
                                                            ('Jean-François Dengis', 'Gérant & Expert Technique', 'Expert Viessmann et formateur.', 'img/jf-dengis.jpg'),
                                                            ('Florence Lambinon', 'Gérante & Administration', 'Gère l\'administration et le lien clientèle.', 'img/florence.jpg');

-- ========================================================
-- 6. Table : CERTIFICATIONS (Agréments)
-- ========================================================
DROP TABLE IF EXISTS `certifications`;
CREATE TABLE `certifications` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `region` varchar(50) NOT NULL,
                                  `title` varchar(100) NOT NULL,
                                  `number` varchar(100) NOT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `certifications` (`region`, `title`, `number`) VALUES
                                                               ('Général', 'QUALIWALL (Solaire thermique)', 'N° 00246'),
                                                               ('Général', 'Cerga (Installateur Gaz)', 'N° 02-03038-NP'),
                                                               ('Bruxelles', 'Installateur Gaz (TG1 / TG2)', '1181988 / 001232593'),
                                                               ('Wallonie', 'Installateur Gaz (TG1 / TG2)', '00711'),
                                                               ('Flandre', 'Installateur Gaz (G1 / G2)', 'GV32834');

-- ========================================================
-- 7. Table : MESSAGES (Contact amélioré)
-- ========================================================
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `nom` varchar(100) NOT NULL,
                            `email` varchar(150) NOT NULL,
                            `telephone` varchar(20) DEFAULT NULL,
                            `subject` varchar(100) DEFAULT NULL,
                            `message` text NOT NULL,
                            `is_read` tinyint(1) DEFAULT 0,
                            `date_envoi` datetime DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- 8. Table : QUOTE_REQUESTS (Système de Devis & Entretien)
-- ========================================================
DROP TABLE IF EXISTS `quote_requests`;
CREATE TABLE `quote_requests` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `is_company` tinyint(1) DEFAULT 0,
                                  `company_name` varchar(150) DEFAULT NULL,
                                  `vat_number` varchar(50) DEFAULT NULL,
                                  `vat_regime` varchar(50) DEFAULT NULL,
                                  `firstname` varchar(100) NOT NULL,
                                  `lastname` varchar(100) NOT NULL,
                                  `email` varchar(150) NOT NULL,
                                  `phone` varchar(20) DEFAULT NULL,
                                  `street` varchar(255) NOT NULL,
                                  `zip` varchar(20) NOT NULL,
                                  `city` varchar(100) NOT NULL,
                                  `house_age` int(11) DEFAULT NULL,
                                  `energy_type` varchar(50) DEFAULT NULL,
                                  `device_brand` varchar(100) DEFAULT 'Viessmann',
                                  `device_model` varchar(100) DEFAULT NULL,
                                  `device_serial` varchar(100) DEFAULT NULL,
                                  `device_year` int(11) DEFAULT NULL,
                                  `device_kw` varchar(20) DEFAULT NULL,
                                  `appointment_date` datetime DEFAULT NULL,
                                  `payment_method` varchar(50) DEFAULT 'intervention',
                                  `total_price_htva` decimal(10,2) DEFAULT NULL,
                                  `description` text,
                                  `status` varchar(20) DEFAULT 'nouveau',
                                  `created_at` datetime DEFAULT current_timestamp(),
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- 9. Table : PRICING (Gestion des tarifs)
-- ========================================================
DROP TABLE IF EXISTS `pricing`;
CREATE TABLE `pricing` (
                           `id` int(11) NOT NULL AUTO_INCREMENT,
                           `service_type` varchar(100) NOT NULL,
                           `price_htva` decimal(10,2) NOT NULL,
                           `description` varchar(255),
                           PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pricing` (`service_type`, `price_htva`, `description`) VALUES
                                                                        ('entretien_mazout_viessmann', 190.00, 'Tarif entretien annuel mazout Viessmann'),
                                                                        ('entretien_gaz_viessmann', 160.00, 'Tarif entretien biennal gaz Viessmann'),
                                                                        ('entretien_adoucisseur_bwt', 140.00, 'Tarif entretien quadriennal adoucisseur BWT');

COMMIT;