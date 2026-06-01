-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 28 mai 2026 à 17:37
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gala_agro`
--

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `nom_complet` varchar(150) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contacts`
--

INSERT INTO `contacts` (`id`, `nom_complet`, `telephone`, `message`, `date_envoi`) VALUES
(1, 'Winx', '45678', 'trop petit', '2026-05-17 08:27:27'),
(2, 'jesica', '656876238', 'besoin de 4 cartons de mayonaise', '2026-05-17 09:53:03'),
(3, 'djoumessi horlane', '699105753', 'besoin de 100 cartons de mayonaise', '2026-05-17 12:02:20'),
(5, 'magne oriane', '671411030', '3 palettes de mayonnaise', '2026-05-17 19:56:33'),
(6, 'djoumessi Kerene', '699105753', 'besoin de 5 cartons', '2026-05-19 20:09:31'),
(7, 'Martin', '679188003', 'besoin de 20 cartons', '2026-05-20 10:27:09'),
(10, 'michel', '679188003', 'Bonjour, je souhaite commander directement 8 carton(s) de seau mayonnaise. Merci de me recontacter.', '2026-05-22 14:58:25'),
(11, 'jesica', '54678909', 'Bonjour, je souhaite commander directement 34 carton(s) de seau mayonnaise. Merci de me recontacter au plus vite pour valider les modalités.', '2026-05-22 16:57:15'),
(12, 'ali', '679188003', 'Bonjour, je souhaite commander directement 25 carton(s) de seau mayonnaise. Merci de me recontacter au plus vite pour valider les modalités.', '2026-05-22 17:04:03'),
(13, 'jule toto', '672345689', 'Bonjour, je souhaite commander directement 100 boite(s) de seau mayonnaise. Merci de me recontacter au plus vite pour valider les modalités.', '2026-05-22 18:43:06'),
(14, 'tyfy', '6542165778', 'Bonjour, je souhaite commander directement 13 boite(s) de seau mayonnaise. Merci de me recontacter au plus vite pour valider les modalités.', '2026-05-28 12:05:28');

-- --------------------------------------------------------

--
-- Structure de la table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `titre` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `gallery`
--

INSERT INTO `gallery` (`id`, `titre`, `image_url`, `created_at`) VALUES
(1, 'mayonaise', 'gala_6a097ba47f2578.61675996.jpeg', '2026-05-17 08:26:12'),
(8, 'mayonnaise', 'gala_6a0da00812b844.66572993.jpg', '2026-05-20 11:50:32'),
(10, 'mayonaise', 'gala_6a0da3415a4154.21809411.jpg', '2026-05-20 12:04:17'),
(20, 'seau mayonnaise', 'gala_6a105a8f813e57.32290581.jpg', '2026-05-22 13:30:55');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `format` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `prix` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 1,
  `en_solde` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `nom`, `format`, `description`, `image_url`, `created_at`, `prix`, `stock`, `en_solde`) VALUES
(1, 'mayonaise', '500g', NULL, '1779047259_6a0a1b5beffee.jpeg', '2026-05-17 10:08:16', 3000, 0, 0),
(7, 'bouteille', '1L', NULL, '1779277625_6a0d9f396386d.jpg', '2026-05-17 19:46:41', 1500, 0, 0),
(8, 'bouteille', '2L', NULL, '1779278269_6a0da1bd07c94.jpeg', '2026-05-20 10:28:50', 1500, 0, 0),
(9, 'seau mayonnaise', '1L', NULL, '1779401821_6a0f845d2edac.jpg', '2026-05-21 13:14:23', 5000, 0, 0),
(10, 'seau mayonnaise', '5L', NULL, '1779453551_6a104e6f27903.jpg', '2026-05-21 13:15:02', 12500, 828, 0);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
