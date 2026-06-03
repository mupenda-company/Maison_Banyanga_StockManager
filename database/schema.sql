-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 01, 2026 at 08:22 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bralima_logistique`
--

-- --------------------------------------------------------

--
-- Table structure for table `alertes`
--

CREATE TABLE `alertes` (
  `id` int UNSIGNED NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `produit_id` int UNSIGNED DEFAULT NULL,
  `emplacement_id` int UNSIGNED DEFAULT NULL,
  `niveau` enum('info','warning','danger') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `approvisionnements`
--

CREATE TABLE `approvisionnements` (
  `id` int UNSIGNED NOT NULL,
  `numero_bon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_approvisionnement` date NOT NULL,
  `fournisseur` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `total_ht` decimal(15,2) DEFAULT '0.00',
  `statut` enum('en_attente','valide','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `approvisionnement_details`
--

CREATE TABLE `approvisionnement_details` (
  `id` int UNSIGNED NOT NULL,
  `approvisionnement_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite_caisses` int NOT NULL,
  `quantite_bouteilles` int NOT NULL,
  `prix_caisse` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prix_unitaire` decimal(12,2) NOT NULL,
  `type_achat` enum('deposer','enlever') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'deposer',
  `sous_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `billetages`
--

CREATE TABLE `billetages` (
  `id` int UNSIGNED NOT NULL,
  `reference_type` enum('vente','mission') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` int UNSIGNED NOT NULL,
  `devise` enum('CDF','USD') COLLATE utf8mb4_unicode_ci NOT NULL,
  `coupure` decimal(12,2) NOT NULL,
  `quantite` int NOT NULL DEFAULT '0',
  `montant_base` decimal(15,2) NOT NULL DEFAULT '0.00',
  `taux_change` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_client` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `zone_id` int UNSIGNED DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taux_ristourne` decimal(5,2) NOT NULL DEFAULT '5.00' COMMENT 'Taux de ristourne client',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `depenses`
--

