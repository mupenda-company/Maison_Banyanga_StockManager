-- Base de données pour l'application Bralima Logistique
-- Version: 1.0.0

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Structure de la table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `telephone` VARCHAR(20) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'magasinier', 'vendeur') NOT NULL DEFAULT 'vendeur',
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `derniere_connexion` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `parametres`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parametres` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cle` VARCHAR(50) NOT NULL UNIQUE,
    `valeur` TEXT,
    `type` VARCHAR(20) DEFAULT 'text',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `zones`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `zones` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `clients`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(150) NOT NULL,
    `telephone` VARCHAR(20),
    `numero_client` VARCHAR(50),
    `adresse` TEXT,
    `zone_id` INT UNSIGNED,
    `email` VARCHAR(100),
    `taux_ristourne` DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Taux de ristourne client',
    `notes` TEXT,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_clients_numero_client` (`numero_client`),
    FOREIGN KEY (`zone_id`) REFERENCES `zones`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `produits`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `produits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `nom` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `categorie` VARCHAR(50),
    `unite_base` VARCHAR(20) NOT NULL DEFAULT 'caisse',
    `bouteilles_par_caisses` INT NOT NULL DEFAULT 24,
    `prix_achat_unitaire` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `prix_vente_unitaire` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `prix_vente_caisses` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `seuil_alerte` INT NOT NULL DEFAULT 10,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `objectifs_produits`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `objectifs_produits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `produit_id` INT UNSIGNED NOT NULL,
    `annee` YEAR NOT NULL,
    `mois` TINYINT UNSIGNED NOT NULL,
    `objectif_caisses` INT NOT NULL DEFAULT 0 COMMENT 'Objectif mensuel en caisses',
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_objectifs_produit_mois` (`produit_id`, `annee`, `mois`),
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `emplacements`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `emplacements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `nom` VARCHAR(100) NOT NULL,
    `type` ENUM('fixe', 'mobile') NOT NULL DEFAULT 'fixe',
    `capacite` INT DEFAULT 0,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `vehicules`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `immatriculation` VARCHAR(20) NOT NULL UNIQUE,
    `marque` VARCHAR(50),
    `modele` VARCHAR(50),
    `agent_responsable_id` INT UNSIGNED,
    `emplacement_id` INT UNSIGNED,
    `capacite` INT DEFAULT 0,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`agent_responsable_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `stocks`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stocks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `produit_id` INT UNSIGNED NOT NULL,
    `emplacement_id` INT UNSIGNED NOT NULL,
    `quantite_pleine` INT NOT NULL DEFAULT 0 COMMENT 'Bouteilles pleines',
    `quantite_vide` INT NOT NULL DEFAULT 0 COMMENT 'Bouteilles vides',
    `caisses_pleine` INT NOT NULL DEFAULT 0 COMMENT 'Caisses pleines',
    `caisses_vide` INT NOT NULL DEFAULT 0 COMMENT 'Caisses vides',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `stock_unique` (`produit_id`, `emplacement_id`),
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `approvisionnements`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `approvisionnements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero_bon` VARCHAR(50) NOT NULL UNIQUE,
    `date_approvisionnement` DATE NOT NULL,
    `fournisseur` VARCHAR(150),
    `notes` TEXT,
    `total_ht` DECIMAL(15,2) DEFAULT 0,
    `statut` ENUM('en_attente', 'valide', 'annule') NOT NULL DEFAULT 'en_attente',
    `created_by` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `approvisionnement_details`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `approvisionnement_details` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `approvisionnement_id` INT UNSIGNED NOT NULL,
    `produit_id` INT UNSIGNED NOT NULL,
    `quantite_caisses` INT NOT NULL,
    `quantite_bouteilles` INT NOT NULL,
    `prix_unitaire` DECIMAL(12,2) NOT NULL,
    `sous_total` DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (`approvisionnement_id`) REFERENCES `approvisionnements`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `approvisionnement_details`
    ADD COLUMN `prix_caisse` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `quantite_bouteilles`;
