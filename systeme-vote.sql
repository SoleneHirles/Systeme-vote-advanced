-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 13 mars 2022 à 23:31
-- Version du serveur : 10.4.21-MariaDB
-- Version de PHP : 8.0.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `systeme-vote`
--

-- --------------------------------------------------------

--
-- Structure de la table `connexion`
--

CREATE TABLE `connexion` (
  `email` varchar(30) NOT NULL,
  `pseudo` varchar(10) NOT NULL,
  `mot de passe` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `electeurs`
--

CREATE TABLE `electeurs` (
  `id` int(255) UNSIGNED NOT NULL,
  `pseudo` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `question`
--

CREATE TABLE `question` (
  `id-sondage` int(255) UNSIGNED NOT NULL,
  `idQuestion` int(255) UNSIGNED NOT NULL,
  `quest` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `reponse-possible`
--

CREATE TABLE `reponse-possible` (
  `id-sondage` int(255) UNSIGNED NOT NULL,
  `id-question` int(255) UNSIGNED NOT NULL,
  `idReponse` int(255) UNSIGNED NOT NULL,
  `reponse` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `reponses`
--

CREATE TABLE `reponses` (
  `id-sondage` int(255) UNSIGNED NOT NULL,
  `id-question` int(255) UNSIGNED NOT NULL,
  `idReponse` int(255) UNSIGNED NOT NULL,
  `votant` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `resultats`
--

CREATE TABLE `resultats` (
  `id` int(255) UNSIGNED NOT NULL,
  `votant` varchar(20) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `sondages`
--

CREATE TABLE `sondages` (
  `id` int(255) UNSIGNED NOT NULL,
  `titre` varchar(100) NOT NULL,
  `publique` tinyint(1) NOT NULL DEFAULT 1,
  `theme` varchar(25) DEFAULT NULL,
  `creation` date NOT NULL DEFAULT current_timestamp(),
  `cloture` date NOT NULL DEFAULT current_timestamp(),
  `createur` varchar(10) NOT NULL,
  `emailCrea` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `connexion`
--
ALTER TABLE `connexion`
  ADD PRIMARY KEY (`pseudo`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `electeurs`
--
ALTER TABLE `electeurs`
  ADD KEY `electeur-pseudo` (`pseudo`),
  ADD KEY `electeur-id` (`id`);

--
-- Index pour la table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`idQuestion`),
  ADD KEY `id-sondage-quest` (`id-sondage`);

--
-- Index pour la table `reponse-possible`
--
ALTER TABLE `reponse-possible`
  ADD PRIMARY KEY (`idReponse`),
  ADD KEY `id-sondage-rep-possible` (`id-sondage`),
  ADD KEY `id-question-rep-possible` (`id-question`);

--
-- Index pour la table `reponses`
--
ALTER TABLE `reponses`
  ADD KEY `id-sondage-rep` (`id-sondage`),
  ADD KEY `id-question-rep` (`id-question`),
  ADD KEY `id-reponse-rep` (`idReponse`),
  ADD KEY `votant-rep` (`votant`);

--
-- Index pour la table `resultats`
--
ALTER TABLE `resultats`
  ADD KEY `id-resultat` (`id`),
  ADD KEY `pseudo-resultat` (`votant`);

--
-- Index pour la table `sondages`
--
ALTER TABLE `sondages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sondage-pseudo` (`createur`),
  ADD KEY `sondage-email` (`emailCrea`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `question`
--
ALTER TABLE `question`
  MODIFY `idQuestion` int(255) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT pour la table `reponse-possible`
--
ALTER TABLE `reponse-possible`
  MODIFY `idReponse` int(255) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT pour la table `sondages`
--
ALTER TABLE `sondages`
  MODIFY `id` int(255) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `electeurs`
--
ALTER TABLE `electeurs`
  ADD CONSTRAINT `electeur-id` FOREIGN KEY (`id`) REFERENCES `sondages` (`id`),
  ADD CONSTRAINT `electeur-pseudo` FOREIGN KEY (`pseudo`) REFERENCES `connexion` (`pseudo`);

--
-- Contraintes pour la table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `id-sondage-quest` FOREIGN KEY (`id-sondage`) REFERENCES `sondages` (`id`);

--
-- Contraintes pour la table `reponse-possible`
--
ALTER TABLE `reponse-possible`
  ADD CONSTRAINT `id-question-rep-possible` FOREIGN KEY (`id-question`) REFERENCES `question` (`idQuestion`),
  ADD CONSTRAINT `id-sondage-rep-possible` FOREIGN KEY (`id-sondage`) REFERENCES `sondages` (`id`);

--
-- Contraintes pour la table `reponses`
--
ALTER TABLE `reponses`
  ADD CONSTRAINT `id-question-rep` FOREIGN KEY (`id-question`) REFERENCES `question` (`idQuestion`),
  ADD CONSTRAINT `id-reponse-rep` FOREIGN KEY (`idReponse`) REFERENCES `reponse-possible` (`idReponse`),
  ADD CONSTRAINT `id-sondage-rep` FOREIGN KEY (`id-sondage`) REFERENCES `sondages` (`id`),
  ADD CONSTRAINT `votant-rep` FOREIGN KEY (`votant`) REFERENCES `resultats` (`votant`);

--
-- Contraintes pour la table `resultats`
--
ALTER TABLE `resultats`
  ADD CONSTRAINT `id-resultat` FOREIGN KEY (`id`) REFERENCES `sondages` (`id`),
  ADD CONSTRAINT `pseudo-resultat` FOREIGN KEY (`votant`) REFERENCES `connexion` (`pseudo`);

--
-- Contraintes pour la table `sondages`
--
ALTER TABLE `sondages`
  ADD CONSTRAINT `sondage-email` FOREIGN KEY (`emailCrea`) REFERENCES `connexion` (`email`),
  ADD CONSTRAINT `sondage-pseudo` FOREIGN KEY (`createur`) REFERENCES `connexion` (`pseudo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
