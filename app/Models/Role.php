<?php

class Role extends Model
{
    protected $table = 'roles';
    protected $fillable = ['nom', 'description', 'is_system'];

    public function getAllWithPermissions()
    {
        $roles = $this->all('nom');
        foreach ($roles as &$role) {
            $role['permissions'] = $this->getPermissions($role['id']);
        }
        return $roles;
    }

    public function getPermissions($roleId)
    {
        return $this->db->fetchAll(
            "SELECT p.* FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id
             ORDER BY p.module, p.action",
            ['role_id' => $roleId]
        );
    }

    public function getPermissionCodes($roleId)
    {
        $rows = $this->db->fetchAll(
            "SELECT p.code FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id",
            ['role_id' => $roleId]
        );
        return array_column($rows, 'code');
    }

    public function syncPermissions($roleId, array $permissionIds)
    {
        $this->db->delete('role_permissions', 'role_id = :rid', ['rid' => $roleId]);
        foreach ($permissionIds as $pid) {
            $this->db->insert('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => (int) $pid
            ]);
        }
    }

    public function getUserRoles($userId)
    {
        return $this->db->fetchAll(
            "SELECT r.* FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :uid",
            ['uid' => $userId]
        );
    }

    public function getUserPermissionCodes($userId)
    {
        $rows = $this->db->fetchAll(
            "SELECT DISTINCT p.code FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :uid",
            ['uid' => $userId]
        );
        return array_column($rows, 'code');
    }

    public function getAllPermissionCodes(): array
    {
        $rows = $this->db->fetchAll("SELECT code FROM permissions ORDER BY code");
        return array_column($rows, 'code');
    }

    public function userHasRole(int $userId, string $roleName): bool
    {
        return (bool) $this->db->fetchColumn(
            "SELECT COUNT(*)
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id AND r.nom = :role_name",
            ['user_id' => $userId, 'role_name' => $roleName]
        );
    }

    public function isOwnerRole(int $roleId): bool
    {
        return (bool) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM roles WHERE id = :id AND nom = 'proprietaire'",
            ['id' => $roleId]
        );
    }

    public function getVisibleRoles(bool $includeOwner): array
    {
        $sql = "SELECT * FROM roles";
        if (!$includeOwner) {
            $sql .= " WHERE nom <> 'proprietaire'";
        }
        return $this->db->fetchAll($sql . " ORDER BY nom");
    }

    public function assignRole($userId, $roleId)
    {
        $this->db->insert('user_roles', [
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
    }

    public function syncUserRoles($userId, array $roleIds)
    {
        try {
            $this->db->beginTransaction();
            
            $this->db->delete('user_roles', 'user_id = :uid', ['uid' => $userId]);
            
            foreach ($roleIds as $rid) {
                $rid = (int) $rid;
                if ($rid > 0) {
                    $this->db->insert('user_roles', [
                        'user_id' => $userId,
                        'role_id' => $rid
                    ]);
                }
            }
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function nomExists($nom, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE nom = :nom";
        $params = ['nom' => $nom];
        if ($excludeId) {
            $sql .= " AND id != :eid";
            $params['eid'] = $excludeId;
        }
        return $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Supprimer un rôle en gérant les contraintes de clé étrangère
     */
    public function deleteRole($id)
    {
        try {
            $this->db->beginTransaction();
            
            // Supprimer les associations de permissions
            $this->db->delete('role_permissions', 'role_id = :rid', ['rid' => $id]);
            
            // Supprimer les associations d'utilisateurs
            $this->db->delete('user_roles', 'role_id = :rid', ['rid' => $id]);
            
            // Supprimer le rôle
            $this->db->delete($this->table, 'id = :id', ['id' => $id]);
            
            $this->db->commit();
            
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
