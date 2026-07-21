<?php

class RoleController extends Controller
{
    private $roleModel;
    private $permissionModel;

    public function __construct()
    {
        parent::__construct();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    private function containsOwnerRole(array $roleIds): bool
    {
        foreach ($roleIds as $roleId) {
            if ($this->roleModel->isOwnerRole((int) $roleId)) {
                return true;
            }
        }
        return false;
    }

    private function requireRoleHierarchy(int $userId, array $roleIds): void
    {
        $userModel = new User();
        $targetIsOwner = $userModel->isOwner($userId);
        if ($targetIsOwner && !is_owner()) {
            $this->error('Ce compte est hors de votre niveau de gestion.', 403);
        }
        if (!is_owner() && $this->containsOwnerRole($roleIds)) {
            $this->error('Vous ne pouvez pas attribuer ce niveau de role.', 403);
        }
        if ($targetIsOwner && !$this->containsOwnerRole($roleIds)) {
            $this->error('Le role proprietaire ne peut pas etre retire de ce compte.', 403);
        }
    }

    private function requireAssignablePermissions(array $permissionIds): void
    {
        if (is_owner()) return;
        foreach ($permissionIds as $permissionId) {
            if ($this->permissionModel->idHasCode((int) $permissionId, 'clients.qr')) {
                $this->error('La permission QR clients est reservee au proprietaire.', 403);
            }
        }
    }

    public function index()
    {
        $this->requirePermission('admin.roles');

        $roles = $this->roleModel->getAllWithPermissions();
        if (!is_owner()) {
            $roles = array_values(array_filter($roles, fn($role) => ($role['nom'] ?? '') !== 'proprietaire'));
            foreach ($roles as &$visibleRole) {
                $visibleRole['permissions'] = array_values(array_filter(
                    $visibleRole['permissions'] ?? [],
                    fn($permission) => ($permission['code'] ?? '') !== 'clients.qr'
                ));
            }
            unset($visibleRole);
        }
        $permissionsGrouped = $this->permissionModel->getAllGroupedByModule();
        if (!is_owner()) {
            foreach ($permissionsGrouped as &$modulePermissions) {
                $modulePermissions = array_values(array_filter(
                    $modulePermissions,
                    fn($permission) => ($permission['code'] ?? '') !== 'clients.qr'
                ));
            }
            unset($modulePermissions);
        }

        if ($this->isAjax()) {
            return $this->success(['roles' => $roles, 'permissionsGrouped' => $permissionsGrouped]);
        }

        $this->view('admin/roles', [
            'roles' => $roles,
            'permissionsGrouped' => $permissionsGrouped
        ]);
    }

    public function store()
    {
        $this->requirePermission('admin.roles');

        $data = $this->getJsonInput();

        $errors = $this->validate($data, [
            'nom' => 'required',
            'description' => ''
        ]);

        if (!empty($errors)) {
            return $this->error('Erreurs de validation', 422, $errors);
        }

        if ($this->roleModel->nomExists($data['nom'])) {
            return $this->error('Ce nom de rôle existe déjà', 422);
        }

        $id = $this->roleModel->create([
            'nom' => trim($data['nom']),
            'description' => trim($data['description'] ?? ''),
            'is_system' => 0
        ]);

        $permIds = $data['permissionIds'] ?? $data['permissions'] ?? [];
        if (is_array($permIds)) {
            $this->requireAssignablePermissions($permIds);
            $this->roleModel->syncPermissions($id, $permIds);
        }

        $role = $this->roleModel->find($id);
        $role['permissions'] = $this->roleModel->getPermissionCodes($id);

        return $this->success($role, 'Rôle créé avec succès');
    }

    public function update($id)
    {
        $this->requirePermission('admin.roles');

        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->error('Rôle non trouvé', 404);
        }

        if (($role['nom'] ?? '') === 'proprietaire') {
            return $this->error('Le role proprietaire est protege.', 403);
        }

        $data = $this->getJsonInput();

        if (isset($data['nom']) && $data['nom'] !== $role['nom']) {
            if ($this->roleModel->nomExists($data['nom'], $id)) {
                return $this->error('Ce nom de rôle existe déjà', 422);
            }
        }

        $updateData = array_intersect_key($data, array_flip(['nom', 'description']));
        if (!empty($updateData)) {
            $this->roleModel->update($id, $updateData);
        }

        $hasPermissions = array_key_exists('permissionIds', $data) || array_key_exists('permissions', $data);
        $permIds = $data['permissionIds'] ?? $data['permissions'] ?? [];
        if ($hasPermissions && is_array($permIds)) {
            $this->requireAssignablePermissions($permIds);
            $this->roleModel->syncPermissions($id, $permIds);
        }

        // Rafraîchir les permissions en session si l'utilisateur courant a ce rôle
        $this->refreshSessionPermissions();

        $role = $this->roleModel->find($id);
        $role['permissions'] = $this->roleModel->getPermissionCodes($id);
        $role['_session_permissions'] = $_SESSION['user_permissions'] ?? [];

        return $this->success($role, 'Rôle mis à jour avec succès');
    }

    public function delete($id)
    {
        $this->requirePermission('admin.roles');

        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->error('Rôle non trouvé', 404);
        }

        if ($role['is_system']) {
            return $this->error('Les rôles système ne peuvent pas être supprimés', 400);
        }

        $this->roleModel->deleteRole($id);

        return $this->success(null, 'Rôle supprimé avec succès');
    }

    public function syncUserRoles($userId)
    {
        try {
            $this->requirePermission('admin.utilisateurs');

            $data = $this->getJsonInput();

            if (!isset($data['role_ids']) || !is_array($data['role_ids'])) {
                return $this->error('role_ids est requis', 422);
            }

            $this->requireRoleHierarchy((int) $userId, $data['role_ids']);

            $this->roleModel->syncUserRoles($userId, $data['role_ids']);

            // Rafraîchir les permissions en session si l'utilisateur courant est modifié
            if ($userId == ($_SESSION['user_id'] ?? null)) {
                $this->refreshSessionPermissions();
            }

            $userModel = new User();
            $user = $userModel->find($userId);
            if (!$user) {
                return $this->error('Utilisateur non trouvé', 404);
            }

            $roles = $this->roleModel->getUserRoles($userId);
            $permissionCodes = $this->roleModel->getUserPermissionCodes($userId);

            return $this->success([
                'roles' => $roles,
                'permissions' => $permissionCodes,
                '_session_permissions' => $_SESSION['user_permissions'] ?? []
            ], 'Rôles mis à jour');
        } catch (Exception $e) {
            error_log('syncUserRoles error: ' . $e->getMessage());
            return $this->error('Erreur lors de la synchronisation des rôles: ' . $e->getMessage(), 500);
        }
    }

    public function permissions()
    {
        $this->requirePermission('admin.roles');

        $grouped = $this->permissionModel->getAllGroupedByModule();
        return $this->success($grouped);
    }
}
