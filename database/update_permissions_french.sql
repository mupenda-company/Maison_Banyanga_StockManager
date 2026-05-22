-- Migration: Mettre à jour les permissions en français et ajouter finance
-- Exécuter ce script dans phpMyAdmin ou votre outil de gestion de base de données

-- Mettre à jour les codes de permission existants
UPDATE `permissions` SET `code` = 'dashboard.voir', `action` = 'voir' WHERE `code` = 'dashboard.view';
UPDATE `permissions` SET `code` = 'ventes.voir', `action` = 'voir' WHERE `code` = 'ventes.view';
UPDATE `permissions` SET `code` = 'ventes.creer', `action` = 'creer' WHERE `code` = 'ventes.create';
UPDATE `permissions` SET `code` = 'ventes.modifier', `action` = 'modifier' WHERE `code` = 'ventes.update';
UPDATE `permissions` SET `code` = 'ventes.supprimer', `action` = 'supprimer' WHERE `code` = 'ventes.delete';
UPDATE `permissions` SET `code` = 'clients.voir', `action` = 'voir' WHERE `code` = 'clients.view';
UPDATE `permissions` SET `code` = 'clients.creer', `action` = 'creer' WHERE `code` = 'clients.create';
UPDATE `permissions` SET `code` = 'clients.modifier', `action` = 'modifier' WHERE `code` = 'clients.update';
UPDATE `permissions` SET `code` = 'clients.supprimer', `action` = 'supprimer' WHERE `code` = 'clients.delete';
UPDATE `permissions` SET `code` = 'produits.voir', `action` = 'voir' WHERE `code` = 'produits.view';
UPDATE `permissions` SET `code` = 'produits.creer', `action` = 'creer' WHERE `code` = 'produits.create';
UPDATE `permissions` SET `code` = 'produits.modifier', `action` = 'modifier' WHERE `code` = 'produits.update';
UPDATE `permissions` SET `code` = 'produits.supprimer', `action` = 'supprimer' WHERE `code` = 'produits.delete';
UPDATE `permissions` SET `code` = 'stock.voir', `action` = 'voir' WHERE `code` = 'stock.view';
UPDATE `permissions` SET `code` = 'stock.gerer', `action` = 'gerer' WHERE `code` = 'stock.manage';
UPDATE `permissions` SET `code` = 'approvisionnements.voir', `action` = 'voir' WHERE `code` = 'approvisionnements.view';
UPDATE `permissions` SET `code` = 'approvisionnements.creer', `action` = 'creer' WHERE `code` = 'approvisionnements.create';
UPDATE `permissions` SET `code` = 'approvisionnements.modifier', `action` = 'modifier' WHERE `code` = 'approvisionnements.update';
UPDATE `permissions` SET `code` = 'approvisionnements.supprimer', `action` = 'supprimer' WHERE `code` = 'approvisionnements.delete';
UPDATE `permissions` SET `code` = 'missions.voir', `action` = 'voir' WHERE `code` = 'missions.view';
UPDATE `permissions` SET `code` = 'missions.creer', `action` = 'creer' WHERE `code` = 'missions.create';
UPDATE `permissions` SET `code` = 'missions.modifier', `action` = 'modifier' WHERE `code` = 'missions.update';
UPDATE `permissions` SET `code` = 'missions.supprimer', `action` = 'supprimer' WHERE `code` = 'missions.delete';
UPDATE `permissions` SET `code` = 'missions.gerer', `action` = 'gerer' WHERE `code` = 'missions.manage';
UPDATE `permissions` SET `code` = 'vehicules.voir', `action` = 'voir' WHERE `code` = 'vehicules.view';
UPDATE `permissions` SET `code` = 'vehicules.gerer', `action` = 'gerer' WHERE `code` = 'vehicules.manage';
UPDATE `permissions` SET `code` = 'depenses.voir', `action` = 'voir' WHERE `code` = 'depenses.view';
UPDATE `permissions` SET `code` = 'depenses.creer', `action` = 'creer' WHERE `code` = 'depenses.create';
UPDATE `permissions` SET `code` = 'depenses.supprimer', `action` = 'supprimer' WHERE `code` = 'depenses.delete';
UPDATE `permissions` SET `code` = 'emballages.voir', `action` = 'voir' WHERE `code` = 'emballages.view';
UPDATE `permissions` SET `code` = 'emballages.gerer', `action` = 'gerer' WHERE `code` = 'emballages.manage';
UPDATE `permissions` SET `code` = 'pertes.voir', `action` = 'voir' WHERE `code` = 'pertes.view';
UPDATE `permissions` SET `code` = 'pertes.creer', `action` = 'creer' WHERE `code` = 'pertes.create';
UPDATE `permissions` SET `code` = 'rapports.voir', `action` = 'voir' WHERE `code` = 'rapports.view';
UPDATE `permissions` SET `code` = 'admin.voir', `action` = 'voir' WHERE `code` = 'admin.view';
UPDATE `permissions` SET `code` = 'admin.utilisateurs', `action` = 'utilisateurs' WHERE `code` = 'admin.users';
UPDATE `permissions` SET `code` = 'admin.parametres', `action` = 'parametres' WHERE `code` = 'admin.settings';

-- Ajouter les permissions finance
INSERT INTO `permissions` (`code`, `module`, `action`, `description`) VALUES
('finance.voir', 'finance', 'voir', 'Voir la finance'),
('finance.creer', 'finance', 'creer', 'Créer une opération financière'),
('finance.modifier', 'finance', 'modifier', 'Modifier une opération financière'),
('finance.supprimer', 'finance', 'supprimer', 'Supprimer une opération financière');

-- Assigner les permissions finance au rôle admin
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p 
WHERE r.nom = 'admin' AND p.code IN ('finance.voir', 'finance.creer', 'finance.modifier', 'finance.supprimer')
ON DUPLICATE KEY UPDATE role_id = role_id;
