-- Migration réexécutable : approvisionnements par injection, produit seul ou emballage seul.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'approvisionnement_details' AND COLUMN_NAME = 'prix_produit'),
    'SELECT ''prix_produit existe deja''',
    'ALTER TABLE `approvisionnement_details` ADD COLUMN `prix_produit` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `prix_unitaire`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'approvisionnement_details' AND COLUMN_NAME = 'prix_emballage'),
    'SELECT ''prix_emballage existe deja''',
    'ALTER TABLE `approvisionnement_details` ADD COLUMN `prix_emballage` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `prix_produit`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Les anciennes lignes « vente » correspondent aux achats de produits seulement.
UPDATE `approvisionnement_details`
SET `type_chargement` = 'produit'
WHERE COALESCE(`type_chargement`, 'vente') = 'vente';

ALTER TABLE `approvisionnement_details`
MODIFY COLUMN `type_chargement` VARCHAR(20) NOT NULL DEFAULT 'produit';

UPDATE `approvisionnement_details`
SET `prix_produit` = `prix_caisse`,
    `prix_emballage` = 0
WHERE `prix_produit` = 0
  AND `type_chargement` = 'produit';
