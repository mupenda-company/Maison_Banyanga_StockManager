-- Suivi du montant laissé chez chaque fournisseur.
-- Migration idempotente.

CREATE TABLE IF NOT EXISTS soldes_fournisseurs (
    fournisseur VARCHAR(150) NOT NULL,
    solde DECIMAL(15,2) NOT NULL DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (fournisseur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @db_name = DATABASE();

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'approvisionnements'
       AND COLUMN_NAME = 'solde_fournisseur_avant') = 0,
    'ALTER TABLE approvisionnements ADD solde_fournisseur_avant DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER total_ht',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'approvisionnements'
       AND COLUMN_NAME = 'montant_depose_fournisseur') = 0,
    'ALTER TABLE approvisionnements ADD montant_depose_fournisseur DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER solde_fournisseur_avant',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'approvisionnements'
       AND COLUMN_NAME = 'montant_utilise_fournisseur') = 0,
    'ALTER TABLE approvisionnements ADD montant_utilise_fournisseur DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_depose_fournisseur',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'approvisionnements'
       AND COLUMN_NAME = 'solde_fournisseur_apres') = 0,
    'ALTER TABLE approvisionnements ADD solde_fournisseur_apres DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant_utilise_fournisseur',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
