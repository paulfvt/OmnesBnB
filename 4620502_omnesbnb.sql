-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : fdb1027.your-hosting.net
-- Généré le : dim. 18 mai 2025 à 20:17
-- Version du serveur : 8.0.32
-- Version de PHP : 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `4620502_omnesbnb`
--

-- --------------------------------------------------------

--
-- Structure de la table `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `property_id` int NOT NULL,
  `user_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `guests` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `bookings`
--

INSERT INTO `bookings` (`id`, `property_id`, `user_id`, `start_date`, `end_date`, `guests`, `total_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2025-05-15', '2025-05-20', 1, 225.00, 'cancelled', '2025-04-14 19:24:08', '2025-04-15 09:21:16'),
(2, 2, 3, '2025-06-10', '2025-06-17', 3, 595.00, 'cancelled', '2025-04-14 19:24:08', '2025-04-15 20:43:23'),
(13, 1, 1, '2025-05-03', '2025-05-05', 1, 90.00, 'confirmed', '2025-05-03 14:26:20', '2025-05-03 14:27:43'),
(9, 7, 3, '2025-04-16', '2025-04-17', 1, 2.00, 'cancelled', '2025-04-15 20:45:41', '2025-04-15 20:47:11'),
(5, 3, 2, '2025-04-15', '2025-04-16', 1, 35.00, 'cancelled', '2025-04-15 10:07:59', '2025-04-15 10:10:21'),
(6, 2, 2, '2025-08-04', '2025-08-08', 1, 340.00, 'cancelled', '2025-04-15 10:11:31', '2025-04-15 20:41:45'),
(11, 3, 2, '2025-04-16', '2025-04-17', 1, 35.00, 'confirmed', '2025-04-15 20:57:24', '2025-04-15 20:57:24'),
(10, 1, 3, '2025-05-02', '2025-05-07', 1, 225.00, 'cancelled', '2025-04-15 20:49:52', '2025-04-15 20:50:39'),
(12, 7, 3, '2025-04-23', '2025-04-24', 1, 2.00, 'cancelled', '2025-04-15 21:06:52', '2025-04-17 10:06:33'),
(14, 1, 1, '2025-05-15', '2025-05-17', 1, 90.00, 'confirmed', '2025-05-08 17:46:54', '2025-05-10 19:57:22');

-- --------------------------------------------------------

--
-- Structure de la table `favorites`
--

CREATE TABLE `favorites` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `property_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `property_id`, `created_at`) VALUES
(3, 3, 1, '2025-04-14 19:24:08'),
(28, 2, 3, '2025-04-15 15:15:01');

-- --------------------------------------------------------

--
-- Structure de la table `properties`
--

