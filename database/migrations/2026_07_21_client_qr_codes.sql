-- QR permanents et non devinables pour identifier les clients.
-- Migration idempotente.

SET @column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'qr_token'
);
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE clients ADD qr_token CHAR(32) NULL AFTER numero_client',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE clients
SET qr_token = LOWER(HEX(RANDOM_BYTES(16)))
WHERE qr_token IS NULL OR qr_token = '';

SET @index_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clients' AND INDEX_NAME = 'uk_clients_qr_token'
);
SET @sql = IF(@index_exists = 0,
    'ALTER TABLE clients ADD UNIQUE KEY uk_clients_qr_token (qr_token)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO permissions (code, module, action, description)
VALUES ('clients.qr', 'clients', 'qr', 'Generer et imprimer les QR codes clients');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT rp.role_id, child.id
FROM role_permissions rp
JOIN permissions parent ON parent.id = rp.permission_id AND parent.code = 'clients.voir'
JOIN permissions child ON child.code = 'clients.qr';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.code = 'clients.qr'
WHERE r.nom = 'admin';
