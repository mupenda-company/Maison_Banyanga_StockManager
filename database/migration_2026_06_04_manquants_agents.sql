CREATE TABLE IF NOT EXISTS manquants_agents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  agent_id INT UNSIGNED NOT NULL,
  produit_id INT UNSIGNED DEFAULT NULL,
  quantite_caisses DECIMAL(12,2) NOT NULL DEFAULT 0,
  montant DECIMAL(15,2) NOT NULL DEFAULT 0,
  date_manquant DATE NOT NULL,
  motif TEXT DEFAULT NULL,
  statut ENUM('ouvert','regle') NOT NULL DEFAULT 'ouvert',
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_manquants_agent_date (agent_id, date_manquant),
  KEY idx_manquants_produit (produit_id),
  CONSTRAINT fk_manquants_agent FOREIGN KEY (agent_id) REFERENCES users(id),
  CONSTRAINT fk_manquants_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL,
  CONSTRAINT fk_manquants_createur FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
