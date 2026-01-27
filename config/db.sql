-- 1. Configuration de base
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Encodage pour gérer les accents et émojis
SET NAMES utf8mb4;

-- ========================================================
-- 2. Table : USERS (Administrateurs)
-- ========================================================

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `username` varchar(50) NOT NULL,
                         `password` varchar(255) NOT NULL, -- Doit contenir le hash (ex: password_hash en PHP)
                         `email` varchar(100) DEFAULT NULL,
                         `created_at` datetime DEFAULT current_timestamp(),
                         PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données : Ajout de l'admin par défaut
-- ATTENTION : Le mot de passe ci-dessous est le hash de "admin123" (généré via password_hash)
-- Tu devras créer un script PHP pour changer ce mot de passe plus tard.
INSERT INTO `users` (`username`, `password`, `email`) VALUES
    ('Jean-François', '$2y$10$8.w.1/5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0.1.2.3.4.5.6', 'info@saniflo.be');
-- Note: Remplace le hash ci-dessus par un vrai hash généré par ton code PHP.

-- ========================================================
-- 3. Table : SERVICES
-- ========================================================

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `title` varchar(100) NOT NULL,
                            `description` text NOT NULL,
                            `icon` varchar(50) NOT NULL, -- Classe CSS (ex: fa-fire)
                            `display_order` int(11) DEFAULT 0, -- Pour trier l'ordre d'affichage
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données Services
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
                            `description` text,
                            `image_url` varchar(255) NOT NULL,
                            `date_completion` date DEFAULT NULL,
                            `created_at` datetime DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données (Exemple vide ou fictif pour tester)
INSERT INTO `projects` (`title`, `description`, `image_url`, `date_completion`) VALUES
    ('Salle de bain moderne à Wavre', 'Rénovation complète avec douche à l\'italienne.', 'img/portfolio/sdb-wavre.jpg', '2023-11-15');

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

-- Données Team
INSERT INTO `team` (`name`, `role`, `bio`, `image_url`) VALUES
                                                            ('Jean-François Dengis', 'Gérant & Expert Technique', 'Issu d\'une formation technique en sanitaire et plomberie, il fonde Saniflo avec passion. Expert Viessmann et formateur.', 'img/jf-dengis.jpg'),
                                                            ('Florence Lambinon', 'Gérante & Administration', 'Diplômée en techniques cinématographiques et sciences-économiques. Elle gère l\'administration et le lien clientèle.', 'img/florence.jpg');

-- ========================================================
-- 6. Table : CERTIFICATIONS (Agréments)
-- ========================================================

DROP TABLE IF EXISTS `certifications`;
CREATE TABLE `certifications` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `region` varchar(50) NOT NULL COMMENT 'Wallonie, Bruxelles, Flandre ou Général',
                                  `title` varchar(100) NOT NULL,
                                  `number` varchar(100) NOT NULL,
                                  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données Certifications
INSERT INTO `certifications` (`region`, `title`, `number`) VALUES
                                                               ('Général', 'QUALIWALL (Solaire thermique)', 'N° 00246'),
                                                               ('Général', 'Cerga (Installateur Gaz)', 'N° 02-03038-NP'),
                                                               ('Bruxelles', 'Installateur Gaz (TG1 / TG2)', '1181988 / 001232593'),
                                                               ('Bruxelles', 'Installateur Mazout', '001232577'),
                                                               ('Bruxelles', 'Chauffagiste Agréé', '001232609'),
                                                               ('Wallonie', 'Installateur Gaz (TG1 / TG2)', '00711'),
                                                               ('Wallonie', 'Installateur Mazout (L1)', 'TF16480'),
                                                               ('Flandre', 'Installateur Gaz (G1 / G2)', 'GV32834'),
                                                               ('Flandre', 'Installateur Mazout', 'TV42728');

-- ========================================================
-- 7. Table : MESSAGES (Formulaire de contact)
-- ========================================================

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `nom` varchar(100) NOT NULL,
                            `email` varchar(150) NOT NULL,
                            `telephone` varchar(20) DEFAULT NULL,
                            `message` text NOT NULL,
                            `is_read` tinyint(1) DEFAULT 0, -- Pour marquer comme "Lu" dans l'admin
                            `date_envoi` datetime DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;