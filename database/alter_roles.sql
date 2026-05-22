-- Migration: Rôles et permissions granulaires
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(100) NOT NULL UNIQUE,
    `module` VARCHAR(50) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rôles système
INSERT INTO `roles` (`nom`, `description`, `is_system`) VALUES
('admin', 'Administrateur - accès complet', 1),
('magasinier', 'Magasinier - gestion stock et approvisionnement', 1),
('vendeur', 'Vendeur - création de ventes uniquement', 1);

-- Permissions par module/action
INSERT INTO `permissions` (`code`, `module`, `action`, `description`) VALUES
('dashboard.voir', 'dashboard', 'voir', 'Voir le tableau de bord'),
('ventes.voir', 'ventes', 'voir', 'Voir les ventes'),
('ventes.creer', 'ventes', 'creer', 'Créer une vente'),
('ventes.modifier', 'ventes', 'modifier', 'Modifier une vente'),
('ventes.supprimer', 'ventes', 'supprimer', 'Annuler une vente'),
('clients.voir', 'clients', 'voir', 'Voir les clients'),
('clients.creer', 'clients', 'creer', 'Créer un client'),
('clients.modifier', 'clients', 'modifier', 'Modifier un client'),
('clients.supprimer', 'clients', 'supprimer', 'Supprimer un client'),
('produits.voir', 'produits', 'voir', 'Voir les produits'),
('produits.creer', 'produits', 'creer', 'Créer un produit'),
('produits.modifier', 'produits', 'modifier', 'Modifier un produit'),
('produits.supprimer', 'produits', 'supprimer', 'Supprimer un produit'),
('stock.voir', 'stock', 'voir', 'Voir le stock'),
('stock.gerer', 'stock', 'gerer', 'Gérer le stock (transferts, inventaire)'),
('approvisionnements.voir', 'approvisionnements', 'voir', 'Voir les approvisionnements'),
('approvisionnements.creer', 'approvisionnements', 'creer', 'Créer un approvisionnement'),
('approvisionnements.modifier', 'approvisionnements', 'modifier', 'Modifier un approvisionnement'),
('approvisionnements.supprimer', 'approvisionnements', 'supprimer', 'Annuler un approvisionnement'),
('missions.voir', 'missions', 'voir', 'Voir les missions'),
('missions.creer', 'missions', 'creer', 'Créer une mission'),
('missions.modifier', 'missions', 'modifier', 'Modifier une mission'),
('missions.supprimer', 'missions', 'supprimer', 'Annuler une mission'),
('missions.gerer', 'missions', 'gerer', 'Créer, modifier, terminer des missions'),
('vehicules.voir', 'vehicules', 'voir', 'Voir les véhicules'),
('vehicules.gerer', 'vehicules', 'gerer', 'Gérer les véhicules'),
('depenses.voir', 'depenses', 'voir', 'Voir les dépenses'),
('depenses.creer', 'depenses', 'creer', 'Créer une dépense'),
('depenses.supprimer', 'depenses', 'supprimer', 'Supprimer une dépense'),
('emballages.voir', 'emballages', 'voir', 'Voir les emballages'),
('emballages.gerer', 'emballages', 'gerer', 'Gérer les emballages'),
('pertes.voir', 'pertes', 'voir', 'Voir les pertes'),
('pertes.creer', 'pertes', 'creer', 'Déclarer une perte'),
('rapports.voir', 'rapports', 'voir', 'Voir les rapports'),
('finance.voir', 'finance', 'voir', 'Voir la finance'),
('finance.creer', 'finance', 'creer', 'Créer une opération financière'),
('finance.modifier', 'finance', 'modifier', 'Modifier une opération financière'),
('finance.supprimer', 'finance', 'supprimer', 'Supprimer une opération financière'),
('admin.voir', 'admin', 'voir', 'Accès administration'),
('admin.utilisateurs', 'admin', 'utilisateurs', 'Gérer les utilisateurs'),
('admin.parametres', 'admin', 'parametres', 'Gérer les paramètres'),
('admin.roles', 'admin', 'roles', 'Gérer les rôles et permissions');

-- Admin = toutes les permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p WHERE r.nom = 'admin';

-- Magasinier
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nom = 'magasinier' AND p.code IN (
    'dashboard.voir',
    'ventes.voir', 'ventes.creer', 'ventes.modifier', 'ventes.supprimer',
    'clients.voir', 'clients.creer', 'clients.modifier',
    'produits.voir',
    'stock.voir', 'stock.gerer',
    'approvisionnements.voir', 'approvisionnements.creer', 'approvisionnements.modifier', 'approvisionnements.supprimer',
    'missions.voir', 'missions.creer', 'missions.modifier', 'missions.supprimer', 'missions.gerer',
    'pertes.voir', 'pertes.creer',
    'vehicules.voir', 'vehicules.gerer',
    'emballages.voir', 'emballages.gerer',
    'rapports.voir'
);

-- Vendeur
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nom = 'vendeur' AND p.code IN (
    'ventes.voir', 'ventes.creer', 'ventes.modifier',
    'clients.voir', 'clients.creer', 'clients.modifier',
    'produits.voir'
);

-- Assigner les rôles aux utilisateurs existants selon leur champ `role`
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id FROM `users` u, `roles` r WHERE u.role = r.nom;
