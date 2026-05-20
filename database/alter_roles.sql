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
('dashboard.view', 'dashboard', 'view', 'Voir le tableau de bord'),
('ventes.view', 'ventes', 'view', 'Voir les ventes'),
('ventes.create', 'ventes', 'create', 'Créer une vente'),
('ventes.update', 'ventes', 'update', 'Modifier une vente'),
('ventes.delete', 'ventes', 'delete', 'Annuler une vente'),
('clients.view', 'clients', 'view', 'Voir les clients'),
('clients.create', 'clients', 'create', 'Créer un client'),
('clients.update', 'clients', 'update', 'Modifier un client'),
('clients.delete', 'clients', 'delete', 'Supprimer un client'),
('produits.view', 'produits', 'view', 'Voir les produits'),
('produits.create', 'produits', 'create', 'Créer un produit'),
('produits.update', 'produits', 'update', 'Modifier un produit'),
('produits.delete', 'produits', 'delete', 'Supprimer un produit'),
('stock.view', 'stock', 'view', 'Voir le stock'),
('stock.manage', 'stock', 'manage', 'Gérer le stock (transferts, inventaire)'),
('approvisionnements.view', 'approvisionnements', 'view', 'Voir les approvisionnements'),
('approvisionnements.create', 'approvisionnements', 'create', 'Créer un approvisionnement'),
('approvisionnements.update', 'approvisionnements', 'update', 'Modifier un approvisionnement'),
('approvisionnements.delete', 'approvisionnements', 'delete', 'Annuler un approvisionnement'),
('missions.view', 'missions', 'view', 'Voir les missions'),
('missions.create', 'missions', 'create', 'Créer une mission'),
('missions.update', 'missions', 'update', 'Modifier une mission'),
('missions.delete', 'missions', 'delete', 'Annuler une mission'),
('vehicules.view', 'vehicules', 'view', 'Voir les véhicules'),
('vehicules.manage', 'vehicules', 'manage', 'Gérer les véhicules'),
('depenses.view', 'depenses', 'view', 'Voir les dépenses'),
('depenses.create', 'depenses', 'create', 'Créer une dépense'),
('depenses.delete', 'depenses', 'delete', 'Supprimer une dépense'),
('emballages.view', 'emballages', 'view', 'Voir les emballages'),
('emballages.manage', 'emballages', 'manage', 'Gérer les emballages'),
('rapports.view', 'rapports', 'view', 'Voir les rapports'),
('admin.view', 'admin', 'view', 'Accès administration'),
('admin.users', 'admin', 'users', 'Gérer les utilisateurs'),
('admin.settings', 'admin', 'settings', 'Gérer les paramètres'),
('admin.roles', 'admin', 'roles', 'Gérer les rôles et permissions'),
('missions.manage', 'missions', 'manage', 'Créer, modifier, terminer des missions'),
('pertes.view', 'pertes', 'view', 'Voir les pertes'),
('pertes.create', 'pertes', 'create', 'Déclarer une perte');

-- Admin = toutes les permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p WHERE r.nom = 'admin';

-- Magasinier
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nom = 'magasinier' AND p.code IN (
    'dashboard.view',
    'ventes.view', 'ventes.create', 'ventes.update', 'ventes.delete',
    'clients.view', 'clients.create', 'clients.update',
    'produits.view',
    'stock.view', 'stock.manage',
    'approvisionnements.view', 'approvisionnements.create', 'approvisionnements.update', 'approvisionnements.delete',
    'missions.view', 'missions.create', 'missions.update', 'missions.delete', 'missions.manage',
    'pertes.view', 'pertes.create',
    'vehicules.view', 'vehicules.manage',
    'emballages.view', 'emballages.manage',
    'rapports.view'
);

-- Vendeur
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nom = 'vendeur' AND p.code IN (
    'ventes.view', 'ventes.create', 'ventes.update',
    'clients.view', 'clients.create', 'clients.update',
    'produits.view'
);

-- Assigner les rôles aux utilisateurs existants selon leur champ `role`
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id FROM `users` u, `roles` r WHERE u.role = r.nom;
