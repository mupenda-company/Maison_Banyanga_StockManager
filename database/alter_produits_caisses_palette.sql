-- Ajout du champ caisses_par_palette dans la table produits
-- Date: 2026-05-23

ALTER TABLE `produits` 
ADD COLUMN `caisses_par_palette` INT NOT NULL DEFAULT 0 
AFTER `bouteilles_par_caisses` 
COMMENT 'Nombre de caisses par palette';