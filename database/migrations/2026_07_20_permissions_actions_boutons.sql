-- Autorisations granulaires pour les boutons et actions sensibles.
-- Cette migration est idempotente et conserve les acces existants en heritant
-- chaque nouvelle autorisation de l'ancienne autorisation plus generale.

START TRANSACTION;

INSERT IGNORE INTO `permissions` (`code`, `module`, `action`, `description`) VALUES
('clients.imprimer', 'clients', 'imprimer', 'Imprimer la liste des clients'),
('clients.exporter', 'clients', 'exporter', 'Exporter la liste des clients'),
('stock.mouvements', 'stock', 'mouvements', 'Voir les mouvements du stock'),
('stock.imprimer', 'stock', 'imprimer', 'Imprimer les donnees du stock'),
('stock.exporter', 'stock', 'exporter', 'Exporter les donnees du stock'),
('stock.transferer', 'stock', 'transferer', 'Transferer du stock'),
('stock.inventaire', 'stock', 'inventaire', 'Realiser un inventaire du stock'),
('stock.corriger', 'stock', 'corriger', 'Corriger le stock'),
('approvisionnements.imprimer', 'approvisionnements', 'imprimer', 'Imprimer un approvisionnement'),
('approvisionnements.exporter', 'approvisionnements', 'exporter', 'Exporter un approvisionnement'),
('approvisionnements.rembourser', 'approvisionnements', 'rembourser', 'Rembourser une dette d approvisionnement'),
('emballages.imprimer', 'emballages', 'imprimer', 'Imprimer les donnees des emballages'),
('emballages.exporter', 'emballages', 'exporter', 'Exporter les donnees des emballages'),
('emballages.inventaire', 'emballages', 'inventaire', 'Realiser un inventaire des emballages'),
('emballages.transferer', 'emballages', 'transferer', 'Transferer des emballages'),
('emballages.emprunter', 'emballages', 'emprunter', 'Creer un emprunt d emballages'),
('emballages.modifier', 'emballages', 'modifier', 'Modifier un emprunt d emballages'),
('emballages.supprimer', 'emballages', 'supprimer', 'Supprimer un emprunt d emballages'),
('emballages.rembourser', 'emballages', 'rembourser', 'Rembourser un emprunt d emballages'),
('ventes.imprimer', 'ventes', 'imprimer', 'Imprimer les ventes'),
('ventes.exporter', 'ventes', 'exporter', 'Exporter les ventes'),
('vehicules.imprimer', 'vehicules', 'imprimer', 'Imprimer la fiche d un vehicule'),
('vehicules.creer', 'vehicules', 'creer', 'Creer un vehicule'),
('vehicules.modifier', 'vehicules', 'modifier', 'Modifier un vehicule'),
('vehicules.supprimer', 'vehicules', 'supprimer', 'Supprimer un vehicule'),
('vehicules.inventaire', 'vehicules', 'inventaire', 'Realiser l inventaire d un vehicule'),
('vehicules.transferer', 'vehicules', 'transferer', 'Transferer des produits entre vehicules'),
('vehicules.retour_emballages', 'vehicules', 'retour_emballages', 'Retourner les emballages d un vehicule'),
('missions.imprimer', 'missions', 'imprimer', 'Imprimer une mission ou une facture'),
('missions.terminer', 'missions', 'terminer', 'Terminer une mission'),
('pertes.imprimer', 'pertes', 'imprimer', 'Imprimer les pertes'),
('pertes.exporter', 'pertes', 'exporter', 'Exporter les pertes'),
('pertes.modifier', 'pertes', 'modifier', 'Modifier une perte'),
('pertes.supprimer', 'pertes', 'supprimer', 'Supprimer une perte'),
('depenses.modifier', 'depenses', 'modifier', 'Modifier une depense'),
('depenses.imprimer', 'depenses', 'imprimer', 'Imprimer les depenses'),
('rapports.imprimer', 'rapports', 'imprimer', 'Imprimer les rapports'),
('rapports.exporter', 'rapports', 'exporter', 'Exporter les rapports'),
('finance.imprimer', 'finance', 'imprimer', 'Imprimer les donnees financieres'),
('finance.exporter', 'finance', 'exporter', 'Exporter les donnees financieres'),
('ristournes.voir', 'ristournes', 'voir', 'Voir les ristournes'),
('ristournes.calculer', 'ristournes', 'calculer', 'Calculer les ristournes'),
('ristournes.payer', 'ristournes', 'payer', 'Payer une ristourne'),
('ristournes.paliers', 'ristournes', 'paliers', 'Gerer les paliers de ristourne'),
('ristournes.imprimer', 'ristournes', 'imprimer', 'Imprimer les ristournes'),
('ristournes.exporter', 'ristournes', 'exporter', 'Exporter les ristournes'),
('objectifs.voir', 'objectifs', 'voir', 'Voir les objectifs'),
('objectifs.gerer', 'objectifs', 'gerer', 'Modifier les objectifs'),
('objectifs.imprimer', 'objectifs', 'imprimer', 'Imprimer les objectifs'),
('objectifs.exporter', 'objectifs', 'exporter', 'Exporter les objectifs'),
('manquants.voir', 'manquants', 'voir', 'Voir les manquants'),
('manquants.creer', 'manquants', 'creer', 'Creer un manquant'),
('manquants.modifier', 'manquants', 'modifier', 'Modifier un manquant'),
('manquants.supprimer', 'manquants', 'supprimer', 'Supprimer un manquant'),
('manquants.payer', 'manquants', 'payer', 'Enregistrer un paiement de manquant'),
('manquants.imprimer', 'manquants', 'imprimer', 'Imprimer les manquants'),
('manquants.exporter', 'manquants', 'exporter', 'Exporter les manquants');