-- --------------------------------------------------------
-- Structure de la table `dettes_emballages`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dettes_emballages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `approvisionnement_id` INT UNSIGNED NOT NULL,
    `produit_id` INT UNSIGNED NOT NULL,
    `quantite_dette_caisses` INT NOT NULL DEFAULT 0,
    `quantite_remboursee` INT NOT NULL DEFAULT 0,
    `statut` ENUM('en_cours', 'solde') NOT NULL DEFAULT 'en_cours',
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`approvisionnement_id`) REFERENCES `approvisionnements`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `missions`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `missions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero_mission` VARCHAR(50) NOT NULL UNIQUE,
    `vehicule_id` INT UNSIGNED NOT NULL,
    `chauffeur_id` INT UNSIGNED,
    `date_depart` DATETIME NOT NULL,
    `date_retour` DATETIME NULL,
    `zone_id` INT UNSIGNED,
    `notes` TEXT,
    `justification_cloture` TEXT,
    `montant_encaisse` DECIMAL(15,2) DEFAULT 0,
    `caisses_vides_retournees` INT NOT NULL DEFAULT 0,
    `statut` ENUM('en_cours', 'terminee', 'annulee') NOT NULL DEFAULT 'en_cours',
    `created_by` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`vehicule_id`) REFERENCES `vehicules`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`chauffeur_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`zone_id`) REFERENCES `zones`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `mission_chargements`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mission_chargements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `mission_id` INT UNSIGNED NOT NULL,
    `produit_id` INT UNSIGNED NOT NULL,
    `quantite_caisses` INT NOT NULL DEFAULT 0 COMMENT 'Quantité en caisses',
    `caisses_deja_dans_vehicule` INT NOT NULL DEFAULT 0 COMMENT 'Caisses déjà présentes dans le véhicule au départ',
    `quantite_chargee` INT NOT NULL COMMENT 'Quantité en bouteilles',
    `quantite_retournee` INT DEFAULT 0 COMMENT 'Quantité retournée à la fin de mission',
    `quantite_vendue` INT DEFAULT 0 COMMENT 'Quantité vendue pendant la mission',
    FOREIGN KEY (`mission_id`) REFERENCES `missions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `mission_chargements`
    ADD COLUMN `prix_caisse` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `quantite_caisses`;
-- --------------------------------------------------------
-- Structure de la table `ventes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ventes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero_facture` VARCHAR(50) NOT NULL UNIQUE,
    `client_id` INT UNSIGNED,
    `date_vente` DATETIME NOT NULL,
    `mission_id` INT UNSIGNED NULL COMMENT 'Si vente depuis un véhicule',
    `emplacement_id` INT UNSIGNED NOT NULL COMMENT 'Point de vente',
    `total_ht` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_tva` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_ttc` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `statut` ENUM('en_attente', 'validee', 'annulee') NOT NULL DEFAULT 'validee',
    `notes` TEXT,
    `created_by` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`mission_id`) REFERENCES `missions`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `vente_details`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vente_details` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vente_id` INT UNSIGNED NOT NULL,
    `produit_id` INT UNSIGNED NOT NULL,
    `quantite_caisses` INT NOT NULL DEFAULT 0 COMMENT 'Quantité en caisses',
    `caisses_vides_recues` INT NOT NULL DEFAULT 0 COMMENT 'Caisses vides reçues',
    `quantite` INT NOT NULL COMMENT 'Quantité en bouteilles',
    `prix_unitaire` DECIMAL(12,2) NOT NULL,
    `sous_total` DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (`vente_id`) REFERENCES `ventes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `vente_details`
    ADD COLUMN `prix_caisse` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `quantite_caisses`;
-- --------------------------------------------------------
-- Structure de la table `pertes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pertes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `produit_id` INT UNSIGNED NOT NULL,
    `emplacement_id` INT UNSIGNED NOT NULL,
    `quantite` INT NOT NULL,
    `type_perte` ENUM('casse', 'dommage', 'expiration', 'vol', 'autre') NOT NULL,
    `motif` TEXT,
    `date_perte` DATE NOT NULL,
    `valeur_perte` DECIMAL(12,2) DEFAULT 0,
    `created_by` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `paliers_ristourne`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `paliers_ristourne` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `ca_min` DECIMAL(15,2) NOT NULL COMMENT 'CA minimum pour ce palier',
    `ca_max` DECIMAL(15,2) NULL COMMENT 'CA maximum (NULL = sans limite)',
    `taux_ristourne` DECIMAL(5,2) NOT NULL COMMENT 'Pourcentage de ristourne',
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `ristournes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ristournes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT UNSIGNED NOT NULL,
    `periode_debut` DATE NOT NULL,
    `periode_fin` DATE NOT NULL,
    `ca_total` DECIMAL(15,2) NOT NULL,
    `palier_id` INT UNSIGNED,
    `taux_applique` DECIMAL(5,2) NOT NULL,
    `montant_ristourne` DECIMAL(15,2) NOT NULL,
    `statut` ENUM('calculee', 'payee', 'annulee') NOT NULL DEFAULT 'calculee',
    `date_paiement` DATE NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`palier_id`) REFERENCES `paliers_ristourne`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `mouvements_stock`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mouvements_stock` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `produit_id` INT UNSIGNED NOT NULL,
    `emplacement_id` INT UNSIGNED NOT NULL,
    `type_mouvement` ENUM('entree', 'sortie', 'transfert', 'inventaire', 'perte') NOT NULL,
    `quantite` INT NOT NULL COMMENT 'Positif pour entrée, négatif pour sortie',
    `quantite_avant` INT NOT NULL,
    `quantite_apres` INT NOT NULL,
    `reference_type` VARCHAR(50) COMMENT 'Type de document (approvisionnement, vente, mission, etc.)',
    `reference_id` INT UNSIGNED COMMENT 'ID du document de référence',
    `motif` TEXT,
    `created_by` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `alertes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alertes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL,
    `titre` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `produit_id` INT UNSIGNED NULL,
    `emplacement_id` INT UNSIGNED NULL,
    `niveau` ENUM('info', 'warning', 'danger') NOT NULL DEFAULT 'warning',
    `lu` TINYINT(1) NOT NULL DEFAULT 0,
    `resolved_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Structure de la table `retours_emballages`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `retours_emballages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT UNSIGNED NOT NULL,
    `produit_id` INT UNSIGNED NOT NULL,
    `quantite` INT NOT NULL,
    `date_retour` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `emplacement_id` INT UNSIGNED NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`emplacement_id`) REFERENCES `emplacements`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `dettes_emballages`
    ADD COLUMN `quantite_dette_bouteilles` INT NOT NULL DEFAULT 0 AFTER `quantite_dette_caisses`,
    ADD COLUMN `quantite_remboursee_bouteilles` INT NOT NULL DEFAULT 0 AFTER `quantite_remboursee`;

ALTER TABLE pertes ADD COLUMN type_stock ENUM('plein', 'vide') NOT NULL DEFAULT 'plein' AFTER emplacement_id;

-- --------------------------------------------------------
-- Ristournes
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ristourne_paliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quantite_min` DECIMAL(15,2) NOT NULL,
    `quantite_max` DECIMAL(15,2) DEFAULT NULL,
    `montant_par_caisse` DECIMAL(15,2) NOT NULL,
    `type_produit` VARCHAR(50) DEFAULT 'tous',
    `actif` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ristournes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `periode_mois` TINYINT NOT NULL,
    `periode_annee` INT NOT NULL,
    `quantite_totale` DECIMAL(15,2) NOT NULL, -- en caisses
    `montant_total` DECIMAL(15,2) NOT NULL,
    `statut` ENUM('calcule', 'paye', 'annule') DEFAULT 'calcule',
    `date_paiement` DATETIME DEFAULT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données par défaut pour les paliers
