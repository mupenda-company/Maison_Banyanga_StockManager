-- Migration: emballages recus separes des produits vendus
-- Permet l'interchange: les vides recus peuvent etre de produits differents des produits vendus.

CREATE TABLE IF NOT EXISTS vente_emballages_recus (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vente_id INT UNSIGNED NOT NULL,
    produit_id INT UNSIGNED NOT NULL,
    caisses_recues INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY vente_id (vente_id),
    KEY produit_id (produit_id),
    CONSTRAINT vente_emballages_recus_ibfk_1 FOREIGN KEY (vente_id) REFERENCES ventes (id) ON DELETE CASCADE,
    CONSTRAINT vente_emballages_recus_ibfk_2 FOREIGN KEY (produit_id) REFERENCES produits (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