-- L'administrateur recoit automatiquement toutes les nouvelles autorisations.
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p
WHERE r.nom = 'admin';

-- Les autres roles gardent exactement leurs capacites actuelles. Ils pourront
-- ensuite etre affines depuis Administration > Roles et permissions.
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT rp.role_id, child.id
FROM `role_permissions` rp
JOIN `permissions` parent ON parent.id = rp.permission_id
JOIN (
    SELECT 'clients.voir' parent_code, 'clients.imprimer' child_code UNION ALL
    SELECT 'clients.voir', 'clients.exporter' UNION ALL
    SELECT 'stock.voir', 'stock.mouvements' UNION ALL
    SELECT 'stock.voir', 'stock.imprimer' UNION ALL
    SELECT 'stock.voir', 'stock.exporter' UNION ALL
    SELECT 'stock.gerer', 'stock.transferer' UNION ALL
    SELECT 'stock.gerer', 'stock.inventaire' UNION ALL
    SELECT 'stock.gerer', 'stock.corriger' UNION ALL
    SELECT 'approvisionnements.voir', 'approvisionnements.imprimer' UNION ALL
    SELECT 'approvisionnements.voir', 'approvisionnements.exporter' UNION ALL
    SELECT 'approvisionnements.modifier', 'approvisionnements.rembourser' UNION ALL
    SELECT 'emballages.voir', 'emballages.imprimer' UNION ALL
    SELECT 'emballages.voir', 'emballages.exporter' UNION ALL
    SELECT 'emballages.gerer', 'emballages.inventaire' UNION ALL
    SELECT 'emballages.gerer', 'emballages.transferer' UNION ALL
    SELECT 'emballages.gerer', 'emballages.emprunter' UNION ALL
    SELECT 'emballages.gerer', 'emballages.modifier' UNION ALL
    SELECT 'emballages.gerer', 'emballages.supprimer' UNION ALL
    SELECT 'emballages.gerer', 'emballages.rembourser' UNION ALL
    SELECT 'ventes.voir', 'ventes.imprimer' UNION ALL
    SELECT 'ventes.voir', 'ventes.exporter' UNION ALL
    SELECT 'vehicules.voir', 'vehicules.imprimer' UNION ALL
    SELECT 'vehicules.gerer', 'vehicules.creer' UNION ALL
    SELECT 'vehicules.gerer', 'vehicules.modifier' UNION ALL
    SELECT 'vehicules.gerer', 'vehicules.supprimer' UNION ALL
    SELECT 'vehicules.gerer', 'vehicules.inventaire' UNION ALL
    SELECT 'vehicules.gerer', 'vehicules.transferer' UNION ALL
    SELECT 'vehicules.gerer', 'vehicules.retour_emballages' UNION ALL
    SELECT 'missions.voir', 'missions.imprimer' UNION ALL
    SELECT 'missions.gerer', 'missions.terminer' UNION ALL
    SELECT 'pertes.voir', 'pertes.imprimer' UNION ALL
    SELECT 'pertes.voir', 'pertes.exporter' UNION ALL
    SELECT 'pertes.creer', 'pertes.modifier' UNION ALL
    SELECT 'pertes.creer', 'pertes.supprimer' UNION ALL
    SELECT 'depenses.creer', 'depenses.modifier' UNION ALL
    SELECT 'depenses.voir', 'depenses.imprimer' UNION ALL
    SELECT 'rapports.voir', 'rapports.imprimer' UNION ALL
    SELECT 'rapports.voir', 'rapports.exporter' UNION ALL
    SELECT 'finance.voir', 'finance.imprimer' UNION ALL
    SELECT 'finance.voir', 'finance.exporter' UNION ALL
    SELECT 'admin.voir', 'ristournes.voir' UNION ALL
    SELECT 'admin.voir', 'ristournes.calculer' UNION ALL
    SELECT 'admin.voir', 'ristournes.payer' UNION ALL
    SELECT 'admin.voir', 'ristournes.paliers' UNION ALL
    SELECT 'admin.voir', 'ristournes.imprimer' UNION ALL
    SELECT 'admin.voir', 'ristournes.exporter' UNION ALL
    SELECT 'admin.voir', 'objectifs.voir' UNION ALL
    SELECT 'admin.voir', 'objectifs.gerer' UNION ALL
    SELECT 'admin.voir', 'objectifs.imprimer' UNION ALL
    SELECT 'admin.voir', 'objectifs.exporter' UNION ALL
    SELECT 'pertes.voir', 'manquants.voir' UNION ALL
    SELECT 'pertes.creer', 'manquants.creer' UNION ALL
    SELECT 'pertes.creer', 'manquants.modifier' UNION ALL
    SELECT 'pertes.creer', 'manquants.supprimer' UNION ALL
    SELECT 'pertes.creer', 'manquants.payer' UNION ALL
    SELECT 'pertes.voir', 'manquants.imprimer' UNION ALL
    SELECT 'pertes.voir', 'manquants.exporter'
) mapping ON mapping.parent_code = parent.code
JOIN `permissions` child ON child.code = mapping.child_code;

COMMIT;
