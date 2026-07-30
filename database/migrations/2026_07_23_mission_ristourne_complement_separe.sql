-- Livraison separee du produit principal et du produit de complement.
-- Migration idempotente.

SET @column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mission_ristournes'
      AND COLUMN_NAME = 'produit_complement_id'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE mission_ristournes ADD produit_complement_id INT UNSIGNED NULL AFTER produit_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mission_ristournes'
      AND COLUMN_NAME = 'caisses_complement_prevues'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE mission_ristournes ADD caisses_complement_prevues INT NOT NULL DEFAULT 0 AFTER bouteilles_prevues',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mission_ristournes'
      AND COLUMN_NAME = 'caisses_complement_livrees'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE mission_ristournes ADD caisses_complement_livrees INT NOT NULL DEFAULT 0 AFTER bouteilles_livrees',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mission_ristournes'
      AND COLUMN_NAME = 'bouteilles_complement_livrees'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE mission_ristournes ADD bouteilles_complement_livrees INT NOT NULL DEFAULT 0 AFTER caisses_complement_livrees',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mission_ristournes'
      AND INDEX_NAME = 'idx_mission_ristournes_produit_complement'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE mission_ristournes ADD KEY idx_mission_ristournes_produit_complement (produit_complement_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'mission_ristournes'
      AND COLUMN_NAME = 'produit_complement_id'
      AND REFERENCED_TABLE_NAME = 'produits'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE mission_ristournes ADD CONSTRAINT fk_mission_ristournes_produit_complement FOREIGN KEY (produit_complement_id) REFERENCES produits(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
