ALTER TABLE manquants_agents
  ADD COLUMN montant_paye DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER montant,
  ADD COLUMN date_reglement DATE DEFAULT NULL AFTER date_manquant,
  ADD COLUMN notes_reglement TEXT DEFAULT NULL AFTER motif,
  MODIFY statut ENUM('ouvert','partiel','paye','regle') NOT NULL DEFAULT 'ouvert';

CREATE TABLE IF NOT EXISTS manquant_paiements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  manquant_id INT UNSIGNED NOT NULL,
  montant DECIMAL(15,2) NOT NULL DEFAULT 0,
  date_paiement DATE NOT NULL,
  note TEXT DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_manquant_paiements_manquant (manquant_id),
  CONSTRAINT fk_manquant_paiements_manquant FOREIGN KEY (manquant_id) REFERENCES manquants_agents(id) ON DELETE CASCADE,
  CONSTRAINT fk_manquant_paiements_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE manquants_agents
SET montant_paye = CASE WHEN statut = 'regle' THEN montant ELSE montant_paye END,
    statut = CASE WHEN statut = 'regle' THEN 'paye' ELSE statut END
WHERE statut = 'regle';