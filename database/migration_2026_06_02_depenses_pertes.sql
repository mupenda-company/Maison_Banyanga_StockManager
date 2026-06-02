-- Migration: devise des depenses et agent responsable des pertes
-- A appliquer sur une base existante avant d'utiliser les nouveaux ecrans.

ALTER TABLE depenses
    ADD COLUMN devise ENUM('CDF','USD') NOT NULL DEFAULT 'CDF' AFTER montant,
    ADD COLUMN montant_original DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER devise,
    ADD COLUMN taux_change DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER montant_original;

UPDATE depenses
SET montant_original = montant,
    devise = 'CDF'
WHERE montant_original = 0;

ALTER TABLE pertes
    ADD COLUMN agent_id INT UNSIGNED NULL AFTER valeur_perte,
    ADD KEY agent_id (agent_id),
    ADD CONSTRAINT pertes_ibfk_agent FOREIGN KEY (agent_id) REFERENCES users (id) ON DELETE SET NULL;
