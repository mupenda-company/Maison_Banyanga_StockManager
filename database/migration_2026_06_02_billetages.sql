-- Migration: billetage des ventes et fins de mission

CREATE TABLE IF NOT EXISTS billetages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference_type ENUM('vente','mission') NOT NULL,
    reference_id INT UNSIGNED NOT NULL,
    devise ENUM('CDF','USD') NOT NULL,
    coupure DECIMAL(12,2) NOT NULL,
    quantite INT NOT NULL DEFAULT 0,
    montant_base DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    taux_change DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY reference_idx (reference_type, reference_id),
    KEY created_by (created_by),
    CONSTRAINT billetages_ibfk_1 FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
