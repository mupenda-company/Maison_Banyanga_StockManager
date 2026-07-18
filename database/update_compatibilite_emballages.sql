-- Migration : familles d'emballages compatibles dans les approvisionnements.
-- Cette migration peut etre executee plusieurs fois sans erreur.
-- Elle conserve toutes les familles deja configurees manuellement.

SET @schema_name = DATABASE();

-- 1. Famille d'emballage portee par chaque produit.
SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'produits'
          AND COLUMN_NAME = 'famille_emballage'
    ),
    'SELECT ''famille_emballage existe deja''',
    'ALTER TABLE `produits` ADD COLUMN `famille_emballage` VARCHAR(80) NULL AFTER `caisses_par_palette`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Familles initiales connues. Class 50CL reste volontairement separe.
UPDATE `produits`
SET `famille_emballage` = '72CL_12'
WHERE (`famille_emballage` IS NULL OR `famille_emballage` = '')
  AND `bouteilles_par_caisses` = 12
  AND (UPPER(`nom`) LIKE '%PRIMUS%72%' OR UPPER(`nom`) LIKE '%TURBO%72%');

UPDATE `produits`
SET `famille_emballage` = '50CL_20'
WHERE (`famille_emballage` IS NULL OR `famille_emballage` = '')
  AND `bouteilles_par_caisses` = 20
  AND (UPPER(`nom`) LIKE '%PRIMUS%50%' OR UPPER(`nom`) LIKE '%TURBO%50%');

UPDATE `produits`
SET `famille_emballage` = 'CLASS_50CL_20'
WHERE (`famille_emballage` IS NULL OR `famille_emballage` = '')
  AND `bouteilles_par_caisses` = 20
  AND UPPER(`nom`) LIKE '%CLASS%50%';

-- Tous les autres produits restent incompatibles entre eux par defaut.
UPDATE `produits`
SET `famille_emballage` = CONCAT('PRODUIT_', `id`)
WHERE `famille_emballage` IS NULL OR `famille_emballage` = '';

-- 2. Produit dont le stock vide est reellement consomme par une ligne.
SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'approvisionnement_details'
          AND COLUMN_NAME = 'emballage_source_produit_id'
    ),
    'SELECT ''emballage_source_produit_id existe deja''',
    'ALTER TABLE `approvisionnement_details` ADD COLUMN `emballage_source_produit_id` INT UNSIGNED NULL AFTER `produit_id`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Historique : avant cette migration, un produit utilisait toujours ses propres vides.
UPDATE `approvisionnement_details`
SET `emballage_source_produit_id` = `produit_id`
WHERE `emballage_source_produit_id` IS NULL
  AND COALESCE(`type_chargement`, 'produit') IN ('produit', 'vente');

-- Index necessaire pour les recherches et la cle etrangere.
SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'approvisionnement_details'
          AND COLUMN_NAME = 'emballage_source_produit_id'
    ),
    'SELECT ''index emballage source existe deja''',
    'ALTER TABLE `approvisionnement_details` ADD INDEX `idx_appro_detail_emballage_source` (`emballage_source_produit_id`)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- La suppression d'un produit source conserve l'historique en mettant la source a NULL.
SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'approvisionnement_details'
          AND COLUMN_NAME = 'emballage_source_produit_id'
          AND REFERENCED_TABLE_NAME = 'produits'
    ),
    'SELECT ''cle etrangere emballage source existe deja''',
    'ALTER TABLE `approvisionnement_details`
       ADD CONSTRAINT `fk_appro_detail_emballage_source`
       FOREIGN KEY (`emballage_source_produit_id`) REFERENCES `produits` (`id`)
       ON DELETE SET NULL ON UPDATE CASCADE'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migration compatibilite emballages terminee' AS resultat;
