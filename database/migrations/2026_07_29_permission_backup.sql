-- Permission de sauvegarde, attribuee uniquement au proprietaire par defaut.
-- Le proprietaire peut ensuite l'accorder a un autre role depuis l'application.

INSERT INTO permissions (code, module, action, description)
VALUES ('admin.backup', 'admin', 'backup', 'Créer et télécharger une sauvegarde de la base de données')
ON DUPLICATE KEY UPDATE
    module = VALUES(module),
    action = VALUES(action),
    description = VALUES(description);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.code = 'admin.backup'
WHERE r.nom = 'proprietaire';
