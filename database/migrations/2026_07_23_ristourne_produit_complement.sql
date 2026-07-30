-- Produit choisi pour recevoir le complement en argent de la ristourne.
-- Migration idempotente.

SET @column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ristournes'
      AND COLUMN_NAME = 'produit_complement_id'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE ristournes ADD produit_complement_id INT UNSIGNED NULL AFTER produits_ristourne',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ristournes'
      AND INDEX_NAME = 'idx_ristournes_produit_complement'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE ristournes ADD KEY idx_ristournes_produit_complement (produit_complement_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ristournes'
      AND CONSTRAINT_NAME = 'fk_ristournes_produit_complement'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE ristournes ADD CONSTRAINT fk_ristournes_produit_complement FOREIGN KEY (produit_complement_id) REFERENCES produits(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