CREATE TABLE `depenses` (
  `id` int UNSIGNED NOT NULL,
  `categorie` enum('Transport','Carburant','Maintenance','Restauration','Autres') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `devise` enum('CDF','USD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CDF',
  `montant_original` decimal(12,2) NOT NULL DEFAULT '0.00',
  `taux_change` decimal(12,2) NOT NULL DEFAULT '0.00',
  `date_depense` date NOT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `dettes_emballages`
--

CREATE TABLE `dettes_emballages` (
  `id` int UNSIGNED NOT NULL,
  `approvisionnement_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite_dette_caisses` int NOT NULL DEFAULT '0',
  `quantite_dette_bouteilles` int NOT NULL DEFAULT '0',
  `quantite_remboursee` int NOT NULL DEFAULT '0',
  `quantite_remboursee_bouteilles` int NOT NULL DEFAULT '0',
  `statut` enum('en_cours','solde') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_cours',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `emplacements`
--

CREATE TABLE `emplacements` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('fixe','mobile') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixe',
  `capacite` int DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emprunts_emballages`
--

CREATE TABLE `emprunts_emballages` (
  `id` int UNSIGNED NOT NULL,
  `source_type` enum('client','externe') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client',
  `client_id` int UNSIGNED DEFAULT NULL,
  `source_nom` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_contact` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite_empruntee` int NOT NULL DEFAULT '0',
  `quantite_utilisee` int NOT NULL DEFAULT '0',
  `quantite_retournee` int NOT NULL DEFAULT '0',
  `emplacement_id` int UNSIGNED NOT NULL,
  `date_emprunt` date NOT NULL,
  `statut` enum('en_cours','solde','annule') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_cours',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `missions`
--

CREATE TABLE `missions` (
  `id` int UNSIGNED NOT NULL,
  `numero_mission` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_mission` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vente',
  `vehicule_id` int UNSIGNED NOT NULL,
  `chauffeur_id` int UNSIGNED DEFAULT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `ristourne_id` int UNSIGNED DEFAULT NULL,
  `date_depart` datetime NOT NULL,
  `date_retour` datetime DEFAULT NULL,
  `zone_id` int UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `montant_ristourne_initial` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_livre` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_restant_admin` decimal(15,2) NOT NULL DEFAULT '0.00',
  `justification_cloture` text COLLATE utf8mb4_unicode_ci,
  `montant_encaisse` decimal(15,2) DEFAULT '0.00',
  `caisses_vides_retournees` int NOT NULL DEFAULT '0',
  `statut` enum('en_cours','terminee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_cours',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `mission_chargements`
--

CREATE TABLE `mission_chargements` (
  `id` int UNSIGNED NOT NULL,
  `mission_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite_caisses` int NOT NULL DEFAULT '0' COMMENT 'Quantité en caisses',
  `prix_caisse` decimal(12,2) NOT NULL DEFAULT '0.00',
  `caisses_deja_dans_vehicule` int NOT NULL DEFAULT '0' COMMENT 'Caisses déjà présentes dans le véhicule au départ',
  `quantite_chargee` int NOT NULL COMMENT 'Quantité en bouteilles',
  `quantite_retournee` int DEFAULT '0' COMMENT 'Quantité retournée à la fin de mission',
  `quantite_vendue` int DEFAULT '0' COMMENT 'Quantité vendue pendant la mission'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `mission_ristournes`
--

CREATE TABLE `mission_ristournes` (
  `id` int UNSIGNED NOT NULL,
  `mission_id` int UNSIGNED NOT NULL,
  `ristourne_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `montant_ristourne` decimal(15,2) NOT NULL DEFAULT '0.00',
  `caisses_prevues` int NOT NULL DEFAULT '0',
  `bouteilles_prevues` int NOT NULL DEFAULT '0',
  `caisses_livrees` int NOT NULL DEFAULT '0',
  `bouteilles_livrees` int NOT NULL DEFAULT '0',
  `caisses_vides_recues` int NOT NULL DEFAULT '0',
  `montant_livre` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_restant_admin` decimal(15,2) NOT NULL DEFAULT '0.00',
  `proposition_montant` decimal(15,2) NOT NULL DEFAULT '0.00',
  `complement_confirme` tinyint(1) NOT NULL DEFAULT '0',
  `client_id` int UNSIGNED NOT NULL,
  `statut` enum('en_attente','livree','non_livree') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `emplacement_id` int UNSIGNED NOT NULL,
  `type_mouvement` enum('entree','sortie','transfert','inventaire','perte') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL COMMENT 'Positif pour entrée, négatif pour sortie',
  `quantite_avant` int NOT NULL,
  `quantite_apres` int NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type de document (approvisionnement, vente, mission, etc.)',
  `reference_id` int UNSIGNED DEFAULT NULL COMMENT 'ID du document de référence',
  `motif` text COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `objectifs_produits`
--

CREATE TABLE `objectifs_produits` (
  `id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `annee` year NOT NULL,
  `mois` tinyint UNSIGNED NOT NULL,
  `objectif_caisses` int NOT NULL DEFAULT '0' COMMENT 'Objectif mensuel en caisses',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paliers_ristourne`
--

CREATE TABLE `paliers_ristourne` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ca_min` decimal(15,2) NOT NULL COMMENT 'CA minimum pour ce palier',
  `ca_max` decimal(15,2) DEFAULT NULL COMMENT 'CA maximum (NULL = sans limite)',
  `taux_ristourne` decimal(5,2) NOT NULL COMMENT 'Pourcentage de ristourne',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parametres`
--

CREATE TABLE `parametres` (
  `id` int UNSIGNED NOT NULL,
  `cle` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `pertes`
--

CREATE TABLE `pertes` (
  `id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `emplacement_id` int UNSIGNED NOT NULL,
  `type_stock` enum('plein','vide') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'plein',
  `quantite` decimal(12,4) NOT NULL,
  `type_perte` enum('casse','dommage','expiration','vol','autre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `motif` text COLLATE utf8mb4_unicode_ci,
  `date_perte` date NOT NULL,
  `valeur_perte` decimal(12,2) DEFAULT '0.00',
  `agent_id` int UNSIGNED DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `produits`
--

CREATE TABLE `produits` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unite_base` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'caisse',
  `bouteilles_par_caisses` int NOT NULL DEFAULT '24',
  `prix_achat_unitaire` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prix_achat_deposer` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prix_achat_enlever` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prix_vente_unitaire` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prix_vente_caisses` decimal(12,2) NOT NULL DEFAULT '0.00',
  `seuil_alerte` int NOT NULL DEFAULT '10',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `caisses_par_palette` int NOT NULL DEFAULT '0' COMMENT 'Nombre de caisses par palette'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `retours_emballages`
--

CREATE TABLE `retours_emballages` (
  `id` int NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite` int NOT NULL,
  `date_retour` datetime DEFAULT CURRENT_TIMESTAMP,
  `emplacement_id` int UNSIGNED NOT NULL,
  `created_by` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `ristournes`
--

CREATE TABLE `ristournes` (
  `id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `ca_total` decimal(15,2) NOT NULL,
  `palier_id` int UNSIGNED DEFAULT NULL,
  `taux_applique` decimal(5,2) NOT NULL,
  `montant_ristourne` decimal(15,2) NOT NULL,
  `statut` enum('calculee','en_livraison','payee','annulee') COLLATE utf8mb4_unicode_ci DEFAULT 'calculee',
  `date_paiement` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `ristourne_paliers`
--

CREATE TABLE `ristourne_paliers` (
  `id` int NOT NULL,
  `quantite_min` decimal(15,2) NOT NULL,
  `quantite_max` decimal(15,2) DEFAULT NULL,
  `montant_par_caisse` decimal(15,2) NOT NULL,
  `type_produit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'tous',
  `actif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `emplacement_id` int UNSIGNED NOT NULL,
  `quantite_pleine` int NOT NULL DEFAULT '0' COMMENT 'Bouteilles pleines',
  `quantite_vide` int NOT NULL DEFAULT '0' COMMENT 'Bouteilles vides',
  `caisses_pleine` int NOT NULL DEFAULT '0' COMMENT 'Caisses pleines',
  `caisses_vide` int NOT NULL DEFAULT '0' COMMENT 'Caisses vides',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','magasinier','vendeur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vendeur',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `derniere_connexion` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `vehicules`
--

CREATE TABLE `vehicules` (
  `id` int UNSIGNED NOT NULL,
  `immatriculation` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marque` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modele` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_responsable_id` int UNSIGNED DEFAULT NULL,
  `emplacement_id` int UNSIGNED DEFAULT NULL,
  `capacite` int DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `ventes`
--

CREATE TABLE `ventes` (
  `id` int UNSIGNED NOT NULL,
  `numero_facture` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `date_vente` datetime NOT NULL,
  `mission_id` int UNSIGNED DEFAULT NULL COMMENT 'Si vente depuis un véhicule',
  `emplacement_id` int UNSIGNED NOT NULL COMMENT 'Point de vente',
  `total_ht` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_tva` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_ttc` decimal(15,2) NOT NULL DEFAULT '0.00',
  `statut` enum('en_attente','validee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'validee',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `vente_details`
--

CREATE TABLE `vente_details` (
  `id` int UNSIGNED NOT NULL,
  `vente_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite_caisses` int NOT NULL DEFAULT '0' COMMENT 'Quantité en caisses',
  `prix_caisse` decimal(12,2) NOT NULL DEFAULT '0.00',
  `caisses_vides_recues` int NOT NULL DEFAULT '0' COMMENT 'Caisses vides reçues',
  `quantite` int NOT NULL COMMENT 'Quantité en bouteilles',
  `prix_unitaire` decimal(12,2) NOT NULL,
  `sous_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `vente_emballages_recus`
--

CREATE TABLE `vente_emballages_recus` (
  `id` int UNSIGNED NOT NULL,
  `vente_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `caisses_recues` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `zones`
--

CREATE TABLE `zones` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Indexes for table `alertes`
--
ALTER TABLE `alertes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `emplacement_id` (`emplacement_id`);

--
-- Indexes for table `approvisionnements`
--
ALTER TABLE `approvisionnements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_bon` (`numero_bon`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `approvisionnement_details`
--
ALTER TABLE `approvisionnement_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approvisionnement_id` (`approvisionnement_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Indexes for table `billetages`
--
ALTER TABLE `billetages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reference_idx` (`reference_type`, `reference_id`),
  ADD KEY `created_by` (`created_by`);
--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_clients_numero_client` (`numero_client`),
  ADD KEY `zone_id` (`zone_id`);

--
-- Indexes for table `depenses`
--
ALTER TABLE `depenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `dettes_emballages`
--
ALTER TABLE `dettes_emballages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approvisionnement_id` (`approvisionnement_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Indexes for table `emplacements`
--
ALTER TABLE `emplacements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `emprunts_emballages`
--
ALTER TABLE `emprunts_emballages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emprunts_client_produit` (`client_id`,`produit_id`,`statut`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `emplacement_id` (`emplacement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_mission` (`numero_mission`),
  ADD KEY `vehicule_id` (`vehicule_id`),
  ADD KEY `chauffeur_id` (`chauffeur_id`),
  ADD KEY `zone_id` (`zone_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `mission_chargements`
--
ALTER TABLE `mission_chargements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_id` (`mission_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Indexes for table `mission_ristournes`
--
ALTER TABLE `mission_ristournes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_id` (`mission_id`),
  ADD KEY `ristourne_id` (`ristourne_id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `emplacement_id` (`emplacement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `objectifs_produits`
--
ALTER TABLE `objectifs_produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_objectifs_produit_mois` (`produit_id`,`annee`,`mois`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `paliers_ristourne`
--
ALTER TABLE `paliers_ristourne`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `pertes`
--
ALTER TABLE `pertes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `emplacement_id` (`emplacement_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `retours_emballages`
--
ALTER TABLE `retours_emballages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `emplacement_id` (`emplacement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `ristournes`
--
ALTER TABLE `ristournes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `palier_id` (`palier_id`);

--
-- Indexes for table `ristourne_paliers`
--
ALTER TABLE `ristourne_paliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_unique` (`produit_id`,`emplacement_id`),
  ADD KEY `emplacement_id` (`emplacement_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `telephone` (`telephone`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `vehicules`
--
ALTER TABLE `vehicules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `immatriculation` (`immatriculation`),
  ADD KEY `agent_responsable_id` (`agent_responsable_id`),
  ADD KEY `emplacement_id` (`emplacement_id`);

--
-- Indexes for table `ventes`
--
ALTER TABLE `ventes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_facture` (`numero_facture`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `mission_id` (`mission_id`),
  ADD KEY `emplacement_id` (`emplacement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `vente_details`
--
ALTER TABLE `vente_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vente_id` (`vente_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Indexes for table `vente_emballages_recus`
--
ALTER TABLE `vente_emballages_recus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vente_id` (`vente_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Indexes for table `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alertes`
--
ALTER TABLE `alertes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `approvisionnements`
--
ALTER TABLE `approvisionnements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `approvisionnement_details`
--
ALTER TABLE `approvisionnement_details`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `billetages`
--
ALTER TABLE `billetages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=183;

--
-- AUTO_INCREMENT for table `depenses`
--
ALTER TABLE `depenses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `dettes_emballages`
--
ALTER TABLE `dettes_emballages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `emplacements`
--
ALTER TABLE `emplacements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `emprunts_emballages`
--
ALTER TABLE `emprunts_emballages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `missions`
--
ALTER TABLE `missions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `mission_chargements`
--
ALTER TABLE `mission_chargements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `mission_ristournes`
--
ALTER TABLE `mission_ristournes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=519;

--
-- AUTO_INCREMENT for table `objectifs_produits`
--
ALTER TABLE `objectifs_produits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paliers_ristourne`
--
ALTER TABLE `paliers_ristourne`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `pertes`
--
ALTER TABLE `pertes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `retours_emballages`
--
ALTER TABLE `retours_emballages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ristournes`
--
ALTER TABLE `ristournes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `ristourne_paliers`
--
ALTER TABLE `ristourne_paliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `vehicules`
--
ALTER TABLE `vehicules`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `vente_details`
--
ALTER TABLE `vente_details`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `vente_emballages_recus`
--
ALTER TABLE `vente_emballages_recus`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zones`
--
ALTER TABLE `zones`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alertes`
--
ALTER TABLE `alertes`
  ADD CONSTRAINT `alertes_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alertes_ibfk_2` FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `approvisionnements`
--
ALTER TABLE `approvisionnements`
  ADD CONSTRAINT `approvisionnements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `approvisionnement_details`
--
ALTER TABLE `approvisionnement_details`
  ADD CONSTRAINT `approvisionnement_details_ibfk_1` FOREIGN KEY (`approvisionnement_id`) REFERENCES `approvisionnements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `approvisionnement_details_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `billetages`
--
ALTER TABLE `billetages`
  ADD CONSTRAINT `billetages_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `depenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dettes_emballages`
--
ALTER TABLE `dettes_emballages`
  ADD CONSTRAINT `dettes_emballages_ibfk_1` FOREIGN KEY (`approvisionnement_id`) REFERENCES `approvisionnements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dettes_emballages_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emprunts_emballages`
--
ALTER TABLE `emprunts_emballages`
  ADD CONSTRAINT `emprunts_emballages_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `emprunts_emballages_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprunts_emballages_ibfk_3` FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprunts_emballages_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `missions`
--
ALTER TABLE `missions`
  ADD CONSTRAINT `missions_ibfk_1` FOREIGN KEY (`vehicule_id`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `missions_ibfk_2` FOREIGN KEY (`chauffeur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `missions_ibfk_3` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `missions_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mission_chargements`
--
ALTER TABLE `mission_chargements`
  ADD CONSTRAINT `mission_chargements_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mission_chargements_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mission_ristournes`
--
ALTER TABLE `mission_ristournes`
  ADD CONSTRAINT `mission_ristournes_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mission_ristournes_ibfk_2` FOREIGN KEY (`ristourne_id`) REFERENCES `ristournes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mission_ristournes_ibfk_3` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mission_ristournes_ibfk_4` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mouvements_stock_ibfk_2` FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mouvements_stock_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `objectifs_produits`
--
ALTER TABLE `objectifs_produits`
  ADD CONSTRAINT `objectifs_produits_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `objectifs_produits_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pertes`
--
ALTER TABLE `pertes`
  ADD CONSTRAINT `pertes_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pertes_ibfk_2` FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pertes_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pertes_ibfk_4` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `retours_emballages`
--
ALTER TABLE `retours_emballages`
  ADD CONSTRAINT `retours_emballages_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `retours_emballages_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `retours_emballages_ibfk_3` FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `retours_emballages_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ristournes`
--
ALTER TABLE `ristournes`
  ADD CONSTRAINT `ristournes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ristournes_ibfk_2` FOREIGN KEY (`palier_id`) REFERENCES `paliers_ristourne` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stocks_ibfk_2` FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicules`
--
ALTER TABLE `vehicules`
  ADD CONSTRAINT `vehicules_ibfk_1` FOREIGN KEY (`agent_responsable_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vehicules_ibfk_2` FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ventes`
--
ALTER TABLE `ventes`
  ADD CONSTRAINT `ventes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ventes_ibfk_2` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ventes_ibfk_3` FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ventes_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vente_details`
--
ALTER TABLE `vente_details`
  ADD CONSTRAINT `vente_details_ibfk_1` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vente_details_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vente_emballages_recus`
--
ALTER TABLE `vente_emballages_recus`
  ADD CONSTRAINT `vente_emballages_recus_ibfk_1` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vente_emballages_recus_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Données initiales
-- --------------------------------------------------------

-- Utilisateur admin par défaut
INSERT INTO `users` (`username`, `telephone`, `password`, `nom`, `prenom`, `role`) VALUES
('admin', '0927161930', '$2y$10$P6GArcijgFX6rQVQQTxxg.TusYWUJObGMjjfuMtJOB1B.dHskS2JC', 'Administrateur', 'Système', 'admin');

-- Paramètres par défaut
INSERT INTO `parametres` (`cle`, `valeur`, `type`) VALUES
('nom_entreprise', 'Bralima', 'text'),
('logo', '', 'text'),
('couleur_primaire', '#3B82F6', 'text'),
('adresse', 'Goma, RD Congo', 'text'),
('telephone', '', 'text'),
('email_contact', '', 'text'),
('contact', '+243998681273', 'text'),
('rccm', 'CD/GOM/RCCM/15-B-0278', 'text'),
('id_nat', '5-93-N466812 Z', 'text'),
('nif', '1504690Q', 'text'),
('numero_compte', '100250288942 BK', 'text'),
('devise', 'CDF', 'text'),
('devise_base', 'CDF', 'text'),
('taux_change','2400','text'),
('taux_tva', '0', 'number');

-- Emplacement principal par défaut (Entrepôt)
INSERT INTO `emplacements` (`code`, `nom`, `type`, `capacite`) VALUES
('ENT-001', 'Entrepôt Principal', 'fixe', 1000000);

-- Produit par defauf pour m'evite de tous les ecrire hahha
INSERT INTO `produits` (`id`, `code`, `nom`, `description`, `categorie`, `unite_base`, `bouteilles_par_caisses`, `prix_achat_unitaire`, `prix_achat_deposer`, `prix_achat_enlever`, `prix_vente_unitaire`, `prix_vente_caisses`, `seuil_alerte`, `actif`, `caisses_par_palette`) VALUES
(1, 'PRD-0001', 'PRIMUS 72CL', '', 'Alcolisé', 'caisse', 12, 3023.67, 37050.00, 36284.00, 3250.00, 39000.00, 10, 1, 102),
(2, 'PRD-0002', 'PRIMUS 50CL', '', 'Alcolisé', 'caisse', 20, 2336.70, 47500.00, 46734.00, 2500.00, 50000.00, 10, 1, 102),
(3, 'PRD-0003', 'TURBO KING 72CL', '', 'Alcolisé', 'caisse', 12, 3182.00, 38950.00, 38184.00, 3416.67, 41000.00, 15, 1, 102),
(4, 'PRD-0004', 'TURBO KING 50CL', '', 'Alcolisé', 'caisse', 20, 2431.70, 49400.00, 48634.00, 2600.00, 52000.00, 10, 1, 102),
(5, 'PRD-0005', 'SUPER BOOCKB 65CL', '', 'Alcolisé', 'caisse', 12, 3182.00, 38950.00, 38184.00, 3416.67, 41000.00, 10, 1, 85),
(6, 'PRD-0006', 'MUTZIG BL 33CL', '', 'Alcolisé', 'caisse', 24, 1868.08, 45600.00, 44834.00, 2000.00, 48000.00, 10, 1, 84),
(7, 'PRD-0007', 'MUTZIG BL 65CL', '', 'Alcolisé', 'caisse', 12, 3182.00, 38950.00, 38184.00, 3416.67, 41000.00, 10, 1, 85),
(8, 'PRD-0008', 'CLASS 50CL', '', 'Alcolisé', 'caisse', 20, 2336.70, 47500.00, 46734.00, 2500.00, 50000.00, 10, 1, 102),
(9, 'PRD-0009', 'CLASS 33CL', '', 'Alcolisé', 'caisse', 24, 1947.25, 47500.00, 46734.00, 2083.33, 50000.00, 10, 1, 84),
(10, 'PRD-0010', 'ENERGY MALT 33CL', '', 'Alcolisé', 'caisse', 24, 1551.42, 38000.00, 37234.00, 1666.67, 40000.00, 10, 1, 84),
(11, 'PRD-0011', 'LEGEND 33CL', '', 'Alcolisé', 'caisse', 24, 2026.42, 49400.00, 48634.00, 2166.67, 52000.00, 10, 1, 84);



-- Rôles système
INSERT INTO `roles` (`nom`, `description`, `is_system`) VALUES
('admin', 'Administrateur - accès complet', 1),
('magasinier', 'Magasinier - gestion stock et approvisionnement', 1),
('vendeur', 'Vendeur - création de ventes uniquement', 1);

-- Permissions par module/action
INSERT INTO `permissions` (`code`, `module`, `action`, `description`) VALUES
('dashboard.voir', 'dashboard', 'voir', 'Voir le tableau de bord'),
('ventes.voir', 'ventes', 'voir', 'Voir les ventes'),
('ventes.creer', 'ventes', 'creer', 'Créer une vente'),
('ventes.modifier', 'ventes', 'modifier', 'Modifier une vente'),
('ventes.supprimer', 'ventes', 'supprimer', 'Annuler une vente'),
('clients.voir', 'clients', 'voir', 'Voir les clients'),
('clients.creer', 'clients', 'creer', 'Créer un client'),
('clients.modifier', 'clients', 'modifier', 'Modifier un client'),
('clients.supprimer', 'clients', 'supprimer', 'Supprimer un client'),
('produits.voir', 'produits', 'voir', 'Voir les produits'),
('produits.creer', 'produits', 'creer', 'Créer un produit'),
('produits.modifier', 'produits', 'modifier', 'Modifier un produit'),
('produits.supprimer', 'produits', 'supprimer', 'Supprimer un produit'),
('stock.voir', 'stock', 'voir', 'Voir le stock'),
('stock.gerer', 'stock', 'gerer', 'Gérer le stock (transferts, inventaire)'),
('approvisionnements.voir', 'approvisionnements', 'voir', 'Voir les approvisionnements'),
('approvisionnements.creer', 'approvisionnements', 'creer', 'Créer un approvisionnement'),
('approvisionnements.modifier', 'approvisionnements', 'modifier', 'Modifier un approvisionnement'),
('approvisionnements.supprimer', 'approvisionnements', 'supprimer', 'Annuler un approvisionnement'),
('missions.voir', 'missions', 'voir', 'Voir les missions'),
('missions.creer', 'missions', 'creer', 'Créer une mission'),
('missions.modifier', 'missions', 'modifier', 'Modifier une mission'),
('missions.supprimer', 'missions', 'supprimer', 'Annuler une mission'),
('missions.gerer', 'missions', 'gerer', 'Créer, modifier, terminer des missions'),
('vehicules.voir', 'vehicules', 'voir', 'Voir les véhicules'),
('vehicules.gerer', 'vehicules', 'gerer', 'Gérer les véhicules'),
('depenses.voir', 'depenses', 'voir', 'Voir les dépenses'),
('depenses.creer', 'depenses', 'creer', 'Créer une dépense'),
('depenses.supprimer', 'depenses', 'supprimer', 'Supprimer une dépense'),
('emballages.voir', 'emballages', 'voir', 'Voir les emballages'),
('emballages.gerer', 'emballages', 'gerer', 'Gérer les emballages'),
('pertes.voir', 'pertes', 'voir', 'Voir les pertes'),
('pertes.creer', 'pertes', 'creer', 'Déclarer une perte'),
('rapports.voir', 'rapports', 'voir', 'Voir les rapports'),
('finance.voir', 'finance', 'voir', 'Voir la finance'),
('finance.creer', 'finance', 'creer', 'Créer une opération financière'),
('finance.modifier', 'finance', 'modifier', 'Modifier une opération financière'),
('finance.supprimer', 'finance', 'supprimer', 'Supprimer une opération financière'),
('admin.voir', 'admin', 'voir', 'Accès administration'),
('admin.utilisateurs', 'admin', 'utilisateurs', 'Gérer les utilisateurs'),
('admin.parametres', 'admin', 'parametres', 'Gérer les paramètres'),
('admin.roles', 'admin', 'roles', 'Gérer les rôles et permissions');

-- Admin = toutes les permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p WHERE r.nom = 'admin';

-- Magasinier
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nom = 'magasinier' AND p.code IN (
    'dashboard.voir',
    'ventes.voir', 'ventes.creer', 'ventes.modifier', 'ventes.supprimer',
    'clients.voir', 'clients.creer', 'clients.modifier',
    'produits.voir',
    'stock.voir', 'stock.gerer',
    'approvisionnements.voir', 'approvisionnements.creer', 'approvisionnements.modifier', 'approvisionnements.supprimer',
    'missions.voir', 'missions.creer', 'missions.modifier', 'missions.supprimer', 'missions.gerer',
    'pertes.voir', 'pertes.creer',
    'vehicules.voir', 'vehicules.gerer',
    'emballages.voir', 'emballages.gerer',
    'rapports.voir'
);

-- Vendeur
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nom = 'vendeur' AND p.code IN (
    'ventes.voir', 'ventes.creer', 'ventes.modifier',
    'clients.voir', 'clients.creer', 'clients.modifier',
    'produits.voir'
);

-- Assigner les rôles aux utilisateurs existants selon leur champ `role`
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id FROM `users` u, `roles` r WHERE u.role = r.nom;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


