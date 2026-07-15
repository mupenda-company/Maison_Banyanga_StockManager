-- Migration : prise en charge de l'approvisionnement des emballages.
-- Cette migration peut être exécutée plusieurs fois sans erreur.
-- Les montants convertis restent dans prix_caisse/prix_unitaire/sous_total.
-- Ces colonnes conservent le prix saisi, sa devise et le taux appliqué.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'approvisionnement_details'
          AND COLUMN_NAME = 'prix_original'
    ),
    'SELECT ''prix_original existe deja''',
    'ALTER TABLE `approvisionnement_details` ADD COLUMN `prix_original` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `prix_unitaire`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'approvisionnement_details'
          AND COLUMN_NAME = 'devise_prix'
    ),
    'SELECT ''devise_prix existe deja''',
    'ALTER TABLE `approvisionnement_details` ADD COLUMN `devise_prix` VARCHAR(3) NOT NULL DEFAULT ''CDF'' AFTER `prix_original`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'approvisionnement_details'
          AND COLUMN_NAME = 'taux_change'
    ),
    'SELECT ''taux_change existe deja''',
    'ALTER TABLE `approvisionnement_details` ADD COLUMN `taux_change` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `devise_prix`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Les anciennes lignes utilisaient déjà la devise de base du système.
SET @base_devise = COALESCE(
    (SELECT `valeur` FROM `parametres` WHERE `cle` = 'devise_base' LIMIT 1),
    'CDF'
);
UPDATE `approvisionnement_details`
SET `prix_original` = `prix_caisse`,
    `devise_prix` = @base_devise
WHERE `prix_original` = 0
  AND `prix_caisse` <> 0;
