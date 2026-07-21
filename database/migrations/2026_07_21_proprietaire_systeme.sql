-- Compte proprietaire, hierarchie des roles et audit technique.
-- Migration idempotente.

INSERT INTO roles (nom, description, is_system)
VALUES ('proprietaire', 'Proprietaire du systeme - niveau maximal protege', 1)
ON DUPLICATE KEY UPDATE description = VALUES(description), is_system = 1;

INSERT INTO users (username, telephone, password, nom, prenom, role, actif)
SELECT
    'Mupenda.cd',
    'OWNER-MUPENDA-CD',
    '$2y$10$iC.prGdorLMx.4g9hHTiwOnc9gX8U8GdBHZr9PPKLEwBr3rvIZFyG',
    'Mupenda',
    'Proprietaire',
    'admin',
    1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'Mupenda.cd');

UPDATE users
SET password = '$2y$10$iC.prGdorLMx.4g9hHTiwOnc9gX8U8GdBHZr9PPKLEwBr3rvIZFyG', actif = 1
WHERE username = 'Mupenda.cd';

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u
JOIN roles r ON r.nom = 'proprietaire'
WHERE u.username = 'Mupenda.cd';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.nom = 'proprietaire';

DELETE rp FROM role_permissions rp
JOIN roles r ON r.id = rp.role_id
JOIN permissions p ON p.id = rp.permission_id
WHERE p.code = 'clients.qr' AND r.nom <> 'proprietaire';

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    methode VARCHAR(10) NOT NULL,
    route VARCHAR(255) NOT NULL,
    statut_http SMALLINT UNSIGNED NOT NULL DEFAULT 200,
    adresse_ip VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_created_at (created_at),
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