CREATE TABLE `properties` (
  `id` int NOT NULL,
  `owner_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `property_type` enum('Location complète','Colocation','Je libère mon logement') NOT NULL,
  `location` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `surface_area` int NOT NULL,
  `rooms` int NOT NULL,
  `max_guests` int NOT NULL,
  `amenities` text,
  `main_image` varchar(255) DEFAULT NULL,
  `available_from` date DEFAULT NULL,
  `available_to` date DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `properties`
--

INSERT INTO `properties` (`id`, `owner_id`, `title`, `description`, `property_type`, `location`, `address`, `city`, `postal_code`, `price`, `surface_area`, `rooms`, `max_guests`, `amenities`, `main_image`, `available_from`, `available_to`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 2, 'Studio cosy près du campus', 'Petit studio idéal pour un étudiant, proche des transports et du campus. ', 'Location complète', 'Paris 15ème', '123 rue de la Convention', 'Paris', '75015', 45.00, 20, 1, 1, 'Wi-Fi,Cuisine équipée,Salle de bain privée', 'assets/property_images/appart1.jpg', '2025-05-01', '2025-07-30', 1, '2025-04-14 19:24:08', '2025-05-18 19:56:58'),
(2, 3, 'Appartement moderne au centre-ville', 'Bel appartement moderne avec 2 chambres, idéal pour colocation.', 'Je libère mon logement', 'Lyon 3eme', '56 rue Bonnand', 'Lyon', '69003', 85.00, 60, 3, 4, 'Wi-Fi,TV,Machine à laver,Cuisine équipée,Terrasse', 'assets/property_images/appart2.jpg', '2025-06-01', '2025-09-30', 1, '2025-04-14 19:24:08', '2025-05-18 19:57:35'),
(16, 7, 'BatCave * Gotham', 'La Grotte du Chevalier Noir : Une Retraite Urbaine Unique\r\n\r\nDécouvrez un sanctuaire secret au cœur de Gotham (ou presque !). Cette propriété souterraine exceptionnelle offre une expérience de séjour inégalée, alliant luxe discret et commodités high-tech.  Anciennement utilisée comme base d\'opérations par un justicier légendaire, la Grotte a été rénovée pour offrir un confort moderne tout en conservant son charme unique.\r\n\r\nCaractéristiques uniques :\r\n\r\nEspaces de vie : Un vaste salon troglodyte avec une cheminée à énergie géothermique, une bibliothèque bien fournie (avec des éditions rares !), et un espace de divertissement avec un système audio-visuel à la pointe.\r\nChambres : Deux suites spacieuses avec lits king-size, literie en soie, et salles de bain privatives avec chromothérapie.\r\nSalle d\'entraînement : Entièrement équipée pour maintenir votre forme physique (équipement d\'escalade, tapis de course high-tech, etc.).\r\nGarage sécurisé : Pour véhicules de luxe (ou Batmobiles, si vous en possédez une).\r\nCommodités spéciales :\r\nSystème de sécurité avancé (accès biométrique, surveillance 24h/24).\r\nConcierge personnel (Alfred, si disponible).\r\nService de blanchisserie express (pour les capes et les costumes).\r\nÀ savoir : L\'emplacement exact est confidentiel pour des raisons de sécurité. Le transport depuis un point de rendez-vous est inclus.\r\nIdéal pour : Les voyageurs exigeants à la recherche d\'une expérience exclusive, les amateurs de technologie, et ceux qui apprécient un peu de mystère.\r\n\r\nRéservez votre séjour et découvrez le luxe caché de la Grotte du Chevalier Noir !', 'Location complète', 'Paris', 'Av. Gustave Eiffel', 'Paris', '75007', 1500.00, 680, 3, 2, '0', 'assets/property_images/property_682a3d433efbe.jpeg', '2025-05-18', '2026-05-07', 1, '2025-05-18 20:04:19', '2025-05-18 20:04:19'),
(11, 1, 'Villa de fou', 'Belle maison, ozefihezofhezoifhdsvdsfdsfdsfds', 'Je libère mon logement', '', 'Neuille sur seine', 'Paris', '75000', 200.00, 250, 4, 6, 'Free parking', 'assets/property_images/appart4.jpg', '2025-04-26', '0000-00-00', 1, '2025-04-25 13:27:55', '2025-05-18 19:21:18'),
(13, 2, 'Appartement  Vichy', 'Studio de 40m2 au calme. Situé au coeur de Vichy. Proche du Grand Marché.\\\\r\\\\nUne pièce de vie avec cuisine aménagée.\\\\r\\\\nUne chambre/coin nuit avec armoire de rangement.\\\\r\\\\nSalle de bain avec toilettes. Petit espace que nous avons essayé de rendre agréable à un prix accessible.\\\\r\\\\nN\\\\\\\'hésitez pas à nous solliciter pour une arrivée anticipée ou un départ tardif. Les horaires peuvent être élargis pour faciliter et rendre plus confortable votre séjour.', 'Location complète', 'Grand Marché', '34 rue Saint Dominique', 'Vichy', '3200', 80.00, 40, 1, 2, '0', 'assets/property_images/property_682a361af0fa4.jpeg', '2025-05-18', '2025-12-31', 1, '2025-05-18 19:33:47', '2025-05-18 19:55:44'),
(14, 2, 'Manoir HauteGente', 'Évadez-vous dans l\'élégance intemporelle du Manoir de l\'Aube Tranquille. Ce joyau historique, niché au cœur d\'un domaine verdoyant, offre une expérience de séjour inoubliable. Avec ses vastes pièces, ses cheminées majestueuses et son jardin enchanteur, il est le refuge parfait pour les familles, les groupes d\'amis et les événements spéciaux. Réservez votre séjour et vivez la vie de château !', 'Location complète', 'Manoir', 'Manoir Hautegente, 260 All. du Manoir', 'Coly-Saint-Amand', '24120', 560.00, 520, 12, 12, '0', 'assets/property_images/property_682a3857dd302.jpeg', '2025-05-18', '2025-12-31', 1, '2025-05-18 19:43:19', '2025-05-18 19:43:19'),
(15, 9, 'Château de Versailles', 'Séjournez au Cœur de l\'Histoire : Une Nuit à Versailles\r\n\r\nDécouvrez l\'emblématique Château de Versailles comme jamais auparavant ! Cette location unique vous offre une immersion dans le faste de la cour de France. Imaginez-vous, vous êtes les hôtes privilégiés de ce site classé au patrimoine mondial de l\'UNESCO.\r\n\r\nCe que votre séjour comprend :\r\n\r\nChambres royales : Des appartements richement décorés, inspirés du style Louis XIV.\r\nEspaces de vie exclusifs : L\'accès à la Galerie des Glaces pour une soirée inoubliable.\r\nJardins à la française : Promenades privées dans les jardins, avec leurs fontaines et leurs statues.\r\n\r\nCommodités spéciales :\r\nMajordome pour répondre à vos besoins.\r\nVisites guidées privées par un historien de l\'art.\r\nPetit-déjeuner servi dans la chambre de la Reine.\r\n\r\nIdéal pour : Les passionnés d\'histoire, les amoureux de l\'art, et tous ceux qui rêvent de vivre la vie de château.\r\n\r\nRéservez votre séjour et laissez la magie de Versailles opérer !', 'Location complète', 'Versailles', 'Place d\'Armes', 'Versailles', '78000', 10000.00, 2400, 56, 2, '0', 'assets/property_images/property_682a3b4031922.jpeg', '2025-05-18', '2026-06-18', 1, '2025-05-18 19:55:44', '2025-05-18 19:55:44');

-- --------------------------------------------------------

--
-- Structure de la table `property_images`
--

CREATE TABLE `property_images` (
  `id` int NOT NULL,
  `property_id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `property_images`
--

INSERT INTO `property_images` (`id`, `property_id`, `image_path`, `created_at`) VALUES
(1, 11, 'assets/property_images/property_680b8ddb6c40d.jpg', '2025-04-25 13:27:55'),
(2, 11, 'assets/property_images/property_680b8ddb6c8fc.jpg', '2025-04-25 13:27:55'),
(3, 13, 'assets/property_images/property_682a361b0113a.jpeg', '2025-05-18 19:33:47'),
(4, 13, 'assets/property_images/property_682a361b01272.jpeg', '2025-05-18 19:33:47'),
(5, 13, 'assets/property_images/property_682a361b01342.jpeg', '2025-05-18 19:33:47'),
(6, 14, 'assets/property_images/property_682a3857dd4e3.jpeg', '2025-05-18 19:43:19'),
(7, 14, 'assets/property_images/property_682a3857dd5c8.jpeg', '2025-05-18 19:43:19'),
(8, 14, 'assets/property_images/property_682a3857dd6ab.jpeg', '2025-05-18 19:43:19'),
(9, 15, 'assets/property_images/property_682a3b4031acd.jpeg', '2025-05-18 19:55:44'),
(10, 15, 'assets/property_images/property_682a3b4031bce.jpeg', '2025-05-18 19:55:44'),
(11, 15, 'assets/property_images/property_682a3b4031dd5.jpeg', '2025-05-18 19:55:44'),
(12, 15, 'assets/property_images/property_682a3b4031eaf.jpeg', '2025-05-18 19:55:44'),
(13, 15, 'assets/property_images/property_682a3b4031fb8.jpeg', '2025-05-18 19:55:44'),
(14, 16, 'assets/property_images/property_682a3d433f19c.jpeg', '2025-05-18 20:04:19'),
(15, 16, 'assets/property_images/property_682a3d433f267.jpeg', '2025-05-18 20:04:19'),
(16, 16, 'assets/property_images/property_682a3d433f342.jpeg', '2025-05-18 20:04:19'),
(17, 16, 'assets/property_images/property_682a3d433f404.jpeg', '2025-05-18 20:04:19'),
(18, 16, 'assets/property_images/property_682a3d433f4ed.jpeg', '2025-05-18 20:04:19');

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `reviews`
--

INSERT INTO `reviews` (`id`, `booking_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'Excellent studio, parfait pour mes besoins. Très proche du campus.', '2025-04-14 19:24:08', '2025-04-14 19:24:08'),
(2, 2, 4, 'Très bel appartement, bien équipé. Un peu bruyant le soir. Je recommande pour les plus fêtards ( pour toi coco en gros)', '2025-04-14 19:24:08', '2025-05-18 20:02:22'),
(3, 3, 5, 'Chambre parfaite et colocataires sympathiques!', '2025-04-14 19:24:08', '2025-04-14 19:24:08');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'assets/default-profile.jpg',
  `user_type` enum('student','staff','admin') DEFAULT 'student',
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone_number`, `profile_image`, `user_type`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 'admin@ece.fr', '$2y$10$vAam5RtDOEcUXtGaa2c28Oha6Lie7AeSYuTxzsGCADllGvFH6y.rG', 'Admin', 'Admin', '+3312345677', 'assets/profile_images/pdpAdmin.png', 'admin', 1, '2025-04-14 19:24:08', '2025-05-18 19:50:37'),
(2, 'etudiant@edu.ece.fr', '$2y$10$vAam5RtDOEcUXtGaa2c28Oha6Lie7AeSYuTxzsGCADllGvFH6y.rG', 'Rayan', 'Cherki', '+3387654321', 'assets/profile_images/pdpEtu.png', 'student', 1, '2025-04-14 19:24:08', '2025-04-15 10:04:58'),
(3, 'prof@ece.fr', '$2y$10$vAam5RtDOEcUXtGaa2c28Oha6Lie7AeSYuTxzsGCADllGvFH6y.rG', 'Professeur', 'ECE', '+3376543210', 'assets/profile_images/profile_67fe53c784add.png', 'staff', 1, '2025-04-14 19:24:08', '2025-05-06 14:16:52'),
(7, 'corentin.jourdan@edu.ece.fr', '$2y$10$5ppRq87TItCiujNJGtfyDOmWuDAngHgdJ0L.uyyK/Y9gfAGeo1HCK', 'Corentin', 'Jourdan', '+33611445215', 'assets/profile_images/profile_67fe53c784add.png', 'student', 0, '2025-05-18 19:26:55', '2025-05-18 19:50:00'),
(9, 'quentin.drigeard@edu.ece.fr', '$2y$10$LjCtvG/rSaKPfaSHwhsZ2Ovyf.wCG9BfUy0LgRhSX81orGk6A5SMm', 'Quentin', 'Drigeard', '+33674543287', 'assets/profile_images/default-profile.jpg', 'student', 0, '2025-05-18 19:36:57', '2025-05-18 19:50:08');

-- --------------------------------------------------------

--
-- Structure de la table `user_activity`
--

CREATE TABLE `user_activity` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `property_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `user_activity`
--

INSERT INTO `user_activity` (`id`, `user_id`, `activity_type`, `property_id`, `created_at`) VALUES
(1, 2, 'property_view', 1, '2025-04-14 19:24:08'),
(2, 2, 'property_view', 2, '2025-04-14 19:24:08'),
(3, 3, 'property_view', 3, '2025-04-14 19:24:08'),
(4, 2, 'search', NULL, '2025-04-14 19:24:08'),
(5, 3, 'booking', 2, '2025-04-14 19:24:08'),
(6, 1, 'favorite_add', 3, '2025-04-15 08:43:59'),
(7, 1, 'favorite_remove', 3, '2025-04-15 08:46:19'),
(8, 1, 'favorite_add', 1, '2025-04-15 08:46:51'),
(9, 1, 'favorite_remove', 1, '2025-04-15 08:46:52'),
(10, 1, 'favorite_add', 1, '2025-04-15 08:46:53'),
(11, 1, 'favorite_remove', 1, '2025-04-15 08:46:54'),
(12, 1, 'favorite_add', 3, '2025-04-15 08:49:02'),
(13, 1, 'favorite_remove', 3, '2025-04-15 08:49:03'),
(14, 1, 'favorite_add', 1, '2025-04-15 08:49:12'),
(15, 1, 'favorite_remove', 1, '2025-04-15 08:49:21'),
(16, 1, 'favorite_add', 3, '2025-04-15 08:51:21'),
(17, 1, 'favorite_remove', 3, '2025-04-15 08:51:21'),
(18, 1, 'favorite_add', 1, '2025-04-15 08:52:05'),
(19, 1, 'favorite_remove', 1, '2025-04-15 08:52:09'),
(20, 1, 'property_view', 3, '2025-04-15 08:55:40'),
(21, 1, 'property_view', 3, '2025-04-15 08:58:26'),
(22, 1, 'property_view', 1, '2025-04-15 08:58:35'),
(23, 1, 'property_view', 2, '2025-04-15 08:58:39'),
(24, 1, 'property_view', 3, '2025-04-15 08:58:44'),
(25, 2, 'property_view', 3, '2025-04-15 08:59:24'),
(26, 2, 'property_view', 1, '2025-04-15 08:59:29'),
(27, 2, 'property_view', 2, '2025-04-15 08:59:33'),
(28, 2, 'property_view', 1, '2025-04-15 09:07:43'),
(29, 2, 'property_view', 1, '2025-04-15 09:08:15'),
(30, 2, 'property_view', 3, '2025-04-15 09:09:36'),
(31, 2, 'favorite_remove', 3, '2025-04-15 09:14:54'),
(32, 2, 'favorite_remove', 2, '2025-04-15 09:15:36'),
(33, 2, 'favorite_add', 2, '2025-04-15 09:15:37'),
(34, 2, 'property_view', 3, '2025-04-15 09:16:02'),
(35, 2, 'property_view', 1, '2025-04-15 09:16:44'),
(36, 2, 'property_view', 3, '2025-04-15 09:21:19'),
(37, 2, 'property_view', 3, '2025-04-15 09:22:36'),
(38, 2, 'property_view', 3, '2025-04-15 09:22:40'),
(39, 2, 'property_view', 3, '2025-04-15 09:23:58'),
(40, 2, 'property_view', 2, '2025-04-15 10:01:47'),
(41, 2, 'property_view', 2, '2025-04-15 10:02:26'),
(42, 2, 'property_view', 3, '2025-04-15 10:02:30'),
(43, 2, 'property_view', 3, '2025-04-15 10:04:05'),
(44, 2, 'property_view', 3, '2025-04-15 10:07:37'),
(45, 2, 'property_view', 1, '2025-04-15 10:08:42'),
(46, 2, 'property_view', 2, '2025-04-15 10:10:26'),
(47, 2, 'property_view', 2, '2025-04-15 10:10:41'),
(48, 2, 'property_view', 2, '2025-04-15 10:11:02'),
(49, 2, 'booking', 2, '2025-04-15 10:11:31'),
(50, 2, 'property_view', 2, '2025-04-15 10:11:35'),
(51, 2, 'property_created', 4, '2025-04-15 10:21:23'),
(52, 2, 'property_view', 4, '2025-04-15 10:21:46'),
(53, 1, 'property_view', 3, '2025-04-15 11:13:50'),
(54, 1, 'property_view', 4, '2025-04-15 11:15:18'),
(55, 1, 'property_view', 3, '2025-04-15 11:43:52'),
(56, 1, 'property_view', 3, '2025-04-15 11:48:46'),
(57, 1, 'property_view', 3, '2025-04-15 11:53:29'),
(58, 1, 'property_view', 4, '2025-04-15 11:53:41'),
(59, 1, 'property_view', 4, '2025-04-15 11:57:31'),
(60, 3, 'property_view', 1, '2025-04-15 11:58:41'),
(61, 2, 'property_view', 2, '2025-04-15 12:00:19'),
(62, 2, 'property_view', 2, '2025-04-15 12:04:51'),
(63, 2, 'property_view', 1, '2025-04-15 12:06:12'),
(64, 2, 'property_view', 2, '2025-04-15 12:06:32'),
(65, 2, 'property_view', 1, '2025-04-15 12:06:39'),
(66, 2, 'property_view', 2, '2025-04-15 12:06:49'),
(67, 2, 'property_view', 2, '2025-04-15 12:29:54'),
(68, 2, 'favorite_remove', 2, '2025-04-15 12:30:10'),
(69, 3, 'favorite_add', 3, '2025-04-15 13:10:41'),
(70, 3, 'favorite_remove', 3, '2025-04-15 13:10:42'),
(71, 3, 'property_view', 3, '2025-04-15 13:11:05'),
(72, 3, 'property_view', 3, '2025-04-15 13:12:10'),
(73, 3, 'property_view', 4, '2025-04-15 13:12:17'),
(74, 3, 'booking', 4, '2025-04-15 13:13:02'),
(75, 2, 'favorite_add', 3, '2025-04-15 13:14:15'),
(76, 2, 'favorite_remove', 3, '2025-04-15 13:20:39'),
(77, 2, 'favorite_add', 3, '2025-04-15 13:20:39'),
(78, 2, 'favorite_remove', 3, '2025-04-15 13:25:50'),
(79, 2, 'favorite_add', 3, '2025-04-15 13:25:57'),
(80, 2, 'property_view', 3, '2025-04-15 13:28:00'),
(81, 2, 'property_view', 3, '2025-04-15 13:28:00'),
(82, 2, 'favorite_remove', 3, '2025-04-15 13:28:06'),
(83, 2, 'favorite_add', 3, '2025-04-15 13:28:07'),
(84, 2, 'favorite_remove', 3, '2025-04-15 13:32:50'),
(85, 2, 'favorite_add', 3, '2025-04-15 13:35:07'),
(86, 2, 'favorite_remove', 3, '2025-04-15 13:35:23'),
(87, 2, 'favorite_add', 3, '2025-04-15 13:35:32'),
(88, 2, 'favorite_remove', 3, '2025-04-15 13:40:28'),
(89, 2, 'favorite_add', 3, '2025-04-15 13:40:38'),
(90, 2, 'property_view', 3, '2025-04-15 13:40:48'),
(91, 2, 'favorite_remove', 3, '2025-04-15 13:41:13'),
(92, 2, 'favorite_add', 3, '2025-04-15 13:41:22'),
(93, 2, 'favorite_remove', 3, '2025-04-15 13:42:36'),
(94, 2, 'favorite_add', 3, '2025-04-15 13:45:34'),
(95, 2, 'favorite_remove', 3, '2025-04-15 13:45:39'),
(96, 2, 'favorite_add', 3, '2025-04-15 13:45:43'),
(97, 2, 'favorite_add', 1, '2025-04-15 13:45:44'),
(98, 2, 'favorite_add', 2, '2025-04-15 13:45:45'),
(99, 2, 'property_view', 3, '2025-04-15 13:45:52'),
(100, 2, 'favorite_remove', 1, '2025-04-15 13:47:06'),
(101, 2, 'favorite_remove', 2, '2025-04-15 13:47:10'),
(102, 2, 'favorite_remove', 3, '2025-04-15 13:51:39'),
(103, 2, 'favorite_add', 3, '2025-04-15 13:51:52'),
(104, 2, 'favorite_add', 1, '2025-04-15 13:51:54'),
(105, 2, 'favorite_remove', 3, '2025-04-15 15:09:12'),
(106, 2, 'favorite_remove', 1, '2025-04-15 15:13:49'),
(107, 2, 'favorite_add', 3, '2025-04-15 15:13:55'),
(108, 2, 'property_view', 3, '2025-04-15 15:14:25'),
(109, 2, 'favorite_remove', 3, '2025-04-15 15:15:00'),
(110, 2, 'favorite_add', 3, '2025-04-15 15:15:01'),
(111, 1, 'property_created', 5, '2025-04-15 18:57:42'),
(112, 1, 'property_view', 5, '2025-04-15 18:57:50'),
(113, 1, 'property_created', 6, '2025-04-15 18:58:33'),
(114, 1, 'property_created', 7, '2025-04-15 19:02:27'),
(115, 1, 'property_view', 7, '2025-04-15 19:02:32'),
(116, 1, 'property_view', 7, '2025-04-15 19:07:26'),
(117, 1, 'property_view', 7, '2025-04-15 19:08:02'),
(118, 1, 'property_view', 7, '2025-04-15 19:08:10'),
(119, 1, 'property_view', 7, '2025-04-15 19:08:56'),
(120, 1, 'property_view', 7, '2025-04-15 19:13:22'),
(121, 1, 'property_view', 7, '2025-04-15 19:14:38'),
(122, 1, 'property_view', 7, '2025-04-15 19:14:42'),
(123, 2, 'favorite_add', 7, '2025-04-15 19:15:41'),
(124, 2, 'favorite_remove', 7, '2025-04-15 19:15:42'),
(125, 2, 'property_view', 7, '2025-04-15 19:15:44'),
(126, 1, 'property_view', 7, '2025-04-15 19:16:51'),
(127, 1, 'property_view', 1, '2025-04-15 19:17:26'),
(128, 1, 'booking', 1, '2025-04-15 19:17:55'),
(129, 1, 'property_view', 1, '2025-04-15 19:18:03'),
(130, 2, 'property_view', 4, '2025-04-15 19:19:48'),
(131, 2, 'property_view', 4, '2025-04-15 19:20:37'),
(132, 2, 'property_view', 2, '2025-04-15 19:23:06'),
(133, 2, 'favorite_add', 2, '2025-04-15 19:23:26'),
(134, 2, 'favorite_remove', 2, '2025-04-15 19:23:28'),
(135, 1, 'property_view', 7, '2025-04-15 20:18:14'),
(136, 1, 'property_view', 7, '2025-04-15 20:27:04'),
(137, 1, 'property_view', 7, '2025-04-15 20:30:21'),
(138, 1, 'property_view', 1, '2025-04-15 20:30:51'),
(139, 3, 'property_view', 7, '2025-04-15 20:45:14'),
(140, 3, 'booking', 7, '2025-04-15 20:45:41'),
(141, 3, 'property_view', 1, '2025-04-15 20:49:37'),
(142, 3, 'booking', 1, '2025-04-15 20:49:52'),
(143, 2, 'property_view', 3, '2025-04-15 20:56:41'),
(144, 2, 'property_view', 3, '2025-04-15 20:57:10'),
(145, 2, 'booking', 3, '2025-04-15 20:57:24'),
(146, 3, 'property_view', 3, '2025-04-15 20:58:13'),
(147, 3, 'property_view', 4, '2025-04-15 21:06:22'),
(148, 3, 'property_view', 7, '2025-04-15 21:06:33'),
(149, 3, 'booking', 7, '2025-04-15 21:06:52'),
(150, 3, 'property_view', 3, '2025-04-15 21:12:32'),
(151, 3, 'favorite_add', 4, '2025-04-15 21:18:22'),
(152, 3, 'property_view', 7, '2025-04-15 21:20:19'),
(153, 3, 'property_view', 1, '2025-04-15 21:22:06'),
(154, 3, 'property_view', 7, '2025-04-15 21:34:03'),
(155, 3, 'property_view', 3, '2025-04-15 21:37:13'),
(156, 3, 'property_view', 3, '2025-04-15 21:40:00'),
(157, 3, 'property_view', 2, '2025-04-15 21:43:31'),
(158, 3, 'property_view', 7, '2025-04-16 08:36:20'),
(159, 5, 'property_view', 1, '2025-04-16 10:03:34'),
(160, 5, 'favorite_add', 7, '2025-04-16 10:04:01'),
(161, 5, 'favorite_remove', 7, '2025-04-16 10:04:25'),
(162, 1, 'property_view', 2, '2025-04-16 10:19:45'),
(163, 1, 'property_view', 3, '2025-04-16 11:22:38'),
(164, 1, 'property_view', 7, '2025-04-16 11:22:47'),
(165, 1, 'property_view', 7, '2025-04-25 08:56:18'),
(166, 1, 'property_view', 3, '2025-04-25 09:05:18'),
(167, 1, 'property_view', 2, '2025-04-25 09:09:10'),
(168, 1, 'property_view', 1, '2025-04-25 09:27:57'),
(169, 1, 'property_view', 1, '2025-04-25 09:42:17'),
(170, 2, 'property_created', 8, '2025-04-25 11:28:51'),
(171, 2, 'property_view', 8, '2025-04-25 11:28:57'),
(172, 2, 'property_view', 7, '2025-04-25 11:30:08'),
(173, 2, 'property_view', 8, '2025-04-25 11:30:15'),
(174, 2, 'property_view', 8, '2025-04-25 11:30:54'),
(175, 2, 'property_view', 2, '2025-04-25 11:31:08'),
(176, 2, 'property_view', 2, '2025-04-25 11:33:41'),
(177, 1, 'property_view', 7, '2025-04-25 11:35:50'),
(178, 1, 'property_view', 7, '2025-04-25 11:36:20'),
(179, 1, 'property_view', 7, '2025-04-25 11:38:35'),
(180, 1, 'property_view', 7, '2025-04-25 11:40:02'),
(181, 1, 'property_created', 9, '2025-04-25 11:42:02'),
(182, 1, 'property_view', 9, '2025-04-25 11:42:06'),
(183, 1, 'property_view', 9, '2025-04-25 11:59:17'),
(184, 1, 'property_view', 9, '2025-04-25 12:06:01'),
(185, 1, 'property_created', 10, '2025-04-25 12:08:42'),
(186, 1, 'property_view', 10, '2025-04-25 12:08:47'),
(187, 1, 'property_view', 7, '2025-04-25 12:08:54'),
(188, 1, 'property_view', 10, '2025-04-25 12:08:58'),
(189, 1, 'property_view', 10, '2025-04-25 12:22:25'),
(190, 1, 'property_view', 10, '2025-04-25 12:22:44'),
(191, 1, 'property_view', 10, '2025-04-25 12:23:25'),
(192, 1, 'property_view', 10, '2025-04-25 12:30:01'),
(193, 1, 'property_created', 11, '2025-04-25 13:27:55'),
(194, 1, 'property_view', 11, '2025-04-25 13:28:03'),
(195, 1, 'property_view', 11, '2025-04-25 13:28:42'),
(196, 1, 'property_view', 11, '2025-04-25 13:30:22'),
(197, 1, 'property_view', 11, '2025-04-25 13:31:04'),
(198, 1, 'property_view', 11, '2025-04-25 13:32:01'),
(199, 1, 'property_view', 11, '2025-04-25 13:32:34'),
(200, 1, 'property_view', 11, '2025-04-25 13:32:42'),
(201, 1, 'property_view', 11, '2025-04-25 13:35:02'),
(202, 1, 'property_view', 11, '2025-04-25 13:36:11'),
(203, 1, 'property_view', 11, '2025-04-25 13:36:33'),
(204, 1, 'property_created', 12, '2025-04-28 12:41:10'),
(205, 1, 'property_view', 7, '2025-04-28 12:56:50'),
(206, 1, 'favorite_add', 7, '2025-05-03 08:43:12'),
(207, 1, 'favorite_remove', 7, '2025-05-03 08:43:13'),
(208, 1, 'property_view', 11, '2025-05-03 08:44:08'),
(209, 1, 'favorite_add', 1, '2025-05-03 14:23:47'),
(210, 1, 'favorite_remove', 1, '2025-05-03 14:23:50'),
(211, 1, 'property_view', 11, '2025-05-03 14:23:53'),
(212, 1, 'property_view', 11, '2025-05-03 14:24:42'),
(213, 1, 'property_view', 1, '2025-05-03 14:25:30'),
(214, 1, 'booking', 1, '2025-05-03 14:26:20'),
(215, 2, 'property_view', 1, '2025-05-03 15:02:15'),
(216, 1, 'property_view', 1, '2025-05-06 13:15:26'),
(217, 1, 'property_view', 1, '2025-05-06 13:35:21'),
(218, 3, 'property_view', 1, '2025-05-06 14:17:02'),
(219, 1, 'property_view', 1, '2025-05-08 17:45:54'),
(220, 1, 'booking', 1, '2025-05-08 17:46:54'),
(221, 2, 'property_view', 1, '2025-05-10 19:54:25'),
(222, 2, 'property_view', 11, '2025-05-10 19:55:42'),
(223, 2, 'property_view', 7, '2025-05-10 19:57:41'),
(224, 2, 'property_view', 3, '2025-05-10 20:00:21'),
(225, 3, 'favorite_add', 7, '2025-05-12 16:02:27'),
(226, 3, 'favorite_remove', 7, '2025-05-12 16:02:28'),
(227, 1, 'property_view', 7, '2025-05-12 17:05:36'),
(228, 1, 'property_view', 1, '2025-05-15 12:53:34'),
(229, 1, 'property_view', 7, '2025-05-18 19:07:47'),
(230, 1, 'property_view', 3, '2025-05-18 19:15:17'),
(231, 1, 'property_view', 2, '2025-05-18 19:15:36'),
(232, 2, 'property_created', 13, '2025-05-18 19:33:47'),
(233, 2, 'property_created', 14, '2025-05-18 19:43:19'),
(234, 1, 'property_view', 13, '2025-05-18 19:51:38'),
(235, 9, 'property_created', 15, '2025-05-18 19:55:44'),
(236, 1, 'property_view', 1, '2025-05-18 19:57:43'),
(237, 1, 'property_view', 3, '2025-05-18 19:57:50'),
(238, 1, 'property_view', 2, '2025-05-18 19:58:16'),
(239, 1, 'property_view', 3, '2025-05-18 20:00:09'),
(240, 1, 'property_view', 13, '2025-05-18 20:01:04'),
(241, 1, 'property_view', 2, '2025-05-18 20:02:31'),
(242, 1, 'property_view', 2, '2025-05-18 20:02:41'),
(243, 1, 'property_view', 1, '2025-05-18 20:03:35'),
(244, 7, 'property_created', 16, '2025-05-18 20:04:19'),
(245, 2, 'property_view', 15, '2025-05-18 20:05:39'),
(246, 2, 'property_view', 16, '2025-05-18 20:05:51'),
(247, 1, 'property_view', 15, '2025-05-18 20:08:59'),
(248, 1, 'property_view', 15, '2025-05-18 20:15:49'),
(249, 1, 'property_view', 15, '2025-05-18 20:15:51'),
(250, 1, 'property_view', 1, '2025-05-18 20:15:57');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`property_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Index pour la table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Index pour la table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `property_id` (`property_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
