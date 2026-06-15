-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 15 juin 2026 à 14:31
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
-- Structure de la table `avis_clients`
--

CREATE TABLE `avis_clients` (
  `id` int(11) NOT NULL,
  `nom_client` varchar(100) DEFAULT NULL,
  `note` int(11) DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp(),
  `order_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis_clients`
--

INSERT INTO `avis_clients` (`id`, `nom_client`, `note`, `date_avis`, `order_id`) VALUES
(1, NULL, 2, '2026-06-05 21:35:58', 0),
(2, NULL, 3, '2026-06-05 21:37:41', 0),
(3, NULL, 3, '2026-06-08 16:30:44', 0),
(4, 'qwer', 2, '2026-06-08 16:45:43', 51),
(5, 'qwerty', 1, '2026-06-08 16:51:43', 52),
(12, 'Anonyme', 3, '2026-06-12 18:23:51', 69);

-- --------------------------------------------------------

--
-- Structure de la table `candidatures`
--

CREATE TABLE `candidatures` (
  `id` int(11) NOT NULL,
  `nom_complet` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `poste` varchar(100) NOT NULL,
  `cv_url` varchar(255) NOT NULL,
  `lettre_url` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `statut` varchar(20) DEFAULT 'En attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `candidatures`
--

INSERT INTO `candidatures` (`id`, `nom_complet`, `email`, `telephone`, `poste`, `cv_url`, `lettre_url`, `created_at`, `statut`) VALUES
(8, 'bouteille', 'djoujesica86@gmail.com', '656876238', 'commercial', '1780501710_337436-djoumessi-cae-lettre.pdf', '1780501710_EVALUATION  DJOUMESSI OIIQ FINAL.pdf', '2026-06-11 12:48:43', 'En attente'),
(9, 'Martin', 'jesica6@gmail.com', '656876238', 'production', '1780502225_certif_learnhub.pdf', '1780502225_f5dt03QeI00uP8P613rmt6wnxsSkzJD1BU5wI2qO.pdf', '2026-06-11 12:48:43', 'En attente'),
(11, 'djoumessi', 'djoujesica86@gmail.com', '656876238', 'commercial', 'cand_6a2aa0ea11b046.80892310_7ca3aa984556009b.pdf', 'cand_6a2aa0ea120b65.44243117_fe9aae66bb1ef4f7.pdf', '2026-06-11 12:50:02', 'Validé'),
(12, 'diloua', 'angelinediloua@gmail.com', '687490079', 'production', 'cand_6a2ac33ce8eb42.16966781_914e2d7f0278969e.pdf', 'cand_6a2ac33ce91175.01852228_e66f6e7436ce6f9b.pdf', '2026-06-11 15:16:28', 'Refusé'),
(13, 'djakou Yann', 'yannkevin29@gmail.com', '697873993', 'marketting', 'cand_6a2ac4292457e2.99370227_23435e4290766571.pdf', 'cand_6a2ac429248997.41472218_091c94bf991415b8.pdf', '2026-06-11 15:20:25', 'Validé');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `cni` varchar(50) DEFAULT NULL,
  `cni_file` varchar(255) DEFAULT NULL,
  `num_commercial` varchar(50) DEFAULT NULL,
  `nom_marche` varchar(100) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `bon_commande` varchar(255) DEFAULT NULL,
  `details_panier` text DEFAULT NULL,
  `date_commande` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `nom`, `prenom`, `cni`, `cni_file`, `num_commercial`, `nom_marche`, `region`, `bon_commande`, `details_panier`, `date_commande`) VALUES
(25, 'djoumessi ', 'jesica', '12ltt23JKas2', '1780601210_cni_VisionOptique2026-SystmedeGestion.pdf', '432', 'koulouloun', 'Littoral', '1780601210_bon_CabinetOptique2026.pdf', 'Détails de la commande :\r\n- 10 carton(s) de mayonnaise (145 000 FCFA)\r\n\r\nTOTAL : 145 000 FCFA', '2026-06-04 20:26:50');

-- --------------------------------------------------------

--
-- Structure de la table `commandes_nouvelles`
--

CREATE TABLE `commandes_nouvelles` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `cni` varchar(50) DEFAULT NULL,
  `cni_file` varchar(255) DEFAULT NULL,
  `num_commercial` varchar(50) DEFAULT NULL,
  `nom_marche` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `bon_commande` varchar(255) DEFAULT NULL,
  `date_commande` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commande_details`
--

CREATE TABLE `commande_details` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(23, 'Martin', '54678909', 'Détails de la commande :\r\n- 1 carton(s) de mayonnaise (12 500 FCFA)\r\n\r\nTOTAL : 12 500 FCFA', '2026-06-04 15:16:24'),
(31, 'djoumessi', '6789876543', 'Détails de la commande :\r\n- 10 carton(s) de mayonnaise (145 000 FCFA)\r\n\r\nTOTAL : 145 000 FCFA', '2026-06-04 19:00:09'),
(32, 'djoumessi', '6789876543', 'Détails de la commande :\r\n- 10 carton(s) de gala  (45 000 FCFA)\r\n\r\nTOTAL : 45 000 FCFA', '2026-06-04 19:45:28'),
(38, 'martin', '6789876543', 'Détails de la commande :\r\n- 10 carton(s) de mayonnaise (145 000 FCFA)\r\n\r\nTOTAL : 145 000 FCFA', '2026-06-05 11:50:57'),
(78, 'djoumessi jesica', '656876238', 'Détails de la commande :\r\n- 1 carton(s) de mayonnaise (14 500 FCFA)\r\n\r\nTOTAL : 14 500 FCFA', '2026-06-09 11:54:36'),
(79, 'dongmo odette', '679188003', 'Détails de la commande :\r\n- 1 carton(s) de mayonnaise (12 500 FCFA)\r\n\r\nTOTAL : 12 500 FCFA', '2026-06-09 12:46:50'),
(80, 'dongmo odette', '234545678', 'Détails de la commande :\r\n- 15 carton(s) de mayonnaise (187 500 FCFA)\r\n\r\nTOTAL : 187 500 FCFA', '2026-06-09 13:12:31'),
(81, 'dongmo odette', '679188003', 'Détails de la commande :\r\n- 10 carton(s) de mayonnaise (125 000 FCFA)\r\n\r\nTOTAL : 125 000 FCFA', '2026-06-09 16:12:29'),
(82, 'dongmo odette', '12345678', 'Détails de la commande :\r\n- 5 carton(s) de mayonnaise (62 500 FCFA)\r\n\r\nTOTAL : 62 500 FCFA', '2026-06-09 17:28:39'),
(83, 'dongmo odette', '12345678', 'Détails de la commande :\r\n- 10 boite(s) de mayonnaise (145 000 FCFA)\r\n\r\nTOTAL : 145 000 FCFA', '2026-06-12 17:07:07'),
(84, 'dongmo odette', '12345678', 'Détails de la commande :\r\n- 100 carton(s) de seau mayonnaise (1 250 000 FCFA)\r\n\r\nTOTAL : 1 250 000 FCFA', '2026-06-12 17:11:10'),
(85, 'dongmo odette', '12345678', 'Détails de la commande :\r\n- 100 carton(s) de seau mayonnaise (1 250 000 FCFA)\r\n\r\nTOTAL : 1 250 000 FCFA', '2026-06-12 17:13:13');

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
(20, 'seau mayonnaise', 'gala_6a105a8f813e57.32290581.jpg', '2026-05-22 13:30:55'),
(22, 'video', 'gala_6a1ee3b534fe04.60167080.jpeg', '2026-06-02 14:07:49');

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
(1, 'mayonnaise', '500g', NULL, '1779047259_6a0a1b5beffee.jpeg', '2026-05-17 10:08:16', 3000, 16, 0),
(7, 'bouteille', '1L', NULL, '1779277625_6a0d9f396386d.jpg', '2026-05-17 19:46:41', 1500, 0, 0),
(8, 'bouteille', '2L', NULL, '1779278269_6a0da1bd07c94.jpeg', '2026-05-20 10:28:50', 1500, 0, 0),
(9, 'seau mayonnaise', '1L', NULL, '1779401821_6a0f845d2edac.jpg', '2026-05-21 13:14:23', 5000, 231, 0),
(10, 'seau mayonnaise', '5L', NULL, '1779453551_6a104e6f27903.jpg', '2026-05-21 13:15:02', 12500, 822, 0),
(12, 'gala ', '500ml', NULL, '1780326578_6a1da0b2d4977.jpeg', '2026-06-01 15:09:38', 3500, 42, 0),
(13, 'gala ', '500ml', NULL, '1780326710_6a1da136399a3.jpeg', '2026-06-01 15:11:50', 4500, 0, 0),
(14, 'mayonnaise', '500g', NULL, '1780413326_6a1ef38ea6669.png', '2026-06-02 15:15:26', 12500, 21, 0),
(16, 'mayonnaise', '500g', NULL, '1780413701_6a1ef505a2d71.png', '2026-06-02 15:21:41', 14500, 5, 0);

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
-- Index pour la table `avis_clients`
--
ALTER TABLE `avis_clients`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `candidatures`
--
ALTER TABLE `candidatures`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `commandes_nouvelles`
--
ALTER TABLE `commandes_nouvelles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `commande_details`
--
ALTER TABLE `commande_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `product_id` (`product_id`);

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
-- AUTO_INCREMENT pour la table `avis_clients`
--
ALTER TABLE `avis_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `candidatures`
--
ALTER TABLE `candidatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT pour la table `commandes_nouvelles`
--
ALTER TABLE `commandes_nouvelles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT pour la table `commande_details`
--
ALTER TABLE `commande_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT pour la table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commande_details`
--
ALTER TABLE `commande_details`
  ADD CONSTRAINT `commande_details_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commande_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