INSERT INTO `ristourne_paliers` (`quantite_min`, `quantite_max`, `montant_par_caisse`) VALUES
(0, 100, 0.10),
(101, 500, 0.25),
(501, NULL, 0.50);

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
('taux_tva', '0', 'number');

-- Emplacement principal par défaut (Entrepôt)
INSERT INTO `emplacements` (`code`, `nom`, `type`, `capacite`) VALUES
('ENT-001', 'Entrepôt Principal', 'fixe', 1000000);

-- Produit par defauf pour m'evite de tous les ecrire hahha
INSERT INTO `produits` (`id`, `code`, `nom`, `description`, `categorie`, `unite_base`, `bouteilles_par_caisses`, `prix_achat_unitaire`, `prix_vente_unitaire`, `prix_vente_caisses`, `seuil_alerte`, `actif`) VALUES
(1, 'PRD-0001', 'PRIMUS 72CL', '', 'Alcolisé', 'caisse', 12, 3023.67, 3250.00, 39000.00, 10, 1),
(2, 'PRD-0002', 'PRIMUS 50CL', '', 'Alcolisé', 'caisse', 20, 2336.70, 2500.00, 50000.00, 10, 1),
(3, 'PRD-0003', 'TURBO KING 72CL', '', 'Alcolisé', 'caisse', 12, 3182.00, 3416.67, 41000.00, 10, 1),
(4, 'PRD-0004', 'TURBO KING 50CL', '', 'Alcolisé', 'caisse', 20, 2431.70, 2600.00, 52000.00, 10, 1),
(5, 'PRD-0005', 'SUPER BOOCKB 65CL', '', 'Alcolisé', 'caisse', 12, 3166.67, 3416.67, 41000.00, 10, 1),
(6, 'PRD-0006', 'MUTZIG BL 33CL', '', 'Alcolisé', 'caisse', 24, 1868.08, 2000.00, 48000.00, 10, 1),
(7, 'PRD-0007', 'MUTZIG BL 72CL', '', 'Alcolisé', 'caisse', 12, 3250.00, 3416.67, 41000.00, 10, 1),
(8, 'PRD-0008', 'CLASS 50CL', '', 'Alcolisé', 'caisse', 20, 2336.70, 2500.00, 50000.00, 10, 1),
(9, 'PRD-0009', 'CLASS 33CL', '', 'Alcolisé', 'caisse', 24, 1947.25, 2083.33, 50000.00, 10, 1),
(10, 'PRD-0010', 'ENERGY MALT 33CL', '', 'Alcolisé', 'caisse', 24, 1551.42, 1666.67, 40000.00, 10, 1),
(11, 'PRD-0011', 'LEGEND 33CL', '', 'Alcolisé', 'caisse', 24, 2026.42, 2166.67, 52000.00, 10, 1);

-- Paliers de ristourne par défaut
INSERT INTO `paliers_ristourne` (`nom`, `ca_min`, `ca_max`, `taux_ristourne`) VALUES
('Bronze', 0, 500000, 2.00),
('Argent', 500000, 1000000, 3.00),
('Or', 1000000, 2500000, 5.00),
('Platine', 2500000, NULL, 7.00);

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Structure de la table `depenses`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `depenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `categorie` ENUM('Transport', 'Carburant', 'Maintenance', 'Restauration', 'Autres') NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `montant` DECIMAL(12,2) NOT NULL,
    `date_depense` DATE NOT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
