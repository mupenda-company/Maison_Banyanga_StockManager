ALTER TABLE `objectifs_produits`
  ADD COLUMN `type_objectif` enum('vente','approvisionnement') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vente' AFTER `mois`;

ALTER TABLE `objectifs_produits`
  DROP INDEX `uk_objectifs_produit_mois`,
  ADD UNIQUE KEY `uk_objectifs_produit_mois_type` (`produit_id`,`annee`,`mois`,`type_objectif`);
