ALTER TABLE `missions`
  ADD COLUMN `manquant_id` INT UNSIGNED NULL AFTER `caisses_vides_retournees`,
  ADD COLUMN `caisses_retournees_physiques` INT NOT NULL DEFAULT 0 AFTER `manquant_id`,
  ADD COLUMN `caisses_vides_retournees_physiques` INT NOT NULL DEFAULT 0 AFTER `caisses_retournees_physiques`,
  ADD COLUMN `ecart_caisses_pleines` INT NOT NULL DEFAULT 0 AFTER `caisses_vides_retournees_physiques`,
  ADD COLUMN `ecart_caisses_vides` INT NOT NULL DEFAULT 0 AFTER `ecart_caisses_pleines`,
  ADD COLUMN `montant_retour_physique` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `montant_encaisse`,
  ADD COLUMN `ecart_montant_systeme` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `montant_retour_physique`;

ALTER TABLE `mission_chargements`
  ADD COLUMN `caisses_retournees_physiques` INT NOT NULL DEFAULT 0 AFTER `quantite_retournee`,
  ADD COLUMN `caisses_vides_retournees_physiques` INT NOT NULL DEFAULT 0 AFTER `caisses_retournees_physiques`;

ALTER TABLE `manquants_agents`
  ADD COLUMN `mission_id` INT UNSIGNED NULL AFTER `agent_id`,
  ADD COLUMN `type_manquant` VARCHAR(30) NOT NULL DEFAULT 'manuel' AFTER `mission_id`,
  ADD COLUMN `quantite_emballages` DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER `quantite_caisses`,
  ADD INDEX `idx_manquants_mission` (`mission_id`),
  ADD INDEX `idx_manquants_type` (`type_manquant`);
