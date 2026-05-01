-- Ajouter le paramètre taux_change pour la conversion USD/CDF
INSERT INTO `parametres` (`cle`, `valeur`, `type`) 
VALUES ('taux_change', '2800', 'number')
ON DUPLICATE KEY UPDATE `valeur` = '2800';
